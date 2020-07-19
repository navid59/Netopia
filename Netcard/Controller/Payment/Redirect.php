<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;

class Redirect extends Action {
    /**
     * @var JsonFactory
     */

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory)
    {
        $this->resultFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultFactory->create();
        $data = ['test' => 'Thiis is test of Redirect in Json'];
        return $result->setData($data);
    }
}
