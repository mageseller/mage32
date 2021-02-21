<?php
namespace Mageseller\LeadersystemsImport\Controller\Adminhtml\Reset;

use Mageseller\LeadersystemsImport\Controller\Adminhtml\AbstractReset;

class Products extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {

        $this->connectorConfig->resetSyncDate('leadersystems', 'product');

        $this->messageManager->addSuccessMessage(__('Last products synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
