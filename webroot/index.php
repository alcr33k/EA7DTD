<?php
require __DIR__.'/config_with_app.php'; 
$app->url->setUrlType(\Anax\Url\CUrl::URL_CLEAN);
$app->theme->configure(ANAX_APP_PATH . 'config/theme.php');
$app->session();
if((isset($_SESSION["loginStatus"])) && ($_SESSION["loginStatus"] != null)) {
	$app->navbar->configure(ANAX_APP_PATH . 'config/navbar_loggedin.php');
}
else {
	$app->navbar->configure(ANAX_APP_PATH . 'config/navbar.php');
}
 
$di->setShared('db', function() {
    $db = new \Mos\Database\CDatabaseBasic();
    $db->setOptions(require ANAX_APP_PATH . 'config/database_mysql.php');
    $db->connect();
    return $db;
});
$di->setShared('pdo', function() {
		try {
			$mysql = require ANAX_APP_PATH . './config/database_mysql.php'; // get connection details array
			$pdo = new PDO($mysql['dsn'], $mysql['username'], $mysql['password'], $mysql['driver_options']);
			return $pdo;
		}
		catch(PDOException $ex) {
			echo $e->getMessage();
		}
});
$di->set('form', '\Mos\HTMLForm\CForm');
$di->set('FormController', function () use ($di) {
    $controller = new \Anax\HTMLForm\FormController();
    $controller->setDI($di);
    return $controller;
});

$di->set('UsersController', function() use ($di) {
    $controller = new \Anax\Users\UsersController();
    $controller->setDI($di);
    return $controller;
});

$di->set('QuestionsController', function() use ($di) {
    $controller = new \Anax\Questions\QuestionsController();
    $controller->setDI($di);
    return $controller;
});

$di->set('CommentsController', function() use ($di) {
    $controller = new \Anax\Comments\CommentsController();
    $controller->setDI($di);
    return $controller;
});

$di->set('TagsController', function() use ($di) {
    $controller = new \Anax\Tags\TagsController();
    $controller->setDI($di);
    return $controller;
});

$app->router->add('', function() use ($app) {
	// home page
	$app->theme->setTitle("Everrything about 7 days to die");
	$mostActive = $app->dispatcher->forward([
		'controller'    => 'questions',
		'action'         => 'getMostActive',
		'params'        => ['pdo' => $app->pdo],
	]);
	$newest = $app->dispatcher->forward([
		'controller'    => 'questions',
		'action'         => 'getNewest',
		'params'        => [],
	]);
	$popularTags = $app->dispatcher->forward([
		'controller'    => 'questions',
		'action'         => 'getPopularTags',
		'params'        => [],
	]);
	$content = '<h1>Senaste poster</h1>';
	$content .= $newest;
	$content .= '<h1>Mest aktiva användare</h1>';
	$content .= $mostActive;
	$content .= '<h1>Mest populära taggar</h1>';
	$content .= $popularTags;
	$app->views->add('default/page', [
		'content' => $content,
	]);
});
 
$app->router->add('questions', function() use ($app) {
	// page for questions
	$app->theme->setTitle("Questions");
	$loginstatus = null;
	if(isset($_SESSION['loginStatus']))
	{
		$loginstatus = $_SESSION['loginStatus'];
	}
	$content = $app->dispatcher->forward([
		'controller'    => 'questions',
		'action'         => 'page',
		'params'        => ['poster' => $loginstatus],
	]);
	$app->views->add('default/page', [
		'content' => $content,
	]);
});

$app->router->add('questions/submit', function() use ($app) {
	if ((isset($_SESSION["loginStatus"]) == true) || ($_SESSION["loginStatus"] != null)) {
		$value = $app->dispatcher->forward([
			'controller'    => 'questions',
			'action'         => 'submit',
			'params'        => ['poster' => $_SESSION['loginStatus'], 'pdo' => $app->pdo],
		]);
		echo $value;
	}
	else {
		header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/login');
	}
});

$app->router->add('register', function() use ($app) { 
	if((isset($_SESSION["loginStatus"]) == false) || ($_SESSION["loginStatus"] == null)) {
		$app->dispatcher->forward([
		'controller'    => 'users',
		'action'         => 'add',
		'params'        => [],
		]);
	}
	else {
		header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
	}
});

$app->router->add('login', function() use ($app) { 
	if((isset($_SESSION["loginStatus"]) == false) || ($_SESSION["loginStatus"] == null)) {
		$app->dispatcher->forward([
		'controller'    => 'users',
		'action'         => 'login',
		'params'        => [],
		]);
	}
	else {
		header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
	}
});

$app->router->add('logout', function() use ($app) { 
	if($_SESSION['loginStatus'] != null) {
		$_SESSION['loginStatus'] = null;
	}
	header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
});

$app->router->add('edit', function() use ($app) { 
	if($_SESSION['loginStatus'] != null) {
		$app->dispatcher->forward([
			'controller'    => 'users',
			'action'         => 'editUser',
			'params'        => ['username' => $_SESSION['loginStatus']],
		]);;
	}
	else {
		header('Location: http://www.student.bth.se/~albh14/phpmvc/kmom07-10/webroot/');
	}

});

$app->router->add('tags', function() use ($app) { 
	$app->theme->setTitle("Tags");
	$content = '<h1>Tags</h1>';
	$app->views->add('default/page', [
		'content' => $content,
	]);
	// page for tags
});

$app->router->add('about', function() use ($app) {
	$app->theme->setTitle("About");
	$content = $app->fileContent->get('about.md');
	$content = $app->textFilter->doFilter($content, 'shortcode, markdown');
	$app->views->add('default/page', [
		'content' => $content,
	]);
	// about page
});
 
$app->router->handle();
$app->theme->render();