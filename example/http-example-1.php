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

$loop = Factory::create();
$socket = new SocketServer(isset($argv[1]) ? $argv[1] : '0.0.0.0:1337', $loop);

$httpServer = new HttpServer($socket);
$app = new Application($httpServer);
$app->get("/",function ($request,$response) {
    $response->writeHead(200,["Content-Type"=>"application/json"]);
    $response->end(json_encode(["message"=>"Hello world"]));
});

echo 'Listening on http://' . $socket->getAddress() . PHP_EOL;

set_exception_handler(function (Throwable $error) {
    error_log($error);
});
$loop->run();