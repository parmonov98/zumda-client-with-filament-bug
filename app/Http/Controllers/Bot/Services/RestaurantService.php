<?php

namespace App\Http\Controllers\Bot\Services;

use App\Models\Category;
use App\Models\RestaurantTranslation;
use App\Repositories\CategoryRepository;
use App\Repositories\RestaurantRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class RestaurantService
{
    /**
     * @var $restaurantRepository
     */
    protected $restaurantRepository;

    /**
     * UserService constructor
     *
     * @param RestaurantRepository $restaurantRepository
     */

    function __construct(RestaurantRepository $restaurantRepository)
    {
        $this->restaurantRepository = $restaurantRepository;
    }


    public function find($id, $relations = [])
    {
        $items = $this->restaurantRepository->getByID($id, $relations);
        if ($items->count() === 1) {
            return $items->first();
        }
        return null;
    }

    public function addRestaurantName($data = [])
    {
        return $this->restaurantRepository->create($data);
    }

    public function addRestaurantAddress($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function addRestaurantLocation($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function addRestaurantOwner($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function addRestaurantEmployee($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function editRestaurantEmployee($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }
    public function editRestaurantOwner($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function removeRestaurant($id)
    {
        return $this->restaurantRepository->delete($id);
    }

    public function editRestaurantName($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function editRestaurantAddress($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function editRestaurantLocation($id, $data = [])
    {
        return $this->restaurantRepository->update($id, $data);
    }

    public function getActiveRestaurants($as_array = false)
    {

        $items = $this->restaurantRepository->getByStatus(true);

        $filtered_items = $items->filter(fn ($item) => $item->status);

        $items = [];
        if ($as_array){
            $filtered_items->each( function($item) use (&$items) {
                $items[$item->name] = $item->id;
            });
            return $items;
        }else{
            if ($filtered_items->count() > 0) {
                return $filtered_items->pick('name', 'id');
            }
        }
        return new Collection();
    }
    public function getRestaurants($as_array = false)
    {

        $restaurants = $this->restaurantRepository->getByStatus("*");

        $items = [];
        if ($as_array){
            $restaurants->each( function($item) use (&$items) {
                $items[$item->name] = $item->id;
            });
            return $items;
        }else{
            if ($restaurants->count() > 0) {
                return $restaurants->pick('name', 'id');
            }
        }
        return [];
    }
    public function getRestaurantByName($as_array = false)
    {

        $items = $this->restaurantRepository->getByStatus('active');

        $filtered_items = $items->filter(function ($item) {
            // if ($item->status == 'active' && $item->children->count() > 0) {
            if ($item->status == 'active') {
                return true;
            }
        });
        $items = [];
        if ($as_array){
            $filtered_items->each( function($item) use (&$items) {
                $items[$item->translation->name] = $item->id;
            });
            return $items;
        }else{
            if ($filtered_items->count() > 0) {
                return $filtered_items->pick('translation.name', 'id');
            }
        }
        return [];
    }


}
