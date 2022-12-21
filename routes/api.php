<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\ApiController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/get_products', [ApiController::class,'ObtenerProductos']);

Route::post('/create_product', [ApiController::class,'CrearProducto']);

Route::put('/disminuir_stock/{p_id}',[ApiController::class,'DisminuirStockProducto'] );

Route::post('/callback_webhook', [ApiController::class,'HandleWebhook']);

Route::delete('/productos/{id}', [ApiController::class,'EliminarProducto']);

Route::get('/productos/categoria/{id}', [ApiController::class,'ProductosByCategoria']);
