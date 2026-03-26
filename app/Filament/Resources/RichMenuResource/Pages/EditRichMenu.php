<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Filament\Resources\RichMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRichMenu extends EditRecord
{
    protected static string $resource = RichMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
