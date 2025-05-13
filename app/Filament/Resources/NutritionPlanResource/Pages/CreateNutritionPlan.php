<?php

namespace App\Filament\Resources\NutritionPlanResource\Pages;

use App\Filament\Resources\NutritionPlanResource;
use App\Models\DailyMenu;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateNutritionPlan extends CreateRecord
{
    protected static string $resource = NutritionPlanResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Begin a database transaction
        return DB::transaction(function () use ($data) {
            // Check if this is from an existing nutrition plan
            $nutritionPlanId = $data['selected_nutrition_plan'] ?? null;
            
            if ($nutritionPlanId) {
                // We are using an existing nutrition plan, so get it
                $record = static::getModel()::find($nutritionPlanId);
                
                // If not found, create a new one
                if (!$record) {
                    $record = static::getModel()::create([
                        'date' => $data['date'],
                    ]);
                }
            } else {
                // Create the nutrition plan record
                $record = static::getModel()::create([
                    'date' => $data['date'],
                ]);
                
                // If we have nutrition items, create them
                if (isset($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        $record->items()->create([
                            'nutrient_id' => $item['nutrient_id'],
                            'amount' => $item['amount'],
                        ]);
                    }
                }
            }

            // Now handle the daily menu items
            if (isset($data['menu_items']) && is_array($data['menu_items'])) {
                // Buat satu record DailyMenu untuk tanggal ini
                $dailyMenu = DailyMenu::create([
                    'nutrition_plan_id' => $record->id,
                    'date' => $data['date'],
                ]);

                // Buat DailyMenuItem untuk setiap menu_item
                foreach ($data['menu_items'] as $menuItem) {
                    // Update menu_name di nutrition_plan_items
                    if (isset($menuItem['item_id']) && $menuItem['item_id']) {
                        $item = $record->items()->find($menuItem['item_id']);
                        if ($item) {
                            $item->update([
                                'menu_name' => $menuItem['menu_name'],
                            ]);
                        }
                    }

                    // Buat record DailyMenuItem
                    $dailyMenu->items()->create([
                        'nutrient_id' => $menuItem['nutrient_id'],
                        'menu_name' => $menuItem['menu_name'],
                    ]);
                }
            }

            return $record;
        });
    }
}