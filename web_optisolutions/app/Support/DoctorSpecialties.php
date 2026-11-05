<?php

namespace App\Support;

class DoctorSpecialties
{
    public static function catalog(): \Illuminate\Support\Collection
    {
        return \Illuminate\Support\Facades\DB::table('services')->orderBy('title')->pluck('title')
            ->flatMap(fn ($title) => self::split($title))->unique()->sort()->values();
    }

    public static function split(?string $specialty): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode('/', $specialty ?? '')),
            fn ($value) => $value !== '')));
    }
}
