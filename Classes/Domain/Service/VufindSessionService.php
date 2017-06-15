<?php
/**
 * Class VufindSessionService
 *
 * Copyright (C) Leipzig University Library 2017 <info@ub.uni-leipzig.de>
 *
 * @author  Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Ubl\VufindAuth\Domain\Service;

/**
 * Class VufindSessionService
 *
 * @package Ubl\VufindAuth\Domain\Service
 */
class VufindSessionService implements \TYPO3\CMS\Core\SingletonInterface
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

	/**
	 * Fetches the session from vufind session table and updates `last_used` field
	 *
	 * @return this
	 */
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

	/**
	 * Fetches the user from the vufind user table
	 *
	 * @return this
	 * @throws \Exception if user stored in session was not found
	 */
	protected function fetchUser() {
		$this->user = $this->dbConnection->exec_SELECTgetSingleRow(
			'id, username, cat_username, firstname, lastname, email, created', 'user', 'id = '
				. $this->dbConnection->fullQuoteStr($this->getSession()['Account']->userId, 'user')
		);

		if (!$this->user) throw new \Exception(sprintf('no user found for id \'%s\'', $this->getSession()['Account']->userId));
		return $this;
	}

	/**
	 * Initializes the object after creation by object manager
	 *
	 * @return void
	 * @throws \Excepion cookie was not found
	 */
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

	/**
	 * Returns the session id
	 *
	 * @return void
	 */
	public function getSessionId() {
		return $this->sessionId;
	}

	/**
	 * fetches the user's groups from PAIA-scope if available, otherwise sets hardcoded array ['vufind_users']
	 *
	 * @return void
	 */
	protected function fetchGroups() {
		$this->groups = $this->getSession('PAIA') && $this->getSession('PAIA')->scope ? $this->getSession('PAIA')->scope : ['vufind_users'];
	}

	/**
	 * Returns the user
	 *
	 * @return array
	 */
	public function getUser() {
		if (!$this->user) $this->fetchUser();
		return $this->user;
	}

	/**
	 * returns the session as arra yor a specific value when a key was provided
	 *
	 * @param string [optional] $key
	 * @return mixed
	 */
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

	/**
	 * returns the user's groups
	 *
	 * @return array
	 */
	public function getGroups() {
		if (!$this->groups) $this->fetchGroups();
		return $this->groups;
	}
}
