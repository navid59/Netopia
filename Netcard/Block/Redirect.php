<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Block;
use Magento\Framework\View\Element\Template;
use Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentInvoice;
use Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestCard;
use \Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentAddress;
use Magento\Framework\Module\Dir;

/**
 * Class Redirect
 * To handel Redirect from Magento to Sandbox
 * @package Netopia\Netcard\Block
 */
class Redirect extends Template
{
    protected $_storeManager;
    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_orderFactory;
    protected $_resource;
    protected $_moduleReader;
    Protected $quoteFactory;

    /**
     * @var \Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestCard
     */
    Protected $mobilpayPaymentRequestCard;
    /**
     * @var \Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentInvoice
     */
    Protected $mobilpayPaymentInvoice;
    /**
     * @var \Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentAddress
     */
    Protected $mobilpayPaymentAddress;

    /**
     * Redirect constructor.
     *
     * @param Template\Context $context
     * @param \Magento\Checkout\Model\Session $session
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Sales\Model\Order $orderFactory
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Framework\Module\Dir\Reader $reader
     * @param array $data
     */
    public function __construct(
                                \Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Checkout\Model\Session $session,
                                \Magento\Framework\App\ResourceConnection $resource,
                                \Magento\Sales\Model\Order $orderFactory,
                                \Magento\Quote\Model\QuoteFactory $quoteFactory,
                                \Magento\Framework\Module\Dir\Reader $reader,
                                array $data)
    {
        $this->_resource = $resource;
        $this->_checkoutSession = $session;
        $this->_orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->_moduleReader = $reader;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');
        $quoteId = $this->getRequest()->getParam('quote');

        /** @var \Magento\Framework\App\ObjectManager $ */
        $obm = \Magento\Framework\App\ObjectManager::getInstance();

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


    public function getFormData()
    {
        $e = null;
        $moduleDirectory = $this->_moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Netopia_Netcard');
        $shipping = $this->getOrder()->getShippingAddress();
        $billing = $this->getOrder()->getBillingAddress();
        $order = $this->getOrder();
        echo $order->getId();
        //die('jahsdkjsa');
        //var_dump($order);
        $result = [];

        try {
            $objPmReqCard = new MobilpayPaymentRequestCard();
            $objPmReqCard->signature = $this->getConfigData('auth/signature');

            // Get Public Key filename
            $x509FilePath = $moduleDirectory . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR . "sandbox." . $objPmReqCard->signature . ".public.cer";

            $objPmReqCard->orderId = $this->getOrder()->getId();
//            echo ($objPmReqCard->orderId);
//            die('www');
            $objPmReqCard->returnUrl = $this->getUrl('netopia/payment/success');
            $objPmReqCard->confirmUrl = $this->getUrl('netopia/payment/ipn');
//            $objPmReqCard->cancelUrl = $this->getUrl('netopia/magenpayment/cancel');

            $this->setLog($objPmReqCard->returnUrl);
            $this->setLog($objPmReqCard->confirmUrl);
            
            

            // Add invoice info to Obj
            $objPmReqCard->invoice = new MobilpayPaymentInvoice();

            $objPmReqCard->invoice->currency = $order->getBaseCurrencyCode();
            $objPmReqCard->invoice->amount = $order->getBaseGrandTotal();

            $cart_description = $this->getConfigData('api/description');
            if ($cart_description != '') {
                $objPmReqCard->invoice->details = $cart_description;
            } else {
                $objPmReqCard->invoice->details = "Netopia - Magento 2 - Default description";
            }

            // Add billing address info to Obj
            $billingAddress = new MobilpayPaymentAddress();
            $company = $billing->getCompany();
            if (!empty($company)) {
                $billingAddress->type = 'company';
            } else {
                $billingAddress->type = 'person';
            }
            $billingAddress->firstName = $billing->getFirstname();
            $billingAddress->lastName = $billing->getLastname();
            $billingAddress->country = $billing->getCountryId();

            $billingAddress->city = $billing->getCity();
            $billingAddress->zipCode = $billing->getPostcode();
            $billingAddress->state = $billing->getRegion();
            $billingAddress->address = implode(', ', $billing->getStreet());
            $billingAddress->email = $billing->getEmail();
            $billingAddress->mobilePhone = $billing->getTelephone();

            $objPmReqCard->invoice->setBillingAddress($billingAddress);

            $objPmReqCard->encrypt($x509FilePath);
        } catch (\Exception $e) {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        if (!($e instanceof \Exception)) {
            $result['status'] = 1;
            $result['data'] = $objPmReqCard->getEncData();
            $result['form_key'] = $objPmReqCard->getEnvKey();
            $result['billing'] = $billing->getData();

        } else {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function setLog($log) {
        file_put_contents('/var/www/html/var/log/requestLog.log', $log.' <<<>>> ', FILE_APPEND | LOCK_EX);
    }
}
