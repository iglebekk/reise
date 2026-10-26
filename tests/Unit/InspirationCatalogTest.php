<?php

use App\Services\InspirationCatalog;

test('packing item names match only after trimming and case folding', function (): void {
    expect(InspirationCatalog::canonicalItemName('  PHONE CHARGER  '))
        ->toBe('Phone charger');
});

test('packing item names reject unlisted, fuzzy, and private-looking text', function (string $name): void {
    expect(InspirationCatalog::canonicalItemName($name))->toBeNull();
})->with([
    'unlisted word' => 'charger cable',
    'fuzzy phrase' => 'phone charger cable',
    'personal name and item' => "John's medication",
    'contact detail' => '+47 900 00 000',
    'identifier' => 'passport number AB-123',
]);

test('trip options are fixed reusable values', function (): void {
    expect(InspirationCatalog::tripTypes())->toBe(['family', 'business', 'adventure'])
        ->and(InspirationCatalog::transportOptions())->toBe(['car', 'train', 'plane'])
        ->and(InspirationCatalog::accommodationOptions())->toBe(['hotel', 'cabin', 'camping'])
        ->and(InspirationCatalog::activityOptions())->toBe([
            'beach',
            'camping',
            'cycling',
            'hiking',
            'museums',
            'sightseeing',
            'skiing',
            'swimming',
        ]);
});
