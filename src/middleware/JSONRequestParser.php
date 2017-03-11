<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 18:55
 */

namespace RestifiedPHP\Middleware;


use React\Http\Response;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\BufferedSink;
use RestifiedPHP\HttpRequest;
use RestifiedPHP\Middleware;

class JSONRequestParser implements Middleware
{
    private const CONTENT_TYPE_HEADER = "content-type";
    private const JSON_CONTENT_TYPE = "application/json";
    public const ATTRIBUTE_NAME = "jsonRequest";

    function process(HttpRequest $request, Response $response, callable $next): void
    {
        $content_type = $request->getHeader(JSONRequestParser::CONTENT_TYPE_HEADER)??'';
        if ($content_type === JSONRequestParser::JSON_CONTENT_TYPE) {
            $requestBody = $request->getAttribute('content');
            $json_request_body = json_decode($requestBody,true);
            if ($json_request_body == null) {
                // Seems like an invalid JSON content
                $next(new \Exception("Invalid JSON content in request body"));
            } else {
                $request->setAttribute(JSONRequestParser::ATTRIBUTE_NAME, $json_request_body);
                $next();
            }
        } else {
            $request->setAttribute(JSONRequestParser::ATTRIBUTE_NAME, array());
            $next();
        }
    }
}