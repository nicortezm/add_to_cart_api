<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
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

Route::get('/get_products', function () {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $url = env('API_ENDPOINT').'/products.json';
    $response = Http::withBasicAuth($login, $authtoken)->get($url);
    return $response;
});

Route::post('/create_product', function () {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $base_url = env('API_ENDPOINT');
    $url = $base_url.'/products.json';
    $body_crear_producto = '{
        "product": {
          "name": "Producto Sample",
          "price": 15990,
          "sku": "Borrable",
          "stock": 3,
          "stock_unlimited": false
        }
    }';
    $response_crear_producto = Http::withBasicAuth($login, $authtoken)->withBody($body_crear_producto, 'application/json')->post($url);
    
    return $response_crear_producto->json();
});

Route::post('/create_product_image', function (Request $request) {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $base_url = env('API_ENDPOINT');
    $product_id = $request['product_id'];
    $image_url = $request['image_url'];
    $url = $base_url."/products/{$product_id}/images.json";
    $body_image = '{
        "image": {
            "url": "' . $image_url . '"
            }
    }';
    $response = Http::withBasicAuth($login, $authtoken)->withBody($body_image, 'application/json')->post($url);
    return $response->json();
});

Route::post('/create_product_variation', function (Request $request) {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $base_url = env('API_ENDPOINT');
    $mermelada_id = $request['mermelada_id'];
    $pasta_id = $request['pasta_id'];
    $product_id = $request['producto_id'];
    $url = $base_url."/products/{$product_id}/variants.json";

    $body_variacion = '{
      "variant": {
        "sku": "borrable",
        "stock_unlimited": false,
        "options": [
          {
            "name": "Mermelada",
            "value": "'. $mermelada_id .'"
          },
          {
            "name": "Pasta",
            "value": "'. $pasta_id .'"
          }
        ]
      }
    }';
    $response = Http::withBasicAuth($login, $authtoken)->withBody($body_variacion, 'application/json')->post($url);
    return $response->json();
});

Route::get('/obtener_stock/{id}', function ($id) {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $base_url = env('API_ENDPOINT');
 
    $url = $base_url."/products/{$id}.json";
    $response = Http::withBasicAuth($login, $authtoken)->get($url);
    // return $response->json()['product']['variants'][0]['stock'];
    return $response->json()['product'];
});

Route::put('/disminuir_stock/{p_id}/{v_id}', function ($p_id,$v_id, Request $request) {
    $login = env('API_LOGIN');
    $authtoken = env('API_AUTH_TOKEN');
    $base_url = env('API_ENDPOINT');
    // Primero hay que obtener el stock actual de la variante generada
    $qty = $request['qty'];
    $url_obtener_stock = $base_url."/products/{$p_id}.json";
    $response_obtener_stock = Http::withBasicAuth($login, $authtoken)->get($url_obtener_stock);
    $stock_actual = $response_obtener_stock->json()['product']['variants'][0]['stock'];
    $stock_nuevo = $stock_actual - $qty;

    // Luego hacemos la resta del stock actual - la qty que se obtiene del body

    $body_cambiar_stock = '{
      "variant": {
        "stock": '. $stock_nuevo .'
      }
    }';

    $url = $base_url."/products/{$p_id}/variants/{$v_id}.json";
    $response = Http::withBasicAuth($login, $authtoken)->withBody($body_cambiar_stock, 'application/json')->put($url);
    return $response->json();
});