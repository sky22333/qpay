<?php
// 定义安全常量，允许加载 config.php
if (!defined('IN_SYSTEM')) {
    define('IN_SYSTEM', true);
}

// 加载并返回根目录下的配置文件 (此时在 public 上级)
return require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/config.php';
