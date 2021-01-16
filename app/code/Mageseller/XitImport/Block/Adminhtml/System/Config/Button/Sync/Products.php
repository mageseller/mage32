<?php
namespace Mageseller\XitImport\Block\Adminhtml\System\Config\Button\Sync;

use Mageseller\XitImport\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Products extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label' => 'Import in Magento',
            'url' => 'mageseller_xitimport/sync/products',
            'confirm' => 'Are you sure? This will update all modified offers since the last synchronization.',
            'class' => 'scalable',
        ],
        [
            'label' => 'Reset Date',
            'url' => 'mageseller_xitimport/reset/products',
            'confirm' => 'Are you sure? This will reset the last synchronization date.',
            'class' => 'scalable primary',
        ],
    ];
}
