# Vufind Authentication SSO

This extension enables [typo3][1] to use [vufind][2] for frontend user authentication.
After logging in into the catalog this extension reads the session-cookie and
retrieves all necessary information from the vufind-database to identify an
authorized user, assuming the requirements are met.

## Requirements

* vufind >= v2
* typo3 >= 7.0.0
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

Most simply way to provide this is by installing the packages with composer e.g.

```bash
COMPOSER_VENDOR_DIR="Packages/Libraries" composer require zendframework/zend-stdlib
```

Be aware that you have to manually adjust the vendor-dir within the composer.json in order to keep
the folder for further installations.
if you have a composer-enabled typo3-installation you probably might be fine with the defaults.

## Preparing VuFind

At first be aware that your Typo3 host has an access to the VuFind database. Therefor we prefer an own user with restricted rules to the VuFind tables _session_ and _user_.    

### Database as Session Storage

VuFind has a simple option to store the session-data into its own database.
therefore one has to set the option `type` on 'Database' in section `Session` at `config.ini` and keep the session unencrypted.

```ini
[Session]
type = Database
secure = false

```

### Cookie Domain

In order to enable Typo3 to read the vufind-cookie we have to set the cookie-domain
to the shared domain value. This is done by the option `domain` in the `Cookies` section:

```ini
[Cookies]
domain = ".example.edu"
```

## Configuring the extension

It is necessary to configure _tx-vufind-auth_ at the extension manager.

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

## Command line tools

### Cleanup users

There is a cleanup-command on typo3's commandline interface to remove outdated user data from _fe_users_ table. 

Be aware that the concept of saving users at fe_users for VuFind only depends on needs of other extensions at Typo3. Therefore calculate well between requirements of applications and data economy due to risk holding trusted personal data.

Parameters:

```
--days int (Default: 60) Amount of days to keep the record of a user since last login.
```

#### Instruction for setting up at backend

Go to *Scheduler->Add Task*
* At *Class* choose **Extbase-CommandController-Task**
* At *Frequency* specify how often and in which period scheduler task should be run. (Seconds or cronjob settings required.)  
* At select box of *CommandController Command* choose **VuFindAuth Cleanup: cleanupFrontendUser** 
* On next step save the task! This is important to display the form element for additional arguments of command line tool.
* Scroll down and specify the amount of **days** to keep the record since last login of user. Default are _60_ days. 
* Save the task again!

For developing issue it is also possible to run the task on a terminal.  