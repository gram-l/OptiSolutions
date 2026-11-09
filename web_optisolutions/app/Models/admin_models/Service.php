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
        'card_image', 'card_color',
    ];

    protected $casts = ['available' => 'boolean'];

    // Clinical focus areas (service_conditions), one per line, for the admin form.
    protected $appends = ['focus_areas'];

    public function getFocusAreasAttribute(): string
    {
        return \Illuminate\Support\Facades\DB::table('service_conditions')
            ->where('service_id', $this->service_id)
            ->pluck('condition_name')
            ->implode("\n");
    }

    public function doctors(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_service', 'service_id', 'doctor_id');
    }
}