<?php

$db = new PDO('sqlite:database.db');

if(file_exists('settings.php'))
	include('settings.php');

// if scheme missing, create tables

function makeKey($length)
{
	$alphabet = implode(range('a', 'z')) . implode(range('0', '9'));
	$key = '';
	while(strlen($key) < $length)
		$key .= $alphabet[rand(0, strlen($alphabet) - 1)];
	return $key;
}

function showPage($page, $args = array())
{
	extract($args);
	unset($args);
	if(preg_match('/[^a-z]/i', $page) || !file_exists("$page.html"))
		die("Template not found: $page");
	include("$page.html");
	exit;
}

function makeAbsolute($url, $base)
{
	if(!$url) return $base;                                   // Return base if no url
	if(parse_url($url, PHP_URL_SCHEME) != '') return $url;    // Return if already absolute URL
	if($url[0] == '#' || $url[0] == '?') return $base . $url; // Urls only containing query or anchor
	extract(parse_url($base));                                // Parse base URL and convert to local variables: $scheme, $host, $path
	if(!isset($path)) $path = '/';                            // If no path, use /
	$path = preg_replace('#/[^/]*$#', '', $path);             // Remove non-directory element from path
	if($url[0] == '/') $path = '';                            // Destroy path if relative url points to root
	$abs = "$host$path/$url";                                 // Dirty absolute URL
	$re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');       // Replace '//' or '/./' or '/foo/../' with '/'
	for($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {}
	return "$scheme://$abs";
}

if($_POST['action'] == 'load')
{
	$url = trim((string) $_POST['load']);
	if(!preg_match('/^https?:\/\//', $url))
		die(json_encode(array('errors' => 'Invalid URL.')));
	$site = array(
		'title'       => '',
		'description' => '',
		'images'      => array(),
	);
	if(!($html = @file_get_contents($url)))
		die(json_encode(array('errors' => array('Page not found.'))));
	$doc = new DomDocument();
	@$doc->loadHTML($html);
	if($title = $doc->getElementsByTagName('title')->item(0))
		$site['title'] = $title->textContent;
	foreach($doc->getElementsByTagName('meta') as $meta)
	{
		if($meta->getAttribute('name') == 'description')
			$site['description'] = $meta->getAttribute('content');
		if($meta->getAttribute('property') == 'og:image')
			$site['images'][] = $meta->getAttribute('content');
	}
	$base = preg_replace('/[^\/]*$/', '', $url); 
	if($tag = $doc->getElementsByTagName('base')->item(0))
		$base = $tag->getAttirbute('href');
	foreach($doc->getElementsByTagName('img') as $img)
	{
		if($src = $img->getAttribute('src'))
			$site['images'][] = makeAbsolute($src, $base);
		if(count($site['images']) > 5)
			break;
	}
	die(json_encode($site));
}

if($_POST['action'] == 'create')
{
	$length = 4;
	$link = array(
		'url'         => stripslashes(trim((string) $_POST['url'])),
		'title'       => stripslashes(trim((string) $_POST['title'])),
		'description' => stripslashes(trim((string) $_POST['description'])),
		'image'       => stripslashes(trim((string) $_POST['image'])),
		'ip'          => $_SERVER['REMOTE_ADDR'],
	);
	$errors = array();
	if(!preg_match('/^https?:\/\//', $link['url']))
		$errors[] = 'Invalid URL.';
	if(!preg_match('/^https?:\/\//', $link['image']))
		$errors[] = 'Invalid image URL.';
	if(strlen($link['title']) > 255)
		$errors[] = 'Title is too long.';
	if(strlen($link['description']) > 255)
		$errors[] = 'Description is too long.';
	if($errors)
		die(json_encode(array('errors' => $errors)));
	$query = $db->prepare('
		INSERT INTO `link` (`key`, `url`, `title`, `description`, `image`, `ip`)
		VALUES (:key, :url, :title, :description, :image, :ip)
	');
	do
	{
		$link['key'] = makeKey($length++);
		$success = $query->execute($link);
	}
	while(!$success);
	die(json_encode(array('link' => "http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}{$link['key']}")));
}


if($key = (string) $_GET['key'])
{
	$query = $db->prepare('SELECT * FROM `link` WHERE `key` = ?');
	$query->execute(array($key));
	$link = $query->fetch(PDO::FETCH_ASSOC);
	if(!$link)
		showPage('error', array('message' => "Page not found: $key"));
	showPage('link', $link);
}

showPage('home');