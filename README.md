# Feedbucket Magento 2 Plugin

[![License][ico-license]][link-license]
[![Total Downloads][ico-downloads]][link-downloads]
[![Latest Stable Version][ico-version]][link-version]
[![StyleCI][ico-styleci]][link-styleci]

Full documentation (SOZO staff): https://sozodesign-co.getoutline.com/doc/feedbucket-1uP4OwrCIE

## Installation
1. You need an active [Feedbucket account](https://dashboard.feedbucket.app/register).
2. Install with Composer: `composer require sozodesign/magento2-module-sozo-feedbucket`. It needs `sozodesign/magento2-module-sozo-core` v402 from SOZO's private package repository.
3. Run `bin/magento setup:upgrade` (and `setup:di:compile` in production mode).
4. In the admin, go to **Stores > Configuration > SOZO Extensions > Feedbucket** (also under **SOZO Design > Feedbucket**), turn the module on and enter the widget key from Feedbucket's install snippet (the value of `data-feedbucket`).
5. Turn on **Display on Frontend** and/or **Display on Backend**.

## Settings

| Setting | Scope | What it does |
|---|---|---|
| General > Enabled, Secret | Default, website, store view | Switches the module on and sets the widget key. With no key, nothing is output. |
| Frontend Configuration | Default, website, store view | Shows the widget on the storefront. **Capture User Data** sends the logged-in customer's name and email to Feedbucket. **Hide on Checkout** (on by default) keeps it off checkout, payment review and order confirmation pages. **Also Hide on Pages** takes more page names (full action names, `prefix*` allowed). |
| Backend Configuration | Default only | Shows the widget in the admin. **Capture User Data** sends the admin user's name and email. |
| IP Whitelist | Default, website, store view | Only show the widget to listed addresses. One entry per line: an address (IPv4 or IPv6), CIDR (`10.0.0.0/8`, `2001:db8::/32`), address/netmask (`192.168.1.0/255.255.255.0`), wildcard (`192.168.*.*`) or range (`10.0.0.10-10.0.0.20`). |

## How the storefront widget works with the full page cache

Storefront pages are cached for everyone, so the page itself only depends on store settings:

- **IP whitelist off and Capture User Data off:** the page loads the widget directly.
- **Either one on:** the page carries a small loader. It asks `GET /feedbucket/widget/config` (never cached) whether this visitor gets the widget, and gets the key and, for a logged-in customer, their reporter details. The widget key is then not in public pages. Answers without reporter details are kept in `sessionStorage` for 10 minutes, so most visitors make one request per session.

The admin widget is rendered on the server (admin pages aren't cached).

## Reporter token

With Capture User Data on, each reporter gets a token so Feedbucket can show users only their own feedback ([Feedbucket docs](https://help.feedbucket.app/en/article/user-feedback-do-not-show-feedback-between-users-17veakg/)). It is an HMAC-SHA256 of the user type and ID, keyed with the first `crypt/key` in `app/etc/env.php`, so it doesn't change when a user edits their name or the encryption key is rotated, and it can't be worked out from the page.

## Behind Cloudflare, Varnish or a load balancer

The IP whitelist checks the address Magento sees (`RemoteAddress`). Behind a proxy that is the proxy's address unless the real visitor IP is passed through, for example with nginx `real_ip` (`set_real_ip_from` + `real_ip_header CF-Connecting-IP` or `X-Forwarded-For`), or by giving `Magento\Framework\HTTP\PhpEnvironment\RemoteAddress` `alternativeHeaders` in the project's `di.xml`. Check this before relying on the whitelist.

## SOZO Design menu, settings panel and log

These come from Sozo_Core 402:

- **Menu:** Sozo_Core's SOZO Design menu lists Feedbucket with a Configuration link (from the settings section) and a Documentation link (from `support.docs` in `composer.json`). The module has no `menu.xml`.
- **Settings panel:** the panel at the top of the settings is Core's `AbstractHint`.
- **Log:** `var/log/sozo/core.log`, channel `Sozo_Feedbucket`. In developer mode there is a debug line for each IP whitelist check (entry count, the line numbers of entries it can't read, and the result; no IP addresses). There is also a warning when a reporter token can't be made because there is no encryption key.

## Content Security Policy

`etc/csp_whitelist.xml` allows `cdn.feedbucket.app` and `dashboard.feedbucket.app`. The inline scripts are output through Magento's `SecureHtmlRenderer`, so they work under the strict CSP Magento 2.4.7+ uses on checkout.

## Tests

Unit tests for the IP whitelist and page rules are in `Test/Unit`. They only need PHPUnit, so they run without Magento:

```bash
phpunit --bootstrap Test/Unit/bootstrap.php Test/Unit
```

From a Magento project they also run with `dev/tests/unit/phpunit.xml.dist`.

[ico-license]][link-license]
[![Total Downloads][ico-downloads]][link-downloads]
[![Latest Stable Version][ico-version]][link-version]
[![StyleCI][ico-styleci]][link-styleci]

## Installation
**Using Composer:**
1. You will need to have an active [Feedbucket account](https://dashboard.feedbucket.app/register) to set up the plugin.
2. Install using composer `composer require sozodesign/magento2-module-sozo-feedbucket`
3. Log into the admin and enable the plugin and add the widget id
4. Clear your Magento cache and you're done


## Todo
- Documentation
- Unit Tests

[ico-license]: https://poser.pugx.org/sozodesign/magento2-module-sozo-feedbucket/license
[ico-downloads]: https://poser.pugx.org/sozodesign/magento2-module-sozo-feedbucket/downloads
[ico-version]: https://poser.pugx.org/sozodesign/magento2-module-sozo-feedbucket/v/stable
[ico-styleci]: https://github.styleci.io/repos/722575812/shield?branch=main

[link-license]: ./LICENSE
[link-downloads]: https://packagist.org/packages/sozodesign/magento2-module-sozo-feedbucket
[link-version]: https://packagist.org/packages/sozodesign/magento2-module-sozo-feedbucket
[link-styleci]: https://github.styleci.io/repos/722575812?branch=main
