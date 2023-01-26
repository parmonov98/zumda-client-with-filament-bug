<?php

namespace App\Filament\Resources\CommonCategoryResource\Pages;

use App\Filament\Resources\CommonCategoryResource;
use App\Models\Category;
use App\Models\CommonCategory;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCommonCategory extends CreateRecord
{
    protected static string $resource = CommonCategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {

        $data['name'] = $data['translations']['uz']['name'];
        $record  = CommonCategory::create($data);

        if (!$record) return false;

        $record->translations()->delete();
        foreach ($data['translations'] as $lang => $item){
            $record->translations()->create([
                'lang' => $lang,
                'name' => $item['name'],
            ]);

        }

        return $record;
    }
}
