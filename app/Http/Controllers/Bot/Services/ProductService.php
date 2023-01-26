<?php

namespace App\Http\Controllers\Bot\Services;

use App\Models\Category;
use App\Repositories\ProductRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ProductService
{
    /**
     * @var $productRespository
     */
    protected $productRepository;

    /**
     * ProductService constructor
     *
     * @param ProductRepository $productRepository
     */

    function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }
    public function addProduct(array $data)
    {
        return $this->productRepository->create($data);
    }
    public function addProductName($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function addProductPhoto($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function editProductPhoto($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function editProductDescription($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function addProductPrice($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function addProductDescription($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function removeProduct($id, $data = [])
    {
        return $this->productRepository->delete($id);
    }
    public function editProductName($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function editProductPrice($id, $data = [])
    {
        return $this->productRepository->update($id, $data);
    }
    public function find($id, $relations = [])
    {
        $items = $this->productRepository->getByID($id, $relations);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function getActiveCategories($is_first_level = false)
    {
        $items = $this->productRepository->getByStatus('active', $is_first_level);
        $filtered_items = $items->filter(function ($item) {
            if ($item->status == 'active' && $item->children->count() > 0) {
                return true;
            }
        });
        if ($filtered_items->count() > 0) {
            return $filtered_items->pick('translation.name', 'id');
        }
        return [];
    }

    public function getAll($category_id)
    {
        $items = $this->productRepository->getByCategory($category_id, $status = '*');

        if ($items->count() > 0) {
            return $items->pick('translation.name', 'id', 'status');
        }
        return [];
    }
    public function getActiveProducts($category_id)
    {
        $items = $this->productRepository->getByCategory($category_id, true);

        if ($items->count() > 0) {
            return $items;
        }
        return [];
    }
}
