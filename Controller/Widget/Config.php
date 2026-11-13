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

namespace Sozo\Feedbucket\Controller\Widget;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Sozo\Feedbucket\Service\StorefrontWidget;

/**
 * GET feedbucket/widget/config: whether this visitor gets the Feedbucket widget, and its settings
 *
 * Never cached: the answer depends on the visitor's IP address and customer session.
 */
class Config implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var StorefrontWidget
     */
    private $storefrontWidget;

    /**
     * @param JsonFactory $jsonFactory
     * @param StorefrontWidget $storefrontWidget
     */
    public function __construct(JsonFactory $jsonFactory, StorefrontWidget $storefrontWidget)
    {
        $this->jsonFactory = $jsonFactory;
        $this->storefrontWidget = $storefrontWidget;
    }

    /**
     * @inheritdoc
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private', true);
        $result->setHeader('Pragma', 'no-cache', true);
        $result->setHeader('X-Robots-Tag', 'noindex, nofollow', true);

        $data = $this->storefrontWidget->forCurrentVisitor();
        if (isset($data['config']) && $data['config'] === []) {
            $data['config'] = new \stdClass();
        }

        return $result->setData($data);
    }
}
