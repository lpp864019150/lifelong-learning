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
}