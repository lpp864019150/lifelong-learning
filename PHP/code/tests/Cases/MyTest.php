<?php

namespace Test\Cases;

use PHPUnit\Framework\TestCase;

class MyTest extends TestCase
{
    public function testAddition()
    {
        $result = 1 + 1;
        logger('MyTest', 'test')->info(PHP_EOL . __METHOD__ . " result: " . $result . PHP_EOL);
        $this->assertEquals(2, $result);
    }

    public function testHello()
    {
        $this->assertEquals(1, 1);
    }
}