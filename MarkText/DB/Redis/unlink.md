# unlink

## 参考博文

1. [UNLINK | Redis](https://redis.io/commands/unlink/)

2. [Redis删除键命令: 入门用del，老手用unlink，有何区别？_Java`纯鹿人的博客-CSDN博客](https://blog.csdn.net/CXikun/article/details/130243370)

3. [深入理解redis的一个del和unlink的命令的执行过程-1-腾讯云开发者社区-腾讯云](https://cloud.tencent.com/developer/article/1987008)

## 命令

```shell
UNLINK key [key ...]
```

> `unlink`和`del`的作用一样，都是删除`key`，区别在于他不会立马执行删除，而是先放入一个删除列表立马返回，接下来由其他线程异步执行，这样就不会阻塞主线程，在删除大`key`或者大量`key`时效果明显。而且他会回收内存占用，建议线上优先考虑使用`unlink`
> 
> Redis 4.0提供的功能
