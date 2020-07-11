## Synopsis
An extension to add integration with Payment Gateway for Mobilepay.

## Motivation
Chalanging test

## Technical feature

### Module configuration
1. Package details [composer.json](composer.json).
2. Module configuration details (sequence) in [module.xml](etc/module.xml).
3. Module configuration available through Stores->Configuration [system.xml](etc/adminhtml/system.xml)

Payment gateway module depends on `Sales`, `Payment` and `Checkout` Magento modules.
For more module configuration details, please look through [module development docs](http://devdocs.magento.com/guides/v2.0/extension-dev-guide/module-load-order.html).

### Gateway configuration
On the next step, we might specify gateway domain configuration in [config.xml](etc/config.xml).

##### Let's look into configuration attributes:
 * <code>debug</code> enables debug mode by default, e.g log for request/response
 * <code>active</code> is payment active by default
 * <code>model</code> `Payment Method Facade` used for integration with `Sales` and `Checkout` modules
 * <code>merchant_gateway_key</code> encrypted merchant credential
 * <code>order_status</code> default order status
 * <code>payment_action</code> default action of payment
 * <code>title</code> default title for a payment method
 * <code>currency</code> supported currency
 * <code>can_authorize</code> whether payment method supports authorization
 * <code>can_capture</code> whether payment method supports capture
 * <code>can_void</code> whether payment method supports void
 * <code>can_use_checkout</code> checkout availability
 * <code>is_gateway</code> is an integration with gateway
 * <code>sort_order</code> payment method order position on checkout/system configuration pages
 * <code>debugReplaceKeys</code> request/response fields, which will be masked in log
 * <code>paymentInfoKeys</code> transaction request/response fields displayed on payment information block
 * <code>privateInfoKeys</code> paymentInfoKeys fields which should not be displayed in customer payment information block

### Dependency Injection configuration


#### Pools

##### Value Handlers
There should be at least one Value Handler with `default` key provided for ValueHandlerPool.

```xml
!-- Value handlers infrastructure -->
<virtualType name="NetcardValueHandlerPool" type="Magento\Payment\Gateway\Config\ValueHandlerPool">
    <arguments>
        <argument name="handlers" xsi:type="array">
            <item name="default" xsi:type="string">NetcardConfigValueHandler</item>
        </argument>
    </arguments>
</virtualType>
<virtualType name="NetcardConfigValueHandler" type="Magento\Payment\Gateway\Config\ConfigValueHandler">
    <arguments>
        <argument name="configInterface" xsi:type="object">NetcardConfig</argument>
    </arguments>
</virtualType>
```

##### Commands


## License
[Open Source License](LICENSE.txt)
