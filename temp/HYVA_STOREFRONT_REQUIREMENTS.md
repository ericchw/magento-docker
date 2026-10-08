# Aeromall Magento 2.4.9 / Hyvä 首頁實作規格

> **用途：** 交畀 Codex 在現有 Magento repository 建立可維護、可管理、使用真實商品資料的 Hyvä child theme。
>
> **參考：** Centralfield 首頁的資訊架構及互動模式；不是複製對方程式碼、商標、圖片、政策或商店資料。  
> **文件日期：** 2026-10-07  
> **目標平台：** Magento 2.4.9；edition、patch level、Hyvä、PHP / Node 相容性以 repository 實際檢查結果為準。  
> **範圍：** 首頁、共用 header / navigation / footer、商品搜尋預覽、cart / account 入口、自選砌機 custom page；完整砌機及 AI 分階段處理。
>
> 下列尺寸、API 路徑、時序及設定值是本專案的**設計決定 / 建議預設值**，不是聲稱從參考網站量度取得。

---

## 0. 給 Codex 的核心指令

你正在修改現有 Magento 專案，不是建立獨立 HTML demo。

1. 先讀本文件、repository 的 `AGENTS.md` 及現有架構；遵守既有命名、測試與部署規範。
2. 使用 **Hyvä child theme + 小型自訂 Magento modules**。畫面放 theme，資料查詢與商業邏輯放 module / service / ViewModel。
3. 不修改 `vendor/`、Magento core 或 Hyvä parent；不重裝 Magento、不自行升降級、不覆寫既有商店設定。
4. 分類、商品、價格、庫存、搜尋、登入及購物車必須接 Magento 真實資料。Mock 只可用於測試 fixture 或明確標示的開發預覽。
5. 本次預設完成 **P0 首頁 MVP**。P1 完整手動砌機及 P2 真實 AI 不得擅自混入 P0。
6. 每階段提供：修改檔案、設定 / 資料變更、執行指令、測試結果及未完成項目。未執行的測試不得標示通過。
7. 缺套件權限、素材或憑證時，完成不受阻擋部分並列出 blockers；不得編造商店資料、商品、keys 或授權。

**完成定義：** 不只是畫面相似；後台可管理、分類來自 Magento、搜尋可到商品頁、cart / account 正常、手機可用、checkout 未被破壞，並有可重現驗收證據。

---

## 1. 已知專案背景

| 項目 | 已知背景 / 不得改動事項 |
|---|---|
| Magento | 使用者指定 2.4.9。先查實際版本，不能為配合 theme 自行升級 / 降級。 |
| 開發環境 | Ubuntu VM + Docker Compose；PHP 指令使用 `docker compose exec phpfpm`。 |
| 本機網址 | `http://ubuntu-server/`，只作開發背景；不可寫死到程式碼、API 或圖片 URL。 |
| 搜尋 | 現有 OpenSearch，背景連線 `opensearch:9200`；不得直接暴露給 browser。 |
| 貨幣 | CAD。價格由 Magento 貨幣 / 稅務流程顯示，不沿用 HK$。 |
| 時區 | `America/Vancouver`；活動排程按 store 時區設定。 |
| 語系 | 現有 `en_US`，不可默默更改 locale 或建立新 store view。 |
| Namespace | 沿用 `Aeromall`，除非 repository 另有具體規範。 |
| MPN | 已討論 / 開發 `Aeromall_ProductEnhancement` 及產品 EAV `mpn`。先檢查，勿重建、改名或改動既有唯一性檢查。 |
| Hyvä | 安裝狀態、版本、standard / CSP parent、Tailwind major 及額外產品授權須檢查，不能假設。 |
| 原有資料 | 不刪除商品、分類、訂單、客戶、CMS 或既有 modules。Seed 不得覆蓋商戶已編輯內容。 |

### 語言策略

本文件用繁中說明，技術識別字用英文。

Magento 首階段改為 i18n 架構，但只支援 enUS，作為第一階段語系。日後會再加入 frCA（French Canada）。

使用者指定的選單及 CMS label，包括「本週熱賣」「合作夥伴」「自選砌機／散件」，都是改成英文，由 i18n label 管理。

保持 en_US；menu / CMS label 按 store scope 管理，之後可按不同 store view／語系修改。

固定系統字串使用 Magento 翻譯機制處理；不自動翻譯整個 catalog，也不替商戶新增語系。

i18n 架構從第一階段開始按多語系擴充方式設計，避免日後加入 fr_CA 時需要重新調整內容結構。

第一階段：en_US

第二階段：加入 fr_CA（French Canada）

目前不需要因應 fr_CA 而提前翻譯 catalog 或建立完整法文內容；相關翻譯會在第二階段正式加入時再處理。

---

## 2. 參考網站分析與證據邊界

### 2.1 已確認的資訊架構

參考首頁的公開可讀索引內容包含商品熱賣區、合作夥伴、AI 砌機入口及多欄 footer；分類頁的共用 header 內容亦有資訊連結、搜尋、account、cart、自選砌機及多層商品分類。[R1][R2]

本次未取得可用的首頁即時渲染 DOM / viewport 截圖，亦未登入或測試其 cart / chat。因此：

- 下列 CSS class 由使用者提供，屬定位參考，不是本次獨立驗證的 DOM 結果。
- 不聲稱已確認精確像素、modal 頻率、動畫速度、搜尋 API 或 AI 後端。
- 開發時有可用 browser 可補 desktop / mobile 觀察；不能取得時依本文件實作。
- **功能驗收優先於逐像素複製。**

### 2.2 參考元素 → 本專案元件

| 參考 | 實作要求 |
|---|---|
| `div.modal.fade.show` | 廣告 modal，用 installed Hyvä dialog / modal，不引入 Bootstrap。 |
| Canadian ownership / shipping header | 可管理的信任及免運資訊列。 |
| 資訊連結 header | 可管理 label、排序及目的頁的 utility navigation。 |
| 自由輸入及預覽 | 關鍵字建議 + 真實商品圖片 / 價格預覽，點商品到商品頁。 |
| Cart | 原生 Magento quote + Hyvä mini-cart / section data。 |
| Login menu | Login、Register、Forgot Password；登入後帳戶選單。 |
| 自選砌機／散件 | 獨立 custom page，不只是一般 category URL。 |
| `nav.header-nav-bar` | 當前 Magento root category 的多層導覽。 |
| 本週熱賣 | 真實商品 shelf + prev / next + 水平捲動。 |
| `.container-fluid.gov-marquee-container` | 可點擊 logo / URL 的 partner 列。 |
| Footer | 類似資訊分欄，改為自家加拿大商店內容。 |
| `.chat-stub-wrap` | 可關閉的砌機助理入口；真實 AI 屬 P2。 |

不要複製對方 logo、banner、產品文案、政策、地址、電話、價格、付款方式、追蹤碼或 AI 接口。素材須由商戶提供或具使用權；缺少時用中性開發佔位並列上線待辦。

---

## 3. 範圍與優先級

| 優先級 | 內容 |
|---|---|
| **P0 首頁 MVP** | Child theme、公告、utility links、main header、真實搜尋預覽、account / mini-cart、多層分類、modal、熱賣商品、partners、footer、PC custom page 基礎版、chat flag / fallback、設定及測試。 |
| **P1 手動砌機** | 真實零件選擇、相容性規則、總價、儲存配置、確認後加入 cart。 |
| **P2 AI 砌機** | Provider、catalog tools、規則驗證、限流、資料保護及推薦交接。 |
| **不屬預設範圍** | 重做 checkout、支付 gateway、ERP、Shopify migration、完整 B2B portal、會員制度、全站產品比較、全站內容重寫。 |

P0 的商品、分類、登入、註冊及重設密碼頁沿用既有 Hyvä 相容 Magento 頁面，僅做必要品牌一致化。

---

## 4. 首頁結構及 Responsive

### Desktop

```text
[Trust / shipping bar]
Canadian Owned & Operated | Free Shipping Canada on Orders $199+

[Utility links]
關於我們 | 如何購買 | 尋找我們 | 私隱政策 | 使用條款 |
除舊服務 | 產品分類總覽 | 開箱指南 | 企業採購 Business

[Main header]
Logo             [搜尋產品 / SKU / MPN....................]    [帳戶] [Cart]

[nav.header-nav-bar]
自選砌機／散件（服務入口） | Magento 多層商品分類 ...

[Optional promotion content：沒有資料不留空白高度]

[本週熱賣]                                  [上一組] [下一組]
[商品卡] [商品卡] [商品卡] [商品卡] [商品卡]
[局部水平捲動區]

[合作夥伴]
[可點擊 logo] [可點擊 logo] [可點擊 logo] ...

[Footer]
品牌簡介 | 購物 / 支援 | 聯絡 / 營業時間 | 社交 / 付款
Copyright / 私隱政策 / 使用條款

[右下砌機助理入口：feature flag 控制]
[符合條件才開啟的 promo modal]
```

### Responsive 決定

| 寬度 | 行為 |
|---|---|
| `< 768px` | Hamburger、logo、account、cart；搜尋獨立一行；分類 drawer + accordion；utility links 收到 drawer / 資訊選單。 |
| `768–1023px` | 保留足夠寬搜尋框，分類可用 drawer，不硬塞 desktop menu。 |
| `>= 1024px` | 完整主 header / mega menu；長 utility links 可換行，不縮成難讀字體。 |
| 大螢幕 | 內容置中，建議 max-width `1440px`；滿版背景與內容寬度分開。 |

- 測試 `360 / 390 / 768 / 1024 / 1440 / 1920px`。
- 只有商品列 / logo 列可局部水平捲動，整頁不可左右溢出。
- 預設只 sticky 主 header / 必要 nav；公告及 utility row 可隨頁捲走。
- Search、mobile nav、cart、promo、chat 必須協調，不能同時兩個 modal focus trap。
- Chat 不可覆蓋 overlay close、cart 或表單按鈕。
- 使用一致 spacing、字級、border、radius、focus 及品牌 tokens。
- 白底商品卡、清晰型號與價格；未有正式品牌指南，可暫用深色文字、淺背景、單一紅色 accent。這是可修改預設，不是已量度的參考站色值。
- 不從外站載入字體或散落寫死顏色。

---

## 5. FR-01 廣告 Modal

**參考：** `class="modal fade show"`  
**建議 hook：** `data-component="promo-modal"`

### 使用者行為

- 只在首頁、活動啟用、時間有效且內容有效時出現。
- 建議延遲 `1500ms`；使用者已開其他 overlay、正在搜尋 / 填表時不強行搶焦點。
- 預設每個 `store + campaign_id` 每 browser session 一次；可配置 24 小時頻率。
- 可按 Close、Escape；backdrop 點擊關閉可配置。
- 圖片 / CTA 用正常 `<a href>`；無 URL 不生成 `href="#"`。
- 手機內容不超出 viewport，長內容在 modal 內捲動。
- 正式設定初始不自動彈出；商戶配置素材及排程後才啟用。驗收可開測試 campaign。

### 後台欄位

`enabled`、`campaign_id`、title、desktop / mobile image、alt、target URL、CTA label、start / end、delay、frequency、close-on-backdrop。

圖片用 Magento media / 安全上傳，不接受任意 scripts 或未消毒 SVG。

### 技術與驗收

- 使用 installed Hyvä 版本支援的 API；官方有新舊 modal 差異，不盲用最新版或舊版範例。[R6]
- 不要求保留 `modal fade show`，不為 class 引入 Bootstrap。
- 有 accessible name、focus trap、focus return、明顯 close button。
- 頻率狀態在 browser 管理，不因此令首頁不可快取。
- 排程按 store timezone，儲存 / 比對一致；cache 不得令過期廣告繼續顯示。
- 已看過、未開始、過期、缺內容時不彈出。
- Storage 不可用 / 圖片失效時，首頁及關閉仍正常。
- 關鍵購物資訊不能只放 modal。

---

## 6. FR-02 信任及免運資訊列

使用指定文案：

```text
Canadian Owned & Operated
Free Shipping Canada on Orders $199+
```

- 兩段文案、enabled、分隔及 shipping policy URL 可管理。
- 手機可分兩行，不自動跑馬燈。
- 貨幣為 CAD，不沿用 HK$。
- 免運文案連到自家 Shipping Policy / 如何購買。
- **顯示文案不等於建立 shipping rule。** 不能畫好 header 就聲稱 checkout 已滿 $199 免運。
- 上線前確認 ownership 聲明，以及門檻是稅前 / 折扣後、是否排除偏遠郵遞區號、大件貨等。
- 文案與實際 shipping method / cart price rule 不一致屬 launch blocker。
- P0 不做「還差 $X 免運」進度條；未能沿用真實規則時不自行計算。

---

## 7. FR-03 Utility Links

初始順序及名稱：

| 名稱 | 目的內容 / 資料方式 |
|---|---|
| 關於我們 | 自家 About Us，CMS page。 |
| 如何購買 | 購物流程、運送 / 付款說明，CMS page。 |
| 尋找我們 | 自家店址 / pickup；沒有門市可改聯絡方式，CMS page。 |
| 私隱政策 | 自家 Privacy Policy，CMS page。 |
| 使用條款 | 自家 Terms，CMS page。 |
| 除舊服務 | 自家適用的回收 / 舊機處理，CMS page；未提供可隱藏。 |
| 產品分類總覽 | Magento tree 產生，CMS page + block / ViewModel。 |
| 開箱指南 | 自家開箱、檢查及售後指引，CMS page。 |
| 企業採購 Business | 商務採購介紹 / 聯絡，CMS page；不是完整 B2B module。 |

- 後台可新增、隱藏、排序、改 label / destination。
- 內部 URL 經 Magento / CMS identifier 解析，支援 store code、base path、rewrite。
- 手機仍可找到全部啟用入口，不能只隱藏而無替代。
- Footer 可重用目的 CMS page，但分組 / 排序獨立。
- 除舊服務只保留能力，不複製香港內容、不假設加拿大商店已有服務。
- 法律 / 運送內容不得編造為已生效政策；開發可明確待填，上線前須完成。
- 分類總覽與主 nav 用相同 store root / 可見規則，不能維護第二份硬編碼分類。

---

## 8. FR-04 主 Header / Product Autocomplete

### 8.1 Main header

Logo 連當前 store 首頁，從自家素材 / config 取得；缺圖用文字店名。搜尋是主要入口，desktop 足夠寬。Account / cart 有可讀 label。搜尋提示可用 `搜尋產品、SKU、MPN…`。

首頁、商品、分類及一般 CMS 頁使用同一套 header。

### 8.2 搜尋互動

1. 自由輸入，不限點擊預設 keyword。
2. trim 空白，建議至少 `2` 字元查詢，須與 Magento 實際最短搜尋設定協調。
3. debounce `250ms`；用 `AbortController` 或 sequence ID 避免舊 response 蓋過新 query。
4. 正確處理繁中 IME composition，組字中不反覆搜尋或攔截選字 Enter。
5. Dropdown 分為關鍵字建議（最多 5 個）、商品預覽（預設 6 件，上限 10）、查看全部結果。
6. 商品顯示圖片、名稱、SKU、可選 MPN、價格 / 貨幣、基本庫存 / 可售狀態。
7. 點商品名稱 / 圖片 / 單一選取目標，去 Magento 正式 product URL。
8. 點 keyword 或沒有 active 商品時按 Enter，去完整搜尋結果頁；不自動開第一件商品。
9. 上下鍵移動 active suggestion；active 商品 + Enter 才開商品。
10. Escape / 清空 / click outside 關閉；Tab 順序合理。
11. 手機 panel 可捲動，不被 sticky header / 軟鍵盤遮住主要操作。

狀態至少有 `idle`、`loading`、`success`、`no-results`、`error`。空結果給修改提示；網絡錯誤保留普通搜尋提交；不以假商品填空。

### 8.3 資料規則

- Magento catalog + 現有 OpenSearch / 索引是真實來源。
- 搜 name、SKU；`mpn` 存在且配置為 searchable 時納入。
- Exact SKU / MPN 優先，其餘按索引 relevance。
- 必須測未輸入完整名稱 / 型號的合理 prefix 預覽。現有索引不支援時實作有測試的 indexed prefix 策略；不做全站 PHP 篩選或大範圍 `%LIKE%`。
- 不承諾任意中間子字串、拼寫修正或語意搜尋，這些非 P0 必備。
- MPN 未完成時列依賴，name / SKU 繼續工作，不建立第二個 attribute。
- 只回傳當前 website / store 可搜尋 / 可展示產品；禁用、其他 website、不可獨立展示 child 不可洩漏。
- Out-of-stock 顯示遵從商店設定，可顯示不代表可買。
- 價格 / special price / tax display 用 Magento 流程；可變商品正確表示 From / range。
- URL 經 Magento rewrite，不拼 `/product/{sku}`。
- Keyword suggestions 不洩漏其他客戶私人搜尋紀錄。
- 不假設既有文字 suggestion endpoint 已包含商品圖片 / 價格，須檢查及實作資料來源。
- 不默認有 Adobe Commerce Live Search；原生 `products` 與 Live Search `productSearch` 是不同服務，不能混用。[R10][R11]

### 8.4 建議 API

由 `Aeromall_SearchAutocomplete` 建立同源只讀路由，例如：

```http
GET {store_base_url}/aeromall_search/suggest/index?q=keyword&limit=6
```

這是要建立的自訂 route，不是聲稱 Magento 已內建。先查衝突，不同時建立兩套相反的 controller / GraphQL 搜尋。

```json
{
  "query": "test",
  "suggestions": [
    {"label": "test keyword", "url": "<Magento-generated search URL>"}
  ],
  "items": [
    {
      "id": 123,
      "sku": "TEST-SKU-0001",
      "mpn": "TEST-MPN-0001",
      "name": "TEST PRODUCT — fixture only",
      "url": "<Magento-generated product URL>",
      "image": {
        "url": "<Magento-generated media URL>",
        "alt": "TEST PRODUCT — fixture only"
      },
      "price": {
        "currency": "CAD",
        "display_type": "fixed",
        "final": 100.00,
        "regular": 120.00,
        "formatted_final": "CA$100.00",
        "formatted_regular": "CA$120.00"
      },
      "is_salable": true
    }
  ],
  "total_count": 1,
  "all_results_url": "<Magento-generated search URL>"
}
```

數字及商品只是 fixture，不得當正式 catalog。Price 結構必須可表達 `fixed / from / range`；不適用欄位用 null / 明確型別，不靠前端猜。

### 8.5 安全 / 效能 / Accessibility

- DI 呼叫 service；不在 PHTML 查 DB，不用 ObjectManager。
- 不暴露 OpenSearch host / credentials、admin / integration tokens。
- Query 上限建議 128 字元，server 強制 limit，正確 Unicode。
- 安全 escape；不把 query / 商品名稱直接塞 `innerHTML`。
- URL 後端生成，fetch 同源，不允許任意 backend URL。
- 有限流 / timeout，錯誤不暴露 stack trace。
- 保守預設 `Cache-Control: private, no-store`，避免共用不同群組 / 稅務 / 貨幣價格。
- 加 server cache 時包含完整 store / price context，不能只用 query 當 key。
- JS 不工作時，表單仍能提交 Magento 搜尋。
- 有適當 combobox / suggestions 語意及 live status；每項保持單一主要操作，不塞一堆內嵌互動按鈕。

---

## 9. FR-05 Account / Cart

### Account menu

| Guest 選項 | Magento route reference |
|---|---|
| 登入 / Login | `customer/account/login` |
| 註冊 / Register | `customer/account/create` |
| 忘記密碼 / Forgot Password | `customer/account/forgotpassword` |

登入後顯示 My Account、My Orders、Logout，沿用該版本原生安全操作 / form key。上述為 route reference，實際 URL 由 Magento 生成。

- 不重寫 auth、session、password reset 或 verification email。
- 支援 click、keyboard、Escape、click outside，不只 hover。
- 姓名、email、登入狀態不烘焙進 public FPC；使用 customer section data。[R7]
- 重設密碼測試區分表單接受與郵件送達；mail 未配置時不宣稱送達測試通過。

### Mini-cart

- 重用 installed Hyvä mini-cart drawer，不建 localStorage cart。
- Badge 用 Magento quantity / `summary_count`，不是 items array length。
- 支援 empty / populated、商品、Magento subtotal、remove、View Cart、Checkout。
- Qty update 依現有支援保留，否則用標準 cart 頁，不另寫半套。
- Add / remove / update 後同步 badge、subtotal、drawer。
- AJAX POST 後按 Hyvä 流程 reload section data，從 `private-content-loaded` 接收；不搬 Luma Knockout 寫法。[R7]
- Add to Cart 保留 salability、required options、form key、錯誤訊息。
- Configurable / bundle / 有必填選項時先去商品頁，不能加入不完整項目。

### Checkout 邊界

- 先查是 Hyvä Checkout 還是 Luma-based checkout；Theme 不代表 Checkout 已安裝 / 可用。
- Luma checkout 可透過 theme fallback 保留，該路由不是首頁的 Tailwind / Alpine 技術棧。[R9]
- 不覆寫已正常結帳流程；驗收要真的從 cart 到 checkout。
- Shared header / footer 若會在 strict CSP checkout 出現，使用相容寫法，不關 CSP 過關。[R8]

---

## 10. FR-06 多層 Magento Category Navigation

**保留 hook：** `<nav class="header-nav-bar ...">`。Class 可用於識別 / 測試，樣式及行為用 Hyvä。

### 資料來源

- 按當前 store root category、active、`include_in_menu`、position、store label、URL rewrite 產生。
- 至少三層，例如 `電腦零件 → CPU → AMD`；只作結構示例，不自動建立這些分類。
- 更深層可展開或透過「查看全部」到達，不令版面崩潰。
- 不讀其他 website root、不展示隱藏節點；父節點隱藏 / 停用時後代不能繞過規則。
- Theme 不寫死 category ID。後台 selector 可儲存當前環境 ID，但須驗證。
- Admin 改分類、position、label 後正常 cache invalidation 即反映。

### Desktop

- Mega menu / flyout 有至少三層。
- Click / keyboard 必須可用，hover 只是增強。
- 父分類連結與 expand button 分開，不讓一次點擊同時跳頁 / 展開。
- Panel 不超 viewport，長清單可內部捲動。
- Escape / click outside 關閉，焦點返回。
- 有 `aria-expanded` / `aria-controls`。
- 一般 nav + disclosure 即可；不要套 ARIA application `menubar` 卻不實作完整鍵盤行為。

### Mobile

- Hamburger → drawer → 多層 accordion。
- 父分類 landing page 仍可到達。
- 有 Back / Close，長內容可捲動，背景鎖定。
- Touch targets 建議至少 `44 × 44px`。
- Utility links、服務入口與產品分類分組清楚。

### 技術

- 優先延伸 installed Hyvä / Magento menu；新 ViewModel 批次載入，避免逐節點查詢。
- 有 store context / cache identities，不為 menu 把首頁 `cacheable="false"`。
- 無 JS 仍有分類連結或分類總覽 fallback。
- 自選砌機是獨立服務入口，可放首項，但不是假的 catalog category。

---

## 11. FR-07 自選砌機／散件 Custom Page

**Label：** `自選砌機／散件`  
**建議 route：** `/pcbuilder`，由 `Aeromall_PcBuilder` 實作，URL 用 Magento 生成。

### P0：真實可用的基礎入口頁

- Controller、layout handle、block / ViewModel、Hyvä template；先查是否已有可重用功能。
- 顯示 title、簡短流程、零件分類入口、自家組裝服務說明。
- 分組建議：CPU、Motherboard、RAM、GPU、Storage、PSU、Case、Cooling、OS / Services。
- 分組連到後台映射的真實 categories，不能靠名稱字串猜 ID。
- 未配置 / category 不存在時隱藏或明確提示，不生成 404。
- 有共用 header、footer、breadcrumb，mobile 可用。
- 此版本可以讓客人瀏覽散件，**不宣稱已自動驗證相容性 / 完整配置總價**。
- 不提供假的「全部加入 cart」「儲存配置」「AI 已驗證」按鈕。
- 主 nav 及 chat fallback 連到同一個 custom route。

### P1：完整手動 Builder，另行範圍

| 功能 | 要求 |
|---|---|
| 選零件 | 真實 catalog 商品、型號、price、salability、product link。 |
| Category / attribute mapping | 後台可管理，不硬編碼品牌 / ID。 |
| 數量 | 明確區分單一 / 可重複類別，如 storage / fans。 |
| 相容性 | `pass / warning / fail / unknown`；缺規格不算 pass。 |
| 總價 | Magento 當前價格，區分商品 subtotal、服務費及未計 tax / shipping。 |
| 保存草稿 | Guest 可存 product ID / SKU / qty；不存付款 / 敏感資料。登入儲存需權限。 |
| 加入 cart | 使用者確認後，server 再查商品、options、quantity、price、salability。 |
| 失敗處理 | 列明部分商品失效；不靜默換貨、不重複加入、不假報全部成功。 |

首批規則可涵蓋 socket、CPU / BIOS 支援、RAM 類型 / 容量、case form factor、GPU / cooler clearance、PSU 功率 / 接頭、storage interface。

規格不足或有 BIOS / 空間條件時標 `unknown / warning`，不只靠商品名稱推斷。P1 需另做 catalog attribute 盤點，不因 UI 有選項就宣稱引擎完成。

---

## 12. FR-08 本週熱賣 Product Shelf

**Label：** `本週熱賣`  
**Hook：** `data-component="weekly-deals"`

### 資料定義

- P0 採用管理員策展的熱賣分類，由後台 category selector 指定。
- 「本週熱賣」是商戶選品，不冒充即時銷量排名。
- 可配置 enabled、title、category、limit、view-all label / destination。
- 預設最多 12 件、上限建議 24，按 category product position。
- 不灌參考站 SKU / category IDs。
- 遵守 website assignment、status、catalog visibility 及 stock display config。
- 少量商品自然顯示，無商品不輸出空 slider；dev 可另顯示配置提示。
- 未來「最近 7 日銷量榜」需離線彙總及訂單狀態定義，不能首頁 request 即時掃訂單。

### 商品卡

- 重用 installed Hyvä product item / Magento price renderer。
- 圖、名稱、價格 / currency、stock / salability、product URL。
- 真的有 discount 才顯示原價 / badge；缺價 / 0 不當「免費」。
- 不硬寫 Free Shipping badge，資格須由真實規則決定。
- 點圖 / 名稱到商品頁；Add to Cart 沿用原生 options / salability。
- 長名稱合理行數限制，不靠細字塞入，完整名稱仍可被輔助技術讀取。
- 圖片有尺寸；首屏主圖合理 eager-load，低於首屏才適當 lazy-load。

### Slider / Horizontal Scroll

**P0 決定：CSS scroll-snap / installed Hyvä slider + 上一組 / 下一組；touch、trackpad、keyboard 及可用水平捲動介面。**

- Desktop 約 4–5 卡，tablet 2–3，手機約 1.2–2，按寬度保持可讀。
- 預設不 autoplay，不搶走正在閱讀的商品。
- Overflow 才有有效導航，到首 / 尾適當 disabled。
- 每次移動合理可視組數，不留空頁 / 卡一半。
- Swiper 示例按鈕 class 為 `swiper-button-prev` / `swiper-button-next`；`swiper-button-prev-next` 不當既有 API。[R12]
- **箭頭外觀不等於必須使用 Swiper library。** 不為兩個箭頭新增整套依賴。
- 新版本 Hyvä 可重用 snap slider，先查 installed version。[R5]
- 區域 / buttons 有可讀 label，鍵盤能到各商品。
- 不隱藏唯一操作；OS scrollbar 自動隱藏時仍有 prev / next。
- JS 失效時仍能橫向捲動及點商品。

---

## 13. FR-09 合作夥伴 Logo 列

**Label：** `合作夥伴`  
**可保留 wrapper：** `class="container-fluid gov-marquee-container"`  
**Hook：** `data-component="partner-logos"`

### 後台資料

每筆：enabled、name、image、alt、URL、open-in-new-tab、sort order、store scope。

使用 store-scoped dynamic rows + 安全 image uploader，或現有合適元件。P0 不需建立大型獨立 partner application。

### 前台

- 圖片 / 卡片用 `<a href="..."><img ...></a>` 點到設定 URL，不只用 JavaScript `onclick`。
- 新分頁外鏈有 `rel="noopener noreferrer"`。
- 拒絕 `javascript:`、`data:` 等 scheme；同站目的地由 Magento 解析。
- 無 URL 不生假連結；無 partner 資料則隱藏 section。
- `object-fit: contain`、一致可視高度，保留比例。
- Desktop / mobile 皆可水平捲動。
- P0 預設手動 scroll；可選 marquee 屬可關閉增強，不是必要。
- Marquee 若啟用，hover / focus 可暫停、有 Pause、尊重 reduced motion。
- 視覺循環重複 logos 須 `aria-hidden` 並退出 Tab，避免重複閱讀。
- 只用自家有權使用的 partner 素材，不冒充參考站的合作關係。
- `gov-marquee-container` 只是 class，不代表政府認證。

---

## 14. FR-10 Footer

參考「支援連結 + 公司 / 聯絡 + 社交 / 付款」資訊分組，改為自家內容。[R1][R2]

| Desktop 欄 | 內容 |
|---|---|
| 品牌 / 商店 | Logo、自家簡介、確認後的 Canadian ownership 文案。 |
| 購物 / 支援 | 如何購買、Shipping、Returns、Warranty、開箱指南、FAQ；只顯示真實已有頁。 |
| 公司 / 法律 | About、Contact、Privacy、Terms、Business、Recycling（有提供才顯示）。 |
| 聯絡 / 社交 | 自家 email、電話、地址、營業時間、social、實際接受的付款方式。 |

底列：copyright、當年年份及必要政策連結。

- 內容後台可改，prefer CMS blocks / store config。
- 電話 / email 用安全 `tel:` / `mailto:`。
- 無資料欄位隱藏，不填假加拿大地址、不殘留香港聯絡內容。
- 不複製參考站 FPS / PayMe 等付款方式。
- 新增 Returns / Warranty 頁只是可配置擴充，不把待填頁當生效承諾。
- 手機可 accordion，無 JS 仍可讀內容。
- 有文字 / accessible label，不只有無法識別的 social icons。
- 壞連結、未完成正式政策列 launch blocker。

---

## 15. FR-11 Chat Stub / AI 砌機

**保留 hook：** `class="chat-stub-wrap ..."` / `data-component="pc-assistant"`

使用者以問號提出 AI，因此區分入口 / UI 與真實服務，不默認同意購買 / 串接第三方。

### P0：入口與誠實 fallback

- Config：`enabled`、`mode = link | preview | live`、label。
- 無 provider 時 production 預設關閉，或 `link` 顯示「砌機助手」連 `/pcbuilder`。
- Link 模式不冒充 AI 對話。
- `preview` 只供 dev / staging，標「介面預覽，AI 尚未連接」，不生成假成功推薦。
- `live` 必須通過 P2 gate 才啟用；改 config 不代表後端已存在。
- 右下固定但不遮 cart、privacy controls、submit、close。
- Mobile 顧及 safe area / keyboard；panel 可 close / Escape，焦點正常。
- 關閉時不載外部 SDK、不發 AI request。
- 未安裝 `Aeromall_AiAssistant` 時 P0 首頁仍正常，不引用不存在的 class。
- Checkout / payment routes 不自動載 chat。

### P2：真正 AI 功能

流程：理解 CAD 預算、用途、已有零件、品牌 / 尺寸 / 噪音限制，查真實 catalog，回覆選擇理由、價格時間點、stock、product / builder 連結。

```text
Hyvä chat UI
  -> 同源 Aeromall_AiAssistant endpoint
     -> session validation / rate limit / privacy controls
     -> provider adapter
     -> allowlisted catalog / product / compatibility tools
     -> server validation
  -> 結構化推薦、說明及警告
```

- Provider 用 interface / adapter，SDK / model name 不散落 theme。
- API key 只在 server env / encrypted config，不進 Git、JS、HTML 或 browser network。
- SKU、price、stock 從 Magento tools，不能用模型記憶造商品。
- 相容性由可測試 rule engine 驗證，AI 只協助選擇 / 解釋。
- 缺規格、BIOS / clearance 未明時 `unknown / warning`，不說「100% compatible」。
- 訪客輸入、catalog 描述、檢索結果均是不可信資料；其中讀 secrets / 忽略規則 / 操作後台指示不可當指令。
- 只提供 allowlisted tools，不給任意 SQL、filesystem、admin API 或任意 URL fetch。
- 輸出 schema 驗證，product IDs、URLs、prices 由 server 校驗。
- 轉入 builder 由使用者確認；加入 cart 再驗 price / stock / options。
- 不自動 create order、收款、checkout、不靜默換貨。
- Provider timeout / quota / unavailable 有明確手動 builder fallback。

### 資料 / 營運

- 啟用前確認 provider、model、費用上限、資料傳送 / 保留政策及商戶告知。
- 預設不送姓名、email、電話、地址、order、payment 或會員資料。
- Guest 使用最少資料的短期 session，由 server 驗證，不只相信前端標記。
- Rate limit、payload / conversation 上限、timeout、schema validation、成本上限須真正執行。
- 修改 session / cart 保留適當 CSRF。
- 不默認長期存原始 chats；保留 / 刪除規則清楚，log 不含 keys / 敏感資料。
- 後台能即時關閉，關閉後不再呼叫 provider。
- 不宣稱需求文件等同法規 / 付款安全認證。

---

## 16. 技術架構與版本要求

Hyvä 官方建議以 child theme 自訂，透過 parent fallback 保留升級空間。[R3]

### 建議責任分工

```text
app/design/frontend/Aeromall/hyva/
    registration.php
    theme.xml
    composer.json
    etc/view.xml                         # 需要改圖片設定才加
    Magento_Theme/
        layout/default.xml
        templates/html/...              # 最小必要 header / footer overrides
    Magento_Cms/layout/...              # 首頁 handle 按實際方案
    Aeromall_Storefront/templates/...
    Aeromall_SearchAutocomplete/templates/...
    Aeromall_PcBuilder/templates/...
    web/css/
    web/js/
    web/tailwind/                       # 根據實際 parent 的 build
    i18n/

app/code/Aeromall/
    Storefront/
        etc/module.xml
        etc/acl.xml
        etc/adminhtml/system.xml
        etc/config.xml
        ViewModel/
        Model/
        Block/                          # 必要才加
        Setup/Patch/Data/
        view/frontend/layout/
    SearchAutocomplete/
        etc/frontend/routes.xml
        Controller/Suggest/Index.php
        Api/
        Model/
        Test/
    PcBuilder/
        etc/frontend/routes.xml
        Controller/Index/Index.php
        ViewModel/
        view/frontend/layout/
    AiAssistant/                        # P2 完整實作
```

這是責任示意，不要求製造空檔案。新 modules 要有完整 registration / declaration / dependencies；template overrides 依 installed parent，不能覆寫不存在的檔案。

| 類型 | 位置 |
|---|---|
| Layout、PHTML、Tailwind、Alpine、tokens | Theme |
| Query、price / stock context、config provider、controller | Module |
| Banner、資訊頁、footer 文案 | Magento CMS / config |
| MPN / uniqueness | 保留 `Aeromall_ProductEnhancement`，不移入 theme |
| Categories / product assignment | Magento Catalog Admin |
| Auth、cart、checkout 邏輯 | 重用 Magento / 相容模組 |
| AI tools、keys、rules | P2 server module |

### 版本相容性

- 讀 `composer.lock`、package composer.json、theme package.json / lockfile。
- 檢查 `Hyva/default`、`Hyva/default-csp` 或既有 parent，採正確繼承。
- 不假設全部 Hyvä 用 Tailwind v3；v3 / v4 source scanning / build 不同，沿用 installed 機制。[R3][R4]
- Theme / module templates 要被 build 掃描，動態 classes 不能 production purge 後消失。
- 不硬寫過時 modal / slider / Alpine initialization。
- 新 Alpine components 優先 CSP-compatible 命名 / handler；shared templates 實測 strict CSP。[R8]
- 不關 CSP、不全域加 `unsafe-inline / unsafe-eval` 遮掩問題。
- Hyvä storefront 不新增 Bootstrap、jQuery、RequireJS、Knockout、React、Vue 作一般 UI 依賴。
- 上條不代表刪 Magento Admin / Luma fallback 原本需要的依賴。
- 不假設 Hyvä Checkout、Commerce CMS、Menu Builder、UI Library、third-party search 已安裝或授權。
- 缺基本 Hyvä package 時列確切 blocker，不能改 Luma 後宣稱 Hyvä 完成。

### Coding

- 遵 repository PHP 版本、DI、型別、清楚錯誤處理、可測試 services。
- PHTML 不做 SQL、collection、remote AI 或大批 repository loops。
- 按 context 用 `escapeHtml / escapeHtmlAttr / escapeUrl`，安全序列化 JS data。
- 保留 native price renderer、salability、URLs，不在 JS 再造價格規則。
- 寫入保留 ACL / CSRF。
- Assets 本地 build / host，不用 public CDN 注入框架。
- 最小 overrides，記錄每個 override 原因。

---

## 17. 後台、初始化及 Cache

建議設定：`Stores > Configuration > Aeromall > Storefront`

| Group | 必備設定 |
|---|---|
| General | Enabled、brand label、必要顯示選項 |
| Announcement | 兩段文案、shipping policy、各自 enabled |
| Utility links | Label、destination type / identifier、sort、enabled |
| Promo | FR-01 活動欄位 |
| Search | Enabled、min length、debounce、limit、MPN、popular keywords |
| Navigation | 必要顯示深度 / mobile options；root 仍來自 store |
| Weekly deals | Enabled、title、category selector、limit、view-all |
| Partners | Enabled、structured rows、可選 animation |
| Footer | CMS block selectors、contact 或重用 store information |
| PC builder | Label、category mappings、service intro block |
| Assistant | Enabled、mode、label；P2 secrets 另安全保存 |

- 合理 website / store scope + inheritance，不能一個 store 改全部。
- Admin ACL、安全 uploads / URL validation；不要求商戶手改 JSON。
- 不把 scripts 放 CMS editor。
- 每份內容只定一個 canonical source，不同 CMS / config 重複存同一段資料。

### CMS identifiers 建議

```text
aeromall-home
aeromall-about-us
aeromall-how-to-buy
aeromall-find-us
aeromall-privacy-policy
aeromall-terms
aeromall-recycling
aeromall-product-categories
aeromall-unboxing-guide
aeromall-business
aeromall-footer-about
aeromall-footer-links
aeromall-pcbuilder-intro
```

- 先查 existing pages，可重用就不重複。
- 只在 identifier 不存在時 seed，不覆寫商戶後續編輯。
- 新 homepage 先建立預覽，不自動取代 production homepage；提供 store-scoped 啟用步驟。
- 不 seed 假地址、政策、products、orders、partners。
- Dev fixtures 獨立且明確，不隨正式 module 安裝灌假商品。
- 必要 migrations 用 data / schema patches；本案預設不改 product EAV。[R14]
- Rollback / uninstall 不直接刪除商戶 CMS / catalog。

### FPC / Cache

依 Magento public content / context 機制實作。[R13]

- 保持首頁及一般 catalog public FPC。
- Cart / account 用 private section data，個資不進 public HTML。
- Category / CMS / product / config 變更有正確 invalidation。
- 新 cache 含適用 store / website / currency / customer group 等 context。
- 活動排程不能被長效 cache 卡在錯誤時間。
- 不在首頁 request 查最近訂單或呼叫 AI。
- 正確 identities + `cache:clean`，不以反覆 flush 或停用 cache 當修復。

---

## 18. 非功能要求

### Accessibility

Keyboard 可操作 search、menu、modal、cart、slider、partners、chat。Semantic header / nav / main / footer、合理 H1 / headings、visible focus、input label、icon name、足夠 contrast。

Overlay 關閉後焦點返回，隱藏內容不保留焦點。尊重 reduced motion。Ajax 有適度 live status，不每鍵朗讀整張清單。

### 效能目標（不是已量度結果）

- 首載不呼叫 AI、不載 chat SDK、不查整個 catalog。
- 圖片尺寸 / responsive assets 減少位移，eager / lazy 按位置使用。
- 暖機 search API 在記錄的資料量 / 環境下目標 p95 ≤ 500ms；不足時報實測瓶頸。
- 建議 staging 目標 LCP ≤ 2.5s、CLS ≤ 0.1；記錄裝置、throttling、cache、日期、工具版本。
- Lighthouse 不代替功能測試，本機 VM 結果不等於 production 保證。

### 安全

- 未登入不見 hidden / disabled / 非本 website 商品。
- 輸入輸出防 XSS，外鏈 / uploads validation。
- Cart / session / account changes 保留安全驗證。
- 不公開 keys、tokens、完整 exceptions / 私人資料。
- 不因 FPC / CDN 讓兩位客戶看到對方 cart、account、特殊價格。
- Log 只留必要 request / error context，不存敏感內容。
- 不移除既有 checkout、captcha / reCAPTCHA、privacy 等安全能力。

---

## 19. 驗收測試清單

以下是**待執行**測試，不是已經通過。Codex 交付時記錄 `PASS / FAIL / BLOCKED / NOT RUN`，附 screenshot、network / console 或實際測試輸出。

| ID | 場景 | 預期 |
|---|---|---|
| ENV-01 | Magento / Hyvä / PHP / Node / Tailwind | 有實際盤點，parent / build 正確，沒有擅自升級。 |
| THEME-01 | 啟用 child theme | 正常 render，沒有 vendor 修改，fallback 正常。 |
| THEME-02 | Product / category / CMS / login | 共用元件正常，不只首頁可用。 |
| UI-01 | 360 / 390 / 768 / 1024 / 1440 / 1920px | 無 body 橫向溢出，文字 / 按鈕清楚。 |
| UI-02 | Keyboard-only | 能搜尋、導航、關 overlay、進 cart。 |
| HDR-01 | 公告列 | 兩段指定文案、正確換行、policy link。 |
| HDR-02 | 9 個 utility links | 順序 / destination 正確，後台可改、mobile 可到達。 |
| PROMO-01 | 有效 campaign 首次進首頁 | 按條件顯示，close / Escape 正常。 |
| PROMO-02 | 已看過 / 未開始 / 過期 / 無內容 | 不彈出、不留空 overlay。 |
| PROMO-03 | Search / cart 開啟時 timer 到期 | 不搶焦點，不出現雙 focus trap。 |
| SEARCH-01 | 部分 product name | 真實商品預覽，非靜態 mock，debounce 正常。 |
| SEARCH-02 | 完整 / prefix SKU | Exact 優先，prefix 有實測。 |
| SEARCH-03 | 完整 / prefix MPN | Attribute 已存在時正確搜尋，不重建 attribute。 |
| SEARCH-04 | 點 product / keyword / Enter | 到對應商品 / 搜尋頁，不無條件開第一件。 |
| SEARCH-05 | 快打 / 刪除 / 慢網絡 | 舊 response 不覆蓋，清空不留結果。 |
| SEARCH-06 | 繁中 IME | 組字 / 選字 Enter 不誤搜尋跳頁。 |
| SEARCH-07 | 無結果 / timeout / 無 JS | 清楚狀態，一般 form search 可用。 |
| SEARCH-08 | Disabled / hidden child / other website | 不洩漏不可展示產品。 |
| SEARCH-09 | Customer group / currency / tax | 價格正確，不被 CDN 錯誤共用。 |
| SEARCH-10 | 惡意 / 過長 query、過大 limit | 無 XSS / stack trace，server enforce limits。 |
| NAV-01 | 三層 desktop / mobile | 全部可到達、父分類可點、無 off-screen panel。 |
| NAV-02 | Admin 改 label / position / visibility | 正常反映，不顯示 hidden descendants。 |
| NAV-03 | 分類總覽 | 同 store root / 規則，無硬編碼第二份。 |
| ACCOUNT-01 | Guest links | Login / Register / Forgot Password 正確。 |
| ACCOUNT-02 | Login / logout / 另一 session | 狀態正確，無私人資料串流。 |
| CART-01 | Empty / add / remove | Badge、items、subtotal 一致更新。 |
| CART-02 | Guest 加貨後 login | 原生 quote / merge，無自製第二套 cart。 |
| CART-03 | Configurable / required options | 必須選 options，不加入不完整品。 |
| CART-04 | Mini-cart → cart → checkout | 可到達，既有 checkout 未破壞。 |
| DEALS-01 | 0 / 1 / 12 件 | 空區安全、無假導航、完整可瀏覽。 |
| DEALS-02 | Price / discount / stock 更新 | 沿 Magento，無假價格 / 免運 badge。 |
| DEALS-03 | Arrows / touch / trackpad / keyboard | 可到各卡，首尾狀態正確。 |
| PARTNER-01 | Admin 增刪 / 排序 / URL | Logo 去正確 URL，不冒充他站夥伴。 |
| PARTNER-02 | New tab / missing URL / reduced motion | 安全 rel、無假 link、動畫可停。 |
| FOOTER-01 | Desktop / mobile | 自家內容、可管理、無香港範本殘留。 |
| BUILDER-01 | 自選砌機入口 | 到獨立 custom page，不是 category / 404。 |
| BUILDER-02 | Category mappings | 真實分類；未配置安全處理。 |
| CHAT-01 | Disabled / 無 AI module | 首頁正常、不載 SDK、不呼叫 provider。 |
| CHAT-02 | Link / preview | 不冒充 AI；正確 fallback / 預覽標示。 |
| CACHE-01 | FPC + 兩位獨立客戶 | 首頁可快取，account / cart / prices 不串資料。 |
| CACHE-02 | CMS / category / product / promo 改動 | 正確 invalidation / schedule。 |
| CSP-01 | 支援的 strict CSP 路由 | 無新增違規，不停用 CSP 過關。 |
| ADMIN-01 | 有 / 無權限角色 | ACL、uploads、links validation 正確。 |
| DEPLOY-01 | 再次部署 / setup:upgrade | 無重複 seed、無覆寫商戶內容、無破壞。 |

### 最低測試層級

- PHP unit / integration：query validation、context、product filtering、category tree、config、CMS seed conflict。
- Component / browser：search race / IME、overlay focus、menu、cart refresh。
- E2E：首頁 → search / category → product → cart → checkout entry，guest 及登入各一次。
- Visual QA：desktop / mobile、console、network、broken links。
- 重用 repository 的 PHPUnit / Playwright / Magento testing setup，不平行造第二套。

### P1 / P2 額外 Gate

缺規格不 pass；價格 / stock 改變會攔截及重新確認；provider timeout / quota / malformed JSON / prompt injection / 假 SKU 有測試；AI 未經確認不能改 cart / order；keys / 個資不在 HTML / browser / 公開 logs；成本上限、kill switch 及 fallback 有實測。

---

## 20. Codex 實作階段及交付

### Phase 0 — Repository audit

先讀：

```text
AGENTS.md / README
composer.json / composer.lock
app/etc/config.php
app/design/frontend/*
app/code/Aeromall/ProductEnhancement/*
Hyvä parent 的 composer.json / package.json / lockfile / build files
compose.yml / docker-compose.yml 及 overrides
既有 tests / lint / CI
```

不把 `app/etc/env.php`、`auth.json` 等 secrets 的完整內容輸出報告。

確認為開發環境後，可用只讀檢查：

```bash
docker compose exec phpfpm php bin/magento --version
docker compose exec phpfpm php -v
docker compose exec phpfpm composer show 'hyva-themes/*'
docker compose exec phpfpm composer check-platform-reqs
docker compose exec phpfpm php bin/magento module:status
docker compose exec phpfpm php bin/magento deploy:mode:show
```

Node / npm 先查 host 或既有 build service；不假設 `phpfpm` 有 Node，不發明未存在的 `node` compose service。

寫 `docs/hyva-environment-audit.md`：實際版本、可重用元件、checkout、MPN、衝突及 blockers。官方 2.4.9 notes 可參考，但不是本機相容性證明。[R15]

### Phase 1 — P0 Theme / Content 骨架

Child theme、正確 Tailwind build、header / footer / mobile、config、必要 CMS。

退出條件：dev / staging 可 render，無 vendor 修改，不替換 production homepage，無 body 溢出。

### Phase 2 — P0 Magento 接駁

Category tree、account / mini-cart、真實 autocomplete、weekly deals、PC custom page。

退出條件：真實 browse → search → product → cart → checkout entry 正常。

### Phase 3 — P0 Promo / Partners / QA

Modal 規則、partners、chat flag / fallback、accessibility、cache、CSP、responsive 及驗收。

退出條件：P0 tests 有結果，blocked / not-run 明確；P1 / P2 不冒充完成。

### Phase 4 — P1 手動 Builder

先盤點商品 attributes / compatibility，再實作完整選件、驗證及 cart 交接。

### Phase 5 — P2 AI

Provider、費用、資料處理及權限確定後才啟用 live，不影響已完成 P0。

### 本機指令模板

以下僅供 modules 已正確建立、確認開發環境及適當備份後使用，不是本文件已執行的操作：

```bash
# Enable only modules actually created.
docker compose exec phpfpm php bin/magento module:enable \
  Aeromall_Storefront Aeromall_SearchAutocomplete Aeromall_PcBuilder

docker compose exec phpfpm php bin/magento setup:upgrade
docker compose exec phpfpm php bin/magento cache:clean
```

Tailwind 使用 actual `web/tailwind/package.json` scripts / lockfile。先確定 Node runtime / working directory，再做 `npm ci` 及該版本 build；不要混 v3 / v4 指令。

Production DI compile、static content deploy、assets 及 theme 啟用用既有 CI/CD，保留 rollback；不在 production 即席 build CSS / 跑未審核 seed。

### 必須交付

| 交付物 | 要求 |
|---|---|
| Theme / modules | 完整可安裝，不只是 HTML demo。 |
| `docs/hyva-environment-audit.md` | 版本、環境、checkout、MPN、blockers。 |
| `docs/hyva-implementation.md` | 架構、files、API、必要 overrides、設計決定。 |
| `docs/hyva-admin-guide.md` | 編輯公告、links、modal、deals、partners、footer、builder mappings。 |
| `docs/hyva-test-results.md` | Test IDs、PASS / FAIL / BLOCKED / NOT RUN、重現步驟。 |
| Screenshots / evidence | 實際 desktop / mobile / overlay；不能偽造。 |
| Deployment / rollback | Build、enable、恢復原 theme / CMS、非破壞性資料策略。 |
| Scope report | P0 完成度、P1 / P2 未實作及 launch blockers。 |

---

## 21. 商戶上線前確認事項

不阻止建立 theme / 功能，但未有資料不可自行編造：

| 項目 | 安全預設 |
|---|---|
| 正式 logo / 品牌色 / 廣告 | 文字品牌 / 中性開發佔位，上線替換。 |
| Canadian ownership | 可配置，列待商戶確認。 |
| $199 免運資格及例外 | 不自建任意 shipping rule，文案要與 checkout 對齊。 |
| 法律 / 售後 / recycling | 待填或隱藏，不抄他站政策。 |
| 地址 / email / phone / hours | 未提供欄位隱藏。 |
| Partner 名單 / 素材權利 | 無資料隱藏 section。 |
| 熱賣 category | 後台選取，不用假商品。 |
| MPN | 偵測重用，不改既有資料模型。 |
| Hyvä / checkout / build | 依 Phase 0 installed package audit。 |
| 完整 builder / AI | P1 / P2；P0 只交付本文基礎頁 / fallback。 |

---

## 22. 可直接貼給 Codex 的啟動 Prompt

```text
請讀取 repository 的 AGENTS.md 及 docs/HYVA_STOREFRONT_REQUIREMENTS.md。

在現有 Magento 2.4.9 專案建立 Aeromall Hyvä child theme。
不要做獨立 HTML demo，不修改 vendor，不重裝 Magento，
不更改既有 store locale / currency。

先完成 Phase 0 audit：installed Hyvä、Tailwind、Alpine / CSP、checkout、
Docker build 環境，以及 Aeromall_ProductEnhancement 的 mpn 實作。
寫 docs/hyva-environment-audit.md。

然後依序實作 Phase 1–3，即 P0：
header / utility links、真實搜尋預覽、Magento 多層分類、
原生 account / mini-cart、廣告 modal、熱賣 scroll-snap、
可點 partner logos、footer、自選砌機 custom page 基礎版、
可關閉的 chat stub / 誠實 fallback。

使用真實商品、價格、庫存、URL 及客戶資料流程。
完整手動 builder 屬 P1，真實 AI 屬 P2，今次不要自行擴大範圍。
缺相依套件 / 內容時，完成不受影響部分並列 blockers。

按文件 test IDs 測試，交付修改 files、指令、實際證據、
PASS / FAIL / BLOCKED / NOT RUN、後台管理方法、deployment / rollback
及剩餘 launch 待辦。不可將 mock、未執行測試或未接 AI 宣稱完成。
```

---

## 23. 來源

查閱日期：2026-10-07。技術實作仍以 repository installed versions 為準。

| 參考 | 內容 |
|---|---|
| [R1] | Centralfield 首頁公開索引內容；即時渲染 / 互動未驗證。 |
| [R2] | Centralfield 分類頁，共用 header / category 結構。 |
| [R3] | Hyvä child theme、parent fallback 及 build。 |
| [R4] | Hyvä Modules：Tailwind source / config 版本差異。 |
| [R5] | Hyvä x-snap-slider：CSS-driven slider / 版本要求。 |
| [R6] | Hyvä modal dialogs：新舊 API 差異。 |
| [R7] | Hyvä sectionData：private content 及更新流程。 |
| [R8] | Hyvä Alpine CSP：相容寫法。 |
| [R9] | Hyvä Luma fallback：保留既有 checkout 邊界。 |
| [R10] | Adobe 原生 products query。 |
| [R11] | Adobe Live Search / Catalog Service productSearch。 |
| [R12] | Swiper 按鈕 class 示例；本案不強制依賴。 |
| [R13] | Adobe Public Content：cache contexts / identities / 私密資料。 |
| [R14] | Adobe Data / Schema Patches。 |
| [R15] | Magento Open Source 2.4.9 release notes，非本機相容性證明。 |

[R1]: https://centralfield.com/
[R2]: https://centralfield.com/product-category/biz-products/
[R3]: https://docs.hyva.io/hyva-themes/building-your-theme/index.html
[R4]: https://docs.hyva.io/hyva-themes/working-with-tailwindcss/using-hyva-modules/index.html
[R5]: https://docs.hyva.io/hyva-themes/working-with-alpinejs/alpine-plugins/x-snap-slider.html
[R6]: https://docs.hyva.io/hyva-themes/view-utilities/modal-dialogs/index.html
[R7]: https://docs.hyva.io/hyva-themes/writing-code/working-with-sectiondata.html
[R8]: https://docs.hyva.io/hyva-themes/writing-code/csp/alpine-csp.html
[R9]: https://docs.hyva.io/hyva-themes/building-your-theme/luma-theme-fallback.html
[R10]: https://developer.adobe.com/commerce/webapi/graphql/schema/products/queries/products
[R11]: https://developer.adobe.com/commerce/webapi/graphql/schema/live-search/queries/product-search
[R12]: https://swiperjs.com/get-started
[R13]: https://developer.adobe.com/commerce/php/development/cache/page/public-content
[R14]: https://developer.adobe.com/commerce/php/development/components/declarative-schema/patches
[R15]: https://experienceleague.adobe.com/en/docs/commerce-operations/release/notes/magento-open-source/2-4-9

---

**核心原則：外觀借鑑參考站；商品、分類、客戶、價格及購物流程屬於自己的 Magento；互動用相容版本的 Hyvä；自訂邏輯放 module，正式內容由後台管理。**
