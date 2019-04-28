<?php
/**
 * Set of models used to aid the test cases with scenarios involving model
 * specific functions and settings.
 */

class BookWithDefaultCallback extends CakeTestModel {
	public $name = 'BookWithDefaultCallback';
	public $useTable = 'books';

	public function postableMappingCallback($field, &$data) {
		return 'DefaultCallbackMapping';
	}
}

class BookWithNonDefaultCallback extends CakeTestModel {
	public $name = 'BookWithDefaultCallback';
	public $useTable = 'books';

	public function nonDefaultMappingCallback($field, &$data) {
		return 'NonDefaultMappingCallback';
	}
}

class BookWithInclusionCallback extends CakeTestModel {
	public $name = 'BookWithInclusionCallback';
	public $useTable = 'books';

	public function postableInclusionCallback(&$data) {
		return (bool)($data['BookWithInclusionCallback']['title'] == 'Title One');
	}
}

class NonDefaultStorageModel extends CakeTestModel {
	public $name = 'NonDefaultStorageModel';
	public $useTable = 'posts';
}

class InvalidStorageModel extends CakeTestModel {

	public $name = 'InvalidStorageModel';
	public $useTable = false;

	protected $_schema = array(
		'id'=> array('type' => 'integer', 'null' => '', 'default' => '1', 'length' => '8', 'key'=>'primary')
	);
}

class Post extends CakeTestModel {
	public $name = 'Post';
}
class Book extends CakeTestModel {
	public $name = 'Book';
}
class Report extends CakeTestModel {
	public $name = 'Report';
}
?>