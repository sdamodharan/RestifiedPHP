<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 16:22
 */


error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../vendor/autoload.php";

use RestifiedPHP\HttpRequest as HttpRequest;
use RestifiedPHP\Server as Application;

$app = new Application(isset($argv[1]) ? $argv[1] : '0.0.0.0:1337');

$app->get("/", function (HttpRequest $request, $response) {
    $name = $request->getQueryParam('name')??'';
    if ($name === '') {
        $name = 'World';
    }
    $message = "Hello " . $name;
    $response->writeHead(200, ["Content-Type" => "application/json"]);
    $response->end(json_encode(["message" => $message]));
});

set_exception_handler(function (Throwable $error) {
    error_log($error);
});
$app->run();