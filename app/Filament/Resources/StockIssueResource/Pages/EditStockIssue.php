<?php

namespace App\Filament\Resources\StockIssueResource\Pages;

use App\Filament\Resources\StockIssueResource;
use App\Models\StockIssue;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditStockIssue extends EditRecord
{
    protected static string $resource = StockIssueResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (StockIssue $record) => $record->status === 'draft'),
                
            Actions\Action::make('request')
                ->label('Kirim Request')
                ->color('primary')
                ->action(function () {
                    $this->record->status = 'requested';
                    $this->record->save();
                    
                    $this->notify('success', 'Request bahan berhasil dikirim ke gudang');
                })
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Pengiriman Request')
                ->modalSubheading('Apakah Anda yakin ingin mengirim request ini ke gudang?')
                ->modalButton('Ya, Kirim Request')
                ->visible(fn () => $this->record->status === 'draft'),
                
            Actions\Action::make('process')
                ->label('Proses Request')
                ->color('warning')
                ->url(fn (StockIssue $record): string => route('filament.resources.stock-issues.process', $record))
                ->visible(fn () => in_array($this->record->status, ['requested', 'preparing'])),
                
            Actions\Action::make('complete')
                ->label('Selesaikan')
                ->color('success')
                ->action(function () {
                    // Proses pengurangan stok
                    foreach ($this->record->items as $item) {
                        $warehouseItem = $item->warehouseItem;
                        if ($item->issued_quantity > 0) {
                            $warehouseItem->quantity -= $item->issued_quantity;
                            $warehouseItem->save();
                        }
                    }
                    
                    $this->record->status = 'completed';
                    $this->record->save();
                    
                    $this->notify('success', 'Pengeluaran bahan berhasil diselesaikan');
                })
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Penyelesaian')
                ->modalSubheading('Tindakan ini akan mengurangi stok di gudang sesuai dengan jumlah yang disiapkan. Pastikan semua item telah disiapkan dengan benar.')
                ->modalButton('Ya, Selesaikan')
                ->visible(fn () => $this->record->status === 'preparing'),
                
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->color('danger')
                ->action(function () {
                    $this->record->status = 'cancelled';
                    $this->record->save();
                    
                    $this->notify('success', 'Request bahan berhasil dibatalkan');
                })
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['draft', 'requested', 'preparing'])),
        ];
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update($data);
        
        return $record;
    }
}