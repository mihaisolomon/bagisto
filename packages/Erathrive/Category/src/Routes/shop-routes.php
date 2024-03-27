<?php

use Illuminate\Support\Facades\Route;
use Erathrive\Category\Http\Controllers\Shop\CategoryController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency'], 'prefix' => 'category'], function () {
    Route::get('', [CategoryController::class, 'index'])->name('shop.category.index');
});