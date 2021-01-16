<?php
namespace Mageseller\XitImport\Controller\Adminhtml\Reset;

use Mageseller\XitImport\Controller\Adminhtml\AbstractReset;

class Images extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('image');

        $this->messageManager->addSuccessMessage(__('Last image synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
