<?php

namespace Mageseller\ProductImport\Model\Data;

use Mageseller\ProductImport\Api\Data\DownloadLink;
use Mageseller\ProductImport\Helper\Decimal;

/**
 * @author Patrick van Bergen
 */
class DownloadLinkInformation
{
    /**
     * @var DownloadLink 
     */
    protected $downloadLink;

    /**
     * @var string 
     */
    protected $title;

    /**
     * @var string 
     */
    protected $price;

    public function __construct(DownloadLink $downloadLink, string $title, string $price)
    {
        $this->downloadLink = $downloadLink;
        $this->title = trim($title);
        $this->price = Decimal::formatPrice($price);
    }

    /**
     * @return DownloadLink
     */
    public function getDownloadLink(): DownloadLink
    {
        return $this->downloadLink;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }
}
