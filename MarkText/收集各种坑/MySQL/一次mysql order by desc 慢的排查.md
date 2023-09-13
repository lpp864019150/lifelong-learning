# 一次mysql order by desc 慢的排查

## 参考资料

1. [一次mysql order by desc 慢的排查 - 掘金](https://juejin.cn/post/6844903874839445517)

> 转载自[一次mysql order by desc 慢的排查 - 掘金](https://juejin.cn/post/6844903874839445517)，以下为原文

前几天帮同事排查了一个sql慢的原因, 觉得有点意思, 这里记录一下

### 问题描述

有这么一个表:

> 备注: 表是我直接复制过来的,但表中的innodb的主键是UUID,其实是不合理的,innodb一般要求主键是单调递增,否则在频繁插入的时候, innodb的B+树会频繁地进行分裂,非常影响性能.

```sql
CREATE TABLE `spider_record` (
     `id` varchar(32) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin NOT NULL,
     `platform_id` int(11) DEFAULT NULL COMMENT '平台推文id',
     `titile` varchar(200) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '标题',
     `description` varchar(800) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '描述',
     `updated_at` datetime DEFAULT NULL COMMENT '更新日期',
     `news_url` varchar(255) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '原文链接',
     `published_at` datetime DEFAULT NULL COMMENT '推送日期',
     `create_by` varchar(32) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
     `create_time` datetime DEFAULT NULL,
     `update_by` varchar(32) CHARACTER SET
         utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
     `update_time` datetime DEFAULT NULL,
     `is_analyze` tinyint(2) DEFAULT '0' COMMENT '是否分析 0否 1是',
     KEY `platform_id_idx` (`platform_id`),
     KEY `create_tm_idx` (`create_time`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
```

表中的数据只有`14万`(算很小的表了), `mysql`的版本是`5.7`, 环境是自己开发的电脑, 中高配(`8G`内存+`SSD`硬盘)

有以下一个`sql(sql1)`, 刚开始执行的时候还比较快, 但当同一个执行了上千次以后, 单次sql的执行时间变得非常的慢, 最慢的可以达到`30多秒`.

```sql
SELECT id,titile,published_at from spider_record 
where is_analyze=0 ORDER BY create_time desc LIMIT  10; // sql1
```

然后如果把`order by`后面的`desc`去掉的话,也就是以下的`sql2`, 执行时间变成`几十毫秒`

```sql
SELECT id,titile,published_at from spider_record 
where is_analyze=0 ORDER BY create_time  LIMIT  10; // sql2
```

所以问题就是:

1. 为什么`14万`数据会查询这么慢, 就算全表扫描也不至于这么慢?
2. 为什么把`desc`去掉后, 就不慢啦?

到网上查找一些类似的问题, 有几种说法:

1. `mysql`没有开启缓存. 但是查看本地( `show variables like '%query_cache%';` )的配置是开了的.
2. `order by`没有走索引. 但是就算没有走索引,也不应该是这么慢.(实际上我们的`sql`是走索引了的, 参见下面的`explain`)

所以网上的解决方案并不适用于我们这里, 只能自己解决

### 问题排查

#### explain

首先看到`sql`执行慢, 第一反应肯定是查看执行计划:

```shell
mysql>  EXPLAIN  SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time desc LIMIT  10; 

+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
| id | select_type | table         | partitions | type  | possible_keys | key           | key_len | ref  | rows | filtered | Extra       | 
+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
|  1 | SIMPLE      | spider_record | NULL       | index | NULL          | create_tm_idx | 6       | NULL |   10 |    10.00 | Using where | 
+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
1 row in set (0.05 sec) 

mysql>  EXPLAIN  SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time LIMIT  10; 
+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
| id | select_type | table         | partitions | type  | possible_keys | key           | key_len | ref  | rows | filtered | Extra       | 
+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
|  1 | SIMPLE      | spider_record | NULL       | index | NULL          | create_tm_idx | 6       | NULL |   10 |    10.00 | Using where | 
+----+-------------+---------------+------------+-------+---------------+---------------+---------+------+------+----------+-------------+ 
1 row in set (0.04 sec)
```

然而, 两个`sql`的执行计划都是一模一样的, `type`都是`"index"`说明是扫描了索引树,`key`是`"create_tm_idx"`说明是扫描了`"create_tm_idx"`的索引树去查找数据. 总之从执行计划来看, `sql`层面没有什么问题,也不至于这么慢.

#### show profiles

既然通过执行计划没看出什么异常, 那么我们就来第二招, show profiles

1. 先执行一下sql, 可以看到整个sql是耗时了10秒多的

```shell
mysql> SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time desc LIMIT  10; 
+----------------------------------+--------------------------------------------+---------------------+ 
| id                               | titile                                     | published_at        | 
+----------------------------------+--------------------------------------------+---------------------+ 
| 980a0aee8ca0eab8c9d7e830ae8dad89 | AT&T明年或收购沃达丰：中移动卷入交易传闻   | 2013-11-01 09:02:05 | 
| 95412ea5c82f4c7878ed9ce59f6ec496 | 58同城上市首日上涨41.88%                   | 2013-11-01 07:59:28 | 
| 93142044d8cfe8ab3ed5c21be2a538b3 | 腾讯联席CTO熊明华离职                      | 2013-11-01 17:02:55 | 
| 896fe75c842ef2c6e28ed9f6a7884873 | 阿里小微金融公布股权架构：马云持股不超7.3% | 2013-11-01 09:23:14 | 
| 851c98dbddc1883d8733b713ea682325 | 手机预装软件今日起“被规范” 谁将受到冲击？  | 2013-11-01 14:22:52 | 
| 85172df9b1c6ee02cb1afcd6ffe2ae66 | 消息称搜狐总编辑刘春将离职                 | 2013-10-14 16:24:28 | 
| 7c3334c97abd81a8631adc69d95b3a25 | 网易筹备游戏海外战略部                     | 2013-11-01 12:24:31 | 
| 63e7dd5a6374052661cf7bb97638e905 | Nexus 5万圣夜低调上线                      | 2013-11-01 07:59:06 | 
| 60f7b46485af71dc375bfd3ae38fd776 | 传Google Hangouts 将整合短信               | 2013-10-09 07:58:55 | 
| 5b0f87cf90c27ed9161693c88951afeb | 黑莓或被Facebook收购：双方高管展开接触     | 2013-10-30 10:12:26 | 
+----------------------------------+--------------------------------------------+---------------------+ 
10 rows in set (10.67 sec)
```

2. 再`show profiles`. `profiles`中已经有了几条`sql`,其中最后一条才是我刚刚执行的

```shell
mysql> show profiles; 
+----------+------------+---------------------------------------------------------------------------------------------------------+ 
| Query_ID | Duration   | Query                                                                                                   | 
+----------+------------+---------------------------------------------------------------------------------------------------------+ 
|        1 | 10.62811400 | SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time desc LIMIT  10 | 
|        2 | 10.78871825 | SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time desc LIMIT  10 | 
|        3 | 0.05494200 | SHOW FULL TABLES WHERE Table_type != 'VIEW'                                                             | 
|        4 | 0.01013250 | SHOW TABLE STATUS                                                                                       | 
|        5 | 0.00034200 | SET profiling = 1                                                                                       | 
|        6 | 10.65613175 | SELECT id,titile,published_at from spider_record where is_analyze=0 ORDER BY create_time desc LIMIT  10 | 
+----------+------------+---------------------------------------------------------------------------------------------------------+ 
6 rows in set (0.07 sec)
```

3.再来看看第`6条sql`的具体执行情况,执行

```shell
mysql> show profile for query 6; 
+----------------------+----------+ 
| Status               | Duration | 
+----------------------+----------+ 
| starting             | 0.000215 | 
| checking permissions | 0.000017 | 
| Opening tables       | 0.000085 | 
| init                 | 0.000013 | 
| System lock          | 0.000020 | 
| optimizing           | 0.000020 | 
| statistics           | 0.000049 | 
| preparing            | 0.000034 | 
| Sorting result       | 0.000010 | 
| executing            | 0.000007 | 
| Sending data         | 10.655393 | 
| end                  | 0.000036 | 
| query end            | 0.000030 | 
| closing tables       | 0.000023 | 
| freeing items        | 0.000151 | 
| cleaning up          | 0.000033 | 
+----------------------+----------+ 
16 rows in set (0.07 sec)
```

通过上面的`profiles`可以知道, 整个`sql`最耗时的就是中间的`sending data`这个阶段了.

所以我们来看一下[官方文档](https://link.juejin.cn?target=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fgeneral-thread-states.html "https://dev.mysql.com/doc/refman/5.7/en/general-thread-states.html")是怎么描述这个状态的:

> The thread is reading and processing rows for a SELECT statement, and sending data to the client.  Because operations occurring during this state tend to perform large amounts of disk access (reads),  it is often the longest-running state over the lifetime of a given query. 当前线程正在读取和处理一个select语句涉及到的行记录, 并发送到客户端.  出现这个状态时一般是由于当前线程正在频繁地访问磁盘(读磁盘), 所以这个状态一般会占据整个查询的生命周期的大部分时间

也就是说,一般的`select`语句都是这个`sending data`占据大部分时间的(虽然说也不应该占`10秒`).
所以通过`profiles`, 我们还是不知道, 为啥这个`sql`会这么慢.

#### innodb_buffer_pool_size

在`explain`和`show profile`都没有找到原因之后, 我极度怀疑`sql`本身是没有问题的. 把注意力放在`mysql`实例的身上.
首先排查的就是`innodb_buffer_pool_size`这个参数

```shell
mysql> show variables like 'innodb_buffer_pool%'; 
+-------------------------------------+----------------+ 
| Variable_name                       | Value          | 
+-------------------------------------+----------------+ 
| innodb_buffer_pool_chunk_size       | 8388608        | 
| innodb_buffer_pool_dump_at_shutdown | ON             | 
| innodb_buffer_pool_dump_now         | OFF            | 
| innodb_buffer_pool_dump_pct         | 25             | 
| innodb_buffer_pool_filename         | ib_buffer_pool | 
| innodb_buffer_pool_instances        | 1              | 
| innodb_buffer_pool_load_abort       | OFF            | 
| innodb_buffer_pool_load_at_startup  | ON             | 
| innodb_buffer_pool_load_now         | OFF            | 
| innodb_buffer_pool_size             | 8388608        | 
+-------------------------------------+----------------+ 
10 rows in set (0.09 sec)
```

查出来一看, `innodb_buffer_pool_size`才`8M`左右? 怎么都说不过去吧.

于是赶紧再看看[mysql官方文档](https://link.juejin.cn?target=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Finnodb-parameters.html%23sysvar_innodb_buffer_pool_size "https://dev.mysql.com/doc/refman/5.7/en/innodb-parameters.html#sysvar_innodb_buffer_pool_size")

> A larger buffer pool requires less disk I/O to access the same table data more than once. On a dedicated database server, you might set the buffer pool size to 80% of the machine's physical memory size. 设置更大的buffer pool可以避免多次重复地从硬盘读取同一个表的数据, 所以可以减少磁盘I\O. 在专业的数据库服务器中, 你可以把buffer_pool_size的大小设置为物理内存的80%

官网都建议说`innodb_buffer_pool_size`应该取物理内存的`80%`了, 那么这里一个`8G`内存的服务器才设置`8m`, 那肯定是不合适的.

所以后来我们把`innodb_buffer_pool_size`设置为`1G`大小, 再执行同样的`sql`, 执行时间就都降到`几十毫秒`了.

问题解决.

### 问题分析

先回顾一下我们的问题, 其实有两个:

1. 为什么`innodb_buffer_pool_size`会导致`sql`执行慢
2. 为什么`innodb_buffer_pool_size`只影响到了降序排序的`sql`

为了回答这个问题, 先来看看`mysql innodb`引擎的几个术语

#### buffer

`buffer` 一般是用于临时存储的一块磁盘空间或内存空间.

1. 数据缓冲在内存中可以提高写的性能, 因为相对于多次写小块数据而言, 大块数据的写入可以减少磁盘的`I/O`次数.

2. 数据缓冲在硬盘中更加可靠, 因为在某些极端情况下,系统崩溃的时候,数据还可以从硬盘中恢复过来.

`innodb`中用到的`buffer`主要有几种,` buffer pool`, `doublewrite buffer`, `change buffer`

#### buffer pool

`buffer pool`是`innodb`存放缓存数据的内存区域, 这些缓存数据包括`innodb`的表和索引.

1. 为了提高并发读的性能, `buffer pool`会被划分为一个个的`page`, 其中每一个`page`可以存放若干行的数据.

2. 为了方便进行缓存管理, `buffer pool`被设计成为一个以`page`为节点的链表.因此一些很少用到的数据,就可以根据`LRU`算法进行淘汰.

3. 在一些内存比较大的系统中, 如果`buffer pool`比较大, 还可以把`buffer pool`划分为多个 `buffer pool instance` 来提高并发度.

因为`buffer pool`的数据位于内存中, 所以当`mysql`实例关闭的时候, `buffer pool`中的数据也会丢失.

当`mysql`实例重启后, 又需要一段漫长的时间
重新把数据预热到`buffer pool`中去(`mysql`官网称之为[warm up](https://link.juejin.cn?target=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Fglossary.html%23glos_warm_up "https://dev.mysql.com/doc/refman/5.7/en/glossary.html#glos_warm_up")).

从`mysql 5.6`开始, 可以通过配置一些系统参数, 在`mysql`关闭的时候, 把`buffer pool`的状态保存下来.而在`mysql`重启以后, 再把`buffer pool`恢复过来.
通过这两个动作, 可以极大地减少`warm up`的时间.

在`mysql5.7`以后, `buffer pool`的自动保存和加载已经成为默认配置

#### innodb_buffer_pool_size

`innodb_buffer_pool_size` 就是`buffer pool`的大小了.
一般来说默认值是`128M`. 在`32位`系统中,最大值为`2的32次方减1字节`. 在`64位`系统中,最大值为`2的64次方减1字节`.

因为`buffer pool`作为是`innodb`的数据和索引的缓存, 在物理内存无限大的情况下, `buffer pool`的也是越大越好. 但是`mysql`只是建议你最多用到物理内存的`80%,` 主要是出于以下几点考虑:

1. 跟操作系统竞争内存, 有可能会导致操作系统的频繁缺页, 导致整个机器的性能下降
2. `mysql`会为它的一些其它数据结构保留部分内存, 所以实际占用内存会比`buffer pool`多个`10%`左右
3. `buffer pool` 一般需要分配连续内存, 否则在`windows`操作系统中会有一些问题
4. `buffer pool`的初始化时间是跟它的大小成正比的

#### clustered index 和 secondary index

`clustered index`就是聚集索引. 一般是指主键的索引; `secondary index`辅助索引就是指普通的索引.

聚集索引和辅助索引都是一颗`B+树`.而且非叶节点都不存储实际数据. 数据都存在叶节点中.而且每一个叶节点都有一个指针指向下一个叶节点和前一个叶节点. 也就是说所有的叶节点会组成一个双向链表,可以用于范围查询

聚集索引是以主键(也可能是唯一键)作为索引, 叶节点存储了这个主键对应的行的所有数据.所以可以通过主键直接查到这行的任何一列数据

辅助索引是以索引的列作为索引, 叶节点存储了索引列的数据和对应的主键. 所以如果`select`中的列就是索引中的一列的话, 直接在叶节点中就能查到数据. 这就是"索引覆盖".
但是如果`select`语句中的列不是索引中的一列的话, 就必须在叶节点中查到主键, 再用主键去查到对应列的数据, 这就是"回表"

在`mysql8.0`之前, `innodb`的所有的索引树都是升序的, 虽然创建索引的时候可以指定是`asc`还是`desc`, 但是实际创建索引的时候, 都是`asc`的. 直到`mysql8.0`中, 索引才支持`desc`

好了, 说了这一大堆之后, 开始来回答一下开始的两个问题

1. 为什么`innodb_buffer_pool_size`会导致`sql`执行慢
   
   这个是很明显的, 当`buffer pool`不够用的时候, 大多数的数据请求都会落到磁盘数, 磁盘`IO`性能会比内存读取高出很多个数量级

2. 为什么`innodb_buffer_pool_size`只影响到了降序排序的`sql`
   
   这个问题其实我也没有很确切的答案. 只能利用现有的条件做一下推测.
   
   首先我们知道`create_time_idx`是一颗升序的`B+树`, 数据都存放在叶节点中, 其中`create_time`较小的一般集中于树的左边, `create_time`较大的一般集中于树的右边.
   
   当`sql`根据升序排序时, `mysql`需要找到这颗`B+树`的最左叶节点, 然后利用叶节点的双向链表直接遍历`10`条符合条件(`is_analyze=0`)的即可.
   
   当`sql`根据降序排序时, `mysql`需要找到这颗`B+树`的最右叶节点, 然后利用叶节点的双链表直接遍历`10`条符合条件(`is_analyze=0`)的数据即可.
   
   另外,由于`B+树`的内部节点一般都有成千上万个指针, 找最右叶节点时一般都要遍历大部分这些内部节点的指针, 而找最左叶节点时相对遍历的指针会比较少一点.
   所以找到最右叶节点会比找到最左叶节点相对耗时一点
   
   找到最左最右叶节点后, 接下来就是要遍历出`10`条符合条件(`is_analyze=0`)的数据了, 这个时候就要看目标数据主要分布在哪了. 如果数据分布在左边, 那么倒序排序(从最右叶节点开始遍历)就需要遍历更多的叶节点,导致`buffer pool`不够用, 最终需要进行`磁盘IO`, 导致性能下降. 而如果数据分布在右边的话, 查询性能就会好很多.
   
   纯文字描述会比较乱,也不好理解 可以参考一下[这篇文章](https://link.juejin.cn?target=https%3A%2F%2Fwww.cnblogs.com%2Fwy123%2Fp%2F7003157.html "https://www.cnblogs.com/wy123/p/7003157.html")的两张图. 图中目标的数据就是分布在"右边", 所以反向扫描的话, 可以很快就能扫描到
   
   1. 正向扫描
      
      ![image](https://p1-jj.byteimg.com/tos-cn-i-t2oaga2asx/gold-user-assets/2019/6/26/16b9203b77b781e8~tplv-t2oaga2asx-jj-mark:3024:0:0:0:q75.awebp)
   
   2. 反向扫描
      
      ![image](https://p1-jj.byteimg.com/tos-cn-i-t2oaga2asx/gold-user-assets/2019/6/26/16b9203af1207dfa~tplv-t2oaga2asx-jj-mark:3024:0:0:0:q75.awebp)

下面来证明一下我的推测.

首先来看一下这个表中的`create_time`的分布都是怎么样. 可以看到`create_time`只有四天的数据, 其中主要都是在前两天(`b+树`的左边)

```shell
mysql> SELECT date(create_time) , count(0) from spider_record GROUP BY date(create_time); 
+-------------------+----------+ 
| date(create_time) | count(0) | 
+-------------------+----------+ 
| 2019-06-17        |       52 | 
| 2019-06-18        |   141042 | 
| 2019-06-19        |      100 | 
| 2019-06-20        |       55 | 
+-------------------+----------+ 
4 rows in set (0.18 sec)
```

再看看`is_analyze=0`的数据分布, 也的确是都分布在`b+树`"左边"

```shell
mysql> SELECT date(create_time) , count(0) from spider_record where is_analyze=0 GROUP BY date(create_time); 
+-------------------+----------+ 
| date(create_time) | count(0) | 
+-------------------+----------+ 
| 2019-06-18        |   141042 | 
| 2019-06-20        |       55 | 
+-------------------+----------+ 
2 rows in set (0.18 sec)
```

然后, 我们把`6月18日`的数据的`create_time`都增加两天

```sql
update 
    spider_record 
set 
    create_time = create_time + INTERVAL 2 day 
where 
    date(create_time) = '2019-06-18';
```

最后查看数据分布, 现在不管是所有数据, 还是`is_analyze=0`的数据, 都是大部分分布在"右边"了

```shell
mysql> SELECT date(create_time) , count(0) from spider_record GROUP BY date(create_time); 
+-------------------+----------+ 
| date(create_time) | count(0) | 
+-------------------+----------+ 
| 2019-06-17        |       52 | 
| 2019-06-19        |      100 | 
| 2019-06-20        |   141097 | 
+-------------------+----------+ 
3 rows in set (0.28 sec) 

mysql> SELECT date(create_time) , count(0) from spider_record where is_analyze=0 GROUP BY date(create_time); 
+-------------------+----------+ 
| date(create_time) | count(0) | 
+-------------------+----------+ 
| 2019-06-20        |   140683 | 
| 2019-06-17        |       52 | 
+-------------------+----------+ 
2 rows in set (0.92 sec)
```

最后再来看看这个`sql`的执行耗时(我在`navicat`里面连续执行`1000多次`), 如图. 时间都在几毫秒内了...

![image](https://p1-jj.byteimg.com/tos-cn-i-t2oaga2asx/gold-user-assets/2019/6/26/16b9203af11b95dc~tplv-t2oaga2asx-jj-mark:3024:0:0:0:q75.awebp)

考虑到有可能是`mysql`的状态变了, 或其他缓存的原因造成的查询变快了, 我又把`create_time`为`6月20日`的数据改成`6月18`, 即执行下面`sql`

```sql
update 
    spider_record 
set 
    create_time = create_time - INTERVAL 2 day 
where 
    date(create_time) = '2019-06-20';
```

然后再来看看那个`sql`的执行耗时(也是在`navicat`里面连续执行`1000多次`), 结果如图, 果然是慢了很多...

![image](https://p1-jj.byteimg.com/tos-cn-i-t2oaga2asx/gold-user-assets/2019/6/26/16b9203af156b322~tplv-t2oaga2asx-jj-mark:3024:0:0:0:q75.awebp)

### 总结

其实这个问题共性应该不是很大, 因为一般生产环境的`innodb_buffer_pool_size`是绝对不会配置成`8M`的.

另外上面提到的数据分布在"左边"和"右边"的说法, 其实有点牵强, 因为`B+树`毕竟是一颗平衡树, 都不存在什么偏向左边偏向右边的说法.(然而实验结果支持了这个说法, 我也很绝望~~)

但是, 从这一次问题排查中, 我们至少可以很确定地认识到了2点:

1. `innodb_buffer_pool_size`的参数一定不要配置得太小,否则会极大地影响`mysql`的性能.

2. 一定要注意`order by desc`. 虽然在`mysql 8.0`已经支持了降序索引. 但是如果你的索引是升序的而`order by`又指定`desc`的话. `mysql`查询计划的`extra中`还是会给你指出这个`sql`会进行`"Backward index scan"`(如下图), 让你注意到它用了反向扫描.

![image](https://p1-jj.byteimg.com/tos-cn-i-t2oaga2asx/gold-user-assets/2019/6/26/16b9203af14a5310~tplv-t2oaga2asx-jj-mark:3024:0:0:0:q75.awebp)

说明反向扫描还是会比正向扫描相对耗时.所以如果`sql`中能避免反向扫描的话, 最好还是避免反向扫描。