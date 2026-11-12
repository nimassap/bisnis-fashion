<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\VendorStatus;
use App\Enums\VendorType;
use App\Models\Business;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
final class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'type' => VendorType::Maker,
            'name' => 'Konveksi '.fake()->lastName(),
            'phone' => '0812'.fake()->numerify('########'),
            'location' => 'Bandung',
            'moq' => 50,
            'unit_price' => '35000.00',
            'price_unit' => 'pcs',
            'lead_time_days' => 21,
            'rating' => 4,
            'status' => VendorStatus::Prospect,
        ];
    }
}
