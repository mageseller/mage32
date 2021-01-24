<?php
namespace Mageseller\DickerdataImport\Controller\Adminhtml\Reset;

use Mageseller\DickerdataImport\Controller\Adminhtml\AbstractReset;

class Images extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('images');

        $this->messageManager->addSuccessMessage(__('Last image synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
