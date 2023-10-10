# 一台服务器最大能支持多少条TCP连接

## 参考资料

1. [一台服务器最大能支持多少条TCP连接 - 掘金](https://juejin.cn/post/7162824884597293086)

## 概念

1. TCP四元组
   
   客户端IP、客户端port、服务器端IP、服务器端port

2. 文件描述符
   
   

3. 端口
   
   0-65535

## 描述符限制

1. 系统级限制
   
   file-max
   
   对root用户无效，即使超过了限制root用户依然可以登录系统打开文件
   
   ```shell
   # vim /etc/sysctl.conf
   fs.file-max=1100000 // 系统级别设置成110万，多留点buffer
   #fs.nr_open=1100000 // 进程级别也设置成110万，因为要保证比 hard nofile大
   ```

2. 进程级限制
   
   针对单个进程也针对单个用户
   
   soft nofile、hard nofile
   
   ```shell
   # vim /etc/sysctl.conf
   #fs.file-max=1100000 // 系统级别设置成110万，多留点buffer
   fs.nr_open=1100000 // 进程级别也设置成110万，因为要保证比 hard nofile 大
   
   # vim /etc/security/limits.conf
   soft nofile 1000000
   hard nofile 1000000
   ```

## 物理资源限制

1. 理论上可支持：【2^32 (ip数) * 2^16 (端口数)】条连接（约等于两百多万亿）

2. 现实中受限于cpu和内存，单个`ESTABLISH`空闲链接占用`3.3kb`，4GB理论上可达100w+上限，但是一般只能占用10%内存，毕竟服务器除了TCP链接还有很多其他操作都需要使用内存资源，不可能全被占用完。