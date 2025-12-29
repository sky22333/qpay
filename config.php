<?php
if (!defined('IN_SYSTEM')) die('Access Denied');

// 获取基础域名配置
$site_url = getenv('SITE_URL') ?: 'https://example.com';
$pay_domain = getenv('PAY_DOMAIN') ?: 'https://pay.example.com';

// 移除末尾斜杠，确保拼接正确
$site_url = rtrim($site_url, '/');
$pay_domain = rtrim($pay_domain, '/');

return [
    // 你的域名 (例如: https://example.com)
    'SITE_URL' => $site_url,
    
    // 支付平台域名 (例如: https://pay.example.com)
    'PAY_DOMAIN' => $pay_domain,

    // 商户ID
    'MERCHANT_ID' => getenv('MERCHANT_ID') ?: '1000',
    
    // 商户KEY
    'API_KEY' => getenv('API_KEY') ?: 'xxxxxxxxxxxxxxxxxxxxxx',
    
    // 自动拼接完整路径 (支持通过环境变量覆盖)
    'PAYMENT_URL' => getenv('PAYMENT_URL') ?: ($pay_domain . '/mapi.php'),
    'NOTIFY_URL' => getenv('NOTIFY_URL') ?: ($site_url . '/pay/notify.php'),
    'RETURN_URL' => getenv('RETURN_URL') ?: ($site_url . '/success.php')
];
