<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Último acceso por IMAP de un buzón y dispositivo (BD `mailserver`). Lo escribe Dovecot (last_login);
 * la web solo lee. device = id de app_passwords; 0 = sin contraseña de dispositivo (webmail).
 */
class LastLogin extends Model
{
    protected $connection = 'mailserver';

    protected $table = 'last_logins';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['device' => 'integer', 'last_login' => 'integer'];
    }

    public function at(): Carbon
    {
        return Carbon::createFromTimestampUTC($this->last_login);
    }
}
