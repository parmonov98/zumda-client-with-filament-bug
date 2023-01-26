<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantDish;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Actions;
use Filament\Resources\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): Model
    {

        $record  = Product::create($data);

        if (!$record) return false;
        $record->translations()->delete();
        foreach ($data['translations'] as $lang => $item){
            $record->translations()->create([
                'lang' => $lang,
                'name' => $item['name'],
                'description' => $item['description'],
            ]);

        }
        $record->dishes()->delete();
        foreach ($data['dishes'] as $key => $item){
            $record->dishes()->create([
                'product_id' => $record->id,
                'restaurant_dish_id' => $item['dish_id'],
                'quantity' => $item['quantity'],
            ]);

        }

        if ($data['has_options'] === true){
            if (isset($data['options']) && count($data['options']) > 0){
                $record->has_options = true;

                foreach ($data['options'] as $key => $item){
                    $record->options()->create([
                        'product_id' => $record->id,
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'photo_id' => $item['photo_id'],
                    ]);
                }
            }
            $record->has_options = true;
            $record->save();
        }else{
            $record->has_options = false;
            $record->save();
        }

        return $record;
    }
}
