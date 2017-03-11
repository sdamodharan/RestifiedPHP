<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 19:07
 */

namespace RestifiedPHP\impl;


use Evenement\EventEmitter;
use React\Http\Request;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use RestifiedPHP\HttpRequest;

class HttpRequestImpl extends EventEmitter implements HttpRequest
{
    private $attributes;
    private $_request;
    private $queryParams;
    public function __construct(Request $request)
    {
        $this->_request = $request;
        $this->attributes = array();
        $this->queryParams = $this->_request->getQueryParams();
    }

    public function getRemoteIP(): string
    {
        return $this->_request->remoteAddress;
    }

    public function getMethod(): string
    {
        return $this->_request->getMethod();
    }

    public function getRequestBodyRaw(): ReadableStreamInterface
    {
        return $this->_request->getRequestBodyRaw();
    }

    public function getAttribute(string $key)
    {
        return $this->attributes[$key]??'';
    }

    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getPath(): string
    {
       return $this->_request->getPath();
    }

    public function getQueryParam(string $key): ?string
    {
        if(array_key_exists($key,$this->queryParams)) {
            return $this->queryParams[$key];
        }
        return null;
    }

    public function getHeader(string $name): ?string
    {
        $headers = $this->_request->getHeader($name);
        $valueCount = count($headers);
        if($valueCount == 0){
            $headers = null;
        } else {
            $headers = $headers[$valueCount-1];
        }
        return $headers;
    }

    public function getHeaderValues(string $name): ?array
    {
        $headers = $this->_request->getHeader($name);
        if(count($headers)==0){
            $headers = null;
        }
        return $headers;
    }

    public function pipeRequestBody(WritableStreamInterface $dest, array $options = array()): WritableStreamInterface
    {
        return $this->_request->pipe($dest,$options);
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->_request;
    }
}