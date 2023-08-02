<?php
namespace Lpp\Compress;

class Gzip implements CompressInterface
{
    public function compress($data)
    {
        return gzencode($data);
    }

    public function unCompress($data)
    {
        return gzdecode($data);
    }
}
