<?php

namespace App\Filament\Resources\Vouchers\Tables;

use App\Models\Voucher;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Voucher')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->color('primary'),

                TextColumn::make('discount_type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'info',
                        'fixed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percent' => 'Persen (%)',
                        'fixed' => 'Harga Tetap',
                        default => $state,
                    }),

                TextColumn::make('discount_value')
                    ->label('Nilai Diskon')
                    ->formatStateUsing(function (mixed $state, Voucher $record): string {
                        if ($record->discount_type === 'percent') {
                            return number_format((float) $state, 0, ',', '.') . '%';
                        }

                        return 'Rp ' . number_format((float) $state, 0, ',', '.');
                    }),

                TextColumn::make('min_purchase')
                    ->label('Min. Belanja')
                    ->money('IDR', locale: 'id'),

                TextColumn::make('valid_until')
                    ->label('Berlaku Sampai')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Selamanya')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}