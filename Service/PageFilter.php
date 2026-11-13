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

namespace Sozo\Feedbucket\Service;

/**
 * Decides whether the widget is kept off a page, by Magento full action name (route_controller_action)
 *
 * A rule is an exact full action name (`checkout_index_index`) or a prefix ending in `*` (`checkout_index_*`).
 * Has no Magento dependencies, so it can be unit tested on its own.
 */
class PageFilter
{
    /**
     * Storefront checkout and payment pages (PCI DSS 4.0 6.4.3 expects every script on a payment page
     * to be listed and justified). The basket (checkout_cart_*) is not included.
     */
    public const CHECKOUT_PAGES = [
        'checkout_index_*',
        'checkout_onepage_*',
        'multishipping_*',
        'paypal_express_review',
        'paypal_express_placeOrder',
        'braintree_paypal_review',
        'onestepcheckout_*',
    ];

    /**
     * Storefront account pages that show the customer's personal data. Login, registration and password
     * reset pages are not included, so reviewers can still comment on them.
     */
    public const ACCOUNT_PAGES = [
        'customer_account_index',
        'customer_account_edit',
        'customer_address_*',
        'sales_order_*',
        'sales_guest_*',
        'wishlist_*',
        'review_customer_*',
        'newsletter_manage_*',
        'vault_cards_*',
        'downloadable_customer_*',
        'paypal_billing_agreement_*',
    ];

    /**
     * Admin pages that show customers' or admin users' personal data
     */
    public const ADMIN_PERSONAL_DATA_PAGES = [
        'adminhtml_dashboard_*',
        'customer_index_*',
        'customer_online_*',
        'sales_order_*',
        'sales_invoice_*',
        'sales_shipment_*',
        'sales_creditmemo_*',
        'sales_transactions_*',
        'sales_archive_*',
        'adminhtml_order_shipment_*',
        'paypal_billing_agreement_*',
        'newsletter_subscriber_*',
        'newsletter_problem_*',
        'reports_report_customer_*',
        'reports_report_shopcart_*',
        'reports_report_review_customer',
        'adminhtml_rma_*',
        'adminhtml_user_*',
    ];

    /**
     * Split a "one rule per line" setting into rules, ignoring blank lines and surrounding whitespace
     *
     * @param string|null $rules
     * @return string[]
     */
    public function parseList(?string $rules): array
    {
        $lines = preg_split('/[\r\n,]+/', (string)$rules) ?: [];
        $lines = array_map('trim', $lines);

        return array_values(array_filter($lines, static fn(string $line): bool => $line !== ''));
    }

    /**
     * Does the full action name match any rule
     *
     * @param string $fullActionName
     * @param string[] $rules
     * @return bool
     */
    public function isHidden(string $fullActionName, array $rules): bool
    {
        $fullActionName = strtolower(trim($fullActionName));
        if ($fullActionName === '') {
            return false;
        }

        foreach ($rules as $rule) {
            $rule = strtolower(trim((string)$rule));
            if ($rule === '' || $rule === '*') {
                continue;
            }

            if (substr($rule, -1) === '*') {
                if (strpos($fullActionName, substr($rule, 0, -1)) === 0) {
                    return true;
                }
            } elseif ($fullActionName === $rule) {
                return true;
            }
        }

        return false;
    }
}
