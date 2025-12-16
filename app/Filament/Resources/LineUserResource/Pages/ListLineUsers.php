<?php

namespace App\Filament\Resources\LineUserResource\Pages;

use App\Filament\Resources\LineUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLineUsers extends ListRecords
{
    protected static string $resource = LineUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
