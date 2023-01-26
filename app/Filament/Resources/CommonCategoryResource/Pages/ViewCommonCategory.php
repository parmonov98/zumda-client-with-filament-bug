<?php

namespace App\Filament\Resources\CommonCategoryResource\Pages;

use App\Filament\Resources\CommonCategoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCommonCategory extends ViewRecord
{
    protected static string $resource = CommonCategoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
