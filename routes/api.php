<?php

use Illuminate\Support\Facades\Route;

// routes/api.php
Route::get('/jobs', [App\Http\Controllers\Api\JobController::class, 'index']);
