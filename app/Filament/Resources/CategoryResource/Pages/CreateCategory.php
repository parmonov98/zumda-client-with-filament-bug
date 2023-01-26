<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use App\Models\Category;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {

        $record  = Category::create($data);

        if (!$record) return false;
        $record->translations()->delete();
        foreach ($data['translations'] as $lang => $item){
            $record->translations()->create([
                'lang' => $lang,
                'name' => $item['name'],
                'description' => $item['description'],
            ]);

        }

        return $record;
    }
}
