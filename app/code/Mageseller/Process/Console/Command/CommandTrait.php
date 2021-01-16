<?php
namespace Mageseller\Process\Console\Command;

/**
 * @property \Magento\Framework\App\State $appState
 */
trait CommandTrait
{
    /**
     * Set area code in safe mode
     *
     * @param string $code
     */
    public function setAreaCode($code)
    {
        try {
            $area = $this->appState->getAreaCode();
        } catch (\Exception $e) {
            // Ignore potential exception
        } finally {
            if (empty($area)) {
                $this->appState->setAreaCode($code);
            }
        }
    }
}