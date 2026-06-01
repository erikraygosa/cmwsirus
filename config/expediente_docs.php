<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catálogo de documentos — Lineamientos ATY 2026
    |--------------------------------------------------------------------------
    | nomenclatura → NOMBREARCHIVO que se usa al nombrar el archivo en Drive
    |                El archivo queda: {CURP}_{nomenclatura}.{ext}
    | obligatorio  → cuenta para el % de completitud
    | zip          → se incluye en descarga ZIP por defecto
    | drive        → se incluye en envío masivo a Drive por defecto
    |--------------------------------------------------------------------------
    */
    'documentos' => [

        // ── Documentos obligatorios ──────────────────────────────────────────

        'INE' => [
            'nombre'      => 'Identificación oficial (INE)',
            'icono'       => 'fas fa-id-card',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'INE/IFE vigente — anverso y reverso en un solo archivo',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'INE',
        ],

        'LICENCIA' => [
            'nombre'        => 'Licencia TPP',
            'icono'         => 'fas fa-car',
            'accept'        => 'image/*,application/pdf',
            'descripcion'   => 'Licencia vigente tipo TPP emitida por la SSP — ambas caras en un solo archivo',
            'obligatorio'   => true,
            'zip'           => true,
            'drive'         => true,
            'nomenclatura'  => 'LICENCIA',
            'campo_vigencia'=> 'FechaVL',
        ],

        'COMP_DOM' => [
            'nombre'      => 'Comprobante de domicilio',
            'icono'       => 'fas fa-home',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'No mayor a 6 meses (digital CFE o copia fiel)',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'DOMICILIO',
        ],

        'CERT_MEDICO' => [
            'nombre'      => 'Certificado médico',
            'icono'       => 'fas fa-stethoscope',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Certificado médico anual emitido por institución pública (IMSS, ISSTEY, SSY)',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'CERTMEDICO',
        ],

        'TOXICOLOGICO' => [
            'nombre'      => 'Exámenes médico-toxicológicos',
            'icono'       => 'fas fa-vial',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Exámenes anuales emitidos por institución pública de salud',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'TOXICOLOGICO',
        ],

        'TARJETON' => [
            'nombre'      => 'Tarjetón único',
            'icono'       => 'fas fa-id-badge',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Último tarjetón único o certificado vigente',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'TARJETON',
        ],

        'REG_INCIDENTES' => [
            'nombre'      => 'Registro de incidentes',
            'icono'       => 'fas fa-exclamation-circle',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Registro de incidentes e infracciones de tránsito del último año',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'INCIDENTES',
        ],

        'CAP_CONTINUA' => [
            'nombre'      => 'Comprobación de capacitación',
            'icono'       => 'fas fa-graduation-cap',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Comprobación de capacitación continua',
            'obligatorio' => true,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'CAPCONTINUA',
        ],

        // ── Documentos adicionales (complementarios) ─────────────────────────

        'ACTA' => [
            'nombre'      => 'Acta de nacimiento',
            'icono'       => 'fas fa-baby',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Original o copia certificada — legible y completa',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'ACTANACIMIENTO',
        ],

        'CURP' => [
            'nombre'      => 'CURP',
            'icono'       => 'fas fa-fingerprint',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Versión actualizada emitida por RENAPO',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'CURP',
        ],

        'IMSS' => [
            'nombre'      => 'Alta IMSS / NSS',
            'icono'       => 'fas fa-hospital',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Hoja de afiliación o constancia IMSS',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'NSS',
        ],

        'CONTRATO' => [
            'nombre'      => 'Contrato firmado',
            'icono'       => 'fas fa-file-signature',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Contrato individual de trabajo',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => false,
            'nomenclatura'=> 'CONTRATO',
        ],

        'EXAMEN_VISTA' => [
            'nombre'      => 'Examen de la vista',
            'icono'       => 'fas fa-eye',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Examen anual emitido por institución pública de salud',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'EXAMENVISTA',
        ],

        'RFC' => [
            'nombre'      => 'RFC / Constancia situación fiscal',
            'icono'       => 'fas fa-file-invoice',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Constancia de situación fiscal vigente al ejercicio actual',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'RFC',
        ],

        'CONST_NO_DEUDOR' => [
            'nombre'      => 'Constancia no deudor alimentario',
            'icono'       => 'fas fa-balance-scale',
            'accept'      => 'image/*,application/pdf',
            'descripcion' => 'Constancia de no deudor alimentario',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'NODEUDOR',
        ],

        'FOTO' => [
            'nombre'      => 'Fotografía del operador',
            'icono'       => 'fas fa-camera',
            'accept'      => 'image/jpeg,image/png',
            'descripcion' => 'Foto reciente (máx 6 meses) — fondo blanco, frente, con uniforme, sin accesorios. Mín 300 dpi.',
            'obligatorio' => false,
            'zip'         => true,
            'drive'       => true,
            'nomenclatura'=> 'FOTO',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Almacenamiento
    |--------------------------------------------------------------------------
    */
    'disco'    => 'public',
    'bucket'   => 'expedientes',
    'tabla_bd' => 'EMPLEADO',
    'max_mb'   => 10,
];