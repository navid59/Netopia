<?php
namespace Netopia\Netcard\Controller\Adminhtml\Tools;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Jsonrequest extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $_scopeConfig;
    
    protected $jsonData;
    protected $encData;

    private $outEnvKey  = null;
    private $outEncData = null;

    const ERROR_LOAD_X509_CERTIFICATE = 0x10000001;
    const ERROR_ENCRYPT_DATA          = 0x10000002;

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
        $ntpDeclare = array (
                      'completeDescription' => $this->getConfigData('conditions/complete_description'),
                      'priceCurrency' => $this->getConfigData('conditions/price_currency'),
                      'contactInfo' => $this->getConfigData('conditions/contact_info'),
                      'forbiddenBusiness' => $this->getConfigData('forbidden_business')
                    );

        $ntpUrl = array(
                  'termsAndConditions' => $this->getConfigData('terms_conditions_url'),
                  'privacyPolicy' => $this->getConfigData('privacy_policy_url'),
                  'deliveryPolicy' => $this->getConfigData('delivery_policy_url'),
                  'returnAndCancelPolicy' => $this->getConfigData('return_cancel_policy_url'),
                  'gdprPolicy' => $this->getConfigData('gdpr_policy_url')
                  );

        $ntpImg = array(
                  'visaLogoLink' => $this->getConfigData('visa_logo_link'),
                  'masterLogoLink' => $this->getConfigData('master_logo_linkl'),
                  'netopiaLogoLink' => $this->getConfigData('netopia_logo_link')
                );
        
        $this->jsonData = $this->makeActivateJson($ntpDeclare, $ntpUrl, $ntpImg);
        $this->encrypt();
        
        $this->encData = array(
          'env_key' => $this->getEnvKey(),
          'data'    => $this->getEncData()
          );
        
        $result = json_decode($this->sendJsonCurl());
        
        if($result->code == 200) {
          $response = array(
            'status' =>  true,
            'msg' => 'succesfully sent your request' );
        } else {
          $response = array(
            'status' =>  false,
            'msg' => 'Error, '.$result->message );
        }
        /*
        * Send response to JS
        */
        echo (json_encode($response));
           
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


    protected function _getUploadDir()
    {
        $certificateDir = getcwd().DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'code'.DIRECTORY_SEPARATOR.'Netopia'.DIRECTORY_SEPARATOR.'Netcard'.DIRECTORY_SEPARATOR.'etc'.DIRECTORY_SEPARATOR.'certificates'.DIRECTORY_SEPARATOR;
        return $certificateDir;
    }

    // public function agreementExist(){
    //   $agreemnetFile = $this->_getUploadDir().$this->getConfigData('auth/signature').'_agreements.xml';
    //   if (file_exists($agreemnetFile)) {
    //      unlink($agreemnetFile);
    //   }
    // }

    function makeActivateJson($declareatins, $urls, $images) {
      $jsonData = array(
        "sac_key" => $this->getConfigData('auth/signature'),
        "agrremnts" => array(
              "declare" => $declareatins,
              "urls"    => $urls,
              "images"  => $images,
              "ssl"     => $this->has_ssl()
            ),
        "lastUpdate" => date("Y/m/d H:i:s"),
        "platform" => 'Magento 2'
      );
      
      $post_data = json_encode($jsonData, JSON_FORCE_OBJECT);
      return $post_data;
    }

    
    public function encrypt()
      {
        $x509FilePath = $this->_getUploadDir().$this->getConfigData('mode/live_public_key');
        $publicKey = openssl_pkey_get_public("file://{$x509FilePath}");
        if($publicKey === false)
          {
            $this->outEncData = null;
            $this->outEnvKey  = null;
            $errorMessage = "Error while loading X509 public key certificate! Reason:";
            while(($errorString = openssl_error_string()))
            {
              $errorMessage .= $errorString . "\n";
            }
            throw new \Exception($errorMessage, self::ERROR_LOAD_X509_CERTIFICATE);
          }
        $srcData = $this->jsonData;
        $publicKeys = array($publicKey);
        $encData  = null;
        $envKeys  = null;
        $result   = openssl_seal($srcData, $encData, $envKeys, $publicKeys);
        if($result === false)
          {
            $this->outEncData = null;
            $this->outEnvKey  = null;
            $errorMessage = "Error while encrypting data! Reason:";
            while(($errorString = openssl_error_string()))
            {
              $errorMessage .= $errorString . "\n";
            }
            throw new Exception($errorMessage, self::ERROR_ENCRYPT_DATA);
          }
        $this->outEncData = base64_encode($encData);
        $this->outEnvKey  = base64_encode($envKeys[0]);  
      }

      public function getEnvKey()
        {
          return $this->outEnvKey;
        }

      public function getEncData()
        {
          return $this->outEncData;
        }

      public function sendJsonCurl() {
        $url = 'https://netopia-payments-user-service-api-fqvtst6pfa-ez.a.run.app/user/verify';
        $ch = curl_init($url);

        $payload = json_encode($this->encData);

        // Attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

        // Return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute the POST request
        $result = curl_exec($ch);
        
        // Close cURL resource
        curl_close($ch);
        
        return $result;
      }
}
