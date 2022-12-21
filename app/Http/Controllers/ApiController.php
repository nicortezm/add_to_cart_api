<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ApiController extends Controller
{
    //
    private $baseUrl = null;
    private $login = null;
    private $authToken = null;

    function __construct() {
      // Asignar valores env desde el constructor
      $this->login = env('API_LOGIN');
      $this->authToken = env('API_AUTH_TOKEN');
      $this->baseUrl = env('API_ENDPOINT');
  }

    /*
    Obtener todos los productos, Limitrate = 50 x default, para obtenerlos todos hay que hacer modificaciones
   
    */
    public function ObtenerProductos(Request $request){
        $url = $this->baseUrl.'/products.json';
        $response = Http::withBasicAuth($this->login, $this->authToken)->get($url);
        return $response->json();
    }
    
    public function CrearProducto(Request $request){

      $url = $this->baseUrl.'/products.json';
      $qty = $request['qty'];
      $price = $request['price'];
      $name = $request['name'];
      $body = '{
          "product": {
            "name": "'. $name .'",
            "price": '. $price .',
            "sku": "Arma tu pack",
            "stock_unlimited": false
          }
      }';
      $response = Http::withBasicAuth($this->login, $this->authToken)->withBody($body, 'application/json')->post($url);
      // Asignar imagen al producto creado
      $product_id = $response->json()['product']['id'];
      $image_url = $request['image_url'];
      $url_asignar_imagen = $this->baseUrl."/products/{$product_id}/images.json";
      $body_image = '{
        "image": {
            "url": "' . $image_url . '"
            }
      }';
      $response_img = Http::withBasicAuth($this->login, $this->authToken)->withBody($body_image, 'application/json')->post($url_asignar_imagen);
      // Crear variación con los productos seleccionados
      $variantes = $request['products'];
      $body_options = "";
      $product_list = "";
      foreach ($variantes as $key => $value) {
        if ($key == 0) {
          $body_options = $body_options.'{"name": "Producto '. $key+1 .'","value": "'. $value['name'] .'"}';
          $product_list = "Producto " . $key+1 . ": ". $value['id'];
          
        }
        else {
          $body_options = $body_options.',{"name": "Producto '. $key+1 .'","value": "'. $value['name'] .'"}';
          $product_list = $product_list.", Producto " . $key+1 . ": ". $value['id'];
        }
      }
      $url_variant = $this->baseUrl."/products/{$product_id}/variants.json";

      $body_variant = '{
      "variant": {
          "sku": "Arma tu Pack",
          "stock_unlimited": false,
          "stock": '. $qty .',
          "options": ['. $body_options .']
      }
      }';

      $response = Http::withBasicAuth($this->login, $this->authToken)->withBody($body_variant, 'application/json')->post($url_variant);
      // Crear Custom Field
      $url_custom_field = $this->baseUrl."/products/{$product_id}/fields.json";
      $body_cf = '{
        "field": {
        "id": 48871,
        "value": "'. $product_list .'"
        }
      }';
      $response_cf = Http::withBasicAuth($this->login, $this->authToken)->withBody($body_cf, 'application/json')->post($url_custom_field);
      return $response_cf->json();

    }

    public function DisminuirStockProducto($p_id,$qty){

        // Primero hay que obtener el stock actual de la variante generada
        $url_obtener_stock = $this->baseUrl."/products/{$p_id}.json";
        $response_obtener_stock = Http::withBasicAuth($this->login, $this->authToken)->get($url_obtener_stock);
        if (empty($response_obtener_stock->json()['product']['variants'])) {
          $stock_actual = $response_obtener_stock->json()['product']['stock'];
          $stock_nuevo = $stock_actual - $qty;
          // Luego hacemos la resta del stock actual - la qty que se obtiene del body
          $body = '{
          "product": {
            "stock": '. $stock_nuevo .'
          }
          }';
          $url = $this->baseUrl."/products/{$p_id}.json";
          $response = Http::withBasicAuth($this->login, $this->authToken)->withBody($body, 'application/json')->put($url);

          return $response->json();
        }
        else {
          $stock_actual = $response_obtener_stock->json()['product']['variants'][2]['stock'];
          $stock_nuevo = $stock_actual - $qty;
          $body = '{
            "variant": {
              "stock": '. $stock_nuevo .'
            }
          }';
          $variant_id = $response_obtener_stock->json()['product']['variants'][2]['id'];

          $url = $this->baseUrl."/products/{$p_id}/variants/{$variant_id}.json";
          $response = Http::withBasicAuth($this->login, $this->authToken)->withBody($body, 'application/json')->put($url);
          return $response->json();
        }
    }

    public function HandleWebhook(Request $request){        
        // Obtener stock del producto 
        $order = $request->get(key: 'order');
        $order_products = $order["products"];
        $array_product_qty = array();
        foreach ($order_products as $var_product) {
          if ($var_product['sku']== "Arma tu Pack") {
            $p_id = $var_product['id'];

            $url = $this->baseUrl."/products/{$p_id}.json";
            $response = Http::withBasicAuth($this->login, $this->authToken)->get($url);
            $string_products = $response->json()['product']['fields'][0]['value'];
            $aux_products = preg_split('([A-Za-z0-9]+( [A-Za-z0-9]+)+: )', $string_products);
            $i = 0;
            foreach ($aux_products as $temp_product) {
              if ($i == 0) {
                $i++;
                continue;
              }
              array_push($array_product_qty, [intval(trim($temp_product,", ")),$var_product['qty']]);
              $i++;
            }
          }
        }
        if (empty($array_product_qty)) {
          return null;
        }
        foreach ($array_product_qty as $key) {
          $this->DisminuirStockProducto($key[0],$key[1]);
        }
        return 'OK';
    }

    public function EliminarProducto($id){
      $url_delete =$this->baseUrl."/products/{$id}.json";
      $response_delete = Http::withBasicAuth($this->login, $this->authToken)->delete($url_delete);
      return $response_delete->json();
    }

    public function ProductosByCategoria($id){
      $url = $this->baseUrl."/products/category/{$id}.json";
      $response = Http::withBasicAuth($this->login, $this->authToken)->get($url);
      return $response->json();
    }
    
}