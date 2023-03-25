<?php

namespace Lpp\Test\Cases;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testAddition()
    {
        $result = 1 + 1;
        logger()->info(PHP_EOL . __METHOD__ . "result: " . $result . PHP_EOL);
        $this->assertEquals(2, $result);
        throw new \Exception('this is a exception');
    }
}