<?php

namespace App\Filament\Resources\PartnerOperatorResource\Pages;

use App\Filament\Resources\PartnerOperatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPartnerOperators extends ListRecords
{
    protected static string $resource = PartnerOperatorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
