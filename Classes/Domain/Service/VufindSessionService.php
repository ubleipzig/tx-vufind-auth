<?php
namespace UBL\VufindAuth\Domain\Service;


/**
 * <copyright>
 */

/**
 * Class VufindSessionService
 *
 * @package UBL\VufindAuth\Domain\Service
 */
class VufindSessionService implements  \TYPO3\CMS\Core\SingletonInterface
{

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 * @inject
	 */
	protected $dbConnection;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility
	 * @inject
	 */
	protected $extensionUtility;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * the vufind session id taken from the cookie
	 *
	 * @var string
	 */
	protected $sessionId;

	/**
	 * the session lifetime
	 *
	 * @var integer
	 */
	protected $lifetime;
	/**
	 * hold the vufind session information if any
	 *
	 * @var array
	 */
	protected $session;

	/**
	 * holds the vufind user row
	 *
	 * @var array
	 */
	protected $user;

	/**
	 * holds the vufind groups
	 *
	 * @var array
	 */
	protected $groups;

	protected function fetchSession() {
		$sessionRow = $this->dbConnection->exec_SELECTgetSingleRow('*', 'session', sprintf('session_id = %s', $this->dbConnection->fullQuoteStr($this->getSessionId(), 'session')));


		if (!$sessionRow || !$sessionRow['data']) throw new \Exception(sprintf('no session found for session id \'%s\'', $this->getSessionId()));

		if (!$sessionRow['last_used'] || $sessionRow['last_used'] + $this->lifetime <= time()) throw new \Exception('session expired');

		session_start();
		$currentSession = $_SESSION;
		session_decode($sessionRow['data']);
		$this->session = $_SESSION;
		$_SESSION = $currentSession;

		$this->dbConnection->exec_UPDATEquery('session', sprintf('session_id = %s', $this->dbConnection->fullQuoteStr($this->getSessionId(), 'session')), ['last_used'], [time()]);
		return $this;
	}

	protected function fetchUser() {
		$this->user = $this->dbConnection->exec_SELECTgetSingleRow(
			'id, username, cat_username, firstname, lastname, email, created', 'user', 'id = '
				. $this->dbConnection->fullQuoteStr($this->getSession()['Account']->userId, 'user')
		);

		if (!$this->user) throw new \Exception(sprintf('no user found for id \'%s\'', $this->getSession()['Account']->userId));
		return $this;
	}

	public function initializeObject() {
		$config = $this->extensionUtility->getCurrentConfiguration('vufind_auth');

		$cookie_name = $config['cookiename']['value'];
		if (!$_COOKIE[$cookie_name]) throw new \Exception(sprintf('cookie "%s" not found or empty value', $cookie_name));

		$this->sessionId = $_COOKIE[$cookie_name];
		$this->lifetime = (int)$config['lifetime']['value'];
		$this->dbConnection->setDatabaseHost($config['host']['value']);
		$this->dbConnection->setDatabasePort($config['port']['value']);
		$this->dbConnection->setDatabaseName($config['name']['value']);
		$this->dbConnection->setDatabaseUsername($config['user']['value']);
		$this->dbConnection->setDatabasePassword($config['pass']['value']);
	}

	/**
	 * connects to vufind session database
	 * @throws \RuntimeException
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	public function connectDb() {
		$this->dbConnection->connectDB();
	}

	public function getSessionId() {
		return $this->sessionId;
	}

	protected function fetchGroups() {
		$this->groups = $this->getSession('PAIA') && $this->getSession('PAIA')->scope ? $this->getSession('PAIA')->scope : ['vufind_users'];
	}

	public function getUser() {
		if (!$this->user) $this->fetchUser();
		return $this->user;
	}

	public function getSession($key = false) {
		if (!$this->session) $this->fetchSession();

		if ($key && isset($this->session[$key])) {
			return $this->session[$key];
		} else if (!$key) {
			return $this->session;
		} else {
			return;
		}
	}

	public function getGroups() {
		if (!$this->groups) $this->fetchGroups();
		return $this->groups;
	}
}
