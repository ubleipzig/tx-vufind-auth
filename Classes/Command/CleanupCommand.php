<?php
declare(strict_types=1);
/**
 * Class CleanupCommand
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ubl\VufindAuth\Domain\Repository\VufindUserRepository;

/**
 * Class CleanupCommand
 *
 * Provides commandline interface to remove users
 *
 * @package Ubl\Booking\Command
 */
class CleanupCommand extends Command
{
		/**
		 * Repository of frontend users
		 *
		 * @Extbase\Inject
		 * @var Ubl\VufindAuth\Domain\Repository\VufindUserRepository
		 */
		protected $vufindUserRepository;

		/**
		 * The object manager
		 *
		 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
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
		 * Initialize Repositories
		 *
		 * @return void
		 * @access protected
		 */
		protected function initializeRepositories()
		{
			$this->storagePid = (int)GeneralUtility::makeInstance(ExtensionConfiguration::class)
				->get('vufind_auth', 'pid');
			$this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
			$this->vufindUserRepository = $this->objectManager->get(VufindUserRepository::class);
		}

		/**
		 * Configure the command by defining the name, options and arguments
		 *
		 * @return void
		 * @access public
		 */
		public function configure()
		{
			$this
				->setDescription('Removes users of frontend tables at a defined interval.')
				->setHelp('')
				->addOption(
					'days',
					'd',
					InputOption::VALUE_REQUIRED,
					'Pass this option to set time interval defined in days for removing users. Default is 60.',
					'60'
				)->addOption(
					'dry-run',
					null,
					InputOption::VALUE_NONE,
					'If this option is set, the database will not be processed.'
			);
		}

		/**
		 * Clean up and removes user from frontend user table to prevent collecting an amount of trusted data
		 *
		 * @param InputInterface $input
		 * @param OutputInterface $output
		 *
		 * @access protected
		 * @return int
		 */
		protected function execute(InputInterface $input, OutputInterface $output) : int
		{
			$this->initializeRepositories();
			$isDryRun = $input->getOption('dry-run') != false ? true : false;

			$io = new SymfonyStyle($input, $output);
			$io->title($this->getDescription());
			if ($isDryRun === true) {
				$io->writeln('<info>This is a dry-run. Data will not be removed.</info>');
			}
			$days = $input->getOption('days');

			$dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
			$current = $dt->modify('midnight');
			$ts = $current->sub(new \DateInterval("P{$days}D"));
			$userList = $this->vufindUserRepository->findAbsenceOfUsersBeforeTime($ts, $this->storagePid);
			$uids = array_chunk(
				array_column($userList, 'uid'),
				$this->sqlOperatingChunksize
			);
			$cnt = 0;
			if ($isDryRun === false) {
				foreach ($uids as $package) {
					$cnt += $this->vufindUserRepository->removeUsersByIds($package);
					sleep(5);
				}
			} else {
				$cnt = count($userList);
			}
			$io->writeln(
				sprintf('%d users removed before %s', $cnt, $ts->format('d.m.Y H:i:s'))
			);
			return 0;
		}
}