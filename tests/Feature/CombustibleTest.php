<?php

use App\Models\User;
use App\Services\Combustible\Decimal;
use App\Services\Combustible\FuelService;
use App\Services\Combustible\Journal;
use App\Services\Combustible\Reports;
use Database\Seeders\CombustiblePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\Support\FuelSchema;

beforeEach(function () {
    FuelSchema::prepare();
    $this->service = app(FuelService::class);
    $this->db = DB::connection('fuel_test');
    $this->actor = ['id' => '123', 'name' => 'José de la Cruz '.str_repeat('ñ', 35).' 😀'];
    $this->input = [
        'FechaCarga' => '2026-02-10', 'FechaTrabajo' => '2026-01-31', 'Turno' => 'PM',
        'IdUnidad' => 1, 'Litros' => '80.001', 'Observaciones' => 'Prueba', 'TokenRegistro' => (string) Str::uuid(),
    ];
});

it('uses charge date rather than work date and decimal half up rounding', function () {
    $row = $this->service->create($this->input, $this->actor);
    expect($row->FechaPrecio)->toBe('2026-02-01')
        ->and(Decimal::normalize((string) $row->PrecioLitro))->toBe('25.123')
        ->and(Decimal::normalize((string) $row->Importe, 2))->toBe('2009.87')
        ->and(Decimal::amount('0.001', '25.000', '0'))->toBe('0.03');
});

it('blocks missing prices, inactive units, unknown discount convention and overflow', function () {
    foreach ([['FechaCarga' => '2025-12-31'], ['IdUnidad' => 2]] as $change) {
        expect(fn () => $this->service->create(array_replace($this->input, $change), $this->actor))->toThrow(ValidationException::class);
    }
    $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->update(['PorcDto' => '0.1000']);
    expect(fn () => $this->service->create($this->input, $this->actor))->toThrow(ValidationException::class);
    expect(fn () => Decimal::amount('99999999.999', '999999.999', '0'))->toThrow(ValidationException::class);
    expect($this->db->table('tblcargascombustible')->count())->toBe(0);
});

it('resolves exact active QR codes and refuses ambiguous matches', function () {
    expect($this->service->qr('001')->IdUnidad)->toBe(1);
    foreach (['1', ' 001', '002', 'unknown'] as $code) {
        expect(fn () => $this->service->qr($code))->toThrow(ValidationException::class);
    }
    $this->db->table('catunidades')->insert(['IdUnidad' => 3, 'Unidad' => '001', 'Estatus' => 'A']);
    expect(fn () => $this->service->qr('001'))->toThrow(ValidationException::class);
});

it('allows partial loads but rejects duplicates and token reuse with different data', function () {
    $first = $this->service->create($this->input, $this->actor);
    $retry = $this->service->create($this->input, $this->actor);
    expect($retry->IdCarga)->toBe($first->IdCarga);
    expect(fn () => $this->service->create(array_replace($this->input, ['Litros' => '20']), $this->actor))->toThrow(ValidationException::class);
    $second = array_replace($this->input, ['Litros' => '20', 'TokenRegistro' => (string) Str::uuid()]);
    $this->service->create($second, $this->actor);
    expect(fn () => $this->service->create(array_replace($second, ['TokenRegistro' => (string) Str::uuid()]), $this->actor))->toThrow(ValidationException::class);
    expect($this->db->table('tblcargascombustible')->count())->toBe(2);
});

it('cancels without deleting and releases only the duplicate control', function () {
    $row = $this->service->create($this->input, $this->actor);
    $args = ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($row), 'MotivoCancelacion' => 'Error de captura'];
    $this->service->cancel($row->IdCarga, $args, $this->actor);
    $this->service->cancel($row->IdCarga, $args, $this->actor);
    $cancelled = $this->db->table('tblcargascombustible')->first();
    expect($cancelled->Estatus)->toBe('C')->and($cancelled->ControlDuplicado)->toBeNull()->and($cancelled->MotivoCancelacion)->toBe('Error de captura');
    $this->service->create(array_replace($this->input, ['TokenRegistro' => (string) Str::uuid()]), $this->actor);
    expect($this->db->table('tblcargascombustible')->count())->toBe(2);
});

it('preserves historical price for work date turn and notes edits and recalculates changed litres', function () {
    $row = $this->service->create($this->input, $this->actor);
    $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->update(['Costo' => '30']);
    $edit = array_replace($this->input, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($row), 'FechaTrabajo' => '2026-02-02', 'Turno' => 'AM', 'Observaciones' => 'Corregido']);
    unset($edit['TokenRegistro']);
    $this->service->edit($row->IdCarga, $edit, $this->actor);
    $changed = $this->db->table('tblcargascombustible')->first();
    expect(Decimal::normalize((string) $changed->PrecioLitro))->toBe('25.123');
    $edit = array_replace($edit, ['Litros' => '40', 'Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($changed)]);
    $this->service->edit($row->IdCarga, $edit, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcargascombustible')->value('Importe'), 2))->toBe('1200.00');
    expect(fn () => $this->service->edit($row->IdCarga, array_replace($edit, ['Operacion' => (string) Str::uuid()]), $this->actor))->toThrow(ValidationException::class);
});

it('registers a daily notice per stable user id and marks omission or update', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-03-03 05:30:00', 'UTC')); // Aún marzo 2 en Mérida.
    expect($this->service->today())->toBe('2026-03-02');
    expect($this->service->notice($this->actor))->toBeTrue()
        ->and($this->service->notice($this->actor))->toBeFalse();
    $this->service->omitNotice($this->actor);
    expect($this->db->table('tblavisospreciodiesel')->value('Respuesta'))->toBe('OMITIDO');
    $other = ['id' => '456', 'name' => 'José'];
    expect($this->service->notice($other))->toBeTrue();
    $state = $this->service->priceState($this->db, $this->service->today());
    $this->service->savePrice(['Fecha' => $this->service->today(), 'Costo' => '30.123', 'Version' => $this->service->version($state), 'Recalcular' => 'no', 'Operacion' => (string) Str::uuid()], $other);
    expect($this->db->table('tblavisospreciodiesel')->where('IdUsuario', '456')->value('Respuesta'))->toBe('ACTUALIZADO')
        ->and($this->service->notice(['id' => '789', 'name' => 'Otro']))->toBeFalse();
});

it('requires the exact confirmed set and only recalculates active loads when selected', function () {
    $active = $this->service->create($this->input, $this->actor);
    $cancelled = $this->service->create(array_replace($this->input, ['Litros' => '20', 'TokenRegistro' => (string) Str::uuid()]), $this->actor);
    $this->service->cancel($cancelled->IdCarga, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($cancelled), 'MotivoCancelacion' => 'Error'], $this->actor);
    $state = $this->service->priceState($this->db, '2026-02-01');
    $args = ['Fecha' => '2026-02-01', 'Costo' => '30', 'Version' => $this->service->version($state), 'Recalcular' => 'no', 'Operacion' => (string) Str::uuid()];
    $this->service->savePrice($args, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcargascombustible')->where('IdCarga', $active->IdCarga)->value('PrecioLitro')))->toBe('25.123');
    expect(fn () => $this->service->savePrice(array_replace($args, ['Operacion' => (string) Str::uuid(), 'Recalcular' => 'si']), $this->actor))->toThrow(ValidationException::class);
    $args = array_replace($args, ['Operacion' => (string) Str::uuid(), 'Recalcular' => 'si', 'Version' => $this->service->version($this->service->priceState($this->db, '2026-02-01'))]);
    $this->service->savePrice($args, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcargascombustible')->where('IdCarga', $active->IdCarga)->value('PrecioLitro')))->toBe('30.000')
        ->and(Decimal::normalize((string) $this->db->table('tblcargascombustible')->where('IdCarga', $cancelled->IdCarga)->value('PrecioLitro')))->toBe('25.123');
});

it('preserves or inherits discounts and never deletes intermediate or used prices', function () {
    $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->update(['PorcDto' => '.1000']);
    $args = ['Fecha' => '2026-03-01', 'Costo' => '30', 'Version' => $this->service->version($this->service->priceState($this->db, '2026-03-01')), 'Recalcular' => 'no', 'Operacion' => (string) Str::uuid()];
    $this->service->savePrice($args, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcostos')->where('Fecha', '2026-03-01')->value('PorcDto'), 4))->toBe('0.1000');
    $last = $this->db->table('tblcostos')->where('Fecha', '2026-03-01')->first();
    $this->service->deletePrice('2026-03-01', ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($last)], $this->actor);
    expect($this->db->table('tblcostos')->count())->toBe(2);
    $first = $this->db->table('tblcostos')->where('Fecha', '2026-01-01')->first();
    expect(fn () => $this->service->deletePrice($first->Fecha, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($first)], $this->actor))->toThrow(ValidationException::class);
    $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->update(['PorcDto' => 0]);
    $row = $this->service->create($this->input, $this->actor);
    $this->service->cancel($row->IdCarga, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($row), 'MotivoCancelacion' => 'Error'], $this->actor);
    $last = $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->first();
    expect(fn () => $this->service->deletePrice($last->Fecha, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($last)], $this->actor))->toThrow(ValidationException::class);
});

it('stores complete correlated latin1-safe audit fragments below 256 bytes', function () {
    $this->service->create($this->input, $this->actor);
    $journal = app(Journal::class);
    $intent = $journal->read($this->db, $this->input['TokenRegistro']);
    expect($intent['actor'])->toBe($this->actor)->and($journal->done($this->db, $this->input['TokenRegistro']))->toBeTrue();
    foreach ($this->db->table('tblexcepciones')->get() as $row) {
        expect(strlen($row->Datos))->toBeLessThanOrEqual(255)
            ->and(mb_strlen($row->Usuario))->toBeLessThanOrEqual(30)
            ->and(preg_match('/[^\x00-\x7f]/', $row->Datos))->toBe(0);
    }
});

it('recovers a creation after final audit failure without a second insert', function () {
    $real = app(Journal::class);
    $broken = Mockery::mock(Journal::class)->makePartial();
    $broken->shouldReceive('finish')->once()->andThrow(new RuntimeException('Auditoría no disponible'));
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $broken);
    expect(fn () => $service->create($this->input, $this->actor))->toThrow(RuntimeException::class);
    expect($this->db->table('tblcargascombustible')->count())->toBe(1)
        ->and($real->done($this->db, $this->input['TokenRegistro']))->toBeFalse();
    $this->service->create($this->input, $this->actor);
    expect($this->db->table('tblcargascombustible')->count())->toBe(1)
        ->and($real->done($this->db, $this->input['TokenRegistro']))->toBeTrue();
});

it('filters totals beyond the page and exports valid xlsx without formulas', function () {
    for ($i = 1; $i <= 27; $i++) {
        $this->service->create(array_replace($this->input, ['Litros' => (string) $i, 'Observaciones' => '=1+1', 'TokenRegistro' => (string) Str::uuid()]), $this->actor);
    }
    $reports = app(Reports::class);
    $query = $reports->query(['desde' => '2026-01-31', 'hasta' => '2026-01-31', 'tipo_fecha' => 'trabajo']);
    $totals = $reports->totals($query);
    expect($query->paginate(25)->count())->toBe(25)->and($totals['Litros'])->toBe('378.000');
    $file = $reports->excel($query->get(), $totals);
    $zip = new ZipArchive;
    expect($zip->open($file))->toBeTrue();
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    expect($xml)->toContain('inlineStr', '=1+1', 'TOTALES ACTIVAS', '378.000')->not->toContain('<f>');
    expect(simplexml_load_string($xml))->not->toBeFalse();
    $zip->close();
    unlink($file);
});

it('seeds permissions idempotently without assigning roles and protects every endpoint', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $this->seed(CombustiblePermissionSeeder::class);
    expect(Permission::where('name', 'like', 'combustible_%')->count())->toBe(7);
    $user = User::factory()->create();
    $this->actingAs($user);
    foreach (['/combustible', '/combustible/crear', '/combustible/unidades', '/combustible/precios', '/combustible/auditoria', '/combustible/exportar/pdf'] as $url) {
        $this->getJson($url)->assertForbidden();
    }
    $user->givePermissionTo('combustible_ver');
    foreach ([['POST', '/combustible/cargas'], ['PUT', '/combustible/cargas/1'], ['POST', '/combustible/cargas/1/cancelar'], ['POST', '/combustible/precios'], ['DELETE', '/combustible/precios/2026-02-01'], ['POST', '/combustible/aviso'], ['POST', '/combustible/aviso/omitir']] as [$method, $url]) {
        $this->json($method, $url)->assertForbidden();
    }
    $this->get('/combustible?desde=2026-01-01&hasta=2026-03-01')->assertOk()->assertSee('Cargas de combustible')->assertDontSee('Revisar y confirmar');
});

it('renders capture prices confirmations audit and functional exports for authorized users', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::where('name', 'like', 'combustible_%')->get());
    $this->actingAs($user);
    $this->get('/combustible/crear')->assertOk()->assertSee('Escanear QR');
    $this->postJson('/combustible/cargas', $this->input)->assertOk();
    $id = $this->db->table('tblcargascombustible')->value('IdCarga');
    $this->get('/combustible/cargas/'.$id.'/editar')->assertOk()->assertSee('Precio histórico');
    $this->get('/combustible/precios')->assertOk();
    $this->get('/combustible/precios/revisar?Fecha=2026-02-01&Costo=30')->assertOk()->assertSee('1 cargas activas');
    $this->get('/combustible/auditoria')->assertOk()->assertSee('CREAR');
    $this->get('/combustible/exportar/pdf?desde=2026-02-01&hasta=2026-02-28')->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get('/combustible/exportar/xlsx?desde=2026-02-01&hasta=2026-02-28')->assertOk()->assertDownload('combustible.xlsx');
});

it('rejects forged and stale price confirmations and checks validation at endpoints', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::where('name', 'like', 'combustible_%')->get());
    $this->actingAs($user);
    $this->getJson('/combustible/unidades?qr=%20001')->assertUnprocessable();
    $this->getJson('/combustible/unidades?qr=001')->assertOk()->assertJsonPath('IdUnidad', 1);
    foreach (['0', '-1', '1.2345', '1e3', 'NaN'] as $litres) {
        $this->postJson('/combustible/cargas', array_replace($this->input, ['Litros' => $litres]))->assertUnprocessable();
    }
    $this->postJson('/combustible/precios', ['Operacion' => (string) Str::uuid(), 'Confirmacion' => 'forjada', 'Recalcular' => 'si'])->assertUnprocessable();
    $review = $this->get('/combustible/precios/revisar?Fecha=2026-02-01&Costo=30');
    $signed = $review->viewData('confirmation');
    $this->service->create($this->input, $this->actor);
    $this->postJson('/combustible/precios', ['Operacion' => (string) Str::uuid(), 'Confirmacion' => $signed, 'Recalcular' => 'si'])->assertUnprocessable();
    $review = $this->get('/combustible/precios/revisar?Fecha=2026-02-01&Costo=30');
    $args = ['Operacion' => (string) Str::uuid(), 'Confirmacion' => $review->viewData('confirmation'), 'Recalcular' => 'si'];
    $this->postJson('/combustible/precios', $args)->assertOk();
    $this->postJson('/combustible/precios', $args)->assertOk();
    expect(Decimal::normalize((string) $this->db->table('tblcargascombustible')->value('PrecioLitro')))->toBe('30.000');
});

it('recovers an incomplete intent after time advances without mutating twice', function () {
    $broken = new class extends Journal
    {
        public function write(\Illuminate\Database\Connection $db, string $id, string $action, array $data, array $actor): void
        {
            $chunks = str_split(json_encode($data, JSON_THROW_ON_ERROR), 170);
            $db->table('tblexcepciones')->insert(['Fecha' => now()->format('Y-m-d H:i:s'), 'Usuario' => 'Prueba', 'Modulo' => 'combustible',
                'Excepcion' => $action, 'Datos' => $id.'|P|1/'.count($chunks).'|'.$chunks[0]]);
            throw new RuntimeException('Falla tras primer fragmento');
        }
    };
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $broken);
    expect(fn () => $service->create($this->input, $this->actor))->toThrow(RuntimeException::class);
    expect($this->db->table('tblcargascombustible')->count())->toBe(0);
    $this->travel(10)->minutes();
    $this->service->create($this->input, $this->actor);
    expect($this->db->table('tblcargascombustible')->count())->toBe(1);
});

it('resumes a partial recalculation and refuses overwriting external changes', function () {
    $row = $this->service->create($this->input, $this->actor);
    $args = ['Fecha' => '2026-02-01', 'Costo' => '30', 'Recalcular' => 'si', 'Operacion' => (string) Str::uuid(),
        'Version' => $this->service->version($this->service->priceState($this->db, '2026-02-01'))];
    $journal = new class extends Journal
    {
        private int $finishes = 0;

        public function finish(\Illuminate\Database\Connection $db, string $id, string $action, array $actor): void
        {
            if (++$this->finishes === 2) {
                throw new RuntimeException('Auditoría falló después de escribir una carga');
            }
            parent::finish($db, $id, $action, $actor);
        }
    };
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $journal);
    expect(fn () => $service->savePrice($args, $this->actor))->toThrow(RuntimeException::class);
    $this->service->savePrice($args, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcargascombustible')->value('PrecioLitro')))->toBe('30.000');

    $new = $this->input;
    $new['TokenRegistro'] = (string) Str::uuid();
    $new['Litros'] = '30';
    $broken = Mockery::mock(Journal::class)->makePartial();
    $broken->shouldReceive('finish')->once()->andThrow(new RuntimeException('Error tras escritura'));
    $partial = new FuelService(app(\App\Services\Combustible\Database::class), $broken);
    expect(fn () => $partial->create($new, $this->actor))->toThrow(RuntimeException::class);
    $this->db->table('tblcargascombustible')->where('TokenRegistro', $new['TokenRegistro'])->update(['Observaciones' => 'Cambio externo']);
    expect(fn () => $this->service->create($new, $this->actor))->toThrow(RuntimeException::class);
});

it('excludes cancelled rows from filtered totals even when the report includes them', function () {
    $first = $this->service->create($this->input, $this->actor);
    $this->service->create(array_replace($this->input, ['Litros' => '20', 'TokenRegistro' => (string) Str::uuid()]), $this->actor);
    $this->service->cancel($first->IdCarga, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($first), 'MotivoCancelacion' => 'Error'], $this->actor);
    $reports = app(Reports::class);
    $filters = ['desde' => '2026-02-10', 'hasta' => '2026-02-10', 'tipo_fecha' => 'carga', 'IdUnidad' => 1, 'Turno' => 'PM'];
    expect($reports->query($filters)->count())->toBe(2)
        ->and($reports->totals($reports->query($filters))['Litros'])->toBe('20.000')
        ->and($reports->totals($reports->query($filters + ['Estatus' => 'C']))['Litros'])->toBe('0.000');
});

it('serializes real concurrent MyISAM captures and daily notices', function () {
    if (getenv('FUEL_MYSQL_TEST') !== '1') {
        $this->markTestSkipped('Requiere MySQL 5.7 del contenedor de integración.');
    }
    $run = function ($action, $inputs) {
        $workers = [];
        foreach ($inputs as $input) {
            $process = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('tests/Support/fuel-worker.php'), $action, base64_encode(json_encode($input))], base_path());
            $process->start();
            $workers[] = $process;
        }

        return array_map(function ($p) {
            $code = $p->wait();
            expect($code)->toBe(0, $p->getErrorOutput());

            return json_decode($p->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        }, $workers);
    };
    $results = $run('create', array_fill(0, 4, $this->input));
    expect(count(array_unique(array_column($results, 'ok'))))->toBe(1)
        ->and($this->db->table('tblcargascombustible')->count())->toBe(1);
    $different = array_map(fn () => array_replace($this->input, ['Litros' => '20', 'TokenRegistro' => (string) Str::uuid()]), range(1, 4));
    $results = $run('create', $different);
    expect(count(array_filter($results, fn ($r) => isset($r['duplicate']))))->toBe(3)
        ->and($this->db->table('tblcargascombustible')->count())->toBe(2);
    $results = $run('notice', array_fill(0, 4, []));
    expect(count(array_filter($results, fn ($r) => $r['ok'] === true)))->toBe(1)
        ->and($this->db->table('tblavisospreciodiesel')->count())->toBe(1);
});

it('recovers from the original stored plan only for its owner with the required permission', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['combustible_ver', 'combustible_crear']);
    $actor = $this->service->actor($user);
    $broken = Mockery::mock(Journal::class)->makePartial();
    $broken->shouldReceive('finish')->once()->andThrow(new RuntimeException('Error de auditoría'));
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $broken);
    expect(fn () => $service->create($this->input, $actor))->toThrow(RuntimeException::class);
    $other = User::factory()->create();
    $other->givePermissionTo(['combustible_ver', 'combustible_crear']);
    $this->actingAs($other)->postJson('/combustible/operaciones/reintentar', ['Operacion' => $this->input['TokenRegistro']])->assertForbidden();
    $user->revokePermissionTo('combustible_crear');
    $this->actingAs($user)->postJson('/combustible/operaciones/reintentar', ['Operacion' => $this->input['TokenRegistro']])->assertForbidden();
    $user->givePermissionTo('combustible_crear');
    $this->postJson('/combustible/operaciones/reintentar', ['Operacion' => $this->input['TokenRegistro']])->assertOk();
    expect($this->db->table('tblcargascombustible')->count())->toBe(1);
});

it('revalidates active references before applying a pending insertion', function () {
    $journal = new class extends Journal
    {
        private int $writes = 0;

        public function write(\Illuminate\Database\Connection $db, string $id, string $action, array $data, array $actor): void
        {
            if (++$this->writes === 2) {
                throw new RuntimeException('Falla antes de insertar');
            }
            parent::write($db, $id, $action, $data, $actor);
        }
    };
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $journal);
    expect(fn () => $service->create($this->input, $this->actor))->toThrow(RuntimeException::class);
    $this->db->table('catunidades')->where('IdUnidad', 1)->update(['Estatus' => 'I']);
    expect(fn () => $this->service->create($this->input, $this->actor))->toThrow(ValidationException::class);
    expect($this->db->table('tblcargascombustible')->count())->toBe(0);
});

it('uses zero only without price history and preserves an undefined stored discount', function () {
    $this->db->table('tblcostos')->delete();
    $args = ['Fecha' => '2026-02-01', 'Costo' => '30', 'Recalcular' => 'no', 'Operacion' => (string) Str::uuid(),
        'Version' => $this->service->version($this->service->priceState($this->db, '2026-02-01'))];
    $this->service->savePrice($args, $this->actor);
    expect(Decimal::normalize((string) $this->db->table('tblcostos')->value('PorcDto'), 4))->toBe('0.0000');
    $this->db->table('tblcostos')->update(['PorcDto' => null]);
    $args = array_replace($args, ['Costo' => '31', 'Operacion' => (string) Str::uuid(),
        'Version' => $this->service->version($this->service->priceState($this->db, '2026-02-01'))]);
    $this->service->savePrice($args, $this->actor);
    expect($this->db->table('tblcostos')->value('PorcDto'))->toBeNull();
    expect(fn () => $this->service->create($this->input, $this->actor))->toThrow(ValidationException::class);
});

it('never retries a statement on a new connection after losing the session', function () {
    if (getenv('FUEL_MYSQL_TEST') !== '1') {
        $this->markTestSkipped('Requiere una sesión MySQL 5.7 real.');
    }
    config(['database.connections.fuel_observer' => config('database.connections.fuel_test')]);
    DB::purge('fuel_observer');
    $observer = DB::connection('fuel_observer');
    expect(function () use ($observer) {
        app(\App\Services\Combustible\Database::class)->locked(function ($db) use ($observer) {
            $id = (int) $db->selectOne('SELECT CONNECTION_ID() AS id')->id;
            $observer->statement('KILL CONNECTION '.$id);
            $db->table('tblexcepciones')->insert(['Fecha' => '2026-02-01', 'Usuario' => 'Prueba', 'Modulo' => 'combustible', 'Excepcion' => 'NO_DEBE_EXISTIR', 'Datos' => 'sin bloqueo']);
        });
    })->toThrow(RuntimeException::class);
    expect($observer->table('tblexcepciones')->where('Excepcion', 'NO_DEBE_EXISTIR')->count())->toBe(0);
    DB::purge('fuel_observer');
});

it('conditional write returns zero affected rows when the row was modified between the read and the write', function () {
    $row = $this->service->create($this->input, $this->actor);
    $before = (array) $row;
    // El WHERE del before state debe coincidir antes de cualquier modificación externa.
    expect($this->db->table('tblcargascombustible')->where($before)->count())->toBe(1);
    // Simular modificación concurrente que ocurre entre la comprobación del before y la escritura.
    $this->db->table('tblcargascombustible')->where('IdCarga', $row->IdCarga)->update(['FechaCarga' => '2026-03-15']);
    // La escritura condicional retorna 0 porque el before state ya no coincide.
    $affected = $this->db->table('tblcargascombustible')->where($before)->update(['Estatus' => 'C', 'ControlDuplicado' => null]);
    expect($affected)->toBe(0);
    // La modificación concurrente permanece; la escritura no fue aplicada.
    $final = $this->db->table('tblcargascombustible')->where('IdCarga', $row->IdCarga)->first();
    expect($final->FechaCarga)->toBe('2026-03-15')->and($final->Estatus)->toBe('A');
});

it('conditional update ensures only one of two concurrent writes on the same row succeeds', function () {
    if (getenv('FUEL_MYSQL_TEST') !== '1') {
        $this->markTestSkipped('Requiere MySQL 5.7 del contenedor de integración.');
    }
    $row = $this->service->create($this->input, $this->actor);
    $version = $this->service->version($row);
    $cancelArgs = ['Operacion' => (string) Str::uuid(), 'Version' => $version, 'MotivoCancelacion' => 'Error de captura'];
    $editArgs   = ['Operacion' => (string) Str::uuid(), 'Version' => $version,
        'IdUnidad' => $this->input['IdUnidad'], 'FechaCarga' => $this->input['FechaCarga'],
        'FechaTrabajo' => '2026-02-11', 'Turno' => $this->input['Turno'],
        'Litros' => $this->input['Litros'], 'Observaciones' => null];
    $cancelData = base64_encode(json_encode(['id' => $row->IdCarga, 'input' => $cancelArgs], JSON_THROW_ON_ERROR));
    $editData   = base64_encode(json_encode(['id' => $row->IdCarga, 'input' => $editArgs], JSON_THROW_ON_ERROR));
    $pCancel = new Symfony\Component\Process\Process(['php', __DIR__.'/../Support/fuel-worker.php', 'cancel', $cancelData]);
    $pEdit   = new Symfony\Component\Process\Process(['php', __DIR__.'/../Support/fuel-worker.php', 'edit',   $editData]);
    $pCancel->start();
    $pEdit->start();
    $pCancel->wait();
    $pEdit->wait();
    $cancelOk = $pCancel->getExitCode() === 0;
    $editOk   = $pEdit->getExitCode() === 0;
    // Exactamente uno debe haber ganado la carrera; el otro debe haber recibido RuntimeException.
    expect($cancelOk xor $editOk)->toBeTrue(
        'cancel: '.$pCancel->getErrorOutput().' / edit: '.$pEdit->getErrorOutput()
    );
    $final = $this->db->table('tblcargascombustible')->where('IdCarga', $row->IdCarga)->first();
    if ($cancelOk) {
        expect($final->Estatus)->toBe('C')->and($final->FechaTrabajo)->toBe('2026-02-01');
    } else {
        expect($final->Estatus)->toBe('A')->and($final->FechaTrabajo)->toBe('2026-02-11');
    }
});

it('revalidates pending deletion when newer prices or cancelled references appear', function (string $dependency) {
    $last = $this->db->table('tblcostos')->where('Fecha', '2026-02-01')->first();
    $args = ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($last)];
    $journal = new class extends Journal
    {
        private int $writes = 0;

        public function write(\Illuminate\Database\Connection $db, string $id, string $action, array $data, array $actor): void
        {
            if (++$this->writes === 2) {
                throw new RuntimeException('Falla antes de eliminar');
            }
            parent::write($db, $id, $action, $data, $actor);
        }
    };
    $service = new FuelService(app(\App\Services\Combustible\Database::class), $journal);
    expect(fn () => $service->deletePrice($last->Fecha, $args, $this->actor))->toThrow(RuntimeException::class);
    if ($dependency === 'newer') {
        $this->db->table('tblcostos')->insert(['IdCosto' => 2, 'Fecha' => '2026-03-01', 'Costo' => '30', 'PorcDto' => 0]);
    } else {
        $row = $this->service->create($this->input, $this->actor);
        $this->service->cancel($row->IdCarga, ['Operacion' => (string) Str::uuid(), 'Version' => $this->service->version($row), 'MotivoCancelacion' => 'Error'], $this->actor);
    }
    expect(fn () => $this->service->deletePrice($last->Fecha, $args, $this->actor))->toThrow(ValidationException::class);
    expect($this->db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', $last->Fecha)->exists())->toBeTrue();
})->with(['newer', 'cancelled']);

it('renders fuel screens without blocking CDN assets and keeps other modules configuration unchanged', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::where('name', 'like', 'combustible_%')->get());
    $this->actingAs($user);
    $settings = config('adminlte');
    foreach (['/combustible', '/combustible/crear', '/combustible/precios', '/combustible/auditoria'] as $url) {
        $this->get($url)->assertOk()->assertDontSee('fonts.googleapis.com')->assertDontSee('cdn.datatables.net')
            ->assertDontSee('cdnjs.cloudflare.com')->assertDontSee('cdn.jsdelivr.net')
            ->assertDontSee('<script src="https://unpkg', false)->assertDontSee('class="preloader ', false);
        expect(config('adminlte'))->toBe($settings);
    }
    $response = $this->get('/combustible/crear');
    $response->assertSee('vendor/select2/select2.full.min.js')->assertSee('name="TokenRegistro"', false)
        ->assertDontSee('Determina el precio aplicable.')->assertDontSee('Puede corresponder a un día anterior trabajado.')
        ->assertDontSee('El servidor valida la unidad y calcula el importe.')->assertDontSee('Identificador de envío:')
        ->assertDontSee('Buscar unidad activa')->assertDontSee('id="unit-search"', false);
});

it('reads audit fragments in a batch rather than rescanning per operation', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['combustible_ver', 'combustible_auditoria']);
    for ($i = 1; $i <= 4; $i++) {
        $this->service->create(array_replace($this->input, ['Litros' => (string) $i, 'TokenRegistro' => (string) Str::uuid()]), $this->actor);
    }
    $this->db->enableQueryLog();
    $this->db->flushQueryLog();
    $this->actingAs($user)->get('/combustible/auditoria')->assertOk()->assertSee('CREAR')->assertSee('COMPLETA')->assertSee($this->actor['name']);
    $queries = collect($this->db->getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'tblexcepciones'));
    expect($queries->count())->toBe(2);
    $this->db->disableQueryLog();
});

it('uses the daily unique key without requesting locks on operational tables', function () {
    $database = Mockery::mock(\App\Services\Combustible\Database::class);
    $database->shouldReceive('connection')->times(3)->andReturn($this->db);
    $database->shouldNotReceive('locked');
    $fuel = new FuelService($database, app(Journal::class));
    expect($fuel->notice($this->actor))->toBeTrue()->and($fuel->notice($this->actor))->toBeFalse();
    $fuel->omitNotice($this->actor);
    expect($this->db->table('tblavisospreciodiesel')->value('Respuesta'))->toBe('OMITIDO');
});

it('keeps the original token and reports no new writes only when acquisition failed', function (bool $acquisition) {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo(['combustible_ver', 'combustible_crear']);
    if ($acquisition) {
        $database = Mockery::mock(\App\Services\Combustible\Database::class);
        $database->shouldReceive('locked')->once()->with(Mockery::type('callable'), 'CREAR')
            ->andThrow(new \App\Services\Combustible\LockUnavailable('No se adquirieron los bloqueos.'));
        $this->app->instance(\App\Services\Combustible\Database::class, $database);
    } else {
        $journal = new class extends Journal
        {
            public function write(\Illuminate\Database\Connection $db, string $id, string $action, array $data, array $actor): void
            {
                $cause = new PDOException('Lock wait timeout exceeded');
                $cause->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded'];
                throw new \Illuminate\Database\QueryException('fuel_test', 'INSERT audit fragment', [], $cause);
            }
        };
        $this->app->instance(Journal::class, $journal);
    }
    $response = $this->actingAs($user)->postJson('/combustible/cargas', $this->input)
        ->assertStatus(503)->assertJsonPath('operacion', $this->input['TokenRegistro']);
    if ($acquisition) {
        $response->assertHeader('Retry-After', '10')->assertJsonPath('message', 'La base está ocupada por un bloqueo. Este intento no inició escrituras. Conserve los datos y reintente el mismo envío cuando se libere.');
    } else {
        $response->assertSee('Puede existir una escritura parcial.')->assertDontSee('Este intento no inició escrituras.');
    }
    expect($this->db->table('tblcargascombustible')->count())->toBe(0)
        ->and($this->db->table('tblexcepciones')->count())->toBe($acquisition ? 0 : 1);
})->with([true, false]);

it('shows a controlled unavailable response when MySQL metadata waits expire', function () {
    $this->seed(CombustiblePermissionSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('combustible_ver');
    $this->actingAs($user);
    $cause = new PDOException('Lock wait timeout exceeded');
    $cause->errorInfo = ['HY000', 1205, 'Lock wait timeout exceeded'];
    $exception = new \Illuminate\Database\QueryException('fuel_test', 'select * from tblcargascombustible', [], $cause);
    $reports = Mockery::mock(Reports::class);
    $reports->shouldReceive('query')->twice()->andThrow($exception);
    $this->app->instance(Reports::class, $reports);
    $settings = config('adminlte');
    $this->getJson('/combustible')->assertStatus(503)->assertHeader('Retry-After', '10')->assertJsonPath('message', 'La base está ocupada por un bloqueo. Espere a que se libere antes de reintentar.');
    $this->get('/combustible')->assertStatus(503)->assertSee('La base está ocupada por un bloqueo.');
    expect(config('adminlte'))->toBe($settings);
});
