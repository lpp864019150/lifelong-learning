<?php

//declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH . '/vendor/autoload.php';

$errorLog = \logger('error', 'error');
// 设置错误和异常处理程序
set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($errorLog) {
    $errorLog->error($errstr, array('errno' => $errno, 'file' => $errfile, 'line' => $errline));
});

set_exception_handler(function ($exception) use ($errorLog) {
    $errorLog->error($exception->getMessage(), array('exception' => $exception));
});