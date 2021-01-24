<?php
namespace Mageseller\IngrammicroImport\Controller\Adminhtml\Reset;

use Mageseller\IngrammicroImport\Controller\Adminhtml\AbstractReset;

class Products extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {

        $this->connectorConfig->resetSyncDate('product');

        $this->messageManager->addSuccessMessage(__('Last products synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
