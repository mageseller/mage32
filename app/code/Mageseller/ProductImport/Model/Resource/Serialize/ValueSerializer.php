<?php

namespace Mageseller\ProductImport\Model\Resource\Serialize;

/**
 * @author Patrick van Bergen
 */
interface ValueSerializer
{
    /**
     * @param  $value
     * @return string|null
     */
    public function serialize($value);

    /**
     * @param  string|null $serializedSource
     * @param  string      $field
     * @return mixed
     */
    public function extract($serializedSource, string $field);
}