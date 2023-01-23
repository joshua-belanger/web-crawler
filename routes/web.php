<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebCrawlerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('scrape', [WebCrawlerController::class, 'index']);