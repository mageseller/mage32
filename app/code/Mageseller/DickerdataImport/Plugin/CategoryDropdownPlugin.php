<?php

namespace Mageseller\DickerdataImport\Plugin;

class CategoryDropdownPlugin
{
    public function afterGetData(\Magento\Catalog\Model\Category\DataProvider $subject, $result)
    {
        $category = $subject->getCurrentCategory();
        if (isset($result[$category->getId()]['dickerdata_category_ids']) && $result[$category->getId()]['dickerdata_category_ids'] && is_array($result[$category->getId()]['dickerdata_category_ids'])) {
            $result[$category->getId()]['dickerdata_category_ids'] = explode(",", $result[$category->getId()]['dickerdata_category_ids']);
        }
        return $result;
    }
}
