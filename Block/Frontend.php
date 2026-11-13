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

namespace Sozo\Feedbucket\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Sozo\Feedbucket\Service\StorefrontWidget;

/**
 * Storefront widget loader
 *
 * The page output depends only on store settings, so it is safe in the full page cache. When the
 * IP whitelist or "Capture User Data" is on, the page only carries a small loader that asks the
 * uncached feedbucket/widget/config endpoint whether this visitor gets the widget.
 */
class Frontend extends Template
{
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * Seconds a visitor's "no reporter details" answer is reused (sessionStorage)
     */
    private const CACHE_SECONDS = 600;

    /**
     * @var StorefrontWidget
     */
    private $storefrontWidget;

    /**
     * @param Context $context
     * @param StorefrontWidget $storefrontWidget
     * @param array $data
     */
    public function __construct(Context $context, StorefrontWidget $storefrontWidget, array $data = [])
    {
        $this->storefrontWidget = $storefrontWidget;
        parent::__construct($context, $data);
    }

    /**
     * Loader options as a JavaScript object literal
     *
     * Either the key and an empty config (no visitor check needed), or the endpoint URL.
     *
     * @return string
     */
    public function getJsonLoaderOptions(): string
    {
        if ($this->storefrontWidget->needsVisitorCheck()) {
            $options = [
                'url' => $this->getUrl('feedbucket/widget/config', ['_secure' => $this->getRequest()->isSecure()]),
                'cacheSeconds' => $this->storefrontWidget->needsReporter() ? 0 : self::CACHE_SECONDS,
            ];
        } else {
            $options = ['key' => $this->storefrontWidget->getKey()];
        }

        return (string)json_encode($options, self::JSON_FLAGS);
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml(): string
    {
        return $this->storefrontWidget->isActive() ? parent::_toHtml() : '';
    }
}
