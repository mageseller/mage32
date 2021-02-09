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
namespace Mageseller\Customization\Block\Adminhtml\Attribute\Edit;

/**
 * Class Devices
 *
 * @package Mageseller\Customization\Block\Adminhtml\Attribute\Edit
 */
class Devices extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Cms\Model\Wysiwyg\Config  
     */
    protected $_wysiwygConfig;

    /**
     * Devices constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param \Magento\Cms\Model\Wysiwyg\Config       $wysiwygConfig
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig,
        array $data = []
    ) {
        $this->_wysiwygConfig = $wysiwygConfig;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Adding product form elements for editing attribute
     *
     * @return                                      $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function _prepareForm()
    {
        $data = $this->getOptionData();
        $form = $this->_formFactory->create(
            [
            'data' => [
                'id'            => 'devices_attribute_save',
                'action'        => $this->getUrl('devices/attribute/save', ['id' => $data['customization_id'] ?? ""]),
                'method'        => 'post',
                'use_container' => true,
                'enctype'       => 'multipart/form-data'
            ]
            ]
        );

        $mainfieldset = $form->addFieldset(
            'devices_fieldset',
            [
                'legend' => __('Attribute Custom Information'),
                'class'  => 'fieldset-wide'
            ]
        );
        $mainfieldset->addField(
            'option_id',
            'hidden',
            [
                'name' => 'option_id'
            ]
        );
        $mainfieldset->addField(
            'store_id',
            'hidden',
            [
                'name' => 'store_id'
            ]
        );
        $mainfieldset->addField(
            'alternate_options',
            'text',
            [
                'name'  => 'alternate_options',
                'label' => __('Attribute replacement names'),
                'title' => __('Attribute replacement names'),
                'note'  => __('Add semicolon seperator ";" if more than one replacement is there.')
            ]
        );




        $form->addValues($data);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
