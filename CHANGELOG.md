# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

##### Types of changes
* [Added] for new features.
* [Changed] for changes in existing functionality.
* [Deprecated] for soon-to-be removed features.
* [Removed] for now removed features.
* [Fixed] for any bug fixes.
* [Security] in case of vulnerabilities.

## [Unreleased]
### Security
- The widget script is output through Magento's `SecureHtmlRenderer`, so Magento 2.4.7+ can allow it under the strict checkout Content Security Policy, and the reporter details and key are JSON-encoded so a name containing `</script>` or quotes can't break out of the script
- The reporter token was an HMAC of the user's name and email keyed with the widget key, which is printed in every page, so anyone who knew a user's name and email could compute it and see that user's feedback. It is now an HMAC of the user type and ID keyed with the installation's encryption key. Reporters who used "user feedback" mode before this release get a new token, so their earlier feedback is no longer shown to them

### Fixed
- Full page cache: the IP whitelist decision and the logged-in customer's details were rendered into cached storefront pages, so whichever visitor loaded a page first decided whether everyone saw the widget (and with whose details). The cached page now only depends on store settings. When the IP whitelist or "Capture User Data" is on, a small loader asks the new uncached `feedbucket/widget/config` endpoint per visitor, and the widget key is no longer in public pages
- An enabled module with no Secret threw a TypeError on every page. The widget is now left out until a Secret is entered
- Storefront "Capture User Data" sent the customer's ID number to Feedbucket as their name
- Settings saved at website or store view scope were ignored: the module only read the Default Config values. The storefront now uses the current store view's settings
- IP whitelist: only the last line of the list could match, because the admin saves lines with CRLF and the lines weren't trimmed. Blank lines and spaces are now ignored too
- IP whitelist: a CIDR entry (e.g. `10.0.0.0/8`) after a plain address was never checked

### Added
- `GET /feedbucket/widget/config` (never cached): whether the current visitor gets the storefront widget, its key and reporter details. Answers without reporter details are reused for 10 minutes per browser session
- IP whitelist: IPv6 addresses and CIDR ranges
- Unit tests for the IP whitelist matching (`Test/Unit`)
### Changed
- Backend Configuration settings can only be set at Default Config, the only scope the admin uses

### Removed
- `ConfigProvider::getUserHash()` (replaced by `Service\ReporterToken`) and the unused `State` dependency of the widget blocks
- GitHub Actions workflow (`m2-test`): it could no longer install Magento 2.4.6 and failed on every push

## [v400.1.0] - 2026-11-13
### Changed
- Allow sozodesign/magento2-module-sozo-core v402 (`^401.0 || ^402.0`), so Feedbucket can be installed alongside Sozo_Imagify v401. No code changes were needed: the Core classes, menu parent and image Feedbucket uses are unchanged in v402

### Fixed
- PHP 8.4: implicitly nullable `$storeId` parameters in `ConfigProvider` raised deprecation notices (which break admin pages in developer mode)

## [v400.0.0] - 2023-11-23
### Added
- Initial release
