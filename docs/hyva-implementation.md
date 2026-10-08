# Aeromalls Hyvä implementation

## Structure

- Theme: `src/app/design/frontend/Aeromalls/aeromalls`, parent `Hyva/default`.
- Module: `src/app/code/Aeromalls/Storefront` (`Aeromalls_Storefront`). P0 configuration, search and builder share one small module to avoid duplicate configuration/service layers.
- Existing `Aeromalls_ProductMpn` is retained. A data patch makes its existing attribute searchable and available to product listings; reindex catalogsearch_fulltext after installation.
- Mounts are present in PHP-FPM, nginx and cron. Vendor/core files are untouched.

## Homepage and routes

The intended homepage is `/`, rendered by the `aeromalls-home` CMS page under the globally assigned `Aeromalls/aeromalls` store theme. The route-specific theme plugin has been removed from DI. `HomeRedirect` now forwards the former `/aeromalls` homepage internally to the native CMS noroute action (404), without an HTTP redirect; the shared controller still renders `/pcbuilder`. `/aeromalls/categories` remains the category overview. Both service pages inherit the assigned store theme.

The seed creates `aeromalls-home` only if absent, initially disabled. `bash bin/aeromalls-activate-home daiwa-ubuntu-server` reuses the existing page, adds any missing store assignment without changing its content, or creates a page if needed, then writes store-scoped theme/homepage settings for stores with the requested base URL host. It saves previous settings before mutation. Existing merchant content is never replaced. The page uses a CMS block directive to the homepage template, with required slider/modal handles supplied by the child theme's CMS layout. No unfinished legal pages are published. Deployment remains pending until Docker access is available.

## Native behavior retained

The header template is a minimal adaptation of the installed parent: native account component, summary_count badge, private-content-loaded cart data, cart drawer, authentication popup and native scripts are retained. The visual header arrangement and search form change. Account/cart URLs and payment routes remain Magento's. The Luma fallback uses its own theme, so the child theme's header/footer and assistant do not enter checkout.

Navigation uses one store-scoped category collection with active/include_in_menu/position/rewrite filtering. A descendant is admitted only after its visible parent is admitted. Native HTML details provides disclosures and a dialog provides the mobile drawer; JavaScript adds Escape, expanded-state updates and scroll locking. Category links survive without JavaScript, and mobile includes a category-overview fallback.

## Search

The same-origin read-only endpoint is `/aeromalls_search/suggest/index?q=...&limit=6`. Unicode query length is capped at 128 and item limit at 10. The minimum is the greater of two, Magento's configured minimum and the module setting. Requests are limited to 60 per source IP/store/minute using a lock-protected cache counter. Autocomplete-only OpenSearch calls have a two second server search timeout and three second client timeout.

A dedicated copy of the installed native indexed quick-search request includes name, SKU and the existing MPN phrase-prefix matches. Exact SKU/MPN equality queries prioritize matching products; there are no broad product LIKE scans. Products are filtered for current website/store, status, searchable visibility and configured out-of-stock display. Product URLs, images, currency formatting and price amounts are generated through Magento. Prices use the current customer group and Magento tax-adjusted amount objects. Variable products use From, or a bundle range when Magento supplies distinct minimum/maximum amounts; JSON includes explicit min/max fields. JSON responses are private/no-store and expose no stack traces.

The Alpine component handles debounce, Unicode min length, IME composition, request abortion, sequence guards, Arrow keys, Escape, Tab, active-option Enter and default search submission. Results use textContent/x-text, never untrusted innerHTML. JavaScript failure leaves the ordinary search form usable. Public curated keyword suggestions do not use customer search history.

## Content and caching

Homepage: configurable CMS hero or text hero, real category links, configured native Hyvä product slider, partner logos, optional scheduled promotion. Deals use the native product card, price renderer and add-to-cart behavior. No fabricated products, discount badges, partners, policy text or contact details are inserted.

Public content remains cacheable. Category/CMS tags and native slider product identities participate in invalidation. Saving the new configuration cleans block HTML and full-page cache. Promo schedules are also enforced in the browser so a cached page cannot keep an expired campaign open. Session/24h campaign storage is store-specific. Ownership/shipping claims, promo and assistant are disabled by default. AI live mode is unavailable; no third-party AI SDK is loaded.

## Overrides

| File/handle | Reason |
|---|---|
| Magento_Theme/templates/html/header.phtml | Header arrangement; prominent search; native account/cart retained |
| Magento_Theme/templates/html/header/logo.phtml | Configured logo or accessible Aeromalls text branding |
| Magento_Theme/layout/default.xml | Navigation/search/footer/announcement blocks; explicitly attach inherited Hyvä variables/bootstrap blocks to head.additional for the child preview; add native forgot-password route link |
| cms_index_index.xml | Remove the parent's sample category product sliders and hero |
| cms_page_view.xml | Native slider and modal support for merchant-selected CMS home |
| Dedicated preview/builder handles | Add actual module page templates and inherited shared UI |
| Tailwind input/config | Inherit installed parent CSS and scan both parent and custom templates |

## Deployment

1. Review `git diff` and back up the existing database using the repository backup workflow.
2. Mount source with `docker compose up -d --no-deps php-fpm nginx cron`.
3. Check affected paths writable by www-data; do not use 777.
4. `docker compose exec -T -u www-data php-fpm bin/magento module:enable Aeromalls_Storefront`
5. `docker compose exec -T -u www-data php-fpm bin/magento setup:upgrade`
6. `./bin/aeromalls-theme build`
7. `./bin/aeromalls-theme deploy`
8. `docker compose exec -T -u www-data php-fpm bin/magento indexer:reindex catalogsearch_fulltext`
9. Check `/aeromalls`, `/pcbuilder`, search and the original storefront. Follow the admin guide before activating the new homepage/theme.

The helper checks writable paths before running and uses www-data for every Magento/npm command. npm dependencies are ignored in git; the CSS build and lockfile are reproducible. The build helper also refreshes the existing deployed child styles.css as www-data: Magento static deployment can retain an already present stylesheet, so a successful deploy alone does not prove its contents changed. This is a developer-mode environment; no production-mode switch is necessary.

## Rollback

Restore the prior theme assignment and prior store-scoped CMS home selection, then clean config/layout/block_html/full_page caches as www-data. Audit values were Hyva/default (theme ID 4) and homepage `home`; use the values recorded for the store being changed, not those IDs in another environment. The module can remain installed with its settings disabled. If disabling it, first remove any CMS directives referencing its blocks and revert the theme; do not delete merchant CMS data or reset Magento volumes. MPN searchability is an additive attribute change and remains until explicitly reverted through a reviewed data patch.

## Scope

P0 storefront and component browsing only. P1 selection/compatibility/build-total/save/all-add and P2 AI are not implemented. Production activation and purchase/payment acceptance need merchant content, mapped categories, real product coverage and valid payment credentials.
