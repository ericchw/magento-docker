# Klarna 返回後改用信用卡

Stripe Payments 4.7.0 的 `Helper/PaymentIntent::getConfirmParams()` 在每次確認付款時，都加入 `Helper/PaymentMethodOptions` 提供的 ACSS debit mandate options。Klarna 已確認並進入跳轉流程後，返回 checkout 改用信用卡再次確認同一 PaymentIntent，可能收到 `Mandate options can't be updated after confirmation.`。本機 exception log 顯示錯誤發生在 `PaymentElement::confirm()` 的 Stripe confirm API 呼叫。

自訂 plugin `Aeromalls/Storefront/Plugin/Stripe/PreserveConfirmedMandate.php` 在 PaymentIntent 已進入確認後狀態，或已有付款錯誤／charge 記錄時，移除再次送出的 ACSS `mandate_options`。首次確認保留原有 mandate options；其他付款參數、3DS、原生已付款判斷不變。不修改 vendor，亦不取消或重新收取已有付款。

此修正針對重新確認參數，沒有自動完成信用卡付款。實際端到端驗證需要在 checkout 返回 Klarna 後選信用卡再次落單。

Stripe API 說明：[Confirm a PaymentIntent](https://docs.stripe.com/api/payment_intents/confirm)、[Update a PaymentIntent](https://docs.stripe.com/api/payment_intents/update)。

## My Orders 顯示待付款訂單

Magento 原生 My Orders 列表及訂單詳情頁都要求訂單狀態的 `visible_on_front` 為 1。原本 `pending_payment` 的設定為 0，令正確綁定客戶嘅待付款訂單仍被隱藏。

已將 `sales_order_status_state` 的 `pending_payment`／`pending_payment` 映射設為 `visible_on_front = 1`。這是原生 Order Status 設定，適用所有店舖；登入用戶仍只能查看自己的訂單，不會改變付款狀態。亦可在 **Stores > Settings > Order Status** 管理對應狀態的 **Visible On Storefront** 設定。

修改前備份位於 Magento `var/aeromalls-pending-payment-visibility-*.json`。

## Stripe 成功但 Magento 仍 Pending Payment

本機 sandbox 測試發現 Stripe PaymentIntent 為 succeeded，但 Magento `total_paid` 尚未記錄。Stripe webhook URL 設為內網主機，Stripe 無法連入；Magento webhook 記錄 `last_event = 0`、事件表空白，對應 charge.succeeded 事件仍有 pending webhook。

已使用原生指令重播從 Stripe API 核對過的付款成功事件：

```bash
docker compose exec -T -u www-data php-fpm bin/magento stripe:webhooks:process-event 'VERIFIED_STRIPE_EVENT_ID'
```

將 `VERIFIED_STRIPE_EVENT_ID` 換成已核對、屬於目標訂單嘅事件 ID。測試事件處理回傳 200 OK，訂單更新為 Processing、餘額 0，並建立已付款發票。沒有再次收款，亦沒有直接 SQL 修改付款狀態。

自動同步仍需可公開存取的 HTTPS webhook URL。內網開發可使用 Stripe CLI `stripe listen --forward-to` 轉發，但必須把 CLI 提供的 signing secret 正確加入接收端設定，不能略過 webhook 簽章驗證。[Stripe webhook 官方說明](https://docs.stripe.com/webhooks)。
