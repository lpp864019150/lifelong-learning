# 一条SQL差点引发离职

## 参考资料

1. [一条SQL差点引发离职 - 掘金](https://juejin.cn/post/7275550679790960640)

## 坑

```sql
update db_name set selling_price = xx,sell_type = xx where id = 0;
```

其中`id`由`uuid`生成，为字符串，执行时我们使用了`0`，整型，此时`MySQL`会进行类型转换，当两边的类型不一致时，其中一个为数字，则另外一个也转换为数字再进行比较

## 解决

严格按照字段类型来格式化参数，把上面`SQL`的`id=0`改成`id='0'`即可

## MySQL官方对于隐式转换的说明

1. 两个参数至少有一个是 `NULL`时，比较的结果也是` NULL`，例外是使用` <``=``>` 对两个 `NULL` 做比较时会返回 `1`，这两种情况都不需要做类型转换；
   
   也就是两个参数中如果只有一个是`NULL`，则不管怎么比较结果都是 `NULL`，而两个 `NULL` 的值不管是判断大于、小于或等于，其结果都是`1`。

2. 两个参数都是字符串，会按照字符串来比较，不做类型转换；
3. 两个参数都是整数，按照整数来比较，不做类型转换；
4. 十六进制的值和非数字做比较时，会被当做二进制字符串；
5. 有一个参数是 `TIMESTAMP` 或 `DATETIME`，并且另外一个参数是常量，常量会被转换为 时间戳；
6. 有一个参数是 `decimal` 类型，如果另外一个参数是 `decimal` 或者整数，会将整数转换为 `decimal` 后进行比较，如果另外一个参数是浮点数（一般默认是 `double`），则会把 `decimal` 转换为浮点数进行比较；
7. 所有其他情况下，两个参数都会被转换为浮点数再进行比较；