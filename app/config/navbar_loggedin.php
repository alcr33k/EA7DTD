<?php
/**
 * Config-file for navigation bar.
 *
 */
return [

  // Use for styling the menu
  'class' => 'navbar',
 
  // Here comes the menu strcture
	'items' => [

		// This is a menu item
		'home'  => [
			'text'  => 'Home',
			'url'   => '',
			'title' => 'Home'
		],
		'questions'  => [
			'text'  => 'Questions ',
			'url'   => 'questions',
			'title' => 'Questions'
		],
		'tags'  => [
			'text'  => 'Tags',
			'url'   => 'tags/list',
			'title' => 'Popular tags'
		],
		'about'  => [
			'text'  => 'About',
			'url'   => 'about',
			'title' => 'About the website'
		],
		'edit'  => [
			'text'  => 'My account',
			'url'   => 'edit',
			'title' => 'Edit you account info',
		],
		'logout'  => [
			'text'  => 'Log out',
			'url'   => 'logout',
			'title' => 'Log out',
		],
	],
	
	// Callback tracing the current selected menu item base on scriptname
	'callback' => function ($url) {
		if ($url == $this->di->get('request')->getRoute()) {
						return true;
		}
	},

	// Callback to create the urls
	'create_url' => function ($url) {
		return $this->di->get('url')->create($url);
	},
];
