<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->guard('landlord')->check()) {
        return redirect('/landlord');
    }
    return redirect('/landlord/login');
});
