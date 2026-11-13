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

namespace Sozo\Feedbucket\Block\System\Config\Form\Fieldset;

use Sozo\Feedbucket\Model\ConfigProvider;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

class Hint extends Template implements RendererInterface
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var string
     */
    protected $_template = 'Sozo_Feedbucket::system/config/fieldset/hint.phtml';

    /**
     * Hint constructor.
     *
     * @param Context $context
     * @param ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configProvider = $configProvider;
    }

    /**
     * @inheritdoc
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        return $this->toHtml();
    }

    /**
     * Retrieve the module version number
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->configProvider->getExtensionVersion();
    }
}
