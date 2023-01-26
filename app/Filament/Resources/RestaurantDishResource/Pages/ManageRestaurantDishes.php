<?php

namespace App\Filament\Resources\RestaurantDishResource\Pages;

use App\Filament\Resources\RestaurantDishResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRestaurantDishes extends ManageRecords
{
    protected static string $resource = RestaurantDishResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
