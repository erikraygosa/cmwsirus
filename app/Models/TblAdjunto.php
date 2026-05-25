<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TblAdjunto extends Model
{
    protected $connection = 'mysql2';
    protected $table      = 'tbladjuntos';
    protected $primaryKey = 'Id';  

    public $timestamps = false; // manejamos Creado / created_at / updated_at manual

    protected $fillable = [
        'Tabla',
        'IdRegTab',
        'Bucket',
        'FullFileName',
        'OriginalFileName',
        'Peso',
        'Comentarios',
        'IdDocRel',
        'Estatus',
        'Creado',
        'created_at',
        'updated_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes de conveniencia
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos de un empleado específico por tipo.
     *
     * Uso: TblAdjunto::delEmpleado($idEmpleado)->porTipo('INE')->first()
     */
    public function scopeDelEmpleado($query, int $idEmpleado): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->where('Tabla', config('expediente_docs.tabla_bd'))
            ->where('IdRegTab', $idEmpleado);
    }

    public function scopePorTipo($query, string $tipo): \Illuminate\Database\Eloquent\Builder
    {
        // El tipo se guarda en Comentarios con prefijo TIPO:
        return $query->where('Comentarios', 'like', "TIPO:{$tipo}%");
    }

    public function scopeActivo($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('Estatus', '!=', 'ELIMINADO');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** URL pública del archivo */
    public function urlPublica(): string
    {
        return asset('storage/' . $this->FullFileName);
    }

    /** Tipo de documento extraído de Comentarios */
    public function tipoDocumento(): string
    {
        if (preg_match('/TIPO:([A-Z_]+)/', $this->Comentarios ?? '', $m)) {
            return $m[1];
        }
        return '';
    }

    /** ¿Es una imagen? */
    public function esImagen(): bool
    {
        $ext = strtolower(pathinfo($this->OriginalFileName, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }
}