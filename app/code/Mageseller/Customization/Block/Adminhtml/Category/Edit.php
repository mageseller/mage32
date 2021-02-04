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

namespace Mageseller\Customization\Block\Adminhtml\Category;

/**
 * CMS block edit form container
 */
class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    public $_coreRegistry;

    /**
     * constructor
     *
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Block\Widget\Context $context,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_objectId   = 'cat_id';
        $this->_blockGroup = 'Mageseller_Customization';
        $this->_controller = 'adminhtml_category';

        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Category'));
        $this->buttonList->update('delete', 'label', __('Delete Category'));

        $this->buttonList->add(
            'saveandcontinue',
            [
                'label'          => __('Save and Continue Edit'),
                'class'          => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']]
                ]
            ],
            -100
        );
    }

    /**
     * Get edit form container header text
     *
     * @return string
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('current_devices_category')->getId()) {
            return __("Edit Category '%1'", $this->escapeHtml($this->_coreRegistry->registry('current_devices_category')->getName()));
        } else {
            return __('New Category');
        }
    }
}
