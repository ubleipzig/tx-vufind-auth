# Vufind Authentication SSO

This extension enables [typo3][1] to use [vufind][2] for authentication. 
After logging in into the catalog this extension reads the session-cookie and 
retrieves all necessary information from the vufind-database to identify an 
authorized user, assuming the requirements are met.
 
## Requirements

* vufind >= v2
* typo3 >= 6.2.1
* vufind-database is mysql
* vufind-session is stored into database
* typo3-installation has access to vufind-database
* a shared cookie-domain

## preparing vufind

### database as session storage 
vufind has a simple option to store the session-data into its own database.
therefor one has to set the option `type` in section `Session` in the `config.ini`

```
[Session]
type                        = mysql
```

### cookie domain
in order to enable typo3 to read the vufind-cookie we have to set the cookie-domain
to the shared domain value. this is done by the option `domain` in the `Cookies` section:
```
[Cookies]
domain = ".example.edu"
```

## configuring the extension

there are several config options to the extension

### storage
* *pid = 1*: this is the typo3-pag-id where the frontend users and groups are stored

### database
* *host = localhost*: the host where vufinds database-server is running 
* *port = 3306*: the port where vufinds database-server is running
* *name = vufind*: the vufind database name
* *user = vufind*: the user to connect with to the vufind database
* *pass*: the vufind database-user's password

## session
* *cookiename = PHPSESSID*: the name of the vufind session cookie
* *lifetime = 3600*: the session lifetime (see vufind config.ini, section `Session`, option `lifetime`)

[1]: https://typo3.org
[2]: https://vufind.org