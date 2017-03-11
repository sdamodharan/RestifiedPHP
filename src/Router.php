<?php
declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 27/02/17
 * Time: 21:27
 */

namespace RestifiedPHP;

use Evenement\EventEmitter;
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
    private $upstream;
    private $middleWares;

    public function __construct(?EventEmitter $eventEmitter, $pathPrefix = '/')
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
        $this->upstream = $eventEmitter;
        $this->middleWares = array();
    }

    public static function genericRouter(): Router
    {
        return new Router(null);
    }

    public function useMiddleWare(Middleware $middleware)
    {
        array_push($this->middleWares, $middleware);
    }

    private function processMiddleWareChain(HttpRequest $request, Response $response)
    {
        if (count($this->middleWares) > 0) {
            $request->setAttribute('nextMWIndex', 1);
            $this->_invokeMiddleWare($request, $response, 0);
        } else {
            $request->emit('completedMiddlewareProcessing', array());
        }
    }

    private function _invokeMiddleWare(HttpRequest $request, Response $response, int $index)
    {
        $self = $this;
        $next = function (\Throwable $throwable = null) use ($self, $request, $response) {
            if ($throwable != null) {
                $self->upstream->emit('error', [$request, $response, $throwable]);
                return;
            } else {
                $mwIndex = $request->getAttribute('nextMWIndex');
                if (count($self->middleWares) > $mwIndex-1) {
                    $GLOBALS['loop']->futureTick(function () use ($request) {
                        $request->emit('completedMiddlewareProcessing', array());
                    });
                    return;
                } else {
                    $request->setAttribute('nextMWIndex', $mwIndex + 1);
                    $GLOBALS['loop']->futureTick(function () use ($self, $request, $response, $mwIndex) {
                        $self->_invokeMiddleWare($request, $response, $mwIndex);
                    });
                }
            }
        };
        /** @var Middleware $current_middleware */
        $current_middleware = &$this->middleWares[$index];
        $current_middleware->process($request, $response, $next);
    }

    public function handleRequest(HttpRequest $request, Response $response, array $path): void
    {
        $self = $this;
        $request->once('completedMiddlewareProcessing', function () use ($request, $response, $path, $self) {
            $request->removeAllListeners('errorProcessingMiddleware');
            $self->lookupAndDelegate($request, $response, $path);
        });
        $request->once('errorProcessingMiddleware', function ($error) use ($request, $response, $path, $self) {
            $throwable = null;
            if ($error instanceof \Throwable) {
                $throwable = $error;
            } else {
                $msg = "";
                if (is_array($error)) {
                    $msg = json_encode($error, 0, 4);
                } else {
                    $msg = (string)$error;
                }
                $throwable = new \Exception($msg);
            }
            $self->upstream->emit('error', [$request, $response, $throwable]);
        });
        $this->processMiddleWareChain($request, $response);
    }

    private function lookupAndDelegate(HttpRequest $request, Response $response, array $path): void
    {
        $method = $request->getMethod();
        $currentArray = $this->handlerMap[$method];
        $handler = $this->lookupRequestHandler($currentArray, $path);
        if ($handler["notfound"] ?? false) {
            $this->upstream->emit('notfound', [$request, $response]);
        } else if ($handler["isRouter"]) {
            $router = $handler["router"];
            $depth = $handler["depth"];
            $path = array_slice($path, $depth);
            $router->handleRequest($request, $response, $path);
        } else {
            $requestHandler = $handler["handler"];
            $requestHandler($request, $response);
        }
    }

    private function lookupRequestHandler(array $currentArray, array $path): ?array
    {
        for ($i = 0; $i < count($path); $i++) {
            if (isset($currentArray[$path[$i]])) {
                if (is_array($currentArray[$path[$i]])) {
                    $currentArray = $currentArray[$path[$i]];
                    continue;
                } else if ($currentArray[$path[$i]] instanceof Router) {
                    return array("isRouter" => true,
                        "router" => $currentArray[$path[$i]],
                        "depth" => $i + 1);
                } else if (($currentArray[$path[$i]] instanceof \Closure) || is_callable($currentArray[$path[$i]])) {
                    return array("isRouter" => false,
                        "handler" => $currentArray[$path[$i]],
                        "depth" => $i + 1);
                } else {
                    return ["notfound" => true];
                }
            } else {
                return ["notfound" => true];
            }
        }
        return ["notfound" => true];
    }

    private function registerHandler(string $method, string $mountPath, callable $handler): void
    {
        $this->_setHandler($method, $mountPath, $handler);
    }

    private function _setHandler(string $method, string $mountPath, $handlerOrRouter): void
    {
        if (!Util::isValidRequestHandlerPath($mountPath)) {
            throw new \Exception("Invalid path provided for requestHandler $mountPath");
        }
        $path = Util::splitIncomingURLToPathComponents($mountPath);
        $currentArray = &$this->handlerMap[$method];
        for ($i = 0; $i < count($path) - 1; $i++) {
            if (!isset($currentArray[$path[$i]])) {
                $currentArray[$path[$i]] = array();
            }
            $currentArray = &$currentArray[$path[$i]];
        }
        $currentArray[$path[$i]] = $handlerOrRouter;
    }

    private function registerRouter(string $method, string $mountPath, Router $router): void
    {
        $this->_setHandler($method, $mountPath, $router);
    }

    public function get(string $path, callable $handler): void
    {
        $this->registerHandler(Router::GET, $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->registerHandler(Router::POST, $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->registerHandler(Router::PUT, $path, $handler);
    }

    public function patch(string $path, callable $handler): void
    {
        $this->registerHandler(Router::PATCH, $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->registerHandler(Router::DELETE, $path, $handler);
    }

    public function options(string $path, callable $handler): void
    {
        $this->registerHandler(Router::OPTIONS, $path, $handler);
    }

    public function head(string $path, callable $handler): void
    {
        $this->registerHandler(Router::HEAD, $path, $handler);
    }

    public function map(string $path, Router $router): void
    {
        $this->registerRouter(Router::GET, $path, $router);
        $this->registerRouter(Router::POST, $path, $router);
        $this->registerRouter(Router::PUT, $path, $router);
        $this->registerRouter(Router::PATCH, $path, $router);
        $this->registerRouter(Router::OPTIONS, $path, $router);
        $this->registerRouter(Router::HEAD, $path, $router);
        $this->registerRouter(Router::DELETE, $path, $router);
        $router->upstream = $this->upstream;
    }

}