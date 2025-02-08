<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    protected $table = 'tbluser'; // Map to tbluser table

    protected $fillable = [
        'username', 'role', 'password','main_module', // existing columns
        'order', 'unreceived', 'receiving', 'labeling', 'testing', 'cleaning', 'packing', 'stockroom' // new boolean columns
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}