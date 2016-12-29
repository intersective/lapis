<?php
/**
 * Secured Document
 */
class SecDocBehavior extends ModelBehavior {

	protected $_defaults = array();
	protected $_types = array('string', 'number', 'boolean'); // Default: string

	public function setup(Model $Model, $settings = array()) {
		$this->documentSchema = $this->_normalizeSchema($Model->documentSchema);
		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);
	}

	public function beforeSave(Model $Model, $options = array()) {
		debug($Model->data);
		debug('hey');
	}

	/**
	 * $documentSchema normalization
	 * Supports either named-array or non-named array
	 *
	 * Example:
	 * array('field1', 'field2' ...); or
	 * array('field1' => 'number', 'field2' => 'boolean' ...);
	 */
	protected function _normalizeSchema($documentSchema) {
		// Converting of non-named array, taking all fields as strings
		if (isset($documentSchema[0])) {
			$fields = $documentSchema;
		} else {
			$fields = array_keys($documentSchema);
		}

		// Normalized schema
		$schema = array();

		foreach ($fields as $field) {
			if (
				isset($documentSchema[$field]) &&
				in_array($documentSchema[$field], $this->_types)
			) {
				$schema[$field] = $documentSchema[$field];
			} else {
				$schema[$field] = 'string';
			}
		}

		debug($fields);
		debug($schema);
	}
}