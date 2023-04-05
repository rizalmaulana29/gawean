<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SurveyMaster extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $table = 'ra_survey_master';
    protected $guarded = [];

    // public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
