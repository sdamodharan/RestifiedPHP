<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: sriram
 * Date: 27/02/17
 * Time: 21:43
 */

namespace RestifiedPHP;

use \PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testIncomingURLPathSplitterValid() : void {
        $testCases = ["/","/a/b/c/d"];
        $expectedResults = [["/"],["a","b","c","d"]];

        for ($i = 0; $i < sizeof($testCases); $i++) {
            $components = Util::splitIncomingURLToPathComponents($testCases[$i]);
            $this->assertEquals($expectedResults[$i],$components);
        }
    }

    public function testIncomingURLPathSplitterInvalid() : void {
        $testCases = ["/","/a/b/c/d"];
        $expectedResults = [["/"],["a","b","c","d"]];

        for ($i = 0; $i < sizeof($testCases); $i++) {
            $components = Util::splitIncomingURLToPathComponents($testCases[$i]);
            $this->assertEquals($expectedResults[$i],$components);
        }
    }
}
