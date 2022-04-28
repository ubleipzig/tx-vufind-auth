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

namespace Ubl\VufindAuth\Domain\Repository;

use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Exception;

class VufindUserRepository extends FrontendUserRepository
{
		/**
		 * The typo3 db connection
		 *
		 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
		 */
		protected $db;

		/**
		* Initializes the repository.
		*
		* @return void
		* @see \TYPO3\CMS\Extbase\Persistence\Repository::initializeObject()
		*/
		public function initializeObject()
		{
				$this->db = $GLOBALS['TYPO3_DB'];
		}

		/**
		 * Finds user which last login lasted at a specified time
		 *
		 * @param \DateTimeInterface $time Time
		 * @param int $pid	Storage PID
		 *
		 * @return array  	Return associative array with result.
		 * @access public
		 */
		public function findAbsenceOfUsersBeforeTime(\DateTimeInterface $time, int $pid)
		{
				try {
						$res = $this->db->exec_SELECTquery(
								'uid',
								'fe_users',
								'pid = '. $pid .' AND lastlogin < ' . $time->getTimestamp()
						);
						$results = [];
						while ($arr =  $this->db->sql_fetch_assoc($res)) {
							$results[] = $arr;
						}
						return $results;
				} catch (Exception $e) {
						'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
				}
		}

		/**
		 * Remove users by id
		 *
		 * @param array $uids	Uids to remove
		 *
		 * @return int 	Affected row to proceed.
		 * @access public
		 */
		public function removeUsersByIds(array $uids)
		{
				$deleteList = implode(
						', ',
						array_map(function ($item) {
								return $this->db->fullQuoteStr($item, 'fe_users');
						},
						$uids)
				);
				try {
						$this->db->exec_DELETEquery(
								'fe_users',
								sprintf('uid IN(%s)', $deleteList)
						);
						return $this->db->sql_affected_rows();
				} catch (Exception $e) {
						'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
				}
		}
}
