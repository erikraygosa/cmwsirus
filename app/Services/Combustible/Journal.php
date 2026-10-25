<?php

namespace App\Services\Combustible;

use Illuminate\Database\Connection;
use RuntimeException;

/** Write-ahead journal in the existing TINYTEXT, without relying on transactions. */
class Journal
{
    public function header(Connection $db, string $id): ?array
    {
        $row = $db->table('tblexcepciones')->where('Modulo', 'combustible')
            ->where('Datos', 'like', $id.'|BEGIN|%')->value('Datos');

        return $row === null ? null : json_decode(substr($row, strlen($id.'|BEGIN|')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function begin(Connection $db, string $id, array $header, string $action, array $actor): void
    {
        if ($this->header($db, $id) === null) {
            $this->row($db, $action, $id.'|BEGIN|'.json_encode($header, JSON_THROW_ON_ERROR), $actor);
        }
    }

    public function read(Connection $db, string $id): ?array
    {
        $rows = $db->table('tblexcepciones')->where('Modulo', 'combustible')
            ->where('Datos', 'like', $id.'|P|%')->pluck('Datos')->all();

        return $this->decode($rows, $id);
    }

    public function decode(array $rows, string $id): ?array
    {
        if (! $rows) {
            return null;
        }
        $parts = [];
        $count = null;
        foreach ($rows as $row) {
            $fields = explode('|', $row, 4);
            [$index, $n] = array_map('intval', explode('/', $fields[2]));
            $count ??= $n;
            if ($index < 1 || $index > $n || $n < 1 || $count !== $n || (isset($parts[$index]) && $parts[$index] !== $fields[3])) {
                throw new RuntimeException('Auditoría inconsistente: '.$id);
            }
            $parts[$index] = $fields[3];
        }
        if (count($parts) !== $count) {
            return null; // No se ejecuta ningún cambio antes de persistir la intención completa.
        }
        ksort($parts);

        return json_decode(implode('', $parts), true, 512, JSON_THROW_ON_ERROR);
    }

    public function write(Connection $db, string $id, string $action, array $data, array $actor): void
    {
        // JSON ASCII conserva incluso nombres Unicode en una tabla latin1 sin pérdida.
        $json = json_encode($data, JSON_THROW_ON_ERROR);
        $chunks = str_split($json, 170);
        foreach ($chunks as $index => $chunk) {
            $prefix = $id.'|P|'.($index + 1).'/'.count($chunks).'|';
            $existing = $db->table('tblexcepciones')->where('Modulo', 'combustible')
                ->where('Datos', 'like', $prefix.'%')->value('Datos');
            if ($existing !== null && $existing !== $prefix.$chunk) {
                throw new RuntimeException('Conflicto en auditoría parcial. Conserve el identificador '.$id.' para conciliación.');
            }
            if ($existing === null) {
                $this->row($db, $action, $prefix.$chunk, $actor);
            }
        }
        if (json_encode($this->read($db, $id), JSON_THROW_ON_ERROR) !== $json) {
            throw new RuntimeException('No se pudo verificar la auditoría completa: '.$id);
        }
    }

    public function done(Connection $db, string $id): bool
    {
        return $db->table('tblexcepciones')->where('Modulo', 'combustible')->where('Datos', $id.'|OK')->exists();
    }

    public function finish(Connection $db, string $id, string $action, array $actor): void
    {
        if (! $this->done($db, $id)) {
            $this->row($db, $action, $id.'|OK', $actor);
        }
    }

    private function row(Connection $db, string $action, string $data, array $actor): void
    {
        if (strlen($data) > 255) {
            throw new RuntimeException('Fragmento de auditoría demasiado largo.');
        }
        $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $actor['name']);
        $name = iconv('ISO-8859-1', 'UTF-8', substr($name === false ? 'Usuario' : $name, 0, 30));
        $db->table('tblexcepciones')->insert([
            'Fecha' => now(config('combustible.timezone'))->format('Y-m-d H:i:s'),
            'Usuario' => $name, 'Modulo' => 'combustible', 'Excepcion' => $action, 'Datos' => $data,
        ]);
    }
}
