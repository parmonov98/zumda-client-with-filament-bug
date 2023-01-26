<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderBotMessagesRequest;
use App\Http\Requests\UpdateOrderBotMessagesRequest;
use App\Models\OrderBotMessages;

class OrderBotMessagesController extends Controller
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
     * @param  \App\Http\Requests\StoreOrderBotMessagesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreOrderBotMessagesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\OrderBotMessages  $orderBotMessages
     * @return \Illuminate\Http\Response
     */
    public function show(OrderBotMessages $orderBotMessages)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\OrderBotMessages  $orderBotMessages
     * @return \Illuminate\Http\Response
     */
    public function edit(OrderBotMessages $orderBotMessages)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateOrderBotMessagesRequest  $request
     * @param  \App\Models\OrderBotMessages  $orderBotMessages
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateOrderBotMessagesRequest $request, OrderBotMessages $orderBotMessages)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\OrderBotMessages  $orderBotMessages
     * @return \Illuminate\Http\Response
     */
    public function destroy(OrderBotMessages $orderBotMessages)
    {
        //
    }
}
