<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyMenuResource\Pages;
use App\Filament\Resources\DailyMenuResource\RelationManagers;
use App\Models\DailyMenu;
use App\Models\NutritionPlan;
use Filament\Actions\Modal\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
// use Filament\Forms\Components\Section;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DailyMenuResource extends Resource
{
    protected static ?string $model = DailyMenu::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Menu Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Select::make('nutrition_plan_id')
                            ->label('Tanggal (Nutrition Plan)')
                            ->options(NutritionPlan::query()->pluck('date', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $plan = NutritionPlan::with('items.nutrient')->find($state);
                                
                                // Set date dari nutrition plan ke field date
                                if ($plan) {
                                    $set('date', $plan->date);
                                }

                                $items = collect($plan?->items)->map(function ($item) {
                                    return [
                                        'nutrient_id' => $item->nutrient_id,
                                        'menu_name' => null,
                                        'nutrient_name' => $item->nutrient->name ?? '',
                                        'unit' => $item->nutrient->unit ?? '',
                                        'amount' => $item->amount,
                                    ];
                                })->toArray();

                                $set('items', $items);
                            }),
                            
                        // Tambahkan field date yang diisi otomatis
                        Hidden::make('date')
                            ->required(),
                    ]),

                Card::make()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->label('Menu Harian')
                            ->schema([
                                TextInput::make('nutrient_name')
                                    ->label('Nutrisi')
                                    ->disabled(),

                                TextInput::make('amount')
                                    ->label('Kebutuhan')
                                    ->suffix(fn($state, $record) => $record['unit'] ?? '')
                                    ->disabled(),

                                TextInput::make('menu_name')
                                    ->label('Menu yang Disajikan')
                                    ->required(),
                                TextInput::make('qty')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required(),

                                Hidden::make('nutrient_id'),
                            ])
                            ->default([])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nutritionPlan.date')
                    ->label('Tanggal Nutrition Plan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Nutrisi')
                    ->counts('items'),
                TextColumn::make('qty')
                    ->label('Target Masak')
                    // ->counts('items.qty')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('View Detail')
                    ->infolist([
                        Section::make('Informasi Umum')
                            ->schema([
                                TextEntry::make('date')->label('Tanggal'),
                                TextEntry::make('nutritionPlan.date')->label('Tanggal Nutrition Plan'),
                            ]),
                        Section::make('Menu Harian')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->schema([
                                        TextEntry::make('nutrient.name')->label('Nutrisi'),
                                        TextEntry::make('menu_name')->label('Menu'),
                                        TextEntry::make('qty')->label('Jumlah'),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->slideOver()
                    ->label('Lihat Detail'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->paginated();
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
            'index' => Pages\ListDailyMenus::route('/'),
            'create' => Pages\CreateDailyMenu::route('/create'),
            'edit' => Pages\EditDailyMenu::route('/{record}/edit'),
        ];
    }
}