<?php

use App\Http\Controllers\SentenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [SentenceController::class, 'index'])->name('home');
Route::get('/generate', [SentenceController::class, 'generate'])->name('generate');
Route::post('/like', [SentenceController::class, 'like'])->name('like');
Route::post('/check-like', [SentenceController::class, 'checkLike'])->name('check.like');
Route::post('/generate-share-link', [SentenceController::class, 'generateShareLink'])->name('generate.share.link');
