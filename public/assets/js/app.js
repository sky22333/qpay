let selectedPayment = "wxpay";
let checkInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    if (window.location.pathname.includes('success.php')) {

    } else {
        document.querySelector('.fade-in').classList.add('active');
        
        // 支付方式切换事件
        const wxpayBtn = document.getElementById('wxpay-btn');
        const alipayBtn = document.getElementById('alipay-btn');
        
        wxpayBtn.addEventListener('click', function() {
            selectedPayment = 'wxpay';
            wxpayBtn.classList.add('selected');
            alipayBtn.classList.remove('selected');
        });
        
        alipayBtn.addEventListener('click', function() {
            selectedPayment = 'alipay';
            alipayBtn.classList.add('selected');
            wxpayBtn.classList.remove('selected');
        });

        const amountSelect = document.getElementById('amount-select');
        const customAmount = document.getElementById('custom-amount');

        if (amountSelect && customAmount) {
            amountSelect.classList.add('hidden');
            customAmount.classList.remove('hidden');
        }

        if (amountSelect) {
            amountSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customAmount.classList.remove('hidden');
                    this.classList.add('hidden');
                } else {
                    customAmount.classList.add('hidden');
                    this.classList.remove('hidden');
                }
            });
        }

        const submitButton = document.getElementById('submit');
        if (submitButton) {
            const resetButton = () => {
                submitButton.disabled = false;
                submitButton.innerText = "前往付款";
                submitButton.onclick = createOrder;
            };

            const createOrder = () => {
                const amountSelect = document.getElementById('amount-select');
                const customAmount = document.getElementById('custom-amount').value;
                const qrcodeContainer = document.getElementById('qrcode-container');
                const qrcodeDiv = document.getElementById('qrcode');
                const qrLoader = document.getElementById('qr-loader');
                const orderInfo = document.getElementById('order-info');

                let amount = amountSelect.value === 'custom' ? customAmount : amountSelect.value;

                if (!amount || parseFloat(amount) <= 0) {
                    alert("❌ 请输入有效的支付金额");
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerText = "正在创建订单...";

                qrcodeContainer.classList.remove("hidden");
                qrcodeDiv.classList.add("hidden");
                qrLoader.style.display = "block";

                fetch('/pay/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `money=${amount}&type=${selectedPayment}`
                })
                .then(response => response.text())
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('Response text:', text);
                        throw new Error(`JSON解析失败: ${e.message}`);
                    }
                    
                    if (data.code === 1) {
                        const payUrl = data.payurl || data.qrcode;
                        const order_id = data.order_id; 
                        const pay_type_text = selectedPayment === 'wxpay' ? '微信支付' : '支付宝';
                        const pay_type_icon = selectedPayment === 'wxpay' ? '💚' : '💙';
                        
                        orderInfo.innerHTML = `
                            <div class="text-center space-y-2">
                                <div class="text-2xl font-bold text-gray-800 flex items-center justify-center gap-2">
                                    <span>${pay_type_icon}</span>
                                    <span>${pay_type_text}</span>
                                </div>
                                <div class="text-xl font-bold text-red-600 my-2">
                                    ⚠️ 请务必按扫码后的实际金额支付
                                </div>
                                <div class="text-xs text-gray-400 font-mono">
                                    订单号: ${order_id}
                                </div>
                            </div>
                        `;

                        submitButton.disabled = false;
                        submitButton.innerText = "无法扫码? 🔗 点这里";
                        submitButton.onclick = () => {
                            if (payUrl) {
                                window.location.href = payUrl;
                            } else {
                                alert("支付链接未获取到，请重试");
                                resetButton();
                            }
                        };

                        setTimeout(() => {
                            qrLoader.style.display = "none";
                            qrcodeDiv.classList.remove("hidden");
                            qrcodeDiv.innerHTML = "";
                            new QRCode(qrcodeDiv, {
                                text: payUrl,
                                width: 160,
                                height: 160
                            });

                            if (order_id) {
                                let checkCount = 0;
                                const maxChecks = 450; // 900秒(15分钟) / 2秒 = 450次
                                
                                checkInterval = setInterval(() => {
                                    checkCount++;
                                    if (checkCount > maxChecks) {
                                        clearInterval(checkInterval);
                                        console.log("订单轮询超时，停止检查");
                                        const resultP = document.getElementById('result');
                                        resultP.classList.remove('hidden');
                                        resultP.innerText = "支付状态检测超时，如果您已支付，请手动刷新页面。";
                                        return;
                                    }
                                    checkOrderStatus(order_id);
                                }, 2000);
                                console.log("开始检查订单状态:", order_id);
                            } else {
                                console.error("未获取到订单号，无法开始检查状态");
                            }
                        }, 1000);
                    } else {
                        throw new Error(data.msg || "创建订单失败");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`请求失败: ${error.message}`);
                    qrcodeContainer.classList.add("hidden");
                    resetButton();
                });
            };

            submitButton.onclick = createOrder;
        }
    }
});

function checkOrderStatus(orderId) {
    fetch(`pay/query.php?order_id=${orderId}`)
        .then(response => response.text())
        .then(text => {
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Response text:', text);
                throw new Error(`JSON解析失败: ${e.message}`);
            }

            if (data.data && data.data.trade_status === "PAID") {
                clearInterval(checkInterval);
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/success.php';
                const fields = {
                    'order_id': orderId,
                    'money': data.data.money,
                    'type': data.data.type,
                    'pay_time': data.data.pay_time
                };
                Object.entries(fields).forEach(([key, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = value;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            }
        })
        .catch(error => {
            console.error('订单状态检查失败:', error);
        });
}