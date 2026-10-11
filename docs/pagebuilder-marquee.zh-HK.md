# Page Builder 品牌 Logo 走馬燈及 Slider

## 加入品牌 Logo 走馬燈

1. 到 **Content → Pages → Home page → Edit → Content → Edit with Page Builder**。
2. 展開 **Elements**，拖 **Brand Logo Marquee** 到 Row／Column。
3. 點走馬燈區塊內嘅 **＋ Add Logo**，或者從 **Media** 拖 **Image** 到走馬燈內。可以重複加入多張圖片，每張代表一個品牌。
4. 打開每個 Image 嘅設定：上載品牌 Logo、填寫 Alternative Text、設定 **Link** URL。**Open in new tab** 勾選時開新視窗／分頁，不勾選時喺目前視窗開啟；每個 Logo 可以各自設定。建議 Desktop／Mobile 使用同一個 Logo。
5. 可以拖動 Image 改變排序，或複製、刪除個別品牌。
6. 打開走馬燈設定，調整 **Loop duration (seconds)**（5–120 秒，越大越慢）及 **Direction**，然後儲存頁面。

前台無 Pause 按鈕。滑鼠移入整个走馬燈時暫停，移出繼續；鍵盤焦點喺 Logo 連結時亦會暫停。點 Logo 會打開該 Image 設定嘅 URL。系統設定「減少動態效果」時顯示靜態 Logo 排列。

後台 Stage 使用靜態圖片預覽，連續動畫喺前台執行。前台自動複製 Logo 群組來銜接循環，CMS 只儲存原始品牌列表，毋須手動放重複圖片。

Home 頁面已加入 AeroMalls Logo 示範，位置喺 Slider 之後、產品分頁之前；其他品牌圖片及 URL 可以直接喺 Page Builder 加入。

## Slider

圖片及 Show Arrows／Show Dots 繼續由 Page Builder Slider 設定管理。首頁加入圖片左右兩側嘅圓形箭頭及底部浮動分頁，無底部白色控制列，完整顯示橫幅圖片。展示區使用 4:1 比例；其他比例會留邊，避免裁走圖片内容。輪播沿用 Hyvä SnapSlider，並加上自訂擴充，讀取 Page Builder **Autoplay**、**Autoplay Speed** 及 **Infinite Loop** 設定。最後一張可循環返第一張，上一張亦可由第一張返最後一張。Autoplay 開啟但 Infinite Loop 關閉時，播放到最後一張便停止。

滑鼠移入、鍵盤焦點進入 Slider、滑動操作、頁面切換到背景或 Slider 離開畫面時會暫停自動播放；回到可播放狀態後繼續。系統設定「減少動態效果」時不會自動播放，手動箭頭及分頁仍可操作。

## 實作位置

- `src/app/code/Aeromalls/Storefront/view/adminhtml/pagebuilder/content_type/aeromalls_marquee.xml`
- `src/app/code/Aeromalls/Storefront/view/adminhtml/ui_component/pagebuilder_aeromalls_marquee_form.xml`
- `src/app/code/Aeromalls/Storefront/view/adminhtml/web/template/content-type/marquee/default/`
- `src/app/code/Aeromalls/Storefront/view/frontend/templates/pagebuilder-marquee.phtml`
- `src/app/design/frontend/Aeromalls/aeromalls/web/tailwind/aeromalls.css`

修改樣式後執行 `bash bin/aeromalls-theme build`，再清除相關 Magento 快取。

內容元件結構參考 [Adobe Page Builder 官方文件](https://developer.adobe.com/commerce/frontend-core/page-builder/content-types/create/)。所有自訂檔案放喺自訂模組及主題，無修改 vendor。

## 全頁寬度

Slider 同 Brand Logo Marquee 仍放喺 Page Builder Row 內，方便用後台拖動及排序。

1. 編輯外層 **Row**，將 **Appearance** 設為 **Full Bleed**。
2. Slider／Brand Logo Marquee 直接放喺呢個 Row 入面。
3. Row 嘅左右 Padding 設為 `0`，儲存頁面。

**Full Width** 只將 Row 背景延伸；**Full Bleed** 先係內容亦全寬。自訂主題會令直接包含 Slider／Brand Logo Marquee 嘅 Full Bleed Row 延伸至可用視窗宽度，避免垂直 scrollbar 令頁面橫向溢出。產品分頁及其他 Contained Row 仍保留原本內容寬度。

Home 嘅 Slider Row 及走馬燈 Row 已設為 Full Bleed；內容仍由 **Content → Pages → Home page** 管理。

### 走馬燈本身嘅全寬開關

編輯 **Brand Logo Marquee → Full page width → Yes**（預設）。即使外層 Row 係 Contained，品牌 Logo 條仍可延伸至全頁。選 No 則使用外層 Row 寬度。設定會儲存於走馬燈內容，唔需要每次改 Row Appearance。
