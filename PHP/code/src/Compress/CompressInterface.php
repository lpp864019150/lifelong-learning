<?php
namespace Lpp\Compress;

interface CompressInterface
{
    public function compress($data);

    public function unCompress($data);
}
