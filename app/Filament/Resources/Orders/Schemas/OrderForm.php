<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            // Kita atur kolom langsung di root schema (Cara paling rapi di Filament)
            ->columns(['default' => 1, 'lg' => 3]) 
            ->components([
                
                // --- KOTAK KIRI: INFORMASI PESANAN ---
                // 💡 PERBAIKAN: Menggunakan 'lg' => 2 agar hanya membelah di layar yang benar-benar lebar
                Section::make('Informasi Pesanan')
                    ->columnSpan(['default' => 1, 'lg' => 2])
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Nomor Pesanan')
                            ->required()
                            ->readOnly(),

                        Select::make('user_id')
                            ->label('Pelanggan')
                            ->relationship('user', 'name') 
                            ->required()
                            ->disabled(),

                        Grid::make(2)->schema([
                            TextInput::make('total_amount')
                                ->label('Total Belanja')
                                ->numeric()
                                ->prefix('Rp')
                                ->readOnly(),

                            TextInput::make('shipping_cost')
                                ->label('Ongkos Kirim')
                                ->numeric()
                                ->prefix('Rp')
                                ->readOnly(),
                        ]),

                        Placeholder::make('address_snapshot')
                            ->label('Alamat Pengiriman')
                            ->content(function ($record) {
                                if (!$record || !$record->address_snapshot) return '-';

                                $data = is_string($record->address_snapshot) ? json_decode($record->address_snapshot, true) : $record->address_snapshot;
                                
                                if (is_array($data)) {
                                    $nama = $data['name'] ?? '-';
                                    $hp = $data['phone'] ?? '-';
                                    $alamat = $data['address'] ?? '-';
                                    
                                    return new HtmlString("<div style='background-color: #f3f4f6; padding: 12px; border-radius: 8px; color: #374151; font-size: 14px;'><b>Penerima:</b> {$nama} <br><b>No. HP:</b> {$hp} <br><b>Detail:</b> {$alamat}</div>");
                                }
                                
                                return $record->address_snapshot;
                            })
                            ->columnSpanFull(),
                    ]),

                // --- KOTAK KANAN: UPDATE PENGIRIMAN ---
                Section::make('Update Pengiriman')
                    ->columnSpan(['default' => 1, 'lg' => 1])
                    ->schema([
                        Select::make('status')
                            ->label('Status Pesanan')
                            ->options([
                                'unpaid' => 'Belum Bayar',
                                'paid' => 'Sudah Bayar',
                                'processing' => 'Diproses',
                                'shipping' => 'Dikirim',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->default('unpaid')
                            // 💡 PERBAIKAN: Hapus native(false) agar dropdown menggunakan tampilan bawaan browser yang tidak akan pernah merusak teks panjang
                            ->required(),

                        TextInput::make('tracking_number')
                            ->label('Nomor Resi')
                            ->placeholder('Misal: JNE12345...'),
                    ]),

            ]);
    }
}