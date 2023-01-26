<?php

namespace App\Filament\Resources\PartnerOperatorResource\Pages;

use App\Filament\Resources\PartnerOperatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartnerOperator extends EditRecord
{
    protected static string $resource = PartnerOperatorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
