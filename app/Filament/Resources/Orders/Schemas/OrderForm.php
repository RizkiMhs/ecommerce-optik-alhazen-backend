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
            // 💡 PERBAIKAN: Hapus pembagian kolom root, biarkan mengalir secara default (full)
            ->components([
                
                // --- KOTAK INFORMASI PESANAN (Sekarang Tampil Penuh/Full Width) ---
                Section::make('Informasi Pesanan')
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

                // 💡 Kotak "Update Pengiriman" sudah dihapus total!

            ]);
    }
}