<?php

namespace App\Support;

class Currency
{
    public static function format(?float $amount, int $decimals = 2): string
    {
        if ($amount === null) {
            return '';
        }

        return config('currency.symbol').number_format((float) $amount, $decimals);
    }

    public static function code(): string
    {
        return (string) config('currency.code', 'EUR');
    }

    public static function symbol(): string
    {
        return (string) config('currency.symbol', '€');
    }
}
