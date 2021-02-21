<?php
namespace Mageseller\IngrammicroImport\Controller\Adminhtml\Reset;

use Mageseller\IngrammicroImport\Controller\Adminhtml\AbstractReset;

class Images extends AbstractReset
{
    /**
     * Resets last synchronization date of offers
     */
    public function execute()
    {
        $this->connectorConfig->resetSyncDate('ingrammicro', 'images');

        $this->messageManager->addSuccessMessage(__('Last image synchronization date has been reset successfully.'));

        return $this->redirectReferer();
    }
}
