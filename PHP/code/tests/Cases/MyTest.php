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

    public function testSwitch()
    {
        $num = 25;
        switch(true) {
            case $num < 0:
                logger('MyTest', 'test')->info(PHP_EOL . __METHOD__ . " num < 0" . PHP_EOL);
                break;
            case $num >= 0 && $num <= 10:
                logger('MyTest', 'test')->info(PHP_EOL . __METHOD__ . " num >= 0 && num <= 10" . PHP_EOL);
                break;
            case $num >= 10 && $num <= 20:
                logger('MyTest', 'test')->info(PHP_EOL . __METHOD__ . " num >= 10 && num <= 20" . PHP_EOL);
                break;
            default:
                logger('MyTest', 'test')->info(PHP_EOL . __METHOD__ . " num >= 20" . PHP_EOL);
                break;
        }

        $this->assertEquals(1, 1);
    }

    public function testRedisType()
    {
        $redis = redis();
        $key = 'test_type_aaa1';
        $int = 100;
        $redis->set($key, $int, 600);
        $type = gettype($redis->get($key));
        var_dump($redis->get($key));
        $this->assertEquals('string', $type);
    }
}