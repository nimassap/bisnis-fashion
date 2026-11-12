<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'gender' => Gender::Women,
            'name' => 'Dress Senja',
            'status' => ProductStatus::Idea,
        ];
    }
}
