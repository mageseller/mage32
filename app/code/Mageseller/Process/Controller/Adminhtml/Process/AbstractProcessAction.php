<?php
namespace Mageseller\Process\Controller\Adminhtml\Process;

use Mageseller\Process\Controller\Adminhtml\RedirectRefererTrait;
use Mageseller\Process\Model\Process;
use Mageseller\Process\Model\ResourceModel\Process as ProcessResource;

abstract class AbstractProcessAction extends \Magento\Backend\App\Action
{
    use RedirectRefererTrait;

    /**
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Mageseller_Process::process';

    /**
     * @return  Process
     */
    protected function getProcess()
    {
        $id = (int) $this->getRequest()->getParam('id');

        /** @var Process $process */
        $process = $this->getProcessModel();
        $this->getProcessResource()->load($process, $id);

        return $process;
    }

    /**
     * @return  Process
     */
    protected function getProcessModel()
    {
        return $this->_objectManager->create(Process::class);
    }

    /**
     * @return  ProcessResource
     */
    protected function getProcessResource()
    {
        return $this->_objectManager->create(ProcessResource::class);
    }

    /**
     * @param   string  $errorMessage
     * @param   bool    $referer
     * @return  \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    protected function redirectError($errorMessage, $referer = false)
    {
        $this->messageManager->addErrorMessage($errorMessage);
        $resultRedirect = $this->resultRedirectFactory->create();

        return $referer ? $this->redirectReferer() : $resultRedirect->setPath('*/*/');
    }
}
