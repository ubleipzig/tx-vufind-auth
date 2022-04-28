<?php
/**
 * Class VufindSessionService
 *
 * Copyright (C) Leipzig University Library 2022 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
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

namespace Ubl\VufindAuth\Command;

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Mvc\Controller\CommandController;


/**
 * Class CleanupCommandController
 *
 * Provides commandline interface to cleanup past bookings
 *
 * @package Ubl\Booking\Command
 */
class CleanupCommandController extends CommandController
{
		/**
		 * Repository of frontend users
		 *
		 * @var Ubl\VufindAuth\Domain\Repository\VufindUserRepository
		 * @inject
		 */
		protected $vufindUserRepository;

		/**
		 * The object manager
		 *
		 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
		 * @inject
		 */
		protected $objectManager;

		/**
		 * Size of chunk for large scales sql queries
		 *
		 * @var int sqlOperatingChunksize
		 * @access protected
		 */
		protected $sqlOperatingChunksize = 5000;

		/**
		 * Where the users and groups are stored
		 *
		 * @var int
		 */
		protected $storagePid = 0;

		/**
		 * Constructor
		 *
		 * @return void
		 */
		public function __construct()
		{
				if (!$this->objectManager) {
						$this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
				}
				$extensionUtility = $this->objectManager->get('TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility');
				$this->storagePid = (int)$extensionUtility->getCurrentConfiguration('vufind_auth')['pid']['value'];
		}

		/**
		 * Clean up and removes user from frontend user table to prevent collecting an amount of trusted data
		 *
		 * @param int $days 	How long user in fe_users should be kept. Default is 60 days.
		 *
		 * @return void
		 */
		public function cleanupFrontendUserCommand($days = 60)
		{
				$dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
				$current = $dt->modify('midnight');
				$ts = $current->sub(new \DateInterval("P{$days}D"));
				$userList = $this->vufindUserRepository->findAbsenceOfUsersBeforeTime($ts, $this->storagePid);
				$uids = array_chunk(
						array_column($userList, 'uid'),
						$this->sqlOperatingChunksize
				);
				$cnt = 0;
				foreach ($uids as $package) {
						$cnt += $this->vufindUserRepository->removeUsersByIds($package);
						sleep(5);
				}
				$this->outputLine('%d users removed before %s', [$cnt, $ts->format('d.m.Y H:i:s')]);
		}
}