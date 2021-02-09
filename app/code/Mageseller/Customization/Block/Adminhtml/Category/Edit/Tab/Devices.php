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
 * @category  Mageseller
 * @package   Mageseller_Customization
 * @copyright Copyright (c) 2017 Mageseller (http://www.mageseller.com/)
 * @license   https://www.mageseller.com/LICENSE.txt
 */

namespace Mageseller\Customization\Block\Adminhtml\Category\Edit\Tab;

/**
 * Class Devices
 *
 * @package Mageseller\Customization\Block\Adminhtml\Category\Edit\Tab
 */
class Devices extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Devices constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        //todo create default attributes table
        /**
 * @var \Mageseller\Customization\Model\Category $model 
*/
        $model = $this->_coreRegistry->registry('current_devices_category');
        $form  = $this->_formFactory->create();
        $form->setHtmlIdPrefix('cat_');

        $fieldset = $form->addFieldset(
            'base_fieldset',
            [
                'legend' => __('Custom Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $field = $fieldset->addField(
            'devices',
            'text',
            [
                'label' => __('Custom Information'),
                'title' => __('Custom Information')
            ]
        );

        /**
 * @var \Magento\Framework\Data\Form\Element\Renderer\RendererInterface $renderer 
*/
        $renderer = $this->getLayout()->createBlock('Mageseller\Customization\Block\Adminhtml\Form\Renderer\DevicesCategory');
        $field->setRenderer($renderer);

        $form->addValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Custom Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Custom Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
