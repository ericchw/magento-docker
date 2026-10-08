# Magento 更換及修改 Hyvä Theme 教學

適用專案：`/home/daiwa/magento-docker`。以下指令均在專案根目錄執行。

## 1. 目前已經用緊 Hyvä

2026-10-08 以容器及資料庫唯讀查詢確認：

| 項目 | 目前狀態 |
| --- | --- |
| Hyvä Default Theme | 已安裝 `1.5.2` |
| Magento 模式 | `developer` |
| 已註冊子主題 | `Aeromalls/aeromalls`，後台名稱 **Aeromalls** |
| 子主題父層 | `Hyva/default` |
| Store View | `aeroamlls_view`（現有代碼的拼法） |
| Store View 主題設定 | 已指定 **Aeromalls**，本環境 theme ID 為 `5` |
| CSS 工具 | Tailwind CSS 4；容器 Node.js `24.21.0` |

所以，如果你想改目前網站外觀，直接改 **Aeromalls 子主題**就可以，毋須重新安裝 Hyvä。ID 只用作記錄，其他環境不要照抄。

本次只建立教學，沒有執行主題切換、CSS build、部署或資料庫修改。上述主題設定不代表所有購物流程已完成驗收。

## 2. 先分清「切換主題」同「修改外觀」

| 你想做的事 | 做法 |
| --- | --- |
| 使用原裝 Hyvä 外觀 | 後台選 **Hyvä Default** |
| 保留 Aeromalls 客製外觀 | 後台選 **Aeromalls**，並修改現有子主題 |
| 改 Logo、首頁文案、宣傳內容 | 先用後台 Design Configuration、CMS 或 Aeromalls 設定 |
| 改顏色、間距、Header、Footer、版面 | 修改子主題 CSS、PHTML、Layout XML，再 build／清 cache |
| 換首頁內容 | 另外設定 CMS Home Page；換 theme 不會自動換首頁 |

客製內容放在子主題，避免直接改 `vendor/hyva-themes/`，否則 Composer 更新時可能被覆蓋。[Hyvä 子主題官方教學](https://docs.hyva.io/hyva-themes/building-your-theme/index.html)

## 3. 後台切換 Hyvä 主題

1. 到 **Content → Design → Configuration**。
2. 記錄目標 Website／Store View 原本的 Applied Theme，以及有沒有繼承上層設定，方便回復。
3. 編輯目標 Website，確認已指定一個主題。若只想更改某個 Store View，Website 可保留原有主題。
4. 編輯目標 Store View，在 **Applied Theme** 選 **Aeromalls** 或 **Hyvä Default**；如正在繼承，先取消相應繼承選項。
5. 按 **Save Configuration**。
6. 清除相關 cache：

```bash
docker compose exec -u www-data php-fpm bin/magento cache:clean config layout block_html full_page
```

Hyvä 官方提醒：Website 層不要留在 `-- No Theme --`，即使主題主要設定在 Store View 層亦一樣。[Hyvä 安裝與啟用說明](https://docs.hyva.io/hyva-themes/getting-started/index.html#activating-the-hyva-theme-in-magento-admin)

到 **Stores → Configuration → General → Web → Default Pages → CMS Home Page** 查看首頁設定。切換到原裝 Hyvä 時，若首頁仍指向 Aeromalls 專用 CMS 頁，首頁內容可能仍包含客製模組；應另外選擇適合的 CMS 頁。

Aeromalls 首頁及內容設定見 [現有後台操作指南](hyva-admin-guide.md)。當中的 `aeromalls-activate-home` helper 會同時處理主題、首頁及部署，不適合只想切換 Applied Theme 的情況。

## 4. 修改外觀要改邊個檔案？

主題位置：

```text
src/app/design/frontend/Aeromalls/aeromalls/
├── theme.xml                         # 名稱及父主題
├── registration.php                  # Magento 主題註冊
├── Magento_Theme/
│   ├── layout/default.xml            # 全站共用區塊配置
│   ├── layout/cms_index_index.xml    # 首頁 layout
│   ├── layout/cms_page_view.xml       # CMS 頁支援
│   └── templates/html/
│       ├── header.phtml              # Header
│       └── header/logo.phtml         # Logo 呈現
└── web/
    ├── css/styles.css                # Build 產物
    └── tailwind/
        ├── package.json              # Build 指令及依賴
        ├── package-lock.json         # 鎖定 npm 依賴
        ├── hyva.config.json          # 掃描來源及顏色 tokens
        ├── tailwind-source.css       # CSS 入口及掃描設定
        └── aeromalls.css             # 客製 CSS
```

本專案另外把部分區塊放在 `Aeromalls_Storefront` 模組：

| 修改項目 | 主機檔案／後台位置 |
| --- | --- |
| 主色、背景、間距、RWD | `src/app/design/frontend/Aeromalls/aeromalls/web/tailwind/aeromalls.css` |
| 主色 tokens | 同目錄的 `hyva.config.json` |
| Header 排列 | `src/app/design/frontend/Aeromalls/aeromalls/Magento_Theme/templates/html/header.phtml` |
| 首頁結構 | `src/app/code/Aeromalls/Storefront/view/frontend/templates/home.phtml` |
| Footer 結構 | 同模組的 `view/frontend/templates/footer.phtml` |
| 分類導航 | 同模組的 `view/frontend/templates/navigation.phtml` |
| 搜尋呈現 | 同模組的 `view/frontend/templates/search.phtml` |
| Logo 圖片 | Content → Design → Configuration → 目標設定 → Header |
| Hero、宣傳、合作品牌、Footer 內容 | Stores → Configuration → Aeromalls → Storefront，配合 CMS Blocks／Pages |

`styles.css` 及 `web/tailwind/generated/` 是產生出來的檔案。日常修改應改來源檔，再重新 build。

### 例子 A：將紅色主色改為藍色

在 `hyva.config.json` 的 `tokens.values.color` 修改現有值，保留其他設定：

```json
"primary": "#2563eb",
"on-primary": "#ffffff"
```

同時在 `aeromalls.css` 更新現有 `@theme` 及 `:root` 對應值：

```css
@theme {
    --color-primary: #2563eb;
    --color-secondary: #334155;
    --color-on-primary: #ffffff;
}

:root {
    --aero-accent: #2563eb;
    --aero-ink: #111827;
    --aero-surface: #ffffff;
}
```

這個專案同時使用 Hyvä tokens 及自訂 `--aero-accent`，兩邊要保持一致。以上只替換對應的設定區塊，毋須覆蓋整份 CSS。

### 例子 B：改 Header 或加 Tailwind class

直接編輯現有子主題 `header.phtml`。若要覆寫另一個父主題 template，先查看容器內 `vendor/hyva-themes/magento2-default-theme/` 的檔案，再把需要的檔案按相同相對路徑放到子主題。

例如修改 template 中的完整 class 字串：

```html
<div class="rounded-xl bg-primary p-6 text-on-primary">
    ...
</div>
```

新增 Tailwind class 後要 build CSS。避免用 PHP／JavaScript 拼接 `bg-` + 顏色名稱，掃描器可能找不到完整 class。修改 Header 時保留現有購物車、登入及 Alpine 元件所需的程式。

### 例子 C：改 Footer 文字

公司資料、連結或 CMS 文字先改後台設定；只有改 HTML 結構或排列時才編輯 `footer.phtml`。Footer 樣式則在 `aeromalls.css` 修改。

## 5. 修改後如何生效？

### 平時改 CSS／PHTML／Layout

```bash
cd /home/daiwa/magento-docker
bash bin/aeromalls-theme build
docker compose exec -u www-data php-fpm bin/magento cache:clean layout block_html full_page
```

`build` helper 會在 PHP 容器內以 `www-data` 執行 `npm ci` 和 `npm run build`，產生子主題 `web/css/styles.css`，並更新已存在的 `en_US` 靜態 CSS。它會檢查相關路徑可否寫入；失敗時先處理權限。

然後在瀏覽器 hard refresh，確認新樣式。純 PHP template／XML 修改、沒有新增 CSS class 時，通常只需要清相關 cache；純後台文案修改毋須 npm build。

### 首次部署或需要重新發佈靜態檔案

```bash
bash bin/aeromalls-theme build
bash bin/aeromalls-theme deploy
```

現有 `deploy` helper 包含 DI compile、`en_US` 靜態部署及 cache clean，適合完整部署。日常外觀小修改不用每次 compile。本專案維持 developer mode。

若 Store View 使用其他語系，先在後台確認實際 Locale，再把下面的 `<locale>` 換成該代碼：

```bash
docker compose exec -u www-data php-fpm bin/magento setup:static-content:deploy -f --area frontend --theme Aeromalls/aeromalls <locale>
docker compose exec -u www-data php-fpm bin/magento cache:clean config layout block_html full_page
```

`<locale>` 是佔位符，不能原樣執行。現有 build helper 只同步 `en_US`；其他語系若仍讀到舊 CSS，按第 7 節檢查並清除對應的已部署 CSS，再部署。

## 6. 只有「未安裝 Hyvä」的新環境才需要做

目前環境已安裝，可跳過本節。先備份：

```bash
./backup.sh
```

1. 如 `env/hyva.env` 不存在，才由 `env/hyva.env.example` 複製；已有檔案不要覆蓋。
2. 從 Hyvä 帳戶取得目前專案的 Composer key 及 project name，填入 `HYVA_PACKAGIST_KEY`、`HYVA_PACKAGIST_PROJECT`。不要提交真實 key。
3. 重建 PHP 容器以載入環境變數，這會短暫影響 PHP 請求：

```bash
docker compose up -d --no-deps --force-recreate php-fpm
```

本專案 entrypoint 會設定 Hyvä Composer repository 及認證，然後在容器內安裝：

```bash
docker compose exec -u www-data php-fpm composer require hyva-themes/magento2-default-theme
docker compose exec -u www-data php-fpm bin/magento setup:upgrade
docker compose exec -u www-data php-fpm bin/magento cache:clean
```

Composer 會修改容器內 Magento 的依賴檔；`setup:upgrade` 會更新 Magento 資料庫。檢查已安裝版本與目前 PHP／Magento 的相容性，依賴衝突時先處理衝突，勿用 `--ignore-platform-reqs` 強行安裝。

新安裝亦要按官方說明檢查 legacy Captcha、Magento minify／merge／bundling 設定，以及功能需要的 GraphQL modules；只修改目標 scope。現有多 Store View 或 checkout fallback 的設定應一起核對。[Hyvä 安裝後設定](https://docs.hyva.io/hyva-themes/getting-started/index.html)

本專案有現成 Aeromalls 子主題，可沿用。新增另一個子主題時，除了 theme 註冊及 Tailwind 設定，也要在 `compose.yaml` 為 **php-fpm、nginx、cron** 加入新目錄的 bind mount；現有 Compose 只掛載指定的客製目錄。

## 7. 常見問題

| 問題 | 檢查方式 |
| --- | --- |
| 改完主題但前台未變 | 確認網站屬於哪個 Store View、Applied Theme、Website 層設定、Design Schedule，再清 cache |
| 主題換了但首頁內容一樣 | CMS Home Page 是獨立設定，要另行選擇 |
| 新加 class 無效果 | 執行 build，確認來源檔在掃描範圍內；本專案入口是 `tailwind-source.css`，不是 Tailwind 3 的 `tailwind.config.js` |
| 主色只有部分變了 | 檢查 `hyva.config.json`、`aeromalls.css` 及個別硬編碼顏色 |
| 靜態部署後仍是舊 CSS | 比較來源與該語系部署的 CSS；靜態部署可能保留已存在的檔案 |
| Permission denied | 檢查輸出路徑的 owner/group 及 `www-data` 寫入權限；只修正受影響路徑，勿用 `chmod 777` |
| 改了主機檔案但容器沒變 | 檢查 Compose bind mount；整個 `src/` 並非完整掛載 |
| 第三方外掛前台失效 | 查該外掛有沒有 Hyvä 相容版本／compatibility module |
| Checkout 外觀與其他頁不同 | 核對目前 Luma fallback 設定；Theme 與 Hyvä Checkout 是分開的項目 |

例如確認 `en_US` CSS 是否一致：

```bash
docker compose exec -u www-data php-fpm sha256sum \
  app/design/frontend/Aeromalls/aeromalls/web/css/styles.css \
  pub/static/frontend/Aeromalls/aeromalls/en_US/css/styles.css
```

若已確認該部署檔是舊版本，且是一般檔案而非 symlink，可只移除這一個已產生的 CSS，再部署該語系：

```bash
docker compose exec -u www-data php-fpm sh -c 'target=pub/static/frontend/Aeromalls/aeromalls/en_US/css/styles.css; if [ -f "$target" ] && [ ! -L "$target" ]; then rm "$target"; fi'
docker compose exec -u www-data php-fpm bin/magento setup:static-content:deploy -f --area frontend --theme Aeromalls/aeromalls en_US
docker compose exec -u www-data php-fpm bin/magento cache:clean layout block_html full_page
```

其他語系需同時替換路徑及部署的 locale。不要刪整個 `pub/static` 或 `.htaccess`。

主題、套件及 PHP 執行環境檢查：

```bash
docker compose ps
docker compose exec -u www-data php-fpm composer show hyva-themes/magento2-default-theme
docker compose exec -u www-data php-fpm bin/magento deploy:mode:show
docker compose logs --tail=100 php-fpm nginx
```

Magento 主程式及 `vendor/` 位於 Docker `magento-app` named volume 的 `/var/www/html`。現有 Aeromalls 主題／模組則由主機 bind mount，修改相應 `src/app/...` 檔案會同步進容器。安裝及 CLI 指令一律在容器內以 `www-data` 執行。

## 8. 驗收及回復

修改後以 guest／登入用戶檢查首頁、分類、產品、搜尋、購物車、checkout、手機版及 Console／Network 錯誤。不要單靠首頁外觀判斷全站功能正常。

要回復主題，在後台還原第 3 節記錄的 Applied Theme 及繼承狀態；如果改過首頁，亦還原 CMS Home Page，然後清 `config layout block_html full_page` cache。要回復外觀，還原今次修改的來源檔，再 build。回復前先保留其他未提交的修改。

切換主題不需要重裝 Magento、重設資料庫或執行 `docker compose down -v`。

相關文件：[專案架構及部署](hyva-implementation.md)、[Aeromalls 後台操作](hyva-admin-guide.md)、[Hyvä 子主題官方教學](https://docs.hyva.io/hyva-themes/building-your-theme/index.html)。
