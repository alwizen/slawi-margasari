<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NutritionPlanResource\Pages;
use App\Models\NutritionPlan;
use App\Models\Nutrient;
use App\Models\DailyMenu; // Tambahkan model DailyMenu
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class NutritionPlanResource extends Resource
{
    protected static ?string $model = NutritionPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Gizi & Menu Harian';

    protected static ?string $navigationLabel = 'Nutrisi Harian';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Pilih Nutrition Plan')
                        ->schema([
                            Forms\Components\DatePicker::make('date')
                                ->label('Tanggal')
                                ->required(),

                            Forms\Components\Repeater::make('items')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('nutrient_id')
                                        ->label('Nutrisi')
                                        ->relationship('nutrient', 'name')
                                        ->required()
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                            $nutrient = Nutrient::find($state);
                                            if ($nutrient) {
                                                $set('unit', $nutrient->unit);
                                            }
                                        })
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->required()
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('unit')
                                                ->required()
                                                ->maxLength(255),
                                        ]),

                                    Forms\Components\TextInput::make('amount')
                                        ->label('Jumlah')
                                        ->numeric()
                                        ->suffix(fn(Get $get) => $get('unit') ?? '')
                                        ->required(),
                                        
                                    Hidden::make('unit'),
                                ])
                                ->createItemButtonLabel('Tambah Nutrisi')
                                ->minItems(1)
                                ->columns(2)
                        ])
                        ->afterValidation(function (array $state, Set $set) {
                            // Save the nutrition plan to make it available for step 2
                            if (!isset($state['id']) || !$state['id']) {
                                $nutritionPlan = NutritionPlan::create([
                                    'date' => $state['date'],
                                ]);
                                
                                // Save nutrition plan ID to form state
                                $set('id', $nutritionPlan->id);
                                
                                // Create items for this nutrition plan
                                $items = collect($state['items'] ?? [])->map(function ($item) use ($nutritionPlan) {
                                    return [
                                        'nutrition_plan_id' => $nutritionPlan->id,
                                        'nutrient_id' => $item['nutrient_id'],
                                        'amount' => $item['amount'],
                                    ];
                                })->toArray();
                                
                                $nutritionPlan->items()->createMany($items);
                                
                                // Persiapkan data untuk step 2
                                $menuItems = $nutritionPlan->items->map(function ($item) {
                                    return [
                                        'nutrient_name' => $item->nutrient->name,
                                        'amount' => $item->amount,
                                        'unit' => $item->nutrient->unit,
                                        'nutrient_id' => $item->nutrient_id,
                                        'item_id' => $item->id,
                                        'menu_name' => '',
                                        'qty' => '',
                                    ];
                                })->toArray();
                                
                                $set('menu_items', $menuItems);
                            }
                        }),

                    Wizard\Step::make('Input Menu Harian')
                        ->schema([
                            Hidden::make('id'),
                            
                            Forms\Components\Select::make('selected_nutrition_plan')
                                ->label('Pilih Tanggal Nutrition Plan')
                                ->options(function () {
                                    return NutritionPlan::pluck('date', 'id');
                                })
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if (!$state) {
                                        $set('menu_items', []);
                                        return;
                                    }
                                    
                                    $nutritionPlan = NutritionPlan::with('items.nutrient')->find($state);
                                    if (!$nutritionPlan) {
                                        $set('menu_items', []);
                                        return;
                                    }
                                    
                                    // Set nilai date dari nutritionPlan yang dipilih
                                    $set('date', $nutritionPlan->date);
                                    
                                    // Persiapkan data menu items dari nutrition plan
                                    $menuItems = $nutritionPlan->items->map(function ($item) {
                                        return [
                                            'nutrient_name' => $item->nutrient->name ?? '',
                                            'amount' => $item->amount,
                                            'unit' => $item->nutrient->unit ?? '',
                                            'nutrient_id' => $item->nutrient_id,
                                            'item_id' => $item->id,
                                            'menu_name' => $item->menu_name ?? '',
                                            'qty' => $item->qty ?? '',
                                        ];
                                    })->toArray();
                                    
                                    $set('menu_items', $menuItems);
                                }),
                            
                            Forms\Components\DatePicker::make('date')
                                ->label('Tanggal')
                                ->disabled()
                                ->dehydrated(true),
                                
                            Repeater::make('menu_items')
                                ->label('Menu Harian')
                                ->schema([
                                    TextInput::make('nutrient_name')
                                        ->label('Nutrisi')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('amount')
                                        ->label('Kebutuhan')
                                        ->suffix(fn(Get $get) => $get('unit') ?? '')
                                        ->disabled()
                                        ->dehydrated(false),

                                    TextInput::make('menu_name')
                                        ->label('Menu yang Disajikan')
                                        ->required(),

                                        TextInput::make('qty')
                                        ->label('Target Produksi')
                                        ->required(),

                                    Hidden::make('nutrient_id'),
                                    Hidden::make('unit'),
                                    Hidden::make('item_id'),
                                ])
                                ->columns(4),
                        ]),
                ])
                ->columnSpan('full')
                ->persistStepInQueryString()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Nutrisi')
                    ->counts('items'),
                    
                // Tables\Columns\TextColumn::make('items.menu_name')
                //     ->label('Menu')
                //     ->listWithLineBreaks()
                //     ->limitList(3)
                //     ->expandableLimitedList(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('View Information')
                    ->infolist([
                        Section::make('Informasi Umum')
                            ->schema([
                                TextEntry::make('date')->label('Tanggal'),
                            ]),

                        Section::make('Daftar Nutrisi')
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->label('Nutrisi')
                                    ->schema([
                                        TextEntry::make('nutrient.name')->label('Nama Nutrisi'),
                                        TextEntry::make('amount')->label('Jumlah')
                                            ->numeric()
                                            ->suffix(fn($record) => optional($record->nutrient)->unit ?? ''),
                                        TextEntry::make('menu_name')->label('Menu yang Disajikan'),
                                        TextEntry::make('qty')->label('Target Produksi'),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                    ->slideOver()
                    ->label('Lihat Detail'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListNutritionPlans::route('/'),
            'create' => Pages\CreateNutritionPlan::route('/create'),
            'edit' => Pages\EditNutritionPlan::route('/{record}/edit'),
        ];
    }
}