# try catch影响性能吗

## 参考资料

1. [使用 try-catch 会影响性能吗？大部分人都会答错！_java面试笔试的博客-CSDN博客](https://blog.csdn.net/Y0Q2T57s/article/details/129361115)

2. [try catch 对性能影响_心中要有一片海的博客-CSDN博客](https://blog.csdn.net/lylwo317/article/details/51869893)

## 结论

1. 有影响，比不用性能差一点，但是差距不大

2. 优先保证正确性再来考虑性能

3. 如果性能没有出问题，不必纠结于是否使用`try catch`，大胆用就是了

4. 尽量在靠近`catch`的地方使用，减少影响的范围，当然如果不确定的话范围扩大也无所谓

5. 需根据业务逻辑来使用，不可随意改变位置