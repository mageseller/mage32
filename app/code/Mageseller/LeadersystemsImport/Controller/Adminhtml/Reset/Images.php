<?php
namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\Reset;

use Mageseller\LeadersystemsImport\Controller\Adminhtml\AbstractReset;

class Images extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('leadersystems', 'images');

        $this->messageManager->addSuccessMessage(__('Last image synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
