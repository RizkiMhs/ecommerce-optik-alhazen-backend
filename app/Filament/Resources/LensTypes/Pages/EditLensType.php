<?php

namespace App\Filament\Resources\LensTypes\Pages;

use App\Filament\Resources\LensTypes\LensTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLensType extends EditRecord
{
    protected static string $resource = LensTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
