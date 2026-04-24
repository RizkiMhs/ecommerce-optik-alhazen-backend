<?php

namespace App\Filament\Resources\Orders\Tables;

// use Filament\Actions\BulkActionGroup;
// use Filament\Actions\DeleteBulkAction;
// use Filament\Actions\EditAction;
// use Filament\Actions\ViewAction;
// use Filament\Tables\Columns\TextColumn;
// use Filament\Tables\Table;
// use Illuminate\Support\Str;
// use App\Models\Order;
// use Filament\Actions\Action;
// use Filament\Notifications\Notification;
// use Illuminate\Database\Eloquent\Builder; // ✅ WAJIB (ini yang kurang)

// class OrdersTable
// {
//     public static function configure(Table $table): Table
//     {
//         return $table
//             // FILTER: hanya status paid
//             ->modifyQueryUsing(function (Builder $query) {
//                 $query->where('status', 'paid');
//             })

//             ->columns([
//                 TextColumn::make('order_number')
//                     ->label('No. Pesanan')
//                     ->searchable()
//                     ->weight('bold'),

//                 TextColumn::make('user.name')
//                     ->label('Nama Pelanggan')
//                     ->searchable(),

//                 TextColumn::make('total_amount')
//                     ->label('Total Dibayar')
//                     ->money('IDR', true) // ✅ lebih rapi (pakai separator)
//                     ->sortable(),

//                 TextColumn::make('status')
//                     ->label('Status')
//                     ->badge()
//                     ->color(fn ($state) => match ($state) {
//                         'paid' => 'success',
//                         'shipping' => 'warning',
//                         default => 'gray',
//                     }),

//                 TextColumn::make('tracking_number')
//                     ->label('No. Resi')
//                     ->searchable()
//                     ->placeholder('Belum ada resi'),

//                 TextColumn::make('created_at')
//                     ->label('Tanggal Pesan')
//                     ->dateTime('d M Y, H:i')
//                     ->sortable(),
//             ])

//             ->filters([
//                 //
//             ])

//             ->recordActions([
//                 ViewAction::make()
//                     ->label('Lihat Resep & Rakit')
//                     ->color('info'),

//                 Action::make('cetak_label')
//                     ->label('Cetak Label & Panggil Kurir')
//                     ->icon('heroicon-o-printer')
//                     ->color('warning')
//                     ->requiresConfirmation()
//                     ->modalHeading('Cetak Resi & Panggil Kurir')
//                     ->modalDescription('Apakah kacamata sudah selesai dirakit dan paket siap pickup?')
//                     ->modalSubmitActionLabel('Ya, Panggil Kurir')

//                     ->action(function (Order $record) {

//                         // simulasi delay
//                         sleep(2);

//                         // generate resi
//                         $resiPalsu = 'BITE-' . strtoupper(Str::random(6));

//                         // update data
//                         $record->update([
//                             'tracking_number' => $resiPalsu,
//                             'status' => 'shipping',
//                         ]);

//                         Notification::make()
//                             ->title('Berhasil Memanggil Kurir!')
//                             ->body("Resi ($resiPalsu) berhasil dibuat.")
//                             ->success()
//                             ->send();
//                     })

//                     // ✅ tambahan: disable kalau sudah ada resi
//                     ->disabled(fn (Order $record) => $record->tracking_number !== null),
//             ])

//             ->toolbarActions([
//                 BulkActionGroup::make([
//                     DeleteBulkAction::make(),
//                 ]),
//             ]);
//     }
// }



namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
// use Filament\Filters\SelectFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // 💡 BUG FIXED: Hapus modifyQueryUsing agar semua pesanan tampil
            ->columns([
                TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Nama Pelanggan')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Total Dibayar')
                    ->money('IDR', locale: 'id') // Format Rupiah Indonesia
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    // 💡 UPDATE: Warna disesuaikan dengan 5 Status kita
                    ->color(fn ($state) => match ($state) {
                        'unpaid' => 'danger',       // Merah
                        'paid' => 'warning',        // Kuning/Oranye
                        'processing' => 'info',     // Biru
                        'shipping' => 'primary',    // Ungu/Biru Tua
                        'completed' => 'success',   // Hijau
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)), // Huruf Kapital

                TextColumn::make('tracking_number')
                    ->label('No. Resi')
                    ->searchable()
                    ->placeholder('Belum ada resi'),

                TextColumn::make('created_at')
                    ->label('Tanggal Pesan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])

            ->filters([
                // 💡 FITUR BARU: Filter Dropdown untuk menyaring status
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'unpaid' => 'Unpaid (Belum Bayar)',
                        'paid' => 'Paid (Sudah Bayar)',
                        'processing' => 'Processing (Diproses)',
                        'shipping' => 'Shipping (Dikirim)',
                        'completed' => 'Completed (Selesai)',
                    ]),
            ])

            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Resep')
                    ->color('info'),

                // 💡 BUG FIXED: Tambahkan EditAction agar Form yang kita buat tadi bisa dibuka!
                EditAction::make()
                    ->label('Update Resi/Status')
                    ->color('primary'),

                Action::make('cetak_label')
                    ->label('Panggil Kurir')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Cetak Resi & Panggil Kurir')
                    ->modalDescription('Kacamata sudah selesai dirakit?')
                    ->modalSubmitActionLabel('Ya, Panggil Kurir')
                    ->action(function (Order $record) {
                        sleep(1); // Simulasi API Kurir
                        $resiPalsu = 'BITE-' . strtoupper(Str::random(6));

                        $record->update([
                            'tracking_number' => $resiPalsu,
                            'status' => 'shipping',
                        ]);

                        Notification::make()
                            ->title('Berhasil Memanggil Kurir!')
                            ->body("Resi ($resiPalsu) otomatis tersimpan.")
                            ->success()
                            ->send();
                    })
                    // Hanya bisa dipanggil jika statusnya 'processing' (sedang dirakit)
                    ->visible(fn (Order $record) => $record->status === 'processing'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}