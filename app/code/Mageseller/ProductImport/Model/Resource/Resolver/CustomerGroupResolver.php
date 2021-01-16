<?php

namespace Mageseller\ProductImport\Model\Resource\Resolver;

use Mageseller\ProductImport\Model\Resource\MetaData;

/**
 * @author Patrick van Bergen
 */
class CustomerGroupResolver
{
    /** @var MetaData */
    protected $metaData;

    public function __construct(MetaData $metaData)
    {
        $this->metaData = $metaData;
    }

    public function resolveCustomerGroupName(string $name): array
    {
        $error = "";
        $id = null;

        if (!array_key_exists($name, $this->metaData->customerGroupMap)) {
            $error = "customer group name not found: " . $name;
        } else {
            $id = $this->metaData->customerGroupMap[$name];
        }

        return [$id, $error];
    }
}