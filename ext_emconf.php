<?php

$EM_CONF[$_EXTKEY] = [
	'title' => 'Vufind Authentication',
	'description' => 'Authenticates users based on authenticated vufind session',
	'category' => 'services',
	'version' => '3.2.0',
	'state' => 'stable',
	'author' => 'Ulf Seltmann',
	'author_email' => 'bdd_dev@ub.uni-leipzig.de',
	'author_company' => 'Leipzig University Library',
	'constraints' => [
		'depends' => [
			'php' => '7.4.0-8.1.99',
			'typo3' => '9.5.0-10.4.99',
		],
		'conflicts' => [
		],
		'suggests' => [
		],
	],
	'autoload' =>
  [
    'psr-4' =>
    [
      'Ubl\\VufindAuth\\' => 'Classes',
    ],
  ],
  'autoload-dev' =>
  [
    'psr-4' =>
    [
      'Ubl\\VufindAuth\\Tests' => 'Tests',
    ],
  ],
];

