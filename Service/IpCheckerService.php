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

namespace Sozo\Feedbucket\Service;

use Sozo\Feedbucket\Model\ConfigProvider;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class IpCheckerService
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var IpMatcher
     */
    private $ipMatcher;

    /**
     * @param ConfigProvider $configProvider
     * @param RemoteAddress $remoteAddress
     * @param IpMatcher $ipMatcher
     */
    public function __construct(ConfigProvider $configProvider, RemoteAddress $remoteAddress, IpMatcher $ipMatcher)
    {
        $this->configProvider = $configProvider;
        $this->remoteAddress = $remoteAddress;
        $this->ipMatcher = $ipMatcher;
    }

    /**
     * Check if the whitelist is used and if so the current IP is allowed
     *
     * @return bool
     */
    public function checkAllowed(): bool
    {
        if (!$this->configProvider->getWhitelistEnabled()) {
            return true;
        }

        $ipAddress = (string)$this->remoteAddress->getRemoteAddress();
        $entries = $this->ipMatcher->parseList($this->configProvider->getWhitelist());

        return $this->ipMatcher->isAllowed($ipAddress, $entries);
    }
}
