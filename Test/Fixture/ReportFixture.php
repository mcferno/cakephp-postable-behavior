<?php  
class ReportFixture extends CakeTestFixture { 
	public $name = 'Report'; 
	
	public $fields = array( 
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'length' => 255, 'null' => false), 
		'author' => array('type' => 'string', 'length' => 255, 'null' => false), 
		'region' => array('type' => 'string', 'length' => 255, 'null' => false), 
		'color' => array('type' => 'string', 'length' => 255, 'null' => false),
	); 
	public $records = array(
		array(
			'id' => 1,
			'title' => 'Report Title',
			'author' => 'Report Author',
			'region' => 'Report Region', 
			'color' => 'Report Color'
		)
	);
} 
 ?>