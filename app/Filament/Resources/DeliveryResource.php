<?php
namespace App\Filament\Resources;
use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('delivery_number')  
                        ->label('No. Pengiriman')  
                        ->default(function() {  
                            $date = Carbon::now();  
                            $randomStr = Str::random(3);  
                            return 'SPPG-SLW/' . $date->format('d/m/Y') . '/' . strtoupper($randomStr);  
                        })  
                        ->disabled()  
                        ->dehydrated()  
                        ->required(), 
                Forms\Components\DatePicker::make('delivery_date')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('school_id')
                    ->relationship('school', 'name')
                    ->required(),
                Forms\Components\TextInput::make('qty_delivery'),
                Forms\Components\Select::make('status')
                ->label('Status Pengiriman')
                ->options([
                    'dalam_perjalanan' => 'Dalam Perjalanan',
                    'terkirim' => 'Terkirim',
                ])
                ->default('dalam_perjalanan')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('delivery_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('school.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_delivery'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tambahkan tombol action untuk kirim WhatsApp
                Tables\Actions\Action::make('kirimWhatsApp')
                    ->label('Kirim WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->action(function (Delivery $record) {
                        // Format tanggal untuk pesan
                        $formattedDate = Carbon::parse($record->delivery_date)->format('d/m/Y');
                        
                        // Format pesan WhatsApp
                        $message = "Informasi Pengiriman:\n"
                            . "Tanggal: {$formattedDate}\n"
                            . "No. Order: {$record->delivery_number}\n"
                            . "Jumlah: {$record->qty_delivery}\n"
                            . "Nama Sekolah: {$record->school->name}\n"
                            . "Status: {$record->status}";
                            
                        // Encode pesan untuk URL WhatsApp
                        $encodedMessage = urlencode($message);
                        
                        // Ambil nomor WhatsApp sekolah
                        $phoneNumber = $record->school->phone ?? '';
                        
                        // Format nomor telepon ke format internasional
                        // Jika nomor dimulai dengan '0', ganti dengan kode negara Indonesia (62)
                        if (strlen($phoneNumber) > 0) {
                            if (substr($phoneNumber, 0, 1) === '0') {
                                $phoneNumber = '62' . substr($phoneNumber, 1);
                            } 
                            // Jika nomor tidak dimulai dengan '+' atau '62', tambahkan '62'
                            elseif (substr($phoneNumber, 0, 1) !== '+' && substr($phoneNumber, 0, 2) !== '62') {
                                $phoneNumber = '62' . $phoneNumber;
                            }
                            
                            // Hapus karakter '+' jika ada
                            $phoneNumber = str_replace('+', '', $phoneNumber);
                        }
                        
                        // Redirect ke WhatsApp dengan pesan yang sudah disiapkan
                        return redirect()->away("https://wa.me/{$phoneNumber}?text={$encodedMessage}");
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeliveries::route('/'),
        ];
    }
}