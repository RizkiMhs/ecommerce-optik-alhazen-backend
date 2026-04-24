<?php

namespace App\Filament\Resources\LensTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class LensTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('lens_name')
                    ->required(),
                TextInput::make('additional_price')
                    ->required()
                    ->numeric()
                    ->default(0.0)
                    ->prefix('Rp. '),
                Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }
}
