<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // KOTAK 1: Info Transaksi Dasar
                Section::make('Informasi Pesanan')
                    ->schema([
                        TextEntry::make('order_number')->label('No. Pesanan')->weight('bold'),
                        TextEntry::make('total_amount')->label('Total Harga')->money('IDR', locale: 'id'),
                        TextEntry::make('status')->badge()
                            ->color(fn ($state) => match ($state) {
                                'unpaid' => 'danger',
                                'paid' => 'warning',
                                'processing' => 'info',
                                'shipping' => 'primary',
                                'completed' => 'success',
                                default => 'gray',
                            }),
                        // 💡 PERBAIKAN: Format JSON Alamat agar tampil rapi dan elegan
                        TextEntry::make('address_snapshot')
                            ->label('Alamat Pengiriman')
                            ->html() // Mengizinkan render HTML (seperti <b> dan <br>)
                            ->formatStateUsing(function ($state) {
                                // Coba ubah JSON string menjadi Array
                                $data = is_string($state) ? json_decode($state, true) : $state;
                                
                                // Jika berhasil diubah dan bentuknya Array, format dengan rapi
                                if (is_array($data)) {
                                    $nama = $data['name'] ?? '-';
                                    $hp = $data['phone'] ?? '-';
                                    $alamat = $data['address'] ?? '-';
                                    
                                    return "<b>Penerima:</b> {$nama} <br>" .
                                           "<b>No. HP:</b> {$hp} <br>" .
                                           "<b>Detail Alamat:</b> {$alamat}";
                                }
                                
                                // Jika bukan JSON (teks biasa), tampilkan apa adanya
                                return $state;
                            })
                            ->columnSpanFull(),
                    ])->columns(3),

                // KOTAK 2: Resep Mata Jumbo
                Section::make('TUGAS TEKNISI: Resep Kacamata')
                    ->schema([
                        TextEntry::make('resep')
                            ->label('') 
                            ->html()    
                            ->state(function ($record) {
                                $html = '';
                                
                                // 💡 BUG FIXED: Relasi di Laravel adalah orderItems (sesuai Flutter)
                                foreach ($record->orderItems as $item) {
                                    // 💡 BUG FIXED: Ambil dari kolom JSON prescription_data
                                    $resep = $item->prescription_data;
                                    
                                    // Pastikan data JSON di-decode menjadi Array
                                    if (is_string($resep)) {
                                        $resep = json_decode($resep, true);
                                    }

                                    // Cek apakah array resep ada isinya (sph_right atau sph_left tidak kosong)
                                    if (is_array($resep) && (!empty($resep['sph_right']) || !empty($resep['sph_left']))) {
                                        $frame = $item->product ? $item->product->name : 'Frame/Kacamata';
                                        $lens = $item->lensType ? $item->lensType->lens_name : 'Lensa Standar';
                                        
                                        // --- Desain Kotak HTML Jumbo ---
                                        $html .= '<div style="border: 3px solid #ef4444; padding: 20px; border-radius: 12px; background-color: #fef2f2; margin-bottom: 15px;">';
                                        $html .= '<div style="font-size: 16px; margin-bottom: 10px; color: #374151;">Merakit: <b>' . $frame . '</b> | Lensa: <b>' . $lens . '</b></div>';
                                        
                                        $html .= '<div style="font-size: 32px; font-weight: 900; color: #b91c1c; line-height: 1.4; font-family: monospace;">';
                                        $html .= 'Kanan (R): SPH ' . ($resep['sph_right'] ?? '-') . ' | CYL ' . ($resep['cyl_right'] ?? '-') . '<br>';
                                        $html .= 'Kiri (L) : SPH ' . ($resep['sph_left'] ?? '-') . ' | CYL ' . ($resep['cyl_left'] ?? '-') . '';
                                        $html .= '</div>';
                                        
                                        $html .= '<div style="font-size: 20px; font-weight: bold; margin-top: 15px; color: #111827;">PD (Jarak Pupil): ' . ($resep['pd'] ?? '-') . '</div>';
                                        
                                        if (!empty($resep['note'])) {
                                            $html .= '<div style="font-size: 16px; margin-top: 10px; color: #4b5563;">Catatan: ' . $resep['note'] . '</div>';
                                        }
                                        $html .= '</div>';
                                    }
                                }

                                // Jika tidak ada resep 
                                if ($html === '') {
                                    return '<div style="color: gray; font-style: italic;">Tidak ada resep kacamata (Hanya membeli aksesoris).</div>';
                                }

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                    ])
            ]);
    }
}