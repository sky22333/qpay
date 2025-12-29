<?php

class Logger {
    private static $logFile;
    
    public static function init() {
        self::$logFile = dirname($_SERVER['DOCUMENT_ROOT']) . "/log/log-" . date('Y-m-d') . ".log";
        self::ensureLogDirectory();
    }
    
    /**
     * 记录日志
     * @param string $type 日志类型
     * @param string $message 日志信息
     * @param mixed $data 相关数据
     * @param string $client_ip 客户端IP
     */
    public static function log($type, $message, $data = null, $client_ip = '') {
        $log = sprintf(
            "[%s] 【%s】 %s%s %s\n",
            date('Y-m-d H:i:s'),
            $type,
            $message,
            $client_ip ? " | IP: {$client_ip}" : "",
            $data ? "数据: " . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ""
            );
            file_put_contents(self::$logFile, $log, FILE_APPEND | LOCK_EX);
        }
    
    private static function ensureLogDirectory() {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    /**
     * 清理旧日志文件
     * @param int $days 保留天数
     */
    public static function clean($days = 5) {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) return;
        
        $files = glob($logDir . '/log-*.log');
        $expireTime = time() - ($days * 86400);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                // 解析文件名中的日期，或者直接使用文件修改时间
                // 这里使用文件修改时间比较简单可靠
                if (filemtime($file) < $expireTime) {
                    @unlink($file);
                }
            }
        }
    }
}

// 初始化日志系统
Logger::init();
