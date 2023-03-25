<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 通用打印参数并退出，用于断点调试
if (!function_exists('dd')) {
    function dd() {
        var_dump(...func_get_args());
        die();
    }
}

/**
 * 文件日志
 */
if (!function_exists('logger')) {
    function logger($name = 'default', $group = 'default')
    {
        $log = new Logger($name);
        $log->pushHandler(new StreamHandler(sprintf(__DIR__ . './../../logs/%s.log', $group), Logger::DEBUG));

        return $log;
    }
}

// 消耗时间
if (!function_exists('time_used')) {
    function time_used($start) {
        $diff = microtime(true) - $start;

        $t = [];
        $fmt = function($diff, $field) {return sprintf('%s%s', $diff, $field);};
        $map = [
            86400 => '天',
            3600 => '小时',
            60 => '分钟',
            1 => '秒',
            0 => function($diff) {return sprintf('%.2f毫秒', $diff * 1000);}
        ];

        foreach ($map as $seconds => $v) {
            if ($diff < $seconds) continue;

            if (is_callable($v)) $t[] = $v($diff);
            else {
                $d = $seconds ? round($diff / $seconds) : 0;
                $diff -= $d * $seconds;
                $t[] = $fmt($d, $v);
            }
        }

        return implode('', $t);
    }
}

// 内存占用
if (!function_exists('memory_used')) {
    function memory_used($bytes) {
        $unit=array('B','KB','MB','GB','TB','PB');
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[$i];
    }
}

// 格式化标准datetime
if (! function_exists('datetime')) {
    function datetime($timestamp = null){
        return date('Y-m-d H:i:s', $timestamp?:time());
    }
}

// 进制转换
// PHP原生支持的转换函数：二进制：bindec/decbin/bin2hex/hex2bin、十六进制：hexdec/dexhex、八进制：octdec/decoct
const DICT = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
if (! function_exists('sixtytwodec')) {
    /**
     * 62进制数转换成十进制数
     *
     * @param string $sixtyTwoString
     * @return string
     */
    function sixtytwodec($sixtyTwoString)
    {
        $from = 62;
        $num = strval($sixtyTwoString);
        $len = strlen($num);
        $dec = 0;
        for($i = 0; $i < $len; $i++) {
            $pos = strpos(DICT, $num[$i]);
            echo "bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec)" . PHP_EOL;
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
            echo "$i - $num[$i] - $pos - $dec" . PHP_EOL;
        }
        return $dec;
    }
}
if (! function_exists('decsixtytwo')) {
    /**
     * 十进制数转换成62进制
     *
     * @param integer $num
     * @return string
     */
    function decsixtytwo($num) {
        $to = 62;
        $ret = '';
        do {
            $ret = DICT[bcmod($num, $to)] . $ret; //bcmod取得高精确度数字的余数。
            $num = bcdiv($num, $to);  //bcdiv将二个高精确度数字相除。
        } while ($num > 0);
        return $ret;
    }
}
if (! function_exists('decx')) {
    /**
     * 十进制数转换成其它进制
     * 可以转换成2-62任何进制
     *
     * @param integer $num
     * @param integer $to
     * @return string
     */
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
}
if (! function_exists('xdec')) {
    /**
     * 其它进制数转换成十进制数
     * 适用2-62的任何进制
     *
     * @param string $num
     * @param integer $from
     * @return number
     */
    function xdec($num, $from = 62)
    {
        if ($from == 10 || $from > 62 || $from < 2) {
            return $num;
        }
        $num = strval($num);
        $len = strlen($num);
        $dec = 0;
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos(DICT, $num[$i]);
            if ($pos >= $from) {
                continue; // 如果出现非法字符，会忽略掉。比如16进制中出现w、x、y、z等
            }
            $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
        }
        return $dec;
    }
}
if (! function_exists('radix')) {
    /**
     * 数字的任意进制转换
     *
     * @param integer|string $number
     * @param integer $to 目标进制数
     * @param integer $from 源进制数
     * @return string
     */
    function radix($number, $to = 62, $from = 10)
    {
        // 先转换成10进制
        $number = xdec($number, $from);
        // 再转换成目标进制
        $number = decx($number, $to);
        return $number;
    }
}


