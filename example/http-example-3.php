<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 16:22
 */

require "../vendor/autoload.php";

use RestifiedPHP\Server as Application;
use RestifiedPHP\HttpRequest as HttpRequest;


$app = new Application(isset($argv[1]) ? $argv[1] : '0.0.0.0:1337');
$app->get("/",function (HttpRequest $request,$response) {
    $name = $request->getQueryParam('name');
    $name = $name??'';
    if( $name === '' ) {
        $name = 'World';
    }
    $message = "Hello ".$name;
    $response->writeHead(200,["Content-Type"=>"application/json"]);
    $response->end(json_encode(["message"=>$message]));
});

$router1 = \RestifiedPHP\Router::genericRouter();
$router1->get("/bar",function (HttpRequest $request, $response) {
    $response->writeHead(200,["Content-Type"=>"application/json"]);
    $response->end(json_encode(["message"=>"You have reached Foo Bar!!"]));
});
$router1->post("/bar",function (HttpRequest $request,$response){
    $name = $request->getQueryParam('name');
    $name = $name??'';
    if( $name === '' ) {
        $requestJSON = $request->getAttribute(\RestifiedPHP\Middleware\JSONRequestParser::ATTRIBUTE_NAME);
        $name = $requestJSON["name"]??'';
    }
    if($name === '') {
        $name = "World!";
    }
    $message = "Foo Bar says - Hello ".$name;
    $response->writeHead(200,["Content-Type"=>"application/json"]);
    $response->end(json_encode(["message"=>$message]));
});

$app->map("/foo",$router1);

set_exception_handler(function (Throwable $error) {
    print_r("Caught unhandled exception");
    print_r($error);
});

$app->run();
