<?php

use App\Http\Controllers\ContactController;

Route::get('/', [ContactController::class, 'index'])->name('contact.index');
