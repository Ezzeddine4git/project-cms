<?php

namespace App\Support;

class Money
{
    public static function format(float|string $amount): string
    {
        return number_format((float) $amount, 3, ',', ' ').' TND';
    }
}
