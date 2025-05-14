<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionReportResource\Pages;
use App\Models\ProductionReport;
use App\Models\DailyMenu;
use Filament\Forms;
//use Filament\Resources\Form;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Notifications\Notification; // Pastikan import ini

class ProductionReportResource extends Resource
{
    protected static ?string $model = ProductionReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard';

    protected static ?string $navigationLabel = 'Laporan Produksi (QQ)';

    protected static ?string $navigationGroup = 'Produksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('daily_menu_items', []);

                                if (!$state) return;

                                // Ambil daily menu untuk tanggal yang dipilih
                                $dailyMenu = DailyMenu::whereDate('date', $state)->first();

                                if (!$dailyMenu) {
                                    // Tidak perlu notifikasi di sini
                                    return;
                                }

                                // Ambil semua item menu
                                $menuItems = $dailyMenu->items;

                                if ($menuItems->isEmpty()) {
                                    // Tidak perlu notifikasi di sini
                                    return;
                                }

                                // Siapkan data untuk repeater
                                $itemsData = [];
                                foreach ($menuItems as $item) {
                                    $itemsData[] = [
                                        'id' => $item->id,
                                        'menu_name' => $item->menu_name,
                                        'qty' => $item->qty,
                                    ];
                                }

                                $set('daily_menu_items', $itemsData);
                                // Tidak perlu notifikasi di sini
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpan('full'),

                        Forms\Components\Section::make('Menu Produksi')
                            ->schema([
                                Forms\Components\Hidden::make('daily_menu_items'),

                                // Tampilkan menu dalam tabel statis
                                Forms\Components\Placeholder::make('menu_items_preview')
                                    ->label('Preview Menu Harian')
                                    ->content(function (callable $get) {
                                        $date = $get('date');
                                        if (!$date) return 'Pilih tanggal untuk melihat menu.';

                                        $dailyMenuItems = $get('daily_menu_items');

                                        // Jika daily_menu_items tersedia dari state
                                        if (!empty($dailyMenuItems) && is_array($dailyMenuItems)) {
                                            $output = '<div class="overflow-x-auto">
                                                <table class="w-full text-sm border-collapse">
                                                    <thead>
                                                        <tr>
                                                            <th class="border px-4 py-2 text-left">Nama Menu</th>
                                                            <th class="border px-4 py-2 text-left">Target Jumlah</th>
                                                            <th class="border px-4 py-2 text-left">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>';

                                            foreach ($dailyMenuItems as $item) {
                                                $output .= '<tr>
                                                    <td class="border px-4 py-2">' . $item['menu_name'] . '</td>
                                                    <td class="border px-4 py-2">' . $item['qty'] . '</td>
                                                    <td class="border px-4 py-2">Belum Diproduksi</td>
                                                </tr>';
                                            }

                                            $output .= '</tbody></table></div>';
                                            $output .= '<p class="mt-4 text-gray-600">Simpan laporan ini untuk mengisi jumlah produksi aktual.</p>';

                                            return new \Illuminate\Support\HtmlString($output);
                                        }

                                        // Jika tidak ada dari state, coba ambil langsung dari database
                                        try {
                                            $dailyMenu = DailyMenu::whereDate('date', $date)->first();
                                            if (!$dailyMenu) return 'Tidak ada menu yang tersedia untuk tanggal ini.';

                                            $items = $dailyMenu->items;
                                            if ($items->isEmpty()) return 'Tidak ada item menu yang tersedia.';

                                            $output = '<div class="overflow-x-auto">
                                                <table class="w-full text-sm border-collapse">
                                                    <thead>
                                                        <tr>
                                                            <th class="border px-4 py-2 text-left">Nama Menu</th>
                                                            <th class="border px-4 py-2 text-left">Target Jumlah</th>
                                                            <th class="border px-4 py-2 text-left">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>';

                                            foreach ($items as $item) {
                                                $output .= '<tr>
                                                    <td class="border px-4 py-2">' . $item->menu_name . '</td>
                                                    <td class="border px-4 py-2">' . $item->qty . '</td>
                                                    <td class="border px-4 py-2">Belum Diproduksi</td>
                                                </tr>';
                                            }

                                            $output .= '</tbody></table></div>';
                                            $output .= '<p class="mt-4 text-gray-600">Simpan laporan ini untuk mengisi jumlah produksi aktual.</p>';

                                            return new \Illuminate\Support\HtmlString($output);
                                        } catch (\Exception $e) {
                                            return 'Error: ' . $e->getMessage();
                                        }
                                    })
                                    ->columnSpan('full'),

                                // Tambahkan Debug Info jika diperlukan
                                Forms\Components\Placeholder::make('debug_info')
                                    ->label('Debug Info')
                                    ->content(function ($get) {
                                        $date = $get('date');
                                        if (!$date) return 'Tanggal belum dipilih';

                                        $output = "Tanggal dipilih: {$date}<br>";

                                        try {
                                            $dailyMenu = DailyMenu::whereDate('date', $date)->first();

                                            if (!$dailyMenu) {
                                                return $output . "Tidak ada daily menu untuk tanggal tersebut.";
                                            }

                                            $output .= "Daily Menu ID: {$dailyMenu->id}<br>";
                                            $output .= "Daily Menu Date: {$dailyMenu->date}<br>";

                                            $items = $dailyMenu->items;
                                            $output .= "Jumlah items: " . $items->count() . "<br>";

                                            if ($items->count() > 0) {
                                                $output .= "<ul>";
                                                foreach ($items as $item) {
                                                    $output .= "<li>ID: {$item->id}, Menu: {$item->menu_name}, Qty: {$item->qty}</li>";
                                                }
                                                $output .= "</ul>";
                                            }

                                            return new \Illuminate\Support\HtmlString($output);
                                        } catch (\Exception $e) {
                                            return "Error: " . $e->getMessage() . "<br>" . $e->getTraceAsString();
                                        }
                                    })
                                    ->columnSpan('full')
                                    ->visible(fn () => config('app.debug', false)), // Hanya tampilkan di mode debug
                            ])
                            ->columnSpan('full')
                            ->visible(fn ($get) => $get('date') !== null),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Jumlah Menu')
                    ->counts('items'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Filter bisa ditambahkan nanti
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProductionReports::route('/'),
            'create' => Pages\CreateProductionReport::route('/create'),
//            'view' => Pages\ViewProductionReport::route('/{record}'),
            'edit' => Pages\EditProductionReport::route('/{record}/edit'),
        ];
    }
}
