<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Netopia\Netcard\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'net_card';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'logo' => [
                        'alt' => __('Short Alt text'),
                        'src' => 'https://netopia-payments.com/core/assets/5993428bab/images/logo.png'
                    ],
                    'method' => [
                        'card' => __('Card'),
                        'crypto' => __('Crypto')
                    ],
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
