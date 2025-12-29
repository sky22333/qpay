<?php

class OrderFile {
    // 订单状态常量
    const STATUS_CREATED = 'CREATED';           // 订单已创建
    const STATUS_PAID = 'PAID';                 // 已支付（支付成功）
    const STATUS_AMOUNT_MISMATCH = 'AMOUNT_MISMATCH';  // 回调的支付金额和订单金额不符
    const STATUS_EXCEPTION = 'EXCEPTION';        // 支付异常
    
    private static $orderDir;
    
    public static function init() {
        self::$orderDir = dirname($_SERVER['DOCUMENT_ROOT']) . "/log/orders/";
        self::ensureOrderDirectory();
    }
    
    /**
     * 创建订单文件
     * @return bool 是否成功创建(false表示订单已存在)
     */
    public static function create($order_id, $money, $type, $client_ip) {
        // 1. 检查是否为双数小时
        if (intval(date('H')) % 2 === 0) {
            $gcStatusFile = self::$orderDir . 'gc_status.json';
            $currentHour = date('YmdH'); // 2023122910
            
            // 2. 读取状态文件，判断当前小时是否已执行
            $shouldRunGc = true;
            if (file_exists($gcStatusFile)) {
                $status = json_decode(file_get_contents($gcStatusFile), true);
                if ($status && isset($status['last_run_hour']) && $status['last_run_hour'] === $currentHour) {
                    $shouldRunGc = false;
                }
            }
            
            // 3. 执行清理并更新状态
            if ($shouldRunGc) {
                // 使用 JSON 格式记录状态，方便人工查看和管理
                $newStatus = [
                    'last_run_hour' => $currentHour,
                    'last_run_time' => date('Y-m-d H:i:s'),
                    'description' => '上次清理任务执行时间'
                ];
                file_put_contents($gcStatusFile, json_encode($newStatus, JSON_PRETTY_PRINT), LOCK_EX);
                
                self::gc(5);
                Logger::clean(5);
            }
        }

        $orderFile = self::getOrderFilePath($order_id);
        if (file_exists($orderFile)) {
            return false;
        }

        $orderData = [
            'order_id' => $order_id,
            'money' => $money,
            'type' => $type,
            'client_ip' => $client_ip,
            'create_time' => date('Y-m-d H:i:s'),
            'trade_status' => self::STATUS_CREATED
        ];

        file_put_contents($orderFile, json_encode($orderData, JSON_PRETTY_PRINT), LOCK_EX);
        Logger::log('订单', "创建订单文件: {$order_id}", $orderData);
        return true;
    }

    /**
     * 更新订单状态
     */
    public static function updateStatus($order_id, $status, $trade_no = '', $notify_time = '', $extra_data = []) {
        $orderData = self::getData($order_id);
        if ($orderData) {
            $orderData['trade_status'] = $status;
            if ($trade_no) {
                $orderData['trade_no'] = $trade_no;
            }
            if ($notify_time) {
                $orderData['notify_time'] = $notify_time;
            }
            // 合并额外数据
            if (!empty($extra_data)) {
                $orderData = array_merge($orderData, $extra_data);
            }
            return self::saveData($order_id, $orderData);
        }
        return false;
    }

    /**
     * 获取订单数据
     * @return array|null 订单数据,不存在返回null
     */
    public static function getData($order_id) {
        $orderFile = self::getOrderFilePath($order_id);
        if (!file_exists($orderFile)) {
            return null;
        }
        return json_decode(file_get_contents($orderFile), true);
    }

    /**
     * 检查订单支付状态
     */
    public static function checkStatus($order_id) {
        $orderData = self::getData($order_id);
        if (!$orderData) {
            return ['success' => false, 'message' => '订单不存在'];
        }
        
        switch ($orderData['trade_status']) {
            case self::STATUS_PAID:
                return ['success' => true, 'message' => '支付成功'];
            case self::STATUS_CREATED:
                return ['success' => false, 'message' => '等待支付'];
            case self::STATUS_AMOUNT_MISMATCH:
                return ['success' => false, 'message' => '支付金额不匹配'];
            case self::STATUS_EXCEPTION:
                return ['success' => false, 'message' => '支付异常'];
            default:
                return ['success' => false, 'message' => '未知状态'];
        }
    }

    /**
     * 垃圾回收：清理旧订单文件
     * @param int $days 保留天数
     */
    public static function gc($days = 5) {
        if (!is_dir(self::$orderDir)) return;
        
        $files = glob(self::$orderDir . '*');
        $expireTime = time() - ($days * 86400);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if (filemtime($file) < $expireTime) {
                    @unlink($file);
                }
            }
        }
        Logger::log('系统', "执行订单清理GC，清理 {$days} 天前的文件");
    }

    /**
     * 保存订单数据
     * @param string $order_id 订单ID
     * @param array $orderData 订单数据
     * @return bool 保存是否成功
     */
    private static function saveData($order_id, $orderData) {
        $orderFile = self::getOrderFilePath($order_id);
        try {
            file_put_contents($orderFile, json_encode($orderData, JSON_PRETTY_PRINT), LOCK_EX);
            return true;
        } catch (Exception $e) {
            Logger::log('错误', "保存订单数据失败: {$order_id}", [
                'error' => $e->getMessage(),
                'data' => $orderData
            ]);
            return false;
        }
    }

    private static function getOrderFilePath($order_id) {
        return self::$orderDir . $order_id . '.json';
    }

    private static function ensureOrderDirectory() {
        if (!is_dir(self::$orderDir)) {
            mkdir(self::$orderDir, 0777, true);
        }
    }
}

// 初始化订单文件系统
OrderFile::init();
