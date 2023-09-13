# DDL方案

## 参考资料

1. [[MySQL]慢SQL优化记录 -- DDL选择 - 掘金](https://juejin.cn/post/7224518612161396794)

## 方案

1. Offline(COPY)
   
   默认方式，当然也可以指定
   
   ```sql
   ALTER TABLE tbl_name ADD PRIMARY KEY (column), ALGORITHM=COPY;
   ```
   
   执行流程：
   
   1. 对原数据添加MDL 读锁(s)，读取表结构 -- 这个阶段很快
   
   2. 升级MDL成排他锁(x) -- 不允许进行其他DDL，DML
   
   3. 创建一张一样的新表:table_new
   
   4. 修改新表的表结构
   
   5. copy 原表的数据到新表
   
   6. 新表rename成旧表
   
   7. 删除旧表
   
   8. 释放所有锁

2. Online
   
   ```sql
   ALTER TABLE tbl_name ADD Index Index_name (column), ALGORITHM=INPLACE,LOCK=NONE;
   ```

3. pt-osc
   
   pt-online schema change，由perconal推出的一个MySQL管理小工具