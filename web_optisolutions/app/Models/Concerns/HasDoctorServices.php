<?php

namespace App\Models\Concerns;

use App\Models\admin_models\Service;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasDoctorServices
{
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'doctor_service', 'doctor_id', 'service_id')
            ->orderBy('services.title');
    }

    // Compatibility display field for existing web and mobile clients; never stored.
    public function getSpecialtyAttribute(): string
    {
        return $this->services->pluck('title')->implode(' / ');
    }
}
