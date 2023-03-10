<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditProduct extends EditRecord implements HasForms
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $translations = [];
        foreach ($this->record->translations as $item){
            $translations[$item->lang] = [
                'name' => $item->name,
                'description' => $item->description,
            ];
        }
        $data['restaurant_id'] = $this->record->restaurant->id;
        $data['translations'] = $translations;

        $this->record->load(['dishes', 'options']);
        $product = $this->record;
        $storedDishes = $product->getRelation('dishes')->pick('restaurant_dish_id as dish_id', 'quantity');

        if (count($storedDishes) > 0){
            $data['dishes'] = $storedDishes;
        }

        $storedOptions = $product->getRelation('options')->pick('name', 'price', 'photo_id');
        if (count($storedOptions) > 0){
            $data['options'] = $storedOptions;
        }
        return parent::mutateFormDataBeforeFill($data); // TODO: Change the autogenerated stub
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {

        $record->update($data);

        $record->translations()->delete();
        foreach ($data['translations'] as $lang => $item){
            $record->translations()->create([
                'lang' => $lang,
                'name' => $item['name'],
                'description' => $item['description'],
            ]);
        }

        $record->dishes()->delete();
        if (isset($data['dishes']) && $data['has_required_dish'] === true){
            foreach ($data['dishes'] as $key => $item){
                $record->dishes()->create([
                    'product_id' => $record->id,
                    'restaurant_dish_id' => $item['dish_id'],
                    'quantity' => $item['quantity'],
                ]);

            }
        }
        $record->options()->delete();
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

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make()->afterFormFilled(function () {
                // Runs after the form fields are populated from the database.
                dd(1);
            }),
            Actions\DeleteAction::make(),
        ];
    }

}
