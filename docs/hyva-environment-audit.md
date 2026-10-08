# Aeromalls environment audit

Audited 2026-10-07. Source: installed packages, theme files, Magento configuration and read-only catalog queries; no credentials exported.

| Item | Actual environment |
|---|---|
| Magento | Open Source 2.4.9 |
| PHP / Node for theme builds | PHP 8.5.11 / Node 24.21.0 inside php-fpm |
| Parent | Hyva/default; installed default theme 1.5.2 |
| Build | Tailwind 4.3.1, @hyva-themes/hyva-modules, npm lockfile from installed parent |
| Application filesystem | Docker named volume /var/www/html; custom source bind mounted from src/ |
| Existing theme / homepage | theme ID 4, Hyva/default; CMS identifier home |
| New theme | ID 5, Aeromalls/aeromalls |
| Currency / locale / timezone | CAD / en_US / America/Vancouver |
| Catalog at audit | 1 existing product; 45 category entities including root categories |
| Existing MPN | Aeromalls_ProductMpn, attribute mpn; originally not searchable |
| Search | OpenSearch; no magento/live-search package or Magento_LiveSearch modules |
| Checkout | Hyva_LumaCheckout + Hyva_ThemeFallback, StripeIntegration_Payments |
| Original public cache state | layout, block_html, full_page disabled; other core caches enabled |
| Repository instructions | Magento skill: CLI / Composer as www-data, no 777, no reinstall/reset |

The installed Commerce Services Connector is not Live Search. Adobe's supported Live Search platforms are licensed Adobe Commerce on-premises and cloud, with license-holder API keys and catalog sync. See [Adobe requirements](https://experienceleague.adobe.com/en/docs/commerce/live-search/install#requirements). The storefront uses native indexed catalog search, with an isolated autocomplete search request; no SaaS connector is introduced.

The existing namespace is Aeromalls; new code uses that namespace. No duplicate MPN attribute, category fixtures or product fixtures were inserted. The existing homepage and theme assignment remain unchanged. No changes to currency, timezone, locale, payment credentials or checkout fallback configuration.

## Ownership

Custom source remains editable by the host owner; its group is www-data (33), directories 2775, source files 664. Build outputs are generated as www-data. A narrowly scoped root chgrp/chmod was necessary to give PHP-FPM group access to the new bind mounts. CLI and npm builds are run as www-data. npm uses /tmp/aeromalls-npm-cache because the default /var/www/.npm cache was not writable.
