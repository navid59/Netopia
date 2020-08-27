<div align="center"><a href="https://netopia-payments.com/"><img alt="Parsedown" src="https://suport.mobilpay.ro/np-logo-blue.svg" width="240" /></a></div>

# Netopia Payment module for Magento 2.4
## Options
* Card payment
* mobilPay WALLET payment

## Installation
The Module placed in folder "Netopia"
1. put this code inside of <your_magento_root>/app/code/
2. SSH to your Magento proiect and ru the following command
* <code>php bin/magento setup:upgrade</code>
* <code>php bin/magento setup:static-content:deploy</code>
* <code>php bin/magento ca:cl</code>

## verification
By run the following command you can make sure, if this module is installed successfully on your Magento Proiect
* <code>php bin/magento module:status</code>

## After installation
Recommended to firstly, go to Admin panel & fill the necessary data
<code><your_magento_admin>->Stores->Configuration->Sales->Payment Methods->Netopia Payment</code>