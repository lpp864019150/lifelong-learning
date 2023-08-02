# 优化冗余的if-else

## 参考博文

1. [可以一学的代码优化小技巧：减少if-else冗余 - 掘金](https://juejin.cn/post/7185440368647733306)

2. [使用switch(true)代替冗长的if-else - 简书](https://www.jianshu.com/p/49aaecf021d0)

## 技巧一：短路法

1. 在判断时让其短路，直接`return`，若未触发`return`则自然会流入`else`分支
   
   ```php
   if ($express1) return 1;
   if ($express2) return 2;
   return 0;
   ```

2. 直接短路并执行操作
   
   ```php
   $a = 0;
   // 前面的表达式若成功，则执行后面的代码，也即代替了 if (express) $a = 1;
   $express && $a = 1;
   // 前面的表达式若不成功，则执行后面的代码，也即代替了 if (!express) $a = 1;
   $express || $a = 1;
   ```

## 技巧二：三元表达式

```php
$a = $express ? 1 : 2;
```

## 技巧三：使用switch

这里需要做一些转换，需要能最终产生一个值并针对这个值来判断，不一定能替代`if-elseif-else`模式

```php
switch ($express) {
    case $value1:
        return 1;
    case $value2:
        return 2;
    default:
        return 0;
}
```

## 技巧四：巧用switch(true)

1. 可以完美代替`if-elseif-else`模式，在`case`里面把`if`条件直接平移过去即可

2. 这里巧妙的使用了`true`为一个值，然后`case`里面也必须出一个`true`否则执行`default`，完美替代了if里面的`bool`判断了

```php
swith (true) {
    case $express1:
        return 1;
    case $express2:
        return 2;
    default:
        return 0;
}
```

## 技巧五：改用设计模式，策略模式

1. 这里是把`if-elseif-else`里面的模块进行封装，避免直接在主流程里写代码，并且对多个分支进行封装更易维护，不易出错

2. 在使用时依然需要选择一个分支来执行，只是代码封装了，更简洁

## 技巧六：使用对象

1. 这里是前面技巧三`switch`的优化，写起来更加简洁易懂，但是依然存在一个问题，需要提炼一个能产生最终值的表达式，若不能则无法使用该方式

2. 当然，这个也算是技巧五设计模式的一种简单实现版，也是对各分支进行封装

```php
$maps = [
    'a' => function() {},
    'b' => function() (),
];
$maps[$express]();
```
