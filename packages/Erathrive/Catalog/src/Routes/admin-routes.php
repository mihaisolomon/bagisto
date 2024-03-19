<?php

use Illuminate\Support\Facades\Route;
use Erathrive\Catalog\Http\Controllers\Admin\CatalogController;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/catalog'], function () {
    Route::controller(CatalogController::class)->group(function () {
        Route::get('', 'index')->name('admin.catalog.index');
    });
});