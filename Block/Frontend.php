<?php
/*
 * SOZO Design Ltd
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category    SOZO Design Ltd
 * @package     Sozo_Feedbucket
 * @copyright   Copyright (c) SOZO Design Ltd (https://sozodesign.co.uk)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Sozo\Feedbucket\Block;

use Sozo\Feedbucket\Model\ConfigProvider;
use Sozo\Feedbucket\Service\IpCheckerService;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;

class Frontend extends Display
{
    /**
     * @var SessionFactory
     */
    private $customerSession;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param IpCheckerService $ipCheckerService
     * @param State $state
     * @param SessionFactory $customerSession
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        IpCheckerService $ipCheckerService,
        State $state,
        SessionFactory $customerSession,
        array $data = []
    ) {
        parent::__construct($context, $configProvider, $ipCheckerService, $state, $data);
        $this->customerSession = $customerSession;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if ($this->configProvider->isFrontendEnabled()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * @inheritdoc
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function prepareConfig(): array
    {
        $config = parent::prepareConfig();
        $customerSession = $this->customerSession->create();
        if ($customerSession->isLoggedIn() && $this->configProvider->isFrontendCaptureUserData()) {
            $customer = $customerSession->getCustomer();
            $config['reporter'] = [
                'name' => $customer->getId(),
                'email' => $customer->getEmail()
            ];

            $config['reporter']['token'] = $this->configProvider->getUserHash($config['reporter']);
        }

        return $this->jsConfig = array_merge($this->jsConfig, $config);
    }
}
