# 如何ping指定IP的端口号

## 参考资料

1. [如何ping指定IP的端口号 - 简书](https://www.jianshu.com/p/fbdf744a3fbd)

## ping IP/域名

```shell
# 可以直接使用ping命令即可
root@DESKTOP-MEQ1GD0:~# ping www.baidu.com
PING www.a.shifen.com (120.232.145.185) 56(84) bytes of data.
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=1 ttl=49 time=3.38 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=2 ttl=49 time=3.62 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=3 ttl=49 time=3.28 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=4 ttl=49 time=3.93 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=5 ttl=49 time=3.20 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=6 ttl=49 time=4.12 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=7 ttl=49 time=3.46 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=8 ttl=49 time=3.14 ms
64 bytes from 120.232.145.185 (120.232.145.185): icmp_seq=9 ttl=49 time=3.40 ms
^C
--- www.a.shifen.com ping statistics ---
9 packets transmitted, 9 received, 0% packet loss, time 8008ms
rtt min/avg/max/mdev = 3.143/3.506/4.126/0.318 ms
```

## ping指定端口

> 若要ping指定端口，则ping命令无法实现，需要使用其他命令

```shell
# 1. 使用 telnet ip port
root@DESKTOP-MEQ1GD0:~# telnet www.baidu.com 80
Trying 120.232.145.185...
Connected to www.a.shifen.com.
Escape character is '^]'.

# 2. 使用 nc ip port
root@DESKTOP-MEQ1GD0:~# nc -vz www.baidu.com 80
Connection to www.baidu.com 80 port [tcp/http] succeeded!

# 3. 使用 curl ip:port
root@DESKTOP-MEQ1GD0:~# curl www.baidu.com:80
<!DOCTYPE html>
<!--STATUS OK--><html>
...

# 4. 使用 wget ip:port
root@DESKTOP-MEQ1GD0:~# wget www.baidu.com:80
--2023-09-19 10:35:21--  http://www.baidu.com/
Resolving www.baidu.com (www.baidu.com)... 120.232.145.144, 120.232.145.185
Connecting to www.baidu.com (www.baidu.com)|120.232.145.144|:80... connected.
HTTP request sent, awaiting response... 200 OK
Length: 2381 (2.3K) [text/html]
Saving to: ‘index.html’

index.html                                      100%[====================================================================================================>]   2.33K  --.-KB/s    in 0s       

2023-09-19 10:35:21 (109 MB/s) - ‘index.html’ saved [2381/2381]

# 5. 使用 cat < /dev/tcp/ip/port
root@DESKTOP-MEQ1GD0:~# cat < /dev/tcp/120.232.145.144/80

```

## 其他

1. curl可以设置连接超时 `--connect-timeout seconds`