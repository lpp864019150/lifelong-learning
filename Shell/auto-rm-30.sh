#!/bin/sh

# 每天凌晨2执行一次
# crontab -e
# 0 2 * * * /bin/sh /mnt/e/webroot/lifelong-learning/PHP/code/Shell/rm.sh

# link: https://blog.csdn.net/m1ssyAn/article/details/105190249
# link: https://www.cnblogs.com/todarcy/p/15618618.html
# link: https://blog.csdn.net/u014209205/article/details/100417188
# 删除30天前的日志文件
# find /mnt/e/webroot/lifelong-learning/PHP/code/logs -type f -mtime +30 -name *.log -exec rm -rf {} \;

# 删除 runtimes/logs 目录下的以.log结尾的30天前的文件
# find /mnt/e/webroot/lifelong-learning/PHP/code/runtimes/logs -type f -mtime +30 -name *.log -exec rm -rf {} \;

# 待删除的目录，后缀为.log，30天前的文件
dirs="/mnt/e/webroot/lifelong-learning/PHP/code/logs /mnt/e/webroot/lifelong-learning/PHP/code/runtimes/logs"
name="*.log"
expire=30
for dir in $dirs; do
    find $dir -name "$name" -mtime +$expire -exec rm {} \;
done