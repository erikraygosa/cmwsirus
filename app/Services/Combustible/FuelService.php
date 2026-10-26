<?php

namespace App\Services\Combustible;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FuelService
{
    private ?string $operationTime = null;

    public function __construct(public Database $database, private Journal $journal) {}

    public function today(): string
    {
        return now(config('combustible.timezone'))->toDateString();
    }

    public function actor($user): array
    {
        return ['id' => (string) $user->getAuthIdentifier(), 'name' => $user->name];
    }

    public function version(mixed $value): string
    {
        return hash('sha256', json_encode($this->canonical((array) $value), JSON_THROW_ON_ERROR));
    }

    private function canonical(array $data): array
    {
        $scales = ['Litros' => 3, 'Costo' => 3, 'PrecioLitro' => 3, 'PorcDto' => 4, 'Importe' => 2];
        foreach ($data as $key => &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->canonical((array) $value);
            } elseif ($value !== null && isset($scales[$key])) {
                $value = Decimal::normalize((string) $value, $scales[$key]);
            } elseif (is_int($value)) {
                $value = (string) $value;
            }
        }
        ksort($data);

        return $data;
    }

    public function unit(Connection $db, int $id): object
    {
        $unit = $db->table('catunidades')->where('IdUnidad', $id)->where('Estatus', 'A')->first();
        if (! $unit) {
            $this->invalid('IdUnidad', 'La unidad no existe o no está activa.');
        }

        return $unit;
    }

    public function qr(string $code): object
    {
        // No convertir a número, ni recortar o cambiar mayúsculas del contenido QR.
        $matches = $this->database->connection()->table('catunidades')->where('Unidad', $code)
            ->get()->filter(fn ($u) => $u->Unidad === $code && $u->Estatus === 'A');
        if ($matches->count() !== 1) {
            $this->invalid('qr', 'El QR debe corresponder exactamente a una única unidad activa. Use la búsqueda manual.');
        }

        return $matches->first();
    }

    public function price(Connection $db, string $date): object
    {
        $price = $db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', '<=', $date)->orderByDesc('Fecha')->first();
        if (! $price || $price->Costo === null || \Brick\Math\BigDecimal::of((string) $price->Costo)->isLessThanOrEqualTo(0)) {
            $this->invalid('FechaCarga', 'No existe precio de diésel válido aplicable a la fecha de carga. Solicite su registro.');
        }

        if ($price->PorcDto === null) {
            $this->invalid('FechaCarga', 'El precio tiene descuento sin definir. Debe verificarse antes de calcular.');
        }

        return $price;
    }

    private function priced(Connection $db, string $date, string $litres): array
    {
        $p = $this->price($db, $date);

        return [
            'IdCosto' => 2, 'FechaPrecio' => $p->Fecha,
            'PrecioLitro' => Decimal::normalize((string) $p->Costo),
            'PorcDto' => Decimal::normalize((string) ($p->PorcDto ?? '0'), 4),
            'Importe' => Decimal::amount($litres, (string) $p->Costo, (string) ($p->PorcDto ?? '0')),
        ];
    }

    public function create(array $input, array $actor): object
    {
        $token = $input['TokenRegistro'];
        $this->operation($token, 'CREAR', $input, $actor, function ($db) use ($input, $actor) {
            $unit = $this->unit($db, (int) $input['IdUnidad']);
            $data = [
                'FechaCarga' => $input['FechaCarga'], 'FechaTrabajo' => $input['FechaTrabajo'],
                'Turno' => $input['Turno'], 'IdUnidad' => $unit->IdUnidad, 'Unidad' => $unit->Unidad,
                'NumeroTicket' => $input['NumeroTicket'] ?? null,
                'Km' => isset($input['Km']) && $input['Km'] !== '' ? (int) $input['Km'] : null,
                'Litros' => Decimal::normalize($input['Litros']),
                ...$this->priced($db, $input['FechaCarga'], $input['Litros']),
                'Observaciones' => $input['Observaciones'] ?? null,
                'Estatus' => 'A', 'ControlDuplicado' => 1, 'TokenRegistro' => $input['TokenRegistro'],
                'UsuarioRegistro' => $this->storedName($actor['name']), 'FechaRegistro' => $this->timestamp(),
            ];

            return [$this->entry('tblcargascombustible', ['TokenRegistro' => $input['TokenRegistro']], null, $data, 'CREAR')];
        });

        return $this->database->connection()->table('tblcargascombustible')->where('TokenRegistro', $token)->first();
    }

    public function edit(int $id, array $input, array $actor): void
    {
        $this->operation($input['Operacion'], 'EDITAR', ['id' => $id, ...$input], $actor, function ($db) use ($id, $input, $actor) {
            $before = $this->active($db, $id, $input['Version']);
            $unit = $this->unit($db, (int) $input['IdUnidad']);
            $after = (array) $before;
            foreach (['FechaCarga', 'FechaTrabajo', 'Turno'] as $field) {
                $after[$field] = $input[$field];
            }
            $after['IdUnidad'] = $unit->IdUnidad;
            $after['Unidad'] = $unit->Unidad;
            if (array_key_exists('NumeroTicket', $input)) {
                $after['NumeroTicket'] = $input['NumeroTicket'];
            }
            if (array_key_exists('Km', $input)) {
                $after['Km'] = isset($input['Km']) && $input['Km'] !== '' ? (int) $input['Km'] : null;
            }
            $after['Litros'] = Decimal::normalize($input['Litros']);
            $after['Observaciones'] = $input['Observaciones'] ?? null;
            if ($before->FechaCarga !== $input['FechaCarga'] || Decimal::normalize((string) $before->Litros) !== $after['Litros']) {
                $after = array_replace($after, $this->priced($db, $input['FechaCarga'], $input['Litros']));
            }
            $after['UsuarioModificacion'] = $this->storedName($actor['name']);
            $after['FechaModificacion'] = $this->timestamp();

            return [$this->entry('tblcargascombustible', ['IdCarga' => $id], (array) $before, $after, 'EDITAR')];
        });
    }

    public function cancel(int $id, array $input, array $actor): void
    {
        $this->operation($input['Operacion'], 'CANCELAR', ['id' => $id, ...$input], $actor, function ($db) use ($id, $input, $actor) {
            $before = $this->active($db, $id, $input['Version']);
            $after = array_replace((array) $before, [
                'Estatus' => 'C', 'ControlDuplicado' => null,
                'UsuarioCancelacion' => $this->storedName($actor['name']), 'FechaCancelacion' => $this->timestamp(),
                'MotivoCancelacion' => $input['MotivoCancelacion'],
            ]);

            return [$this->entry('tblcargascombustible', ['IdCarga' => $id], (array) $before, $after, 'CANCELAR')];
        });
    }

    private function active(Connection $db, int $id, string $version): object
    {
        $row = $db->table('tblcargascombustible')->where('IdCarga', $id)->first();
        if (! $row || $row->Estatus !== 'A' || ! hash_equals($this->version($row), $version)) {
            $this->invalid('Version', 'La carga cambió o está cancelada. Recargue la consulta antes de continuar.');
        }

        return $row;
    }

    public function priceState(Connection $db, string $date): array
    {
        $price = $db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', $date)->first();
        $rows = $db->table('tblcargascombustible')->where('IdCosto', 2)->where('FechaPrecio', $date)
            ->where('Estatus', 'A')->orderBy('IdCarga')->get();
        // Captura todos los valores, no sólo el número de cargas: evita confirmar un conjunto obsoleto.
        $previous = $db->table('tblcostos')->where('IdCosto', 2)->orderByDesc('Fecha')->first();

        return ['price' => $price, 'rows' => $rows->map(fn ($r) => (array) $r)->all(), 'previous' => $previous];
    }

    public function savePrice(array $input, array $actor): void
    {
        $this->operation($input['Operacion'], 'PRECIO', $input, $actor, function ($db) use ($input, $actor) {
            $state = $this->priceState($db, $input['Fecha']);
            if (! hash_equals($this->version($state), $input['Version'])) {
                $this->invalid('Version', 'Los precios o las cargas cambiaron. Revise de nuevo y confirme el conjunto actual.');
            }
            $before = $state['price'] ? (array) $state['price'] : null;
            $discount = $before !== null ? $before['PorcDto'] : ($state['previous'] !== null ? $state['previous']->PorcDto : '0');
            $after = ['IdCosto' => 2, 'Fecha' => $input['Fecha'], 'Costo' => Decimal::normalize($input['Costo']), 'PorcDto' => $discount === null ? null : Decimal::normalize((string) $discount, 4)];
            $entries = [['confirmation' => $input['Version'], ...$this->entry('tblcostos', ['IdCosto' => 2, 'Fecha' => $input['Fecha']], $before, $after, 'PRECIO')]];
            if ($input['Recalcular'] === 'si') {
                if ($after['PorcDto'] === null && $state['rows']) {
                    $this->invalid('Costo', 'El descuento del precio está sin definir; no se puede recalcular.');
                }
                foreach ($state['rows'] as $row) {
                    $changed = array_replace($row, [
                        'PrecioLitro' => $after['Costo'], 'PorcDto' => $after['PorcDto'],
                        'Importe' => Decimal::amount((string) $row['Litros'], $after['Costo'], $after['PorcDto']),
                        'UsuarioModificacion' => $this->storedName($actor['name']), 'FechaModificacion' => $this->timestamp(),
                    ]);
                    $entries[] = $this->entry('tblcargascombustible', ['IdCarga' => $row['IdCarga']], $row, $changed, 'RECALCULAR');
                }
            }
            // La respuesta diaria se guarda dentro de la misma operación recuperable.
            if ($input['Fecha'] === $this->today()) {
                $notice = $db->table('tblavisospreciodiesel')->where('IdUsuario', $actor['id'])->where('Fecha', $this->today())->first();
                if ($notice) {
                    $entries[] = $this->entry('tblavisospreciodiesel', ['IdAviso' => $notice->IdAviso], (array) $notice,
                        array_replace((array) $notice, ['Respuesta' => 'ACTUALIZADO', 'FechaRespuesta' => $this->timestamp()]), 'AVISO');
                }
            }

            return $entries;
        });
    }

    public function deletePrice(string $date, array $input, array $actor): void
    {
        $this->operation($input['Operacion'], 'ELIMINAR_PRECIO', ['Fecha' => $date, ...$input], $actor, function ($db) use ($date, $input) {
            $row = $db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', $date)->first();
            $last = $db->table('tblcostos')->where('IdCosto', 2)->max('Fecha');
            if (! $row || $last !== $date || $db->table('tblcargascombustible')->where('IdCosto', 2)->where('FechaPrecio', $date)->exists()) {
                $this->invalid('Fecha', 'Sólo se puede eliminar el último precio y sin cargas asociadas, incluidas canceladas.');
            }
            if (! hash_equals($this->version($row), $input['Version'])) {
                $this->invalid('Version', 'El precio cambió. Revise antes de eliminarlo.');
            }

            return [$this->entry('tblcostos', ['IdCosto' => 2, 'Fecha' => $date], (array) $row, null, 'ELIMINAR_PRECIO')];
        });
    }

    public function notice(array $actor): bool
    {
        // La clave única usuario/fecha resuelve aperturas simultáneas sin bloquear cargas ni auditoría.
        $db = $this->database->connection();
        $today = $this->today();
        if ($db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', $today)->exists()) {
            return false;
        }
        try {
            $db->table('tblavisospreciodiesel')->insert([
                'IdUsuario' => $actor['id'], 'Usuario' => $this->storedName($actor['name']), 'Fecha' => $today,
                'FechaAviso' => $this->timestamp(), 'Respuesta' => 'MOSTRADO',
            ]);
        } catch (QueryException $e) {
            if ($this->isDuplicate($e)) {
                return false;
            }
            throw $e;
        }

        return ! $db->table('tblcostos')->where('IdCosto', 2)->where('Fecha', $today)->exists();
    }

    public function omitNotice(array $actor): void
    {
        // UPDATE condicional de una fila; no necesita el bloqueo de las otras tablas.
        $this->database->connection()->table('tblavisospreciodiesel')
            ->where('IdUsuario', $actor['id'])->where('Fecha', $this->today())->where('Respuesta', 'MOSTRADO')
            ->update(['Respuesta' => 'OMITIDO', 'FechaRespuesta' => $this->timestamp()]);
    }

    public function resume(string $id, array $actor): void
    {
        $intent = $this->journal->read($this->database->connection(), $id);
        if (! $intent) {
            $this->invalid('Operacion', 'La intención está incompleta o no existe. Reenvíe los datos originales desde su captura.');
        }
        abort_unless($intent['actor']['id'] === $actor['id'], 403);
        $permission = match ($intent['action']) {
            'CREAR' => 'crear', 'EDITAR' => 'editar', 'CANCELAR' => 'cancelar',
            'PRECIO', 'ELIMINAR_PRECIO' => 'precios',
            default => throw new RuntimeException('Acción no recuperable.'),
        };
        \Illuminate\Support\Facades\Gate::authorize('combustible_'.$permission);
        $this->operation($id, $intent['action'], $intent['input'], $actor, fn () => throw new RuntimeException('No se encontró el plan original.'));
    }

    private function entry(string $table, array $key, ?array $before, ?array $after, string $action): array
    {
        return compact('table', 'key', 'before', 'after', 'action');
    }

    private function operation(string $id, string $action, array $input, array $actor, callable $plan): void
    {
        $this->database->locked(function ($db) use ($id, $action, $input, $actor, $plan) {
            $mysql = $db->getDriverName() !== 'sqlite';
            // Serializa solicitudes concurrentes con el mismo identificador (doble envío, reintento
            // de red que llega antes de que termine el primero). El lock es por token, no por tabla.
            if ($mysql) {
                $lock = $db->selectOne('SELECT GET_LOCK(?, 3) AS acquired', ['combustible:'.$id]);
                if (! $lock->acquired) {
                    throw new LockUnavailable('Solicitud duplicada en curso para este identificador. Reintente en unos segundos con el mismo identificador.');
                }
            }
            try {
                $fingerprint = $this->version(['input' => $input, 'user' => $actor['id'], 'action' => $action]);
                $header = $this->journal->header($db, $id);
                if ($header && ! hash_equals($header['fingerprint'], $fingerprint)) {
                    $this->invalid('Operacion', 'El identificador pertenece a otros valores. Recupere el envío original o descarte el borrador.');
                }
                $this->operationTime = $header['at'] ?? now(config('combustible.timezone'))->format('Y-m-d H:i:s');
                try {
                    $intent = $this->journal->read($db, $id);
                    if ($intent && ! hash_equals($intent['fingerprint'], $fingerprint)) {
                        $this->invalid('Operacion', 'El identificador ya pertenece a otra captura o a otros valores. Recupere el envío original.');
                    }
                    if ($intent && $this->journal->done($db, $id)) {
                        return;
                    }
                    if (! $intent) {
                        $intent = ['fingerprint' => $fingerprint, 'actor' => $actor, 'action' => $action, 'input' => $input, 'entries' => $plan($db)];
                        $this->journal->begin($db, $id, ['fingerprint' => $fingerprint, 'at' => $this->operationTime], $action, $actor);
                        $this->journal->write($db, $id, $action, $intent, $actor);
                    }
                    foreach ($intent['entries'] as $index => $entry) {
                        $step = hash('md5', $id.':'.$index);
                        if ($this->journal->done($db, $step)) {
                            if (! $this->matches($db->table($entry['table'])->where($entry['key'])->first(), $entry['after'])) {
                                throw new RuntimeException('Operación parcial '.$id.': un cambio completado fue modificado externamente. Requiere conciliación.');
                            }

                            continue;
                        }
                        $this->journal->write($db, $step, $entry['action'], ['operation' => $id, 'actor' => $intent['actor'], ...$entry], $intent['actor']);
                        $current = $db->table($entry['table'])->where($entry['key'])->first();
                        if (! $this->matches($current, $entry['after'])) {
                            if (! $this->matches($current, $entry['before'])) {
                                throw new RuntimeException('Operación parcial '.$id.': el registro cambió externamente. Requiere conciliación antes de reintentar.');
                            }
                            $this->validatePendingReferences($db, $entry);
                            try {
                                if ($entry['after'] === null) {
                                    // DELETE atómico: WHERE incluye el estado previo completo para detectar
                                    // modificaciones concurrentes entre la lectura y la escritura.
                                    $affected = $db->table($entry['table'])->where($entry['before'])->delete();
                                    if ($affected === 0) {
                                        throw new RuntimeException('Operación parcial '.$id.': el registro fue modificado entre la lectura y la escritura. Requiere conciliación.');
                                    }
                                } elseif ($entry['before'] === null) {
                                    $db->table($entry['table'])->insert($entry['after']);
                                } else {
                                    // UPDATE atómico: WHERE incluye el estado previo completo; si otra
                                    // escritura ganó la carrera, affected = 0 y se lanza excepción.
                                    $affected = $db->table($entry['table'])->where($entry['before'])->update($entry['after']);
                                    if ($affected === 0) {
                                        throw new RuntimeException('Operación parcial '.$id.': el registro fue modificado entre la lectura y la escritura. Requiere conciliación.');
                                    }
                                }
                            } catch (QueryException $e) {
                                if ($this->isDuplicate($e)) {
                                    $this->invalid('Litros', 'Ya existe una carga activa idéntica o el token está utilizado. No se insertó otra carga. Identificador: '.$id);
                                }
                                throw $e;
                            }
                            if (! $this->matches($db->table($entry['table'])->where($entry['key'])->first(), $entry['after'])) {
                                throw new RuntimeException('No se verificó la escritura. Reintente con el identificador '.$id);
                            }
                        }
                        $this->journal->finish($db, $step, $entry['action'], $intent['actor']);
                    }
                    $this->journal->finish($db, $id, $action, $intent['actor']);
                } finally {
                    $this->operationTime = null;
                }
            } finally {
                if ($mysql) {
                    try {
                        $db->selectOne('SELECT RELEASE_LOCK(?)', ['combustible:'.$id]);
                    } catch (\Throwable) {
                        // Conexión caída; MySQL libera los named locks al cerrar la sesión.
                    }
                }
            }
        }, $action);
    }

    private function validatePendingReferences(Connection $db, array $entry): void
    {
        if ($entry['action'] === 'ELIMINAR_PRECIO') {
            $date = $entry['key']['Fecha'];
            if ($db->table('tblcostos')->where('IdCosto', 2)->max('Fecha') !== $date
                || $db->table('tblcargascombustible')->where('IdCosto', 2)->where('FechaPrecio', $date)->exists()) {
                $this->invalid('Fecha', 'El precio pendiente ya no es el último o ahora tiene cargas asociadas; no se eliminó.');
            }
        }
        if ($entry['action'] === 'PRECIO' && $this->version($this->priceState($db, $entry['key']['Fecha'])) !== $entry['confirmation']) {
            $this->invalid('Version', 'El conjunto confirmado cambió antes de guardar el precio. Revise de nuevo.');
        }
        if (! in_array($entry['action'], ['CREAR', 'EDITAR'], true)) {
            return;
        }
        $after = $entry['after'];
        $unit = $this->unit($db, (int) $after['IdUnidad']);
        if ($unit->Unidad !== $after['Unidad']) {
            throw new RuntimeException('La unidad cambió desde el envío original. Requiere conciliación.');
        }
        $before = $entry['before'];
        if ($before === null || $before['FechaCarga'] !== $after['FechaCarga']
            || Decimal::normalize((string) $before['Litros']) !== Decimal::normalize((string) $after['Litros'])) {
            $price = $this->priced($db, $after['FechaCarga'], (string) $after['Litros']);
            if ($this->version($price) !== $this->version(array_intersect_key($after, $price))) {
                throw new RuntimeException('El precio cambió desde el envío original. Requiere conciliación antes de guardar.');
            }
        }
    }

    private function matches(?object $current, ?array $expected): bool
    {
        if ($expected === null || $current === null) {
            return $expected === null && $current === null;
        }

        // Los INSERT omiten la clave AUTO_INCREMENT; comparar sólo campos previstos.
        return $this->version(array_intersect_key((array) $current, $expected)) === $this->version($expected);
    }

    private function isDuplicate(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062 || ($e->errorInfo[1] ?? null) === 19;
    }

    private function storedName(string $name): string
    {
        $latin = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $name);

        return iconv('ISO-8859-1', 'UTF-8', substr($latin === false ? 'Usuario' : $latin, 0, 150));
    }

    private function timestamp(): string
    {
        return $this->operationTime ?? now(config('combustible.timezone'))->format('Y-m-d H:i:s');
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
