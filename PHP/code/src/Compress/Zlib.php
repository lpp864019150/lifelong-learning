<?php
namespace Lpp\Compress;

class Zlib implements CompressInterface
{
    public function compress($data)
    {
        return gzcompress($data);
    }

    public function unCompress($data)
    {
        return gzuncompress($data);
    }
}