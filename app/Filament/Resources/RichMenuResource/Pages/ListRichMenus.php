<?php

namespace App\Filament\Resources\RichMenuResource\Pages;

use App\Filament\Resources\RichMenuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRichMenus extends ListRecords
{
    protected static string $resource = RichMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
