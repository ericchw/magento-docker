# Aeromalls test results

2026-10-07, Magento Open Source 2.4.9, developer mode. This report distinguishes implementation checks from merchant configuration and payment acceptance.

## Homepage migration update

The former `/aeromalls` preview is replaced in code by the native Magento 404 page. A deployment helper now assigns the child theme and existing CMS page to matching store views. Docker socket access was denied during this change, so DI compilation, static deployment, live configuration activation and HTTP checks of `/` and the removed route are BLOCKED. Earlier results below describe the previous preview implementation and do not certify this migration.

## Passed before homepage migration

| Check | Evidence |
|---|---|
| PHP syntax and DI | New PHP/PHTML linted; DI compilation successful after correcting factory wiring |
| Magento XML schemas | New module/theme XML validated against installed Magento XSDs |
| Database/module upgrade | setup:upgrade completed; setup:db:status reports all modules up to date |
| Theme assets | Tailwind 4.3.1 build; en_US deployment for admin, blank, luma, Hyva/default and Aeromalls/aeromalls |
| Filesystem | Build/CLI as www-data; generated/static/preprocessed files owned by www-data; no 777 introduced |
| Unit tests | PHPUnit 12.5.38: 5 tests / 22 assertions; Unicode length/control filtering, bounds, URL schemes, three category levels, hidden/missing parents, other roots, order and empty tree |
| Preview | /aeromalls, /aeromalls/categories, /pcbuilder HTTP 200; child stylesheet used |
| Responsive | 360, 390, 768, 1024, 1440, 1920: no document horizontal overflow; one search input; screenshots saved |
| Navigation | Mobile dialog opens and Escape closes it; category overview has the three actual top-level categories |
| Real search | Name prefix Arct, SKU prefix B000, MPN prefix ACFR, exact B0000000001 and ACFRE00180A return existing product ID 1; unknown query returns no products |
| Search validation | 150 Unicode characters become 128; oversized item limit bounded; JSON private/no-store |
| Search UI | Real product option appears; ArrowDown sets active ID; Escape closes the preview; public result links use real rewritten URLs |
| Race / IME | Delayed browser-only response fixtures: latest response wins, no request while composing, request after composition; no console errors |
| Guest account | Native account menu renders Sign In/Create Account and added Forgot Password link |
| Cart / checkout | Existing Arctic cooler added; preview native summary_count becomes 1; checkout reaches /checkout#shipping with Magento/luma assets; remove action exercised and test cart verified empty |
| FPC | Temporarily enabled original disabled public caches: preview first request MISS, second HIT. Original disabled layout/block_html/full_page states restored afterward |
| Original storefront | Existing Hyva/default assignment and CMS home selection retained; no new homepage automatically activated |

Browser evidence is in `docs/aeromalls/browser-checks.json`, `flow-checks.json`, `component-checks.json` and the PNG screenshots. Product data came from the existing catalog, not inserted fixtures. The product currently uses Magento's placeholder image.

## Not run / blocked

| Check | Status / reason |
|---|---|
| Signed-in account/orders/logout, multiple customer groups | NOT RUN: no dedicated QA customer accounts/group fixtures provided |
| Password-reset email delivery | NOT RUN: no reset email sent; link/form route coverage does not prove email delivery |
| Actual Stripe payment / place order | BLOCKED: valid Stripe API credentials/payment configuration required; reaching shipping is not a payment acceptance test |
| Composite/configurable/bundle prices and alternate currencies/tax modes | NOT RUN against live fixtures: catalog has only one simple product; code uses native Magento price amounts, not proof of every combination |
| Stock/visibility/multiple-website negative catalog fixtures | NOT RUN as live integration fixtures: no production catalog data mutated for negative tests; store/tree filtering covered by implementation and unit checks |
| Admin scope inheritance, restricted-role ACL and uploader browser workflow | NOT RUN with authenticated QA admin roles; system XML, DI and upload validation code checked |
| Promo timing/frequency/focus with actual merchant assets | BLOCKED by missing campaign images/content; defaults disabled; schedule guards implemented |
| Deals with 0/1/12 products, partner links/images and full footer policies | Merchant categories/assets/CMS content not configured; missing content hides safely. Native slider reused; 12-product test cannot be claimed from a one-product catalog |
| Product/CMS/config update invalidation in a configured deals homepage | NOT RUN as a mutation test: merchant content/settings not altered; public FPC MISS/HIT measured and entity tags/config cache cleaning implemented |
| Full keyboard/screen-reader audit and all CSP payment states | NOT RUN; observed browser runs had no console errors, checkout fallback preserved |
| P1 builder / P2 AI | Outside P0 scope; no fake compatibility/AI/payment success claimed |

## Resolved during implementation

- Default npm cache was not writable by www-data: use a dedicated /tmp/aeromalls-npm-cache.
- Initial CMS seed used a rejected database layout update: use child-theme file based handles instead.
- Reference-block ttl attribute failed XSD validation: supply ttl as a block argument.
- Missing inherited head bootstrap on the child preview caused native Hyva helper errors: explicitly attach native variable/helper blocks to head.additional.
- Autocomplete initially read the input's dataset: use Alpine's component root dataset.
- Static deployment retained an older stylesheet: refresh only the generated child stylesheet as www-data and compare it to the built file.

## Reproduce

From the repository root:

```bash
docker compose exec -T -u www-data php-fpm vendor/bin/phpunit --bootstrap app/bootstrap.php app/code/Aeromalls/Storefront/Test/Unit
./bin/aeromalls-theme status
```

Browser scripts are in `src/app/code/Aeromalls/Storefront/Test/Browser`. Run with an available Node 20+ and Playwright installation; the implementation used temporary tooling in /tmp and the container's Node 24 for the CSS build, without adding a production browser dependency. The guest flow uses the existing product, creates a temporary guest cart, reaches checkout without placing an order, and removes the test item. Race fixtures exist only in intercepted browser responses, never in Magento catalog/CMS data.

## Activation retry correction

User execution confirms DI compilation and child static deployment succeeded. CMS activation stopped because the page was unavailable to aeroamlls_view, before configuration changes were written. The helper now adds missing store assignments while preserving content, or creates the required page if no suitable page exists. The old homepage now forwards internally to native CMS noroute, without a redirect. PHP syntax checks passed; activation retry and live HTTP 404/homepage checks remain pending because agent Docker access is denied.
