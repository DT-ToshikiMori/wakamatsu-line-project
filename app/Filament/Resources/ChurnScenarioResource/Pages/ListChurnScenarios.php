<?php

namespace App\Filament\Resources\ChurnScenarioResource\Pages;

use App\Filament\Resources\ChurnScenarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChurnScenarios extends ListRecords
{
    protected static string $resource = ChurnScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
