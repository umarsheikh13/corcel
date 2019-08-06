<?php

Route::get('{slug}', [
    'uses' => 'Ronin\Http\Controllers\RoutingController@init'
])->where('slug', '([A-Za-z0-9\-\/]+)?')->middleware('web');
