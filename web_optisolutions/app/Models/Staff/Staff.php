<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Staff extends Authenticatable
{
    use HasFactory;

    protected $table = 'staff';
    protected $primaryKey = 'staff_id';

    protected $fillable = [
        'staff_name',
        'staff_email',
        'staff_password',
        'staff_role',
    ];

    protected $hidden = [
        'staff_password',
        'remember_token',
    ];
}