<?php

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
use Illuminate\Support\Facades\Http; // 💡 TAMBAHKAN INI DI ATAS

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
                    ->color(fn($state) => match ($state) {
                        'unpaid' => 'danger',       // Merah
                        'paid' => 'warning',        // Kuning/Oranye
                        'processing' => 'info',     // Biru
                        'shipping' => 'primary',    // Ungu/Biru Tua
                        'completed' => 'success',   // Hijau
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => strtoupper($state)), // Huruf Kapital

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

                // 

                Action::make('cetak_label')
                    ->label('Panggil Kurir (Biteship)')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Panggil Kurir Ekspedisi')
                    ->modalDescription('Pastikan kacamata sudah dikemas dan siap dijemput oleh kurir.')
                    ->modalSubmitActionLabel('Ya, Panggil Kurir')
                    ->action(function (Order $record) {

                        // 1. Ambil data alamat
                        $alamat = is_string($record->address_snapshot) ? json_decode($record->address_snapshot, true) : $record->address_snapshot;

                        // 💡 2. LOGIKA CERDAS: Ekstrak Kode Pos Otomatis menggunakan Regex
                        $kodeposTujuan = 12345; // Angka aman/fallback
                        $teksAlamat = $alamat['address'] ?? '';

                        // Skenario A: Cari angka 5 digit berturut-turut
                        if (preg_match('/\b\d{5}\b/', $teksAlamat, $matches)) {
                            $kodeposTujuan = (int) $matches[0];
                        } 
                        // Skenario B: Cari angka setelah tulisan "Kode Pos:"
                        elseif (preg_match('/Kode Pos:\s*(\d+)/i', $teksAlamat, $matches)) {
                            $kodeposTujuan = (int) $matches[1];
                            if (strlen((string)$kodeposTujuan) < 5) {
                                $kodeposTujuan = 12345; // Fallback jika user iseng ketik kurang dari 5 digit
                            }
                        }

                        // 3. Siapkan Payload
                        $payload = [
                            // --- DATA TOKO OPTIK ALHAZEN ---
                            "origin_contact_name" => "Optik Alhazen",
                            "origin_contact_phone" => "082352306497",
                            "origin_address" => "jalan medan-banda aceh kuta blang bireuen, Kuta Blang, Bireuen, Nanggroe Aceh Darussalam (NAD)",
                            "origin_postal_code" => 24358, 

                            // --- DATA PELANGGAN ---
                            "destination_contact_name" => $alamat['name'] ?? 'Pelanggan',
                            "destination_contact_phone" => $alamat['phone'] ?? '081200000000',
                            "destination_address" => $teksAlamat,
                            "destination_postal_code" => $kodeposTujuan, // 💡 Sudah Otomatis!
                            
                            // --- LAYANAN KURIR ---
                            // "courier_company" => $record->courier ?? "jnt", // Ambil dari DB, default J&T
                            // "courier_type" => "ez", // Layanan standar J&T
                            // "delivery_type" => "now", // Jemput secepatnya

                            // --- LAYANAN KURIR ---
                            "courier_company" => "jne", // 💡 Ubah ke jne
                            "courier_type" => "reg",    // 💡 Ubah ke reg
                            "delivery_type" => "now",
                            
                            // --- DATA BARANG ---
                            "items" => [
                                [
                                    "name" => "Pesanan Kacamata " . $record->order_number,
                                    "value" => $record->total_amount,
                                    "weight" => 500, // Berat 500 gram
                                    "quantity" => 1
                                ]
                            ]
                        ];

                        // 4. Tembak API Biteship
                        $response = \Illuminate\Support\Facades\Http::withHeaders([
                            'Authorization' => env('BITESHIP_API_KEY'),
                            'Content-Type'  => 'application/json'
                        ])->post('https://api.biteship.com/v1/orders', $payload);

                        // 5. Proses Hasilnya
                        if ($response->successful()) {
                            $data = $response->json();
                            
                            $record->update([
                                'tracking_number' => $data['courier']['waybill_id'],
                                'biteship_order_id' => $data['id'],
                                'status' => 'shipping',
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Kurir Berhasil Dipanggil!')
                                ->body("Resi otomatis: " . $data['courier']['waybill_id'])
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Gagal Memanggil Kurir')
                                ->body($response->json()['error'] ?? 'Terjadi kesalahan pada sistem ekspedisi.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Order $record) => $record->status === 'processing'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
