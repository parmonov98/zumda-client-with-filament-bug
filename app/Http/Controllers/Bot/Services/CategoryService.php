<?php

namespace App\Http\Controllers\Bot\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class CategoryService
{
    /**
     * @var $categoryRespository
     */
    protected $categoryRepository;

    /**
     * UserService constructor
     *
     * @param CategoryRepository $categoryRepository
     */

    function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }
    public function addCategory($data = [])
    {
        return $this->categoryRepository->create($data);
    }
    public function addCategoryName($id, $data = [])
    {
        return $this->categoryRepository->create_translation($id, $data);
    }
    public function addCategoryDescription($id, $data = [])
    {
        return $this->categoryRepository->update($id, $data);
    }
    public function editCategoryName($id, $data = [])
    {
        return $this->categoryRepository->update($id, $data);
    }
    public function editCategoryDescription($id, $data = [])
    {
        return $this->categoryRepository->update($id, $data);
    }
    public function removeCategory($id)
    {
        return $this->categoryRepository->delete($id);
    }
    public function find($id, $relations = ['translation', 'restaurant'])
    {
        $items = $this->categoryRepository->getByID($id, $relations);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function getActiveCategories($restaurant_id, $is_first_level = false)
    {
        $items = $this->categoryRepository->getByRestaurant($restaurant_id, true, $is_first_level);
        $filtered_items = $items->filter(fn($item) => $item->status);
        if ($filtered_items->count() > 0) {
            return $filtered_items->pick('translation.name', 'id');
        }
        return New Collection();
    }
    public function getAll($restaurant_id)
    {
        $items = $this->categoryRepository->getByRestaurant($restaurant_id, $status = '*');

        if ($items->count() > 0) {
            return $items->pick('translation.name', 'id');
        }
        return New Collection();
    }
}
