<?php
namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'description', 'icon', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}