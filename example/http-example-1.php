<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 28/02/17
 * Time: 17:44
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../vendor/autoload.php";

use React\EventLoop\Factory;
use React\Http\Server as HttpServer;
use React\Socket\Server as SocketServer;
use RestifiedPHP\Server as Application;

$app = new Application(isset($argv[1]) ? $argv[1] : '0.0.0.0:1337');
$app->get("/",function ($request,$response) {
    $response->writeHead(200,["Content-Type"=>"application/json"]);
    $response->end(json_encode(["message"=>"Hello world"]));
});

set_exception_handler(function (Throwable $error) {
    error_log($error);
});
$app->run();