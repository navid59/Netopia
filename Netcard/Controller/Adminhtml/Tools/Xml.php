<?php
namespace Netopia\Netcard\Controller\Adminhtml\Tools;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Xml extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $_scopeConfig;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param ScopeConfigInterface $scopeConfig
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $ntpConfigData = array (
                      'declare_complete_description' => $this->getConfigData('conditions/complete_description'),
                      'declare_price_currency' => $this->getConfigData('conditions/price_currency'),
                      'declare_contact_info' => $this->getConfigData('conditions/contact_info'),
                      'declare_mandatory_pages' => $this->getConfigData('conditions/mandatory_pages'),
                      'declare_forbidden_business' => $this->getConfigData('forbidden_business'),
                      'terms_conditions_url' => $this->getConfigData('terms_conditions_url'),
                      'privacy_policy_url' => $this->getConfigData('privacy_policy_url'),
                      'delivery_policy_url' => $this->getConfigData('delivery_policy_url'),
                      'return_cancel_policy_url' => $this->getConfigData('return_cancel_policy_url'),
                      'gdpr_policy_url' => $this->getConfigData('gdpr_policy_url'),
                      'visa_logo_link' => $this->getConfigData('visa_logo_link'),
                      'master_logo_linkl' => $this->getConfigData('master_logo_linkl'),
                      'netopia_logo_link' => $this->getConfigData('netopia_logo_link'),
                      'has_ssl' => $this->has_ssl()
                    );
        
        if($this->goliveValidationXml($ntpConfigData)) {
          $xmlResponse = array(
            'status' =>  true,
            'msg' => 'Your Request is sent succefuly' );
        } else {
          $xmlResponse = array(
            'status' =>  false,
            'msg' => 'Your Request is Failed' );
        }
        /*
        * Send response to JS
        */
        echo (json_encode($xmlResponse));
           
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function has_ssl() {
        $domain = "https://netopia-payments.com";
        // $domain = $_SERVER['HTTP_HOST'];
        $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
        $read = fopen($domain, "rb", false, $stream);
        $cont = stream_context_get_params($read);
        $var = ($cont["options"]["ssl"]["peer_certificate"]);
        $result = (!is_null($var)) ? true : false;
        return $result;
    }

    function goliveValidationXml($configData) {
    
    $domtree = new \DOMDocument('1.0', 'UTF-8');
    $domtree->formatOutput = true;
    $xmlRoot = $domtree->createElement("xml");
    $xmlRoot = $domtree->appendChild($xmlRoot);

    $sac_key = $domtree->createElement("sac_key", $this->getConfigData('auth/signature'));
    $sac_key = $xmlRoot->appendChild($sac_key);
    $agr = $domtree->createElement("agrremnts");
    $agr = $xmlRoot->appendChild($agr);

    foreach ($configData as $key => $value) {
        $agr->appendChild($domtree->createElement($key, $value ));
    }

    $last_update = $domtree->createElement("last_update", date("Y/m/d H:i:s"));
    $last_update = $xmlRoot->appendChild($last_update);

    $last_update = $domtree->createElement("platform", 'Magento 2');
    $last_update = $xmlRoot->appendChild($last_update);

    $this->agreementExist();
    $result = $domtree->save($this->_getUploadDir().$this->getConfigData('auth/signature').'_agreements.xml') ? true : false;
    return $result;
    }

    protected function _getUploadDir()
    {
        $xmlDir = getcwd().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'Netopia'.DIRECTORY_SEPARATOR.'Netcard'.DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR.'certificates'.DIRECTORY_SEPARATOR;
        return $xmlDir;
    }

    public function agreementExist(){
      $agreemnetFile = $this->_getUploadDir().$this->getConfigData('auth/signature').'_agreements.xml';
      if (file_exists($agreemnetFile)) {
         unlink($agreemnetFile);
      }
    }
}
