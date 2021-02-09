<?php
namespace Mageseller\Process\Helper;

interface SynchroResultInterface
{
    /**
     * Gets synchronization error report file
     *
     * @param  string $synchroId
     * @return \SplFileObject
     */
    public function getErrorReport($synchroId);

    /**
     * Gets synchronization result by its id
     *
     * @param string $synchroId
     */
    public function getSynchroResult($synchroId);
}