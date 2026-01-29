<?php

namespace App\Filament\Resources\StampCardDefinitionResource\Pages;

use App\Filament\Resources\StampCardDefinitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStampCardDefinitions extends ListRecords
{
    protected static string $resource = StampCardDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
