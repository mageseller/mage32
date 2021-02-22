<?php namespace Mageseller\Utility\Block\Adminhtml\Form\Field;

use Magento\CatalogInventory\Block\Adminhtml\Form\Field\SignOption;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class PriceMargin extends AbstractFieldArray
{
    private $signOptions;

    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('min', ['label' => __('Min. Cost'),'size' => "65", 'class' => 'required-entry']);
        $this->addColumn('max', ['label' => __('Max. Cost'), 'size' => "65",'class' => 'required-entry']);
        $this->addColumn('sign', ['label' => __('Sign'),'size' => "40", 'class' => 'required-entry', 'renderer' => $this->getSignOptions()]);
        $this->addColumn('value', ['label' => __('Value'),'size' => "100", 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Margin');
    }

    public function getSignOptions()
    {
        if (!$this->signOptions) {
            $this->signOptions = $this->getLayout()->createBlock(
                \Mageseller\Utility\Block\Adminhtml\Form\Field\SignOption::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->signOptions->setExtraParams('style="width:70px; font-size:14px"');
            $this->signOptions->setClass('sign_option');
        }
        return $this->signOptions;
    }
    /**
     * Prepare existing row data object
     *
     * @param \Magento\Framework\DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $optionExtraAttr = [];
        $optionExtraAttr['option_' . $this->getSignOptions()->calcOptionHash($row->getData('sign'))] =
            'selected="selected"';
        $row->setData(
            'option_extra_attrs',
            $optionExtraAttr
        );
    }
    protected function _toHtml()
    {
        return parent::_toHtml() .
            "<style>.accordion .config .value {
                padding-right: 40px;
                vertical-align: middle;
                width: 54%;
            }</style>";
    }
}
