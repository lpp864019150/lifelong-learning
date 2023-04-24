<?php

use Lpp\BloomFilter\BloomFilterArray;

include_once 'vendor/autoload.php';

//include_once 'Functions.php';
//include_once 'BloomFilter.php';

function testFunc()
{
    $s = microtime(true);

    $s -= mt_rand(0, 100) * mt_rand(1000, 86400);

    usleep(mt_rand(1000, 9999));
    dd(time_used($s));


}
//testFunc();

function testRadix()
{
    $from = 'RYgs';
    dd(sixtytwodec($from));

    $from = '49784984668';
    dd(sixtytwodec($from));
}
//testRadix();

function testBloomFilter()
{
    $a = '4984165165';
    $bf = new BloomFilter();
    dd($bf->contains($a));
}
//testBloomFilter();

function testBloomFilterArray()
{
    // 使用示例
    $bf = new BloomFilterArray();
    $bf->add('foo');
    $bf->add('bar');

    if ($bf->exists('foo')) {
        echo "foo exists\n";
    }

    if ($bf->exists('baz')) {
        echo "baz exists\n";
    } else {
        echo "baz not exists\n";
    }
}
//testBloomFilterArray();

function testRedisSet()
{
    // 连接Redis服务器
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    // 开始管道事务
    $redis->multi(Redis::PIPELINE);

    // 向Redis插入1到1000000的值
    for ($i = 1; $i <= 10000; $i++) {
        $redis->sAdd('myset:10000', $i);
    }

    // 提交管道事务
    $redis->exec();
}
//testRedisSet();
function testImg()
{
    getImgFromHtml('<html><img src="aaa">');
}
//testImg();
function testVideo()
{
    $html = '<html><video   ss bb src=video.mp4></video><video src="video2.mp4"></video>';
    dd(getVideoFromHtml($html));
}
testVideo();