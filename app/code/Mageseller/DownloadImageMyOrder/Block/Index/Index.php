<?php
/*
 * A Magento 2 module named Mageseller/DownloadImageMyOrder
 * Copyright (C) 2020
 *
 *  @author      satish29g@hotmail.com
 *  @site        https://www.mageseller.com/
 *
 * This file included in Mageseller/DownloadImageMyOrder is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 *
 */

namespace Mageseller\DownloadImageMyOrder\Block\Index;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Gallery\ImagesConfigFactoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\View\Element\Template\Context;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;
    protected $product;
    /**
     * @var UrlBuilder
     */
    private $imageUrlBuilder;
    /**
     * @var Image|mixed
     */
    private $_imageHelper;


    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param array $data
     * @param Image $imageHelper
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        array $data = [],
        Image $imageHelper
    ) {
        parent::__construct($context, $data);
        $this->jsonEncoder = $jsonEncoder;
        $this->_imageHelper  = $imageHelper ?? ObjectManager::getInstance()->get(Image::class);
    }

    public function isFilesExist(\Magento\Sales\Model\Order\Item $item)
    {
        $product = $item->getProduct();
        $mediaDirectory = $this->getMediaDirectory();
        $mainImage = $product->getData('image');
        $filepath = $mediaDirectory->getAbsolutePath("catalog/product" . $mainImage);
        if (!$mainImage || !($mediaDirectory->isExist($filepath) && $mediaDirectory->isFile($filepath))) {
            $mediaGallery = $product->getData('media_gallery');
            $imageToDownload = $mediaGallery['images'] ?? [];
            foreach ($imageToDownload as $imageTo) {
                $image = $imageTo['file'];
                $filepath = $mediaDirectory->getAbsolutePath("catalog/product" . $image);
                if (($mediaDirectory->isExist($filepath) && $mediaDirectory->isFile($filepath))) {
                    return true;
                }
            }
        } else {
            return true;
        }
        return false;
    }

    public function getGalleryImages(\Magento\Sales\Model\Order\Item $item = null)
    {
        $product = $item ? $item->getProduct() : $this->getProduct();
        $this->setProduct($product);
        $images = $product->getMediaGalleryImages();

        if (!$images instanceof \Magento\Framework\Data\Collection) {
            return $images;
        }
        foreach ($images as $image) {
            $small_image_url = $this->_imageHelper->init($product, 'product_page_image_small')
                ->setImageFile($image->getFile())
                ->getUrl();
            $image->setData('small_image_url',$small_image_url);
        }

        return $images;
    }

    /**
     * @return mixed
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param mixed $product
     */
    public function setProduct($product): void
    {
        $this->product = $product;
    }
}
