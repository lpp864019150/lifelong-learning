# Redis分布式锁

## 参考博文

1. [Redis实战篇：Redis分布式锁无死角分析_51CTO博客_redis setnx 分布式锁](https://blog.51cto.com/MageByte/2930601)
2. [Redis分布式锁的10个坑 - 掘金](https://juejin.cn/post/7178327462869205051)

## 实战

```php
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
```

## 坑

1. 加锁非原子操作
   
   加锁一般操作为：抢锁，也即`setnx`；设置过期时间，也即`expire`。
   
   在以前的低版本(低于`2.6.12`)还不支持`set`多参数命令，上面两个操作需分开两次命令执行，无法做到原子操作，若需原子操作则需借助lua脚本。但是后来的高版本(不低于`2.6.12`)支持`set`多参数，直接一个命令即可完成，`set nx ex 600`。

2. 未设置过期时间
   
   未设置过期时间，若用户未做解锁，或者出现异常宕机了未执行解锁，则该锁就变成了死锁，后续所有用户都无法再次获取到锁。

3. 解锁时把其他用户加的锁给解了
   
   一把钥匙开一把锁，给每个锁设置一个唯一值，然后在解锁时需要拿这个钥匙(唯一值)来开锁。若不做锁判断就进行删除则可能删除了其他用户加的锁。

4. 解锁非原子操作
   
   解锁一般操作为：判断是否为自己加的锁，`get == value`；删除，`del`。
   
   这里面涉及到两个命令，目前`redis`不支持把这两个命令当作一次原子操作，那么我们只能借助`lua`脚本
   
   ```lua
   if redis.call('get', KEYS[1]) == ARGV[1] then
       return redis.call('del', KEYS[1])
   end
   ```

5. 锁过期释放，业务依然未完成
   
   在设置过期时间，需评估业务最长执行时间，在此基础上加上几百毫秒即可。
   
   当然还有一个方案就是维护一个进程，不断轮询，若发现锁快过期了则给其续期，此处也需注意原子操作。


