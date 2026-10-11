# OpenSearch 搜尋故障：No alive nodes found

2026-10-08 約下午 2:43（Vancouver），主機 kernel 記錄顯示 global OOM 殺死 OpenSearch Java 程序。容器退出碼 137；Magento 指向 `opensearch:9200` 嘅設定正確，但搜尋服務停止，搜尋 AMD 因此回傳 HTTP 500。

## 修復設定

- `compose.yaml`：OpenSearch 加入 `restart: unless-stopped`。
- `env/opensearch.env`：`OPENSEARCH_JAVA_OPTS=-Xms512m -Xmx512m`，原值為 1 GB。實際容器使用量包括 native memory，會高於 heap。
- 重建 OpenSearch 容器時沿用 `opensearch-data` volume，原有索引保留。

512 MB 適合目前細小索引；商品量、流量或 aggregation 負載增加時應監察 heap／GC，配合增加主機記憶體重新調整。自動重啟並不能代替充足嘅 RAM。

## 檢查及恢復

```bash
docker compose ps -a opensearch
docker compose logs --tail=50 opensearch
docker compose exec -T -u www-data php-fpm curl -fsS http://opensearch:9200/_cluster/health
```

設定改動後套用至搜尋服務：

```bash
docker compose up -d --no-deps opensearch
```

啟動期間需等叢集 ready。單節點有 replica 未分配時可能顯示 yellow，只要 primary shards 已恢復，搜尋仍可正常運作。不要刪除 volume 或索引來處理連線故障。

主機 OOM 證據可以從 `journalctl -k` 查找 `Out of memory`／`Killed process`；Docker 的 OOMKilled 標記未必反映主機 global OOM。

JVM heap 設定方式參考 [OpenSearch 官方 Docker 文件](https://docs.opensearch.org/latest/install-and-configure/install-opensearch/docker/)。
