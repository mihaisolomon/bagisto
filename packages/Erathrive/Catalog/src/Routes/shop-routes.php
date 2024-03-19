<?php

use Illuminate\Support\Facades\Route;
use Erathrive\Catalog\Http\Controllers\Shop\CatalogController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency'], 'prefix' => 'catalog'], function () {
    Route::get('', [CatalogController::class, 'index'])->name('shop.catalog.index');
});