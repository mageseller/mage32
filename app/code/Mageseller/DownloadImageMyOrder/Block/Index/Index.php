<?php
/*
 * A Magento 2 module named Mageseller/DownloadImageMyOrder
 * Copyright (C) 2020
 *
 *  @author      satish29g@hotmail.com
 *  @site        https://www.mageseller.com/
 *
 * This file included in Mageseller/DriveFx is licensed under OSL 3.0
 *
 * http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * Please see LICENSE.txt for the full text of the OSL 3.0 license
 *
 */

namespace Mageseller\DownloadImageMyOrder\Block\Index;

use Magento\Catalog\Block\Product\View\GalleryOptions;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Gallery\ImagesConfigFactoryInterface;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Framework\View\Element\Template\Context;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Config\View
     */
    protected $configView;

    /**
     * @var EncoderInterface
     */
    protected $jsonEncoder;
    protected $product;
    /**
     * @var array
     */
    private $galleryImagesConfig;
    /**
     * @var ImagesConfigFactoryInterface
     */
    private $galleryImagesConfigFactory;
    /**
     * @var UrlBuilder
     */
    private $imageUrlBuilder;
    /**
     * @var GalleryOptions
     */
    private $galleryOptions;

    /**
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param GalleryOptions $galleryOptions
     * @param array $data
     * @param ImagesConfigFactoryInterface|null $imagesConfigFactory
     * @param array $galleryImagesConfig
     * @param UrlBuilder|null $urlBuilder
     */
    public function __construct(
        Context $context,
        ArrayUtils $arrayUtils,
        EncoderInterface $jsonEncoder,
        GalleryOptions $galleryOptions,
        array $data = [],
        ImagesConfigFactoryInterface $imagesConfigFactory = null,
        array $galleryImagesConfig = [],
        UrlBuilder $urlBuilder = null
    ) {
        parent::__construct($context, $data);
        $this->jsonEncoder = $jsonEncoder;
        $this->galleryOptions = $galleryOptions;
        $this->galleryImagesConfigFactory = $imagesConfigFactory ?: ObjectManager::getInstance()
            ->get(ImagesConfigFactoryInterface::class);
        $this->galleryImagesConfig = $galleryImagesConfig;
        $this->galleryImagesConfig = [
            'small_image' => [
                'image_id' => 'product_page_image_small',
                'data_object_key' => 'small_image_url',
                'json_object_key' => 'thumb',
            ],
            'medium_image' => [
                'image_id' => 'product_page_image_medium',
                'data_object_key' => 'medium_image_url',
                'json_object_key' => 'img',
            ],
            'large_image' => [
                'image_id' => 'product_page_image_large',
                'data_object_key' => 'large_image_url',
                'json_object_key' => 'full',
            ],
        ];
        $this->imageUrlBuilder = $urlBuilder ?? ObjectManager::getInstance()->get(UrlBuilder::class);
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
            $image = "";
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
            $galleryImagesConfig = $this->getGalleryImagesConfig()->getItems();
            foreach ($galleryImagesConfig as $imageConfig) {
                $image->setData(
                    $imageConfig->getData('data_object_key'),
                    $this->imageUrlBuilder->getUrl($image->getFile(), $imageConfig['image_id'])
                );
            }
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

    /**
     * Returns image gallery config object
     *
     * @return Collection
     */
    private function getGalleryImagesConfig()
    {
        if (false === $this->hasData('gallery_images_config')) {
            $galleryImageConfig = $this->galleryImagesConfigFactory->create($this->galleryImagesConfig);
            $this->setData('gallery_images_config', $galleryImageConfig);
        }

        return $this->getData('gallery_images_config');
    }
}
