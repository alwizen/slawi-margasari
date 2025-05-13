<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyMenuResource\Pages;
use App\Filament\Resources\DailyMenuResource\RelationManagers;
use App\Models\DailyMenu;
use App\Models\NutritionPlan;
use Filament\Actions\Modal\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Card;
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
                                ->suffix(fn ($state, $record) => $record['unit'] ?? '')
                                ->disabled(),

                            TextInput::make('menu_name')
                                ->label('Menu yang Disajikan')
                                ->required(),

                            Hidden::make('nutrient_id'),
                        ])
                        ->default([])
                        ->columns(3),
                ]),
        ]);
}
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nutritionPlan.date')
                    ->label('Tanggal')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('items_count')
                    ->label('Jumlah Nutrisi')
                    ->counts('items'),
            ])
            ->actions([
                Tables\Actions\Action::make('View Detail')
                    ->infolist([
                        Section::make('Informasi Umum')
                            ->schema([
                                TextEntry::make('nutritionPlan.date')->label('Tanggal'),
                            ]),
                        Section::make('Menu Harian')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->schema([
                                        TextEntry::make('nutrient.name')->label('Nutrisi'),
                                        TextEntry::make('menu_name')->label('Menu'),
                                    ])
                                    ->columns(2),
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
