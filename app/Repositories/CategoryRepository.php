<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Restaurant;
use App\Models\RestaurantTranslation;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    /**
     * @var Category
     */
    protected $category;

    function __construct(Category $category)
    {
        $this->category = $category;
    }

    public function create($data)
    {
        try {
            $item = Category::create($data);
//            $translation = new CategoryTranslation;
//            $translation->category_id = $item->id;
//            $translation->name = $data['name'];
//            $translation->lang = $data['lang'];
//            $translation->save();
            return $item;
        }catch(\Exception $e){
            return $e->getCode();
        }
    }

    public function update($id, $data)
    {
        $item = Category::find($id);

        if (!$item instanceof Category){
            return false;
        }
        $item->update($data);

        if (isset($data['name'])){
            $item->translation->name = $data['name'];
            $item->translation->save();
        }
        if (isset($data['description'])){
            $item->translation->description = $data['description'];
            $item->translation->save();
        }
        $item->refresh();
        return $item;
    }

    public function create_translation($id, $data)
    {
        $item = Category::find($id);

        if (!$item instanceof Category){
            return false;
        }
        if (isset($data['name'])){
            $translation = new CategoryTranslation;
            $translation->category_id = $id;
            $translation->lang = $data['lang'];
            $translation->name = $data['name'];
            $translation->save();
        }
        $item->refresh();
        return $item;
    }

    public function delete($id)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $item = Category::find($id);
        if (!$item instanceof Category) return true;
        if ($item->translation){
            $item->translation->delete();
        }
        $item->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return true;
    }

    public function getByID($id, $relations = [])
    {
        if ($relations) {
            return $this->category
                ->where('id', $id)
                ->with($relations)
                ->get();
        } else {
            return $this->category
                ->where('id', $id)
                ->with(['translation'])
                ->get();
        }
    }

    public function getByStatus(string $status = '*', $is_first_level = false)
    {
        $states = ['active', 'inactive'];

        if ($is_first_level) {
            if (in_array($status, $states)) {
                return $this->category
                    ->has('translation')
                    ->with(['translation', 'children'])
                    ->where('parent_id', null)
                    ->where('status', $status)
                    ->get();
            } else {
                return $this->category->get();
            }
        } else {
            if (in_array($status, $states)) {
                return $this->category
                    ->has('translation')
                    ->with('translation')
                    ->where('status', $status)
                    ->get();
            } else {
                return $this->category->get();
            }
        }
    }

    public function getByRestaurant($restaurant_id, mixed $status = '*', $is_first_level = false)
    {
//        dd($restaurant_id, $status, $is_first_level);
        if ($status === true || $status === false) {
            return $this->category
                ->has('translation')
                ->with(['translation'])
                ->where('status', $status)
                ->where('restaurant_id', $restaurant_id)
                ->get();
        } else {
            return $this->category
                ->where('restaurant_id', $restaurant_id)
                ->get();
        }
    }
}
