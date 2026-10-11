# Aeromalls 訂單編號

訂單使用 `A` 加至少 8 位數字，例如 `A00000001`。原生資料庫流水號每次增加 1，前面的零只用於顯示；超過 8 位數字時不會截斷。

目前 store 1 的訂單 `000000001` 已改為 `A10003012`，下一個可分配號碼是 `A10003013`。新店舖的原生流水號由 1 開始，因此第一個號碼是 `A00000001`。

格式由自訂模組 `Aeromalls_Storefront/Model/OrderSequence.php` 及 `etc/di.xml` 處理，只影響訂單，保留 Magento 原生原子流水號機制。Invoice、Shipment、Credit Memo 保留原有格式。沒有修改 vendor。

一次性遷移程式為 `bin/aeromalls-order-number.php`，會同步訂單、訂單列表、相關文件列表的訂單參照及原購物車記錄。執行前在 Magento `var/aeromalls-order-number-*.json` 保存備份。程式針對 entity ID 1，不適合直接用於其他訂單。

Magento 在 checkout 預留訂單號碼，未完成付款或取消的訂單可能導致跳號；`+1` 是號碼分配規則，不保證成功訂單列表完全連號。
