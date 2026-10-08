# Aeromalls Shop By：價格與庫存篩選

本功能使用現有 Magento／OpenSearch，不需要 Adobe Live Search。所有客製程式均位於 `app/code/Aeromalls/Storefront` 及 `app/design/frontend/Aeromalls/aeromalls`，沒有修改 `vendor/`。

## 前台操作

- 分類頁（例如 `/components.html`）及搜尋結果頁提供 **Price** 和 **Availability** 篩選，並保留原有商品屬性篩選。
- Price 提供同一軌道上的最低／最高價格滑桿、數字輸入框及 **Apply price** 按鈕。價格以目前幣別顯示，交給 Magento 原生價格篩選處理。
- 順序為 **In stock → Price → 商品屬性**。In stock 使用 `form-toggle-body` 開關：預設 OFF 顯示全部符合條件的商品，ON 只顯示現貨；OFF 不是「只顯示缺貨」。
- Shop By 在桌面和手機預設展開。價格及最多 8 個可用選項的屬性直接顯示；超過 8 個選項才使用可展開、可捲動的選單。
- 品牌等 select／multiselect 屬性使用多選 checkbox。同一屬性的選項用 OR（例如 Brand A 或 Brand B），不同屬性與價格／庫存之間用 AND。選中後仍可加入或取消同一屬性的其他選項。
- Yes／No 屬性使用開關：OFF 不限制該屬性，ON 限制為 Yes。
- 更新篩選會重新載入頁面、保留其他條件及重設分頁。原生 Currently Shopping By／Clear All 仍可使用。
- 價格滑桿支援鍵盤和數字輸入；停用 JavaScript 時保留原生價格區間連結。

滑桿上下限及庫存數量來自 OpenSearch aggregation，覆蓋全部符合條件的商品，而非只計目前一頁。各自的 aggregation 排除自己的條件，保留其他已選條件，避免套用價格後不能再擴大範圍、或選擇 In Stock 後看不到 Out of Stock 的數量。

庫存使用 Magento MSI 已索引的網站 salability `is_out_of_stock`，不是以單一倉庫數量判斷。庫存更新需由 Magento 的 inventory／catalogsearch 索引同步。

## 後台設定

Store View **aeroamlls_view** 需要 `cataloginventory/options/show_out_of_stock = 1`，否則 Magento 會從目錄及搜尋索引排除缺貨商品。這會讓缺貨商品在此 Store View 可見；實際購買資格仍由原生庫存與 checkout 規則決定。

後台位置：**Stores → Configuration → Catalog → Inventory → Stock Options → Display Out of Stock Products**。其他 Store View 可保留原有設定。

商品屬性篩選仍需在 **Stores → Attributes → Product** 設定對應的 layered navigation 選項；分類頁需允許 layered navigation。價格與庫存篩選不需要新增假的商品屬性。

## 實作位置

| 檔案（相對 `src/`） | 用途 |
| --- | --- |
| `app/code/Aeromalls/Storefront/Model/Layer/Filter/Availability.php` | In stock 開關、原生 layer 狀態及庫存數量 |
| `app/code/Aeromalls/Storefront/Model/Layer/Filter/MultiSelect.php` | 屬性多選及同屬性 OR 查詢 |
| `app/code/Aeromalls/Storefront/Model/Layer/Filter/PriceRange.php` | 原生價格 filter 加上滑桿資料 |
| `app/code/Aeromalls/Storefront/Plugin/ProductFilters.php` | 接入分類／搜尋 filter list |
| `app/code/Aeromalls/Storefront/Plugin/FilterAggregations.php` | 排除 facet 自己的篩選條件 |
| `app/code/Aeromalls/Storefront/Plugin/PriceBounds.php` | 取得 OpenSearch min／max |
| `app/code/Aeromalls/Storefront/Plugin/FilterRenderer.php` | 切換價格／庫存 template |
| `app/code/Aeromalls/Storefront/etc/search_request.xml` | 擴充分類及搜尋原生 request |
| `app/code/Aeromalls/Storefront/view/frontend/templates/layer/` | 滑桿及庫存前台介面 |
| `app/design/frontend/Aeromalls/aeromalls/web/tailwind/aeromalls.css` | 滑桿及選項樣式 |
| `app/design/frontend/Aeromalls/aeromalls/Magento_LayeredNavigation/templates/layer/view.phtml` | 預設展開、排序及大量選項選單 |

Magento／Hyvä 更新後應核對原生 filter、搜尋 query 及 template 的相容性；Composer 不會覆蓋上述自訂檔案。

## 部署與回復

PHP 或 DI 設定更新後，以 `www-data` 執行 DI compile 及 cache clean。CSS 更新使用 `bash bin/aeromalls-theme build`。變更缺貨顯示設定後，需更新 inventory 及 catalogsearch 索引。

啟用前的 Store View 原始設定保存於容器內 `var/aeromalls-filters-before-*.json`。回復時還原記錄的 `cataloginventory/options/show_out_of_stock` 值；如果原本沒有 store override，應移除新增的 override，回復繼承。再更新搜尋索引及清相關 cache。

要撤回前台功能，可移除本次新增的 DI plugin 註冊、搜尋 request 擴充、客製 PHP／template 及 CSS，保留其他既有修改，再重新編譯及 build。
