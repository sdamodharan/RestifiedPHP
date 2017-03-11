<?php
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 01/03/17
 * Time: 16:36
 */

namespace RestifiedPHP;


use React\Http\Request;
use React\Http\Response;
use React\Promise\Promise;

interface Middleware
{
    public function process (HttpRequest $request,Response $response, callable $next) : void;
}