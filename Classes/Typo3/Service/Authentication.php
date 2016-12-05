<?php
namespace UBL\VufindAuth\Typo3\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Authentication
 *
 * @package UBL\VufindAuth\Typo3\Service
 */
class Authentication extends \TYPO3\CMS\Sv\AbstractAuthenticationService
{
	const AUTHENTICATION_SUCCEEDED = 200;
	const AUTHENTICATION_FAILED = 0;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject (somehow not working)
	 */
	protected $objectManager;

	/**
	 * @var \UBL\VufindAuth\Domain\Service\VufindSessionService
	 * @inject
	 */
	protected $vufindSessionService;

	/**
	 * typo3 db connection
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * where the users and groups are stored
	 *
	 * @var int
	 */
	protected $storagePid = 0;

	/**
	 * the user we fetched from the database
	 *
	 * @var array
	 */
	protected $user = false;

	/**
	 * the groups we fetched from the database
	 *
	 * @var array
	 */
	protected $groups = [];

	/**
	 * initializes the authentication service
	 *
	 * @return bool
	 */
	public function init() {
		try {
			$this->db = $GLOBALS['TYPO3_DB'];

			if (!$this->objectManager) $this->objectManager = GeneralUtility::makeInstance('\TYPO3\CMS\ExtBase\Object\ObjectManager');
			$this->vufindSessionService = $this->objectManager->get('UBL\VufindAuth\Domain\Service\VufindSessionService');
			$extensionUtility = $this->objectManager->get('\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
			$this->storagePid = (int)$extensionUtility->getCurrentConfiguration('vufind_auth')['pid']['value'];
			$this->vufindSessionService->connectDb();
			$this->createGroups();
			$this->createOrUpdateUser();
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function createOrUpdateUser() {
		$user = $this->vufindSessionService->getUser();

		$user_table = $this->db_user['table'];

		$userRow = array('crdate' => mktime($user['created']),
			'tstamp' => time(),
			'pid' => $this->storagePid,
			'uid' => $user['username'],
			'username' => $user['cat_username'],
			'first_name' => $user['firstname'],
			'last_name' => $user['lastname'],
			'email' => (empty($user['email']) ? 'test@example.com' : $user['email']), // required! $this->getServerVar($this->extConf['mail']),
			'name' => sprintf('%s %s', $user['firstname'], $user['lastname']), // required! $this->getServerVar($this->extConf['displayName']),
			'usergroup' => join(', ', array_map(function($item) { return $item['uid']; }, $this->groups)),
		);

		$result = $this->db->exec_SELECTgetSingleRow('uid', 'fe_users', sprintf('pid = %d AND uid = %d', $this->storagePid, (int)$userRow['uid']));

		if (is_array($result) && isset($result['uid'])) {
			$this->db->exec_UPDATEquery('fe_users', sprintf('pid = %d AND uid = %d', $this->storagePid, (int)$result['uid']), $userRow);
		} else {
			$this->db->exec_INSERTquery('fe_users', $userRow);
		}

		$this->user = $this->db->exec_SELECTgetSingleRow('*', 'fe_users', sprintf('pid = %d AND uid = %d', $this->storagePid, (int)$userRow['uid']));
	}

	public function createGroups() {
		$groups = ($this->vufindSessionService->getGroups() && count($this->vufindSessionService->getGroups()) > 0)
			? $this->vufindSessionService->getGroups()
			: ['vufind_users'];

		$groupList = implode(', ', array_map(function($item) {
			return $this->db->fullQuoteStr($item, 'fe_groups');
		}, $groups));

		$groupRows = $this->db->exec_SELECTgetRows('uid, title', 'fe_groups', sprintf('pid = %d AND title IN(%s)', $this->storagePid, $groupList));

		if ($groupRows === null) $groupRows = [];

		$titles = array_map(function($item) { return $item['title']; }, $groupRows);

		$newGroups = array_filter($groups, function($item) use ($titles) {
			return !in_array($item, $titles);
		});

		if (count($newGroups) > 0) {
			$result = $this->db->exec_INSERTmultipleRows('fe_groups', ['pid', 'title', 'description'], array_map(function($item) {
				return [$this->storagePid, $item, 'automatically added by VufindAuthenticationService'];
			}, $newGroups));

			$groupList = implode(', ', array_map(function($item) {
				return $this->db->fullQuoteStr($item, 'fe_groups');
			}, $newGroups));

			$groupRows += $this->db->exec_SELECTgetRows('uid, title', 'fe_groups', sprintf('pid = %d AND title IN(%s)', $this->storagePid, $groupList));
		}

		$this->groups = $groupRows;
	}

	/**
	 * Gets the user automatically
	 *
	 * @return bool
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * Authenticate a user
	 * Return 200 if the IP is right.
	 * This means that no more checks are needed.
	 * Otherwise authentication may fail because we may don't have a password.
	 *
	 * @param array $user
	 * @return integer
	 */
	public function authUser($user)
	{
		return $this->user ? self::AUTHENTICATION_SUCCEEDED : self::AUTHENTICATION_FAILED;
	}

	/**
	 * Get the group list
	 *
	 * @param string $user
	 * @param array $knownGroups
	 * @return array
	 */
	public function getGroups($user, $knownGroups) {
		$result = [];
		foreach($this->groups as $group) {
			$result[$group['uid']] = $group['title'];
		}

		return $result;
	}
}
