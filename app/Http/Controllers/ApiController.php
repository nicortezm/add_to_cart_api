<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    
    //
    public function ObtenerProductos(Request $request){
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        $url = $baseUrl.'/products.json';
        $response = Http::withBasicAuth($login, $authToken)->get($url);
        return $response->json();
    }
    public function CrearProducto(Request $request){
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        $url = $baseUrl.'/products.json';
        $qty = $request['qty'];
        $body = '{
            "product": {
              "name": "Producto Sample",
              "price": 15990,
              "sku": "Borrable",
              "stock": '. $qty .',
              "stock_unlimited": false
            }
        }';
        $response = Http::withBasicAuth($login, $authToken)->withBody($body, 'application/json')->post($url);
        return $response->json();
    }

    public function AsignarImagenProducto(Request $request){
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        $product_id = $request['product_id'];
        $image_url = $request['image_url'];
        $url = $baseUrl."/products/{$product_id}/images.json";
        $body_image = '{
            "image": {
                "url": "' . $image_url . '"
                }
        }';
        $response = Http::withBasicAuth($login, $authToken)->withBody($body_image, 'application/json')->post($url);
        return $response->json();
    }

    public function CrearVariacionProducto(Request $request){
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        $mermelada_id = $request['mermelada_id'];
        $pasta_id = $request['pasta_id'];
        $product_id = $request['producto_id'];
        $url = $baseUrl."/products/{$product_id}/variants.json";

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
        $response = Http::withBasicAuth($login, $authToken)->withBody($body_variacion, 'application/json')->post($url);
        return $response->json();
    }

    public function ObtenerStock($id){
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
    
        $url = $baseUrl."/products/{$id}.json";
        $response = Http::withBasicAuth($login, $authToken)->get($url);
        return $response->json();
        // return $response->json()['product'];
    }

    public function DisminuirStock($p_id, Request $request)
    {
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        // Primero hay que obtener el stock actual de la variante generada
        $qty = $request['qty'];
        $url_obtener_stock = $baseUrl."/products/{$p_id}.json";
        $response_obtener_stock = Http::withBasicAuth($login, $authToken)->get($url_obtener_stock);
        $stock_actual = $response_obtener_stock->json()['product']['stock'];

        $stock_nuevo = $stock_actual - $qty;

        // Luego hacemos la resta del stock actual - la qty que se obtiene del body

        $body = '{
        "product": {
            "stock": '. $stock_nuevo .'
        }
        }';

        $url = $baseUrl."/products/{$p_id}.json";
        $response = Http::withBasicAuth($login, $authToken)->withBody($body, 'application/json')->put($url);
        return $response->json();
    }


    public function ObtenerProducto($name)
    {
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        $url = $baseUrl.'/products.json';
        $response = Http::withBasicAuth($login, $authToken)->get($url);
        // return $response->json();

        $products = $response->json();
        
        foreach ($products as $key) {

            if ($key["product"]["name"] == $name) {                
                return $key["product"]["id"];
            }
        }
        
        return;

    }




    public function HandleWebhook(Request $request)
    {
        
        $login = env('API_LOGIN');
        $authToken = env('API_AUTH_TOKEN');
        $baseUrl = env('API_ENDPOINT');
        // Obtener stock del producto 
        $order = $request->get(key: 'order');
        // var_dump($order["products"]);
        foreach ($order["products"] as $key) {
            if ($key["sku"] == "borrable") {
                $name = $key["name"];
                $qty = $key["qty"];
                $arraySplit_1 = explode("(",$name); 
                $arraySplit_2 = explode(")",$arraySplit_1[1]);
                $arraySplit_3 = explode(", ",$arraySplit_2[0]);
                $mermelada_name = explode(": ",$arraySplit_3[0])[1];
                $mermelada_id = $this->ObtenerProducto($mermelada_name);

                $pasta_name = explode(": ",$arraySplit_3[1])[1];
                $pasta_id = $this->ObtenerProducto($pasta_name);
                //  Disminuir stock Mermelada
                $url_mermelada = $baseUrl."/products/{$mermelada_id}.json";
                $response_mermelada = Http::withBasicAuth($login, $authToken)->get($url_mermelada);
                $stock_actual_mermelada = $response_mermelada->json()['product']['stock'];

                $stock_nuevo_mermelada = $stock_actual_mermelada - $qty;
                $body_mermelada = '{
                "product": {
                    "stock": '. $stock_nuevo_mermelada .'
                }
                }';

                $url = $baseUrl."/products/{$mermelada_id}.json";
                $response_mermelada = Http::withBasicAuth($login, $authToken)->withBody($body_mermelada, 'application/json')->put($url);
                // return $response_mermelada->json();
                // Disminuir stock Pasta

                $url_pasta = $baseUrl."/products/{$pasta_id}.json";
                $response_pasta = Http::withBasicAuth($login, $authToken)->get($url_pasta);
                $stock_actual_pasta = $response_pasta->json()['product']['stock'];
                $stock_nuevo_pasta = $stock_actual_pasta - $qty;
                $body_pasta = '{
                    "product": {
                        "stock": '. $stock_nuevo_pasta .'
                    }
                    }';
                $url = $baseUrl."/products/{$pasta_id}.json";
                $response_pasta = Http::withBasicAuth($login, $authToken)->withBody($body_pasta, 'application/json')->put($url);
                return 'ok';
            }
            else {
                return;
            }
        }
        
        

    }
    
}
