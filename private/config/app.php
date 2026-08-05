<?php
return [
    'app' => [
        'url' => 'http://localhost',
        'env' => 'production', // development | production
        'debug' => true,
        'site_linked' => true,
        'default_lang' => 'pt-BR',
        'theme_color' => 'default',
        'timezone' => 'America/Sao_Paulo',
        'db_timezone' => 'America/Sao_Paulo',
        'panel_url' => '/public/ucp',
		'discord_link' => 'https://discord.gg/hZ3yCmMa',
    ],

    'contact' => [
        'donate_email' => 'seu@email.com',
    ],
	'auth' => [
		'accounts_table' => 'accounts',
		'characters_table' => 'characters',
	
		'login_column' => 'login',
		'password_column' => 'password',
		'email_column' => 'email',
		'characters_account_column' => 'account_name',
	
		// plain | md5 | sha1 | sha1_base64
		'password_mode' => 'sha1_base64',
	
		'use_email' => false,
		'default_access_level' => 0,
	],
];