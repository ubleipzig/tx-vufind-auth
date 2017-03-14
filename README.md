# Vufind Authentication SSO

This extension enables [typo3][1] to use [vufind][2] for frontend user authentication.
After logging in into the catalog this extension reads the session-cookie and
retrieves all necessary information from the vufind-database to identify an
authorized user, assuming the requirements are met.

## Requirements

* vufind >= v2
* typo3 >= 6.2.1
* zendframework/zendstdlib >= 3.1
* vufind-database is mysql
* vufind-session is stored into database
* typo3-installation has access to vufind-database
* a shared cookie-domain

## Preparing Typo3

In order for this extension to work we need the zendframework/zend-stdlib classes available.
therefore we require an autoloader, that loads this classes for us at

```ini
<PATH_site>/Packages/Libraries/autoload.php
```

the easiest way to provide this is by installing the packages with composer e.g.

```bash
COMPOSER_VENDOR_DIR="Packages/Libraries" composer require zendframework/zend-stdlib
```

be aware that you have to manually adjust the vendor-dir within the composer.json in order to keep
the folder for further installations.
if you have a composer-enabled typo3-installation you probably might be fine with the defaults.

## Preparing VuFind

### Database as Session Storage

VuFind has a simple option to store the session-data into its own database.
therefore one has to set the option `type` in section `Session` in the `config.ini`

```ini
[Session]
type                        = mysql
```

### Cookie Domain

In order to enable Typo3 to read the vufind-cookie we have to set the cookie-domain
to the shared domain value. This is done by the option `domain` in the `Cookies` section:

```ini
[Cookies]
domain = ".example.edu"
```

## configuring the extension

there are several config options to the extension

### Storage

* *pid = 1*: this is the typo3-pag-id where the frontend users and groups are stored

### Database

* *host = localhost*: the host where vufinds database-server is running
* *port = 3306*: the port where vufinds database-server is running
* *name = vufind*: the vufind database name
* *user = vufind*: the user to connect with to the vufind database
* *pass*: the vufind database-user's password

## Session

* *cookiename = PHPSESSID*: the name of the vufind session cookie
* *lifetime = 3600*: the session lifetime (see vufind config.ini, section `Session`, option `lifetime`)

[1]: https://typo3.org
[2]: https://vufind.org
