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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;


class VufindUserRepository extends FrontendUserRepository
{
		/**
		 * Get connection for table
		 *
		 * @param string $tbl	Table name
		 *
		 * @return TYPO3\CMS\Core\Database\Connection
		 * @access protected
		 */
		protected function getConnectionForTable($tbl)
		{
				/** @var ConnectionPool $connectionPool */
				return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tbl);
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
						$queryBuilder = $this->getConnectionForTable('fe_users');
						return $queryBuilder
								->select('uid')
								->from('fe_users')
								->where(
									$queryBuilder->expr()->eq(
											'pid',
											$queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
									)
								)
								->andWhere(
										$queryBuilder->expr()->lt(
												'lastlogin',
												$time->getTimestamp()
										)
								)
								->execute()
								->fetchAll();
				} catch (\Exception $e) {
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
				try {
						$deleteList = implode(
								', ',
								array_map(function ($item) {
										return "'" . $item . "'";
								},
								$uids)
						);
						$queryBuilder = $this->getConnectionForTable('fe_users');
						return $queryBuilder
							->delete('fe_users')
							->where(
									$queryBuilder->expr()->in('uid', $deleteList)
							)
							->execute();
				} catch (Exception $e) {
						'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
				}
		}
}
