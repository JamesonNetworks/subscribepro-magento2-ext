<?php

namespace Swarming\SubscribePro\Block\Customer;

use Magento\Customer\Model\Address\Config as AddressConfig;
use Swarming\SubscribePro\Model\Config\SubscriptionOptions;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Swarming\SubscribePro\Platform\Service\OauthToken
     */
    protected $oauthTokenService;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig
     */
    protected $priceConfigProvider;

    /**
     * @var \Magento\Payment\Model\CcConfigProvider
     */
    protected $ccConfigProvider;

    /**
     * @var \Swarming\SubscribePro\Gateway\Config\Config
     */
    protected $gatewayConfig;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;

    /**
     * @var \Swarming\SubscribePro\Ui\ConfigProvider\SubscriptionConfig
     */
    protected $subscriptionConfig;
    
    /**
     * @var \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes
     */
    protected $addressAttributes;

    /**
     * @var \Magento\Checkout\Block\Checkout\AttributeMerger
     */
    protected $attributeMerger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Swarming\SubscribePro\Platform\Service\OauthToken $oauthTokenService
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider
     * @param \Magento\Payment\Model\CcConfigProvider $ccConfigProvider
     * @param \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Swarming\SubscribePro\Ui\ConfigProvider\SubscriptionConfig $subscriptionConfig
     * @param \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes $addressAttributes
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $attributeMerger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Swarming\SubscribePro\Platform\Service\OauthToken $oauthTokenService,
        \Magento\Customer\Model\Session $customerSession,
        \Swarming\SubscribePro\Ui\ConfigProvider\PriceConfig $priceConfigProvider,
        \Magento\Payment\Model\CcConfigProvider $ccConfigProvider,
        \Swarming\SubscribePro\Gateway\Config\Config $gatewayConfig,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Swarming\SubscribePro\Ui\ConfigProvider\SubscriptionConfig $subscriptionConfig,
        \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes $addressAttributes,
        \Magento\Checkout\Block\Checkout\AttributeMerger $attributeMerger,
        \Swarming\SubscribePro\Gateway\Config\ConfigProvider $gatewayConfigProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerRepository = $customerRepository;
        $this->oauthTokenService = $oauthTokenService;
        $this->customerSession = $customerSession;
        $this->priceConfigProvider = $priceConfigProvider;
        $this->ccConfigProvider = $ccConfigProvider;
        $this->gatewayConfig = $gatewayConfig;
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->subscriptionConfig = $subscriptionConfig;
        $this->addressAttributes = $addressAttributes;
        $this->attributeMerger = $attributeMerger;
    }

    /**
     * @return array
     */
    public function getCustomerData()
    {
        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        $customerData = $customer->__toArray();
        foreach ($customer->getAddresses() as $key => $address) {
            $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
        }

        return $customerData;
    }

    public function getApiAccessToken()
    {
        return $this->oauthTokenService->retrieveToken('johnspar1+johnjohnjohn@gmail.com');
    }

    protected function _beforeToHtml()
    {
        $this->initJsLayout();
        return parent::_beforeToHtml();
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    protected function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(AddressConfig::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    protected function getSubscribeProConfig()
    {
        return $this->gatewayConfigProvider->getConfig();
    }

    protected function initJsLayout()
    {
        $data = [
            'components' => [
                'subscriptions-container' => [
                    'children' => [
                        'subscriptions' => [
                            'config' => [
                                'datepickerOptions' => [
                                    'minDate' => SubscriptionOptions::QTY_MIN_DAYS_TO_NEXT_ORDER,
                                    'showOn' => 'button',
                                    'buttonImage' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
                                    'buttonText' => __('Click to change date'),
                                    'buttonImageOnly' => true,
                                    'dateFormat' => 'yyyy-mm-dd',
                                ],
                                'subscriptionConfig' => $this->subscriptionConfig->getConfig(),
                                'priceConfig' => $this->priceConfigProvider->getConfig(),
                                'paymentConfig' => [
                                    'ccIcons' => $this->ccConfigProvider->getIcons(),
                                    'ccTypesMapper' => $this->gatewayConfig->getCcTypesMapper()
                                ],
                                'shippingAddressOptions' => [
                                    'dataScopePrefix' => 'shippingAddress',
                                    'deps' => 'spAddressProvider',
                                    'children' => [
                                        'shipping-address-fieldset' => [
                                            'children' => $this->attributeMerger->merge(
                                                $this->addressAttributes->getElements(),
                                                'spAddressProvider',
                                                'shippingAddress',
                                                [
                                                    'region' => [
                                                        'visible' => false,
                                                    ],
                                                    'region_id' => [
                                                        'component' => 'Magento_Ui/js/form/element/region',
                                                        'config' => [
                                                            'template' => 'ui/form/field',
                                                            'elementTmpl' => 'ui/form/element/select',
                                                            'customEntry' => 'shippingAddress.region',
                                                        ],
                                                        'validation' => [
                                                            'required-entry' => true,
                                                        ],
                                                        'filterBy' => [
                                                            'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                                            'field' => 'country_id',
                                                        ],
                                                    ],
                                                    'country_id' => [
                                                        'sortOrder' => 115,
                                                    ],
                                                    'postcode' => [
                                                        'component' => 'Magento_Ui/js/form/element/post-code',
                                                        'validation' => [
                                                            'required-entry' => true,
                                                        ],
                                                    ],
                                                    'company' => [
                                                        'validation' => [
                                                            'min_text_length' => 0,
                                                        ],
                                                    ],
                                                    'telephone' => [
                                                        'config' => [
                                                            'tooltip' => [
                                                                'description' => __('For delivery questions.'),
                                                            ],
                                                        ],
                                                    ],
                                                ]
                                            )
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $this->jsLayout = array_merge_recursive($this->jsLayout, $data);
    }
}
