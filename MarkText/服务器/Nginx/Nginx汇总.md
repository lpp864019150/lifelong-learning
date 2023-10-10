# Nginx汇总

#### 0、参考文档

* 【官网】[Nginx中文文档](https://www.nginx.cn/doc/index.html)
* 【博客】[Nginx配置详解](https://www.cnblogs.com/knowledgesea/p/5175711.html)
* 【博客】[反向代理和正向代理区别](https://www.cnblogs.com/taostaryu/p/10547132.html)
* 【博客】[Epoll原理解析](https://blog.csdn.net/armlinuxww/article/details/92803381)
* 【博客】[Nginx配置文件详解](https://www.jianshu.com/p/18eeee0911a0)
* 【博客】[Nginx各个模块的配置](https://www.jianshu.com/p/ad4c21ab2266)
* 【博客】[OpenResty+Lua+Redis+Canal实现多级缓存架构](https://www.jianshu.com/p/5e4dbaedaecd)
* 【CSDN】[Nginx 面试题 40 问](blog.csdn.net/wuzhiwei549/article/details/122758937)

#### 1、术语

1. 代理
   
   * 代理其实就是一个中介，A和B本来可以直连，中间插入一个C，C就是中介。
   
   * 刚开始的时候，代理多数是帮助内网client访问外网server用的
   
   * 后来出现了反向代理，反向这个词在这儿的意思其实就是指方向相反，即代理将来自外网client的请求转发到内网server，从外到内

2. 正向代理
   
   > 正向代理类似一个跳板机，代理访问外部资源，比如我们无法访问google，通过代理就可以访问
   
   * 买票的黄牛
     
     * 客户端的代理，服务器不知道实际发起请求的客户端
     
     * 正向代理的用途
       
       * 访问原来无法访问的资源，如google
       * 可以做缓存，加速访问资源
       * 对客户端访问授权，上网进行认证
       * 代理可以记录用户访问记录(上网行为管理)，对外隐藏用户信息

3. 反向代理
   
   > Reverse Proxy，有别与正向代理，是从用户角度来思考问题的，如果用户访问一个网站不能直接访问，可以通过代理去访问，这个就是正向，用户能感知。如果用户访问一个网站，但是最后却是nginx反向代理的一个服务器给予服务，用户是无法感知到的
   
   > 反向代理（Reverse Proxy）实际运行方式是指以代理服务器来接受internet上的连接请求，然后将请求转发给内部网络上的服务器，并将从服务器上得到的结果返回给internet上请求连接的客户端，此时代理服务器对外就表现为一个服务器
   
   * 租房的代理
   
   * 服务端的代理，客户端不知道实际提供服务的服务端
   
   * 反向代理的作用
     
     * 保证内网的安全，阻止web攻击，大型网站，通常将反向代理作为公网访问地址，web服务器是内网
     * 负载均衡，通过反向代理服务器来优化网站负载

4. Nginx
   
   > engine x，由俄罗斯

5. LVS
   
   > Linux Virtual Server，虚拟机

6. LB
   
   > Load Balance，负载均衡
   
   * 负载均衡的方式
     * 轮询，默认方式，也即仅仅列出来
     * 权重，在ip后面通过`weight=n`来控制，用于后端服务器性能不均的情况
     * ip\_hash，在列表顶部加一行`ip_hash;`，每个访客访问同一个服务器
     * fair，按后端服务器响应时间来分配，响应时间短的优先分配，在列表加一行`fair;`
     * url\_hash，通过url定向同一台服务器，后端服务器为缓存时比较有效

<!---->

    // 默认，轮询
    upstream bakend {
        server 127.0.0.1:8027;
        server 127.0.0.1:8028;
        server 127.0.0.1:8029;
    }
    
    // weight
    upstream bakend {
        server 192.168.0.14 weight=10;
        server 192.168.0.15 weight=10;
    }
    
    // ip_hash
    upstream bakend {
        ip_hash;
        server 192.168.0.14:88;
        server 192.168.0.15:80;
    }
    
    // fair
    upstream backend {
        server server1;
        server server2;
        fair;
    }
    
    // url hash
    upstream backend {
        server squid1:3128;
        server squid2:3128;
        hash $request_uri;
        hash_method crc32;
    }
    
    在需要使用负载均衡的server中增加
    proxy_pass http://bakend/;

7. 非阻塞
   
   > nginx 只有一个master进程和已配置个数的 worker进程，master 进程把请求交给 worker 去处理，一个worker 在可能出现阻塞的地方会注册一个事件就放过去了（epoll模型），而不是干巴巴的等待阻塞被处理完，他会继续处理后续的请求（非阻塞），当这个事件处理完之后会通过callback来通知worker继续处理那条请求后续的事情（事件驱动）因此单个worker可以处理大量请求而不会轻易让整个系统卡住。

8. [epoll](https://blog.csdn.net/armlinuxww/article/details/92803381)
   
   > event poll，epoll是Linux内核为处理大批量文件描述符而作了改进的poll，是Linux下多路复用IO接口select/poll的增强版本，它能显著提高程序在大量并发连接中只有少量活跃的情况下的系统CPU利用率。

9. 事件驱动

10. 单机并发
    
    > 同一时间段同时请求的数量，比如一秒内，QPS
    
    * apache 200-300
    
    * tomcat 1000-3000
    
    * Nginx 几万+
    
    * OpenResty 可达百万级
    
    * MySQL 4000-8000
    
    * redis 几万

11. I/O多路复用
    
    > I/O多路复用（multiplexing）的本质是通过一种机制（系统内核缓冲I/O数据），让单个进程可以监视多个文件描述符，一旦某个描述符就绪（一般是读就绪或写就绪），能够通知程序进行相应的读写操作
    
    * 网卡接收数据
      * 网卡收到从网线传来的数据
      * 经过硬件电路的传输
      * 将数据写入到内存中的某个地址上
    * 如何知道接收了数据
      * 中断
      * CPU需要处理由硬件发出的信号，比如中断信号，优先级高，先处理完再回去处理用户程序
      * 当网卡把数据写入到内存后，网卡向 CPU 发出一个中断信号，操作系统便能得知有新数据到来，再通过网卡中断程序去处理数据。
    * 进程阻塞为什么不占用 CPU 资源?
      * 工作队列，运行状态，运行中，等待中，运行中状态分时执行，由于速度快，看起来像同时进行
      * 等待队列，socket文件系统，引用等待中进程，把数据缓存起来，等接收完了进行唤醒
      * 唤醒进程，从等待列表唤醒，并从socket缓存区拿数据进入运行中状态，执行
      * 一个socket对应一个端口号，内核可以通过端口号找到对应的socket
    * 同时监视多个socket
      * fd列表，循环监视
      * epoll，efd，rdlist，socket和epoll对象
    * select
    
    > 监听列表，循环遍历，默认最多1024个socket，多次遍历，还需要把FDS列表传递给内核，有一定的开销
    
    * poll
    * epoll
    
    > Epoll 在 Select 和 Poll 的基础上引入了 eventpoll 作为中间层，使用了先进的数据结构，是一种高效的多路复用技术。红黑树

12. Location
    
    * 匹配符 匹配规则 优先级逐级降

<!---->

    = 精确匹配 1
    ^~ 以某个字符串开头 2
    ~ 区分大小写的正则匹配 3
    ~* 不区分大小写的正则匹配 4
    !~ 区分大小写的不匹配正则 5
    !~* 不区分大小写的不匹配正则 6
    / 通用匹配，任何请求都会匹配到 7

#### 2、部署

###### 1. php服务器配置

* [nginx用户认证配置（ Basic HTTP authentication）](http://www.ttlsa.com/nginx/nginx-basic-http-authentication/)

<!---->

    // 放入nginx.conf http模块
    server {
        # 监听端口
        listen 80;
        # 设置域名
        server_name www.example.com;
        # 根目录
        set $root /data/www/example_com;
        # if -d若是一个文件夹
        if (-d $root/web) {
            set $root $root/web;
        }
        root $root;
    
        # Nginx访问日志
        access_log /var/log/nginx/www_example_com.access_log.log
        # Nginx错误日志
        error_log /var/log/nginx/www_example_com.error_log.log
    
        # 可以开启简单的basic http auth
        auth_basic "need auth"; # off则为关闭
        # 设置账户密码 账户:密码 密码非明文，可使用openssl加密
        # printf "lpp:$(openssl passwd -crypt 123456)\n" >> /data/nginx/htpasswd
        auth_basic_user_file /data/nginx/htpasswd;
    
        location / {
            index index.html index.php
        }
    
        # 可以使用set设置一个变量
        set $phpfpm 127.0.0.1:9000;
        location ~ \.php$ {
            fastcgi_pass $phpfpm;
            fastcgi_index index.php;
            include fastcgi.conf;
        }
    }

#### 3、其他知识点

###### 1. Linux最多有多少个端口

> 65535，在TCP、UDP协议的开头，会分别用16位来存储源端口号和目标端口号，所以端口个数是2^16-1=65535个。

###### 2. Nginx详细配置

> 整体分为三块，全局块、events块、http块，下面是一个简单版的配置

###### 1. 概览

    # 全局块
     user www-data; ## 默认是 user nginx nginx
     worker_processes  2;  ## 默认1，一般建议设成CPU核数1-2倍
     error_log  logs/error.log; ## 错误日志路径
     pid  logs/nginx.pid; ## 进程id
     worker_rlimit_nofile 204800; ## 指定进程可以打开的最大描述符：数目。
     # Events块
     events {
       # 使用epoll的I/O 模型处理轮询事件。
       # 可以不设置，nginx会根据操作系统选择合适的模型
       use epoll;
       # 工作进程的最大连接数量, 默认1024个
       worker_connections  2048;
       # http层面的keep-alive超时时间
       keepalive_timeout 60;
       # 客户端请求头部的缓冲区大小
       client_header_buffer_size 2k;
     }
     # http块
     http { 
       include mime.types;  # 导入文件扩展名与文件类型映射表
       default_type application/octet-stream;  # 默认文件类型
       # 日志格式及access日志路径
       log_format   main '$remote_addr - $remote_user [$time_local]  $status '
         '"$request" $body_bytes_sent "$http_referer" '
         '"$http_user_agent" "$http_x_forwarded_for"';
       access_log   logs/access.log  main; 
       # 允许sendfile方式传输文件，默认为off。
       sendfile     on;
       tcp_nopush   on; # sendfile开启时才开启。
    
       # 设置请求头超时时间
       client_header_timeout 60m;
       # 设置请求体超时时间
       client_body_timeout 60m;
       # 上传文件大小限制
       client_max_body_size 100m;
       # 响应客户端超时时间
       send_timeout 60m;
    
       connection_pool_size 256;
    
    
       # http server块
       # 简单反向代理
       server {
         listen       80;
         server_name  domain2.com www.domain2.com;
         access_log   logs/domain2.access.log  main;
         # 转发动态请求到web应用服务器
         location / {
           proxy_pass      http://127.0.0.1:8000;
           deny 192.24.40.8;  # 拒绝的ip
           allow 192.24.40.6; # 允许的ip   
         }
         # 错误页面
         error_page   500 502 503 504  /50x.html;
            location = /50x.html {
                 root   html;
            }
       }
       # 负载均衡
       upstream backend_server {
         server 192.168.0.1:8000 weight=5; # weight越高，权重越大
         server 192.168.0.2:8000 weight=1;
         server 192.168.0.3:8000;
         server 192.168.0.4:8001 backup; # 热备
       }
       server {
         listen          80;
         server_name     big.server.com;
         access_log      logs/big.server.access.log main;
         charset utf-8;
         client_max_body_size 10M; # 限制用户上传文件大小，默认1M
         location / {
           # 使用proxy_pass转发请求到通过upstream定义的一组应用服务器
           proxy_pass      http://backend_server;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header Host $http_host;
           proxy_redirect off;
           proxy_set_header X-Real-IP  $remote_addr;
         } 
       }
    
       # 可以引入其他文件来扩充server定义
       include /data/nginx-sites/*.conf
     }

###### 2. 请求转发

     # 转发动态请求
     server {  
         listen 80;                                           
         server_name  localhost;                                               
         client_max_body_size 1024M;
         location / {
             proxy_pass http://localhost:8080;   
             proxy_set_header Host $host:$server_port;
         }
     } 
     # http请求重定向到https请求
     server {
         listen 80;
         server_name Domain.com;
         return 301 https://$server_name$request_uri;
     }

###### 3. Nginx全局变量

     $args, 请求中的参数;
     $content_length, HTTP请求信息里的"Content-Length";
     $content_type, 请求信息里的"Content-Type";
     $document_root, 针对当前请求的根路径设置值;
     $document_uri, 与$uri相同;
     $host, 请求信息中的"Host"，如果请求中没有Host行，则等于设置的服务器名;
     $limit_rate, 对连接速率的限制;
     $request_method, 请求的方法，比如"GET"、"POST"等;
     $remote_addr, 客户端地址;
     $remote_port, 客户端端口号;
     $remote_user, 客户端用户名，认证用;
     $request_filename, 当前请求的文件路径名
     $request_body_file,当前请求的文件
     $request_uri, 请求的URI，带查询字符串;
     $query_string, 与$args相同;
     $scheme, 所用的协议，比如http或者是https，比如rewrite ^(.+)$
     $scheme://example.com$1 redirect;        
     $server_protocol, 请求的协议版本，"HTTP/1.0"或"HTTP/1.1";
     $server_addr, 服务器地址;
     $server_name, 请求到达的服务器名;
     $server_port, 请求到达的服务器端口号;
     $uri, 请求的URI，可能和最初的值有不同，比如经过重定向之类的。

###### 4. 静态文件，压缩相关

     http {
         # 开启gzip压缩功能
         gzip on;
    
         # 设置允许压缩的页面最小字节数; 这里表示如果文件小于10k，压缩没有意义.
         gzip_min_length 10k; 
    
         # 设置压缩比率，最小为1，处理速度快，传输速度慢；
         # 9为最大压缩比，处理速度慢，传输速度快; 推荐6
         gzip_comp_level 6; 
    
         # 设置压缩缓冲区大小，此处设置为16个8K内存作为压缩结果缓冲
         gzip_buffers 16 8k; 
    
         # 设置哪些文件需要压缩,一般文本，css和js建议压缩。图片视需要要锁。
         gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml application/xml+rss text/javascript; 
    
    
          # 使用expires选项开启静态文件缓存，10天有效
         location ~ ^/(images|javascript|js|css|flash|media|static)/  {
           root    /var/www/big.server.com/static_files;
           expires 10d;
         }
     }

###### 5. 文件下载服务器

     server {
    
         listen 80 default_server;
         listen [::]:80 default_server;
         server_name  _;
    
         location /download {    
             # 下载文件所在目录
             root /usr/share/nginx/html;
    
             # 开启索引功能
             autoindex on;  
    
             # 关闭计算文件确切大小（单位bytes），只显示大概大小（单位kb、mb、gb）
             autoindex_exact_size off; 
    
             #显示本机时间而非 GMT 时间
             autoindex_localtime on;   
    
             # 对于txt和jpg文件，强制以附件形式下载，不要浏览器直接打开
             if ($request_filename ~* ^.*?\.(txt|jpg|png)$) {
                 add_header Content-Disposition 'attachment';
             }
         }
     }

###### 6. Nginx配置HTTPS

     # 负载均衡，设置HTTPS
     upstream backend_server {
         server APP_SERVER_1_IP;
         server APP_SERVER_2_IP;
     }
    
     # 禁止未绑定域名访问，比如通过ip地址访问
     # 444:该网页无法正常运作，未发送任何数据
     server {
         listen 80 default_server;
         server_name _;
         return 444;
     }
    
     # HTTP请求重定向至HTTPS请求
     server {
         listen 80;
         listen [::]:80;
         server_name your_domain.com;
    
         location / {
             proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
             proxy_set_header X-Forwarded-Proto $scheme;
             proxy_set_header Host $http_host;
             proxy_redirect off;
             proxy_pass http://backend_server; 
          }
    
         return 301 https://$server_name$request_uri;
     }
    
     server {
         listen 443 ssl http2;
         listen [::]:443 ssl http2;
         server_name your_domain.com;
    
         # ssl证书及密钥路径
         ssl_certificate /path/to/your/fullchain.pem;
         ssl_certificate_key /path/to/your/privkey.pem;
    
         # SSL会话信息
         client_max_body_size 75MB;
         keepalive_timeout 10;
    
         location / {
             proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
             proxy_set_header X-Forwarded-Proto $scheme;
             proxy_set_header Host $http_host;
             proxy_redirect off;
             proxy_pass http://django; # Django+uwsgi不在本机上，使用代理转发
         }
    
     }

###### 7. 日志配置

    http {
      # 日志格式及access日志路径
       log_format main '$remote_addr - $remote_user [$time_local]  $status '
         '"$request" $body_bytes_sent "$http_referer" '
         '"$http_user_agent" "$http_x_forwarded_for"';
       access_log   logs/access.log  main;
    
       # favicon.ico无需记录日志
       location = /favicon.ico {
           log_not_found off; 
           access_log off; # 不在access_log记录该项访问
        }
     }

###### 8. 超时请求设置

     # 客户端连接保持会话超时时间，超过这个时间，服务器断开这个链接。
     keepalive_timeout 60;
    
     # 设置请求头的超时时间，可以设置低点。
     # 如果超过这个时间没有发送任何数据，nginx将返回request time out的错误。
     client_header_timeout 15;
    
     # 设置请求体的超时时间，可以设置低点。
     # 如果超过这个时间没有发送任何数据，nginx将返回request time out的错误。
     client_body_timeout 15;
    
     # 响应客户端超时时间
     # 如果超过这个时间，客户端没有任何活动，nginx关闭连接。
     send_timeout 15;
    
     # 上传文件大小限制
     client_max_body_size 10m;
    
     # 也是防止网络阻塞，不过要包涵在keepalived参数才有效。
     tcp_nodelay on;
    
     # 客户端请求头部的缓冲区大小，这个可以根据你的系统分页大小来设置。
     # 一般一个请求头的大小不会超过 1k，不过由于一般系统分页都要大于1k
     client_header_buffer_size 2k;
    
     # 这个将为打开文件指定缓存，默认是没有启用的。
     # max指定缓存数量，建议和打开文件数一致，inactive 是指经过多长时间文件没被请求后删除缓存。
     open_file_cache max=102400 inactive=20s;
    
     # 这个是指多长时间检查一次缓存的有效信息。
     open_file_cache_valid 30s;
    
     # 告诉nginx关闭不响应的客户端连接。这将会释放那个客户端所占有的内存空间。
     reset_timedout_connection on;

###### 9. Proxy反向代理超时设置

     # 该指令设置与upstream服务器的连接超时时间，这个超时建议不超过75秒。
     proxy_connect_timeout 60;
    
     # 该指令设置应用服务器的响应超时时间，默认60秒。
     proxy_read_timeout 60；
    
     # 设置了发送请求给upstream服务器的超时时间
     proxy_send_timeout 60;
    
     # max_fails设定Nginx与upstream服务器通信的尝试失败的次数。
     # 在fail_timeout参数定义的时间段内，如果失败的次数达到此值，Nginx就认为服务器不可用。
    
     upstream big_server_com {
        server 192.168.0.1:8000 weight=5  max_fails=3 fail_timeout=30s; # weight越高，权重越大
        server 192.168.0.2:8000 weight=1  max_fails=3 fail_timeout=30s;
        server 192.168.0.3:8000;
        server 192.168.0.4:8001 backup; # 热备
     }

###### 10. fastcgi

    fastcgi_buffer_size 128k;
    fastcgi_buffers 32 32k;
    fastcgi_connect_timeout 10s;
    fastcgi_read_timeout 60m;
    fastcgi_send_timeout 60m;
    fastcgi_busy_buffers_size 256k;