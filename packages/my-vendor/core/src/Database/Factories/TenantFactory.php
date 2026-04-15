<?php

namespace VHAP\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use VHAP\Core\Models\Tenant;

class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a clean slug from the fake company name
        $company = $this->faker->unique()->company();
        $slug = Str::slug($company);

        return [
            'name' => $company,
            'domain' => $slug . '.myapp.com',
            'database' => 'tenant_' . str_replace('-', '_', $slug),
        ];
    }
}