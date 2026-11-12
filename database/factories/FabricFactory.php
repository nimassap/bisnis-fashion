<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FabricStatus;
use App\Models\Business;
use App\Models\Fabric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fabric>
 */
final class FabricFactory extends Factory
{
    protected $model = Fabric::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => 'Linen Rami',
            'composition' => '55% linen, 45% rayon',
            'gsm' => 160,
            'width_cm' => 145,
            'price_per_meter' => '48000.00',
            'status' => FabricStatus::Candidate,
        ];
    }
}
