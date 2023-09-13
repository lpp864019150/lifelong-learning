<?php

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

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
        $handler = new RotatingFileHandler(sprintf(__DIR__ . './../../runtime/logs/%s.log', $group), 0, Logger::DEBUG);
        $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s'));
        $log->pushHandler($handler);

        return $log;
    }
}

static $_instance = [];
// 获取redis实列
if (!function_exists('redis')) {
    function redis($db = 0)
    {
        try{
            if (isset($_instance[$db]) && $_instance[$db]->ping()) {
                return $_instance[$db];
            }
        } catch (Exception $e) {

        }

        $_instance[$db] = new \Redis();
        $_instance[$db]->pconnect('127.0.0.1', 6379);
        $_instance[$db]->select($db);
        return $_instance[$db];
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
// PHP原生支持的转换函数：二进制：bindec/decbin/bin2hex/hex2bin、十六进制：hexdec/dexhex、八进制：octdec/decoct、2-36进制：base_convert
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
     * @return integer|string
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
if (! function_exists('getImgFromHtml')) {
    /**
     * 从html获取所有图片
     *
     * @link https://juejin.cn/post/7107036013582614536
     *
     * @param string $html
     * @return array
     */
    function getImgFromHtml(string $html) : array
    {
        $pattern = '/<img\s[^<>]*src=[\'"]?(?P<src>[^\'" >]+)[\'"]?[^>]*>/i';
        preg_match_all($pattern, $html, $matches);
        return $matches['src'] ?? [];
    }
}
if (! function_exists('getVideoFromHtml')) {
    /**
     * 从html获取所有视频
     *
     * @link https://juejin.cn/post/7107036013582614536
     *
     * @param string $html
     * @return array
     */
    function getVideoFromHtml(string $html) : array
    {
        $pattern = '/<video\s.*?src=[\'"]?(?P<src>[^\'" >]+)[\'"]?[^>]*>/i';
        preg_match_all($pattern, $html, $matches);
        return $matches['src'] ?? [];
    }
}
if (! function_exists('getImgFromMarkdown')) {
    /**
     * 从markdown获取所有图片
     *
     * @link https://juejin.cn/post/7107036013582614536
     *
     * @param string $markdown
     * @return array
     */
    function getImgFromMarkdown(string $markdown) : array
    {
        $pattern = '/!\[.*?\]\((?P<src>.*?)\)/i';
        preg_match_all($pattern, $markdown, $matches);
        return $matches['src'] ?? [];
    }
}
// Redis分布式锁
if (! function_exists('redisLock')) {
    /**
     * Redis分布式锁
     *
     * @param string $key
     * @param integer $expire
     * @return bool
     */
    function redisLock(string $key, int $expire = 5) : bool
    {
        $redis = redis();
        $token = uniqid();
        $result = $redis->set($key, $token, ['nx', 'ex' => $expire]);
        if ($result) {
            return true;
        }
        // 防止死锁
        $val = $redis->get($key);
        if ($val && $val == $token) {
            $redis->del($key);
        }
        return false;
    }
}
// Redis先判断是否存在，然后删除，使用lua脚本保证原子性
if (! function_exists('redisDelByLua')) {
    /**
     * Redis先判断是否存在，然后删除，使用lua脚本保证原子性
     *
     * @param string $key
     * @return bool
     */
    function redisDelByLua(string $key, $val) : bool
    {
        $redis = redis();
        $lua = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
LUA;
        return $redis->eval($lua, [$key, $val], 1);
    }
}
if (! function_exists('redisExistsAndDel')) {
    /**
     * Redis先判断是否存在，然后删除，利用del的返回值是否大于0来判断是否存在，保证原子性
     *
     * @param string $key
     * @return bool
     */
    function redisExistsAndDel(string $key) : bool
    {
        $redis = redis();
        return $redis->del($key) > 0;
    }
}
if (! function_exists('lock')) {
    /**
     * 加锁操作，其中加锁使用set nx ex命令保证原子性，解锁使用lua脚本保证原子性
     * @param callable $call 加锁后执行的逻辑
     * @param string $key 锁的key
     * @param int $ttl 锁的过期时间
     * @return mixed
     * @throws Exception
     */
    function lock(callable $call, string $key, int $ttl = 20)
    {
        $luaTpl = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1] then
    return redis.call('del', KEYS[1])
end
LUA;
        $redis = redis();
        $random = uniqid();
        if ($redis->set($key, $random, ['nx', 'ex' => $ttl])) {
            try {
                return call_user_func($call);
            } finally { // 无论成功与否都要解锁
                $redis->eval($luaTpl, [$key, $random], 1);
            }
        } else {
            throw new \Exception('blocked by lock: ' . $key);
        }
    }
}
// 统计有多少个汉字
if (! function_exists('countChinese')) {
    /**
     * 统计有多少个汉字
     *
     * @param string $str
     * @param int $max 最大统计个数，0表示不限制
     * @return int
     */
    function countChinese(string $str, int $max = 0) : int
    {
        $count = 0;
        $len = mb_strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($str, $i, 1);
            if (mb_ord($char) >= 0x4E00 && mb_ord($char) <= 0x9FA5) {
                $count++;
                if ($max && $count >= $max) return $max;
            }
        }
        return $count;
    }
}
// 判断是否有中文字符
if (! function_exists('hasChinese')) {
    /**
     * 判断是否有中文字符
     *
     * @param string $str
     * @return bool
     */
    function hasChinese(string $str) : bool
    {
        return preg_match('/[\x{4e00}-\x{9fa5}]/u', $str) > 0;
    }
}
// 检测是否是苹果IP
if (! function_exists('isAppleIp')) {
    /**
     * 检测是否是苹果IP
     *
     * @param string $ip
     * @return bool
     */
    function isAppleIp(string $ip) : bool
    {
        $ip = gethostbyname($ip);
        $pattern = '/^(17[0-9]|19[0-9]|25[0-9]|27[0-9]|30[0-9]|36[0-9]|42[0-9]|45[0-9]|50[0-9]|58[0-9]|60[0-9]|90[0-9]|100[0-9])\.(16[0-9]|24[0-9]|32[0-9]|40[0-9]|48[0-9]|56[0-9]|64[0-9]|72[0-9]|80[0-9]|88[0-9]|96[0-9]|104[0-9]|112[0-9]|120[0-9]|128[0-9]|136[0-9]|144[0-9]|152[0-9]|160[0-9]|168[0-9]|176[0-9]|184[0-9]|192[0-9]|200[0-9]|208[0-9]|216[0-9]|224[0-9]|232[0-9]|240[0-9]|248[0-9])\.(0|255)$/';
        return preg_match($pattern, $ip) > 0;
    }
}
// 检测是否是苹果IP
if (! function_exists('isAppleIp2')) {
    /**
     * 检测是否是苹果IP
     *
     * @param string $ip
     * @return bool
     */
    function isAppleIp2(string $ip) : bool
    {
        $ip = gethostbyname($ip);
        $pattern = '/^(17[0-9]|19[0-9]|25[0-9]|27[0-9]|30[0-9]|36[0-9]|42[0-9]|45[0-9]|50[0-9]|58[0-9]|60[0-9]|90[0-9]|100[0-9])\.(16[0-9]|24[0-9]|32[0-9]|40[0-9]|48[0-9]|56[0-9]|64[0-9]|72[0-9]|80[0-9]|88[0-9]|96[0-9]|104[0-9]|112[0-9]|120[0-9]|128[0-9]|136[0-9]|144[0-9]|152[0-9]|160[0-9]|168[0-9]|176[0-9]|184[0-9]|192[0-9]|200[0-9]|208[0-9]|216[0-9]|224[0-9]|232[0-9]|240[0-9]|248[0-9])\.(0|255)$/';
        return preg_match($pattern, $ip) > 0;
    }
}
// 获取jsapi_ticket
if (! function_exists('getJsapiTicket')) {
    /**
     * 获取jsapi_ticket
     *
     * @param string $appId
     * @param string $appSecret
     * @return string
     */
    function getJsapiTicket(string $appId, string $appSecret) : string
    {
        $cacheKey = 'wx_jsapi_ticket_' . $appId;
        $redis = redis();
        $ticket = $redis->get($cacheKey);
        if (! $ticket) {
            $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . getAccessToken($appId, $appSecret) . '&type=jsapi';
            $res = json_decode(file_get_contents($url), true);
            if ($res['errcode'] == 0) {
                $ticket = $res['ticket'];
                $redis->set($cacheKey, $ticket, 7000);
            } else {
                throw new \Exception('获取jsapi_ticket失败：' . $res['errmsg']);
            }
        }
        return $ticket;
    }
}
// 获取access_token
if (! function_exists('getAccessToken')) {
    /**
     * 获取access_token
     *
     * @param string $appId
     * @param string $appSecret
     * @return string
     */
    function getAccessToken(string $appId, string $appSecret) : string
    {
        $cacheKey = 'wx_access_token_' . $appId;
        $redis = redis();
        $token = $redis->get($cacheKey);
        if (! $token) {
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appId . '&secret=' . $appSecret;
            $res = json_decode(file_get_contents($url), true);
            if ($res['access_token']) {
                $token = $res['access_token'];
                $redis->set($cacheKey, $token, 7000);
            } else {
                throw new \Exception('获取access_token失败：' . $res['errmsg']);
            }
        }
        return $token;
    }
}
// 获取微信用户信息
if (! function_exists('getWxUserInfo')) {
    /**
     * 获取微信用户信息
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $code
     * @return array
     */
    function getWxUserInfo(string $appId, string $appSecret, string $code) : array
    {
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appId . '&secret=' . $appSecret . '&code=' . $code . '&grant_type=authorization_code';
        $res = json_decode(file_get_contents($url), true);
        if (isset($res['errcode'])) {
            throw new \Exception('获取access_token失败：' . $res['errmsg']);
        }
        $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $res['access_token'] . '&openid=' . $res['openid'] . '&lang=zh_CN';
        $res = json_decode(file_get_contents($url), true);
        if (isset($res['errcode'])) {
            throw new \Exception('获取用户信息失败：' . $res['errmsg']);
        }
        return $res;
    }
}
// 计算签名，后端根据jsapi_ticket等信息将签名计算好并将signature、timestamp、nonceStr，返回给前端
if (! function_exists('calcSignature')) {
    /**
     * 计算签名，后端根据jsapi_ticket等信息将签名计算好并将signature、timestamp、nonceStr，返回给前端
     *
     * @param string $ticket
     * @param string $url
     * @param int $timestamp
     * @param string $nonceStr
     * @return string
     */
    function calcSignature(string $ticket, string $url, int $timestamp, string $nonceStr) : string
    {
        $data = [
            'jsapi_ticket' => $ticket,
            'noncestr' => $nonceStr,
            'timestamp' => $timestamp,
            'url' => $url,
        ];
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        return sha1($str);
    }
}
// 判断是否是微信浏览器
if (! function_exists('isWeixinBrowser')) {
    /**
     * 判断是否是微信浏览器
     *
     * @return bool
     */
    function isWeixinBrowser() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false;
    }
}
// 判断是否是微信小程序
if (! function_exists('isWeixinMiniProgram')) {
    /**
     * 判断是否是微信小程序
     *
     * @return bool
     */
    function isWeixinMiniProgram() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram') !== false;
    }
}
// 判断是否是支付宝小程序
if (! function_exists('isAlipayMiniProgram')) {
    /**
     * 判断是否是支付宝小程序
     *
     * @return bool
     */
    function isAlipayMiniProgram() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false;
    }
}
// 判断是否是QQ小程序
if (! function_exists('isQqMiniProgram')) {
    /**
     * 判断是否是QQ小程序
     *
     * @return bool
     */
    function isQqMiniProgram() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false;
    }
}
// 判断是否是百度小程序
if (! function_exists('isBaiduMiniProgram')) {
    /**
     * 判断是否是百度小程序
     *
     * @return bool
     */
    function isBaiduMiniProgram() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'swan-baiduboxapp') !== false;
    }
}
// 判断是否是头条小程序
if (! function_exists('isToutiaoMiniProgram')) {
    /**
     * 判断是否是头条小程序
     *
     * @return bool
     */
    function isToutiaoMiniProgram() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'toutiao') !== false;
    }
}
// 通过ua判断是否为苹果系统
if (! function_exists('isIos')) {
    /**
     * 通过ua判断是否为苹果系统
     *
     * @return bool
     */
    function isIos() : bool
    {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false;
    }
}
// 通过ua获取ios版本号
if (! function_exists('getIosVersion')) {
    /**
     * 通过ua获取ios版本号
     *
     * @return string
     */
    function getIosVersion() : string
    {
        preg_match('/OS (\d+)_(\d+)_?(\d+)?/', $_SERVER['HTTP_USER_AGENT'], $matches);
        return $matches[1] . '.' . $matches[2] . '.' . ($matches[3] ?? 0);
    }
}
// 可逆加密算法
if (! function_exists('encrypt')) {
    /**
     * 可逆加密算法
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    function encrypt(string $data, string $key) : string
    {
        $key = md5($key);
        $x = 0;
        $len = strlen($data);
        $l = strlen($key);
        $char = '';
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= $key[$x];
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord($data[$i]) + (ord($char[$i])) % 256);
        }
        return base64_encode($str);
    }
}
// 可逆解密算法
if (! function_exists('decrypt')) {
    /**
     * 可逆解密算法
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    function decrypt(string $data, string $key) : string
    {
        $key = md5($key);
        $x = 0;
        $data = base64_decode($data);
        $len = strlen($data);
        $l = strlen($key);
        $char = '';
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) {
                $x = 0;
            }
            $char .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            } else {
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return $str;
    }
}
// 数据脱敏
if (! function_exists('dataDesensitization')) {
    /**
     * 数据脱敏
     *
     * @param string $data
     * @param int $start
     * @param int $length
     * @param string $replace
     * @return string
     */
    function dataDesensitization(string $data, int $start = 0, int $length = 0, string $replace = '*') : string
    {
        if ($length == 0) {
            $length = strlen($data) - $start;
        }
        return substr_replace($data, str_repeat($replace, $length), $start, $length);
    }
}
// redis + lua 限流
if (! function_exists('redisLimiter')) {
    /**
     * redis + lua 限流
     *
     * @param string $key
     * @param int $limit
     * @param int $expire
     * @return bool 若返回true则表示已经超过限流阈值，需限流
     */
    function redisLimiter(string $key, int $limit, int $expire): bool
    {
        $lua = <<<LUA
local key = KEYS[1]
local limit = tonumber(ARGV[1])
local expire = tonumber(ARGV[2])
local current = tonumber(redis.call('get', key) or "0")
if current + 1 > limit then
    return 0
else
    redis.call("INCRBY", key, "1")
    redis.call("expire", key, expire)
    return current + 1
end
LUA;
        $redis = redis();
        return $redis->eval($lua, [$key, $limit, $expire], 1) === 0;
    }
}
// md5 16
if (! function_exists('md5_16')) {
    /**
     * md5 16
     *
     * @param string $str
     * @return string
     */
    function md5_16(string $str) : string
    {
        return substr(md5($str), 8, 16);
    }
}








