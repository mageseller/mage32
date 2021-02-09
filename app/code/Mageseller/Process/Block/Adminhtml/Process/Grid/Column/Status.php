<?php
namespace Mageseller\Process\Block\Adminhtml\Process\Grid\Column;

use Mageseller\Process\Model\Process;

class Status extends AbstractColumn
{
    /**
     * {@inheritdoc}
     */
    public function decorate($value, $row, $column, $isExport)
    {
        if (!$value) { return '';
        }

        $isMageseller = strstr($column->getId(), 'mageseller') === false ? false : true;

        return '<span class="' . $row->getStatusClass($isMageseller) . '"><span>' . __($value) . '</span></span>';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        $options = [];
        foreach (Process::getStatuses() as $code => $label) {
            $options[] = [
                'value' => $code,
                'label' => __(ucwords(str_replace('_', ' ', $label))),
            ];
        }

        return $options;
    }
}
