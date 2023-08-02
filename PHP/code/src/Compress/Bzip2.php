<?php
namespace Lpp\Compress;

class Bzip2 implements CompressInterface
{
    public function compress($data)
    {
        return bzcompress($data);
    }

    public function unCompress($data)
    {
        return bzdecompress($data);
    }
}
