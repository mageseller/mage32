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
$supplierId  = $supplierOptions['xitdistribution'];

$productCollection = $obj->get('Magento\Catalog\Model\Product')->getCollection();
$productCollection->addAttributeToFilter('supplier', $supplierId);
$productCollection->walk('delete');

