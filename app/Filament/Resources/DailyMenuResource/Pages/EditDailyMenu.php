<?php

namespace App\Filament\Resources\DailyMenuResource\Pages;

use App\Filament\Resources\DailyMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\DailyMenu;

class EditDailyMenu extends EditRecord
{
    protected static string $resource = DailyMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }

    // Tambahkan hook mutateFormDataBeforeFill untuk memastikan data nutrisi terisi
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Ambil record dailyMenu dengan relasi items dan nutrient
        $dailyMenu = DailyMenu::with('items.nutrient')->find($this->record->id);
        
        if ($dailyMenu) {
            // Persiapkan items dengan data nutrient yang lengkap
            $items = $dailyMenu->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nutrient_id' => $item->nutrient_id,
                    'menu_name' => $item->menu_name,
                    'qty' => $item->qty,
                    'nutrient_name' => $item->nutrient->name ?? '',
                    'unit' => $item->nutrient->unit ?? '',
                    'amount' => $item->amount,
                ];
            })->toArray();
            
            $data['items'] = $items;
        }
        
        return $data;
    }

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}