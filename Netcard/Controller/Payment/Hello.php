<?php
namespace Netopia\Netcard\Controller\Payment;
use Magento\Framework\App\Action\Action;


class Hello extends Action {
    protected $_context;
    protected $_pageFactory;
    protected $_jsonEncoder;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Json\EncoderInterface $encoder,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->_context = $context;
        $this->_pageFactory = $pageFactory;
        $this->_jsonEncoder = $encoder;
        parent::__construct($context);
    }

    public function execute()
    {
        $response = array('status'=>'success');
        $this->getResponse()->representJson($this->_jsonEncoder->encode($response));
        return;
    }
}
