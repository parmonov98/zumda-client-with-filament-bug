<?php

namespace App\Filament\Resources\OperatorResource\Pages;

use App\Filament\Resources\OperatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOperator extends ViewRecord
{
    protected static string $resource = OperatorResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
