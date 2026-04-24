<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // 1. Tambahkan Kolom Foto di urutan paling atas (paling kiri)
                ImageColumn::make('images.image_name')
                    ->label('Foto Produk')
                    ->disk('public') // pastikan disk public digunakan
                    ->circular()
                    ->stacked()
                    ->limit(3), // Maksimal menampilkan 3 tumpukan foto
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('base_price')
                    ->money('Rp.', true)
                    ->sortable(),
                TextColumn::make('stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat Detail'), // Menambahkan tombol "View" untuk melihat detail produk
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
