<?php

namespace Mageseller\IngrammicroImport\Block\Adminhtml\Form\Field;

/**
 * Custom import CSV file field for shipping table rates
 *
 * @author Magento Core Team <core@magentocommerce.com>
 */
class ImportCategory extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    /**
     * Enter description here...
     *
     * @return string
     */
    public function getElementHtml()
    {
        $html = '';

        $html .= '<input id="time_condition" type="hidden" name="' . $this->getName() . '" value="' . time() . '" />';
        $html .= parent::getElementHtml();

        return $html;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setType('file');
    }
}
