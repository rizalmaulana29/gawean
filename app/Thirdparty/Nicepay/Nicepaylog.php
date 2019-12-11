<?php

namespace App\Thirdparty\Nicepay;

use Illuminate\Database\Eloquent\Model;

class Nicepaylog
{
    protected $primaryKey = 'id';
    protected $table = 'ra_nicepayLog';
    protected $fillable = [
        'id_order','txid','no_reference','id_merchant','virtual_account_no','update','request','response','status'
    ];

    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
