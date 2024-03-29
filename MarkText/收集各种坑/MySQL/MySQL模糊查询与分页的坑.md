# MySQL模糊查询与分页的坑

## 参考博文

1. [分页 + 模糊查询 有坑！ - 掘金](https://juejin.cn/post/7143077564810592287)

## 坑

1. like不一定把精准匹配的值放在第一位，需要遍历所有页码才知道是否存在精准匹配值

## 解决方案

1. 加大页码，把第一页的结果集拉大，精准匹配极有可能就会出现在第一页，也可能一页就包括了所有结果集。但是依然存在结果集非常大，精准匹配值在第二页的情况。

2. 分两次查询，一次精准匹配，放在第一页；一次排除精准匹配的模糊匹配。需要两次查询，且需对代码侵入。

3. 对字段进行排序，把精准匹配排在最前面，这里可以使用字符长度来排序，char_length，配置匹配字符在结果集的位移可以把匹配值在字符前面的结果集排在前面，这里可使用locate、instr、position等函数
