<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductDishRequest;
use App\Http\Requests\UpdateProductDishRequest;
use App\Models\ProductDish;

class ProductDishController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreProductDishRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductDishRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ProductDish  $productDish
     * @return \Illuminate\Http\Response
     */
    public function show(ProductDish $productDish)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ProductDish  $productDish
     * @return \Illuminate\Http\Response
     */
    public function edit(ProductDish $productDish)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateProductDishRequest  $request
     * @param  \App\Models\ProductDish  $productDish
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductDishRequest $request, ProductDish $productDish)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ProductDish  $productDish
     * @return \Illuminate\Http\Response
     */
    public function destroy(ProductDish $productDish)
    {
        //
    }
}
