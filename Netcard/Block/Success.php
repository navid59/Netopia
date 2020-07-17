<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order;

/**
 * Class Success
 * To handel Success or Failed Payment
 * @package Netopia\Netcard\Block
 */
class Success extends \Magento\Framework\View\Element\Template
{
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
//        die('Success / Failed test');exit;
    }
}
