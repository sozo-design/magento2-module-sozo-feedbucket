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
 */declare(strict_types=1);

namespace Sozo\Feedbucket\Service;

use Magento\Customer\Model\SessionFactory;
use Sozo\Feedbucket\Model\ConfigProvider;

/**
 * Decides, per visitor, whether the storefront widget loads and with which settings
 *
 * Used by the uncached feedbucket/widget/config endpoint, so the IP whitelist and the logged-in
 * customer's details never end up in a full-page-cached page.
 */
class StorefrontWidget
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var IpCheckerService
     */
    private $ipChecker;

    /**
     * @var ReporterToken
     */
    private $reporterToken;

    /**
     * @var SessionFactory
     */
    private $customerSessionFactory;

    /**
     * @var PageFilter
     */
    private $pageFilter;

    /**
     * @param ConfigProvider $configProvider
     * @param IpCheckerService $ipChecker
     * @param ReporterToken $reporterToken
     * @param SessionFactory $customerSessionFactory
     * @param PageFilter $pageFilter
     */
    public function __construct(
        ConfigProvider $configProvider,
        IpCheckerService $ipChecker,
        ReporterToken $reporterToken,
        SessionFactory $customerSessionFactory,
        PageFilter $pageFilter
    ) {
        $this->configProvider = $configProvider;
        $this->ipChecker = $ipChecker;
        $this->reporterToken = $reporterToken;
        $this->customerSessionFactory = $customerSessionFactory;
        $this->pageFilter = $pageFilter;
    }

    /**
     * Is the widget kept off this page (checkout, payment and account pages, plus the "Also Hide on Pages" list)
     *
     * Depends only on the page and store settings, so it is safe in the full page cache.
     *
     * @param string $fullActionName
     * @return bool
     */
    public function isHiddenOn(string $fullActionName): bool
    {
        return $this->pageFilter->isHidden($fullActionName, $this->getHiddenPageRules());
    }

    /**
     * Rules for the pages the storefront widget is kept off
     *
     * @return string[]
     */
    private function getHiddenPageRules(): array
    {
        $rules = $this->pageFilter->parseList($this->configProvider->getFrontendHiddenPages());
        if ($this->configProvider->isFrontendHiddenOnCheckout()) {
            $rules = array_merge(PageFilter::CHECKOUT_PAGES, $rules);
        }
        if ($this->configProvider->isFrontendHiddenOnAccount()) {
            $rules = array_merge(PageFilter::ACCOUNT_PAGES, $rules);
        }

        return $rules;
    }

    /**
     * Is the storefront widget switched on for the current store view (cache-safe: settings only)
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->configProvider->isEnabled()
            && $this->configProvider->isFrontendEnabled()
            && $this->configProvider->getSecret() !== '';
    }

    /**
     * Does the widget need a per-visitor decision (IP whitelist or customer details)
     *
     * @return bool
     */
    public function needsVisitorCheck(): bool
    {
        return $this->configProvider->getWhitelistEnabled()
            || $this->configProvider->isFrontendCaptureUserData();
    }

    /**
     * Is "Capture User Data" on (the answer then depends on who is logged in)
     *
     * @return bool
     */
    public function needsReporter(): bool
    {
        return $this->configProvider->isFrontendCaptureUserData();
    }

    /**
     * The widget key for the current store view
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->configProvider->getSecret();
    }

    /**
     * The endpoint response for the current visitor
     *
     * @return array{allowed: bool, key?: string, config?: array, reporter?: bool}
     */
    public function forCurrentVisitor(): array
    {
        if (!$this->isActive() || !$this->ipChecker->checkAllowed()) {
            return ['allowed' => false];
        }

        $response = [
            'allowed' => true,
            'key' => $this->configProvider->getSecret(),
            'config' => [],
        ];

        $reporter = $this->getReporter();
        if ($reporter !== null) {
            $response['config']['reporter'] = $reporter;
            $response['reporter'] = true;
        }

        return $response;
    }

    /**
     * Logged-in customer's details, when "Capture User Data" is on
     *
     * @return array|null
     */
    private function getReporter(): ?array
    {
        if (!$this->configProvider->isFrontendCaptureUserData()) {
            return null;
        }

        $session = $this->customerSessionFactory->create();
        if (!$session->isLoggedIn()) {
            return null;
        }

        $customer = $session->getCustomer();
        $reporter = [
            'name' => (string)$customer->getName(),
            'email' => (string)$customer->getEmail(),
        ];

        $token = $this->reporterToken->forUser(ReporterToken::TYPE_CUSTOMER, $customer->getId());
        if ($token !== '') {
            $reporter['token'] = $token;
        }

        return $reporter;
    }
}
