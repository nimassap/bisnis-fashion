<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
final class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'working_name' => 'Proyek Couple',
            'brand_name' => null,
            'starting_capital' => '25000000.00',
        ];
    }
}
