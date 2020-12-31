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

namespace Mageseller\DownloadImageMyOrder\Block\Index;

use Magento\Framework\App\Filesystem\DirectoryList;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_filesystem = $filesystem;
    }

    public function isFilesExist(\Magento\Sales\Model\Order\Item $item)
    {
        $product = $item->getProduct();
        $mediaDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA);
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

}
