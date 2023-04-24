<?php
// 冒泡算法
function bubbleSort($arr)
{
    $len = count($arr);
    for ($i = 0; $i < $len; $i++) {
        for ($j = 0; $j < $len - $i - 1; $j++) {
            if ($arr[$j] > $arr[$j + 1]) {
                $tmp = $arr[$j];
                $arr[$j] = $arr[$j + 1];
                $arr[$j + 1] = $tmp;
            }
        }
    }
    return $arr;
}


// 选择排序
function selectSort($arr)
{
    $len = count($arr);
    for ($i = 0; $i < $len; $i++) {
        $min = $i;
        for ($j = $i + 1; $j < $len; $j++) {
            if ($arr[$j] < $arr[$min]) {
                $min = $j;
            }
        }
        $tmp = $arr[$i];
        $arr[$i] = $arr[$min];
        $arr[$min] = $tmp;
    }
    return $arr;
}

// 10进制转换62进制
function decsixtytwo($num) {
    $to = 62;
    $ret = '';
    do {
        $ret = DICT[bcmod($num, $to)] . $ret; //bcmod取得高精确度数字的余数。
        $num = bcdiv($num, $to);  //bcdiv将二个高精确度数字相除。
    } while ($num > 0);
    return $ret;
}

// 10进制转换其它进制
function decx($num, $to = 62)
{
    if ($to == 10 || $to > 62 || $to < 2) {
        return $num;
    }
    $ret = '';
    do {
        $ret = DICT[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

// 其它进制转换10进制
function xdec($num, $from = 62)
{
    if ($from == 10 || $from > 62 || $from < 2) {
        return $num;
    }
    $num = strval($num);
    $len = strlen($num);
    $dec = 0;
    for ($i = 0; $i < $len; $i++) {
        $dec = bcadd(bcmul($dec, $from), strpos(DICT, $num[$i]));
    }
    return $dec;
}

// 62进制转换10进制
function sixtytwodec($num)
{
    $from = 62;
    $num = strval($num);
    $len = strlen($num);
    $dec = 0;
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos(DICT, $num[$i]);
        $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
    }
    return $dec;
}

// 生成随机字符串
function randomStr($length = 6)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 生成随机数字
function randomNum($length = 6)
{
    $str = null;
    $strPol = "0123456789";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 生成随机字母
function randomLetter($length = 6)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 生成随机数字和字母
function randomNumLetter($length = 6)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)];
    }
    return $str;
}

// 生成随机数字和字母
function randomNumLetter2($length = 6)
{
    $str = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[mt_rand(0, $max)];
    }
    return $str;
}

// 提取html里面的所有图片
function getImgs($html)
{
    $pattern = "/<img.*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png|\.jpeg]))[\'|\"].*?[\/]?>/i";
    preg_match_all($pattern, $html, $match);
    return $match[1];
}

// 获取html里面的所有链接
function getLinks($html)
{
    $pattern = "/<a.*?href=[\'|\"](.*?(?:[\.html|\.htm|\.php|\.jsp]))[\'|\"].*?[\/]?>/i";
    preg_match_all($pattern, $html, $match);
    return $match[1];
}

// 获取html里面的所有视频
function getVideos($html)
{
    $pattern = "/<video.*?src=[\'|\"](.*?(?:[\.mp4|\.avi|\.rmvb|\.rm|\.flv|\.wmv]))[\'|\"].*?[\/]?>/i";
    preg_match_all($pattern, $html, $match);
    return $match[1];
}

// 生成唯一id
function createId()
{
    return md5(uniqid(md5(microtime(true)), true));
}

// ln(x)
function ln($x)
{
    return log($x);
}

// (ln(x) + 2ln(y) + 3ln(z)) * 5 * e^l
function ln2($x, $y, $z, $l)
{
    return (ln($x) + 2 * ln($y) + 3 * ln($z)) * 5 * exp($l);
}

// (n + ln(x) + 2ln(y) + 3ln(z)) * 5 * e^(-0.3*(time()-t)/86400)
function ln3($n, $x, $y, $z, $t)
{
    return ($n + ln($x) + 2 * ln($y) + 3 * ln($z)) * 5 * exp(-0.3 * (time() - $t) / 86400);
}

// round((base + ln(like_times + 1) + 2ln(comment_count + 1) + 3ln(collect_times + 1)) * 5 * e^(-0.3*(time()-strtotime(send_at))/86400) * 1000000)
function ln4($base, $like_times, $comment_count, $collect_times, $send_at)
{
    return round(($base + ln($like_times + 1) + 2 * ln($comment_count + 1) + 3 * ln($collect_times + 1)) * 5 * exp(-0.3 * (time() - strtotime($send_at)) / 86400) * 1000000);
}

// 程序耗时和内存使用
function runtime()
{
    $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
    $memory = memory_get_usage() - $_SERVER['MEMERY'];
    return '耗时：' . $time . '秒，内存使用：' . $memory . '字节';
}

// 执行时间格式化
function runtimeFormat($time)
{
    $str = '';
    $day = floor($time / 86400);
    if ($day > 0) {
        $str .= $day . '天';
    }
    $time = $time % 86400;
    $hour = floor($time / 3600);
    if ($hour > 0) {
        $str .= $hour . '小时';
    }
    $time = $time % 3600;
    $minute = floor($time / 60);
    if ($minute > 0) {
        $str .= $minute . '分钟';
    }
    $time = $time % 60;
    $second = floor($time);
    if ($second > 0) {
        $str .= $second . '秒';
    }
    $time = $time - $second;
    $millisecond = floor($time * 1000);
    if ($millisecond > 0) {
        $str .= $millisecond . '毫秒';
    }
    return $str;
}



