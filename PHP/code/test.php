<?php
include_once 'Functions.php';
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
testBloomFilter();