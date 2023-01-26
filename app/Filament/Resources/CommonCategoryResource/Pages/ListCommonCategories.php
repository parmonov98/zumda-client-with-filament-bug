<?php

namespace App\Filament\Resources\CommonCategoryResource\Pages;

use App\Filament\Resources\CommonCategoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommonCategories extends ListRecords
{
    protected static string $resource = CommonCategoryResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
