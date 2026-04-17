<?php

namespace App\Filament\Resources\VisitScenarioResource\Pages;

use App\Filament\Resources\VisitScenarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVisitScenario extends EditRecord
{
    protected static string $resource = VisitScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
