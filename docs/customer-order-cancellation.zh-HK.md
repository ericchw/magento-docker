# My Orders 取消訂單

Hyvä My Orders 使用原生 extra action blocks 加入 Cancel 按鈕，位於 View／Reorder 旁。三種操作使用圖示加文字按鈕；點擊 Cancel 會開啟確認對話框，顯示訂單號碼，提供 Keep order／Yes, cancel order。支援 Esc 關閉、焦點返回觸發按鈕、背景鎖定捲動及防止重複提交。

列表外觀透過子主題 `Magento_Sales/templates/order/history.phtml` 覆寫；vendor 檔案保持原樣。確認對話框在自訂模組 `view/frontend/templates/order/cancel-dialog.phtml`，使用原生 HTML dialog 及 CSP 註冊腳本。

訂單詳情 `sales/order/view/order_id/...` 亦於 Reorder／Print Order 旁加入同款 Cancel 按鈕，共用資格檢查、確認對話框及 POST 取消流程。

只顯示於 Magento 容許取消、未付款、未開發票、未出貨，且狀態為 Pending／Pending Payment 的訂單。目前支援 Stripe Payments 及原生離線付款（Check / Money Order、Bank Transfer、Cash On Delivery、Free）。其他支付服務需另外整合取消流程。

提交使用 POST 和 Magento form key。Controller 要求登入，並重新核對訂單所屬客戶及取消條件。Stripe 訂單亦核對遠端 PaymentIntent：只容許 requires_payment_method、requires_confirmation、requires_action 或 canceled；processing、requires_capture、succeeded 不容許用戶自行取消。若付款 metadata 指向其他訂單亦會拒絕。

未付款 Stripe PaymentIntent 先取消，阻止舊 Klarna 跳轉付款完成，然後透過 Magento 原生 OrderManagement 取消訂單及處理庫存。Stripe API 出錯會停止取消。此操作不提供用戶自行退款。

程式位於 `Aeromalls/Storefront/Controller/Order/Cancel.php`、`ViewModel/OrderCancellation.php` 及 `view/frontend/layout/sales_order_history.xml`。安裝功能時不會自動取消現有訂單。

按鈕顯示時亦核對 Stripe 遠端狀態，避免 Magento 尚未收到 webhook 而仍顯示 Pending Payment 的已付款訂單被誤認為可取消。若 Stripe 無法查詢，暫時隱藏按鈕。
