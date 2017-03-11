<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 18:57
 */

namespace RestifiedPHP;


use Evenement\EventEmitterInterface;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;

interface HttpRequest extends EventEmitterInterface
{
    public function getRemoteIP() : string;
    public function getMethod() : string;
    public function getRequestBodyRaw() : ReadableStreamInterface;
    public function getAttribute(string $key);
    public function setAttribute(string $key, $value);
    public function getPath() : string;
    public function getHeader(string $name) : ?string;
    public function getHeaderValues(string $name) : ?array;
    public function getQueryParam(string $key) : ?string;
    public function pipeRequestBody(WritableStreamInterface $dest, array $options = array()): WritableStreamInterface;
}