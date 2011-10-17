<?php
/**
 * Set of models used to aid the test cases with scenarios involving model
 * specific functions and settings.
 */

class BookWithDefaultCallback extends CakeTestModel {
	var $name = 'BookWithDefaultCallback';
	var $useTable = 'books';
		
	public function postableMappingCallback($field, &$data) {
		return 'DefaultCallbackMapping';
	}
}

class BookWithNonDefaultCallback extends CakeTestModel {
	var $name = 'BookWithDefaultCallback';
	var $useTable = 'books';
		
	public function nonDefaultMappingCallback($field, &$data) {
		return 'NonDefaultMappingCallback';
	}
}

class BookWithInclusionCallback extends CakeTestModel {
	var $name = 'BookWithInclusionCallback';
	var $useTable = 'books';
	
	public function postableInclusionCallback(&$data) {
		return (bool)($data['BookWithInclusionCallback']['title'] == 'Title One');
	}
}

class NonDefaultStorageModel extends CakeTestModel {
	var $name = 'NonDefaultStorageModel';
	var $useTable = 'posts';
}

class InvalidStorageModel extends CakeTestModel {

	var $name = 'InvalidStorageModel';
	var $useTable = false;

	var $_schema = array(
		'id'=> array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key'=>'primary')
	);
}

class Post extends CakeTestModel {
	var $name = 'Post';
}
class Book extends CakeTestModel {
	var $name = 'Book';
}
class Report extends CakeTestModel {
	var $name = 'Report';
}
?>