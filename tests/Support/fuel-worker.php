<?php

// Sólo para la suite de concurrencia del contenedor local desechable.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (getenv('FUEL_MYSQL_TEST') !== '1') {
    exit(2);
}
Tests\Support\FuelSchema::connect();
$service = app(App\Services\Combustible\FuelService::class);
$input = json_decode(base64_decode($argv[2]), true, 512, JSON_THROW_ON_ERROR);
$actor = ['id' => '123', 'name' => 'Prueba concurrente'];
try {
    $result = $argv[1] === 'notice' ? $service->notice($actor) : $service->create($input, $actor)->IdCarga;
    echo json_encode(['ok' => $result], JSON_THROW_ON_ERROR);
} catch (Illuminate\Validation\ValidationException $e) {
    echo json_encode(['duplicate' => true]);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
