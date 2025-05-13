<?php

namespace App\Filament\Resources\StockIssueResource\Pages;

use App\Filament\Resources\StockIssueResource;
use App\Models\StockIssue;
use App\Models\WarehouseItem;
use Filament\Forms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ProcessStockIssue extends Page
{
    protected static string $resource = StockIssueResource::class;

    protected static string $view = 'filament.resources.stock-issue-resource.pages.process-stock-issue';

    public StockIssue $record;
    
    public $itemData = [];
    
    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        
        // Load data for each item
        foreach ($this->record->items as $item) {
            $warehouseItem = $item->warehouseItem;
            $this->itemData[$item->id] = [
                'warehouse_item_id' => $item->warehouse_item_id,
                'item_name' => $warehouseItem->name,
                'unit' => $warehouseItem->unit,
                'available_stock' => $warehouseItem->quantity,
                'requested_quantity' => $item->requested_quantity,
                'issued_quantity' => $item->issued_quantity ?: 0,
                'notes' => '',
            ];
        }
        
        if ($this->record->status === 'requested') {
            $this->record->status = 'preparing';
            $this->record->save();
        }
    }
    
    protected function resolveRecord($key): StockIssue
    {
        return StockIssue::findOrFail($key);
    }
    
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema($this->getFormSchema())
                ->model($this->record)
                ->statePath('itemData'),
        ];
    }
    
    protected function getFormSchema(): array
    {
        $schema = [];
        
        foreach ($this->record->items as $item) {
            $warehouseItem = $item->warehouseItem;
            
            $schema[] = Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('item_name')
                        ->label('Nama Item')
                        ->disabled()
                        ->extraAttributes(['class' => 'font-bold']),
                        
                    Forms\Components\TextInput::make('unit')
                        ->label('Satuan')
                        ->disabled(),
                        
                    Forms\Components\TextInput::make('available_stock')
                        ->label('Stok Tersedia')
                        ->disabled(),
                        
                    Forms\Components\TextInput::make('requested_quantity')
                        ->label('Jumlah Diminta')
                        ->disabled(),
                        
                    Forms\Components\TextInput::make('issued_quantity')
                        ->label('Jumlah Disiapkan')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(function (callable $get) {
                            return $get('available_stock');
                        })
                        ->extraAttributes(['class' => 'text-primary-600 font-bold']),
                        
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan')
                        ->placeholder('Tambahkan catatan jika perlu'),
                ])
                ->columns(3)
                ->statePath($item->id);
        }
        
        return $schema;
    }
    
    protected function getActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan')
                ->action('save'),
                
            Actions\Action::make('complete')
                ->label('Selesaikan & Kurangi Stok')
                ->color('success')
                ->action('complete')
                ->extraAttributes(['class' => 'filament-button-success']),
                
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(fn (): string => StockIssueResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
    
    public function save()
    {
        DB::beginTransaction();
        
        try {
            foreach ($this->itemData as $itemId => $data) {
                $stockIssueItem = $this->record->items()->find($itemId);
                if ($stockIssueItem) {
                    $stockIssueItem->issued_quantity = $data['issued_quantity'];
                    $stockIssueItem->save();
                }
            }
            
            DB::commit();
            $this->notify('success', 'Data penyiapan bahan berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notify('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    public function complete()
    {
        DB::beginTransaction();
        
        try {
            foreach ($this->itemData as $itemId => $data) {
                $stockIssueItem = $this->record->items()->find($itemId);
                if ($stockIssueItem) {
                    $stockIssueItem->issued_quantity = $data['issued_quantity'];
                    $stockIssueItem->save();
                    
                    // Kurangi stok
                    $warehouseItem = WarehouseItem::find($stockIssueItem->warehouse_item_id);
                    if ($warehouseItem) {
                        if ($stockIssueItem->issued_quantity > $warehouseItem->quantity) {
                            throw new \Exception("Stok {$warehouseItem->name} tidak mencukupi.");
                        }
                        
                        $warehouseItem->quantity -= $stockIssueItem->issued_quantity;
                        $warehouseItem->save();
                    }
                }
            }
            
            // Update status
            $this->record->status = 'completed';
            $this->record->save();
            
            DB::commit();
            $this->notify('success', 'Pengeluaran bahan berhasil diselesaikan dan stok telah dikurangi');
            
            // Redirect
            return redirect()->to(StockIssueResource::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();
            $this->notify('danger', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
