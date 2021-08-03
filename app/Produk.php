<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $table = 'ra_produk';
    protected $guarded = [];
    // protected $casts = [
    //     'id_bank' => 'string'
    // ];
    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
