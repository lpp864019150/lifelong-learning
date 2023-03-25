<?php

namespace Lpp\BloomFilter;

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