<?php

namespace App\Services\Combustible;

use Illuminate\Database\Query\Builder;

class Reports
{
    public function __construct(private Database $database) {}

    public function query(array $filters): Builder
    {
        $field = ($filters['tipo_fecha'] ?? 'carga') === 'trabajo' ? 'FechaTrabajo' : 'FechaCarga';
        $query = $this->database->connection()->table('tblcargascombustible')
            ->whereBetween($field, [$filters['desde'], $filters['hasta']]);
        foreach (['IdUnidad', 'Turno', 'Estatus'] as $column) {
            if (! empty($filters[$column])) {
                $query->where($column, $filters[$column]);
            }
        }

        return $query;
    }

    public function totals(Builder $query): array
    {
        if (in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            // SUM(DECIMAL) es exacto en MySQL: una fila de totales, no todo el detalle.
            $row = (clone $query)->where('Estatus', 'A')->reorder()
                ->selectRaw('COALESCE(SUM(Litros), 0) AS litros, COALESCE(SUM(Importe), 0) AS importe')->first();

            return ['Litros' => Decimal::normalize((string) $row->litros), 'Importe' => Decimal::normalize((string) $row->importe, 2)];
        }
        // SQLite usa punto flotante para SUM; mantener la suma decimal en los dobles de prueba.
        $litres = \Brick\Math\BigDecimal::zero();
        $amount = \Brick\Math\BigDecimal::zero();
        foreach ((clone $query)->where('Estatus', 'A')->select(['Litros', 'Importe'])->cursor() as $row) {
            $litres = $litres->plus((string) $row->Litros);
            $amount = $amount->plus((string) $row->Importe);
        }

        return ['Litros' => (string) $litres->toScale(3), 'Importe' => (string) $amount->toScale(2)];
    }

    /** XLSX auténtico con celdas de texto explícitas: nunca fórmulas de datos del usuario. */
    public function excel(iterable $rows, array $totals): string
    {
        $target = tempnam(sys_get_temp_dir(), 'combustible-xlsx-');
        $sheet = tempnam(sys_get_temp_dir(), 'combustible-sheet-');
        $handle = fopen($sheet, 'wb');
        try {
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
            $write = function (array $values) use ($handle) {
                fwrite($handle, '<row>');
                foreach ($values as $column => $value) {
                    $v = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    if (in_array($column, [5, 7, 8], true) && preg_match('/^\d+(\.\d+)?$/', (string) $value)) {
                        fwrite($handle, '<c t="n"><v>'.$v.'</v></c>');
                    } else {
                        fwrite($handle, '<c t="inlineStr"><is><t xml:space="preserve">'.$v.'</t></is></c>');
                    }
                }
                fwrite($handle, '</row>');
            };
            $write(['Folio', 'Fecha carga', 'Fecha trabajo', 'Turno', 'Unidad', 'Litros', 'Fecha precio', 'Precio litro', 'Importe', 'Estatus', 'Observaciones', 'Motivo cancelación']);
            foreach ($rows as $row) {
                $write([$row->IdCarga, $row->FechaCarga, $row->FechaTrabajo, $row->Turno, $row->Unidad,
                    Decimal::normalize((string) $row->Litros), $row->FechaPrecio, Decimal::normalize((string) $row->PrecioLitro),
                    Decimal::normalize((string) $row->Importe, 2), $row->Estatus === 'A' ? 'ACTIVA' : 'CANCELADA', $row->Observaciones, $row->MotivoCancelacion]);
            }
            $write(['TOTALES ACTIVAS', '', '', '', '', $totals['Litros'], '', '', $totals['Importe']]);
            fwrite($handle, '</sheetData></worksheet>');
            fclose($handle);
            $handle = null;
            $zip = new \ZipArchive;
            if ($zip->open($target, \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo crear Excel.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Combustible" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFile($sheet, 'xl/worksheets/sheet1.xml');
            if (! $zip->close()) {
                throw new \RuntimeException('No se pudo cerrar Excel.');
            }

            return $target;
        } catch (\Throwable $e) {
            @unlink($target);
            throw $e;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            @unlink($sheet);
        }
    }
}
