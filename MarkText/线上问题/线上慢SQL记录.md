# 线上慢SQL记录

#### 1、背景

1. 线上`MySQL`版本`5.6.16-log`，所以下面的慢查询都是基于该版本出现的，其他版本并不一定能复现

2. 时刻关注线上慢查询，把能优化的优化掉，避免`MySQL`出现性能问题进而导致线上各业务线出现卡慢无法访问

3. 解决慢查询的过程也是学习知识巩固知识的过程，把这些案例记录下来方便以后查看学习并在以后的工作中避免出现此类慢查询

4. 对`SQL`进行`explain`是一种美德

#### 2、案例分析

##### 1.  案例一

###### 1. 慢SQL

```sql
SELECT count(*) AS aggregate
FROM `user_activity_reward`
WHERE `received` = '0'
    AND `user_task_id` = '2731940';
```

###### 2. 分析

1. 有一个单索引 `received` ，可以走索引扫描，但是该字段的区分度非常低，效果不佳
   
   ```
   id|select_type|table               |type|possible_keys|key     |key_len|ref  |rows |Extra      |
   --+-----------+--------------------+----+-------------+--------+-------+-----+-----+-----------+
   1|SIMPLE     |user_activity_reward|ref |received     |received|1      |const|43600|Using where|
   ```

2. 阿里云给出的方案是建一个符合索引，`user_task_id, received`

3. `user_task_id` 为`user_task`表的主键，区分度非常高，可以选择在该字段上面创建一个索引，`received`的区分度太低，建索引的意义不大

###### 3. 解决

1. 增加 `user_task_id` 单索引
   
   ```sql
   ALTER TABLE `game_box`.`user_activity_reward`  algorithm=inplace,lock=none, 
   ADD INDEX `index_user_task_id` (`user_task_id`);
   ```

2. `explain` 分析，只扫描一行即可
   
   ```
   id|select_type|table               |type|possible_keys              |key               |key_len|ref  |rows|Extra      |
   --+-----------+--------------------+----+---------------------------+------------------+-------+-----+----+-----------+
   1|SIMPLE     |user_activity_reward|ref |received,index_user_task_id|index_user_task_id|4      |const|   1|Using where|
   ```

3. 速度提升了几万倍

###### 4. 总结

1. 需要在区分度高的常用字段上创建索引，区分度不高的字段即使用上了索引效果也不佳

##### 2. 案例二

###### 1. SQL

```sql
select * from `user_task` order by `complete_time` desc limit 20 offset 0
```

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table    |type|possible_keys|key|key_len|ref|rows   |Extra         |
   --+-----------+---------+----+-------------+---+-------+---+-------+--------------+
   1|SIMPLE     |user_task|ALL |             |   |       |   |2868636|Using filesort|
   ```

2. 从上面的`explain`可以看出，并未使用索引，而是选择了全表扫描，并且使用了临时表进行排序，这是一个比较糟糕的分析结果

3. 由于这个`SQL`只有一个 `complete_time` 字段，很自然的就想到可以在该字段上创建一个单索引

###### 3. 解决

1. 创建一个单索引
   
   ```sql
   ALTER TABLE `game_box`.`user_task` algorithm=inplace,lock=none, 
   ADD INDEX `idx_completetime` (`complete_time`);
   ```

2. `explain`
   
   ```
   id|select_type|table    |partitions|type |possible_keys|key             |key_len|ref|rows|filtered|Extra|
   --+-----------+---------+----------+-----+-------------+----------------+-------+---+----+--------+-----+
   1|SIMPLE     |user_task|          |index|             |idx_completetime|4      |   |  20|   100.0|     |
   ```

3. 由于在管理后台还有其他组合查询的存在，即使加了单索引也可以需要扫描非常多数据，这里建议管理后台可以增加时间查询，比如默认查询7天、1个月内的数据，尽量减少最后的结果集

###### 4. 总结

1. 管理后台，若数据量比较大，按哪个字段排序比较多，则优先在该字段建索引，避免出现临时表排序
2. 管理后台，若数据量比较大，一般需增加一个时间筛选，默认搜索最近几天的数据，在该字段建一个索引

##### 3. 案例三

###### 1. SQL

```sql
SELECT
    user_id,
    activity_id,
    sum(IF(sign = 2, points, 0)) AS add_points,
    sum(IF(sign = 1, points, 0)) AS sub_points,
    sum(IF(sign = 2, points, -points)) AS balance
FROM
    `activity_user_point_flow`
GROUP BY
    `user_id`,
    `activity_id`
HAVING
    user_id > 0
ORDER BY
    `user_id` DESC
LIMIT 20 offset 0
```

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table                   |type|possible_keys    |key|key_len|ref|rows   |Extra                          |
   --+-----------+------------------------+----+-----------------+---+-------+---+-------+-------------------------------+
   1|SIMPLE     |activity_user_point_flow|ALL |idx_activity_user|   |       |   |3102716|Using temporary; Using filesort|
   ```

2. 这里出现了`possible_keys`，分析下为什么没有被用上
   
   ```sql
   KEY `idx_activity_user` (`activity_id`,`user_id`),
   ```

3. 分组和排序以`user_id`为前缀，单纯考虑索引的话，可以把上面的索引改成 `user_id, activity_id`
   
   ```sql
   create index idx_user_activity_id on game_box.activity_user_point_flow(user_id,activity_id);
   ```
   
   ```
   id|select_type|table                   |partitions|type|possible_keys                         |key|key_len|ref|rows  |filtered|Extra                          |
   --+-----------+------------------------+----------+----+--------------------------------------+---+-------+---+------+--------+-------------------------------+
   1|SIMPLE     |activity_user_point_flow|          |ALL |idx_activity_user,idx_user_activity_id|   |       |   |115277|   100.0|Using temporary; Using filesort|
   ```

4. 增加了索引，依然无法用到索引，还是走了全表扫描

5. 回到`SQL`，有一个`having`，但是过滤的是单个字段，非聚合，可以考虑把该语句提前到`where`
   
   ```sql
   SELECT
     user_id,
     activity_id,
     sum(IF(sign = 2, points, 0)) AS add_points,
     sum(IF(sign = 1, points, 0)) AS sub_points,
     sum(IF(sign = 2, points, -points)) AS balance
   FROM
     `activity_user_point_flow`
   WHERE
     user_id > 0
   GROUP BY
     `user_id`,
     `activity_id`
   ORDER BY
     `user_id` DESC
   LIMIT 20 offset 0
   ```
   
   ```
   id|select_type|table                   |partitions|type |possible_keys                         |key                 |key_len|ref|rows |filtered|Extra                                                 |
   --+-----------+------------------------+----------+-----+--------------------------------------+--------------------+-------+---+-----+--------+------------------------------------------------------+
   1|SIMPLE     |activity_user_point_flow|          |range|idx_activity_user,idx_user_activity_id|idx_user_activity_id|4      |   |57638|   100.0|Using index condition; Using temporary; Using filesort|
   ```

6. 使用到了索引，但仅仅是过滤，分组和排序还是用到了临时表，效果依然不佳

7. 继续优化`SQ`L，观察到`group by`和`order by`不一致，`order by`缺少了`activity_id`，两个字段的排序需保持一致，皆为`desc`或`asc`
   
   ```sql
   SELECT
     user_id,
     activity_id,
     sum(IF(sign = 2, points, 0)) AS add_points,
     sum(IF(sign = 1, points, 0)) AS sub_points,
     sum(IF(sign = 2, points, -points)) AS balance
   FROM
     `activity_user_point_flow`
   WHERE
     user_id > 0
   GROUP BY
     `user_id`,
     `activity_id`
   ORDER BY
     `user_id` DESC,
     `activity_id` DESC
   LIMIT 20 offset 0
   ```
   
   ```
   id|select_type|table                   |partitions|type |possible_keys                         |key                 |key_len|ref|rows |filtered|Extra                |
   --+-----------+------------------------+----------+-----+--------------------------------------+--------------------+-------+---+-----+--------+---------------------+
   1|SIMPLE     |activity_user_point_flow|          |range|idx_activity_user,idx_user_activity_id|idx_user_activity_id|4      |   |57638|   100.0|Using index condition|
   ```

8. 到此`SQL`已经优化好了，原查询时间`12s+`，优化后执行几十毫秒

9. 当然也可以考虑换一种思路，利用代码逻辑来解决，分多次`SQL`查询，每次`SQL`都命中索引则总时间必定会减少，先筛选出 `user_id, activity_id` 再分别进行查询统计
   
   ```sql
   SELECT
     user_id,
     activity_id
     -- ,sum(IF(sign = 2, points, 0)) as add_points,sum(IF(sign = 1, points, 0)) as sub_points,sum(IF(sign = 2, points, -points)) as balance 
   FROM
     `activity_user_point_flow`
     -- where user_id > 0 
   GROUP BY
     `user_id`,
     `activity_id`
     -- having user_id > 0 
   ORDER BY
     `user_id` DESC,
     activity_id DESC
   LIMIT 20 offset 0;
   ```

###### 3. 解决

1. 加索引

```sql
create index idx_user_activity_id on game_box.activity_user_point_flow(user_id,activity_id);
```

2. 改造`SQL`

```sql
-- 把having的单字段过滤提前到where
-- 把order by加上完整的索引，并且保持索引顺序

SELECT
 user_id,
 activity_id,
 sum(IF(sign = 2, points, 0)) AS add_points,
 sum(IF(sign = 1, points, 0)) AS sub_points,
 sum(IF(sign = 2, points, -points)) AS balance
FROM
 `activity_user_point_flow`
WHERE
 user_id > 0
GROUP BY
 `user_id`,
 `activity_id`
ORDER BY
 `user_id` DESC,
 `activity_id` DESC
LIMIT 20 offset 0;
```

###### 4. 总结

1. 遇上慢查询，第一反应是看看是否用到了索引
2. 是否有可能使用到的索引，可否通过改造`SQL`来使用该索引
3. 若不行，则考虑增加一个合适的索引
4. 若依然不行，则看看是否需要改造`SQL`去使用索引
5. 另外可以考虑把一次`SQL`改成多次`SQL`
6. 若依然不行，则考虑功能设计是否合理，是否需要修改产品逻辑

##### 4. 案例四

###### 1. SQL

```sql
SELECT *
FROM `user_task`
WHERE ``.`user_id` LIKE '%31836375%'
    AND ``.`activity_id` IN ('30')
ORDER BY `complete_time` DESC
LIMIT 0, 20
```

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table    |type|possible_keys          |key                    |key_len|ref  |rows   |Extra                      |
   --+-----------+---------+----+-----------------------+-----------------------+-------+-----+-------+---------------------------+
   1|SIMPLE     |user_task|ref |activity_setting_detail|activity_setting_detail|4      |const|1435115|Using where; Using filesort|
   ```

2. 使用到了索引，但是索引的区分度和过滤效果不佳
   
   ```sql
   KEY `activity_setting_detail` (`activity_id`,`activity_settings_id`,`detail_id`(191)),
   ```

3. `user_id`这种字段，一般无需模糊匹配，模糊匹配无法使用到索引，可以改成完全匹配

4. 观察到改表是存在 `user_id` 索引的
   
   ```sql
   KEY `user_id` (`user_id`),
   ```

###### 3. 解决

1. `like` 改成 `=`
   
   ```sql
   SELECT *
   FROM `user_task`
   WHERE ``.`user_id` = '31836375'
     AND ``.`activity_id` IN ('30')
   ORDER BY `complete_time` DESC
   LIMIT 0, 20
   ```

2. `explain`
   
   ```
   id|select_type|table    |type|possible_keys                  |key    |key_len|ref  |rows|Extra                      |
   --+-----------+---------+----+-------------------------------+-------+-------+-----+----+---------------------------+
   1|SIMPLE     |user_task|ref |activity_setting_detail,user_id|user_id|4      |const|  45|Using where; Using filesort|
   ```

###### 4. 总结

1. 尽量少用`like`，尤其是有索引的字段，会使索引失效，必须要用的话，不用使用 `%` 开头

##### 5. 案例五

###### 1. SQL

```sql
SELECT *
FROM `user_activity_reward`
WHERE ``.`reward_type` = '1'
    AND ``.`activity_id` IN ('27')
ORDER BY `user_activity_reward`.`id` DESC
LIMIT 0, 20;
```

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table               |type |possible_keys    |key    |key_len|ref|rows|Extra      |
   --+-----------+--------------------+-----+-----------------+-------+-------+---+----+-----------+
   1|SIMPLE     |user_activity_reward|index|idx_activity_user|PRIMARY|4      |   | 130|Using where|
   ```

2. 走的主键索引，进行了全索引扫描，效率不高

3. 这里可以考虑增加时间过滤，把结果集降下来

###### 3. 解决

1. 增加一个时间筛选

2. 并在该字段增加索引
   
   ```sql
   ALTER table user_activity_reward algorithm=inplace,lock=none,
   ADD INDEX index_receive_time(receive_time);
   ```

###### 4. 总结

1. 后台管理，在数据量大了之后就尽量不要再全表搜索了，默认加上一个时间筛选，把结果集降下来
2. 在该时间字段上建立单索引，后台管理搜索条件比较复杂，有些`SQL`可能索引选择上会出现误差，可以考虑强制索引

##### 6. 案例六

###### 1. SQL

```sql
-- 这里in有非常多的id，做了简化
SELECT `coupon_detail`.*, `user_order_extra`.`order_no`, `user_order_extra`.`created_at` AS `order_time`
FROM `coupon_detail`
  LEFT JOIN `user_order_extra`
  ON `user_order_extra`.`scene_id` = `coupon_detail`.`id`
    AND `user_order_extra`.`type` = '2'
    AND `user_order_extra`.`is_use` = '1'
WHERE `coupon_detail`.`group_id` IN (
    '11632', 
    '11642', 
    ...
  )
  AND `coupon_detail`.`created_at` BETWEEN '2023-01-21' AND '2023-01-23 23:59:59'
GROUP BY `coupon_detail`.`id`
ORDER BY `coupon_detail`.`id` DESC
LIMIT 37050, 50
```

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table           |type |possible_keys                                                                                                          |key           |key_len|ref                              |rows    |Extra      |
   --+-----------+----------------+-----+-----------------------------------------------------------------------------------------------------------------------+--------------+-------+---------------------------------+--------+-----------+
   1|SIMPLE     |coupon_detail   |index|PRIMARY,code,account_id,group_id,config_id,created_at,app_type,status_start_end,push_id,index_group_end_at,activity_idx|PRIMARY       |4      |                                 |14992973|Using where|
   1|SIMPLE     |user_order_extra|ref  |type_scene_use                                                                                                         |type_scene_use|6      |const,game.coupon_detail.id,const|       1|           |
   ```

2. 可选索引
   
   ```sql
   PRIMARY KEY (`id`) USING BTREE,
   UNIQUE KEY `code` (`code`) USING BTREE,
   KEY `account_id` (`account_id`) USING BTREE,
   KEY `group_id` (`group_id`) USING BTREE,
   KEY `config_id` (`config_id`) USING BTREE,
   KEY `created_at` (`created_at`) USING BTREE,
   KEY `app_type` (`config_give_app`,`config_give_type`) USING BTREE,
   KEY `status_start_end` (`status`,`group_start_at`,`group_end_at`) USING BTREE,
   KEY `push_id` (`push_id`),
   KEY `index_group_end_at` (`group_end_at`),
   KEY `activity_idx` (`activity_config_id`) USING BTREE
   ```

3. 上面的`SQL`有用到`group_id`, `created_at`，但是`explain`出来走的是主键索引

4. 可以试着强制使用其中一个索引

5. 经测试，效果不佳都要`2s`左右

6. 观察到上面的分页使用到了大偏移量，考虑从这个地方出发，利用主键快速定位到偏移量位置，避免全表扫描

###### 3. 解决

1. 需要在代码上进行配合，在每次分页时带上上次最后一个`id`，快速定位到分页起始位置
   
   ```sql
   SELECT `coupon_detail`.*, `user_order_extra`.`order_no`, `user_order_extra`.`created_at` AS `order_time`
   FROM `coupon_detail`
   LEFT JOIN `user_order_extra`
   ON `user_order_extra`.`scene_id` = `coupon_detail`.`id`
     AND `user_order_extra`.`type` = '2'
     AND `user_order_extra`.`is_use` = '1'
   WHERE `coupon_detail`.`group_id` IN (
     '11632', 
     '11642', 
     ...
   )
   AND `coupon_detail`.`created_at` BETWEEN '2023-01-21' AND '2023-01-23 23:59:59'
   AND `coupon_detail`.`id`<21064836
   GROUP BY `coupon_detail`.`id`
   ORDER BY `coupon_detail`.`id` DESC
   LIMIT 50
   ```

2. 强制使用时间作为索引，经过测试，发现使用`created_at`作为索引效果最佳
   
   ```sql
   SELECT `coupon_detail`.*, `user_order_extra`.`order_no`, `user_order_extra`.`created_at` AS `order_time`
   FROM `coupon_detail` force index(created_at)
   LEFT JOIN `user_order_extra`
   ON `user_order_extra`.`scene_id` = `coupon_detail`.`id`
     AND `user_order_extra`.`type` = '2'
     AND `user_order_extra`.`is_use` = '1'
   WHERE `coupon_detail`.`group_id` IN (
     '11632', 
     '11642', 
     ...
   )
   AND `coupon_detail`.`created_at` BETWEEN '2023-01-21' AND '2023-01-23 23:59:59'
   AND `coupon_detail`.`id`<21064836
   GROUP BY `coupon_detail`.`id`
   ORDER BY `coupon_detail`.`id` DESC
   LIMIT 50
   ```

3. `explain`
   
   ```
   id|select_type|table           |type |possible_keys                                                                                                          |key           |key_len|ref                              |rows  |Extra                                                              |
   --+-----------+----------------+-----+-----------------------------------------------------------------------------------------------------------------------+--------------+-------+---------------------------------+------+-------------------------------------------------------------------+
   1|SIMPLE     |coupon_detail   |range|PRIMARY,code,account_id,group_id,config_id,created_at,app_type,status_start_end,push_id,index_group_end_at,activity_idx|created_at    |9      |                                 |715958|Using index condition; Using where; Using temporary; Using filesort|
   1|SIMPLE     |user_order_extra|ref  |type_scene_use                                                                                                         |type_scene_use|6      |const,game.coupon_detail.id,const|     1|                                                                   |
   ```

4. 可以看到，强制`created_at`索引后预估扫描的行数从`1500w`降到了`70w`，加上`id`过滤后可以提前锁定起始值，减少了扫描到大偏移量的时间

5. 亮点：连表时使用了小表驱动大表，在被驱动表字段加了索引，`ON`谓词里提前加上被驱动表的过滤，可以有效减少连表后的虚拟表数量

###### 4. 总结

1. 【书签页方法】遇到大分页时，可以考虑提前找到起始位置，每次都从头开始截取，避免大偏移量

2. 【小表驱动大表】连表时，尽量小表驱动大表，被驱动表的连接字段加上索引，对被驱动表的过滤条件可以从`where`提前到`ON`谓词里

3. 【强制使用索引】有时`MySQL`不能很好的选择索引，可以使用`force index(idxname)`来指定使用某个索引

##### 7. 案例七

###### 1. SQL

```sql
SELECT *
FROM `coupon_detail`
WHERE `status` IN ('0', '1')
    AND `group_end_at` BETWEEN '2023-02-16 23:59:59' AND '2023-02-17 03:44:00'
ORDER BY `group_end_at` ASC
LIMIT 2000;
```

> 需要维护已过期的券码的状态

###### 2. 分析

1. `explain`

```
id|select_type|table        |type |possible_keys                      |key               |key_len|ref|rows   |Extra                             |
--+-----------+-------------+-----+-----------------------------------+------------------+-------+---+-------+----------------------------------+
1|SIMPLE     |coupon_detail|range|status_start_end,index_group_end_at|index_group_end_at|5      |   |1092360|Using index condition; Using where|
```

2. 所有索引

```sql
PRIMARY KEY (`id`) USING BTREE,
UNIQUE KEY `code` (`code`) USING BTREE,
KEY `account_id` (`account_id`) USING BTREE,
KEY `group_id` (`group_id`) USING BTREE,
KEY `config_id` (`config_id`) USING BTREE,
KEY `created_at` (`created_at`) USING BTREE,
KEY `app_type` (`config_give_app`,`config_give_type`) USING BTREE,
KEY `status_start_end` (`status`,`group_start_at`,`group_end_at`) USING BTREE,
KEY `push_id` (`push_id`),
KEY `index_group_end_at` (`group_end_at`),
KEY `activity_idx` (`activity_config_id`) USING BTREE
```

3. 这已经是优化后的版本，原`SQL`更易触发慢查询

```sql
-- 时间范围太大
SELECT *
FROM `coupon_detail`
WHERE `status` IN ('0', '1')
  AND `group_end_at` < '2023-02-17 03:44:00'
ORDER BY `group_end_at` ASC
LIMIT 2000;
```

4. 原`explain`

```
id|select_type|table        |type |possible_keys                      |key               |key_len|ref|rows    |Extra                             |
--+-----------+-------------+-----+-----------------------------------+------------------+-------+---+--------+----------------------------------+
1|SIMPLE     |coupon_detail|range|status_start_end,index_group_end_at|index_group_end_at|5      |   |10237337|Using index condition; Using where|
```

###### 3. 解决

1. 单纯`SQL`层面的优化空间非常少，`SELECT id`，减少查询字段，但是慢查询问题不在这，优化有限

2. 换一种思路，改用`Redis`的`zset`，这里就需要改很多地方的代码了，在生成`group_end_at`字段时塞入`zset`，以时间为`score`，`id`为值，在出库时获取到`id`再进行批量`update`即可

3. 另外一种思路，干脆不维护这部分数据的`status`了，在后续使用时注意结合`group_end_at`来判断是否已失效，这个也涉及到非常多代码场景

###### 4. 总结

- 若`SQL`层面无法进行优化，则可以考虑改用其他技术方案，比如使用`Redis`
- 另外一种选择是使用`OLAP`，加快大量数据的搜索
- 还有就是放弃维护`status`字段，若需要判断状态时改用其他方法来判断

##### 8. 案例八

###### 1. SQL

```sql
select * 
from `user_activity_reward` 
where ``.`reward_type` = '1' and ``.`create_time` between '2023-02-12' and '2023-02-18 23:59:59' 
order by `user_activity_reward`.`id` desc 
limit 20 offset 0;
```

- 耗时`2.3s+`，总扫描行数`284w+`，返回`0`

###### 2. 分析

1. 此案例为`案例五`优化后出现的新的慢查询，这里跟`MySQL`的版本有关，`5.7`以上的版本不会出现慢查询能正确使用索引

2. `explain`

```
id|select_type|table               |type |possible_keys|key    |key_len|ref|rows|Extra      |
--+-----------+--------------------+-----+-------------+-------+-------+---+----+-----------+
1|SIMPLE     |user_activity_reward|index|create_time  |PRIMARY|4      |   | 699|Using where|
```

3. 直接扫描了主键索引，这个操作有点伤

4. 考虑强制走`create_time`索引

```sql
select * from `user_activity_reward` 
force index(create_time) 
where ``.`reward_type` = '1' and ``.`create_time` between '2023-02-12' and '2023-02-18 23:59:59' order by `user_activity_reward`.`id` desc limit 20 offset 0;
```

5. `explain`

```
id|select_type|table               |type |possible_keys|key        |key_len|ref|rows |Extra                                             |
--+-----------+--------------------+-----+-------------+-----------+-------+---+-----+--------------------------------------------------+
1|SIMPLE     |user_activity_reward|range|create_time  |create_time|4      |   |83152|Using index condition; Using where; Using filesort|
```

6. 优化后：耗时`158ms`

7. 这里依然有个问题，用到了文件排序`Using filesort`

8. 若是直接使用`create_time`排序，则可去掉文件排序

9. 继续优化

```sql
select * from `user_activity_reward` 
where ``.`reward_type` = '1' and ``.`create_time` between '2023-02-12' and '2023-02-18 23:59:59' order by `user_activity_reward`.`create_time` desc limit 20 offset 0;
```

10. `explain`

```
id|select_type|table               |type |possible_keys|key        |key_len|ref|rows |Extra                             |
--+-----------+--------------------+-----+-------------+-----------+-------+---+-----+----------------------------------+
1|SIMPLE     |user_activity_reward|range|create_time  |create_time|4      |   |83202|Using index condition; Using where|
```

11. 优化后：`180ms`

###### 3. 解决

1. 使用`create_time`排序

```sql
select * from `user_activity_reward` 
where ``.`reward_type` = '1' and ``.`create_time` between '2023-02-12' and '2023-02-18 23:59:59' 
order by `user_activity_reward`.`create_time` desc 
limit 20 offset 0;
```

2. 也可以加上强制索引 `force index(create_time)`

###### 4. 总结

1. 此案例跟`MySQL`版本有关，`5.7`以上不会有这个问题，早一点的版本在索引选择上有点问题，无法选择正确的索引，此时可以采用强制走索引的方法来解决
2. 排序字段最好可以走索引

##### 9. 案例九

###### 1. SQL

```sql
-- 原SQL有列出具体搜索的字段，这里为了简化使用 * 
SELECT * 
FROM `game`.`game_brush_record`
WHERE `game_brush_record`.`type` = '3'
    AND `game_brush_record`.`account_id` = '34149187'
    AND `game_brush_record`.`created_at` BETWEEN '2022/11/22' AND '2023/02/19 23:59:59'
ORDER BY `game_brush_record`.`id` DESC
LIMIT 15;
```

- 耗时：`1.642s`；扫描`75w+`

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table            |type |possible_keys                                              |key    |key_len|ref|rows|Extra      |
   --+-----------+-----------------+-----+-----------------------------------------------------------+-------+-------+---+----+-----------+
   1|SIMPLE     |game_brush_record|index|type_pid_clientid,type_pid_ip,type_pid_accountid,created_at|PRIMARY|4      |   |  49|Using where|
   ```

2. `type`类型为`index`，这个意思是索引扫描，这可不是个好类型，仅好于全表扫描，一般看到这个类型要考虑优化了

3. 仔细看上面的`explain`和`案例八`的非常相似，原本按照我们优化的思路是应该走`created_at`单索引的，结果`MySQL`优化器选择了扫描主键索引，我们可以试着把排序字段改成`created_at`试试
   
   ```sql
   SELECT * 
   FROM `game`.`game_brush_record`
   WHERE `game_brush_record`.`type` = '3'
     AND `game_brush_record`.`account_id` = '34149187'
     AND `game_brush_record`.`created_at` BETWEEN '2022/11/22' AND '2023/02/19 23:59:59'
   ORDER BY `game_brush_record`.`created_at` DESC
   LIMIT 15;
   ```

4. `explain`
   
   ```
   id|select_type|table            |type |possible_keys                                              |key       |key_len|ref|rows |Extra      |
   --+-----------+-----------------+-----+-----------------------------------------------------------+----------+-------+---+-----+-----------+
   1|SIMPLE     |game_brush_record|range|type_pid_clientid,type_pid_ip,type_pid_accountid,created_at|created_at|5      |   |95004|Using where|
   ```

5. `type`优化成`range`了，理论上好于`index`，但是这里的`rows`变大了，`9.5w`

6. 优化后：耗时`200ms`

7. 另外，可以考虑强制索引，这个无需改造`SQL`，可以维持原有的业务逻辑按`id`排序
   
   ```sql
   SELECT * 
   FROM `game`.`game_brush_record` force index(created_at)
   WHERE `game_brush_record`.`type` = '3'
     AND `game_brush_record`.`account_id` = '34149187'
     AND `game_brush_record`.`created_at` BETWEEN '2022/11/22' AND '2023/02/19 23:59:59'
   ORDER BY `game_brush_record`.`id` DESC
   LIMIT 15;
   ```

8. `explain`
   
   ```
   id|select_type|table            |type |possible_keys|key       |key_len|ref|rows |Extra                                             |
   --+-----------+-----------------+-----+-------------+----------+-------+---+-----+--------------------------------------------------+
   1|SIMPLE     |game_brush_record|range|created_at   |created_at|5      |   |95004|Using index condition; Using where; Using filesort|
   ```

9. 这里单看`explain`似乎比上面的优化差一点，这里由于排序字段没有走索引用到了`Using filesort`文件排序，但是时间测试貌似效果比上面的还好一点

10. 优化后：`200ms`

###### 3. 解决

1. 通用方案直接强制索引即可，当然这个场景是由于`MySQL`版本问题，`5.7`以上版本无需修改也会选择走`created_at`索引
   
   ```sql
   SELECT * 
   FROM `game`.`game_brush_record` force index(created_at)
   WHERE `game_brush_record`.`type` = '3'
     AND `game_brush_record`.`account_id` = '34149187'
     AND `game_brush_record`.`created_at` BETWEEN '2022/11/22' AND '2023/02/19 23:59:59'
   ORDER BY `game_brush_record`.`id` DESC
   LIMIT 15;
   ```

2. 改造`SQL`语句，使用`created_at`字段进行排序，这个需要考虑业务逻辑是否支持
   
   ```sql
   SELECT * 
   FROM `game`.`game_brush_record`
   WHERE `game_brush_record`.`type` = '3'
     AND `game_brush_record`.`account_id` = '34149187'
     AND `game_brush_record`.`created_at` BETWEEN '2022/11/22' AND '2023/02/19 23:59:59'
   ORDER BY `game_brush_record`.`created_at` DESC
   LIMIT 15;
   ```

###### 4. 总结

1. `SQL`优化之后需要观察线上使用情况，`MySQL`版本对不同的`SQL`优化存在差异，所有的优化效果以线上为准

2. 有些大表，无论怎么优化都会扫描大量数据，则可以考虑优化业务逻辑或者选用其他存储方案，比如转向`OLAP`

##### 10. 案例十

###### 1. SQL

```sql
SELECT COUNT(*) AS `rowcount`
FROM `game`.`user_orders`
WHERE `user_orders`.`status` = '9'
    AND `user_orders`.`created_at` BETWEEN '2023/02/18 80:80:00 ' AND ' 2023/02/18 23:59:59 23:59:59' 
    AND `user_orders`.`from app` IN ('54790');
```

- 执行：耗时`12s+`；扫描`4.8w+`

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table      |type       |possible_keys           |key          |key_len|ref|rows |Extra                                      |
   --+-----------+-----------+-----------+------------------------+-------------+-------+---+-----+-------------------------------------------+
   1|SIMPLE     |user_orders|index_merge|created_at,app_id,status|app_id,status|4,1    |   |47610|Using intersect(app_id,status); Using where|
   ```

2. 从上面的`explain`来看，走了索引合并，并未使用`created_at`索引

3. 强制走`created_at`索引
   
   ```sql
   SELECT COUNT(*) AS `rowcount`
   FROM `game`.`user_orders` force index(created_at)
   WHERE `user_orders`.`status` = '9'
     AND `user_orders`.`created_at` BETWEEN '2023/02/18 80:80:00 ' AND ' 2023/02/18 23:59:59 23:59:59' 
     AND `user_orders`.`from app` IN ('54790');
   ```

4. `explain`
   
   ```
   id|select_type|table      |type |possible_keys|key       |key_len|ref|rows  |Extra                             |
   --+-----------+-----------+-----+-------------+----------+-------+---+------+----------------------------------+
   1|SIMPLE     |user_orders|range|created_at   |created_at|6      |   |137898|Using index condition; Using where|
   ```

5. 预估需要扫描接近`14w+`，所以`MySQL`优化器才会放弃该索引

###### 3. 解决

1. 可以考虑把时间拆分，比如每个小时一条`SQL`，分`24`次来查询，再把结果累加即可
2. 另外一种方案，彻底解决此类问题，改用`OLAP`，在我们的`CK`里有该表，我们可以从`CK`里进行统计

###### 4. 总结

1. 对于一些非常大的表，统计、明细相关的搜索都不再适合从`MySQL`里获取数据了，可以考虑换一种方式，改用`OLAP`，比如`CK`、`StarRocks`、`TiDB`

##### 11. 案例十一

###### 1. SQL

```sql
SELECT `coupon_detail`.`id`
FROM `coupon_detail`
    LEFT JOIN `coupon_extra` ON `coupon_extra`.`detail_id` = `coupon_detail`.`id`
WHERE DATE_FORMAT(coupon_detail.created_at, '%Y-%m-%d') = '2023-02-14'
    AND `group_id` = '12622'
    AND `push_id` = '0'
    AND `status` = '2'
    AND `activity_config_id` = '190'
    AND `from_pid` = '0'
    AND `vip_level` = '0'
```

- 执行：耗时`2.3s+`；扫描`21w+`

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table        |type       |possible_keys                                         |key                          |key_len|ref                  |rows |Extra                                                      |
   --+-----------+-------------+-----------+------------------------------------------------------+-----------------------------+-------+---------------------+-----+-----------------------------------------------------------+
   1|SIMPLE     |coupon_detail|index_merge|PRIMARY,group_id,status_start_end,push_id,activity_idx|group_id,activity_idx,push_id|4,4,4  |                     |14592|Using intersect(group_id,activity_idx,push_id); Using where|
   1|SIMPLE     |coupon_extra |eq_ref     |index_coupon_detail_id                                |index_coupon_detail_id       |4      |game.coupon_detail.id|    1|Using where                                                |
   ```

2. 使用到了索引合并，三个索引`Using intersect(group_id,activity_idx,push_id)`

3. 可以考虑走`created_at`索引
   
   ```sql
   SELECT `coupon_detail`.`id`
   FROM `coupon_detail` force index(created_at)
     JOIN `coupon_extra` ON `coupon_extra`.`detail_id` = `coupon_detail`.`id`
   WHERE 
     -- DATE_FORMAT(coupon_detail.created_at, '%Y-%m-%d') = '2023-02-14'
     coupon_detail.created_at between '2023-02-14 00:00:00' and '2023-02-14 23:59:59'
     AND `group_id` = '12622'
     AND `push_id` = '0'
     AND `status` = '2'
     AND `activity_config_id` = '190'
     AND `from_pid` = '0'
     AND `vip_level` = '0';
   ```

4. `explain`
   
   ```
   id|select_type|table        |type  |possible_keys         |key                   |key_len|ref                  |rows   |Extra                             |
   --+-----------+-------------+------+----------------------+----------------------+-------+---------------------+-------+----------------------------------+
   1|SIMPLE     |coupon_detail|range |created_at            |created_at            |5      |                     |1249274|Using index condition; Using where|
   1|SIMPLE     |coupon_extra |eq_ref|index_coupon_detail_id|index_coupon_detail_id|4      |game.coupon_detail.id|      1|Using where                       |
   ```

5. 预估扫描`125w+`

6. 优化后：`4.9s+`，这比不优化还慢就尴尬了

7. 继续优化，把时间拆分，按小时拆分或者半小时拆分
   
   ```sql
   SELECT `coupon_detail`.`id`
   FROM `coupon_detail` force index(created_at)
     JOIN `coupon_extra` ON `coupon_extra`.`detail_id` = `coupon_detail`.`id`
   WHERE 
     -- DATE_FORMAT(coupon_detail.created_at, '%Y-%m-%d') = '2023-02-14'
     coupon_detail.created_at between '2023-02-14 00:00:00' and '2023-02-14 00:59:59'
     AND `group_id` = '12622'
     AND `push_id` = '0'
     AND `status` = '2'
     AND `activity_config_id` = '190'
     AND `from_pid` = '0'
     AND `vip_level` = '0';
   ```

8. 优化后：最大一次查询为0点到1点，耗时`250ms`

###### 3. 解决

1. 可以考虑走`created_at`索引，然后把时间拆分，比如每小时或者半小时一条，再合并结果集
2. 可以配合协程，多个`SQL`并行执行，减少总执行时间

###### 4. 总结

1. 遇到时间范围的优化，一般的优化思路是：增加索引，缩短时间范围区间，一般扫描的数量就降下来了，自然就不会出现慢查询了，但是业务代码需要改动并且也不一定满足业务需求

##### 12. 案例十二

###### 1. SQL

```sql
UPDATE `tag_related`
SET `is_deleted` = '1'
WHERE `tag_id` = '45'
    AND `is_deleted` = '0'
```

- 耗时：`12s+`；扫描`200w+`

###### 2. 分析

1. `explain`
   
   ```
   id|select_type|table      |type |possible_keys|key    |key_len|ref|rows   |Extra                       |
   --+-----------+-----------+-----+-------------+-------+-------+---+-------+----------------------------+
   1|SIMPLE     |tag_related|index|index_tag_id |PRIMARY|8      |   |4517016|Using where; Using temporary|
   ```

2. 可用索引
   
   ```sql
   PRIMARY KEY (`id`),
   KEY `index_tag_id` (`tag_id`) USING BTREE,
   KEY `index_account_id` (`account_id`) USING BTREE
   ```

3. `tag_id`上有索引，但是区分度不高，需要频繁回表，这里`MySQL`放弃了走索引而选择了扫描主键索引，这个选择是非常糟糕的

4. 强制索引
   
   ```sql
   UPDATE `tag_related` force index (index_tag_id)
   SET `is_deleted` = '1'
   WHERE `tag_id` = '45'
     AND `is_deleted` = '0';
   ```

5. `explain`
   
   ```
   id|select_type|table      |type |possible_keys|key         |key_len|ref  |rows   |Extra                       |
   --+-----------+-----------+-----+-------------+------------+-------+-----+-------+----------------------------+
   1|SIMPLE     |tag_related|range|index_tag_id |index_tag_id|4      |const|2258508|Using where; Using temporary|
   ```

6. 优化后，`type`变成`range`走了`tag_id`索引，但是由于该索引区分度非常低，依然需要扫描非常大量的数据，效果并不佳

7. 分析该`SQL`发现，只用到了`tag_id, is_deleted`两列，可以考虑建一个复合索引，命中覆盖索引，加快搜索速度，避免回表

8. 按阿里云的分析，若搜索`is_deleted=1`优化之后预期性能提升`184881.59`倍，但是搜索`is_deleted=0`效果有限

9. 可以先搜出最大最小`id`，然后按`id`分区间进行`update`
   
   ```sql
   select max(id), min(id) from tag_related where tag_id = 45 and is_deleted = 0;
   ```

10. `explain`
    
    ```
    id|select_type|table|type|possible_keys|key|key_len|ref|rows|Extra                       |
    --+-----------+-----+----+-------------+---+-------+---+----+----------------------------+
    1|SIMPLE     |     |    |             |   |       |   |    |Select tables optimized away|
    ```

11. 耗时：`41ms`，无需访问`table`，单独从索引获取，速度出奇的快
    
    ```
    max(id)  |min(id)  |
    ---------+---------+
    419979863|418359378|
    ```

12. 取出`id`区间，然后每`10w`区间执行一次，`419979863-418359378=1620485`分`17`次

13. 这样操作必然可以把慢查询给优化掉，再不行继续减小区间，但是多次执行`SQL`的总耗时会不会比单次`SQL`多

###### 3. 解决

1. 建一个复合索引
   
   ```sql
   -- 增加一个复合索引
   ALTER table tag_related algorithm=inplace,lock=none,
   ADD INDEX idx_tag_deleted(tag_id,is_deleted);
   -- 把之前的单索引删掉
   ALTER table tag_related algorithm=inplace,lock=none,
   DROP INDEX index_tag_id;
   ```

2. 取出最大最小`id`，再按`id`分区间来多次执行`update`

###### 4. 总结

1. 尽量使用覆盖索引，单索引区分度极低的话效果并不佳
2. 单次`UPDATE`或`DELETE`数据量太大可以考虑分多次执行，最简单的方案就是在末尾加上`LIMIT`限制单次执行的上限，这个是避免`MySQL`从库同步时卡顿出现比较严重的主从延时
3. 可以获取最大最小主键`id`，再按区间进行更新

##### 13. 案例十三

###### 1. SQL

```sql
SELECT COUNT(*) AS `rowcount`
FROM `game`.`game_brush_record`
WHERE `game_brush_record`.`type` = '2'
    AND `game_brush_record`.`account_id` = '34803460'
    AND `game_brush_record`.`created_at` BETWEEN '2022/11/24' AND '2023/02/21 23:59:59';
```

- 耗时：`3.7s+`；扫描：`54.6w+`

###### 2. 分析

1. explain

```
id|select_type|table            |type|possible_keys                                              |key              |key_len|ref  |rows  |Extra      |
--+-----------+-----------------+----+-----------------------------------------------------------+-----------------+-------+-----+------+-----------+
1|SIMPLE     |game_brush_record|ref |type_pid_clientid,type_pid_ip,type_pid_accountid,created_at|type_pid_clientid|1      |const|368144|Using where|
```

2. `type`为`ref`，用到了复合索引前缀，但是索引里面这个`type`列区分度也太低了吧，效果不佳，预估扫描`36.8w`，实际扫描了`54.6w+`

3. 这条`SQL`是上面`案例九`的优化后续，由于没有强制使用`created_at`索引导致`MySQL`选用了其他索引，当然这里可能也是跟`MySQL`版本有关

4. 强制`created_at`索引

```sql
SELECT COUNT(*) AS `rowcount`
FROM `game`.`game_brush_record` force index(created_at)
WHERE `game_brush_record`.`type` = '2'
  AND `game_brush_record`.`account_id` = '34803460'
  AND `game_brush_record`.`created_at` BETWEEN '2022/11/24' AND '2023/02/21 23:59:59';
```

5. `explain`

```
id|select_type|table            |type |possible_keys|key       |key_len|ref|rows |Extra                             |
--+-----------+-----------------+-----+-------------+----------+-------+---+-----+----------------------------------+
1|SIMPLE     |game_brush_record|range|created_at   |created_at|5      |   |98522|Using index condition; Using where|
```

6. `type`为`range`，预估扫描数夏季到`9.8w+`

7. 优化后：`122ms`

###### 3. 解决

1. 强制索引

```sql
SELECT COUNT(*) AS `rowcount`
FROM `game`.`game_brush_record` force index(created_at)
WHERE `game_brush_record`.`type` = '2'
  AND `game_brush_record`.`account_id` = '34803460'
  AND `game_brush_record`.`created_at` BETWEEN '2022/11/24' AND '2023/02/21 23:59:59';
```

###### 4. 总结

1. 优化后要持续跟进线上使用情况，根据实际情况来判断优化效果，由于线上`MySQL`版本问题和用户使用场景的多样化有时并未按照我们的预期来执行

##### 14. 案例十四

###### 1. SQL

###### 2. 分析

###### 3. 解决

###### 4. 总结

##### 15. 案例十五

###### 1. SQL

###### 2. 分析

###### 3. 解决

###### 4. 总结
