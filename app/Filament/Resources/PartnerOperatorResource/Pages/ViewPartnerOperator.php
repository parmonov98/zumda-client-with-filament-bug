<?php

namespace App\Filament\Resources\PartnerOperatorResource\Pages;

use App\Filament\Resources\PartnerOperatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPartnerOperator extends ViewRecord
{
    protected static string $resource = PartnerOperatorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
