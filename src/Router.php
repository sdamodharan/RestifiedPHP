<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 27/02/17
 * Time: 21:27
 */

namespace RestifiedPHP;

use Evenement\EventEmitter;
use React\Http\Request;
use React\Http\Response;

class Router
{

    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';
    public const OPTIONS = 'OPTIONS';
    public const HEAD = 'HEAD';

    private $handlerMap;
    private $pathPrefix;
    private $eventEmitter;

    public function __construct(EventEmitter $eventEmitter,$pathPrefix = '/')
    {
        $this->handlerMap = array(
            Router::GET => array(),
            Router::POST => array(),
            Router::PUT => array(),
            Router::PATCH => array(),
            Router::DELETE => array(),
            Router::OPTIONS => array(),
            Router::HEAD => array()
        );
        $this->pathPrefix = $pathPrefix;
        $this->eventEmitter = $eventEmitter;
    }

    public function handleRequest(Request $request, Response $response,array $path) : void {
        $method = $request->getMethod();
        $currentArray = $this->handlerMap[$method];
        $handler = $this->lookupRequestHandler($currentArray,$path);
        if($handler === null) {
            $this->eventEmitter->emit('notfound',[$request,$response]);
        } else if($handler["isRouter"]) {
            $router = $handler["router"];
            $depth = $handler["depth"];
            $path = array_slice($path,$depth);
            $path[0] = "/";
            $router->handleRequest($request,$response,$path);
        } else {
            $requestHandler = $handler["handler"];
            $requestHandler($request,$response);
        }
    }

    private function lookupRequestHandler(array $currentArray, array $path) : array{
        for ($i = 0; $i < count($path); $i ++) {
            if(isset($currentArray[$path[$i]])){
                if(is_array($currentArray[$path[$i]])){
                    $currentArray = $currentArray[$path[$i]];
                    continue;
                } else if($currentArray[$path[$i]] instanceof Router) {
                    return array("isRouter" => true,
                        "router" => $currentArray[$path[$i]],
                        "depth" => $i);
                } else if(($currentArray[$path[$i]] instanceof  \Closure) || is_callable($currentArray[$path[$i]])) {
                    return array("isRouter" => false,
                        "handler" => $currentArray[$path[$i]],
                        "depth" => $i);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
        return null;
    }

    private function registerHandler(string $method,string $mountPath,callable $handler) : void {
        if(!Util::isValidRequestHandlerPath($mountPath)) {
            throw new \Exception("Invalid path provided for requestHandler $mountPath");
        }
        $path = Util::splitIncomingURLToPathComponents($mountPath);
        $currentArray = &$this->handlerMap[$method];
        for ($i = 0; $i < count($path)-1; $i ++) {
            if(!isset($currentArray[$path[$i]])) {
                $currentArray[$path[$i]] = array();
            }
            $currentArray = &$currentArray[$path[$i]];
        }
        $currentArray[$path[$i]] = $handler;
    }

    private function registerRouter(string $method, string $mountPath, Router $router) : void {
        if(!Util::isValidRequestHandlerPath($mountPath)) {
            throw new \Exception("Invalid path provided for requestHandler $mountPath");
        }
        $path = Util::splitIncomingURLToPathComponents($mountPath);
        $currentArray = $this->handlerMap[$method];
        for ($i = 0; $i < count($path)-1; $i ++) {
            if(!isset($currentArray[$path[$i]])) {
                $currentArray[$path[$i]] = array();
            }
            $currentArray = $currentArray[$path[$i]];
        }
        $currentArray[count($path)-1] = $router;
    }

    public function get(string $path, callable $handler) : void {
        $this->registerHandler(Router::GET,$path,$handler);
    }

    public function post(string $path, callable $handler) : void {
        $this->registerHandler(Router::POST,$path,$handler);
    }

    public function put(string $path, callable $handler) : void {
        $this->registerHandler(Router::PUT,$path,$handler);
    }

    public function patch(string $path, callable $handler) : void {
        $this->registerHandler(Router::PATCH,$path,$handler);
    }

    public function delete(string $path, callable $handler) : void {
        $this->registerHandler(Router::DELETE,$path,$handler);
    }

    public function options(string $path, callable $handler) : void {
        $this->registerHandler(Router::OPTIONS,$path,$handler);
    }

    public function head(string $path, callable $handler) : void {
        $this->registerHandler(Router::HEAD,$path,$handler);
    }

    public function map(string $path,Router $router) : void {
        $this->registerRouter(Router::GET,$path,$router);
        $this->registerRouter(Router::POST,$path,$router);
        $this->registerRouter(Router::PUT,$path,$router);
        $this->registerRouter(Router::PATCH,$path,$router);
        $this->registerRouter(Router::OPTIONS,$path,$router);
        $this->registerRouter(Router::HEAD,$path,$router);
        $this->registerRouter(Router::DELETE,$path,$router);
    }

}