<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    /**
     * @var Product
     */
    protected $product;

    function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function create(array $data)
    {
        try {
            $item = Product::create($data);
//            $translation = new CategoryTranslation;
//            $translation->category_id = $item->id;
//            $translation->name = $data['name'];
//            $translation->lang = $data['lang'];
//            $translation->save();
            return $item;
        }catch(\Exception $e){
//            dd($e->getMessage());
            return $e->getCode();
        }
    }

//    public function update($product_id, $data)
//    {
//        $product = $this->getByID($product_id);
//        // dd($user);
//        if ($product->count() !== 1) {
//            return false;
//        }
//        $product = $product->first();
//
//        $product->update($data);
//
//        return $product->refresh();
//    }


    public function update($id, $data)
    {
        $item = Product::find($id);

        if (!$item instanceof Product){
            return false;
        }
        $item->update($data);

        if (isset($data['name'])){
            if ($item->translation){
                $item->translation->name = $data['name'];
                $item->translation->save();
            }else{
                $translation = new ProductTranslation;
                $translation->product_id = $item->id;
                $translation->lang = $data['lang'];
                $translation->name = $data['name'];
                $translation->save();
            }
        }
        if (isset($data['description'])){
            if ($item->translation) {
                $item->translation->description = $data['description'];
                $item->translation->save();
            }
        }
        $item->refresh();
        return $item;
    }


    public function getByID($id, $relations = [])
    {
        // dd($id);
        if ($relations) {
            return $this->product
                ->where('id', $id)
                ->with($relations)
                ->get();
        } else {
            return $this->product
                ->where('id', $id)
                ->with(['translation', 'category'])
                ->get();
        }
    }
    public function delete($id)
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $item = Product::find($id);
        if (!$item instanceof Product) return true;
        if ($item->translation){
            $item->translation->delete();
        }
        $item->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        return true;
    }

    public function getByStatus(string $status = '*', $is_first_level = false)
    {
        $states = ['active', 'inactive'];

        if ($is_first_level) {
            if (in_array($status, $states)) {
                return $this->product
                    ->has('translation')
                    ->with(['translation', 'category'])
                    ->where('status', $status)
                    ->get();
            } else {
                return $this->product->get();
            }
        } else {
            if (in_array($status, $states)) {
                return $this->product
                    ->has('translation')
                    ->with('translation')
                    ->where('status', $status)
                    ->get();
            } else {
                return $this->product->get();
            }
        }
    }

    public function getByCategory($category_id, mixed $status = '*', $is_first_level = false)
    {
        if ($status === true || $status === false) {
            return $this->product
                ->has('translation')
                ->with(['translation'])
                ->where('status', $status)
                ->where('category_id', $category_id)
                ->get();
        } else {
            return $this->product
                ->with(['translation'])
                ->where('category_id', $category_id)
                ->get();
        }

    }
}
