<?php

namespace Database\Factories;

use App\Models\JobDescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories.Factory<\App\Models\Jobs>
 */
class JobDescriptionFactory extends Factory
{
    protected $model = JobDescription::class; 

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Employment types to choose from
        $employmentTypes = [
            'Full time',
            'Part time',
            'Hybrid',
            'Work from Home',
            'Work From Office',
            'Internship',
        ];

        // Experience levels
        $experienceLevels = [
            'Low Level (0 to 1 Yrs)',
            'Medium Level (2 TO 5 Yrs)',
            'High Level (5+ Yrs)',
        ];

        // Skills to choose from
        $skillsRequired = [
            "MERN",
            "MEAN",
            "LARAVEL+VUE",
            "FLUTTER",
            "DEVOPS",
            "UI / UX",
            "ANDROID",
            "SALESFORCE",
            "REACT.JS",
            "NODE.JS",
            "AWS",
            "DBA",
        ];

        $employmentType = Arr::random($employmentTypes);
        $experienceRequired = Arr::random($experienceLevels);
        $skillRequired = Arr::random($skillsRequired);
        $Name = $this->faker->jobTitle;

        return [
            'company_id' => 1, 
            'title' =>substr($Name, 0, 5), 
            'salary' => $this->faker->numberBetween(50000, 100000), 
            'employment_type' => $employmentType,
            'experience_required' => $experienceRequired,
            'skills_required' => $skillRequired,
            'posted_date' => Carbon::now(),
            'expiry_date' => Carbon::now()->addDays(30),
        ];
    }
}
