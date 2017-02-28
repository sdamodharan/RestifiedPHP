<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 27/02/17
 * Time: 21:01
 */

namespace RestifiedPHP;


use React\Http\Request;
use React\Http\Response;
use RestifiedPHP\Util as Util;

class Server
{
    private $server;
    private $defaultRouter;

    function __construct(\React\Http\Server $server)
    {
        $this->server = $server;
        $this->defaultRouter = new Router($server,"/");
        $this->init();
    }

    private function init() : void{
        $router = $this->getDefaultRouter();
        $eventEmitter = $this->server;
        $requestHandler = function (Request $request, Response $response) use($router,$eventEmitter) {
            try {
                $path = $request->getPath();
//                error_log("Incoming {$request->getMethod()} request: {$path}");
                $router->handleRequest($request, $response, Util::splitIncomingURLToPathComponents($path) );
            }catch (\Throwable $t) {
                error_log($t->__toString());
                $eventEmitter->emit('error',[$request,$response,$t]);
            }
        };
        $this->server->on('request',$requestHandler);
        $this->setErrorHandler($this->getDefaultErrorHandler());
        $this->setNotFoundHandler($this->getDefaultNotFoundHandler());
        return;
    }

    public function setErrorHandler(callable $errorHandler) : void {
        $this->server->on('error',$errorHandler);
    }

    public function setNotFoundHandler(callable $notFoundHandler) : void {
        $this->server->on('notfound',$notFoundHandler);
    }

    private function getDefaultRouter(): Router {
        return $this->defaultRouter;
    }

    private function getDefaultErrorHandler() : callable {
        return function (Request $request, Response $response, \Throwable $throwable) {
            error_log($throwable);
            $error_message = [
              "message" => $throwable->getMessage(),
                "code" => $throwable->getCode(),
                "file" => $throwable->getFile(),
                "trace" => $throwable->getTraceAsString()
            ];
            $response->writeHead(500,["Content-Type"=>"application/json"]);
            $response->end(json_encode($error_message));
        };
    }

    private function getDefaultNotFoundHandler() : callable {
        return function (Request $request,Response $response) {
            $response->writeHead(404,["Content-Type"=>"application/json"]);
            $response->end(json_encode(["message"=>"The requested endpoint is not found"]));
        };
    }

    public function get(string $path, callable $handler) : void {
       $this->getDefaultRouter()->get($path,$handler);
    }

    public function post(string $path, callable $handler) : void {
       $this->getDefaultRouter()->post($path,$handler);
    }

    public function put(string $path, callable $handler) : void {
       $this->getDefaultRouter()->put($path,$handler);
    }

    public function patch(string $path, callable $handler) : void {
       $this->getDefaultRouter()->put($path,$handler);
    }

    public function delete(string $path, callable $handler) : void {
       $this->getDefaultRouter()->delete($path,$handler);
    }

    public function options(string $path, callable $handler) : void {
       $this->getDefaultRouter()->options($path,$handler);
    }

    public function head(string $path, callable $handler) : void {
       $this->getDefaultRouter()->head($path,$handler);
    }

    public function map(string $path,Router $router) : void {
       $this->getDefaultRouter()->map($path,$router);
    }
}