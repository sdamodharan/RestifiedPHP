<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 11/03/17
 * Time: 20:38
 */

namespace RestifiedPHP\Middleware;


use Evenement\EventEmitter;
use React\Http\Request;
use React\Http\Response;
use React\Promise\Promise;
use RestifiedPHP\HttpRequest;
use RestifiedPHP\impl\HttpRequestImpl;
use RestifiedPHP\Middleware;

class BodyHandler implements Middleware
{

    public function process(HttpRequest $request, Response $response,callable $next) : void
    {
        $content_length = (int)$request->getHeader("Content-Length")??0;
        if($content_length==0) {
            $request->setAttribute('content','');
            $next();
        } else {
            $_request = (object) $request;
            /** @var Request $reqObject */
            $reqObject = $_request->getRequest();
            $reqObject->on('data',function ($data) use ($request,$next) {
                $request->setAttribute('content',$data);
                $next(null);
            });
            $reqObject->on('error',function ($t) use($next) {
                $next($t);
            });
        }
    }
}