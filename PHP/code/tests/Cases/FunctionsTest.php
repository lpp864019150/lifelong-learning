<?php

namespace Test\Cases;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testGetImgFromHtml()
    {
        $html = '<img src="https://xcg-box.bygamesdk.com/avatar/default/202204/19/170302625e7ac6583c9.png">';
        $result = getImgFromHtml($html);
        logger('FunctionsTest', 'test')->info(PHP_EOL . __METHOD__ . " result: " . json_encode($result) . PHP_EOL);
        $this->assertEquals(1, count($result));
    }

    public function testGetVideoFromHtml()
    {
        $html = '<p><br></p><div data-w-e-type="video" data-w-e-is-void>
<video poster="" controls="true" width="auto" height="auto"><source src="https://xcg-box.bygamesdk.com//video/202304/17/太好玩了，准备单杀了.mp4" type="video/mp4"/></video>
</div><p><br></p><video src="FunctionsTest.php"></video>';
        $result = getVideoFromHtml($html);
        logger('FunctionsTest', 'test')->info(PHP_EOL . __METHOD__ . " result: " . json_encode($result) . PHP_EOL);
        $this->assertEquals(2, count($result));
    }

    public function testCountChinese()
    {
        $str = '这里面有两种主流升级方式：依据最新版本升级方式引导升级，依据用户当前所用版本升级方式引导用户升级。依据最新版本升级方式引导用户升级：不管用户当前所用版本，所有版本都是依据最新版的升级方式来升级的。';
        $this->assertEquals(92, countChinese($str));
        //$this->assertEquals(50, countChinese($str, 50));
    }

    public function testHasChinese()
    {
        $str = '这里面有两种主流升级方式：依据最新版本升级方式引导升级，依据用户当前所用版本升级方式引导用户升级。依据最新版本升级方式引导用户升级：不管用户当前所用版本，所有版本都是依据最新版的升级方式来升级的。';
        $this->assertEquals(1, countChinese($str, 1));
        $this->assertEquals(true, hasChinese($str));
    }

    public function testRedisDelByLua()
    {
        $key = 'test';
        $val = 'test';
        $expire = 100;
        $redis = redis();
        $redis->del($key);
        $redis->set($key, $val, ['nx', 'ex' => $expire]);

        $result = redisDelByLua($key, $val);
        logger('FunctionsTest', 'test')->info(PHP_EOL . __METHOD__ . " result: " . json_encode($result) . PHP_EOL);
        $this->assertEquals(true, $result);
    }

    public function testRedisPipeline()
    {
        $redis1 = redis();
        $redis = redis();
        $redis->pipeline();

        $redis1->set('test1', 'test1', 100);
        $r = $redis1->get('test1');

        $redis->set('test2', 'test2', 100);
        $redis->get('test2');

        $p = $redis->exec();

        var_dump($r, $p);

        $this->assertEquals(true, true);
    }

    public function testLock()
    {
        $r = lock(function(){

            //throw new \Exception('test');

            return 1;

        }, 'test:lock', 1000);

        $this->assertEquals($r, 1);
    }

    public function testAppleIp()
    {
        $ip = '17.147.100.102';
        dd(isAppleIp($ip));
        $this->assertEquals(true, isAppleIp($ip));
    }

    public function testJsapiTikect()
    {
        $appid = 'xx';
        $secrect = 'xx';
        $ticket = getJsapiTicket($appid, $secrect);
        dd($ticket);
    }

    public function testScan()
    {
        $redis = redis();
        $iterator = null;
        $match = 'ba:*';
        while(false !== ($keys = $redis->scan($iterator, $match, 5))) {
            var_dump($keys);
            foreach($keys as $key) {
                echo $key . PHP_EOL;
            }
        }
    }

    public function testEncrypt()
    {
        $str = '13660045612';
        $key = substr(md5('testkey'), 8, 16);
        $encrypt = encrypt($str, $key);
        $decrypt = decrypt($encrypt, $key);
        logger('FunctionsTest', 'test')->info(PHP_EOL . __METHOD__ . " encrypt: " . $encrypt . PHP_EOL, ['decrypt' => $decrypt, 'encrypt' => $encrypt]);
        $this->assertEquals($str, $decrypt);
    }
}