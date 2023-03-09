<?php

// 通用打印参数并退出，用于断点调试
if (!function_exists('dd')) {
    function dd() {
        var_dump(...func_get_args());
        die();
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

