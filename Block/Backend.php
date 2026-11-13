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
use Sozo\Feedbucket\Service\PageFilter;
use Sozo\Feedbucket\Service\ReporterToken;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Element\Template\Context;

class Backend extends Display
{
    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var PageFilter
     */
    private $pageFilter;

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param IpCheckerService $ipCheckerService
     * @param ReporterToken $reporterToken
     * @param Session $authSession
     * @param PageFilter $pageFilter
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        IpCheckerService $ipCheckerService,
        ReporterToken $reporterToken,
        Session $authSession,
        PageFilter $pageFilter,
        array $data = []
    ) {
        parent::__construct($context, $configProvider, $ipCheckerService, $reporterToken, $data);
        $this->authSession = $authSession;
        $this->pageFilter = $pageFilter;
    }

    /**
     * Return current admin user information
     *
     * @return \Magento\User\Model\User|null
     */
    protected function getCurrentUser(): ?\Magento\User\Model\User
    {
        return $this->authSession->getUser();
    }

    /**
     * @inheritdoc
     *
     * @return array|array[]
     */
    public function prepareConfig(): array
    {
        $config = parent::prepareConfig();
        if ($this->getCurrentUser() && $this->configProvider->isBackendCaptureUserData()) {
            $config['reporter'] = [
                'name' => $this->getCurrentUser()->getName(),
                'email' => $this->getCurrentUser()->getEmail(),
            ];

            $token = $this->reporterToken->forUser(ReporterToken::TYPE_ADMIN, $this->getCurrentUser()->getId());
            if ($token !== '') {
                $config['reporter']['token'] = $token;
            }
        }

        return $this->jsConfig = array_merge($this->jsConfig, $config);
    }

    /**
     * @inheritdoc
     */
    protected function canShow(): bool
    {
        return $this->configProvider->isBackendEnabled()
            && !$this->isHiddenPage()
            && parent::canShow();
    }

    /**
     * Is the widget kept off this admin page (personal data pages, plus the "Also Hide on Pages" list)
     *
     * @return bool
     */
    private function isHiddenPage(): bool
    {
        $rules = $this->pageFilter->parseList($this->configProvider->getBackendHiddenPages());
        if ($this->configProvider->isBackendHiddenOnPersonalData()) {
            $rules = array_merge(PageFilter::ADMIN_PERSONAL_DATA_PAGES, $rules);
        }

        return $this->pageFilter->isHidden((string)$this->getRequest()->getFullActionName(), $rules);
    }
}
