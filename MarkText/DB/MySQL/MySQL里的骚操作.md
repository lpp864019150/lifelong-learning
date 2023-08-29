# MySQL里的骚操作

## 参考博文

1. [聊聊项目如何设计幂等？ - 掘金](https://juejin.cn/post/7263304358794756153)

## 避免重复插入

1. `insert ignore`
   
   这里有一个坑，若已存在记录，然后被`ignore`了，但是自增`id`依然会执行`+1`，但是这条`sql`不会记录到`binlog`里，导致主从同步时从库的自增`id`与主库不一致
   
   `innodb_autoinc_lock_mode=0`可解决上面的问题，但是这样就开启了自增锁，必然会时操作变慢，这里要谨慎考虑加锁是否值得
   
   当然这个方案是不推荐的，直接触发唯一键异常即可，不必去`ignore`
   
   ```sql
   insert ignore INTO tableName VALUES ("id","xxx")
   ```

2. `insert select not exists`
   
   利用了查询再插入，把`select`后的值插入到表，这里采用了逆向思维，查找不存在才进行插入，也即保证了不会重复插入
   
   但是需要注意最后的`limit 0, 1`，这里只能插入一条，必须限制`select`的行数
   
   当然这个方案也是不推荐的
   
   ```sql
   insert into order(id,code,password)
   select ${id},${code},${password}
   from order
   where not exists(select 1 from order where code = ${code}) limit 0,1;
   ```

## 使用or来改造for循环

1. 有些时候我们需要在循环里多次查询，再把结果集合并，这样每执行一次都会产生一次`I/O`，我们都知道`I/O`是很耗时的

2. 我们可以在把每次`for`循环的条件变成一个`or`条件，把多次`I/O`变成少量`I/O`甚至只有一次`I/O`

3. 但是拼接的时候需要注意`sql`的长度，若太长了会消耗网络传输，`sql`执行也会有一定压力，需要慎重考虑

## 使用多字段in来改造for循环

1. 可以使用`where (a, b) in ((1,2), (2,3))`来一次性匹配多条多字段组

2. 这里和上面的`or`改造类似，也是为了减少`I/O`

3. 和上面一样，也要注意`sql`的长度
