<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('category')
                    ->options(['pria' => 'Pria', 'wanita' => 'Wanita', 'unisex' => 'Unisex', 'aksesoris' => 'Aksesoris'])
                    ->required(),
                TextInput::make('base_price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('description')
                    ->columnSpanFull(),

                // ... (kode textarea description sebelumnya) ...

                Repeater::make('images')
                    ->relationship() // Otomatis membaca fungsi images() di model Product
                    ->label('Galeri Foto Produk')
                    ->schema([
                        FileUpload::make('image_name')
                            ->label('Unggah Foto')
                            ->image()
                            ->disk('public') // WAJIB agar file bisa diakses via URL
                            ->directory('product-images') // Akan disimpan di folder storage/app/public/product-images
                            ->required()
                            ->columnSpanFull(),
                            
                        Toggle::make('is_primary')
                            ->label('Jadikan sebagai Cover/Thumbnail Utama')
                            ->default(false),
                    ])
                    ->grid(2) // Menampilkan kotak upload secara berdampingan (2 kolom)
                    ->columnSpanFull()
                    ->defaultItems(1) // Minimal ada 1 kotak upload kosong
                    ->addActionLabel('Tambah Foto Lainnya'),
            ]);
    }
}
