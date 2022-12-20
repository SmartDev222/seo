<?php

$isCronOutdated = $this->studio->getopt('cron-last-run') < (time() - 43200);

return [
	'customize' => [
		'title' => "Customize",
		'description' => "Change the appearance of the website",
		'links' => [
			[
				'title' => 'Branding',
				'url' => 'customize/branding.php'
			],
			[
				'title' => 'Navigation',
				'url' => 'customize/navigation.php'
			],
			[
				'title' => 'Colors',
				'url' => 'customize/colors.php'
			]
		]
	],
	'content' => [
		'title' => 'Content',
		'description' => 'Manage content throughout the website',
		'links' => [
			[
				'title' => 'Languages',
				'url' => 'content/languages/index.php'
			],
			[
				'title' => 'Pages',
				'url' => 'content/pages/index.php'
			],
			[
				'title' => 'Tools',
				'url' => 'content/tools/index.php'
			],
			[
				'title' => 'Advertising',
				'url' => 'content/advertising.php'
			]
		]
	],
	'users' => [
		'title' => 'Users',
		'description' => 'Manage users and groups',
		'links' => [
			[
				'title' => 'Users',
				'url' => 'users/index.php'
			],
			[
				'title' => 'Groups',
				'url' => 'users/groups/index.php'
			],
			[
				'title' => 'Tool usage',
				'url' => 'users/stats/usage.php'
			]
		]
	],
	'services' => [
		'title' => 'Services',
		'description' => 'Manage services and integrations',
		'links' => [
			[
				'title' => 'Cron',
				'url' => 'services/cron.php',
				'issues' => $isCronOutdated ? 1 : 0
			],
			[
				'title' => 'Google',
				'url' => 'services/google.php'
			]
		]
	],
];
