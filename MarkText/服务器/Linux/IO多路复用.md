# IO多路复用

## 参考博文

1. [IO多路复用详解 - 掘金](https://juejin.cn/post/7263731372606341177)
2. [一文读懂五大 IO 模型的前世今生（ select、epoll、epoll） - 掘金](https://juejin.cn/post/7265903586223456295)
3. [半小时搞懂 IO 模型 - 掘金](https://juejin.cn/post/7261252130441707557)
4. [用户态与内核态 -- 帮你解惑，直达本质 - 掘金](https://juejin.cn/post/6920621924791894023)
5. [最近沉迷Redis网络模型，无法自拔！终于知道Redis为啥这么快了 - 掘金](https://juejin.cn/post/7174290554467909639)

## 概念

> IO指Input/Output，输入输出流，常见的包括磁盘IO，网络IO，内存映射IO
> 
> 以网络IO为例，一个socket由[ClientIp, ClientPort, ServerIp, ServerPort, Protocol] 5元素可以唯一标识，多个客户端连接到同一个服务端视为多个"路"，服务器由同一个线程处理多个客户端就叫做IO多路复用

## 基础socket模型

```c
listenSocket = socket(); //系统调用socket()函数，调用创建一个主动ocket
bind(listenSocket);  //给主动socket绑定地址和端口
listen(listenSocket); //将默认的主动socket转换为服务器使用的被动socket(也叫监听socket)
while (true) { //循环监听客户端连接请求
   connSocket = accept(listenSocket); //接受客户端连接，获取已连接socket
   recv(connsocket); //从客户端读取数据，只能同时处理一个客户端
   send(connsocket); //给客户端返回数据，只能同时处理一个客户端
}
```

## 实现方式

1. `select`
   
   ```c
   /** *  参数说明 *  监听的文件描述符数量__nfds、 *  被监听描述符的三个集合*__readfds,*__writefds和*__exceptfds *  监听时阻塞等待的超时时长*__timeout *  返回值：返回一个socket对应的文件描述符    */
      int select(int __nfds, fd_set * __readfds, fd_set * __writefds, fd_set * __exceptfds, struct timeval * __timeout)
   ```
   
    `select` 使用固定长度的 `BitsMap`，表示文件描述符集合，维护一个待处理`rset`集合
   
    轮询`rset`集合，若有准备就绪的`fd`，则进入处理
   
    最多同时处理`FD_SETSIZE`(默认最大`1024`)个客户端

2. `poll`
   
   ```c
   /** * 参数 *__fds 是 pollfd 结构体数组，pollfd 结构体里包含了要监听的描述符，以及该描述符上要监听的事件类型 * 参数 __nfds 表示的是 *__fds 数组的元素个数 *  __timeout 表示 poll 函数阻塞的超时时间    */
      int poll (struct pollfd *__fds, nfds_t __nfds, int __timeout);
      pollfd结构体的定义
   
      struct pollfd {
         int fd;         //进行监听的文件描述符
         short int events;       //要监听的事件类型
         short int revents;      //实际发生的事件类型
      };
   ```
   
   维护一个待处理的`pollfd`数组(链表)，突破了`select`的文件描述符个数限制
   
   轮询`pollfd`数组，若有准备就绪的`fd`，则进入处理
   
   可自定义个数

3. `epoll`
   
   ```c
   typedef union epoll_data
   {
       ...
       int fd;  //记录文件描述符
        ...
   } epoll_data_t;
   
   struct epoll_event
   {
   
       uint32_t events;  //epoll监听的事件类型
       epoll_data_t data; //应用程序数据
   
   };
   ```
- `int epoll_create(int size);` 创建一个`epoll`的句柄，`size`用来告诉内核这个监听的数目一共有多大。`epoll`实例内部维护了两个结构，分别是记录要监听的`fd`(红黑树)和已经就绪的`fd`(链表)，而对于已经就绪的文件描述符来说，它们会被返回给用户程序进行处理。

- `int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event); ``epoll`的事件注册函数，`epoll_ctl`向 `epoll`对象中添加、修改或者删除感兴趣的事件，成功返回0，否则返回–1。此时需要根据errno错误码判断错误类型。它不同与`select()`是在监听事件时告诉内核要监听什么类型的事件，而是在这里先注册要监听的事件类型。

- `epoll_wait`方法返回的事件必然是通过 `epoll_ctl`添加到 `epoll`中的。 `int epoll_wait(int epfd, struct epoll_event * events, int maxevents, int timeout); `等待事件的产生，类似于`select()`调用。参数`events`用来从内核得到事件的集合，`maxevents`是`events`集合的大小，且不大于`epoll_create()`时的`size`，参数`timeout`是超时时间（毫秒，0会立即返回，-1将不确定，也有说法说是永久阻塞）。函数返回需要处理的事件数目，返回0表示已超时，返回–1表示错误，需要检查 errno错误码判断错误类型。 关于`epoll`的`ET`和`LT`两种工作模式
  
  `epoll_create, epoll_ctl, epoll_wait`
  
  设置一个监听的`fd`数组和已经就绪的数组，直接处理已就绪的数组里面的`fd`即可
  
  事件驱动，`epoll_wait`事件回调，无需轮询
  
  可自定义个数

## 处理流程对比

`epoll`与`select/poll`的主要差别

```diff
- 每次调用需要在用户态和内核态之间拷贝文件描述符数组，但高并发场景下这个拷贝的消耗是很大的。
方案：内核中保存一份文件描述符，无需用户每次传入，而是仅同步修改部分。

- 内核检测文件描述符可读还是通过遍历实现，当文件描述符数组很长时，遍历操作耗时也很长。
方案：通过事件唤醒机制唤醒替代遍历。

- 内核检测完文件描述符数组后，当存在可读的文件描述符数组时，用户态需要再遍历检测一遍。
方案：仅将可读部分文件描述符同步给用户态，不需要用户态再次遍历。
```

![image](https://p3-juejin.byteimg.com/tos-cn-i-k3u1fbpfcp/bf1fea044b964a30bb1e2ac6ebda9c21~tplv-k3u1fbpfcp-zoom-in-crop-mark:1512:0:0:0.awebp)

## 框架

1. `Redis`，支持`select`、`epoll`

2. `Nginx`，支持`select`、`epoll`、`kqueue`；使用ET模式的epoll

3. `Reactor`，`Netty`，高性能网络编程框架，`Java`里面的`NIO`也是基于多路复用`IO`