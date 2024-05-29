<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UniversityController;
use App\Http\Controllers\StudentController;


Route::prefix('universities')->group(function () {

    
    Route::get('/', [UniversityController::class, 'index']);   
    Route::get('/{id}', [UniversityController::class, 'show']);  
    
    Route::post('/', [UniversityController::class, 'store']);

    Route::put('/{id}', [UniversityController::class, 'update']);
    
    Route::delete('/{id}', [UniversityController::class, 'destroy']);
});

Route::prefix('students')->group(function () {

    Route::get('/', [StudentController::class, 'index']);    

    Route::post('/', [StudentController::class, 'store']);

    Route::get('/{id}', [StudentController::class, 'show']); 
    
    Route::put('/{id}', [StudentController::class, 'update']);

    Route::delete('/{id}', [StudentController::class, 'destroy']);
});