<?php
/**
 * Class VufindUserRepository
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
	 * Name of table
	 *
	 * @var string $tableName
	 * @access protected
	 */
	protected $tableName = 'fe_users';

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
						$queryBuilder = $this->getConnectionForTable($this->tableName);
						return $queryBuilder
								->select('uid')
								->from($this->tableName)
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
	 * Find users by pid and uid
	 *
	 * @param int $pid
	 * param int $uid
	 *
	 * @return array Return array with all uids
	 * @access public
	 *
	 */
		public function findUserByPidAndUid(int $pid, int $uid)
		{
			try {
				$queryBuilder = $this->getConnectionForTable($this->tableName);
				return $queryBuilder
					->select('*')
					->from($this->tableName)
					->where(
						$queryBuilder->expr()->eq(
							'pid',
							$queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
						)
					)
					->andWhere(
						$queryBuilder->expr()->eq(
							'uid',
							$queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
						)
					)
					->execute()
					->fetch();
			} catch (\Exception $e) {
				'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
			}
		}

	/**
	 * Find all users by pid and group titles
	 *
	 * @param int $pid
	 * @param array $groups
	 *
	 * @return array
	 * @access public
	 */
		public function findUsersByPidAndGroups(int $pid, array $groups)
		{
			try {
				$groupList = $this->createCommaSeparatedList($groups);
				$queryBuilder = $this->getConnectionForTable($this->tableName);
				return $queryBuilder
					->select('uid','title')
					->from($this->tableName)
					->where(
						$queryBuilder->expr()->eq(
							'pid',
							$queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
						)
					)
					->andWhere(
						$queryBuilder->expr()->in('title', $groupList)
					)
					->execute()
					->fetchAll();

			} catch (Exception $e) {
				'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
			}
		}

	/**
	 * Insert groups in frontend user table
	 *
	 * @param int $pid
	 * @param array $groups New groups which should be inserted
	 * @param string $description Optional
	 *
	 * @return int Return affected rows by insert
	 * @access public
	 */
	public function insertGroups(int $pid, array $groups, string $description = "")
	{
		try {
			$cnt = 0;
			$queryBuilder = $this->getConnectionForTable($this->tableName);
			foreach ($groups as $group) {
				$cnt +=	$queryBuilder
						->insert($this->tableName)
						->values([
							'pid' => $pid,
							'title' => $group,
							'description' => $description,
						])
						->execute();
			}
			return $cnt;

		} catch (Exception $e) {
			'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
		}
	}

	/**
	 * Insert user in frontend user table
	 *
	 * @param array $userData with user data to insert
	 *
	 * @return int Return affected rows by insert
	 * @access public
	 */
	 public function insertUser(array $userData)
	 {
		 try {
			 $queryBuilder = $this->getConnectionForTable($this->tableName);
			 return $queryBuilder
				 ->insert($this->tableName)
				 ->values($userData)
				 ->execute();
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
				try {
						$deleteList = $this->createCommaSeparatedList($uids);
						$queryBuilder = $this->getConnectionForTable($this->tableName);
						return $queryBuilder
							->delete($this->tableName)
							->where(
									$queryBuilder->expr()->in('uid', $deleteList)
							)
							->execute();
				} catch (Exception $e) {
						'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
				}
		}

	/**
	 * Updates user in frontend user table
	 *
	 * param array $userData Array with user data
	 * param int $pid
 	 * param int $uid
	 *
	 * @return void
	 * @access public
	 */
	public function updateUserByPidAndUid(array $userData, int $pid, int $uid)
	{
		try {
			$queryBuilder = $this->getConnectionForTable($this->tableName);
			return $queryBuilder
				->update($this->tableName)
				->where(
					$queryBuilder->expr()->eq(
						'pid',
						$queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
					)
				)
				->andWhere(
					$queryBuilder->expr()->eq(
						'uid',
						$queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
					)
				)
				->set('crdate', $userData['crdate'])
				->set('tstamp', $userData['tstamp'])
				->set('pid', $userData['pid'])
				->set('uid', $userData['uid'])
				->set('username', $userData['username'])
				->set('usergroup', $userData['usergroup'])
				->execute();

		} catch (Exception $e) {
			'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
		}
	}

	/**
	 * Creates comma separated quoted list for e.g. mysql queries with IN clause
	 *
	 * @param array $array
	 * @return string
	 *
	 * @return string
	 * @access private
	 */
	private function createCommaSeparatedList(array $array): string
	{
		return implode(
			', ',
			array_map(function ($item) {
				return "'" . $item . "'";
			},
				$array)
		);
	}
}
