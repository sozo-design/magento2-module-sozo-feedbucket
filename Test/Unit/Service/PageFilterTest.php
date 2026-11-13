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

namespace Sozo\Feedbucket\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Sozo\Feedbucket\Service\PageFilter;

class PageFilterTest extends TestCase
{
    /**
     * @var PageFilter
     */
    private $filter;

    protected function setUp(): void
    {
        $this->filter = new PageFilter();
    }

    /**
     * @dataProvider checkoutProvider
     */
    public function testCheckoutPages(string $page, bool $hidden): void
    {
        $this->assertSame($hidden, $this->filter->isHidden($page, PageFilter::CHECKOUT_PAGES));
    }

    public static function checkoutProvider(): array
    {
        return [
            'checkout' => ['checkout_index_index', true],
            'success' => ['checkout_onepage_success', true],
            'failure' => ['checkout_onepage_failure', true],
            'multishipping' => ['multishipping_checkout_billing', true],
            'paypal review' => ['paypal_express_review', true],
            'braintree paypal review' => ['braintree_paypal_review', true],
            'one step checkout' => ['onestepcheckout_index_index', true],
            'basket stays' => ['checkout_cart_index', false],
            'home stays' => ['cms_index_index', false],
            'product stays' => ['catalog_product_view', false],
            'similar prefix stays' => ['checkout_indexer_x', false],
        ];
    }

    public function testExactRuleDoesNotMatchLongerNames(): void
    {
        $this->assertTrue($this->filter->isHidden('cms_page_view', ['cms_page_view']));
        $this->assertFalse($this->filter->isHidden('cms_page_view_extra', ['cms_page_view']));
    }

    public function testMatchingIsCaseInsensitive(): void
    {
        $this->assertTrue($this->filter->isHidden('paypal_express_placeOrder', ['paypal_express_placeorder']));
        $this->assertTrue($this->filter->isHidden('Checkout_Index_Index', ['checkout_index_*']));
    }

    public function testParseListHandlesNewlinesCommasAndBlanks(): void
    {
        $this->assertSame(
            ['amasty_checkout_*', 'mymodule_page_view', 'other_*'],
            $this->filter->parseList("amasty_checkout_*\r\n\r\n  mymodule_page_view , other_*\n")
        );
        $this->assertSame([], $this->filter->parseList(null));
    }

    public function testBareWildcardAndEmptyNamesNeverHide(): void
    {
        $this->assertFalse($this->filter->isHidden('cms_index_index', ['*', '']));
        $this->assertFalse($this->filter->isHidden('', ['checkout_*']));
    }
}
