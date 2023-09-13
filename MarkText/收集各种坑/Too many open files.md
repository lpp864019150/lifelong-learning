# Too many open files

## 参考资料

1. [一次因 Too many open files 引发的服务雪崩 - 掘金](https://juejin.cn/post/7180664063985352764)

## 可能的解决方案

1. 把限制设置大一点
   
   ```shell
   # 查看限制
   ulimit -a 
   
    # 临时修改
    ulimit -n 65535
   
    # 永久修改
    vim /etc/security/limits.conf
    # 添加
    * soft nofile 65535
    * hard nofile 65535
    # 重启
    reboot
   ```

2. 查找原因，为何占用那么多文件句柄，是否有bug