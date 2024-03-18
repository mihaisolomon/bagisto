<?php

use Illuminate\Support\Facades\Route;
use Erathrive\EventsManager\Http\Controllers\Shop\EventsManagerController;

Route::group(['middleware' => ['web', 'theme', 'locale', 'currency'], 'prefix' => 'eventsmanager'], function () {
    Route::get('', [EventsManagerController::class, 'index'])->name('shop.eventsmanager.index');
});