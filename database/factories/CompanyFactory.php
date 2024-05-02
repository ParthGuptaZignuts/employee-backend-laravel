<?php

namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

     protected $model = Company::class;
    public function definition(): array
    {
        $originalName = $this->faker->company;
        $stateName = $this->faker->state;

        return [
            'name' => substr($originalName, 0, 5),
            'email' => $this->faker->companyEmail,
            'website' => $this->faker->domainName,
            'address' => substr($stateName,0,4),
            'status' => 'A',
            'logo' => 'logoOne.png'
        ];
    }
}
