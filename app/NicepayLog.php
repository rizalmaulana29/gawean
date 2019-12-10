<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class NicepayLog extends Model
{
    protected $table = 'nicepayLog';
    protected $fillable = [
        'request','response'
    ];
}
