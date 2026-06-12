<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\TextSize;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1) 
            ->components([
                // 💡 KOTAK 1: Info Transaksi & Pengiriman
                Section::make('Informasi Pesanan')
                    ->description('Detail transaksi, rincian produk, dan informasi pengiriman kustomer.')
                    ->schema([
                        // --- Baris 1: Status & Waktu ---
                        TextEntry::make('order_number')
                            ->label('No. Pesanan')
                            ->weight('bold')
                            ->copyable()
                            ->icon('heroicon-o-hashtag')
                            ->color('primary'),

                        TextEntry::make('created_at')
                            ->label('Tanggal Pemesanan')
                            ->dateTime('d F Y, H:i')
                            ->icon('heroicon-o-calendar'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'unpaid' => 'danger',
                                'paid' => 'warning',
                                'processing' => 'info',
                                'shipping' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),

                        // --- Baris 2: Rincian Keuangan ---
                        TextEntry::make('shipping_cost')
                            ->label('Ongkos Kirim')
                            ->money('IDR', locale: 'id'),

                        TextEntry::make('discount_amount')
                            ->label('Diskon Voucher')
                            ->money('IDR', locale: 'id')
                            ->color('success')
                            ->weight('bold')
                            ->state(function ($record) {
                                return $record->discount_amount > 0 ? -$record->discount_amount : 0;
                            }),

                        TextEntry::make('total_amount')
                            ->label('Grand Total')
                            ->money('IDR', locale: 'id')
                            ->weight('bold')
                            ->color('primary')
                            ->size(TextSize::Large),

                        // --- Baris 3: Kurir & Promo ---
                        TextEntry::make('courier')
                            ->label('Kurir Pengiriman')
                            ->formatStateUsing(fn ($state) => strtoupper($state))
                            ->icon('heroicon-o-truck'),

                        TextEntry::make('tracking_number')
                            ->label('Nomor Resi')
                            ->copyable()
                            ->placeholder('Belum diinput'),

                        TextEntry::make('voucher_code')
                            ->label('Promo Dipakai')
                            ->badge()
                            ->color('success')
                            ->placeholder('Tidak pakai promo'),

                        // 💡 --- FITUR BARU: RINCIAN PRODUK & GAMBAR ---
                        TextEntry::make('rincian_produk')
                            ->label('Produk yang Dibeli')
                            ->html()
                            ->state(function ($record) {
                                $html = '<div style="display: flex; flex-direction: column; gap: 10px; margin-top: 5px;">';
                                
                                foreach ($record->orderItems as $item) {
                                    $productName = $item->product ? $item->product->name : 'Produk Tidak Diketahui';
                                    $lensName = $item->lensType ? '+ Lensa ' . $item->lensType->lens_name : 'Tanpa Tambahan Lensa';
                                    $qty = $item->qty;
                                    $price = number_format($item->price_at_purchase, 0, ',', '.');
                                    
                                    // Mengambil gambar produk
                                    $imageUrl = null;
                                    if ($item->product && $item->product->images) {
                                        // Pengecekan apakah JSON atau array Relasi
                                        $images = is_string($item->product->images) ? json_decode($item->product->images, true) : $item->product->images;
                                        
                                        if (is_iterable($images) && count($images) > 0) {
                                            // Ambil gambar pertama
                                            $firstImage = $images[0]['image_name'] ?? ($images[0]->image_name ?? null);
                                            if ($firstImage) {
                                                $imageUrl = asset('storage/' . $firstImage);
                                            }
                                        }
                                    }

                                    // Render Thumbnail Gambar (Bisa diklik)
                                    $imgHtml = $imageUrl 
                                        ? '<a href="'.$imageUrl.'" target="_blank" title="Klik untuk lihat gambar penuh" style="flex-shrink: 0;"><img src="'.$imageUrl.'" style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer; transition: 0.3s;" onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1"></a>'
                                        : '<div style="width: 55px; height: 55px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;" title="Tidak ada gambar">📦</div>';

                                    // Render Baris Produk
                                    $html .= "
                                        <div style='display: flex; align-items: center; gap: 15px; background-color: #f8fafc; padding: 12px; border-radius: 10px; border: 1px solid #f1f5f9;'>
                                            {$imgHtml}
                                            <div style='flex: 1;'>
                                                <div style='font-weight: bold; color: #1e293b; font-size: 15px;'>{$productName}</div>
                                                <div style='font-size: 13px; color: #64748b; margin-top: 2px;'>{$lensName}</div>
                                            </div>
                                            <div style='text-align: right;'>
                                                <div style='font-size: 13px; color: #64748b;'>{$qty} x</div>
                                                <div style='font-weight: bold; color: #0f172a;'>Rp {$price}</div>
                                            </div>
                                        </div>
                                    ";
                                }
                                $html .= '</div>';
                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),

                        // --- Baris 4: Kotak Alamat Cantik ---
                        TextEntry::make('address_snapshot')
                            ->label('Alamat Tujuan')
                            ->html() 
                            ->formatStateUsing(function ($state) {
                                $data = is_string($state) ? json_decode($state, true) : $state;
                                
                                if (is_array($data)) {
                                    $nama = $data['name'] ?? '-';
                                    $hp = $data['phone'] ?? '-';
                                    $alamat = $data['address'] ?? '-';
                                    
                                    return "
                                        <div style='background-color: #f3f4f6; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;'>
                                            <div style='margin-bottom: 6px; color: #111827;'><b>👤 Penerima:</b> {$nama} ({$hp})</div>
                                            <div style='color: #4b5563; line-height: 1.5;'><b>📍 Detail Alamat:</b><br>{$alamat}</div>
                                        </div>
                                    ";
                                }
                                
                                return $state;
                            })
                            ->columnSpanFull(), 
                    ])
                    ->columns(3) 
                    ->columnSpanFull(),

                // 💡 KOTAK 2: Resep Mata Jumbo + Foto Resep
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

                                    $imagePath = $resep['prescription_image'] ?? $item->prescription_image ?? null;

                                    if (is_array($resep) || !empty($imagePath)) {
                                        $frame = $item->product ? $item->product->name : 'Frame/Kacamata';
                                        $lens = $item->lensType ? $item->lensType->lens_name : 'Lensa Standar';
                                        
                                        $html .= '<div style="border: 3px solid #ef4444; padding: 20px; border-radius: 12px; background-color: #fef2f2; margin-bottom: 15px;">';
                                        $html .= '<div style="font-size: 16px; margin-bottom: 15px; color: #374151; border-bottom: 1px solid #fca5a5; padding-bottom: 10px;">Merakit: <b>' . $frame . '</b> | Lensa: <b>' . $lens . '</b></div>';
                                        $html .= '<div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start;">';
                                        
                                        $html .= '<div style="flex: 1; min-width: 280px;">';
                                        if (!empty($resep['sph_right']) || !empty($resep['sph_left'])) {
                                            $html .= '<div style="font-size: 32px; font-weight: 900; color: #b91c1c; line-height: 1.4; font-family: monospace;">';
                                            $html .= 'Kanan (R): SPH ' . ($resep['sph_right'] ?? '-') . ' | CYL ' . ($resep['cyl_right'] ?? '-') . '<br>';
                                            $html .= 'Kiri (L) : SPH ' . ($resep['sph_left'] ?? '-') . ' | CYL ' . ($resep['cyl_left'] ?? '-') . '';
                                            $html .= '</div>';
                                            $html .= '<div style="font-size: 20px; font-weight: bold; margin-top: 15px; color: #111827;">PD (Jarak Pupil): ' . ($resep['pd'] ?? '-') . '</div>';
                                        } else {
                                            $html .= '<div style="font-size: 15px; color: #dc2626; font-style: italic; font-weight: bold; margin-bottom: 10px;">*Kustomer tidak mengisi angka resep manual.</div>';
                                        }

                                        if (!empty($resep['note'])) {
                                            $html .= '<div style="font-size: 14px; margin-top: 15px; color: #4b5563; background: #fff; padding: 10px; border-radius: 6px; border: 1px solid #fee2e2;"><b>Catatan Kustomer:</b><br>' . nl2br($resep['note']) . '</div>';
                                        }
                                        $html .= '</div>';

                                        $html .= '<div style="flex: 1; min-width: 280px; border-left: 2px dashed #fca5a5; padding-left: 20px;">';
                                        if (!empty($imagePath)) {
                                            $imageUrl = asset('storage/' . $imagePath);
                                            $html .= '<div style="font-size: 15px; font-weight: bold; margin-bottom: 8px; color: #1f2937;">📷 Lampiran Foto Kartu Resep:</div>';
                                            $html .= '<a href="' . $imageUrl . '" target="_blank" style="display: inline-block;">';
                                            $html .= '<img src="' . $imageUrl . '" style="max-width: 100%; max-height: 220px; border-radius: 8px; border: 2px solid #ef4444; object-fit: contain; background-color: white; cursor: pointer;" title="Klik untuk memperbesar gambar">';
                                            $html .= '</a>';
                                            $html .= '<div style="font-size: 12px; color: #4b5563; margin-top: 6px;">💡 <i>Klik pada gambar untuk melihat resolusi penuh.</i></div>';
                                        } else {
                                            $html .= '<div style="font-size: 15px; color: #9ca3af; font-style: italic; padding-top: 20px;">Tidak ada foto resep yang dilampirkan.</div>';
                                        }
                                        $html .= '</div>'; 
                                        $html .= '</div>'; 
                                        $html .= '</div>'; 
                                    }
                                }

                                if ($html === '') {
                                    return '<div style="color: gray; font-style: italic;">Tidak ada resep kacamata (Hanya membeli aksesoris).</div>';
                                }

                                return new HtmlString($html);
                            })
                            ->columnSpanFull()
                    ])
                    ->columnSpanFull() 
            ]);
    }
}