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
use Netopia\Netcard\Mobilpay\Payment\Request\Mobilpay_Payment_Request_Abstract;
use Netopia\Netcard\Mobilpay\Payment\Request\Mobilpay_Payment_Request_Card;
use Netopia\Netcard\Mobilpay\Payment\Mobilpay_Payment_Invoice;
use Netopia\Netcard\Mobilpay\Payment\Mobilpay_Payment_Address;

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

    public function __construct(Context $context,
                                Session $session,
                                ResourceConnection $resource,
                                Order $orderFactory,
                                array $data)
    {
        $this->_resource = $resource;
        $this->_checkoutSession = $session;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $connection = $this->_resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');
        $quoteId = $this->getRequest()->getParam('quote');

        /** @var \Magento\Framework\App\ObjectManager $ */
        $obm = \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Framework\App\Http\Context $context */
        $context = $obm->get('Magento\Framework\App\Http\Context');

        /** @var bool $isLoggedIn */
        $isLoggedIn = $context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id='.$connection->quote($quoteId));
        }
        else {
            $orderId = $connection->fetchAll('SELECT `'.$tblSalesOrder.'`.entity_id FROM `'.$tblSalesOrder.'` INNER JOIN `'.$tblQuoteIdMask.'` ON `'.$tblSalesOrder.'`.quote_id=`'.$tblQuoteIdMask.'`.quote_id AND `'.$tblQuoteIdMask.'`.masked_id='.$connection->quote($quoteId));
        }
        return $this->_orderFactory->load($orderId);
    }


    public function getFormData()
    {
        $e = null;
        $shipping = $this->getOrder()->getShippingAddress();
        $billing = $this->getOrder()->getBillingAddress();
        $order = $this->getOrder();
        $result = [];
        try {
            $objPmReqCard = new Mobilpay_Payment_Request_Card();
            $objPmReqCard->signature = $this->getConfigData('auth/signature');

            $objPmReqCard->orderId = $this->getOrder()->getId();
            $objPmReqCard->returnUrl = $this->getUrl('netcard/success');

            $objPmReqCard->invoice = new Mobilpay_Payment_Invoice();

            $objPmReqCard->invoice->currency = $order->getBaseCurrencyCode();
            $objPmReqCard->invoice->amount = $order->getBaseGrandTotal();
            $cart_description = $this->getConfigData('api/description');
            if ($cart_description != '') $objPmReqCard->invoice->details = $cart_description;

            $billingAddress = new Mobilpay_Payment_Address();

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
            $objPmReqCard->encrypt($this->getConfigData('auth/public_key'));
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
}
