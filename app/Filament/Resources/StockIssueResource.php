<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockIssueResource\Pages;
use App\Filament\Resources\StockIssueResource\RelationManagers;
use App\Models\StockIssue;
use App\Models\WarehouseItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockIssueResource extends Resource
{
    protected static ?string $model = StockIssue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()->schema([
                // Forms\Components\Select::make('purchase_order_id')
                //     ->label('Referensi PO')
                //     ->relationship('purchaseOrder', 'id')
                //     ->searchable()
                //     ->preload()
                //     ->required()
                //     ->disabled(fn ($record) => $record && $record->status !== 'draft'),

                Forms\Components\DatePicker::make('issue_date')
                    ->label('Tanggal Pengeluaran')
                    ->required()
                    ->disabled(fn ($record) => $record && $record->status !== 'draft'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'requested' => 'Diminta',
                        'preparing' => 'Sedang Disiapkan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->required()
                    ->default('draft'),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->nullable(),

                Forms\Components\Repeater::make('items')
                    ->label('Item Dikeluarkan')
                    ->relationship()
                    ->schema([
                        Forms\Components\Select::make('warehouse_item_id')
                            ->label('Item Gudang')
                            ->options(WarehouseItem::all()->pluck('name', 'id'))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $warehouseItem = WarehouseItem::find($state);
                                    if ($warehouseItem) {
                                        $set('available_stock', $warehouseItem->quantity);
                                    }
                                }
                            })
                            ->disabled(fn ($record, $livewire) => 
                                $livewire instanceof Pages\EditStockIssue && 
                                $livewire->record->status !== 'draft'),

                        Forms\Components\TextInput::make('available_stock')
                            ->label('Stok Tersedia')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('requested_quantity')
                            ->label('Jumlah Diminta')
                            ->numeric()
                            ->required()
                            ->disabled(fn ($record, $livewire) => 
                                $livewire instanceof Pages\EditStockIssue && 
                                $livewire->record->status !== 'draft'),

                        Forms\Components\TextInput::make('issued_quantity')
                            ->label('Jumlah Disiapkan')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->disabled(fn ($record, $livewire) => 
                                $livewire instanceof Pages\EditStockIssue && 
                                !in_array($livewire->record->status, ['requested', 'preparing'])),
                    ])
                    ->columns(4)
                    ->default([])
                    ->disabled(fn ($record) => $record && $record->status === 'completed'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('id')
                //     ->label('ID')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Tanggal Pengeluaran')
                    ->date()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('purchaseOrder.id')
                //     ->label('Referensi PO')
                //     ->searchable()
                //     ->sortable(),

                    BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'requested',
                        'warning' => 'preparing',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'draft' => 'Draft',
                            'requested' => 'Diminta',
                            'preparing' => 'Sedang Disiapkan',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            default => ucfirst($state),
                        };
                    }),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'requested' => 'Diminta',
                        'preparing' => 'Sedang Disiapkan',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),

                Tables\Filters\Filter::make('issue_date')
                    ->form([
                        Forms\Components\DatePicker::make('issue_date_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('issue_date_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['issue_date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '>=', $date),
                            )
                            ->when(
                                $data['issue_date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issue_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('prepare')
                    ->label('Siapkan')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->action(function (StockIssue $record) {
                        $record->status = 'preparing';
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (StockIssue $record) => $record->status === 'requested'),

                Tables\Actions\Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (StockIssue $record) {
                        // Proses pengurangan stok
                        foreach ($record->items as $item) {
                            $warehouseItem = $item->warehouseItem;
                            $warehouseItem->quantity -= $item->issued_quantity;
                            $warehouseItem->save();
                        }
                        
                        $record->status = 'completed';
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (StockIssue $record) => $record->status === 'preparing')
                    ->modalHeading('Konfirmasi Penyelesaian')
                    ->modalSubheading('Tindakan ini akan mengurangi stok di gudang sesuai dengan jumlah yang disiapkan. Pastikan semua item telah disiapkan dengan benar.')
                    ->modalButton('Ya, Selesaikan'),

                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (StockIssue $record) {
                        $record->status = 'cancelled';
                        $record->save();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (StockIssue $record) => in_array($record->status, ['draft', 'requested', 'preparing'])),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make()
                //     ->visible(fn () => auth()->user()->can('delete', StockIssue::class)),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockIssues::route('/'),
            'create' => Pages\CreateStockIssue::route('/create'),
            'edit' => Pages\EditStockIssue::route('/{record}/edit'),
        ];
    }
}
