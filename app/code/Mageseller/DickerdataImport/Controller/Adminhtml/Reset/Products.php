<?php
namespace Mageseller\DickerdataImport\Controller\Adminhtml\Reset;

use Mageseller\DickerdataImport\Controller\Adminhtml\AbstractReset;

class Products extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {

        $this->connectorConfig->resetSyncDate('dickerdata', 'product');

        $this->messageManager->addSuccessMessage(__('Last products synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
