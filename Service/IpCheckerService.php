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
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ConfigProvider $configProvider
     * @param RemoteAddress $remoteAddress
     * @param IpMatcher $ipMatcher
     * @param LoggerInterface $logger Sozo_Feedbucket channel of the SOZO log (etc/di.xml)
     */
    public function __construct(
        ConfigProvider $configProvider,
        RemoteAddress $remoteAddress,
        IpMatcher $ipMatcher,
        LoggerInterface $logger
    ) {
        $this->configProvider = $configProvider;
        $this->remoteAddress = $remoteAddress;
        $this->ipMatcher = $ipMatcher;
        $this->logger = $logger;
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

        $allowed = $this->ipMatcher->isAllowed($ipAddress, $entries);

        // Debug lines are only written in developer mode. No IP addresses: they are personal data.
        $invalidLines = [];
        foreach ($entries as $index => $entry) {
            if (!$this->ipMatcher->isValidEntry($entry)) {
                $invalidLines[] = $index + 1;
            }
        }
        $this->logger->debug('IP whitelist checked', [
            'allowed' => $allowed,
            'entries' => count($entries),
            'invalid_entry_lines' => $invalidLines,
            'address_found' => $ipAddress !== '',
        ]);

        return $allowed;
    }
}
