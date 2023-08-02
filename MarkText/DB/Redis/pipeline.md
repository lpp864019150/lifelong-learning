# pipeline

#### 0、参考文档

* [php redis 如何使用pipeline,Redis 高级特性 Pipeline (管道) 使用和基本测试](https://blog.csdn.net/weixin_28684497/article/details/115779382)
* [php读取文件使用redis的pipeline导入大批量数据_逝去日子如风的博客-CSDN博客](https://blog.csdn.net/hzj1064189928/article/details/80499657)

#### 1、术语

1. pipeline
   
   > 管道技术

#### 2、使用

1. `pipeline`进行执行
   
   ```php
   $redis = redis();
   $pipeline = $redis->pipeline();
   foreach ($data as $key => $_data) {
    $pipeline->setex(sprintf($cacheKeyFormat, $key), $ttl, serialize($_data));
   }
   $pipeline->exec();
   ```

2. 使用`multi`开启`pipeline`模式
   
   ```php
   $redis = redis();
   $redis->multi(Redis::PIPELINE);
   foreach ($data as $key => $_data) {
    $redis->setex(sprintf($cacheKeyFormat, $key), $ttl, serialize($_data));
   }
   $redis->exec();
   ```

#### 3、优缺点

1. 优点
   
   > `pipeline` 通过打包命令，一次性执行，可以节省 `连接->发送命令->返回结果` 所产生的往返时间，减少的`I/O`的调用次数。

2. 缺点
   
   > `pipeline` 每批打包的命令不能过多，因为 `pipeline` 方式打包命令再发送，那么 `redis` 必须在处理完所有命令前先缓存起所有命令的处理结果。这样就有一个内存的消耗。
   > `pipeline` 是责任链模式，这个模式的缺点是，每次它对于一个输入都必须从链头开始遍历(参考`Http Server`处理请求就能明白)，这确实存在一定的性能损耗。
   > `pipeline` 不保证原子性，如果要求原子性的，不推荐使用 `pipeline`

Q、`Pipeline` 对命令数量是否有限制？

> A、没有限制，但是打包的命令不能过多，命令越多对内存的消耗就越大。

Q、`Pipeline` 打包执行多少命令合适？

> A、查询 Redis 官方文档，根据官方的解释，推荐是以 `10k` 每批 (*注意：这个是一个参考值，请根据自身实际业务情况调整)。
> IMPORTANT NOTE: While the client sends commands using pipelining, the server will be forced to queue the replies, using memory. So if you need to send a lot of commands with pipelining, it is better to send them as batches having a reasonable number, for instance 10k commands, read the replies, and then send another 10k commands again, and so forth. The speed will be nearly the same, but the additional memory used will be at max the amount needed to queue the replies for this `10k` commands.
> https://redis.io/topics/pipelining

Q、`Pipeline` 批量执行的时候，是否对`Redis`进行了锁定，导致其他应用无法再进行读写？

> A、`Redis` 采用多路`I/O`复用模型，非阻塞`IO`，所以`Pipeline`批量写入的时候，一定范围内不影响其他的读操作。

#### 4、坑

1. 在使用`pipeline`时，开启之后必须要执行`exec`来完成，否则会影响正常的`redis`执行，类比`MySQL`的事务，两者需成对出现，特别是在`foreach`循环里面需注意成对出现，还有在遇到异常时需执行

2. 在开启`pipeline`后，里面不能再执行普通的`redis`操作，这是因为`redis`是阻塞式，我们可以重新开启一个redis连接来绕过这个问题
