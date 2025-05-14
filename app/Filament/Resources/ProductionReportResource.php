<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionReportResource\Pages;
use App\Filament\Resources\ProductionReportResource\RelationManagers;
use App\Models\DailyMenuItem;
use App\Models\ProductionReport;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductionReportResource extends Resource
{
    protected static ?string $model = ProductionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('date')
                ->label('Tanggal')
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    // Ambil daily menu item dari tanggal terpilih
                    $items = DailyMenuItem::whereDate('date', $state)->get(); 

                    $repeaterItems = $items->map(fn($item) => [
                        'daily_menu_item_id' => $item->id,
                        'target_qty' => $item->qty,
                        'actual_qty' => null,
                        'status' => null,
                    ])->toArray();

                    $set('items', $repeaterItems);
                }),

            Repeater::make('items')
                ->label('Item Produksi')
                ->relationship('items')
                ->schema([
                    Select::make('daily_menu_item_id')
                        ->label('Menu')
                        ->disabled()
                        ->options(DailyMenuItem::all()->pluck('menu_name', 'id')),

                    TextInput::make('target_qty')
                        ->label('Target')
                        ->numeric()
                        ->disabled(),

                    TextInput::make('actual_qty')
                        ->label('Realisasi')
                        ->numeric()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $target = $get('target_qty');
                            $status = match (true) {
                                $state == $target => 'tercukupi',
                                $state < $target => 'kurang',
                                $state > $target => 'lebih',
                            };
                            $set('status', $status);
                        }),

                    TextInput::make('status')
                        ->label('Status')
                        ->disabled(),
                ])
                ->columns(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListProductionReports::route('/'),
            'create' => Pages\CreateProductionReport::route('/create'),
            'edit' => Pages\EditProductionReport::route('/{record}/edit'),
        ];
    }
}
