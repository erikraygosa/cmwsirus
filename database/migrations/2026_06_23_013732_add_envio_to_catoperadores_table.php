<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conexión donde vive catoperadores (bdcbcsirus).
     */
    protected $connection = 'mysql3';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql3')->table('catoperadores', function (Blueprint $table) {
            if (!Schema::connection('mysql3')->hasColumn('catoperadores', 'envio')) {
                $table->tinyInteger('envio')->default(0)->after('IdOperOld');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql3')->table('catoperadores', function (Blueprint $table) {
            $table->dropColumn('envio');
        });
    }
};
