<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $table = 'ra_survey';
    protected $guarded = [];

    // public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
