<?php

namespace Mageseller\IngrammicroImport\Plugin;

class CategoryDropdownPlugin
{
    public function afterGetData(\Magento\Catalog\Model\Category\DataProvider $subject, $result)
    {
        $category = $subject->getCurrentCategory();
        if (isset($result[$category->getId()]['ingrammicro_category_ids']) && $result[$category->getId()]['ingrammicro_category_ids'] && is_array($result[$category->getId()]['ingrammicro_category_ids'])) {
            $result[$category->getId()]['ingrammicro_category_ids'] = explode(",", $result[$category->getId()]['ingrammicro_category_ids']);
        }
        return $result;
    }
}
