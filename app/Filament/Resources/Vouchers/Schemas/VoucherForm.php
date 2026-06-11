<?php

namespace App\Filament\Resources\Vouchers\Schemas;

use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Voucher')
                    ->description('Atur kode dan jenis potongan harga di sini.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Voucher')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper(trim($state)) : null)
                            ->helperText('Contoh: ALHAZEN10, PROMO2026'),

                        Select::make('discount_type')
                            ->label('Jenis Diskon')
                            ->options([
                                'percent' => 'Persentase (%)',
                                'fixed' => 'Potongan Harga Tetap (Rp)',
                            ])
                            ->required()
                            ->default('fixed')
                            ->live(),

                        TextInput::make('discount_value')
                            ->label('Nilai Diskon')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->prefix(fn (Get $get): ?string => $get('discount_type') === 'fixed' ? 'Rp' : null)
                            ->suffix(fn (Get $get): ?string => $get('discount_type') === 'percent' ? '%' : null)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    if ($get('discount_type') === 'percent') {
                                        $value = (float) $value;

                                        if ($value < 1 || $value > 100) {
                                            $fail('Nilai diskon persentase harus berada antara 1 sampai 100.');
                                        }
                                    }
                                },
                            ])
                            ->helperText('Jika persen, isi 1-100. Jika harga tetap, isi nominal tanpa titik, misalnya 50000.'),
                    ])
                    ->columns(3),

                Section::make('Syarat & Ketentuan')
                    ->schema([
                        TextInput::make('min_purchase')
                            ->label('Minimal Belanja')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->prefix('Rp'),

                        DateTimePicker::make('valid_until')
                            ->label('Berlaku Sampai')
                            ->nullable()
                            ->helperText('Kosongkan jika voucher berlaku selamanya.'),

                        Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Matikan jika voucher sedang tidak ingin digunakan.'),
                    ])
                    ->columns(3),
            ]);
    }
}