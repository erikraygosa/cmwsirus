<?php

namespace App\Services\Combustible;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Validation\ValidationException;

class Decimal
{
    public static function normalize(string $value, int $scale = 3): string
    {
        return (string) BigDecimal::of($value)->toScale($scale);
    }

    public static function amount(string $litres, string $price, string $discount): string
    {
        if (! BigDecimal::of($discount)->isZero()) {
            throw ValidationException::withMessages(['PrecioLitro' => 'El descuento no es cero. Falta verificar su convención antes de calcular; no se guardó el cálculo.']);
        }
        $amount = BigDecimal::of($litres)->multipliedBy($price)->toScale(2, RoundingMode::HALF_UP);
        if ($amount->isGreaterThan('999999999999.99')) {
            throw ValidationException::withMessages(['Litros' => 'El importe excede la capacidad de la tabla.']);
        }

        return (string) $amount;
    }

    public static function sum(iterable $values, int $scale): string
    {
        $total = BigDecimal::zero();
        foreach ($values as $value) {
            $total = $total->plus((string) $value);
        }

        return (string) $total->toScale($scale, RoundingMode::HALF_UP);
    }
}
