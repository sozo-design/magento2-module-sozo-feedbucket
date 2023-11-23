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

namespace Sozo\Feedbucket\Model;

use Sozo\Core\Model\ConfigProviderAbstract;

class ConfigProvider extends ConfigProviderAbstract
{
    /**
     * xpath prefix of module (section)
     * @var string '{section}/'
     */
    protected string $pathPrefix = 'sozo_feedbucket/';

    /**
     * @var string
     */
    protected string $moduleCode = 'Sozo_Feedbucket';

    private const CFG_FEEDBUCKET_ENABLED = 'general/enabled';
    private const CFG_FEEDBUCKET_SECRET = 'general/secret';

    private const CFG_FEEDBUCKET_FRONTEND_ENABLED = 'frontend/enabled';
    private const CFG_FEEDBUCKET_FRONTEND_CAPTURE_USER_DATA = 'frontend/capture_user_data';

    private const CFG_FEEDBUCKET_BACKEND_ENABLED = 'backend/enabled';
    private const CFG_FEEDBUCKET_BACKEND_CAPTURE_USER_DATA = 'backend/capture_user_data';

    private const CFG_FEEDBUCKET_WHITELIST_ENABLED = 'ip_whitelist/enabled';
    private const CFG_FEEDBUCKET_WHITELIST_WHITELIST = 'ip_whitelist/whitelist';

    /**
     * Is the module enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_ENABLED, $storeId);
    }

    /**
     * Retrieve the Global API Key
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSecret(int $storeId = null): string
    {
        return $this->getValue(self::CFG_FEEDBUCKET_SECRET, $storeId);
    }

    /**
     * Is the frontend widget enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isFrontendEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_FRONTEND_ENABLED, $storeId);
    }

    /**
     * Can the frontend widget capture user data and send it to Feedbucket
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isFrontendCaptureUserData(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_FRONTEND_CAPTURE_USER_DATA, $storeId);
    }

    /**
     * Is the backend widget enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isBackendEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_BACKEND_ENABLED, $storeId);
    }

    /**
     * Can the backend widget capture user data and send it to Feedbucket
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isBackendCaptureUserData(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_BACKEND_CAPTURE_USER_DATA, $storeId);
    }

    /**
     * Check if the whitelist is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function getWhitelistEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::CFG_FEEDBUCKET_WHITELIST_ENABLED, $storeId);
    }

    /**
     * Retrieve the whitelist
     *
     * @param int|null $storeId
     * @return string
     */
    public function getWhitelist(int $storeId = null): string
    {
        return ($this->getValue(self::CFG_FEEDBUCKET_WHITELIST_WHITELIST, $storeId) ?? '');
    }

    /**
     * Retrieve the user hash token
     *
     * @param array $data
     * @return string
     */
    public function getUserHash(array $data)
    {
        $string = implode('|', $data);
        return hash_hmac("sha256", $string, $this->getSecret());
    }
}
