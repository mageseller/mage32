<?php
/*
 * A Magento 2 module named Mageseller/DownloadImageMyOrder
 * Copyright (C) 2020
 *
 *  @author      satish29g@hotmail.com
 *  @site        https://www.mageseller.com/
 *
 * This file included in Mageseller/DriveFx is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 *
 */

namespace Mageseller\DownloadImageMyOrder\Plugin\Magento\Sales\Block\Items;

class AbstractItems
{
    protected $helperData;

    public function __construct(
        \Mageseller\DownloadImageMyOrder\Helper\Data $helperData
    ) {
        $this->helperData = $helperData;
    }
    public function afterGetItemHtml(
        \Magento\Sales\Block\Items\AbstractItems $subject,
        $result,
        $item
    ) {
        if (!$this->helperData->getGeneralConfig('display_image_btn')) {
            return $result;
        }
        $result = $result . $subject->getChildBlock("sales.order.items.renderers.download")->setItem($item)->toHtml();
        return $result;
    }
}
