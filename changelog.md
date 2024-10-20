# Extend Magento2 Changelog

## version: 2.5.3

### Changelog:

---


**2.5.3**

Patch Release : Merge pull request #419 from helloextend/PUE_Order_details_parent_order

PUE orders update the parent order item with warranty orders





**2.5.2**

Patch Release : Merge pull request #418 from helloextend/fix_system_xml

Patch Release : fixed read only field in system settings





**2.5.1**

Patch Release: Merge pull request #417 from helloextend/leads_fix_092024

Patch Release: Update leads.phtml





**2.5.0**

Merge pull request #416 from helloextend/Oauth_implementation

Minor Release: implementation of Oauth





**2.4.0**

Minor Release: implementation of Oauth

Make sure to go to your merchant portal (demo and prod) and generate the corresponding  Client ID and Client Secret.
(go to merchant portal, click on integrations  > New Api integration > give a name to your set of keys
Then you will have Client ID and Client Secret)

The API Key will then be generated automatically and has a lifetime of 3 hours.
Opening the admin settings Store / Configuration / Extend / Store View will show the current age of the token and will refresh it automatically if needed.





**2.3.12**

Merge pull request #415 from helloextend/PUE_order_details_frontend

Patch Release: Add warranty item details





**2.3.11**

Patch Release: Add warranty item details

in the frontend, the Warranty Item was lacking details. this patch brings the warranty details and the parent order ID if present for PUE orders





**2.3.10**

Patch Release : Merge pull request #414 from helloextend/Bundle_product_Sync_price

Patch Release : Update ProductDataBuilder.php





**2.3.9**

Patch Release : Merge pull request #413 from helloextend/PackagistFix

Patch Release : Update composer.json





**2.3.8**

Patch Release : Merge pull request #412 from helloextend/Parent_Order_Post_Purchase

Patch Release : Allow tracking of Parent Order when purchasing from a Lead Token





**2.3.7**

Merge pull request #411 from helloextend/Category_Permission_Fix

Patch Release : Update Warranty.php





**2.3.6**

Patch Release : Merge pull request #410 from helloextend/CSP-m247_fix

Patch Release : converted all remaining script tags to to heredoc+securerenderer





**2.3.5**

Patch Release : Merge pull request #409 from helloextend/M247-CSP-bugfix_2

added CSP rules for magento 2.4.7





**2.3.4**

Merge pull request #408 from helloextend/M247-CSP-bugfix_2

Patch Release : Update installation.phtml





**2.3.3**

Merge pull request #407 from helloextend/M247-CSP-admin-bug-fix

Patch Release : Update extend-js.phtml





**2.3.2**

Patch Release : Merge pull request #406 from helloextend/admin_order_fix

Patch Release : admin layout fix for admin create order





**2.3.1**

Patch Release - Merge pull request #405 from helloextend/add_product_by_sku

Patch Release - Update sales_order_create_load_block_data.xml





**2.3.0**

Minor Release - Merge pull request #404 from helloextend/CategoryFix

Minor Release - account for url breaking characters





**2.2.8**

Patch Release - Merge pull request #402 from helloextend/202401-Hyva-backwards-compatibility

hyva compatibiliy PR





**2.2.7**

Patch Release - Merge pull request #401 from helloextend/227prep

Update Orders.php





**2.2.6**

Patch Release - Merge pull request #400 from helloextend/JM-EE-Admin-fix

Update sales_order_create_index.xml





**2.2.5**

Patch Release - Merge pull request #399 from helloextend/2.2.5-merge

addresses an issue in the admin when creating an order for a warranty only item and check/money order payment method is disabled.





**2.2.4**

Patch Release merge pull request #397 from develop to master.

PR for configurable product fix to master

fix bug for configurable products

mageSwatchRenderer is only for m2.4.3 and below
mage-SwatchRenderer is for m2.4.4 and above

Merge pull request #396 from helloextend/master

08.07.23 Master to Develop





**2.2.3**

Patch Release - Merge pull request #395 from helloextend/martin-bugfix07/24

Bug Fix for 





**2.2.2**

Patch Release - Merge pull request #394 from helloextend/martin-update07/24]

Patch Release - updated version_update.py to update composer.json version

SEINT-1701 Updated highest PHP version to 8.2

Merge pull request #391 from helloextend/EX-484

[EX-484] [M2] Pass 'price' and 'category' parameters & values to the …

Merge pull request #389 from helloextend/EX-484

Ex 484

Merge pull request #388 from helloextend/EX-466

Ex 466

Merge pull request #385 from helloextend/EX-477

Ex 477

Merge pull request #383 from helloextend/EX-466

Ex 466

Update composer.json

Update composer.json version to v2.2.1

Merge pull request #379 from helloextend/EX-486

[EX-486] [M2] Missing logging for add to cart failures, and overall n…

Merge pull request #380 from helloextend/EX-487

[EX-487] [M2] cart and minicart normalization not running after remov…




**2.2.1**

Patch Release Merge pull request #382 from helloextend/develop

05.08 Develop to Master Patch Release

Update composer.json

Update composer.json version to v2.2.1

Merge pull request #379 from helloextend/EX-486

[EX-486] [M2] Missing logging for add to cart failures, and overall n…

Merge pull request #380 from helloextend/EX-487

[EX-487] [M2] cart and minicart normalization not running after remov…





**2.2.0**

Minor Release Merge pull request #340 from helloextend/develop

Develop to master - Apr 2023

Merge pull request #376 from helloextend/EX-479

[EX-479] [M2] Orders POST is not providing the correct Product Purcha…

Merge pull request #375 from helloextend/EX-479

EX-425  [M2] Orders POST is not providing the correct Product Purchase Price, while other fields are missing from the line item or order header

Merge pull request #372 from helloextend/EX-476

[EX-476][M2] Default Contract Event and Contract Create Mode Incorrec…

Merge pull request #371 from helloextend/EX-471

[EX-471] [M2] Configuration Labels changes

Merge pull request #366 from helloextend/EX-472

[EX-472] [M2] M2.4.6 incompatibility

Merge pull request #368 from helloextend/EX-473

[EX-473] The contract payload  shows a 3 character instead of 2 chara…

Merge pull request #365 from helloextend/EX-472

[EX-472] [M2] M2.4.6 incompatibility

Merge pull request #362 from helloextend/EX-474

[EX-474] [M2] Admin/Telesales Lead Token always showing, even after being consumed

Merge pull request #361 from helloextend/EX-453

[EX-453] [M2] - Update Orders Create API POST to send additional Prod…

Merge pull request #357 from helloextend/EX-465

Ex 465

Merge pull request #355 from helloextend/EX-449

[EX-449] [M2] Set Region and Locale Parameters for SDK based on User'…

Merge pull request #353 from helloextend/EX-449

[EX-449] [M2] Set Region and Locale Parameters for SDK based on User'…

Update composer.json

Merge pull request #351 from helloextend/EX-449

[EX-449] [M2] Set Region and Locale Parameters for SDK based on User'…





**2.1.0**

Minor Release - Merge pull request #343 from helloextend/feature/github-action-update

Feature/GitHub action update





**2.2.0**

Minor Release - Merge pull request #342 from helloextend/feature/gitub-action-update

bug fix for version_update.py





**2.1..0**

Minor Release - Merge pull request #341 from helloextend/feature/gitub-action-update




**2.1.2**

Minor Release Merge pull request #332 from helloextend/develop

Develop to Master - Feb 17 2023

Update user in master-changelog

uploaded github actions



