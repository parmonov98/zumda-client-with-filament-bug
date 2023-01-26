<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommonCategoryRequest;
use App\Http\Requests\UpdateCommonCategoryRequest;
use App\Models\CommonCategory;

class CommonCategoryController extends Controller
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
     * @param  \App\Http\Requests\StoreCommonCategoryRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCommonCategoryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CommonCategory  $commonCategory
     * @return \Illuminate\Http\Response
     */
    public function show(CommonCategory $commonCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CommonCategory  $commonCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(CommonCategory $commonCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCommonCategoryRequest  $request
     * @param  \App\Models\CommonCategory  $commonCategory
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCommonCategoryRequest $request, CommonCategory $commonCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CommonCategory  $commonCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommonCategory $commonCategory)
    {
        //
    }
}
