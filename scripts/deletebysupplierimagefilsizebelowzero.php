<?php
use Magento\Framework\App\Bootstrap;

require '../app/bootstrap.php';

$params = $_SERVER;

$bootstrap = Bootstrap::create(BP, $params);

$obj = $bootstrap->getObjectManager();

$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$registry = $obj->get('\Magento\Framework\Registry');
$registry->register('isSecureArea', true);

/*
 * @var \Mageseller\Utility\Helper\Data $utility
 * */
$utility = $obj->get('\Mageseller\Utility\Helper\Data');
$supplierOptions = $utility->loadOptionValues('supplier');
$supplierId  = $supplierOptions['leadersystems'];
$productCollection = $obj->get('Magento\Catalog\Model\Product')->getCollection();
$productCollection->addAttributeToFilter('supplier', $supplierId);
$connection = $productCollection->getConnection();
$select = $connection->select()->from(
    ['value_entity' => $productCollection->getTable('catalog_product_entity_media_gallery')]
);

/*----Join Query for getting value from media_gallery table using value_id----- */

$images = $connection->fetchAll($select);

foreach ($images as $image) {
    $filename = $image['value'];
    $filepath = BP . "/pub/media/catalog/product" . $filename;
    if (file_exists($filepath) && filesize($filepath) < 1) {
        unlink($filepath);
        echo "Deleted " . $filepath . "\n";
    }
}
