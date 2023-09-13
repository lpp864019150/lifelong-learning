# Redis内存突增排查

## 参考资料

1. [redis之BigKEY问题集合 - 掘金](https://juejin.cn/post/7236682185963814969)

#### 1、原因？

> 通常redis内存突增有几种情况：

1. key数量持续增长
这种问题一般是key的数量过多且ttl时间过长，增长的数量超过释放的数量导致内存不断变高，这种情况可以考虑适当的调整key的缓存时间，对于非热点key，设立单独的短期ttl
2. 某些key的值过大
这种情况最常见的就是队列没有及时被消费，这种时候我们需要想办法找出这些value过大的key，再来排查具体原因。

#### 2、如何排查？

###### 1. 通过redis-cli的命令行工具可以很方便的排查redis的这些问题。

监控key的数量和内存

```
./redis-cli -h <hostname> -a <password> --stat
#
------- data ------ --------------------- load -------------------- - child -
keys       mem      clients blocked requests            connections  
key总数  内存      连接数  等待中(如sub)  请求累加  连接累加
#
```

通过stat命令，我们可以监测到这个redis的key和内存是否在持续增长

###### 2. redis正在执行哪些命令？

`./redis-cli -h <hostname> -a <password> monitor`

```shell
# 可以实时看到正在执行的命令
root@DESKTOP-MEQ1GD0:~# redis-cli monitor
OK
1678783993.834983 [0 127.0.0.1:64363] "scan" "0" "MATCH" "*" "COUNT" "500"
1678783993.866504 [0 127.0.0.1:64363] "info"
1678783993.894320 [0 127.0.0.1:64363] "info"
1678784001.425883 [0 127.0.0.1:64366] "set" "lpp" "ppl"
1678784003.839016 [0 127.0.0.1:64363] "ping"
1678784011.601131 [0 127.0.0.1:64366] "del" "lpp"
1678784013.838918 [0 127.0.0.1:64363] "ping"
1678784023.839253 [0 127.0.0.1:64363] "ping"
1678784033.835431 [0 127.0.0.1:64363] "ping"
```

当我们发现key在不断增长，可以通过monitor命令来找出哪些key在被频繁的创建，monitor会在屏幕上实时显示当前被执行的命令，我们可以添加：> monitor.log来导出某段时间内的redis执行命令的情况，便于后续分析。

###### 如何找出占用内存的真凶？

如果是某些占用内存大户的key导致内存占用过高，如何才能揪出这些key呢？

```
./redis-cli -h <hostname> -a <password> --bigkeys -i 0.1
-------- summary -------
Sampled 0 keys in the keyspace!
Total key length in bytes is 0 (avg len 0.00)

0 strings with 0 bytes (00.00% of keys, avg size 0.00)
0 lists with 0 items (00.00% of keys, avg size 0.00)
0 sets with 0 members (00.00% of keys, avg size 0.00)
0 hashs with 0 fields (00.00% of keys, avg size 0.00)
0 zsets with 0 members (00.00% of keys, avg size 0.00)
#
```

使用bigkeys参数会扫描整个redis的所有key，100%扫描完成后，会把每种类型的key的最大值打印出来。其中-i表示每扫描100个key的时间间隔（单位为秒），通过这个命令我们可以很容易找到当前redis占用过大的内存的key。

###### 3. redis响应过慢如何排查？

当我们发现redis的性能下降时，除了检查redis的qps是否过高，还可用命令来查看redis的慢日志，找到对应的命令来做出相应的调整，进入redis-cli执行：

```
# 慢查询时间 单位微秒 1秒 = 1000毫秒 = 1000000微妙，默认10000，也即10ms
slowlog-log-slower-than
# 慢查询列表长度
slowlog-max-len

# 获取10条慢日志
127.0.0.1:6379> slowlog get 10
#
 1) 1) (integer) 87 // 日志id
    2) (integer) 1591107546 // 日志时间戳
    3) (integer) 140995 // 执行时间，微秒
    4) 1) "DEL" // 执行的命令
       2) "channel"
    5) "100.104.175.14:37442" // 客户端ip信息
    6) "ALIYUN_DMS"
 2) 1) (integer) 86
    2) (integer) 1590849519
    3) (integer) 10380
    4) 1) "LRANGE"
       2) "cnclicks"
       3) "0"
       4) "-1"
    5) "172.16.71.143:48986"
    6) ""
```

###### 4. 单个key占用内存数

```
# 查看内存
# Strings或其他类型
memory usage key

# 查看多少个元素
# Hashes
hlen key
# Lists
llen key
# Sets
zcard key
# Sorted sets
scard key
```

###### 5. 如何查qps

```
# 当前实时qps
redis-cli info | grep instantaneous_ops_per_sec

# 压测qps
redis-benchmark -c 50 -n 100000 -d 2

1. 可以在info里面查看大致的实时qps instantaneous_ops_per_sec
redis-cli info | grep instantaneous_ops_per_sec

2. 使用自带的压测工具来测试最大的qps，这个也只能作为参考 
// -c 并发数，-n总请求数，-d每次携带数据大小，这里是2kb
redis-benchmark -h <hostname> -a <password> -c 50 -n 100000 -d 2

3. 使用间隔来测算实时QPS
// 使用watch每60s观察一次总命令数，两者相减再除以60，选取多个时间端来观察
nohup watch -n 60 'redis-cli -h <hostname> -a <password> info | grep total_commands_processe >> qps2.txt && date >>qps2.txt' > log 2>&1 &
```

#### 3、redis的其他注意事项

1. 线上严禁使用`keys`、`flushdb`、`flushall`、`config`，这些命令在线上环境执行非常危险，应该直接禁掉。
2. 谨慎使用`hgetall`、`lrange`、 `smembers`、`zrange`等全量命令，对数据不可控的key尽量避免全量获取，应该改用分页的方式逐页获取，避免阻塞`redis`（`redis`的读写是单线程的）