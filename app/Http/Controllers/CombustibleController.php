<?php

namespace App\Http\Controllers;

use App\Services\Combustible\Decimal;
use App\Services\Combustible\FuelService;
use App\Services\Combustible\Reports;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CombustibleController extends Controller
{
    public function __construct(private FuelService $fuel, private Reports $reports) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->reports->query($filters);
        $totals = $this->reports->totals($query);
        $rows = $query->orderByDesc('FechaCarga')->orderByDesc('IdCarga')->paginate(25)->withQueryString();
        $units = $this->fuel->database->connection()->table('catunidades')->orderBy('Unidad')->get(['IdUnidad', 'Unidad', 'Estatus']);

        return view('combustible.index', compact('filters', 'totals', 'rows', 'units'));
    }

    public function create()
    {
        return view('combustible.form', ['row' => null, 'token' => (string) Str::uuid(), 'version' => '', 'today' => $this->fuel->today()]);
    }

    public function edit(int $id)
    {
        $row = $this->fuel->database->connection()->table('tblcargascombustible')->where('IdCarga', $id)->first();
        abort_unless($row && $row->Estatus === 'A', 404);

        return view('combustible.form', ['row' => $row, 'token' => (string) Str::uuid(), 'version' => $this->fuel->version($row), 'today' => $this->fuel->today()]);
    }

    public function store(Request $request)
    {
        $input = $this->capture($request, false);

        return $this->mutate($request, function () use ($request, $input) {
            $row = $this->fuel->create($input, $this->fuel->actor($request->user()));

            return 'Carga #'.$row->IdCarga.' confirmada ('.($row->Estatus === 'A' ? 'ACTIVA' : 'CANCELADA').'); auditoría completa. Este token no genera otra captura.';
        });
    }

    public function update(Request $request, int $id)
    {
        $input = $this->capture($request, true);

        return $this->mutate($request, function () use ($request, $id, $input) {
            $this->fuel->edit($id, $input, $this->fuel->actor($request->user()));

            return 'Carga actualizada; auditoría completa.';
        });
    }

    public function cancel(Request $request, int $id)
    {
        $input = $request->validate(['Operacion' => ['required', 'uuid'], 'Version' => ['required', 'size:64'],
            'MotivoCancelacion' => ['required', 'string', 'max:255', $this->latin1()]]);

        return $this->mutate($request, function () use ($request, $id, $input) {
            $this->fuel->cancel($id, $input, $this->fuel->actor($request->user()));

            return 'Carga cancelada; auditoría completa.';
        });
    }

    public function resume(Request $request)
    {
        $input = $request->validate(['Operacion' => ['required', 'uuid']]);

        return $this->mutate($request, function () use ($request, $input) {
            $this->fuel->resume($input['Operacion'], $this->fuel->actor($request->user()));

            return 'Operación recuperada o previamente completada; auditoría confirmada.';
        });
    }

    public function units(Request $request)
    {
        $input = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'qr' => ['nullable', 'string', 'max:6']]);
        if (array_key_exists('qr', $input)) {
            return response()->json($this->fuel->qr($input['qr']));
        }

        return response()->json($this->fuel->database->connection()->table('catunidades')->where('Estatus', 'A')
            ->when($input['q'] ?? '', fn ($q, $term) => $q->where('Unidad', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->orderBy('Unidad')->limit(50)->get(['IdUnidad', 'Unidad']));
    }

    public function quote(Request $request)
    {
        $input = $request->validate(['FechaCarga' => ['required', 'date_format:Y-m-d'], 'Litros' => $this->litres()]);
        $price = $this->fuel->price($this->fuel->database->connection(), $input['FechaCarga']);

        return response()->json(['FechaPrecio' => $price->Fecha, 'PrecioLitro' => Decimal::normalize((string) $price->Costo),
            'Importe' => Decimal::amount($input['Litros'], (string) $price->Costo, (string) ($price->PorcDto ?? 0))]);
    }

    public function prices()
    {
        $rows = $this->fuel->database->connection()->table('tblcostos')->where('IdCosto', 2)->orderByDesc('Fecha')->paginate(30);

        return view('combustible.prices', ['rows' => $rows, 'today' => $this->fuel->today()]);
    }

    public function reviewPrice(Request $request)
    {
        $input = $request->validate(['Fecha' => ['required', 'date_format:Y-m-d'], 'Costo' => ['required', 'regex:/^\d{1,6}(\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:999999.999']]);
        $state = $this->fuel->database->locked(fn ($db) => $this->fuel->priceState($db, $input['Fecha']));
        $version = $this->fuel->version($state);
        // Vincula servidor, usuario, precio propuesto, registros y caducidad de la confirmación.
        $confirmation = Crypt::encryptString(json_encode(['user' => (string) $request->user()->getAuthIdentifier(),
            'Fecha' => $input['Fecha'], 'Costo' => $input['Costo'], 'Version' => $version, 'expires' => time() + 1800], JSON_THROW_ON_ERROR));

        return view('combustible.review-price', ['input' => $input, 'state' => $state, 'version' => $version,
            'confirmation' => $confirmation, 'token' => (string) Str::uuid()]);
    }

    public function savePrice(Request $request)
    {
        $input = $request->validate(['Confirmacion' => ['required', 'string'], 'Operacion' => ['required', 'uuid'], 'Recalcular' => ['required', Rule::in(['si', 'no'])]]);
        try {
            $confirmed = json_decode(Crypt::decryptString($input['Confirmacion']), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['Confirmacion' => 'La confirmación no es válida. Revise nuevamente el precio.']);
        }
        abort_unless($confirmed['user'] === (string) $request->user()->getAuthIdentifier(), 403);
        // Una operación ya iniciada debe poder recuperarse aun después del vencimiento.
        if ($confirmed['expires'] < time() && ! app(\App\Services\Combustible\Journal::class)->read($this->fuel->database->connection(), $input['Operacion'])) {
            throw ValidationException::withMessages(['Confirmacion' => 'La confirmación venció. Revise nuevamente el precio.']);
        }
        $data = ['Fecha' => $confirmed['Fecha'], 'Costo' => $confirmed['Costo'], 'Version' => $confirmed['Version'],
            'Operacion' => $input['Operacion'], 'Recalcular' => $input['Recalcular']];

        return $this->mutate($request, function () use ($request, $data) {
            $this->fuel->savePrice($data, $this->fuel->actor($request->user()));

            return 'Precio guardado; cambios y auditoría completos.';
        });
    }

    public function deletePrice(Request $request, string $date)
    {
        validator(['Fecha' => $date], ['Fecha' => ['required', 'date_format:Y-m-d']])->validate();
        $input = $request->validate(['Operacion' => ['required', 'uuid'], 'Version' => ['required', 'size:64']]);

        return $this->mutate($request, function () use ($request, $date, $input) {
            $this->fuel->deletePrice($date, $input, $this->fuel->actor($request->user()));

            return 'Último precio eliminado; auditoría completa.';
        });
    }

    public function notice(Request $request)
    {
        return response()->json(['mostrar' => $this->fuel->notice($this->fuel->actor($request->user()))]);
    }

    public function omitNotice(Request $request)
    {
        $this->fuel->omitNotice($this->fuel->actor($request->user()));

        return response()->json(['ok' => true]);
    }

    public function audit(Request $request)
    {
        $input = $request->validate(['operacion' => ['nullable', 'regex:/^[a-f0-9-]{32,36}$/']]);
        $db = $this->fuel->database->connection();
        $journal = app(\App\Services\Combustible\Journal::class);
        $rows = $db->table('tblexcepciones')->where('Modulo', 'combustible')
            ->where('Datos', 'like', str_repeat('_', 36).'|BEGIN|%')
            ->when($input['operacion'] ?? '', fn ($q, $id) => $q->where('Datos', 'like', $id.'|BEGIN|%'))
            ->orderByDesc('Fecha')->orderBy('Datos')->simplePaginate(25)->withQueryString();
        $ids = $rows->getCollection()->map(fn ($row) => explode('|', $row->Datos, 2)[0]);
        $fragments = $ids->isEmpty() ? collect() : $db->table('tblexcepciones')->where('Modulo', 'combustible')
            ->where(function ($query) use ($ids) {
                foreach ($ids as $id) {
                    $query->orWhere('Datos', 'like', $id.'|P|%')->orWhere('Datos', $id.'|OK');
                }
            })->pluck('Datos')->groupBy(fn ($data) => explode('|', $data, 2)[0]);
        $rows->through(function ($row) use ($fragments, $journal) {
            $id = explode('|', $row->Datos, 2)[0];
            $diagnostic = null;
            try {
                $intent = $journal->decode($fragments->get($id, collect())->filter(fn ($data) => str_starts_with($data, $id.'|P|'))->all(), $id);
            } catch (\Throwable $e) {
                $intent = null;
                $diagnostic = 'Los fragmentos no son consistentes. Solicite conciliación sin modificar la auditoría.';
            }

            return (object) ['id' => $id, 'Fecha' => $row->Fecha, 'action' => $row->Excepcion,
                'actor' => $intent['actor'] ?? ['id' => '?', 'name' => $row->Usuario],
                'complete' => $fragments->get($id, collect())->contains($id.'|OK'), 'entries' => $intent['entries'] ?? [],
                'diagnostic' => $diagnostic ?? ($intent === null ? 'El plan está incompleto. Conserve el formulario original para reintentar.' : null)];
        });

        return view('combustible.audit', compact('rows'));
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $filters = $this->filters($request);
        // Una sola lectura conserva el mismo conjunto para detalle y totales del archivo.
        $rows = $this->reports->query($filters)->orderBy('FechaCarga')->orderBy('IdCarga')->get();
        $active = $rows->where('Estatus', 'A');
        $totals = ['Litros' => Decimal::sum($active->pluck('Litros'), 3), 'Importe' => Decimal::sum($active->pluck('Importe'), 2)];
        if ($format === 'xlsx') {
            return response()->download($this->reports->excel($rows, $totals), 'combustible.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend();
        }

        return Pdf::loadView('combustible.pdf', compact('rows', 'totals', 'filters'))->setPaper('a4', 'landscape')->download('combustible.pdf');
    }

    private function filters(Request $request): array
    {
        $data = array_replace(['desde' => $this->fuel->today(), 'hasta' => $this->fuel->today(),
            'tipo_fecha' => 'carga', 'Estatus' => 'A'], $request->only(['desde', 'hasta', 'tipo_fecha', 'IdUnidad', 'Turno', 'Estatus']));

        return validator($data, [
            'desde' => ['required', 'date_format:Y-m-d'], 'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'tipo_fecha' => ['required', Rule::in(['carga', 'trabajo'])], 'IdUnidad' => ['nullable', 'integer', 'min:1'],
            'Turno' => ['nullable', Rule::in(['AM', 'PM'])], 'Estatus' => ['nullable', Rule::in(['A', 'C'])],
        ])->validate();
    }

    private function capture(Request $request, bool $editing): array
    {
        return $request->validate([
            'FechaCarga' => ['required', 'date_format:Y-m-d'], 'FechaTrabajo' => ['required', 'date_format:Y-m-d'],
            'Turno' => ['required', Rule::in(['AM', 'PM'])], 'IdUnidad' => ['required', 'integer', 'min:1'],
            'NumeroTicket' => ['required', 'string', 'max:50', $this->latin1()],
            'Km' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'Litros' => $this->litres(), 'Observaciones' => ['nullable', 'string', 'max:255', $this->latin1()],
            ...($editing ? ['Operacion' => ['required', 'uuid'], 'Version' => ['required', 'size:64']] : ['TokenRegistro' => ['required', 'uuid']]),
        ]);
    }

    private function litres(): array
    {
        return ['required', 'regex:/^\d{1,8}(\.\d{1,3})?$/', 'numeric', 'gt:0', 'max:99999999.999'];
    }

    private function latin1(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (preg_match('/[^\x{0000}-\x{00ff}]/u', $value)) {
                $fail('El texto contiene caracteres incompatibles con la tabla latin1.');
            }
        };
    }

    private function mutate(Request $request, callable $action)
    {
        try {
            $message = $action();

            return response()->json(['message' => $message]);
        } catch (\App\Services\Combustible\LockUnavailable $e) {
            report($e);

            return response()->json([
                'message' => 'La base está ocupada por un bloqueo. Este intento no inició escrituras. Conserve los datos y reintente el mismo envío cuando se libere.',
                'operacion' => $request->input('Operacion', $request->input('TokenRegistro')),
            ], 503)->header('Retry-After', '10');
        } catch (ValidationException|\Illuminate\Auth\Access\AuthorizationException|\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se confirmó la operación completa. Puede existir una escritura parcial. Conserve los datos y reintente el mismo envío; si persiste, solicite conciliación en auditoría.',
                'operacion' => $request->input('Operacion', $request->input('TokenRegistro'))], 503);
        }
    }
}
