<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebCrawlerController;

Route::middleware(['throttle:10,10'])->group(function () {
    Route::get('/', [WebCrawlerController::class, 'index']);
    Route::get('scrape', [WebCrawlerController::class, 'index']);
});
