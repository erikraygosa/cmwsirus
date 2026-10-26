<?php

namespace App\Console\Commands;

use App\Services\Combustible\Database;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class ActualizarCapturaCombustible extends Command
{
    protected $signature = 'combustible:actualizar-captura {--aplicar : Agregar las columnas faltantes}';

    protected $description = 'Revisa o agrega número de ticket y kilometraje en la base configurada de combustible';

    public function handle(Database $database): int
    {
        $db = $database->connection(); // Valida driver y base real; no usa la conexión predeterminada.
        $schema = $db->getSchemaBuilder();
        if (! $schema->hasTable('tblcargascombustible')) {
            $this->error('No existe tblcargascombustible en la base de combustible.');

            return self::FAILURE;
        }
        $ticket = ! $schema->hasColumn('tblcargascombustible', 'NumeroTicket');
        $km = ! $schema->hasColumn('tblcargascombustible', 'Km');
        if (! $ticket && ! $km) {
            $this->info('Número de ticket y kilometraje ya están disponibles.');

            return self::SUCCESS;
        }
        $this->line('Base: '.$db->getDatabaseName().'; tabla: tblcargascombustible.');
        $this->line('Columnas faltantes: '.implode(', ', array_filter([$ticket ? 'NumeroTicket VARCHAR(50) NULL' : null, $km ? 'Km INT UNSIGNED NULL' : null])).'.');
        if (! $this->option('aplicar')) {
            $this->info('Revisión sin cambios. Use --aplicar para agregar las columnas faltantes.');

            return self::SUCCESS;
        }
        $schema->table('tblcargascombustible', function (Blueprint $table) use ($ticket, $km) {
            if ($ticket) {
                $table->string('NumeroTicket', 50)->nullable();
            }
            if ($km) {
                $table->unsignedInteger('Km')->nullable();
            }
        });
        $this->info('Campos agregados. Los registros anteriores conservan NULL en las columnas nuevas.');

        return self::SUCCESS;
    }
}
