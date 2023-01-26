<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

//    public function handleDehydrateProperty($property, $value)
//    {
//        dd($property, $value);
//    }


    protected function handleRecordCreation(array $data): Model
    {
        $record  = Order::create($data);

        if (!$record) return false;

        $record->items()->delete();
        foreach ($data['items'] as $key => $item){
            $itemProduct = Product::find($item['product_id']);
            if ($itemProduct){
                $record->items()->create([
                    'order_id' => $record->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $itemProduct->price,
                ]);
            }
        }

        return $record;
    }
}
