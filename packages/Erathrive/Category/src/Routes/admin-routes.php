<?php

use Illuminate\Support\Facades\Route;
use Erathrive\Category\Http\Controllers\Admin\CategoryController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/category'], function () {
    Route::controller(CategoryController::class)->group(function () {
        Route::get('', 'index')->name('admin.category.index');
    });
});