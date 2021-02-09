<?php

namespace Mageseller\ProductImport\Model\Resource\Resolver;

use Mageseller\ProductImport\Model\Data\EavAttributeInfo;
use Mageseller\ProductImport\Model\Resource\MetaData;

class WeeeResolver
{
    /**
     * @var MetaData 
     */
    protected $metaData;

    public function __construct(MetaData $metaData)
    {
        $this->metaData = $metaData;
    }

    public function resolveWeeeAttributeId(): string
    {
        if ($this->metaData->weeeAttributeId === null) {
            return "weee attribute not found";
        }

        return "";
    }

}
