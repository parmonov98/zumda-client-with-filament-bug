<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Pages\Actions;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = "Buyurtmalar";

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label("Yangi zakaz")
                ->submit("Qo'shish")
            ,
        ];
    }
}
