<?php
/*
 * A Magento 2 module named Mageseller/DownloadImageMyOrder
 * Copyright (C) 2019
 *
 * This file included in Mageseller/DownloadImageMyOrder is licensed under OSL 3.0
 *
 *  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  Please see LICENSE.txt for the full text of the OSL 3.0 license
 */

namespace Mageseller\DownloadImageMyOrder\Controller\Index;

use Magento\Framework\App\Filesystem\DirectoryList;
use ZipArchive;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Catalog\Model\Product
     */
    private $product;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Magento\Framework\Filesystem
     */
    private $_filesystem;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Product $product,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->product = $product;
        $this->_url = $context->getUrl();
        $this->storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $product = $this->product->load($productId);
        $mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $mainImage = $product->getData('image');
        $filepath = $mediaDirectory->getAbsolutePath("catalog/product" . $mainImage);

        $files = [];
        /*if (($mediaDirectory->isExist($filepath)  && $mediaDirectory->isFile($filepath))) {
            $files[] = $filepath;
        }*/
        //if (!$mainImage || !($mediaDirectory->isExist($filepath) && $mediaDirectory->isFile($filepath))) {
        $mediaGallery = $product->getData('media_gallery');
        $imageToDownload = $mediaGallery['images'] ?? [];
        $image = "";
        foreach ($imageToDownload as $imageTo) {
            $image = $imageTo['file'];
            $filepath = $mediaDirectory->getAbsolutePath("catalog/product" . $image);
            if (($mediaDirectory->isExist($filepath) && $mediaDirectory->isFile($filepath))) {
                $files[] = $filepath;
                //break;
            }
        }
        //}

        $filepath = $mediaDirectory->getAbsolutePath("ProductImages.zip");
        $this->create_zip($files, $filepath);
        if ($mediaDirectory->isExist($filepath)) {
            $this->getResponse()
                ->setHttpResponseCode(200)
                ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Pragma', 'public', true)
                ->setHeader('Content-type', 'application/force-download')
                ->setHeader('Content-Length', filesize($filepath))
                ->setHeader('Content-Disposition', 'attachment' . '; filename=' . basename($filepath));
            $this->getResponse()->clearBody();
            $this->getResponse()->sendHeaders();
            readfile($filepath);
            exit;
        }
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
