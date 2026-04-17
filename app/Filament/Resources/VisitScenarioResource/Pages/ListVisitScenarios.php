<?php

namespace App\Filament\Resources\VisitScenarioResource\Pages;

use App\Filament\Resources\VisitScenarioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitScenarios extends ListRecords
{
    protected static string $resource = VisitScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
