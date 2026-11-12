<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Business;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collection>
 */
final class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name' => 'Linen Senja',
            'target_launch_on' => now()->addMonths(4)->toDateString(),
        ];
    }
}
