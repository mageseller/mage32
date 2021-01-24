<?php
namespace Mageseller\LeadersystemsImport\Block\Adminhtml\System\Config\Button\Sync;

use Mageseller\LeadersystemsImport\Block\Adminhtml\System\Config\Button\AbstractButtons;

class Images extends AbstractButtons
{
    /**
     * @var array
     */
    protected $buttonsConfig = [
        [
            'label' => 'Import images in Magento',
            'url' => 'mageseller_leadersystemsimport/sync/images',
            'confirm' => 'Are you sure? This will update all modified offers since the last synchronization.',
            'class' => 'scalable',
        ],
        [
            'label' => 'Reset Date',
            'url' => 'mageseller_leadersystemsimport/reset/images',
            'confirm' => 'Are you sure? This will reset the last synchronization date.',
            'class' => 'scalable primary',
        ],
    ];
}