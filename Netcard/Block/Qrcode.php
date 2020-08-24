<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\App\ObjectManager;
use Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentInvoice;
use Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestCard;
use Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentAddress;
use Magento\Framework\Module\Dir;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Class Redirect
 * To handel Qrcode from Magento to Sandbox
 * @package Netopia\Netcard\Block
 */
class Qrcode extends Template
{
    protected $_storeManager;
    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_orderFactory;
    protected $_resource;
    protected $_moduleReader;
    Protected $quoteFactory;
    protected $_curl;

    /**
     * @var MobilpayPaymentRequestCard
     */
    Protected $mobilpayPaymentRequestCard;
    /**
     * @var MobilpayPaymentInvoice
     */
    Protected $mobilpayPaymentInvoice;
    /**
     * @var Payment\MobilpayPaymentAddress
     */
    Protected $mobilpayPaymentAddress;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param ResourceConnection $resource
     * @param Order $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param Reader $reader
     * @param array $data
     */
    public function __construct(
                                Context $context,
                                Session $session,
                                ResourceConnection $resource,
                                Order $orderFactory,
                                QuoteFactory $quoteFactory,
                                Reader $reader,
                                Curl $curl,
                                array $data)
    {
        $this->_resource = $resource;
        $this->_checkoutSession = $session;
        $this->_orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->_moduleReader = $reader;
        $this->_curl = $curl;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');
        $quoteId = $this->getRequest()->getParam('quote');

        /** @var ObjectManager $ */
        $obm = ObjectManager::getInstance();

        /** @var \Magento\Framework\App\Http\Context $context */
        $context = $obm->get('Magento\Framework\App\Http\Context');

        // check AUth before Payment
        /** @var bool $isLoggedIn */
        $isLoggedIn = $context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id='.$connection->quote($quoteId).' LIMIT 1');
            //return $this->quoteFactory->create()->load($quoteId);
//            $order = $this->_checkoutSession->getLastRealOrder();
//            $orderId=$order->getEntityId();
        } else {
            $orderId = $connection->fetchAll('SELECT `'.$tblSalesOrder.'`.entity_id FROM `'.$tblSalesOrder.'` INNER JOIN `'.$tblQuoteIdMask.'` ON `'.$tblSalesOrder.'`.quote_id=`'.$tblQuoteIdMask.'`.quote_id AND `'.$tblQuoteIdMask.'`.masked_id='.$connection->quote($quoteId));
           // Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
        }
        //print_r($this->_orderFactory->loadByAttribute('entity_id',$orderId));
        return $this->_orderFactory->loadByAttribute('entity_id',$orderId);
    }


    // Get QrCode 
    public function getQrcodeData()
    {
    $e = null;
    $shipping = $this->getOrder()->getShippingAddress();
    $billing = $this->getOrder()->getBillingAddress();
    $order = $this->getOrder();
    $qrCode = [
            'error' => '',
            'message' => '',
            'transactionId' => ''
        ];
    
    try {
        $data =
                [
                    'account' => ['id' => $this->getConfigData('auth/signature')],
                    'platform' => 4,
                    "order" => [
                        "amount" => $order->getBaseGrandTotal(),
                        "id" => $order->getId(),
                        "currency" => $order->getBaseCurrencyCode(),
                        "description" => "this is for QrCodetest",
                        "billing" => [
                            "address" => implode(', ', $billing->getStreet()).', '.
                                                       $billing->getPostcode().', '.
                                                       $billing->getRegion(),
                            "first_name" => $billing->getFirstname(),
                            "last_name" => $billing->getLastname(),
                            "email" => $billing->getEmail(),
                            "phone" => $billing->getTelephone(),
                            "city" => $billing->getCity()
                        ]
                    ]
                ];

            $params = \Safe\json_encode($data);
            $url = "https://api.mobilpay.com/payment/";
            $xntpUser = 'navid';
            $xntpPassword = 'tamasba118';
            $this->_curl->setCredentials($xntpUser, $xntpPassword);
            $this->_curl->addHeader("Content-Type", "application/json");
            $this->_curl->addHeader("x-ntp-user", $xntpUser);
            $this->_curl->addHeader("x-ntp-password", $xntpPassword);
            $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->_curl->post($url, $params);
            $response = $this->_curl->getBody();
            //var_dump(\Safe\json_decode($response));
            $response = \Safe\json_decode($response);


        }catch (\Exception $exception) {
           //
        }
        return($response);
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function setLog($log) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $directory = $objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        file_put_contents($directory->getRoot().'/var/log/qrCodeLog.log', $log.' <<<>>> ', FILE_APPEND | LOCK_EX);
    }
}
