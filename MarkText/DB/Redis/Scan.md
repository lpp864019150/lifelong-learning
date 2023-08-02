# Scan

## 参考博文

1. [scan 命令 -- Redis中国用户组（CRUG）](http://redis.cn/commands/scan.html)

## 使用

```shell
SCAN cursor [MATCH pattern] [COUNT count] [TYPE type]
```

1. `cursor`游标从`0`开始，每次获取时会返回下一次迭代的游标，下一次请求时需带上相应的游标

2. `Scan`会遍历所有`keys`，从头到尾逐个遍历

3. 若使用了`MATCH`模式，该模式作用于遍历结果和输出到客户端之间，也即先获取`keys`，然后再执行`MATCH`，这样就极有可能当次迭代无匹配值，这里和`Keys`命令有差异

4. 当遍历完之后，`cursor`游标值返回`0`，也即又回到了开头，此时可以认定已经完整遍历

5. 若需要一次获取更多`keys`，可以使用`COUNT`

6. `TYPE`参数为`6.0.0`以上版本才支持

## 用法

```php
$redis = redis();
$iterator = null;
$match = "ba:*";
$count = 100;
while(false !== ($keys = $redis->scan($iterator, $match, $count))) {
    foreach($keys as $key) {
        echo $key . PHP_EOL;
    }
}
```
