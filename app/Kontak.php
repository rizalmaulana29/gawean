<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Kontak extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $table = 'ra_kontak';
    protected $guarded = [];
    // protected $casts = [
    //     'id_order' => 'string'
    // ];
    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
