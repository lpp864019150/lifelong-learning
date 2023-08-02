# Redis长连接

## 参考博文

1. [PHP中使用Redis长连接笔记-腾讯云开发者社区-腾讯云](https://cloud.tencent.com/developer/article/1682554)

## 语法

```php
pconnect(host, port, time_out, persistent_id, retry_interval);      
# host: string. can be a host, or the path to a unix domain socket         
# port: int, optional          
# timeout: float, value in seconds (optional, default is 0 meaning unlimited)          
# persistent_id: string. identity for the requested persistent connection          
# retry_interval: int, value in milliseconds (optional)  
```

## 用法

在`PHP-FPM`的生命周期里不会重复获取`Redis`连接，若使用普通的`connect`则会在`PHP`执行完即刻关闭，`pconnect`在`PHP`里即使执行了`close`也依然存在于`PHP-FPM`里，下一个`PHP`依然使用的是同一个连接

```php
$redis = new Redis();
$redis->pconnect('127.0.0.1', 6379);
```

## 维护一个单例

```php
static $_instance = [];
public static function getInstance($db = 0)
{
    try{
        if (isset(self::$_instance[$db]) && self::$_instance[$db]->Ping() == 'Pong') {
            return self::$_instance[$db];
        }   
    } catch (Exception $e) {

    }

    $redis = new Redis();
    $redis->pconnect('127.0.0.1');
    $redis->select($db);
    self::$_instance[$db] = $redis;

    return self::$_instance[$db];
}
```
