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

namespace Mageseller\DownloadImageMyOrder\Controller\Index;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;
use ZipArchive;

class Index extends Action
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;
    /**
     * @var Product
     */
    private $product;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Magento\Framework\Filesystem
     */
    private $_filesystem;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Product $product
     * @param StoreManagerInterface $storeManager
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Product $product,
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        FileFactory $fileFactory
    )
    {
        $this->product = $product;
        $this->_url = $context->getUrl();
        $this->storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $product = $this->product->load($productId);
        $mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $files = [];
        $mediaGallery = $product->getData('media_gallery');
        $imageToDownload = $mediaGallery['images'] ?? [];
        foreach ($imageToDownload as $imageTo) {
            $image = $imageTo['file'];
            $filepath = $mediaDirectory->getAbsolutePath("catalog/product" . $image);
            if (($mediaDirectory->isExist($filepath) && $mediaDirectory->isFile($filepath))) {
                $files[] = $filepath;
            }
        }
        if (count($files) == 1) {
            $filepath = $files[0] ?? "";
            $downloadName = basename($filepath);
        } else {
            $sku = strtolower(preg_replace('/[^a-zA-Z_0-9]/', '_', $product->getSku()));
            $filepath = $mediaDirectory->getAbsolutePath("catalog\product\productimages_$sku.zip");
            $downloadName = "ProductImages_SKU_$sku.zip";
        }

        $this->create_zip($files, $filepath);
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 1; // If you will set here 1 then, it will remove file from location.
        return $this->fileFactory->create($downloadName, $content, DirectoryList::PUB);
    }

    public function create_zip($files = [], $filepath)
    {
        $zip = new ZipArchive();
        $res = $zip->open($filepath, ZipArchive::CREATE);
        if ($res === true) {
            foreach ($files as $file) {
                $download_file = file_get_contents($file);
                $zip->addFromString(basename($file), $download_file);
            }
            $zip->close();
        }
    }
}
