# Mrmage_FilterOverride (for Magento v. 1.9.x)

## Override default Magento filter index process.

* Index only "size" attribute for simple products
* Index should work as is for the rest types of products

## Installation

	modman clone git@github.com:antviktorov/mrmage-filter-override.git

	or

	cd .modman
	git clone git@github.com:antviktorov/mrmage-filter-override.git
	cd ../
	modman deploy mrmage-filter-override

## Enable extension

* Login in Magento admin panel
* Clear the cache
* Logout from admin
* Login again and go to "System -> Configuration -> MRMAGE -> Filter Override"
* In general section set "Enable" to "Yes"