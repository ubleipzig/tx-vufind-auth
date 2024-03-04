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
use \TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use Ubl\VufindAuth\Domain\Repository\VufindUserRepository;

/**
 * Class Authentication
 *
 * @package Ubl\VufindAuth\Typo3\Service
 */
class Authentication extends \TYPO3\CMS\Core\Authentication\AbstractAuthenticationService
{
	const AUTHENTICATION_SUCCEEDED = 200;
	const AUTHENTICATION_FAILED = 0;

	/**
	 * The object manager
	 *
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @Exbase\Inject
	 */
	protected $objectManager;

	/**
	 * The vufind session service
	 *
	 * @var \Ubl\VufindAuth\Domain\Service\VufindSessionService
	 * @Extbase\Inject
	 */
	protected $vufindSessionService;

	/**
	 * frontendUserRepository
	 *
	 * @var \Ubl\VuFindAuth\Domain\Repository\VufindUserRepository
	 * @Exbase\Inject
	 */
		protected $frontendUserRepository;

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
	 * Constructor initialize repositories
	 *
	 * @return void
	 * @access public
	 */
	public function __construct()
	{
		$this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
		$this->frontendUserRepository = $this->objectManager->get(VufindUserRepository::class);
	}

	/**
	 * Initializes the authentication service
	 *
	 * @return bool
	 */
	public function init()
	{
			if (!$this->objectManager) {
					$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
			}
			if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0', '<=')) {
					$extensionUtility = $this->objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
					$this->storagePid = (int)$extensionUtility->getCurrentConfiguration($this->info['extKey'])['pid']['value'];
			} else {
					$pid = (int)GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
							->get('vufind_auth', 'pid');
					$this->storagePid = $pid['value'];
			}

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
		// Get data from VuFind session
		$user = $this->vufindSessionService->getUser();

		// Collect data for update or insert user
		$userData = [
			'crdate' => mktime($user['created']),
			'tstamp' => time(),
			'pid' => $this->storagePid,
			'uid' => $user['username'],
			'username' => $user['cat_username'],
			'usergroup' => join(', ', array_map(function ($item) {
				return $item['uid'];
			}, $this->groups)),
		];

		// Check if user already exists
		$result = $this->frontendUserRepository->findUserByPidAndUid(
			$this->storagePid,
			(int)$user['username']
		);

		if (is_array($result) && isset($result['uid'])) {
			$this->frontendUserRepository->updateUserByPidAndUid(
				$userData,
				$this->storagePid,
				(int)$result['uid']
			);
		} else {
			$this->frontendUserRepository->insertUser($userData);
		}

		// Get latest saved user data anew
    // @to-do Query isn't really necessary and could be saved on by better data management
		$this->user = $this->frontendUserRepository->findUserByPidAndUid(
			$this->storagePid,
			(int)$result['uid']
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

		$groupRows = $this->frontendUserRepository->findUsersByPidAndGroups(
			$this->storagePid,
			$groups
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
			$this->frontendUserRepository->insertGroups(
				$this->storagePid,
				$newGroups,
				'automatically added via Vufind authentication service'
			);

			$groupRows += $this->frontendUserRepository->findUsersByPidAndGroups(
				$this->storagePid,
				$newGroups
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
