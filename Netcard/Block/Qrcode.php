<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Block;
use Magento\Framework\View\Element\Template;

class Qrcode extends Template {
    protected $_qrCodeImage;

    public function _construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $date = []
    )
    {
        parent::_construct(); // TODO: Change the autogenerated stub
    }

    public function getQrCode () {
        return "https://secure.mobilpay.ro/default/index/qr/id/1112193/type/3";
    }
}
