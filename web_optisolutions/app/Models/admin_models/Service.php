<?php
namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
   protected $table = 'services';
    protected $primaryKey = 'service_id';
    public $timestamps = false;

    protected $fillable = [
        'service_key', 'title', 'icon', 'description', 'room', 'schedule', 'available',
    ];

    protected $casts = ['available' => 'boolean'];
}