<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mageseller\Utility\Block\Adminhtml\Form\Field;

/**
 * HTML select element block with customer groups options
 */
class SignOption extends \Magento\Framework\View\Element\Html\Select
{
    /**
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->addOption("+", "+");
            $this->addOption("-", "-");
            $this->addOption("%", "%");
            $this->addOption("=", "=");
        }
        return parent::_toHtml();
    }
}
