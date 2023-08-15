# Session

## 参考博文

1. [Session是怎么实现的？存储在哪里？_session存在_葡萄干是个程序员的博客-CSDN博客](https://blog.csdn.net/qq_15096707/article/details/74012116)

2. [PHP中Session使用方法详解](https://zhuanlan.zhihu.com/p/94689703)

3. [php官方配置](https://www.php.net/manual/zh/session.configuration.php)

4. [session_set_save_handler](https://www.php.net/manual/zh/function.session-set-save-handler.php)

5. [yii2-Session](https://github.com/yiichina/yii2/blob/api/framework/web/Session.php)

6. [yii2-DbSession](https://github.com/yiichina/yii2/blob/api/framework/web/DbSession.php)

## 配置

```shell
session.save_handler=files;
session.save_path=/var/lib/php/sessions;
```

## 使用

1. 开启
   
   ```php
   // 在执行开启之前，不能有其他输出，否则无效
   session_start();
   ```

2. 设置
   
   ```php
   $_SESSION['username'] = 'pp';
   ```

3. 获取
   
   ```php
   $username = $_SESSION['username'];
   ```

4. 销毁
   
   ```php
   $_SESSION = [];
   // 若要彻底销毁，需执行以下函数
   session_destroy();
   ```

5. 自定义处理，比如使用db或者redis
   
   ```shell
   1. php.ini里设置自定义handler，貌似也可以无需处理这个参数，后面注册handler即可
   session.save_handler=user;
   
   2. 编写自己的handler方法，里面可以使用db或者redis来处理session
   需实现里面的open、close、read、write、destroy、gc方法
   class MySessionHandler implements SessionHandlerInterface {}
   
   3. 注册自己的handler对象
   $handler = new MySessionHandler();
   session_set_save_handler($handler, true);
   ```

## 其他

1. `PHP`的`Session`默认以文件存储；`Java`的`Session`默认存储在内存里

2. 必须在`session_start()`之后才会存储`session`

3. 若有多台机，需要考虑`session`共享问题：`ngx`转发同一`session`到同一台机；使用`db`；使用缓存(`redis`、`memecache`)；使用其他共享网盘文件

4. 有些系统默认文件路径为`/tmp`，有些默认为`/var/lib/php/sessions`，存储在以`sess_`开头的文件
