<?php

declare(strict_types=1);

// Deployment entry point: pipe into PHP inside /var/www/html as www-data.
require 'app/bootstrap.php';

umask(0002);
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objects = $bootstrap->getObjectManager();
$objects->get(\Magento\Framework\App\State::class)->setAreaCode('adminhtml');
$resource = $objects->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();
$themeId = $connection->fetchOne(
    $connection->select()->from($resource->getTableName('theme'), 'theme_id')
        ->where('theme_path = ?', 'Aeromalls/aeromalls')->where('area = ?', 'frontend')
);
if (!$themeId) {
    throw new \RuntimeException('Aeromalls theme is not registered. Run setup:upgrade first.');
}

$host = getenv('AEROMALLS_HOME_HOST') ?: 'daiwa-ubuntu-server';
$storeManager = $objects->get(\Magento\Store\Model\StoreManagerInterface::class);
$pages = $objects->get(\Magento\Cms\Model\ResourceModel\Page\CollectionFactory::class);
$writer = $objects->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$repository = $objects->get(\Magento\Cms\Api\PageRepositoryInterface::class);
$targets = [];
$snapshot = ['host' => $host, 'config' => [], 'pages' => []];
foreach ($storeManager->getStores() as $store) {
    if (parse_url($store->getBaseUrl(), PHP_URL_HOST) !== $host
        && parse_url($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true), PHP_URL_HOST) !== $host) {
        continue;
    }
    $page = $pages->create()->addStoreFilter((int) $store->getId())
        ->addFieldToFilter('identifier', 'aeromalls-home')->getFirstItem();
    if (!$page->getId()) {
        // Reuse an unambiguous existing page even when not assigned to this store.
        // Never overwrite its content or remove its existing store assignments.
        $existing = $pages->create()->addFieldToFilter('identifier', 'aeromalls-home');
        if ($existing->getSize() === 1) {
            $page = $existing->getFirstItem();
        } else {
            $page = $objects->get(\Magento\Cms\Model\PageFactory::class)->create()
                ->setTitle('Aeromalls Home')->setIdentifier('aeromalls-home')
                ->setStores([(int) $store->getId()])->setIsActive(false)
                ->setPageLayout('1column')
                ->setContent('{{block class="Aeromalls\\Storefront\\Block\\Content" template="Aeromalls_Storefront::home.phtml"}}');
        }
    }
    $targets[] = [$store, $page];
    $pageKey = $page->getId() ? (string) $page->getId() : 'new-store-' . $store->getId();
    $snapshot['pages'][$pageKey] = ['existed' => (bool) $page->getId(),
        'is_active' => (int) $page->getIsActive(),
        'stores' => $page->getId() ? $connection->fetchCol(
            $connection->select()->from($resource->getTableName('cms_page_store'), 'store_id')
                ->where('page_id = ?', (int) $page->getId())
        ) : [], 'target_store_id' => (int) $store->getId()];
    foreach (['design/theme/theme_id', 'web/default/cms_home_page'] as $path) {
        $row = $connection->fetchRow(
            $connection->select()->from($resource->getTableName('core_config_data'))
                ->where('scope = ?', 'stores')->where('scope_id = ?', (int) $store->getId())
                ->where('path = ?', $path)
        );
        $snapshot['config'][] = ['scope' => 'stores', 'scope_id' => (int) $store->getId(),
            'path' => $path, 'existed' => (bool) $row, 'value' => $row ? $row['value'] : null];
    }
}
if (!$targets) {
    throw new \RuntimeException('No store base URL matches host: ' . $host);
}
$backup = BP . '/var/aeromalls-home-before-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.json';
if (file_put_contents($backup, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) {
    throw new \RuntimeException('Cannot write configuration snapshot in var/.');
}
$connection->beginTransaction();
try {
    foreach ($targets as [$store, $page]) {
        $assigned = $page->getId() ? array_map('intval', $connection->fetchCol(
            $connection->select()->from($resource->getTableName('cms_page_store'), 'store_id')
                ->where('page_id = ?', (int) $page->getId())
        )) : [];
        if (!in_array(0, $assigned, true)) {
            $assigned[] = (int) $store->getId();
        }
        $page->setStores(array_values(array_unique($assigned)))->setIsActive(true);
        $repository->save($page);
        $writer->save('design/theme/theme_id', (string) $themeId, 'stores', (int) $store->getId());
        $writer->save('web/default/cms_home_page', 'aeromalls-home', 'stores', (int) $store->getId());
    }
    $connection->commit();
} catch (\Throwable $error) {
    $connection->rollBack();
    throw $error;
}
echo "Previous settings saved to: " . $backup . PHP_EOL;
foreach ($targets as [$store]) {
    echo 'Activated Aeromalls theme and homepage for store: ' . $store->getCode() . PHP_EOL;
}
