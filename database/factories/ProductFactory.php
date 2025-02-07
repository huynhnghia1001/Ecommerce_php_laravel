<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->name();
        $slug = Str::slug($title);

        $subCategories = [8,9,10,11];
        $subCatRandKey = array_rand($subCategories);

        $brand = [1,2,4,5,6,7];
        $brandRandKey = array_rand($brand);
        return [
            'title' => $title,
            'slug' => $slug,
            'category_id' => 8,
            'sub_category_id' => $subCategories[$subCatRandKey],
            'brand_id' => $brand[$brandRandKey],
            'price' => rand(10,1000),
            'sku' => rand(1000,100000),
            'track_qty' => 'yes',
            'qty'=>10,
            'is_featured'=>'yes',
            'status'=>1,
        ];
    }
}
