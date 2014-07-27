<?php

if(file_exists('settings.php'))
	include('settings.php');

if(!$db)
	$db = new PDO('sqlite:database.db');

$result = $db->exec('CREATE TABLE IF NOT EXISTS `link` (
	`key` varchar(255) NOT NULL,
	`url` text NOT NULL,
	`title` text NOT NULL,
	`description` text NOT NULL,
	`image` text NULL,
	`type` char(3) NOT NULL,
	`ip` varchar(15) NOT NULL,
	`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`key`)
)');

if(!file_exists('images'))
	mkdir('images');
