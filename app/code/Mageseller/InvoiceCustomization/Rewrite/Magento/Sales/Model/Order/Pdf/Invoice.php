<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mageseller\InvoiceCustomization\Rewrite\Magento\Sales\Model\Order\Pdf;

class Invoice extends \Magento\Sales\Model\Order\Pdf\Invoice
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var array $pageSettings
     */
    private $pageSettings;
    /**
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Sales\Model\Order\Pdf\Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        array $data = []
    ) {
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $localeResolver,
            $data
        );
    }

    /**
     * Return PDF document
     *
     * @param array|Collection $invoices
     * @return \Zend_Pdf
     */
    public function getPdf($invoices = [])
    {
        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $style = new \Zend_Pdf_Style();
        $this->_setFontBold($style, 10);
        $flag = false;
        foreach ($invoices as $invoice) {
            if ($invoice->getStoreId()) {
                $this->_localeResolver->emulate($invoice->getStoreId());
                $this->_storeManager->setCurrentStore($invoice->getStoreId());
            }
            $page = $this->newPage();
            $order = $invoice->getOrder();
            $this->insertWatermark($page, $invoice->getStore());

            /* Add image */
            $this->insertLogo($page, $invoice->getStore());
            /* Add address */
            $this->insertAddress($page, $invoice->getStore());

            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
            $this->_setFontBold($page, 16);
            $this->y -= 15;

            $page->drawText("Invoice", 265, $this->y, 'UTF-8');

            $this->y -= 15;
            /* Add head */
            $this->insertOrder(
                $page,
                $order,
                $this->_scopeConfig->isSetFlag(
                    self::XML_PATH_SALES_PDF_INVOICE_PUT_ORDER_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $order->getStoreId()
                ),
                $invoice
            );

            /* Add document text and number */
            $this->insertDocumentNumber($page, __('Invoice # ') . $invoice->getIncrementId());
            /* Add table */
            $this->_drawHeader($page);

            $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.6));
            $page->setLineWidth(0.5);
            $top = $this->y + 35;
            $page->drawLine(25, $top, 25, $this->y);
            $page->drawLine(220, $top, 220, $this->y);
            $page->drawLine(320, $top, 320, $this->y);
            $page->drawLine(380, $top, 380, $this->y);
            $page->drawLine(450, $top, 450, $this->y);
            $page->drawLine(500, $top, 500, $this->y);
            $page->drawLine(570, $top, 570, $this->y);

            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }
                $size = 15;
                $top = $this->y + 20;
                if ($this->y > 720) {
                    $top = $this->y + 75;
                    $this->insertWatermark($page, $invoice->getStore());
                }
                /* Draw item */
                $this->_drawItem($item, $page, $order);
                $page = end($pdf->pages);

                $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.6));
                $page->setLineWidth(0.5);
                $page->drawLine(25, $top, 25, $this->y + $size);
                if (($top - ($this->y + $size)) >=  0) {
                    $page->drawLine(220, $top, 220, $this->y + $size);
                    $page->drawLine(320, $top, 320, $this->y + $size);
                    $page->drawLine(380, $top, 380, $this->y + $size);
                    $page->drawLine(450, $top, 450, $this->y + $size);
                    $page->drawLine(500, $top, 500, $this->y + $size);
                }
                $page->drawLine(570, $top, 570, $this->y + $size);
                $page->drawLine(25, $this->y + 15, 570, $this->y + 15);
            }
            $top = $this->y + 20;
            /* Add totals */
            $this->insertTotals($page, $invoice);
            $this->insertStamp($page, $invoice->getStore());

            $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.6));
            $page->setLineWidth(0.5);
            $page->drawLine(25, $top, 25, $this->y);
            $page->drawLine(500, $top, 500, $this->y);
            $page->drawLine(570, $top, 570, $this->y);
            $page->drawLine(25, $this->y, 570, $this->y);

            $top = $this->y - 40;
            $num = floatval($invoice->getGrandTotal());
            $amountInWords = $this->numberTowords($num);
            $this->_setFontBold($page, 10);
            $page->drawText("In Words: ", 35, $this->y - 23, 'UTF-8');
            $this->_setFontBoldItalic($page, 10);
            $page->drawText("$amountInWords", 80, $this->y - 23, 'UTF-8');
            $page->drawLine(25, $top, 25, $this->y);
            $page->drawLine(570, $top, 570, $this->y);
            $page->drawLine(25, $top, 570, $this->y - 40);

            $this->_setFontBoldItalic($page, 7);
            $note1 = "*NOTE: Payment must be paid according to the Invoice currency. All disputed Invoice must be notified in writing and no later than three days after customer's receipt of Invoice.";
            $note2 = "This is system generated Invoice does not require any seal and signature.";

            $page->drawText($note1, 25, $this->y - 50, 'UTF-8');
            $page->drawText($note2, 25, $this->y - 60, 'UTF-8');
            if ($invoice->getStoreId()) {
                $this->_localeResolver->revert();
            }
        }
        //$this->_drawFooter($page);
        $this->_afterGetPdf();
        return $pdf;
    }

    /**
     * Insert totals to pdf page
     *
     * @param  \Zend_Pdf_Page $page
     * @param  \Magento\Sales\Model\AbstractModel $source
     * @return \Zend_Pdf_Page
     */
    protected function insertTotals($page, $source)
    {
        $order = $source->getOrder();
        $totals = $this->_getTotalsList();
        $lineBlock = ['lines' => [], 'height' => 15];
        foreach ($totals as $total) {
            $total->setOrder($order)->setSource($source);

            if ($total->canDisplay()) {
                $total->setFontSize(10);
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $lineBlock['lines'][] = [
                        [
                            'text' => $totalData['label'],
                            'feed' => 475,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold',
                        ],
                        [
                            'text' => $totalData['amount'],
                            'feed' => 555,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'bold'
                        ],
                    ];
                }
            }
        }

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, [$lineBlock]);
        return $page;
    }

    /**
     * Set font as bold
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontBold($object, $size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/Calibre/Calibri Bold.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param array $settings
     * @return \Zend_Pdf_Page
     */
    public function newPage(array $settings = [])
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        return $page;
    }

    /**
     * Draw header for item table
     *
     * @param \Zend_Pdf_Page $page
     * @return void
     */
    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        /* Add table head */
        $this->_setFontRegular($page, 10);
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(246, 245, 245));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 15);
        $this->y -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));

        //columns headers
        $lines[0][] = ['text' => __('Products'), 'font' => 'bold', 'feed' => 35];

        $lines[0][] = ['text' => __('Description'), 'font' => 'bold', 'feed' => 280, 'align' => 'right'];

        $lines[0][] = ['text' => __('Qty'), 'font' => 'bold', 'feed' => 355, 'align' => 'right'];

        $lines[0][] = ['text' => __('$ Price / Unit'), 'font' => 'bold', 'feed' => 440, 'align' => 'right'];

        $lines[0][] = ['text' => __('$ Tax'), 'font' => 'bold', 'feed' => 485, 'align' => 'right'];

        $lines[0][] = ['text' => __('$ Amount'), 'font' => 'bold', 'feed' => 555, 'align' => 'right'];

        $lineBlock = ['lines' => $lines, 'height' => 5];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * Set font as regular
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontRegular($object, $size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/Calibre/Calibri.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }

    protected function insertLogo(&$page, $store = null)
    {
        $this->y = $this->y ? $this->y : 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = 830;
                //top border of the page
                $widthLimit = 150;
                //half of the page width
                $heightLimit = 39;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 35;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);

                $this->y = $y1 - 10;
            }
        }
    }

    /**
     * Insert order to pdf page
     *
     * @param \Zend_Pdf_Page &$page
     * @param \Magento\Sales\Model\Order $obj
     * @param bool $putOrderId
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true, $invoice = null)
    {
        if ($obj instanceof \Magento\Sales\Model\Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(25, $top, 570, $top - 55);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->setDocHeaderCoordinates([25, $top, 570, $top - 55]);
        $font = $this->_setFontRegular($page, 10);
        if ($putOrderId) {
            $page->drawText(__('Order # ') . $order->getRealOrderId(), 35, $top -= 30, 'UTF-8');
            $top += 15;
        }
        if ($invoice) {
            $_value = __('Invoice Date: ') .
                $this->_localeDate->formatDate(
                    $this->_localeDate->scopeDate(
                        $invoice->getStore(),
                        $invoice->getCreatedAt(),
                        true
                    ),
                    \IntlDateFormatter::MEDIUM,
                    false
                );
        }

        $page->drawText(
            $_value,
            $this->getAlignRight($_value, 130, 440, $font, 10),
            $top,
            'UTF-8'
        );

        $top -= 15;
        $_value = __('Order Date: ') .
            $this->_localeDate->formatDate(
                $this->_localeDate->scopeDate(
                    $order->getStore(),
                    $order->getCreatedAt(),
                    true
                ),
                \IntlDateFormatter::MEDIUM,
                false
            );
        $page->drawText(
            $_value,
            $this->getAlignRight($_value, 130, 440, $font, 10),
            $top,
            'UTF-8'
        );
        $top -= 10;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(246, 245, 245));
        //$page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        /*$page->drawLine(25, $top, 25, $top );
        $page->drawLine(275, $top, 275, $top - 25);
        $page->drawLine(570, $top - 25, 570, $top - 25);
        $page->drawLine(25, $top - 25, 570, $top  -25);*/

        $page->drawRectangle(25, $top, 275, $top - 25);
        $page->drawRectangle(275, $top, 570, $top - 25);

        /* Calculate blocks info */

        /* Billing Address */
        $billingAddress = $this->_formatAddress($this->addressRenderer->format($order->getBillingAddress(), 'pdf'));

        /* Payment */
        $paymentInfo = $this->_paymentData->getInfoBlock($order->getPayment())->setIsSecureMode(true)->toPdf();
        $paymentInfo = htmlspecialchars_decode($paymentInfo, ENT_QUOTES);
        $payment = explode('{{pdf_row_separator}}', $paymentInfo);
        foreach ($payment as $key => $value) {
            if (strip_tags(trim($value)) == '') {
                unset($payment[$key]);
            }
        }
        reset($payment);

        /* Shipping Address and Method */
        if (!$order->getIsVirtual()) {
            /* Shipping Address */
            $shippingAddress = $this->_formatAddress(
                $this->addressRenderer->format($order->getShippingAddress(), 'pdf')
            );
            $shippingMethod = $order->getShippingDescription();
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontBold($page, 12);

        if (!$order->getIsVirtual()) {
            $page->drawText(__('Sold to:'), 35, $top - 15, 'UTF-8');
            $page->drawText(__('Ship to:'), 285, $top - 15, 'UTF-8');
        } else {
            $page->drawText(__('Bill to / Ship to:'), 35, $top - 15, 'UTF-8');
            $page->drawText(__('Payment Method:'), 285, $top - 15, 'UTF-8');
        }

        $addressesHeight = $this->_calcAddressHeight($billingAddress);
        if (isset($shippingAddress)) {
            $addressesHeight = max($addressesHeight, $this->_calcAddressHeight($shippingAddress));
        }

        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        if (!$order->getIsVirtual()) {
            $page->drawRectangle(25, $top - 25, 570, $top - 33 - $addressesHeight);
        }
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->_setFontRegular($page, 10);
        $this->y = $top - 40;
        $addressesStartY = $this->y;

        foreach ($billingAddress as $value) {
            if ($value !== '') {
                $text = [];
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $text[] = $_value;
                }
                foreach ($text as $part) {
                    $page->drawText(strip_tags(ltrim($part)), 35, $this->y, 'UTF-8');
                    $this->y -= 15;
                }
            }
        }

        $addressesEndY = $this->y;

        if (!$order->getIsVirtual()) {
            $this->y = $addressesStartY;
            foreach ($shippingAddress as $value) {
                if ($value !== '') {
                    $text = [];
                    foreach ($this->string->split($value, 45, true, true) as $_value) {
                        $text[] = $_value;
                    }
                    foreach ($text as $part) {
                        $page->drawText(strip_tags(ltrim($part)), 285, $this->y, 'UTF-8');
                        $this->y -= 15;
                    }
                }
            }

            $addressesEndY = min($addressesEndY, $this->y);
            $this->y = $addressesEndY;

            $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
            $page->setLineWidth(0.5);
            $page->drawRectangle(25, $this->y, 275, $this->y - 25);
            $page->drawRectangle(275, $this->y, 570, $this->y - 25);

            $this->y -= 15;
            $this->_setFontBold($page, 12);
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
            $page->drawText(__('Payment Method'), 35, $this->y, 'UTF-8');
            $page->drawText(__('Shipping Method:'), 285, $this->y, 'UTF-8');

            $this->y -= 10;
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));

            $this->_setFontRegular($page, 10);
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

            $paymentLeft = 35;
            $yPayments = $this->y - 15;
        } else {
            $yPayments = $addressesStartY;
            $paymentLeft = 285;
        }

        foreach ($payment as $value) {
            if (trim($value) != '') {
                //Printing "Payment Method" lines
                $value = preg_replace('/<br[^>]*>/i', "\n", $value);
                $value = str_replace('(Stripe)', '', $value);
                foreach ($this->string->split($value, 45, true, true) as $_value) {
                    $page->drawText(strip_tags(trim($_value)), $paymentLeft, $yPayments, 'UTF-8');
                    $yPayments -= 15;
                }
            }
        }

        if ($order->getIsVirtual()) {
            // replacement of Shipments-Payments rectangle block
            $yPayments = min($addressesEndY, $yPayments);
            $page->drawLine(25, $top - 25, 25, $yPayments);
            $page->drawLine(275, $top - 25, 275, $yPayments);
            $page->drawLine(570, $top - 25, 570, $yPayments);
            $page->drawLine(25, $yPayments, 570, $yPayments);

            $this->y = $yPayments - 15;
        } else {
            $topMargin = 15;
            $methodStartY = $this->y;
            $this->y -= 15;

            foreach ($this->string->split($shippingMethod, 45, true, true) as $_value) {
                $page->drawText(strip_tags(trim($_value)), 285, $this->y, 'UTF-8');
                $this->y -= 15;
            }

            $yShipments = $this->y;
            $totalShippingChargesText = "("
                . __('Total Shipping Charges')
                . " "
                . $order->formatPriceTxt($order->getShippingAmount())
                . ")";

            $page->drawText($totalShippingChargesText, 285, $yShipments - $topMargin, 'UTF-8');
            $yShipments -= $topMargin + 10;

            $tracks = [];
            if ($shipment) {
                $tracks = $shipment->getAllTracks();
            }
            if (count($tracks)) {
                $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.93, 0.92, 0.92));
                $page->setLineWidth(0.5);
                $page->drawRectangle(285, $yShipments, 510, $yShipments - 10);
                $page->drawLine(400, $yShipments, 400, $yShipments - 10);
                //$page->drawLine(510, $yShipments, 510, $yShipments - 10);

                $this->_setFontRegular($page, 9);
                $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
                //$page->drawText(__('Carrier'), 290, $yShipments - 7 , 'UTF-8');
                $page->drawText(__('Title'), 290, $yShipments - 7, 'UTF-8');
                $page->drawText(__('Number'), 410, $yShipments - 7, 'UTF-8');

                $yShipments -= 20;
                $this->_setFontRegular($page, 8);
                foreach ($tracks as $track) {
                    $maxTitleLen = 45;
                    $endOfTitle = strlen($track->getTitle()) > $maxTitleLen ? '...' : '';
                    $truncatedTitle = substr($track->getTitle(), 0, $maxTitleLen) . $endOfTitle;
                    $page->drawText($truncatedTitle, 292, $yShipments, 'UTF-8');
                    $page->drawText($track->getNumber(), 410, $yShipments, 'UTF-8');
                    $yShipments -= $topMargin - 5;
                }
            } else {
                $yShipments -= $topMargin - 5;
            }

            $currentY = min($yPayments, $yShipments);

            // replacement of Shipments-Payments rectangle block
            $page->drawLine(25, $methodStartY, 25, $currentY);
            //left
            $page->drawLine(25, $currentY, 570, $currentY);
            //bottom
            $page->drawLine(570, $currentY, 570, $methodStartY);
            //right

            $this->y = $currentY;
            $this->y -= 15;
        }
    }

    /**
     * Insert title and number for concrete document type
     *
     * @param \Zend_Pdf_Page $page
     * @param string $text
     * @return void
     */
    public function insertDocumentNumber(\Zend_Pdf_Page $page, $text)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(1));
        $this->_setFontRegular($page, 10);
        $docHeader = $this->getDocHeaderCoordinates();
        $page->drawText($text, 35, $docHeader[1] - 15, 'UTF-8');
    }

    public function numberTowords(float $amount)
    {
        $num = floor($amount);

        $amount_after_decimal = round($amount - ($num), 2) * 100;

        // Check if there is any number after decimal
        $amt_hundred = null;
        $count_length = strlen((string)$num);

        $x = 0;
        $string = [];
        $change_words = [0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'];
        $here_digits = ['', ' Hundred ', ' Thousand', ' Lakh ', ' Crore '];
        while ($x < $count_length) {
            $get_divider = ($x == 2) ? 10 : 100;
            $amount = floor($num % $get_divider);
            $num = floor($num / $get_divider);
            $x += $get_divider == 10 ? 1 : 2;
            if ($amount) {
                $add_plural = (($counter = count($string)) && $amount > 9) ? 's' : null;
                $amt_hundred = ($counter == 1 && $string[0]) ? ' & ' : null;
                $string [] = ($amount < 21) ? $change_words[$amount] . ' ' . $here_digits[$counter] . $add_plural . '' . $amt_hundred : $change_words[floor($amount / 10) * 10] . ' ' . $change_words[$amount % 10] . '' . $here_digits[$counter] . $add_plural . ' ' . $amt_hundred;
            } else {
                $string[] = null;
            }
        }
        $implode_to_Rupees = implode('', array_reverse($string));
        $get_paise = ($amount_after_decimal > 0) ? "& " . ($change_words[$amount_after_decimal / 10] . " " . $change_words[$amount_after_decimal % 10]) . ' Cent Only' : '& Zero Cent Only';
        return ($implode_to_Rupees ? $implode_to_Rupees . ' Dollar ' : '') . $get_paise;
    }

    protected function _drawFooter(\Zend_Pdf_Page $page)
    {
        $this->y = 40;
        $page->setFillColor(new \Zend_Pdf_Color_Rgb(1, 1, 1));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(60, $this->y, 510, $this->y - 30);

        $page->setFillColor(new \Zend_Pdf_Color_Rgb(0.1, 0.1, 0.1));
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/Calibre/CalibreMedium.ttf')
        );
        $page->setFont($font, 12);

        $this->y -= 15;

        $page->drawText("Mailing Address: ABC Road sector-1 Noida", 285, $this->y, 'UTF-8');
        $this->y -= 10;
        $page->drawText("help@abc.com", 285, $this->y, 'UTF-8');

        $store = null;
        $this->y = $this->y ? $this->y : 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = 830;
                //top border of the page
                $widthLimit = 270;
                //half of the page width
                $heightLimit = 270;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 455;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $width = 260;
                $height = 40;
                $y = $height / 2.5;
                $page->drawImage($image, 80, $y, 35 + $width / 1.5, $y + $height / 2);
            }
        }
    }

    /**
     * Set font as italic
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontItalic($object, $size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/Calibre/Calibri Italic.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * Set font as bold italic
     *
     * @param \Zend_Pdf_Page $object
     * @param int $size
     * @return \Zend_Pdf_Resource_Font
     */
    protected function _setFontBoldItalic($object, $size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('lib/internal/Calibre/Calibri Bold Italic.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }

    protected function insertWatermark(&$page, $store = null)
    {
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo_watermark',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo_watermark/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = 830;
                //top border of the page
                $widthLimit = 535;
                //half of the page width
                $heightLimit = 550;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 35;
                $x2 = $x1 + $width;
                //echo "y1 : $y1  y2 : $y2 x1 : $x1 x2 : $x2";die;
                //coordinates after transformation are rounded by Zend
                //y1 : 696.28014842301 y2 : 830 x1 : 35 x2 : 185
                //$page->drawImage($image, 35, 696, 185, 830);
                $page->drawImage($image, 35, 200, 570, 750);
            }
        }
    }
    protected function insertStamp(&$page, $store = null)
    {
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo_stamp',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
        if ($image) {
            $imagePath = '/sales/store/logo_stamp/' . $image;
            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = \Zend_Pdf_Image::imageWithPath($this->_mediaDirectory->getAbsolutePath($imagePath));
                $top = $this->y - 10;
                //top border of the page
                $widthLimit = 70;
                //half of the page width
                $heightLimit = 70;
                //assuming the image is not a "skyscraper"
                $width = $image->getPixelWidth();
                $height = $image->getPixelHeight();

                //preserving aspect ratio (proportions)
                $ratio = $width / $height;
                if ($ratio > 1 && $width > $widthLimit) {
                    $width = $widthLimit;
                    $height = $width / $ratio;
                } elseif ($ratio < 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $height * $ratio;
                } elseif ($ratio == 1 && $height > $heightLimit) {
                    $height = $heightLimit;
                    $width = $widthLimit;
                }

                $y1 = $top - $height;
                $y2 = $top;
                $x1 = 470;
                $x2 = $x1 + $width;

                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);
            }
        }
    }
    /**
     * Draw lines
     *
     * Draw items array format:
     * lines        array;array of line blocks (required)
     * shift        int; full line height (optional)
     * height       int;line spacing (default 10)
     *
     * line block has line columns array
     *
     * column array format
     * text         string|array; draw text (required)
     * feed         int; x position (required)
     * font         string; font style, optional: bold, italic, regular
     * font_file    string; path to font file (optional for use your custom font)
     * font_size    int; font size (default 7)
     * align        string; text align (also see feed parameter), optional left, right
     * height       int;line spacing (default 10)
     *
     * @param  \Zend_Pdf_Page $page
     * @param  array $draw
     * @param  array $pageSettings
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Zend_Pdf_Page
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function drawLineBlocks(\Zend_Pdf_Page $page, array $draw, array $pageSettings = [])
    {
        $this->pageSettings = $pageSettings;
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We don\'t recognize the draw line data. Please define the "lines" array.')
                );
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;
            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = [$column['text']];
                        }
                        $top = 0;
                        //
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 15) {
                $page = $this->newPage($pageSettings);
            }
            $this->correctLines($lines, $page, $height);
        }

        return $page;
    }

    /**
     * Correct lines.
     *
     * @param array $lines
     * @param \Zend_Pdf_Page $page
     * @param int $height
     * @throws \Zend_Pdf_Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function correctLines($lines, $page, $height) :void
    {
        foreach ($lines as $line) {
            $maxHeight = 0;
            foreach ($line as $column) {
                $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                if (!empty($column['font_file'])) {
                    $font = \Zend_Pdf_Font::fontWithPath($column['font_file']);
                    $page->setFont($font, $fontSize);
                } else {
                    $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                    switch ($fontStyle) {
                        case 'bold':
                            $font = $this->_setFontBold($page, $fontSize);
                            break;
                        case 'italic':
                            $font = $this->_setFontItalic($page, $fontSize);
                            break;
                        case 'italic bold':
                            $font = $this->_setFontBoldItalic($page, $fontSize);
                            break;
                        default:
                            $font = $this->_setFontRegular($page, $fontSize);
                            break;
                    }
                }

                if (!is_array($column['text'])) {
                    $column['text'] = [$column['text']];
                }
                $top = $this->correctText($column, $height, $font, $page);

                $maxHeight = $top > $maxHeight ? $top : $maxHeight;
            }
            $this->y -= $maxHeight;
        }
    }

    /**
     * Correct text.
     *
     * @param array $column
     * @param int $height
     * @param \Zend_Pdf_Resource_Font $font
     * @param \Zend_Pdf_Page $page
     * @throws \Zend_Pdf_Exception
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function correctText($column, $height, $font, $page) :int
    {
        $top = 0;
        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
        $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
        foreach ($column['text'] as $part) {
            if ($this->y - $lineSpacing < 15) {
                $page = $this->newPage($this->pageSettings);
            }

            $feed = $column['feed'];
            $textAlign = empty($column['align']) ? 'left' : $column['align'];
            $width = empty($column['width']) ? 0 : $column['width'];
            switch ($textAlign) {
                case 'right':
                    if ($width) {
                        $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                    } else {
                        $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                    }
                    break;
                case 'center':
                    if ($width) {
                        $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                    }
                    break;
                default:
                    break;
            }
            $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
            $top += $lineSpacing;
        }
        return $top;
    }
}
