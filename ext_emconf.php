<?php

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Vufind Authentication',
	'description' => 'Authenticates users based on authenticated vufind session',
	'category' => 'services',
	'version' => '1.0.1',
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
);

