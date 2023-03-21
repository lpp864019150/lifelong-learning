<?php

class BloomFilterArray
{
    private $bitmap;     // 位图数组
    private $hashCount;  // 哈希函数个数
    private $size;       // 位图大小
    private $hashFuncs;  // 哈希函数列表

    public function __construct(int $size = 1000, int $hashCount = 5)
    {
        $this->bitmap = array_fill(0, $size, 0);
        $this->hashCount = $hashCount;
        $this->size = $size;

        // 初始化哈希函数
        $this->hashFuncs = [];
        for ($i = 0; $i < $hashCount; $i++) {
            $this->hashFuncs[] = new HashFunc($i);
        }
    }

    // 添加元素
    public function add($element)
    {
        foreach ($this->hashFuncs as $hashFunc) {
            $index = $hashFunc->hash($element) % $this->size;
            $this->bitmap[$index] = 1;
        }
    }

    // 查询元素是否存在
    public function exists($element)
    {
        foreach ($this->hashFuncs as $hashFunc) {
            $index = $hashFunc->hash($element) % $this->size;
            if ($this->bitmap[$index] == 0) {
                return false;
            }
        }
        return true;
    }
}

// 哈希函数类
class HashFunc
{
    private $seed;

    public function __construct($seed)
    {
        $this->seed = $seed;
    }

    // 计算哈希值
    public function hash($value)
    {
        return crc32($value . $this->seed);
    }
}

// 使用示例
$bf = new BloomFilterArray();
$bf->add('foo');
$bf->add('bar');

if ($bf->exists('foo')) {
    echo "foo exists\n";
}

if ($bf->exists('baz')) {
    echo "baz exists\n";
} else {
    echo "baz not exists\n";
}