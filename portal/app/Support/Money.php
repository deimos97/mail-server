<?php

namespace App\Support;

/** Importes en céntimos → texto en euros, formato español ("1,99 €"). */
class Money
{
    public static function format(?int $cents, bool $trimZeroCents = false): string
    {
        if ($cents === null) {
            return '—';
        }
        $decimals = $trimZeroCents && $cents % 100 === 0 ? 0 : 2;

        return number_format($cents / 100, $decimals, ',', '.').' €';
    }
}
