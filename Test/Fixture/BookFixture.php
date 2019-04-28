<?php

class BookFixture extends CakeTestFixture
{
	public $name = 'Book';

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false),
		'author_first_name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'author_last_name' => array('type' => 'string', 'length' => 255, 'null' => false),
		'country' => array('type' => 'string', 'length' => 255, 'null' => false),
		'color' => array('type' => 'string', 'length' => 255, 'null' => false)
	);
	public $records = array(
		array(
			'id' => 1,
			'title' => 'Title One',
			'author_first_name' => 'First Name One',
			'author_last_name' => 'Last Name One',
			'country' => 'Country One',
			'color' => 'Color One'
		),
		array(
			'id' => 2,
			'title' => 'Title Two',
			'author_first_name' => 'First Name Two',
			'author_last_name' => 'Last Name Two',
			'country' => 'Country Two',
			'color' => 'Color Two'
		),
		array(
			'id' => 3,
			'title' => 'Title Three',
			'author_first_name' => 'First Name Three',
			'author_last_name' => 'Last Name Three',
			'country' => 'Country Three',
			'color' => 'Color Three'
		)
	);
}
