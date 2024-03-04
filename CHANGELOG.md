# Changelog

## [v3.1.0](https://github.com/ubleipzig/tx-vufind-auth/tree/3.1.0)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/3.0.2...3.1.0)

* fixes deprecated @inject annotation cmp. [82869](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.0/Feature-82869-ReplaceInjectWithTYPO3CMSExtbaseAnnotationInject.html)
* removes support for typo3 v8
* refactoring of cleanup users task to new [Symfony Console Commands](https://docs.typo3.org/m/typo3/reference-coreapi/9.5/en-us/ApiOverview/CommandControllers/Index.html)
* changes namespace for _AbstractAuthenticationService_ to _\TYPO3\CMS\Core\Authentication_
* removes _$GLOBAL['TYPO3_DB']_ from _Authentication.php_ [80929](https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/9.0/Breaking-80929-TYPO3_DBMovedToExtension.html)  
	* replaced by methods at _VufindUserRepository_ 

## [v3.0.2](https://github.com/ubleipzig/tx-vufind-auth/tree/3.0.2)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/3.0.1...3.0.2)

* fixes initializing extension configuration by new class _\TYPO3\CMS\Core\Configuration\ExtensionConfiguration_

## [v3.0.1](https://github.com/ubleipzig/tx-vufind-auth/tree/3.0.1)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/3.0.0...3.0.1)

* Fixes bug at SQL update typo3 method at VufindSessionService.php 

## [v3.0.0](https://github.com/ubleipzig/tx-vufind-auth/tree/3.0.0)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.1.0...3.0.0)

* Removes support for typo3 v7 and adds support for typo3 v9
* Implements Doctrine DBAL statements for Typo3 task **VuFindAuth Cleanup cleanupFrontendUser** 

## [v2.1.0](https://github.com/ubleipzig/tx-vufind-auth/tree/2.1.0)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.0.4...2.1.0)

* Adds **VuFindAuth Cleanup cleanupFrontendUser** task to remove records from _fe_users_ table of an indicated amount of days.  

## [v2.0.4](https://github.com/ubleipzig/tx-vufind-auth/tree/2.0.4)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.0.3...2.0.4)

* Removes writing of _first_name_, _last_name_, _name_ and _email_ at _fe_users_ table of typo3 from VuFind session. 

## [v2.0.3](https://github.com/ubleipzig/tx-vufind-auth/tree/2.0.3)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.0.2...2.0.3)

* Adds trim() to database parameters at initalizing object due to restricted possibilities to manipulate ext_conf_template.txt 
* Fixes outdated array typology  

## [v2.0.2](https://github.com/ubleipzig/tx-vufind-auth/tree/2.0.2)

[Diff Changelog](https://github.com/ubleipzig/tx-vufind-auth/compare/2.0.1...2.0.2)

* Removes Typo3 v6.2 support due to failed support of composer dependency manager  
* Adjusts README.md to current VuFind changes
* Introducing some PSR2 coding standards 
