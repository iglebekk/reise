<?php

namespace App\Services;

final class InspirationCatalog
{
    /** @var array<string, string> */
    private const PACKING_ITEMS = [
        'passport' => 'Passport',
        'phone' => 'Phone',
        'phone charger' => 'Phone charger',
        'power bank' => 'Power bank',
        'toothbrush' => 'Toothbrush',
        'toothpaste' => 'Toothpaste',
        'sunscreen' => 'Sunscreen',
        'water bottle' => 'Water bottle',
        'rain jacket' => 'Rain jacket',
        'walking shoes' => 'Walking shoes',
        'towel' => 'Towel',
        'hat' => 'Hat',
    ];

    /**
     * @return list<string>
     */
    public static function tripTypes(): array
    {
        return ['family', 'business', 'adventure'];
    }

    /**
     * @return list<string>
     */
    public static function transportOptions(): array
    {
        return ['car', 'train', 'plane'];
    }

    /**
     * @return list<string>
     */
    public static function accommodationOptions(): array
    {
        return ['hotel', 'cabin', 'camping'];
    }

    /**
     * @return list<string>
     */
    public static function activityOptions(): array
    {
        return ['beach', 'camping', 'cycling', 'hiking', 'museums', 'sightseeing', 'skiing', 'swimming'];
    }

    public static function canonicalItemName(string $name): ?string
    {
        return self::PACKING_ITEMS[mb_strtolower(trim($name), 'UTF-8')] ?? null;
    }
}
