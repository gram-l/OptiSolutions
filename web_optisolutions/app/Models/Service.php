<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'service_id';
    public $timestamps = false;

    protected $fillable = [
        'service_key', 'title', 'icon', 'description', 'room', 'schedule', 'available',
    ];

    public function conditions()
    {
        return $this->hasMany(ServiceCondition::class, 'service_id', 'service_id');
    }

    public function doctorsList()
    {
        return $this->hasMany(ServiceDoctor::class, 'service_id', 'service_id');
    }
}