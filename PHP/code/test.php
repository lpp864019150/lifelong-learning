<?php
include_once 'Functions.php';

function testFunc()
{
    $s = microtime(true);

    $s -= mt_rand(0, 100) * mt_rand(1000, 86400);

    usleep(mt_rand(1000, 9999));
    dd(time_used($s));


}
testFunc();