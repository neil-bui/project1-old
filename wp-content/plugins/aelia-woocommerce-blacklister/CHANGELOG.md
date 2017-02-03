# Aelia Blacklister - Change Log

## Version 0.x
####0.8.6.150612
* Updated build file.
* Updated requirement checking class.

####0.8.5.150112-beta
* Improved compatibility with WooCommerce 2.3:
	* Fixed error caused by the sudden removal of a `WooCommerce::add_error()` method from WC core.
* Updated requirements.

####0.8.4.140819-beta
* Updated logic used to for requirements checking.

####0.8.3.140731-beta
* Fixed text domain and plugin name in `Aelia_WC_Blacklister_RequirementsChecks` class.

####0.8.2.140711-beta
* Removed debug code.

####0.8.1.140619-beta
* Modified loading of Aelia_WC_RequirementsChecks class to work around quirks of Opcode Caching extensions, such as APC and XCache.

####0.8.0.140517-beta
* Refactored plugin to depend on Aelia Foundation Classes.
* Fixed bug that caused conflicts in WordPress Admin UI.
* Fixed CSS for plugin's options page.
* Removed unneeded files.

####0.4.3.140419-beta
* Updated base classes.
* Fixed notice error related to text domain.

####0.4.2.140419-beta
* Updated base classes.

####0.4.1.140120-beta
* Fixed bug in that prevented the plugin from being activated.

####0.4.0.140108-beta
* Added possibility of specifying custom messages to display when user is blocked.

####0.3.0.140107-alpha
* Refactored rendering of admin pages to improve compatibility with 3rd party plugins.

####0.2.0.140106-alpha
* Added validation of billing email and visitor's IP address.

####0.1.1.140103-alpha
* First plugin draft.
