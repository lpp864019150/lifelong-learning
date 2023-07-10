# ClickHouse bitmap函数

## 参考文档

1. [Bitmap算法简介_kongmin_123的博客-CSDN博客](https://blog.csdn.net/kongmin_123/article/details/82222023)

2. [Bitmap函数](https://clickhouse.com/docs/zh/sql-reference/functions/bitmap-functions)

## 函数简读

1. 构建：`bitmapBuild`
   
   > 把无符号整数构建`bitmap`对象
   > 
   > `bitmapBuil(array)` => `bitmapBuild([12,3,4,5])`
   
   ```sql
   SELECT bitmapBuild([1, 2, 3, 4, 5]) AS res
   ```

2. 转换：`bitmapToArray`
   
   > 上面的逆操作，把`bitmap`对象转换为数组
   > 
   > `bitmapToArray(bitmap)`
   
   ```sql
   SELECT bitmapToArray(bitmapBuild([1, 2, 3, 4, 5])) AS res
   
   -- output:
   ┌─res─────────┐
   │ [1,2,3,4,5] │
   └─────────────┘
   ```

3. 判断：`bitmapContains`
   
   > 是否包含某个整数，这里只判断单个整数
   > 
   > `bitmapContains(bitmap, uint)`
   
   类似：`bitmapHasAny(bitmap1, bitmap2)` => 是否有交集；`bitmapHasAll(bitmap1, bitmap2)` => 是否包含，也即子集
   
   ```sql
   SELECT bitmapContains(bitmapBuild([1,5,7,9]), toUInt32(9)) AS res1,
   bitmapHasAny(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res2,
   bitmapHasAll(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res3;
   
   -- output:
   res1|res2|res3|
   ----+----+----+
      1|   1|   0|
   ```

4. 运算：`bitmapAnd`
   
   > 与操作，交集部分
   > 
   > `bitmapAnd(bitmap1, bitmap2)`
   
   类似：`bitmapOr(bitmap1, bitmap2) `=> 并集；`bitmapXor(bitmap1, bitmap2)` => 排除交集部分；`bitmapAndNot(bitmap1, bitmap2)` => 差集
   
   ```sql
   SELECT bitmapToArray(bitmapAnd(bitmapBuild([1,2,3]),bitmapBuild([3,4,5]))) AS res1,
   bitmapToArray(bitmapOr(bitmapBuild([1,2,3]),bitmapBuild([3,4,5]))) AS res2,
   bitmapToArray(bitmapXor(bitmapBuild([1,2,3]),bitmapBuild([3,4,5]))) AS res3,
   bitmapToArray(bitmapAndnot(bitmapBuild([1,2,3]),bitmapBuild([3,4,5]))) AS res4;
   
   -- output:
   res1|res2       |res3     |res4 |
   ----+-----------+---------+-----+
   [3] |[1,2,3,4,5]|[1,2,4,5]|[1,2]|
   ```

5. 聚合：`bitmapCardinality`
   
   > 获取`bitmap`的基数，也即总数
   > 
   > `bitmapCardinality(bitmap)`
   
   类似：`bitmapAndCardinality(bitmap1, bitmap2)` => 交集总数；`bitmapOrCardinality(bitmap1, bitmap2)` => 并集总数；`bitmapXorCardinality(bitmap1, bitmap2)` => 排除并集总数；`bitmapAndNotCardinality(bitmap1, bitmap2)` => 差集总数；`bitmapMin(bitmap)` => 最小值；`bitmapMax(bitmap)` => 最大值
   
   ```sql
   SELECT bitmapCardinality(bitmapBuild([1, 2, 3, 4, 5])) AS res1,
   bitmapMin(bitmapBuild([1, 2, 3, 4, 5])) AS res2,
   bitmapMax(bitmapBuild([1, 2, 3, 4, 5])) AS res3,
   bitmapAndCardinality(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res4,
   bitmapOrCardinality(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res5,
   bitmapXorCardinality(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res6,
   bitmapAndnotCardinality(bitmapBuild([1,2,3]),bitmapBuild([3,4,5])) AS res7;
   
   -- output:
   res1|res2|res3|res4|res5|res6|res7|
   ----+----+----+----+----+----+----+
      5|   1|   5|   1|   5|   4|   2|
   ```

6. 截取：`subBitmap`
   
   > 截取一部分
   > 
   > `subBitmap(bitmap, offset, limit)` => 从`offset`开始，截取多少个元素
   
   类似：`bitmapSubsetInRange(bitmap, range_start, range_end)` => 从`bitmap`里以值来获取上下限；`bitmapSubsetLimit(bitmap, range_start, limit)` => 指定起始值，截取`limit`个
   
   ```sql
   SELECT bitmapToArray(bitmapSubsetInRange(bitmapBuild([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,100,200,500]), toUInt32(30), toUInt32(200))) AS res1,
   bitmapToArray(bitmapSubsetLimit(bitmapBuild([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,100,200,500]), toUInt32(30), toUInt32(200))) AS res2,
   bitmapToArray(subBitmap(bitmapBuild([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,100,200,500]), toUInt32(10), toUInt32(10))) AS res3;
   
   -- output:
   res1                |res2                         |res3                              |
   --------------------+-----------------------------+----------------------------------+
      [30,31,32,33,100]|   [30,31,32,33,100,200,500] |   [10,11,12,13,14,15,16,17,18,19]| 
   ```
