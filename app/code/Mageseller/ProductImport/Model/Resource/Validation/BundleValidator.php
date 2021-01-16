<?php

declare(strict_types=1);

namespace Mageseller\ProductImport\Model\Resource\Validation;

use Mageseller\ProductImport\Api\Data\BundleProduct;
use Mageseller\ProductImport\Api\Data\ConfigurableProduct;
use Mageseller\ProductImport\Api\Data\Product;

/**
 * @author Patrick van Bergen
 */
class BundleValidator
{
    /**
     * @param ConfigurableProduct $product
     * @param Product[] $batchProducts
     */
    public function validate(BundleProduct $product, array $batchProducts)
    {
        if (($options = $product->getOptions()) !== null) {
            foreach ($options as $option) {
                foreach ($option->getSelections() as $selection) {
                    $selectionSku = $selection->getSku();
                    if (array_key_exists($selectionSku, $batchProducts)) {
                        if (!$batchProducts[$selectionSku]->isOk()) {
                            $product->addError("A member product is invalid: " . $selectionSku);
                            break;
                        }
                    }
                }
            }
        }
    }
}
