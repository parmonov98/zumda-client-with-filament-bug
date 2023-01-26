<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommonCategoryTranslationRequest;
use App\Http\Requests\UpdateCommonCategoryTranslationRequest;
use App\Models\CommonCategoryTranslation;

class CommonCategoryTranslationController extends Controller
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
     * @param  \App\Http\Requests\StoreCommonCategoryTranslationRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCommonCategoryTranslationRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CommonCategoryTranslation  $commonCategoryTranslation
     * @return \Illuminate\Http\Response
     */
    public function show(CommonCategoryTranslation $commonCategoryTranslation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CommonCategoryTranslation  $commonCategoryTranslation
     * @return \Illuminate\Http\Response
     */
    public function edit(CommonCategoryTranslation $commonCategoryTranslation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCommonCategoryTranslationRequest  $request
     * @param  \App\Models\CommonCategoryTranslation  $commonCategoryTranslation
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCommonCategoryTranslationRequest $request, CommonCategoryTranslation $commonCategoryTranslation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CommonCategoryTranslation  $commonCategoryTranslation
     * @return \Illuminate\Http\Response
     */
    public function destroy(CommonCategoryTranslation $commonCategoryTranslation)
    {
        //
    }
}
