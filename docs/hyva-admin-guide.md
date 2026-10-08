# Aeromalls admin guide

## Activate the storefront homepage

The storefront homepage belongs at `/`. After deploying the route change, `/aeromalls` and `/aeromalls/index/index` return the native Magento CMS 404 page. `/pcbuilder` and `/aeromalls/categories` remain service pages.

From the repository, run `bash bin/aeromalls-activate-home daiwa-ubuntu-server` with an account that can access Docker. It compiles DI, deploys child theme assets, activates the CMS homepage and adds missing store assignments, or creates it when no suitable page exists, sets the theme and CMS homepage at store scope for matching base URL hosts, and cleans the affected caches. Every container command runs as www-data. Previous raw store settings and CMS activation flags and store assignments are saved to `var/aeromalls-home-before-*.json` inside Magento. CMS content, locale, currency and checkout configuration are preserved.

Manual equivalent: Content > Design > Configuration > intended store view > Applied Theme **Aeromalls**; enable the existing `aeromalls-home` CMS page; Stores > Configuration > General > Web > Default Pages > CMS Home Page **Aeromalls Home**. Clean config/layout/block_html/full_page caches. For rollback, restore the prior values from the snapshot (delete a store override when `existed` was false), restore the page activation flag and store assignments, and clean caches.

Activation has not yet been executed in this session: Docker socket access is denied. Confirm deployment and visit `/` before treating the migration as complete.

## Store configuration

Stores > Configuration > **Aeromalls > Storefront**. Use the store-view scope selector and inheritance checkboxes; the ACL permission is Aeromalls Storefront.

- **General:** brand, homepage headline/introduction, optional hero CMS block. Logo can also be set using Magento's existing design configuration.
- **Announcements:** confirm Canadian ownership before enabling that claim; confirm the actual shipping rule/policy before enabling the shipping message. Displaying a message does not create a shipping rule.
- **Utility links:** Add rows. Enabled is 1/0. Type `cms` resolves an active CMS identifier; `route` resolves a Magento route; `url` accepts http/https or a site-relative URL. Supply label, destination/URL, and sort order. Configure About Us, How to Buy, Find Us/Contact, Privacy Policy, Terms, Product Categories, Unboxing Guide and Business only after their pages exist. Recycling stays disabled unless the service exists. For Product Categories use route `aeromalls/categories`.
- **Navigation:** depth is bounded from three to eight; category trees follow the selected store root, active/include_in_menu settings and position.
- **Search:** configure minimum length, debounce, product limit (server maximum 10), MPN display and public curated keywords, one per line. There is no private query-history sharing.
- **Weekly deals:** enable only after selecting a visible category containing the merchant's curated products. Set title, limit (maximum 24) and view-all label. This is curated selection, not a computed sales ranking.
- **Partners:** Add rows, set enabled/name/alt/URL/new-tab/sort. Use each row's file picker to upload a logo, then save configuration. Images must be JPEG/PNG/GIF/WebP and under 5 MB. No SVG/scripts accepted. Existing uploaded media-relative paths can also be entered. Leave URL blank for an unlinked logo. Only authorized merchant partner logos should be used.
- **Footer:** select existing active CMS blocks for company and support content. Contact details come from General > General > Store Information; unconfigured details are omitted. Do not publish placeholder policies or invented payment logos.
- **Promo:** disabled by default. Set campaign ID, title, images, image description, safe destination, CTA, start/end in store local time (`YYYY-MM-DD HH:MM:SS`), delay and frequency. Changing campaign ID starts a new browser frequency window. The page honors expiry even when cached. Requires real merchant campaign images.
- **PC Builder:** map CPU, Motherboard, RAM, GPU, Storage, PSU, Case, Cooling and OS to real visible categories. Select the actual assembly service introduction CMS block. Unmapped/inaccessible categories are hidden, with an honest empty message if none are configured.
- **Assistant:** disabled by default. Link mode opens the same PC Builder route. Preview is developer mode only and says AI is disconnected. Live mode is not offered in P0.

## Launch checklist

Review published CMS/legal content and contact details, ownership/shipping claims, category mappings, enabled products/stock/prices and authorized logos. Verify guest and customer workflows on the activated store view. Preserve Luma checkout fallback. Stripe still needs valid test/live API credentials before actual payment acceptance can be verified. No paid Hyvä Checkout is required for this existing Luma fallback.
