<?php namespace Mageseller\XitImport\Block\Adminhtml\Form\Field;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class BackorderOptions extends AbstractFieldArray
{
    /**
     * {@inheritdoc}
     */
    protected function _prepareToRender()
    {
        $this->addColumn('options', ['label' => __('Options'), 'class' => 'required-entry']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Options');
    }
}
