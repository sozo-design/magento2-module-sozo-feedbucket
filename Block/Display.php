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
use Sozo\Feedbucket\Service\ReporterToken;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Display extends Template
{
    /**
     * Encode values so they are safe inside an inline script (no </script>, quotes or &)
     */
    protected const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var IpCheckerService
     */
    protected $ipChecker;
    /**
     * @var ReporterToken
     */
    protected $reporterToken;

    /**
     * @var array
     */
    protected $jsConfig = [];

    /**
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param IpCheckerService $ipCheckerService
     * @param ReporterToken $reporterToken
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        IpCheckerService $ipCheckerService,
        ReporterToken $reporterToken,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->ipChecker = $ipCheckerService;
        $this->reporterToken = $reporterToken;

        parent::__construct($context, $data);
    }

    /**
     * Get the Widget ID
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->configProvider->getSecret();
    }

    /**
     * Prepare the api configuration
     *
     * @return array[]
     */
    public function prepareConfig(): array
    {
        return $this->jsConfig = [];
    }

    /**
     * Return the JSON encoded api configuration
     *
     * @return string
     */
    public function getJsConfig(): string
    {
        $this->prepareConfig();
        return (string)json_encode($this->jsConfig, self::JSON_FLAGS | JSON_FORCE_OBJECT);
    }

    /**
     * Return the widget key as a JavaScript string literal
     *
     * @return string
     */
    public function getJsonSecret(): string
    {
        return (string)json_encode($this->getSecret(), self::JSON_FLAGS);
    }

    /**
     * Generate the Feedbucket output
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->configProvider->isEnabled() || $this->getSecret() === '') {
            return '';
        }

        return $this->ipChecker->checkAllowed() ? parent::_toHtml() : '';
    }
}
