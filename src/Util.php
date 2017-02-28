<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 27/02/17
 * Time: 21:38
 */

namespace RestifiedPHP;


class Util
{
    private const VALID_HANDLER_PATH_REGEX = "^(\\/\\w+)+\\.\\w+(\\?(\\w+=[\\w\\d]+(&\\w+=[\\w\\d]+)+)+)*$";
    static public function splitIncomingURLToPathComponents(string $path) : array {
        if($path === "/") {
            return ["/"];
        }
        return array_slice(explode("/",$path),1);
    }

    static public function isValidRequestHandlerPath(string $path) : bool {
        $path = trim($path);
//        if(preg_match(Util::VALID_HANDLER_PATH_REGEX,$path) === 1) {
            return true;
//        } else {
//            return false;
//        }
    }
}