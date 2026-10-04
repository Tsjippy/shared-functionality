<?php
// This file is generated. Do not modify it manually.
return array(
	'show_categories' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tsjippy/category_list',
		'version' => '0.1.0',
		'title' => 'Category List',
		'category' => 'widgets',
		'icon' => 'forms',
		'description' => 'List of categories belonging to the current page, post or custom post type. Can be used to create a filterable list of categories.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'tsjippy',
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'count' => array(
				'type' => 'boolean',
				'default' => false
			)
		)
	),
	'show_children' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'tsjippy/show-children',
		'version' => '0.1.0',
		'title' => 'Child List',
		'category' => 'widgets',
		'icon' => 'forms',
		'description' => 'List of child pages',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'tsjippy',
		'editorScript' => 'file:./index.js',
		'render' => 'file:./render.php',
		'attributes' => array(
			'title' => array(
				'type' => 'boolean',
				'default' => false
			),
			'listtype' => array(
				'type' => 'string',
				'default' => 'disc'
			),
			'grandchildren' => array(
				'type' => 'boolean',
				'default' => false
			),
			'parents' => array(
				'type' => 'boolean',
				'default' => false
			),
			'grantparents' => array(
				'type' => 'number',
				'default' => 0
			)
		)
	)
);
