<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZohoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth:sanctum', 'verified'])->get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/form', [ZohoController::class, 'form'])->name('form');;

Route::get('/records', function () {
    return view('records');
})->name('records');

Route::post('/connection', [ZohoController::class, 'connection'])->name('connection');

// Route::get('/deals', [ZohoController::class, 'getDeals']);
// Route::get('/tasks', [ZohoController::class, 'getTasks']);
// Route::get('/deal-creation', [ZohoController::class, 'createDeal']);
// Route::get('/task-creation', [ZohoController::class, 'createTask']);

Route::post('/records', [ZohoController::class, 'createRecords'])->name('create-records');


