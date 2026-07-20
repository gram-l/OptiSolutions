<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'name', 'email', 'password', 'user_role', 'status',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function dashboardRoute()
    {
        return match ($this->user_role) {
            'Admin' => '/admin/dashboard',
            'Staff' => '/staff/dashboard',
            default => '/dashboard',
        };
    }
}