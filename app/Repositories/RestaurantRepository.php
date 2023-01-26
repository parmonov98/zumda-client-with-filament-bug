<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Restaurant;
use App\Models\RestaurantTranslation;
use Illuminate\Support\Facades\DB;

class RestaurantRepository
{
    /**
     * @var Category
     */
    protected $restaurant;

    function __construct(Restaurant $restaurant)
    {
        $this->restaurant = $restaurant;
    }

    public function getByID($id, $relations = [])
    {
        if ($relations) {
            return $this->restaurant
                ->where('id', $id)
                ->with($relations)
                ->get();
        } else {
            return $this->restaurant
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
                return $this->restaurant
                    ->has('translation')
                    ->with(['translation', 'children'])
                    ->where('parent_id', null)
                    ->where('restaurants.status', $status)
                    ->get();
            } else {
                return $this->restaurant->get();
            }
        } else {
            if (in_array($status, $states)) {
                return $this->restaurant
                    ->has('translation')
                    ->with(['translation', 'operators'])
                    ->whereHas('operators', function ($q){
                        $q->where('partner_operators.status', 'active');
//                        $q->where('status', 'active')->where('self_status', 'on');
                    })
                    ->where('restaurants.status', $status)
                    ->get();
//                    ->dd();
            } else {
                return $this->restaurant->get();
            }
        }
    }

    public function update($id, $data)
    {
        $item = Restaurant::find($id);

        if (!$item instanceof Restaurant){
            return false;
        }

        $item->update($data);
        if (isset($data['name']) && $item->translation){
            $item->translation->name = $data['name'];
            $item->translation->save();
        }
        if (isset($data['address']) && $item->translation){
            $item->translation->address = $data['address'];
            $item->translation->save();
        }
        $item->refresh();
        return $item;
    }
    public function create($data)
    {
        try {
            $item = Restaurant::create($data);
            $translation = new RestaurantTranslation;
            $translation->restaurant_id = $item->id;
            $translation->name = $data['name'];
            $translation->lang = $data['lang'];
            $translation->save();
        }catch(\Exception $e){
//            dd($e->getMessage());
            return $e->getCode();
        }
        return $item;
    }

    public function delete($id)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $item = Restaurant::find($id);

        if ($item){
            $item->translation?->delete();
            $item->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            return true;
        }else{
            return true;
        }

    }
}
