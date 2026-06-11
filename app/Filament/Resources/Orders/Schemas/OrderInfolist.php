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
                        
                        // Format JSON Alamat agar tampil rapi dan elegan
                        TextEntry::make('address_snapshot')
                            ->label('Alamat Pengiriman')
                            ->html() 
                            ->formatStateUsing(function ($state) {
                                $data = is_string($state) ? json_decode($state, true) : $state;
                                
                                if (is_array($data)) {
                                    $nama = $data['name'] ?? '-';
                                    $hp = $data['phone'] ?? '-';
                                    $alamat = $data['address'] ?? '-';
                                    
                                    return "<b>Penerima:</b> {$nama} <br>" .
                                           "<b>No. HP:</b> {$hp} <br>" .
                                           "<b>Detail Alamat:</b> {$alamat}";
                                }
                                
                                return $state;
                            })
                            ->columnSpanFull(),
                    ])->columns(3),

                // KOTAK 2: Resep Mata Jumbo + Foto Resep
                Section::make('TUGAS TEKNISI: Resep Kacamata')
                    ->schema([
                        TextEntry::make('resep')
                            ->label('') 
                            ->html()    
                            ->state(function ($record) {
                                $html = '';
                                
                                foreach ($record->orderItems as $item) {
                                    $resep = $item->prescription_data;
                                    
                                    if (is_string($resep)) {
                                        $resep = json_decode($resep, true);
                                    }

                                    // 💡 Cek keberadaan path gambar (bisa di dalam JSON array resep atau kolom terpisah di order item)
                                    $imagePath = $resep['prescription_image'] ?? $item->prescription_image ?? null;

                                    // 💡 UPDATE KONDISI: Kotak muncul jika ada angka resep ATAU ada foto resep yang di-upload
                                    if (is_array($resep) || !empty($imagePath)) {
                                        $frame = $item->product ? $item->product->name : 'Frame/Kacamata';
                                        $lens = $item->lensType ? $item->lensType->lens_name : 'Lensa Standar';
                                        
                                        // --- Desain Kotak HTML Jumbo ---
                                        $html .= '<div style="border: 3px solid #ef4444; padding: 20px; border-radius: 12px; background-color: #fef2f2; margin-bottom: 15px;">';
                                        $html .= '<div style="font-size: 16px; margin-bottom: 10px; color: #374151;">Merakit: <b>' . $frame . '</b> | Lensa: <b>' . $lens . '</b></div>';
                                        
                                        // Hanya tampilkan baris text resep jika ada data angka yang diinput
                                        if (!empty($resep['sph_right']) || !empty($resep['sph_left'])) {
                                            $html .= '<div style="font-size: 32px; font-weight: 900; color: #b91c1c; line-height: 1.4; font-family: monospace;">';
                                            $html .= 'Kanan (R): SPH ' . ($resep['sph_right'] ?? '-') . ' | CYL ' . ($resep['cyl_right'] ?? '-') . '<br>';
                                            $html .= 'Kiri (L) : SPH ' . ($resep['sph_left'] ?? '-') . ' | CYL ' . ($resep['cyl_left'] ?? '-') . '';
                                            $html .= '</div>';
                                            $html .= '<div style="font-size: 20px; font-weight: bold; margin-top: 15px; color: #111827;">PD (Jarak Pupil): ' . ($resep['pd'] ?? '-') . '</div>';
                                        } else {
                                            $html .= '<div style="font-size: 15px; color: #dc2626; font-style: italic; font-weight: bold; margin-bottom: 10px;">*Kustomer tidak mengisi angka resep (Mengandalkan foto kartu resep di bawah)</div>';
                                        }
                                        
                                        // --- 💡 FITUR BARU: RENDER FOTO RESEP JIKA TERSEDIA ---
                                        if (!empty($imagePath)) {
                                            $imageUrl = asset('storage/' . $imagePath);
                                            
                                            $html .= '<div style="margin-top: 20px; border-top: 1px dashed #fca5a5; padding-top: 15px;">';
                                            $html .= '<div style="font-size: 15px; font-weight: bold; margin-bottom: 8px; color: #1f2937;">📷 Lampiran Foto Kartu Resep:</div>';
                                            // Dibungkus tag <a> supaya gambar bisa diklik dan terbuka penuh di tab baru
                                            $html .= '<a href="' . $imageUrl . '" target="_blank" style="display: inline-block;">';
                                            $html .= '<img src="' . $imageUrl . '" style="max-width: 100%; max-height: 350px; border-radius: 8px; border: 2px solid #ef4444; object-fit: contain; background-color: white; cursor: pointer;" title="Klik untuk memperbesar gambar">';
                                            $html .= '</a>';
                                            $html .= '<div style="font-size: 12px; color: #4b5563; margin-top: 6px;">💡 <i>Klik pada gambar di atas untuk melihat resolusi penuh di tab baru.</i></div>';
                                            $html .= '</div>';
                                        }

                                        if (!empty($resep['note'])) {
                                            $html .= '<div style="font-size: 16px; margin-top: 12px; color: #4b5563; background: #fff; padding: 8px; border-radius: 6px; border: 1px solid #fee2e2;"><b>Catatan:</b> ' . $resep['note'] . '</div>';
                                        }
                                        
                                        $html .= '</div>';
                                    }
                                }

                                // Jika tidak ada resep sama sekali
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