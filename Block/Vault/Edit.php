<?php

namespace Swarming\SubscribePro\Block\Vault;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Framework\Exception\LocalizedException;

class Edit extends \Magento\Directory\Block\Data
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Vault\Api\PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileService
     */
    protected $sdkPaymentProfileService;

    /**
     * @var \Magento\Vault\Api\Data\PaymentTokenInterface
     */
    protected $token;

    /**
     * @var \SubscribePro\Service\PaymentProfile\PaymentProfileInterface
     */
    protected $profile;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement
     * @param \Swarming\SubscribePro\Platform\Platform $platform
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Vault\Api\PaymentTokenManagementInterface $paymentTokenManagement,
        \Swarming\SubscribePro\Platform\Platform $platform,
        array $data = []
    ) {
        $this->session = $session;
        $this->messageManager = $messageManager;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->sdkPaymentProfileService = $platform->getSdk()->getPaymentProfileService();
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $data
        );
    }

    protected function _prepareLayout()
    {
        $publicHash = $this->getRequest()->getParam(PaymentTokenInterface::PUBLIC_HASH);
        if ($publicHash) {
            $this->loadVault($publicHash);
        }

        $this->initPageTitle();

        return parent::_prepareLayout();
    }

    /**
     * @param string $publicHash
     */
    protected function loadVault($publicHash)
    {
        try {
            $this->loadToken($publicHash);
            $this->loadProfile();
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }

    /**
     * @param string $publicHash
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function loadToken($publicHash)
    {
        $token = $this->paymentTokenManagement->getByPublicHash($publicHash, $this->session->getCustomerId());
        if (!$token) {
            throw new LocalizedException(__('The saved credit is not found.'));
        }
        $this->token = $token;
    }

    protected function loadProfile()
    {
        $profile = $this->sdkPaymentProfileService->loadProfile($this->token->getGatewayToken());
        if (!$profile) {
            throw new LocalizedException(__('The saved credit is not found.'));
        }
        $this->profile = $profile;
    }

    protected function initPageTitle()
    {
        /** @var \Magento\Theme\Block\Html\Title $pageMainTitle */
        $pageMainTitle = $this->getLayout()->getBlock('page.main.title');
        if ($pageMainTitle) {
            $message = $this->token ? __('Edit Card: ending %1', $this->getNumberLast4Digits()) : __('Add New Card');
            $pageMainTitle->setPageTitle($message);
        }
    }

    /**
     * @return string
     */
    protected function getNumberLast4Digits()
    {
        $tokenDetails = json_decode($this->token->getTokenDetails() ?: '{}', true);
        return $tokenDetails['maskedCC'];
    }

    /**
     * @return string
     */
    public function renderBillingSection()
    {
        /** @var \Swarming\SubscribePro\Block\Vault\Edit\Billing $billingBlock */
        $billingBlock = $this->getChildBlock('billing');
        return $billingBlock ? $billingBlock->render($this->profile) : '';
    }

    /**
     * @return string
     */
    public function renderCardSection()
    {
        if ($this->token) {
            /** @var \Swarming\SubscribePro\Block\Vault\Edit\EditCard $cardEditBlock */
            $cardEditBlock = $this->getChildBlock('card_edit');
            $cardSectionHtml = $cardEditBlock ? $cardEditBlock->render($this->token) : '';
        } else {
            /** @var \Swarming\SubscribePro\Block\Vault\Edit\CreateCard $cardCreateBlock */
            $cardCreateBlock = $this->getChildBlock('card_create');
            $cardSectionHtml = $cardCreateBlock ? $cardCreateBlock->toHtml() : '';
        }
        return $cardSectionHtml;
    }

    /**
     * @return string
     */
    public function getSaveUrl()
    {
        $params = ['_secure' => true];
        if ($this->token) {
            $params[PaymentTokenInterface::PUBLIC_HASH] = $this->token->getPublicHash();
        }
        return $this->getUrl('swarming_subscribepro/cards/save', $params);
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('vault/cards/listaction');
    }
}
