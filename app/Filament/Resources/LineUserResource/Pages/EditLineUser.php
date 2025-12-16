<?php

namespace App\Filament\Resources\LineUserResource\Pages;

use App\Filament\Resources\LineUserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLineUser extends EditRecord
{
    protected static string $resource = LineUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
