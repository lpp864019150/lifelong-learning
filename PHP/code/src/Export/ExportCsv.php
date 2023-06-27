<?php
namespace Lpp\Export;

class ExportCsv
{
    // 导出普通csv文件
    public function exportCsv()
    {
        $filename = 'test.csv';
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $fp = fopen($filename, 'w');
        foreach ($data as $item) {
            fputcsv($fp, $item);
        }
        fclose($fp);
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        readfile($filename);
        unlink($filename);
    }

    // 导出压缩后的csv文件，压缩格式为zip
    public function exportZipCsv()
    {
        $filename = 'test.csv';
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $fp = fopen($filename, 'w');
        foreach ($data as $item) {
            fputcsv($fp, $item);
        }
        fclose($fp);
        $zip = new \ZipArchive();
        $zipName = 'test.zip';
        $zip->open($zipName, \ZipArchive::CREATE);
        $zip->addFile($filename, $filename);
        $zip->close();
        header("Content-type:application/zip");
        header("Content-Disposition:attachment;filename=" . $zipName);
        readfile($zipName);
        unlink($filename);
        unlink($zipName);
    }

    // 导出.csv.gz文件，使用gz函数
    public function exportGzCsv()
    {
        $filename = 'test.csv';
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $fp = fopen($filename, 'w');
        foreach ($data as $item) {
            fputcsv($fp, $item);
        }
        fclose($fp);
        $gzName = 'test.csv.gz';
        $gz = gzopen($gzName, 'w9');
        gzwrite($gz, file_get_contents($filename));
        gzclose($gz);
        header("Content-type:application/gzip");
        header("Content-Disposition:attachment;filename=" . $gzName);
        readfile($gzName);
        unlink($filename);
        unlink($gzName);
    }

    // 导出.csv.gz文件，不生成csv文件，直接使用gz函数
    public function exportGzCsv2()
    {
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $gzName = 'test.csv.gz';
        $gz = gzopen($gzName, 'w9');
        foreach ($data as $item) {
            gzwrite($gz, implode(',', $item) . "\n");
        }
        gzclose($gz);
        header("Content-type:application/gzip");
        header("Content-Disposition:attachment;filename=" . $gzName);
        readfile($gzName);
        unlink($gzName);
    }

    // 导出.tsv.gz文件，不生成tsv文件，直接使用gz函数
    // 需注意直接使用implode函数时，如果数据中有\t，会导致数据错位，所以需要使用array_map函数将数据中的\t替换成空格
    public function exportGzTsv()
    {
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $gzName = 'test.tsv.gz';
        $gz = gzopen($gzName, 'w9');
        foreach ($data as $item) {
            gzwrite($gz, implode("\t", array_map(function($v){ return str_replace("\t", ' ', $v);}, $item)) . "\n");
        }
        gzclose($gz);
        header("Content-type:application/gzip");
        header("Content-Disposition:attachment;filename=" . $gzName);
        readfile($gzName);
        unlink($gzName);
    }

    // 导出.csv.tar.gz文件
    // @link: https://www.cnblogs.com/freephp/p/4935593.html
    public function exportTarGz()
    {
        $filename = 'test.csv';
        $data = [
            ['id' => 1, 'name' => '张三', 'age' => 18],
            ['id' => 2, 'name' => '李四', 'age' => 19],
            ['id' => 3, 'name' => '王五', 'age' => 20],
        ];
        $fp = fopen($filename, 'w');
        foreach ($data as $item) {
            fputcsv($fp, $item);
        }
        fclose($fp);
        $tarName = 'test.tar';
        $tar = new \PharData($tarName);
        $tar->addFile($filename);
        $tar->compress(\Phar::GZ);
        header("Content-type:application/gzip");
        header("Content-Disposition:attachment;filename=" . $tarName . '.gz');
        ob_clean(); // 清除缓冲区内容
        readfile($tarName . '.gz');
        unlink($filename);
        unlink($tarName);
        unlink($tarName . '.gz');
    }


}