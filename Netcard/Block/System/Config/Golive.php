<?php
namespace Netopia\Netcard\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Golive extends Field
{
	protected $_template = 'Netopia_Netcard::system/config/button.phtml';

	public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getCustomUrl()
    {
        return $this->getUrl('MyRouter/controller/action');
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('netopia/tools/xml');
    }

    public function getButtonHtml()
    {
        $button = $this->getLayout()->createBlock(
            'Magento\Backend\Block\Widget\Button'
        )->setData(
            [
                'id' => 'golive_button',
                'label' => __('Send request to Live'),
            ]
        );
        return $button->toHtml();
    }

}