<?php

namespace App\Filament\Resources\ChurnScenarioResource\Pages;

use App\Filament\Resources\ChurnScenarioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChurnScenario extends EditRecord
{
    protected static string $resource = ChurnScenarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
