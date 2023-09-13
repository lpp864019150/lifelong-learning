# ICP索引下推

## 参考资料

1. [MySQL索引下推需要了解下 - 掘金](https://juejin.cn/post/7151422702585118734)
2. [MySQL索引覆盖与索引下推 - 掘金](https://juejin.cn/post/7084536257857978404)

## 术语

1. `ICP`
   
   `Index Condition Pushdown`，索引下推。作用于复合索引，根据最左前缀原则，该次查询只能走索引的一部分字段，但是`where`条件里还有该索引的其他字段，为了减少回表，先在复合索引里把涉及到该索引的过滤的字段都拿出来判断过滤一遍，最后再进行回表(如果需要的话)，相较于之前的查询逻辑可以减少一些回表操作，提升了查询性能。
   
   `MySQL5.6`新增的索引优化功能
   
   `Extra`字段为`using index condition`
   
   减少回表；减少上推到`Server`层的数据

2. 回表
   
   利用索引无法完全得到整条`SQL`的结果，需要通过索引里的主键`id`回到主键索引里去取数据，然后再进行`where`条件过滤或者补齐`select`字段，回到主键索引里取数据就是回表操作。
   
   每个主键`ID`都需要一次回表操作，每次回表都极有可能是随机`IO`

3. 覆盖索引
   
   `Index Cover`索引覆盖。直接使用某个索引就可以完全得到`SQL`的结果，无需回表，提升查询性能。
   
   `Extra`字段为`using index`
   
   避免回表；避免随机`IO`

## 没有ICP的查询步骤

1. 存储引擎读取索引记录；
2. 根据索引中的主键值，定位并读取完整的行记录；
3. 存储引擎把记录交给`Server`层去检测该记录是否满足`WHERE`条件。

## 有ICP的查询步骤

1. 存储引擎读取索引记录（不是完整的行记录）；
2. 判断`WHERE`条件部分能否用索引中的列来做检查，条件不满足，则处理下一行索引记录；
3. 条件满足，使用索引中的主键去定位并读取完整的行记录（就是所谓的回表）；
4. 存储引擎把记录交给`Server`层，`Server`层检测该记录是否满足`WHERE`条件的其余部分。

## 索引下推限制

根据[官网](https://link.juejin.cn?target=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F8.0%2Fen%2Findex-condition-pushdown-optimization.html "https://dev.mysql.com/doc/refman/8.0/en/index-condition-pushdown-optimization.html")可知，索引下推 **受以下条件限制：**

1. 当需要访问整个表行时，`ICP` 用于 `range`、 `ref`、 `eq_ref` 和 `ref_or_null`

2. `ICP`可以用于 `InnoDB` 和 `MyISAM` 表，包括分区表 `InnoDB` 和 `MyISAM` 表。

3. 对于 `InnoDB` 表，`ICP` 仅用于二级索引。`ICP` 的目标是减少全行读取次数，从而减少 `I/O` 操作。对于 `InnoDB` 聚集索引，完整的记录已经读入 `InnoDB` 缓冲区。在这种情况下使用 `ICP` 不会减少 `I/O`。

4. 在虚拟生成列上创建的二级索引不支持 `ICP`。`InnoDB` 支持虚拟生成列的二级索引。

5. 引用子查询的条件不能下推。

6. 引用存储功能的条件不能被按下。存储引擎不能调用存储的函数。

7. 触发条件不能下推。

8. 不能将条件下推到包含对系统变量的引用的派生表。（`MySQL 8.0.30` 及更高版本)。