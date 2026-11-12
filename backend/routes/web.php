<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Sistem Penilaian Ujikom RPL/PPLG API',
        'status' => 'running',
    ]);
});
