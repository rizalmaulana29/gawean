<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CmsUser extends Model 
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $primaryKey = 'id';
    protected $table = 'cms_users';
    protected $guarded = ['password'];
    // protected $casts = [
    //     'id_order' => 'string'
    // ];
    public $timestamps = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password'];
}
