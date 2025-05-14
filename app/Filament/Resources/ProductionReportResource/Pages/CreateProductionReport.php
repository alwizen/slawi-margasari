<?php

namespace App\Filament\Resources\ProductionReportResource\Pages;

use App\Filament\Resources\ProductionReportResource;
use App\Models\DailyMenu;
use App\Models\DailyMenuItem;
use App\Models\ProductionReportItem;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification; // Import ini jika perlu menggunakan notifikasi

class CreateProductionReport extends CreateRecord
{
    protected static string $resource = ProductionReportResource::class;

    // Override metode mutateFormDataBeforeCreate
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Hapus data daily_menu_items dari form data karena tidak ada di model
        if (isset($data['daily_menu_items'])) {
            unset($data['daily_menu_items']);
        }

        return $data;
    }

    // Override metode afterCreate untuk menambahkan item produksi
    protected function afterCreate(): void
    {
        $dailyMenuItems = $this->data['daily_menu_items'] ?? [];

        if (!empty($dailyMenuItems) && is_array($dailyMenuItems)) {
            foreach ($dailyMenuItems as $item) {
                // Pastikan item memiliki ID
                if (isset($item['id'])) {
                    // Ambil DailyMenuItem untuk mendapatkan data lengkap
                    $menuItem = DailyMenuItem::find($item['id']);

                    if ($menuItem) {
                        ProductionReportItem::create([
                            'production_report_id' => $this->record->id,
                            'daily_menu_item_id' => $menuItem->id,
                            'target_qty' => $menuItem->qty,
                            'actual_qty' => 0, // Default 0, perlu diisi oleh QQ
                            'status' => 'kurang', // Default status
                        ]);
                    }
                }
            }
        } else {
            // Jika tidak ada data daily_menu_items, gunakan cara lama
            $dailyMenu = DailyMenu::whereDate('date', $this->record->date)->first();

            if ($dailyMenu) {
                foreach ($dailyMenu->items as $menuItem) {
                    ProductionReportItem::create([
                        'production_report_id' => $this->record->id,
                        'daily_menu_item_id' => $menuItem->id,
                        'target_qty' => $menuItem->qty,
                        'actual_qty' => 0,
                        'status' => 'kurang',
                    ]);
                }
            }
        }

        // Jika ingin menampilkan notifikasi, gunakan cara ini
        Notification::make()
            ->title('Laporan produksi berhasil dibuat')
            ->success()
            ->send();

        // Redirect ke halaman edit untuk mengisi jumlah aktual
        $this->redirect(ProductionReportResource::getUrl('edit', ['record' => $this->record]));
    }
}
