<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRestaurantDishRequest;
use App\Http\Requests\UpdateRestaurantDishRequest;
use App\Models\RestaurantDish;

class RestaurantDishController extends Controller
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
     * @param  \App\Http\Requests\StoreRestaurantDishRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRestaurantDishRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\RestaurantDish  $restaurantDish
     * @return \Illuminate\Http\Response
     */
    public function show(RestaurantDish $restaurantDish)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\RestaurantDish  $restaurantDish
     * @return \Illuminate\Http\Response
     */
    public function edit(RestaurantDish $restaurantDish)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateRestaurantDishRequest  $request
     * @param  \App\Models\RestaurantDish  $restaurantDish
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRestaurantDishRequest $request, RestaurantDish $restaurantDish)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RestaurantDish  $restaurantDish
     * @return \Illuminate\Http\Response
     */
    public function destroy(RestaurantDish $restaurantDish)
    {
        //
    }
}
