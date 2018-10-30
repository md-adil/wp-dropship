#!/usr/bin/env php
<?php
chdir($_SERVER['HOME']);

$plugin = 'bigly-dropship';

$pluginRepository = 'https://plugins.svn.wordpress.org/bigly-dropship';

if(!is_dir($plugin)) {
	echo 'Plugin not found, Cloning...' . PHP_EOL;
	echo shell_exec('svn co ' . $pluginRepository);
} else {
	chdir($plugin);
	echo shell_exec('svn update');
}

