<?php

namespace App\Filament\Resources\StoreQrLinkResource\Pages;

use App\Filament\Resources\StoreQrLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreQrLink extends EditRecord
{
    protected static string $resource = StoreQrLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
