<?php

namespace App\Support;

/**
 * Picture / colour / icon of each service card on the home page.
 *
 * Every service gets its own card. What the admin sets on the service
 * (Admin > System Settings > Services) always wins; anything left empty falls
 * back to the original artwork for the clinic's built-in services, matched by
 * key or by name, and finally to a distinct colour + icon so no two new
 * services look the same.
 */
class ServiceCardStyle
{
    public const COLORS = [
        'icon-blue'   => 'Blue',
        'icon-teal'   => 'Teal',
        'icon-indigo' => 'Indigo',
        'icon-cyan'   => 'Cyan',
        'icon-green'  => 'Green',
        'icon-pink'   => 'Pink',
        'icon-orange' => 'Orange',
        'icon-violet' => 'Violet',
    ];

    // Icons the admin can pick from (Font Awesome 6 free).
    public const ICONS = [
        'fa-stethoscope'    => 'Stethoscope',
        'fa-user-doctor'    => 'Doctor',
        'fa-child'          => 'Child',
        'fa-baby'           => 'Baby',
        'fa-person-dress'   => 'Woman',
        'fa-kit-medical'    => 'Medical kit',
        'fa-lungs'          => 'Lungs',
        'fa-eye'            => 'Eye',
        'fa-ear-listen'     => 'Ear',
        'fa-heart-pulse'    => 'Heart',
        'fa-ribbon'         => 'Ribbon',
        'fa-notes-medical'  => 'Medical notes',
        'fa-house-medical'  => 'Clinic',
        'fa-tooth'          => 'Tooth',
        'fa-bone'           => 'Bone',
        'fa-brain'          => 'Brain',
        'fa-syringe'        => 'Syringe',
        'fa-pills'          => 'Pills',
        'fa-microscope'     => 'Microscope',
        'fa-x-ray'          => 'X-ray',
    ];

    // Built-in services (by service_key).
    private const KNOWN = [
        'pediatrics'    => ['image' => '/images/pediatrics.png',             'color' => 'icon-blue',   'icon' => 'fa-child'],
        'obgyne'        => ['image' => '/images/ob_gynecology.png',          'color' => 'icon-teal',   'icon' => 'fa-person-dress'],
        'surgery'       => ['image' => '/images/general_surgery.png',        'color' => 'icon-indigo', 'icon' => 'fa-kit-medical'],
        'pulmonology'   => ['image' => '/images/im_pulmonology.png',         'color' => 'icon-cyan',   'icon' => 'fa-lungs'],
        'ophthalmology' => ['image' => '/images/ophthalmology_ent.png',      'color' => 'icon-green',  'icon' => 'fa-eye'],
        'cardiology'    => ['image' => '/images/im_cardiology.png',          'color' => 'icon-pink',   'icon' => 'fa-heart-pulse'],
        'adultmedicine' => ['image' => '/images/general_adult_medicine.png', 'color' => 'icon-orange', 'icon' => 'fa-user-doctor'],
        'oncology'      => ['image' => '/images/medical_oncology.png',       'color' => 'icon-violet', 'icon' => 'fa-ribbon'],
    ];

    // Same built-ins, recognised by wording in the title when the key differs.
    private const BY_NAME = [
        'pediatric'   => 'pediatrics',
        'gyne'        => 'obgyne',
        'surg'        => 'surgery',
        'pulmon'      => 'pulmonology',
        'ophthal'     => 'ophthalmology',
        'cardio'      => 'cardiology',
        'adult'       => 'adultmedicine',
        'oncolog'     => 'oncology',
    ];

    // Icons for services that have no built-in artwork.
    private const ICON_BY_NAME = [
        'ent'       => 'fa-ear-listen',
        'ear'       => 'fa-ear-listen',
        'internal'  => 'fa-notes-medical',
        'general'   => 'fa-house-medical',
        'practice'  => 'fa-house-medical',
        'dental'    => 'fa-tooth',
        'ortho'     => 'fa-bone',
        'neuro'     => 'fa-brain',
        'radiolog'  => 'fa-x-ray',
        'laborator' => 'fa-microscope',
    ];

    /**
     * @param object|array $service a services row (service_id, service_key, title, icon, card_image, card_color)
     */
    public static function for($service): array
    {
        $attributes = is_array($service)
            ? $service
            : (method_exists($service, 'getAttributes') ? $service->getAttributes() : (array) $service);
        $svc   = (object) $attributes;
        $key   = (string) ($svc->service_key ?? '');
        $title = mb_strtolower((string) ($svc->title ?? ''));

        $known = self::KNOWN[$key] ?? null;
        if (!$known) {
            foreach (self::BY_NAME as $needle => $knownKey) {
                if ($title !== '' && str_contains($title, $needle)) {
                    $known = self::KNOWN[$knownKey];
                    break;
                }
            }
        }

        // Admin choices first.
        $image = trim((string) ($svc->card_image ?? ''));
        $color = trim((string) ($svc->card_color ?? ''));
        $icon  = trim((string) ($svc->icon ?? ''));
        // Empty = "automatic", and services created before icons were selectable
        // carry the old generic default; both are picked from the service instead.
        if ($icon === 'fa-solid fa-stethoscope') {
            $icon = '';
        }

        if ($image === '' && $known) $image = $known['image'];
        if (!isset(self::COLORS[$color])) {
            $color = $known['color'] ?? self::fallbackColor((int) ($svc->service_id ?? 0));
        }
        if ($icon === '') {
            $icon = $known['icon'] ?? self::iconFromName($title) ?? 'fa-stethoscope';
        }

        return ['image' => $image !== '' ? $image : null, 'color' => $color, 'icon' => $icon];
    }

    private static function fallbackColor(int $id): string
    {
        $colors = array_keys(self::COLORS);
        return $colors[$id % count($colors)];
    }

    private static function iconFromName(string $title): ?string
    {
        foreach (self::ICON_BY_NAME as $needle => $icon) {
            if ($title === $needle || preg_match('/\b'.preg_quote($needle, '/').'/', $title)) {
                return $icon;
            }
        }
        return null;
    }
}