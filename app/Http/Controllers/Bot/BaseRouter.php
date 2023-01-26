<?php

namespace App\Http\Controllers\Bot;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class BaseRouter extends \App\Http\Controllers\Controller
{
    /**
     * Show the profile for the given user.
     *
     * @param  int  $id
     */
    public function __invoke(Request $request)
    {
//        $a = $this->call('GET', '/printer/report')->getContent();
//        dd($request->all());
//        return view('user.profile', ['user' => User::findOrFail($id)]);
    }
}
