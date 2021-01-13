<?php

namespace Mageseller\SupplierImport\Plugin;

class CategoryDropdownPlugin
{
    public function afterGetData(\Magento\Catalog\Model\Category\DataProvider $subject, $result)
    {
        $category = $subject->getCurrentCategory();
        $result[$category->getId()]['xit_category_ids'] = explode(",", $result[$category->getId()]['xit_category_ids']);
        return $result;
    }
}
