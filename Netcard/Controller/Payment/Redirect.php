<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;

class Redirect extends Action {
    /**
     * @var JsonFactory
     */

//    public function __construct(
//        Context $context,
//        JsonFactory $resultJsonFactory)
//    {
//        $this->resultFactory = $resultJsonFactory;
//        parent::__construct($context);
//    }

    public function execute()
    {
        /** @var Json $jsonResult */
        $jsonResult = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $jsonResult->setData([
            'message' => 'Thiis is test of Redirect in Json'
        ]);
        return $jsonResult;
    }
}
