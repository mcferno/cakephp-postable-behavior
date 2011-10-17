<?php
/**
 * CakePHP Postable Behavior
 * @author Patrick McFern <mcferno AT gmail.com>
 * @source https://github.com/mcferno/cakephp-postable
 * 
 * Allows you to map data from one or more models to a central model/table
 * which maintains an index of common fields. The resulting set of data can be 
 * manipulated in an abstract fashion, allowing you to blur the differences
 * between different types of data.
 * 
 * Settings:
 * 
 * - storageModel : default 'Post'. 
 *     Name of the model which will be instantiated and used as the storage for 
 *     the common data. This model should never use this behavior.
 * 
 * - mapping :
 *     By default, the field name of all non-essential columns in the 
 *     storage model will be used as-is to find matches in other models using
 *     the behavior. This is used to override the mapping for a specific model.
 * 
 *     Format : array('columnNameInStorageModel' => 'fieldNameInSourceModel')
 * 
 *     If a columnName is assigned `false`, it will be ignored during mapping.
 *     If a columnName is assigned `true`, it will invoke a Model callback to 
 *       handle the data assignment (below).
 * 
 * - mappingCallback : default 'postableMappingCallback'
 *     The model callback function used to map data. This allows the model to 
 *     manipulate the data as needed before it is recorded in the storage model.
 *     To enable this callback, set the mapping to `true` for a column (above)
 *     
 *     Syntax : postableMappingCallback(columnName, modelData);
 * 
 *     The callback should return data for the specific columnName in a saveable
 *     format.
 * 
 * - inclusionCallback : default `null`
 *     The model callback function used to determine whether or not a specific
 *     record should be included in the storage model.
 * 
 *     Syntax : postableInclusionCallback(modelData);
 * 
 *     The callback should return true (use) or false (omit) 
 */
class PostableBehavior extends ModelBehavior {
	
	/**
	 * Stores an instance of the Model which holds our abstracted copy of common
	 * fields. Does not have to be an explicitly defined model as CakePHP can
	 * generate one on the fly.
	 */
	public $storageModel = false;
	
	/**
	 * List of columns needed at minimum in the storage model in order to 
	 * maintain a meaningful index.
	 */
	public $requiredBaseColumns = array(
		'id','model','foreign_key'
	);
	
	public function setup(&$Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			
			// default settings
			$this->settings[$Model->alias] = array(
				'storageModel'      => 'Post',
				'mapping'           => array(),
				'mappingCallback'   => 'postableMappingCallback',
				'inclusionCallback' => null
			);
		}
		if (!is_array($settings)) {
			$settings = array();
		}
		
		// merge in default settings + user settings
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
		
		// instantiate the storage model. 
		$this->storageModel = ClassRegistry::init($this->settings[$Model->alias]['storageModel']);
		
		// verify that the storage model meets minimum requirements.
		if(!$this->_validateStorageModel()) {
			trigger_error("Postable Behaviour :: Storage model {$this->settings[$Model->alias]['storageModel']} does not have the required fields");
			return;
		}
				
		// default list of columns to record in our storage model.
		$defaultAssignment = $this->_getDefaultMapping();
		
		// assign the mapping default, plus user defined overrides.
		$this->settings[$Model->alias]['mapping'] = array_merge($defaultAssignment,$this->settings[$Model->alias]['mapping']);
	}
	
	/**
	 * Verfies that the storage model meets the minimum requirements to make
	 * proper use of this behavior.
	 * 
	 * @return {Boolean} whether the storage model is valid
	 */
	protected function _validateStorageModel() {
		if(empty($this->storageModel)) {
			return false;
		}
		
		// pull the list of columns present in the storage model.
		$schema = $this->storageModel->schema();
		$requiredColumnKeys = array_flip($this->requiredBaseColumns);
		
		// verify that none of the required columns are missing
		if(count(array_intersect_key($schema,$requiredColumnKeys)) !== count($requiredColumnKeys)) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Inspects the storage model and obtains a list of column names which will
	 * group our data
	 */
	protected function _getDefaultMapping() {
		$schema = $this->storageModel->schema();
		
		// remove all columns which are required (and not used for comparison)
		$whitelist = array_diff_key($schema,array_flip($this->requiredBaseColumns));
		
		// format the list of columns into an associative array ( 'key' => 'key' )
		return array_combine(array_keys($whitelist),array_keys($whitelist));
	}
	
	/**
	 * Maps data from the source model into a format that is saveable in the 
	 * storage model. Depending on the plugin settings, a field may be omitted,
	 * mapped via callback, or mapped via field name.
	 *
	 * @param {Model} $Model containing a populated this->data
	 * @return {Array} mapped data
	 */
	protected function _assignFields(&$Model) {
		
		$data = array();
		
		foreach($this->settings[$Model->alias]['mapping'] as $storageKey=>$mappingField) {
			// field is not to be stored
			if($mappingField === false) {
				continue;
			
			// field is handled by callback
			} elseif($mappingField === true && method_exists($Model,$this->settings[$Model->alias]['mappingCallback'])) {
				$data[$storageKey] = $Model->{$this->settings[$Model->alias]['mappingCallback']}($storageKey, $Model->data);
				
			// field is mapped using the default approach
			} else {
				if(isset($Model->data[$Model->alias][$mappingField])) {
					$data[$storageKey] = $Model->data[$Model->alias][$mappingField];
				}
			}
		}
		
		return $data;
	}
	
	/**
	 * Updates the stoage model data to maintain data freshness.
	 *
	 * @param {Model} $Model
	 * @param {boolean} $created Whether this is a save or an update
	 */
	public function afterSave(&$Model, $created) {
		
		// verify if an inclusion callback has been set and exists for this model
		if(isset($this->settings[$Model->alias]['inclusionCallback']) && method_exists($Model,$this->settings[$Model->alias]['inclusionCallback'])) {
			
			// halt execution if this record is to be omitted.
			if($Model->{$this->settings[$Model->alias]['inclusionCallback']}($Model->data) === false) {
				return;
			}
		}
		
		// convert the record data into a saveable format.
		$data = $this->_assignFields($Model);
		if(empty($data)) {
			return;
		}
		
		// set required fields for later retrieval
		$data['model'] = $Model->alias;
		$data['foreign_key'] = $Model->id;
		
		// if this is not new data, update the existing entry
		if(!$created) {
			$existing = $this->storageModel->find('first',array(
				'conditions'=>array(
					'model'=>$Model->alias,
					'foreign_key'=>$Model->id
				),
				'recursive'=>-1
			));
			
			if(!empty($existing[$this->storageModel->alias]['id'])) {
				$data['id'] = $existing[$this->storageModel->alias]['id'];
			}
		}
		
		// save or update the index with this new data
		$this->storageModel->create();
		$this->storageModel->save($data);
	}
	
	/**
	 * Removes references to data which has been deleted.
	 *
	 * @param {Model} $Model we're operating on.
	 */
	public function afterDelete(&$Model) {
		
		// obtain the record from the storage model, if this deleted record was being indexed.
		$existing = $this->storageModel->find('first',array(
			'conditions'=>array(
				'model'=>$Model->alias,
				'foreign_key'=>$Model->id
			),
			'recursive'=>-1
		));
		
		// if we found a match, delete it.
		if(!empty($existing[$this->storageModel->alias]['id'])) {
			$this->storageModel->delete($existing[$this->storageModel->alias]['id']);
		}
	}
}