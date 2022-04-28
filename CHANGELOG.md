# Changelog

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
