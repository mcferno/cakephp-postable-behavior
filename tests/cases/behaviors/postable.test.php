<?php
App::import('Core', array('AppModel', 'Model'));
require_once dirname(__FILE__) . DS . 'models.php';

class PostableBehaviorTest extends CakeTestCase {

	public $fixtures = array(
		'plugin.postable.post',
		'plugin.postable.book',
		'plugin.postable.report',
	);

	/**
	 * Method executed before each test
	 */
	public function startTest() {
		$this->Post =& new Post();
		$this->Book =& new Book();
	}

	/**
	 * Method executed after each test
	 */
	public function endTest() {
		unset($this->Post);
		unset($this->Book);

		ClassRegistry::flush();
	}
	
	/**
	 * To simplify options testing, we resave the fixtures triggering the
	 * behavior to simulate a pre-populated storage model.
	 */
	protected function _resaveFixtures(&$Model) {
		$all_rows = $Model->find('all');
		$Model->deleteAll(array('1 = 1'));
		$Model->saveAll($all_rows);
		$Model->create();
	}
	
	/**
	 * Testing the utility function used by several test cases to ensure it
	 * doesn't contribute to inconsistent test results.
	 */
	public function testResaveHelper() {
		$before = $this->Book->find('count');
		
		$this->_resaveFixtures($this->Book);
		
		$after = $this->Book->find('count');
		
		$this->assertEqual($before,$after);
	}

	/**
	 * Tests the default behavior save + find, no options.
	 */
	public function testDefaultOptions() {
		$this->Book->Behaviors->attach('postable.postable',array());
		$this->_resaveFixtures($this->Book);
				
		// only direct column name matches are recorded
		$expecting = array(
			'Post' => array(
				'model' => 'Book',
				'foreign_key' => 1,
				'title' => 'Title One',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'Book',
			'foreign_key'=>1
		)));
			
		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests that the storage model stays up to date following an edit.
	 */
	public function testSaveUpdate() {		
		$this->Book->Behaviors->attach('postable.postable',array());
		$this->_resaveFixtures($this->Book);
		
		$expecting = array(
			'Post' => array(
				'model' => 'Book',
				'foreign_key' => 1,
				'title' => 'This has changed',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
				
		$updateData = array(
			'id' => 1,
			'title' => 'This has changed'
		);
		
		$this->assertTrue($this->Book->save($updateData));
				
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'Book',
			'foreign_key'=>1
		)));
			
		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests that the storage model stays up to date following a saveAll.
	 */
	public function testSaveAllUpdate() {		
		$this->Book->Behaviors->attach('postable.postable',array());
		$this->_resaveFixtures($this->Book);
				
		// updates to existing record
		$changes[] = array(
			'id' => 2,
			'title' => 'Title Two New'
		);
		
		// new record
		$changes[] = array(
			'title' => 'Title New', 
			'author_first_name' => 'First Name New', 
			'author_last_name' => 'Last Name New', 
			'country' => 'Country New', 
			'color' => 'Color New'
		);
		
		$expecting = array(
			array(
				'Post' => array(
					'model' => 'Book',
					'foreign_key' => 2,
					'title' => 'Title Two New',
					'author' => '',
					'region' => '',
					'color' => 'Color Two'
				)
			),
			array(
				'Post' => array(
					'model' => 'Book',
					'title' => 'Title New',
					'author' => '',
					'region' => '',
					'color' => 'Color New'
				)
			)
		);

		$this->assertTrue($this->Book->saveAll($changes));
		
		// record the primary key of the new record
		$new_record_id = $this->Book->id;
		$expecting[1]['Post']['foreign_key'] = $new_record_id;
						
		$result = $this->Post->find('all',array(
			'conditions'=>array(
				'model'=>'Book',
				'foreign_key'=>Set::extract('/Post/foreign_key',$expecting)
			),
			'order'=>'foreign_key ASC'
		));
			
		// assign id since we aren't testing primary key assignment
		$expecting[0]['Post']['id'] = $result[0]['Post']['id'];
		$expecting[1]['Post']['id'] = $result[1]['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the storageModel option.
	 */
	public function testStorageModelOption() {
		$this->Book->Behaviors->attach('postable.postable',array(
			'storageModel' => 'NonDefaultStorageModel'
		));
		$this->_resaveFixtures($this->Book);
		
		$expecting = array(
			'NonDefaultStorageModel' => array(
				'model' => 'Book',
				'foreign_key' => 1,
				'title' => 'Title One',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$NonDefaultStorageModel =& new NonDefaultStorageModel();
		$result = $NonDefaultStorageModel->find('first',array('conditions'=>array(
			'model'=>'Book',
			'id'=>1
		)));
		
		// assign id since we aren't testing primary key assignment
		$expecting['NonDefaultStorageModel']['id'] = $result['NonDefaultStorageModel']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the detection of  minimum required columns for the storageModel
	 */
	public function testInvalidStorageModel() {
		$this->expectError();
		
		$this->Book->Behaviors->attach('postable.postable',array(
			'storageModel' => 'InvalidStorageModel'
		));
	}
	
	/**
	 * Tests basic column name-based mapping option.
	 */
	public function testNameMappingOption() {
		$this->Book->Behaviors->attach('postable.postable',array(
			'mapping' => array(
				'region' => 'country'
			)
		));
		$this->_resaveFixtures($this->Book);
		
		$expecting = array(
			'Post' => array(
				'model' => 'Book',
				'foreign_key' => 1,
				'title' => 'Title One',
				'author' => '',
				'region' => 'Country One',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'Book',
			'foreign_key'=>1
		)));
		
		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the column exclusion mapping option
	 */
	public function testMappingExclusionOption() {
		$this->Book->Behaviors->attach('postable.postable',array(
			'mapping' => array(
				'title' => false
			)
		));
		$this->_resaveFixtures($this->Book);
		
		$expecting = array(
			'Post' => array(
				'model' => 'Book',
				'foreign_key' => 1,
				'title' => '',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'Book',
			'foreign_key'=>1
		)));
				
		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	/**
	 * Tests the column callback mapping option, when the column exists
	 */
	public function testMappingCallbackOptionColumnExists() {
		$BookWithDefaultCallback =& new BookWithDefaultCallback();
		$BookWithDefaultCallback->Behaviors->attach('postable.postable',array(
			'mapping' => array(
				'title' => true
			)
		));
		$this->_resaveFixtures($BookWithDefaultCallback);
		
		$expecting = array(
			'Post' => array(
				'model' => 'BookWithDefaultCallback',
				'foreign_key' => 1,
				'title' => 'DefaultCallbackMapping',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'BookWithDefaultCallback',
			'foreign_key'=>1
		)));

		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}

	/**
	 * Tests the column callback mapping option, when the column doesn't exist,
	 * simulating a "virtual column" possibility.
	 */
	public function testMappingCallbackOptionColumnDoesntExists() {
		$BookWithDefaultCallback =& new BookWithDefaultCallback();
		$BookWithDefaultCallback->Behaviors->attach('postable.postable',array(
			'mapping' => array(
				'author' => true
			)
		));
		$this->_resaveFixtures($BookWithDefaultCallback);
		
		$expecting = array(
			'Post' => array(
				'model' => 'BookWithDefaultCallback',
				'foreign_key' => 1,
				'title' => 'Title One',
				'author' => 'DefaultCallbackMapping',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'BookWithDefaultCallback',
			'foreign_key'=>1
		)));

		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the column callback mapping option, with a non default callback
	 * name.
	 */
	public function testNonDefaultMappingCallbackOption() {
		$BookWithNonDefaultCallback =& new BookWithNonDefaultCallback();
		$BookWithNonDefaultCallback->Behaviors->attach('postable.postable',array(
			'mapping' => array(
				'title' => true
			),
			'mappingCallback' => 'nonDefaultMappingCallback'
		));
		$this->_resaveFixtures($BookWithNonDefaultCallback);
		
		$expecting = array(
			'Post' => array(
				'model' => 'BookWithDefaultCallback',
				'foreign_key' => 1,
				'title' => 'NonDefaultMappingCallback',
				'author' => '',
				'region' => '',
				'color' => 'Color One'
			)
		);
		
		$result = $this->Post->find('first',array('conditions'=>array(
			'model'=>'BookWithDefaultCallback',
			'foreign_key'=>1
		)));

		// assign id since we aren't testing primary key assignment
		$expecting['Post']['id'] = $result['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the inclusionCallback option.
	 */
	public function testInclusionCallbackOption() {
		$BookWithInclusionCallback =& new BookWithInclusionCallback();		
		$BookWithInclusionCallback->Behaviors->attach('postable.postable',array(
			'inclusionCallback' => 'postableInclusionCallback'
		));
		$this->_resaveFixtures($BookWithInclusionCallback);
		
		$expecting = array(
			array(
				'Post' => array(
					'model' => 'BookWithInclusionCallback',
					'foreign_key' => 1,
					'title' => 'Title One',
					'author' => '',
					'region' => '',
					'color' => 'Color One'
				)
			)
		);
		
		$result = $this->Post->find('all');
		
		// inclusion callback only allows one record through during indexing
		$this->assertEqual(count($result),1);

		// assign id since we aren't testing primary key assignment
		$expecting[0]['Post']['id'] = $result[0]['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the updating of the storage model on deletion.
	 */
	public function testOnDeletionUpdate() {
		$this->Book->Behaviors->attach('postable.postable');
		$this->_resaveFixtures($this->Book);
		
		$find_query = array('conditions'=>array(
			'model' => 'Book',
			'foreign_key' => 1
		));
		
		// test that a record is in the index
		$before = $this->Post->find('first',$find_query);
		$this->assertEqual($before['Post']['id'],1);
		
		// test that a record can be deleted
		$this->assertTrue($this->Book->delete(1));
		
		// test that the same query will now yeild no records
		$after = $this->Post->find('first',$find_query);
		$this->assertEqual($after,array());
	}
	
	/**
	 * Tests the updating of the storage model on deletion via deleteAll
	 */
	public function testOnDeleteAllUpdate() {
		$this->Book->Behaviors->attach('postable.postable');
		$this->_resaveFixtures($this->Book);
		
		$find_query = array('conditions'=>array(
			'model' => 'Book',
			'foreign_key' => 1
		));
		
		// test that a record is in the index
		$before = $this->Post->find('first',$find_query);
		$this->assertEqual($before['Post']['id'],1);
		
		// deleteAll with callbacks
		$this->assertTrue($this->Book->deleteAll(array('1 = 1'),false,true));
		
		// test that the same query will now yeild no records
		$after = $this->Post->find('first',$find_query);
		$this->assertEqual($after,array());
	}
	
	/**
	 * Tests the mixing of data from multiple models
	 */
	public function testMultipleModelMixing() {
		$Report =& new Report();
		
		$this->Book->Behaviors->attach('postable.postable');
		$Report->Behaviors->attach('postable.postable');
		
		$this->_resaveFixtures($this->Book);
		$this->_resaveFixtures($Report);
		
		$expecting = array(
			array(
				'Post' => array(
					'model' => 'Book',
					'foreign_key' => 1,
					'title' => 'Title One',
					'author' => '',
					'region' => '',
					'color' => 'Color One'
				)
			),
			array(
				'Post' => array(
					'model' => 'Report',
					'foreign_key' => 1,
					'title' => 'Report Title',
					'author' => 'Report Author',
					'region' => 'Report Region',
					'color' => 'Report Color'
				)
			)
		);
		
		$result = $this->Post->find('all',array(
			'conditions'=>array(
				'foreign_key'=>1,
				'model'=>array('Book','Report')
			),
			'order'=>'model ASC'
		));
		
		// assign id since we aren't testing primary key assignment
		$expecting[0]['Post']['id'] = $result[0]['Post']['id'];
		$expecting[1]['Post']['id'] = $result[1]['Post']['id'];
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the refreshing of the storage model data
	 */
	public function testRefreshPostableIndex() {
		$this->Book->Behaviors->attach('postable.postable');
		
		// confirm we don't have any records before refreshing
		$empty = $this->Post->find('all');
		$this->assertEqual($empty, array());
		
		$expecting = array(
			array(
				'Post' => array(
					'id' => 1,
					'model' => 'Book',
					'foreign_key' => 1,
					'title' => 'Title One',
					'author' => '',
					'region' => '',
					'color' => 'Color One'
				)
        	),
			array(
				'Post' => array(
					'id' => 2,
					'model' => 'Book',
					'foreign_key' => 2,
					'title' => 'Title Two',
					'author' => '',
					'region' => '',
					'color' => 'Color Two'
				)
        	),
			array(
				'Post' => array(
					'id' => 3,
					'model' => 'Book',
					'foreign_key' => 3,
					'title' => 'Title Three',
					'author' => '',
					'region' => '',
					'color' => 'Color Three'
				)
        	),
        );
		
		$this->Book->refreshPostableIndex();
		
		$result = $this->Post->find('all',array('order'=>'id'));
		
		$this->assertEqual($result, $expecting);
	}
	
	/**
	 * Tests the refreshing of the storage model data with a limit
	 */
	public function testRefreshPostableIndexWithLimit() {
		$this->Book->Behaviors->attach('postable.postable');
		
		// confirm we don't have any records before refreshing
		$empty = $this->Post->find('all');
		$this->assertEqual($empty, array());
		
		$expecting = array(
			array(
				'Post' => array(
					'id' => 1,
					'model' => 'Book',
					'foreign_key' => 1,
					'title' => 'Title One',
					'author' => '',
					'region' => '',
					'color' => 'Color One'
				)
        	),
			array(
				'Post' => array(
					'id' => 2,
					'model' => 'Book',
					'foreign_key' => 2,
					'title' => 'Title Two',
					'author' => '',
					'region' => '',
					'color' => 'Color Two'
				)
        	),
			array(
				'Post' => array(
					'id' => 3,
					'model' => 'Book',
					'foreign_key' => 3,
					'title' => 'Title Three',
					'author' => '',
					'region' => '',
					'color' => 'Color Three'
				)
        	),
        );
		
		$this->Book->refreshPostableIndex(1);
		
		$result = $this->Post->find('all',array('order'=>'id'));
		$this->assertEqual($result, $expecting);
	}
}
?>