<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AdminEntitas extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id_entitas';
    protected $table = 'admin_entitas';
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
