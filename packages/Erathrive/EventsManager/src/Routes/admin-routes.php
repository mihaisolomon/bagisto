<?php

use Erathrive\EventsManager\Http\Controllers\Admin\EventsManagerController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['web', 'admin'], 'prefix' => 'admin/eventsmanager'], function () {
    Route::controller(EventsManagerController::class)->group(function () {
        Route::get('', 'index')->name('admin.eventsmanager.index');
    });
});
