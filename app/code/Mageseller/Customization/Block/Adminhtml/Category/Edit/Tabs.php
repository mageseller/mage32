<?php
/**
 * Mageseller
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageseller.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageseller.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageseller
 * @package     Mageseller_Customization
 * @copyright   Copyright (c) 2017 Mageseller (http://www.mageseller.com/)
 * @license     https://www.mageseller.com/LICENSE.txt
 */

namespace Mageseller\Customization\Block\Adminhtml\Category\Edit;

/**
 * Class Tabs
 * @package Mageseller\Customization\Block\Adminhtml\ProductsPage\Edit
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    protected function _construct()
    {
        parent::_construct();
        $this->setId('devices_category_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Category'));
    }
}
