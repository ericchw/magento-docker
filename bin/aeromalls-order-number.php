<?php
declare(strict_types=1);

// Run via PHP stdin inside php-fpm, as www-data, from /var/www/html.
require 'app/bootstrap.php';
$bootstrap = Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$resource = $bootstrap->getObjectManager()->get(Magento\Framework\App\ResourceConnection::class);
$db = $resource->getConnection('sales');
$table = fn(string $name): string => $resource->getTableName($name);
$old = '000000001';
$new = 'A10003012';
$db->beginTransaction();
try {
    $order = $db->fetchRow($db->select()->from($table('sales_order'), ['entity_id', 'increment_id', 'store_id', 'quote_id'])->where('entity_id = ?', 1)->forUpdate(true));
    if (!$order || !in_array($order['increment_id'], [$old, $new], true)) {
        throw new RuntimeException('Expected order 1 with the original or migrated number.');
    }
    $conflict = $db->fetchOne($db->select()->from($table('sales_order'), 'entity_id')->where('increment_id = ?', $new)->where('entity_id <> ?', 1));
    if ($conflict) {
        throw new RuntimeException('Target order number already exists.');
    }
    $meta = $db->fetchRow($db->select()->from($table('sales_sequence_meta'))->where('entity_type = ?', 'order')->where('store_id = ?', $order['store_id']));
    $profile = $db->fetchRow($db->select()->from($table('sales_sequence_profile'))->where('meta_id = ?', $meta['meta_id'])->where('is_active = ?', 1)->forUpdate(true));
    if ((int)$profile['step'] !== 1) {
        throw new RuntimeException('Expected native sequence step of one.');
    }
    $snapshot = ['order' => $order, 'meta' => $meta, 'profile' => $profile];
    foreach (['sales_order_grid', 'sales_invoice_grid', 'sales_shipment_grid', 'sales_creditmemo_grid'] as $name) {
        $column = $name === 'sales_order_grid' ? 'entity_id' : 'order_id';
        $snapshot[$name] = $db->fetchAll($db->select()->from($table($name))->where($column . ' = ?', 1));
    }
    $snapshot['quote'] = $db->fetchAll($db->select()->from($table('quote'), ['entity_id', 'reserved_order_id'])->where('entity_id = ?', $order['quote_id']));
    $backup = BP . '/var/aeromalls-order-number-' . gmdate('Ymd-His') . '.json';
    if (file_put_contents($backup, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) {
        throw new RuntimeException('Could not save backup.');
    }
    chmod($backup, 0600);
    $db->update($table('sales_order'), ['increment_id' => $new], ['entity_id = ?' => 1]);
    $db->update($table('sales_order_grid'), ['increment_id' => $new], ['entity_id = ?' => 1]);
    foreach (['sales_invoice_grid', 'sales_shipment_grid', 'sales_creditmemo_grid'] as $name) {
        $db->update($table($name), ['order_increment_id' => $new], ['order_id = ?' => 1]);
    }
    $db->update($table('quote'), ['reserved_order_id' => $new], ['entity_id = ?' => $order['quote_id'], 'reserved_order_id = ?' => $old]);
    // Explicit insertion advances the native AUTO_INCREMENT without allocating a future order.
    $db->insertOnDuplicate($meta['sequence_table'], ['sequence_value' => 10003012], ['sequence_value']);
    $db->commit();
    echo "Order 1: $new; native sequence advanced to at least 10003012. Backup: $backup\n";
} catch (Throwable $exception) {
    $db->rollBack();
    throw $exception;
}
