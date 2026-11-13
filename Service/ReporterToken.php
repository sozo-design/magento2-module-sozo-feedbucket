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

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Config\ConfigOptionsListConstants;

/**
 * Builds the Feedbucket reporter token
 *
 * Feedbucket uses the token to decide which feedback a reporter can see, so it must stay the same for a
 * user and must not be guessable. It is an HMAC of the user type and ID, keyed with this installation's
 * encryption key, which never leaves the server.
 */
class ReporterToken
{
    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_ADMIN = 'admin';

    /**
     * @var DeploymentConfig
     */
    private $deploymentConfig;

    /**
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(DeploymentConfig $deploymentConfig)
    {
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * Token for a customer or admin user, or an empty string when it can't be made safely
     *
     * @param string $type
     * @param int|string $userId
     * @return string
     */
    public function forUser(string $type, $userId): string
    {
        $key = $this->getKey();
        if ($key === '' || (string)$userId === '') {
            return '';
        }

        return hash_hmac('sha256', 'feedbucket|' . $type . '|' . $userId, $key);
    }

    /**
     * The first (original) encryption key, so the token survives a key rotation
     *
     * @return string
     */
    private function getKey(): string
    {
        $keys = (string)$this->deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_CRYPT_KEY);
        foreach (preg_split('/\s+/', trim($keys)) ?: [] as $key) {
            if ($key !== '') {
                return $key;
            }
        }

        return '';
    }
}
