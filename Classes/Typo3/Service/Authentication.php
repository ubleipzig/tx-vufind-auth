<?php
/**
 * Class Authentication
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

namespace Ubl\VufindAuth\Typo3\Service;

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Authentication
 *
 * @package Ubl\VufindAuth\Typo3\Service
 */
class Authentication extends \TYPO3\CMS\Sv\AbstractAuthenticationService
{
	const AUTHENTICATION_SUCCEEDED = 200;
	const AUTHENTICATION_FAILED = 0;

	/**
	 * The object manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * The vufind session service
	 *
	 * @var \Ubl\VufindAuth\Domain\Service\VufindSessionService
	 * @inject
	 */
	protected $vufindSessionService;

	/**
	 * The typo3 db connection
	 *
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $db;

	/**
	 * Where the users and groups are stored
	 *
	 * @var int
	 */
	protected $storagePid = 0;

	/**
	 * The user we fetched from the database
	 *
	 * @var array
	 */
	protected $user = false;

	/**
	 * The groups we fetched from the database
	 *
	 * @var array
	 */
	protected $groups = [];

	/**
	 * Initializes the authentication service
	 *
	 * @return bool
	 */
	public function init()
	{
		$this->db = $GLOBALS['TYPO3_DB'];
		if (!$this->objectManager) {
			$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
		}
		$extensionUtility = $this->objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
		$this->storagePid = (int)$extensionUtility->getCurrentConfiguration($this->info['extKey'])['pid']['value'];
		try {
			$this->vufindSessionService = $this->objectManager->get('Ubl\VufindAuth\Domain\Service\VufindSessionService');
			$this->vufindSessionService->connectDb();
			$this->createGroups();
			$this->createOrUpdateUser();
		} catch (\Exception $e) {
			// do something or let it be ... for example make it aware to backend users/admins
		}
		// always return true since we want to provide a working service
		return true;
	}

	/**
	 * Creates a user if not already existing or updates it
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function createOrUpdateUser()
	{
		$user = $this->vufindSessionService->getUser();

		$user_table = $this->db_user['table'];

		$userRow = array('crdate' => mktime($user['created']),
			'tstamp' => time(),
			'pid' => $this->storagePid,
			'uid' => $user['username'],
			'username' => $user['cat_username'],
			'first_name' => $user['firstname'],
			'last_name' => $user['lastname'],
			'email' => (empty($user['email']) ? 'test@example.com' : $user['email']),
			'name' => sprintf('%s %s', $user['firstname'], $user['lastname']),
			'usergroup' => join(', ', array_map(function ($item) {
				return $item['uid'];
			}, $this->groups)),
		);

		$result = $this->db->exec_SELECTgetSingleRow(
			'uid',
			'fe_users',
			sprintf('pid = %d AND uid = %d', $this->storagePid,
				(int)$userRow['uid'])
		);

		if (is_array($result) && isset($result['uid'])) {
			$this->db->exec_UPDATEquery(
				'fe_users',
				sprintf('pid = %d AND uid = %d', $this->storagePid, (int)$result['uid']),
				$userRow
			);
		} else {
			$this->db->exec_INSERTquery('fe_users', $userRow);
		}

		$this->user = $this->db->exec_SELECTgetSingleRow(
			'*',
			'fe_users',
			sprintf('pid = %d AND uid = %d', $this->storagePid, (int)$userRow['uid'])
		);
	}

	/**
	 * Creates user groups
	 *
	 * @return void
	 */
	protected function createGroups()
	{
		$groups = ($this->vufindSessionService->getGroups() && count($this->vufindSessionService->getGroups()) > 0)
			? $this->vufindSessionService->getGroups()
			: ['vufind_users'];

		$groupList = implode(', ', array_map(function ($item) {
			return $this->db->fullQuoteStr($item, 'fe_groups');
		}, $groups));

		$groupRows = $this->db->exec_SELECTgetRows(
			'uid, title',
			'fe_groups',
			sprintf('pid = %d AND title IN(%s)', $this->storagePid, $groupList)
		);

		if ($groupRows === null) {
			$groupRows = [];
		}

		$titles = array_map(function ($item) {
			return $item['title'];
		}, $groupRows);

		$newGroups = array_filter($groups, function ($item) use ($titles) {
			return !in_array($item, $titles);
		});

		if (count($newGroups) > 0) {
			$result = $this->db->exec_INSERTmultipleRows(
				'fe_groups',
				['pid', 'title', 'description'],
				array_map(function ($item) {
					return [$this->storagePid, $item, 'automatically added by VufindAuthenticationService'];
				},
				$newGroups)
			);

			$groupList = implode(
				', ',
				array_map(function ($item) {
					return $this->db->fullQuoteStr($item, 'fe_groups');
				},
				$newGroups)
			);
			$groupRows += $this->db->exec_SELECTgetRows(
				'uid, title',
				'fe_groups',
				sprintf('pid = %d AND title IN(%s)', $this->storagePid, $groupList)
			);
		}
		$this->groups = $groupRows;
	}

	/**
	 * Gets the user automatically
	 *
	 * @return array|false
	 */
	public function getUser()
	{
		return $this->user;
	}

	/**
	 * Authenticate a user. If the user is authenticated, return 200 no more checks are
	 * needed otherwise authentication failed.
	 *
	 * @param array $user
	 * @return integer
	 */
	public function authUser($user)
	{
		// if there is a user authenticated by another auth service (we distinguish by storageId)
		if ($user && $user['pid'] !== $this->storagePid) {
			return self::AUTHENTICATION_SUCCEEDED;
		}

		// if we found an authenticated user by session
		if ($this->user) {
			return self::AUTHENTICATION_SUCCEEDED;
		}
		// else
		return self::AUTHENTICATION_FAILED;
	}

	/**
	 * Get the group list
	 *
	 * @param string $user
	 * @param array $knownGroups
	 * @return array
	 */
	public function getGroups($user, $knownGroups)
	{
		$result = [];
		foreach ($this->groups as $group) {
			$result[$group['uid']] = $group['title'];
		}

		return $result;
	}
}
