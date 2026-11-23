<?php

namespace Database\Factories;

use App\Models\PortfolioImage;
use App\Models\PortfolioProject;
use Illuminate\Database\Eloquent\Factories\Factory;




class PortfolioImageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => PortfolioProject::factory(),
            'image' => 'demo/portfolio/'.fake()->uuid().'.jpg',
        ];
    }
}
