<?php

class BloomFilter
{
    private $redis;
    private $hashFunctions;
    private $size;
    private $hashIterations;

    public function __construct(int $size = 1000, int $hashIterations = 3)
    {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1');
        $this->size = $size;
        $this->hashIterations = $hashIterations;
        $this->hashFunctions = [
            function ($str) {
                return crc32($str);
            },
            function ($str) {
                return strlen($str) > 0 ? ord($str[0]) : 0;
            },
            function ($str) {
                $hash = 0;
                for ($i = 0; $i < strlen($str); $i++) {
                    $hash = (31 * $hash + ord($str[$i])) % $this->size;
                }
                return $hash;
            },
        ];
    }

    public function add(string $str): void
    {
        for ($i = 0; $i < $this->hashIterations; $i++) {
            $index = $this->hashFunctions[$i]($str) % $this->size;
            $this->redis->setbit('bloom_filter', $index, 1);
        }
    }

    public function contains(string $str): bool
    {
        for ($i = 0; $i < $this->hashIterations; $i++) {
            $index = $this->hashFunctions[$i]($str) % $this->size;
            if (!$this->redis->getbit('bloom_filter', $index)) {
                return false;
            }
        }
        return true;
    }
}