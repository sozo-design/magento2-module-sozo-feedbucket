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

namespace Sozo\Feedbucket\Block\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * "Add IP Address" button: adds the admin user's current IP address to the IP Whitelist field
 */
class IpButton extends Field
{
    protected const WHITELIST_DOM_ID = 'sozo_feedbucket_ip_whitelist_whitelist';

    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @var SecureHtmlRenderer
     */
    private $secureRenderer;

    /**
     * @param Context $context
     * @param RemoteAddress $remoteAddress
     * @param SecureHtmlRenderer $secureRenderer
     * @param array $data
     */
    public function __construct(
        Context $context,
        RemoteAddress $remoteAddress,
        SecureHtmlRenderer $secureRenderer,
        array $data = []
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->secureRenderer = $secureRenderer;

        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        return parent::render($element) . $this->getButtonScript($element);
    }

    /**
     * @inheritdoc
     *
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    public function _getElementHtml(AbstractElement $element): string
    {
        $button = $this->getLayout()->createBlock(Button::class);

        $button->setData([
            'id' => $element->getHtmlId(),
            'label' => __('Add IP Address'),
            'type' => 'button',
        ]);

        return $button->toHtml();
    }

    /**
     * DOM element of whitelist
     *
     * @return string
     */
    protected function getWhitelistDOMId(): string
    {
        return self::WHITELIST_DOM_ID;
    }

    /**
     * Return the current IP address of the user, or an empty string when it can't be found
     *
     * @return string
     */
    public function getCurrentIpAddress(): string
    {
        return (string)$this->remoteAddress->getRemoteAddress();
    }

    /**
     * Script that adds the current IP address to the whitelist, once
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function getButtonScript(AbstractElement $element): string
    {
        $options = (string)json_encode([
            'buttonId' => $element->getHtmlId(),
            'listId' => $this->getWhitelistDOMId(),
            'ip' => $this->getCurrentIpAddress(),
            'messages' => [
                'exists' => (string)__('Your IP address (%1) is already in the list.', $this->getCurrentIpAddress()),
                'noIp' => (string)__('Your IP address could not be found.'),
            ],
        ], self::JSON_FLAGS);

        $script = <<<JS
(function (options) {
    function init() {
        var button = document.getElementById(options.buttonId);
        var list = document.getElementById(options.listId);
        if (!button || !list) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            if (!options.ip) {
                window.alert(options.messages.noIp);
                return;
            }

            var lines = list.value.split(/\\r?\\n/).map(function (line) {
                return line.trim();
            }).filter(function (line) {
                return line !== '';
            });

            if (lines.indexOf(options.ip) !== -1) {
                window.alert(options.messages.exists);
                return;
            }

            lines.push(options.ip);
            list.value = lines.join('\\n');
            list.dispatchEvent(new Event('change', {bubbles: true}));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})({$options});
JS;

        return $this->secureRenderer->renderTag('script', ['type' => 'text/javascript'], $script, false);
    }
}
