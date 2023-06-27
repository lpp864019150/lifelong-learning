#### 1、背景

钉钉监控信息如下：

```shell
2023-06-21 18:30:27 Detecting 1 throwable occurred during parallel execution:
(1) Hyperf\Utils\Exception\ParallelExecutionException: Detecting 1 throwable occurred during parallel execution:
(preview) Swoole\ExitException: swoole exit
#0 /home/wwwroot/box-api/app/Util/Functions.php(314): dd()
#1 /home/wwwroot/box-api/vendor/hyperf/utils/src/Parallel.php(64): {closure}()
#2 /home/wwwroot/box-api/vendor/hyperf/utils/src/Functions.php(274): Hyperf\Utils\Parallel->Hyperf\Utils\{closure}()
#3 /home/wwwroot/box-api/vendor/hyperf/utils/src/Coroutine.php(62): call(Object(Closure))
#4 {main}

#0 /home/wwwroot/box-api/app/Util/Functions.php(317): Hyperf\Utils\Parallel->wait()
#1 /home/wwwroot/box-api/app/Service/PostService.php(291): parallelResult(Array)
#2 /home/wwwroot/box-api/app/Util/Functions.php(314): App\Service\PostService->App\Service\{closure}()
#3 /home/wwwroot/box-api/vendor/hyperf/utils/src/Parallel.php(64): {closure}()
#4 /home/wwwroot/box-api/vendor/hyperf/utils/src/Functions.php(274): Hyperf\Utils\Parallel->Hyperf\Utils\{closure}()
#5 /home/wwwroot/box-api/vendor/hyperf/utils/src/Coroutine.php(62): call(Object(Closure))
#6 {main}
[77] in /home/wwwroot/box-api/vendor/hyperf/utils/src/Parallel.php
```

#### 2、分析

1. 根据报错信息，`swoole exit`，大概是执行了退出函数，比如`exit`、`die`之类的

2. 根据报错信息提示，`Functions.php`第314行，执行了`dd()`函数。代码如下：
   
   ```php
   return is_callable($v) ? call_user_func($v) : $v;
   ```

3. 继续跟踪调用的地方，`PostService.php(291): parallelResult(Array)`，也即此处传入的数组某一个地方执行了`dd()`函数

4. 定位到`dd`函数定义的地方，通过反向查找调用的地方，发现上面的`service`并未调用该方法
   
   ```php
   if (!function_exists('dd')) {
    function dd() {
        var_dump(...func_get_args());
        die();
    }
   }
   ```

5. 继续排查，`parallelResult`方法会判定数组的值是否`callable`，若可执行则会被执行，这里应该是数组里的某个值是`dd`，然后被执行了，经分析最有可能是帖子信息

6. 通过查找报错日志，找到报错的请求接口，分析大概报错的`post_id`，经查找，确实用户直接输入了`dd`，这被判定为`callable`然后被执行了
   ![image](https://xcg-box.bygamesdk.com//img/202306/25/_d492bd0982a9955b.png)

7. 问题找到了，用户输入的字符串`dd`被执行了，然后`dd`里面`die`导致`swoole exit`

#### 3、解决

1. 可以直接删掉`dd`函数或者修改名称即可避免报错，但是这里只能解决`dd`被执行的问题，若用户提交的信息触发了其他函数呢，依然会出现不可控的值。

2. 回到报错那一行代码，`is_callable`会把函数名相同的字符串判定为`true`，但其实这里的设计初衷是数组里面的某些值比较耗时，不直接执行函数而是利用匿名函数来包裹，利用`swoole`的协程去并发执行多个函数。那么这里改成判定是否匿名函数即可解决问题。
   
   ```php
   return is_callable($v) ? call_user_func($v) : $v;
   ```
   
   修改后：
   
   ```php
   return $v instanceof \Closure ? call_user_func($v) : $v;
   ```

3. 匿名函数是继承自`Closure`类，通过上面的修改问题得到解决。

#### 4、总结

1. 不要信任用户的任何输入值，对用户的输入值要谨慎处理，避免注入
2. `is_callable`, `call_user_func`等，对于用户输入值要慎用，避免注入
3. 提高风险意识

#### 5、参考文档

1. [PHP:匿名函数](https://www.php.net/manual/zh/functions.anonymous.php)
