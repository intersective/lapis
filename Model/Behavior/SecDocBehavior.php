<?php
/**
 * Secured Document
 */
class SecDocBehavior extends ModelBehavior {

	protected $_defaults = array(
		'column' => 'document',
		'cipher' => 'aes-256-cbc'
	);
	protected $_types = array('inherit', 'string', 'number', 'boolean');

	public function setup(Model $Model, $settings = array()) {
		$this->schema[$Model->alias] = $this->_normalizeSchema($Model->documentSchema);
		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);
	}

	public function beforeSave(Model $Model, $options = array()) {
		$document = array();

		foreach ($Model->data[$Model->alias] as $field => $value) {
			if (isset($this->schema[$Model->alias][$field])) {
				$document[$field] = $this->_handleType($value, $this->schema[$Model->alias][$field]);
				unset($Model->data[$Model->alias][$field]);
			}
		}

		// TODO: Encryption

		$Model->data[$Model->alias][$this->settings[$Model->alias]['column']] = json_encode($document);
		return true;
	}

	/**
	 * Extract secured document columns into conventional document columns
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		$docColumn = $this->settings[$Model->alias]['column'];
		foreach ($results as $key => $row) {
			if (array_key_exists($docColumn, $row[$Model->alias])) {
				$docData = json_decode($results[$key][$Model->alias][$docColumn], true);
				if (is_array($docData)) {
					$results[$key][$Model->alias] = array_merge($results[$key][$Model->alias], $docData);
				}
				unset($results[$key][$Model->alias][$docColumn]);
			}
		}
		return $results;
	}

	protected function _handleType($value, $type = 'string') {
		switch ($type) {
			case 'boolean':
				return (bool)$value;
				break;
			case 'number':
				return $value + 0; // reliably casting to either int or float
				break;
			case 'string':
				return (string)$value;
				break;
			case 'inherit':
			default:
				return $value;
		}
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
				$schema[$field] = 'inherit'; // non-enforcing, not type-casted
			}
		}
		return $schema;
	}
}
