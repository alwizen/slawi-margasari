<?php

namespace App\Filament\Resources\StockIssueResource\Pages;

use App\Filament\Resources\StockIssueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockIssue extends CreateRecord
{
    protected static string $resource = StockIssueResource::class;
    // protected static bool $canCreateAnother = false;

    //customize redirect after create
    public function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        $record = static::getModel()::create($data);
        
        // Handle pembuatan items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                // Pastikan requested_quantity tersedia
                if (!isset($itemData['requested_quantity']) && isset($itemData['quantity'])) {
                    $itemData['requested_quantity'] = $itemData['quantity'];
                    unset($itemData['quantity']);
                }
                
                $record->items()->create($itemData);
            }
        }
        
        return $record;
    }


}
