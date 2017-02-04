<?php
/**
 * Secured Document
 */
App::uses('Lapis', 'Lapis.Lib');
class SecDocBehavior extends ModelBehavior {

	protected $_defaults = array(
		'column' => 'document',
		'cipher' => 'aes-256-ctr',
		'document_id_digest' => 'sha256',
		'salt' => null, // generated on constructor if it is set to null (recommended)
	);
	protected $_types = array('inherit', 'string', 'number', 'boolean');

	public function setup(Model $Model, $settings = array()) {
		$this->schema[$Model->alias] = $this->_normalizeSchema($Model->documentSchema);
		$this->settings[$Model->alias] = array_merge($this->_defaults, $settings);

		// Generate behavior security salt
		if (empty($this->settings[$Model->alias]['salt'])) {
			$this->settings[$Model->alias]['salt'] = sha1(Configure::read('Security.salt') . $Model->alias);
		}
	}

	public function beforeSave(Model $Model, $options = array()) {
		$publicKeys = $this->_getPublicKeys($Model->forKeys);
		if (empty($publicKeys)) {
			return false; // no keys found
		}

		$document = array();

		foreach ($Model->data[$Model->alias] as $field => $value) {
			if (isset($this->schema[$Model->alias][$field])) {
				$document[$field] = $this->_handleType($value, $this->schema[$Model->alias][$field]);
				unset($Model->data[$Model->alias][$field]);
			}
		}

		$encRes = Lapis::docEncryptForMany($document, $publicKeys);

		$encDoc = array(
			'lapis' => $encRes['lapis'],
			'cipher' => $encRes['cipher'],
			'data' => $encRes['data']
		);
		$encDocJSON = json_encode($encDoc);

		// Hold the keys for afterSave() – after model ID is obtained
		$this->dockeys[$Model->alias][sha1($encDocJSON)] = $encRes['keys'];

		$Model->data[$Model->alias][$this->settings[$Model->alias]['column']] = $encDocJSON;
		return true;
	}

	public function afterSave(Model $Model, $created, $options = array()) {
		if (isset($Model->data[$Model->alias][$this->settings[$Model->alias]['column']])) {
			$encDocJSONHash = sha1($Model->data[$Model->alias][$this->settings[$Model->alias]['column']]);
			if (!isset($this->dockeys[$Model->alias][$encDocJSONHash])) {
				throw new CakeException('Document keys not found after successful save');
			}

			$modelID = $this->_getModelID($Model->alias, $Model->data[$Model->alias]['id']);

			$DocumentModel = ClassRegistry::init('Lapis.Document');
			foreach ($this->dockeys[$Model->alias][$encDocJSONHash] as $keyID => $docKey) {
				$docData[] = array(
					'id' => sha1($modelID . $keyID),
					'key_id' => $keyID,
					'model_id' => $modelID,
					'document_pw' => $docKey
				);
			}

			// Junk the keys after use
			unset($this->dockeys[$Model->alias][$encDocJSONHash]);

			return $DocumentModel->saveMany($docData);
		}
	}

	/**
	 * Extract secured document columns into conventional document columns
	 */
	public function afterFind(Model $Model, $results, $primary = false) {
		$docColumn = $this->settings[$Model->alias]['column'];
		foreach ($results as $key => $row) {
			if (array_key_exists($docColumn, $row[$Model->alias])) {
				$docData = false;
				if (!empty($Model->requestAs)) {
					$requesterPrivateKey = ClassRegistry::init('Lapis.Key')->getPrivateKey($Model->requestAs);
					$encDocKey = ClassRegistry::init('Lapis.Document')->getEncryptedPassword(
						$this->_getModelID($Model->alias, $results[$key][$Model->alias]['id']),
						$Model->requestAs['id']
					);
					$docData = Lapis::docDecrypt(
						$results[$key][$Model->alias][$docColumn],
						$encDocKey,
						$requesterPrivateKey
					);
				}
				if (is_array($docData)) {
					$results[$key][$Model->alias] = array_merge($results[$key][$Model->alias], $docData);
					unset($results[$key][$Model->alias][$docColumn]);
				} else {
					$results[$key][$Model->alias][$docColumn] = '(encrypted)';
				}
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

	/**
	 * Returns list of public keys to encrypt with
	 */
	protected function _getPublicKeys($forKeys) {
		if (!empty($forKeys) && !is_array($forKeys)) {
			$forKeys = array($forKeys);
		}

		$KeyModel = ClassRegistry::init('Lapis.Key');
		$keyIDs = $KeyModel->getAncestorIDs($forKeys);

		$cond = array();
		if (!empty($keyIDs)) {
			$cond['Key.id'] = $keyIDs;
		} else {
			$cond['Key.parent_id'] = null; // get all root keys
		}

		$keys = $KeyModel->find('list', array(
			'conditions' => $cond,
			'fields' => array('Key.id', 'Key.public_key')
		));

		return $keys;
	}

	protected function _getModelID($modelAlias, $id) {
		return sha1($this->settings[$modelAlias]['salt'] . $id);
	}
}
