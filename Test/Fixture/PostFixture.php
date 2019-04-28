<?php

class PostFixture extends CakeTestFixture
{
	public $name = 'Post';

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'model' => array('type' => 'string', 'length' => 100, 'null' => false),
		'foreign_key' => array('type' => 'integer', 'null' => false),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false),
		'author' => array('type' => 'string', 'length' => 255, 'null' => false),
		'region' => array('type' => 'string', 'length' => 255, 'null' => false),
		'color' => array('type' => 'string', 'length' => 255, 'null' => false),
	);
	public $records = array();
}
