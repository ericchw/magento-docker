<?php
declare(strict_types=1);

namespace Aeromalls\Storefront\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\SalesSequence\Model\Meta;
use Magento\SalesSequence\Model\Sequence;

/** Keep native atomic sequences, formatting only order numbers as A + eight digits. */
class OrderSequence extends Sequence
{
    public function __construct(
        Meta $meta,
        ResourceConnection $resource,
        $pattern = self::DEFAULT_PATTERN
    ) {
        parent::__construct($meta, $resource, $meta->getEntityType() === 'order' ? 'A%2$08d' : $pattern);
    }
}
