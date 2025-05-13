<?php

namespace Database\Seeders;

use App\Models\Nutrient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NutrientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nutrients = [
            ['name' => 'Vitamin C', 'unit' => 'mg'],
            ['name' => 'Vitamin D', 'unit' => 'mcg'],
            ['name' => 'Kalsium', 'unit' => 'mg'],
            ['name' => 'Zat Besi', 'unit' => 'mg'],
            ['name' => 'Protein', 'unit' => 'g'],
            ['name' => 'Serat', 'unit' => 'g'],
            ['name' => 'Magnesium', 'unit' => 'mg'],
            ['name' => 'Zinc', 'unit' => 'mg'],
            ['name' => 'Vitamin B12', 'unit' => 'mcg'],
            ['name' => 'Omega-3', 'unit' => 'g']
        ];

        foreach ($nutrients as $nutrient) {
            Nutrient::firstOrCreate(
                ['name' => $nutrient['name']],
                $nutrient
            );
        }
    }
}