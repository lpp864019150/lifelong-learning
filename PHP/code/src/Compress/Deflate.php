<?php
namespace Lpp\Compress;

class Deflate implements CompressInterface
{
    public function compress($data)
    {
        return gzdeflate($data);
    }

    public function unCompress($data)
    {
        return gzinflate($data);
    }
}
