# hf协程问题

## 背景

1. 已知，协程上下文，`Context::set`之后，可以通过`Context::get`获取

2. 在用户获取下拉框列表时，采用了协程，新开多个协程来并发处理多个下拉框列表

3. 在新开的协程里，`Context::get`获取的值为`null`

## 分析

1. `Context`是协程上下文，可在同一协程里共享，一旦设置，可在随后任何地方随时获取

2. 但是新开协程，则无法在新协程里共享其他协程的上下文信息

## 解决

1. 可以使用参数进行传递，在需要使用某个变量时，在构建协程时传递过去

2. 在开启新协程时，重新把需要用到的变量利用`Context`设置一遍，这里同样需要传递参数，只是在设置一遍之后，后续在该协程里面的操作无需再次传递参数
   
   ```php
   $parallel = new Parallel(10);
   $contexts = di(ContextService::class)->getContexts();
   $parallel->add(function () use ($contexts) {
       di(ContextService::class)->setContexts($contexts);
   }
   ```

## 总结

1. 协程给我们提供了并发的可能性，在某些场景给我们带来了极大的便利，但是也不可滥用

2. 多了解协程，多思考使用场景，在用到协程的地方需要谨慎对待
