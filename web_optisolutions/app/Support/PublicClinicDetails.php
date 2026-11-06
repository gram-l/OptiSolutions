<?php

namespace App\Support;

use App\Models\ClinicInfo;

class PublicClinicDetails
{
    public static function from(?object $clinic): array
    {
        $data = [];
        foreach (['clinic_name', 'about_us', 'mission', 'vision', 'address', 'contact_no', 'operating_hours', 'facebook_link', 'email', 'website'] as $field) {
            $value = $clinic->{$field} ?? '';
            $data[$field] = is_string($value) ? trim($value) : '';
        }
        $values = $clinic->core_values ?? [];
        if (is_string($values)) $values = json_decode($values, true);
        $data['core_values'] = is_array($values)
            ? array_values(array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : '', $values), fn ($value) => $value !== ''))
            : [];

        $facebook = $data['facebook_link'];
        $data['facebook_url'] = filter_var($facebook, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($facebook, PHP_URL_SCHEME)), ['http', 'https'], true) ? $facebook : '';
        $number = preg_replace('/[\s().-]+/', '', $data['contact_no']);
        $data['phone_url'] = preg_match('/^\+?[0-9]{3,20}$/', $number) ? 'tel:'.$number : '';
        $address = $data['address'];
        $data['directions_url'] = $address !== '' ? 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($address) : '';
        $data['map_url'] = $address !== '' ? 'https://www.google.com/maps?q='.rawurlencode($address).'&output=embed' : '';

        return $data;
    }

    public static function contactHelp(): string
    {
        try {
            $phone = trim((string) ClinicInfo::value('contact_no'));
            if ($phone !== '') return 'Please contact the clinic directly at '.$phone.'.';
        } catch (\Throwable $exception) {
            // This is also used when a database failure caused the original error.
        }

        return 'Please contact the clinic directly.';
    }
}
