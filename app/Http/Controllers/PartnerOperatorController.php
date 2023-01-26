<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePartnerOperatorRequest;
use App\Http\Requests\UpdatePartnerOperatorRequest;
use App\Models\PartnerOperator;

class PartnerOperatorController extends Controller
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
     * @param  \App\Http\Requests\StorePartnerOperatorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePartnerOperatorRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PartnerOperator  $partnerOperator
     * @return \Illuminate\Http\Response
     */
    public function show(PartnerOperator $partnerOperator)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PartnerOperator  $partnerOperator
     * @return \Illuminate\Http\Response
     */
    public function edit(PartnerOperator $partnerOperator)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePartnerOperatorRequest  $request
     * @param  \App\Models\PartnerOperator  $partnerOperator
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePartnerOperatorRequest $request, PartnerOperator $partnerOperator)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PartnerOperator  $partnerOperator
     * @return \Illuminate\Http\Response
     */
    public function destroy(PartnerOperator $partnerOperator)
    {
        //
    }
}
