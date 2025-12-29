<?php
if (!defined('IN_SYSTEM')) die('Access Denied');

return [
    // 支付接口域名
    'PAYMENT_URL' => getenv('PAYMENT_URL') ?: 'https://pay.example.com/mapi.php',
    
    // 商户ID
    'MERCHANT_ID' => getenv('MERCHANT_ID') ?: '1000',
    
    // 商户KEY
    'API_KEY' => getenv('API_KEY') ?: 'xxxxxxxxxxxxxxxxxxxxxx',
    
    // 你的域名
    'NOTIFY_URL' => getenv('NOTIFY_URL') ?: 'https://example.com/pay/notify.php',
    
    // 你的域名
    'RETURN_URL' => getenv('RETURN_URL') ?: 'https://example.com/success.html'
];
