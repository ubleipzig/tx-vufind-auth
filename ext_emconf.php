<?php

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Vufind Authentication',
	'description' => 'Authenticates users based on authenticated vufind session',
	'category' => 'services',
	'version' => '2.0.0',
	'state' => 'stable',
	'author' => 'Ulf Seltmann',
	'author_email' => 'seltmann@ub.uni-leipzig.de',
	'author_company' => 'Leipzig University Library',
	'constraints' => array (
		'depends' => array (
			'typo3' => '6.2.1-7.99.99',
		),
		'conflicts' => array (
		),
		'suggests' => array (
		),
	),
	'autoload' =>
  array(
    'psr-4' =>
    array(
      'Ubl\\VufindAuth\\' => 'Classes',
    ),
  ),
  'autoload-dev' =>
  array(
    'psr-4' =>
    array(
      'Ubl\\VufindAuth\\Tests' => 'Tests',
    ),
  ),

);

