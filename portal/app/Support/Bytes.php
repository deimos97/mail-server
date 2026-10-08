<?php

namespace App\Support;

/** Bytes → texto en formato español ("1,5 GB", "320 MB"). Unidades binarias, como la cuota de Dovecot. */
class Bytes
{
    public static function format(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }
        foreach (['GB' => 1024 ** 3, 'MB' => 1024 ** 2, 'KB' => 1024] as $unit => $size) {
            if ($bytes >= $size) {
                $value = $bytes / $size;

                return number_format($value, $value < 10 && fmod($value, 1) >= 0.05 ? 1 : 0, ',', '.').' '.$unit;
            }
        }

        return $bytes.' B';
    }
}
