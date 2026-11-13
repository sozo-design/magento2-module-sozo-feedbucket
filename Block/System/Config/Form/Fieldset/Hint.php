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

namespace Sozo\Feedbucket\Block\System\Config\Form\Fieldset;

use Sozo\Core\Block\System\Config\Form\Fieldset\AbstractHint;

/**
 * The SOZO panel at the top of the Feedbucket configuration, from Sozo_Core.
 */
class Hint extends AbstractHint
{
    protected string $moduleCode = 'Sozo_Feedbucket';

    protected string $moduleName = 'SOZO Feedbucket';
}
