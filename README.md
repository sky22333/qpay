### 📌 项目介绍
**Qpay** 是一个 **轻量级、响应式的打赏支付系统**，适用于 **个人 / 商家支付系统、在线充值、订单付款** 等场景。  
基于 **Tailwind CSS + JavaScript**，提供 **直观的支付交互、订单轮询检测、二维码生成**，无需数据库，可快速集成易支付。

### Docker部署
```
docker run -d \
  --name qpay \
  --restart=always \
  -p 8080:80 \
  -e SITE_URL=https://your-domain.com \
  -e PAY_DOMAIN=https://pay.example.com \
  -e MERCHANT_ID=1000 \
  -e API_KEY=xxxxxxxxxxxxxxxxxxxxxx \
  ghcr.io/sky22333/qpay
```


#### 提示
允许0.9元的误差
https://github.com/sky22333/qpay/blob/641dd91eef31fad398cbeb7e048d6921fdc51db4/public/pay/notify.php#L67
