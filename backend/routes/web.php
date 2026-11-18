<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Marhas Ujikom API',
        'status' => 'running',
    ]);
});
