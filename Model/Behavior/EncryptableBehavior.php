<?php
/**
 * Encryptable Behavior
 */
App::uses('Lapis', 'Lapis.Lib');
class EncryptableBehavior extends ModelBehavior {

	protected $_defaults = array(
		'column' => 'encrypted',
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
		$hasEncryption = false;
		foreach ($Model->data[$Model->alias] as $field => $value) {
			if (!empty($this->schema[$Model->alias][$field])) {
				$hasEncryption = true;
				break;
			}
		}
		if (!$hasEncryption) {
			return true; //  no encryption, no Lapis processing needed
		}

		// Update
		$isUpdate = false;
		if (!empty($Model->id)) {
			$old = $Model->find('first', array(
				'conditions' => array($Model->primaryKey => $Model->id),
				'fields' => array($Model->primaryKey, $this->settings[$Model->alias]['column'])
			));

			if (!empty($old)) {
				$isUpdate = true;
			}

			if ($hasEncryption) {
				if (array_key_exists($this->settings[$Model->alias]['column'], $old[$Model->alias])) {
					return false; // Encryption failed
				}

				foreach ($old[$Model->alias] as $field => $value) {
					if (!isset($Model->data[$Model->alias][$field])) {
						$Model->data[$Model->alias][$field] = $value;
					}
				}
			}
		}

		if (!$isUpdate) {
			$vaults = $this->_getVaultPublicKeys($Model->saveFor);
		} else {
			$vaults = $this->_getVaultPublicKeysForUpdate($this->_getModelID($Model->alias, $Model->id));
		}

		if (empty($vaults)) {
			return false;
		}

		$document = array();
		foreach ($Model->data[$Model->alias] as $field => $value) {
			if (isset($this->schema[$Model->alias][$field])) {
				$document[$field] = $this->_handleType($value, $this->schema[$Model->alias][$field]);
				unset($Model->data[$Model->alias][$field]);
			}
		}

		$encryptOptions = array();
		if ($isUpdate && !empty($this->docSecret)) {
			// Do not generate new doc key and iv for update
			$encryptOptions['iv'] = $this->docSecret['iv'];
			$encryptOptions['key'] = $this->docSecret['key'];
		}
		$encRes = Lapis::docEncryptForMany($document, $vaults, $encryptOptions);
		$dockeys = $encRes['keys'];

		$encDoc = $encRes;
		unset($encDoc['keys']);
		$encDocJSON = json_encode($encDoc);

		// Hold the keys for afterSave() – after model ID is obtained
		$this->dockeys[$Model->alias][sha1($encDocJSON)] = $dockeys;

		$Model->data[$Model->alias][$this->settings[$Model->alias]['column']] = $encDocJSON;
		return true;
	}

	public function afterSave(Model $Model, $created, $options = array()) {
		if ($created && isset($Model->data[$Model->alias][$this->settings[$Model->alias]['column']])) {
			$encDocJSONHash = sha1($Model->data[$Model->alias][$this->settings[$Model->alias]['column']]);
			if (!isset($this->dockeys[$Model->alias][$encDocJSONHash])) {
				throw new CakeException('Document keys not found after successful save');
			}

			$DocumentModel = ClassRegistry::init('Lapis.Document');
			foreach ($this->dockeys[$Model->alias][$encDocJSONHash] as $keyID => $docKey) {
				$docData[] = array(
					'model_id' => $this->_getModelID($Model->alias, $Model->id),
					'vault_id' => $keyID,
					'key' => $docKey
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
				$docFields = false;
				if (!empty($Model->requestAs)) {
					$proceed = true;

					$docVaults = ClassRegistry::init('Lapis.Document')->find('list', array(
						'conditions' => array(
							'model_id' => $this->_getModelID($Model->alias, $results[$key][$Model->alias]['id'])
						),
						'fields' => array('vault_id', 'key')
					));
					$proceed = !empty($docVaults);

					if ($proceed) {
						$accessor = ClassRegistry::init('Lapis.Accessor')->find('first', array(
							'conditions' => array(
								'vault_id' => array_keys($docVaults),
								'requester_id' => $Model->requestAs['id']
							),
							'fields' => array('id', 'vault_id', 'key')
						));
						$proceed = !empty($accessor);
					}

					if ($proceed) {
						$vaultID = $accessor['Accessor']['vault_id'];
						$vault = ClassRegistry::init('Lapis.Requester')->find('first', array(
							'conditions' => array(
								'id' => $vaultID,
							),
							'fields' => array('id', 'vault_private_key'),
						));
						$proceed = !empty($vault);
					}

					if ($proceed) {
						$encDoc = $results[$key][$Model->alias][$docColumn];
						$vaultKeyDoc = $vault['Requester']['vault_private_key'];
						$documentKey = $docVaults[$vaultID];
						$requesterAccessorKey = $accessor['Accessor']['key'];
						$requesterPrivateKey = ClassRegistry::init('Lapis.Requester')->getPrivateKey($Model->requestAs);

						$docFields = $this->_decryptDocument(
							$encDoc,
							$vaultKeyDoc,
							$documentKey,
							$requesterAccessorKey,
							$requesterPrivateKey
						);
					}
				}

				if (is_array($docFields)) {
					$results[$key][$Model->alias] = array_merge($results[$key][$Model->alias], $docFields);
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
	 * Returns list of vault public keys
	 *
	 * @param array/string $saveFor ID or IDs of Requester
	 * @return array List of ID and associated vault public keys
	 *               or false, if any of the IDs do not have a vault
	 */
	protected function _getVaultPublicKeys($saveFor) {
		if (!is_array($saveFor)) {
			$saveFor = array($saveFor);
		}

		$Requester = ClassRegistry::init('Lapis.Requester');
		$results = $Requester->find('list', array(
			'conditions' => array('id' => $saveFor, 'vault_public_key IS NOT NULL'),
			'fields' => array('id', 'vault_public_key'),
		));

		// If any of the ID does not have a vault, return false for all
		if (count($results) !== count($saveFor)) {
			return false;
		}
		return $results;
	}

	/**
	 * Returns list of vault public keys, given model id
	 */
	protected function _getVaultPublicKeysForUpdate($modelID) {
		$docVaults = ClassRegistry::init('Lapis.Document')->find('list', array(
			'conditions' => array(
				'model_id' => $modelID
			),
			'fields' => array('vault_id', 'vault_id')
		));

		if (empty($docVaults)) {
			return false;
		}

		return ClassRegistry::init('Lapis.Requester')->find('list', array(
			'conditions' => array('id' => array_keys($docVaults), 'vault_public_key IS NOT NULL'),
			'fields' => array('id', 'vault_public_key'),
		));
	}

	/**
	 * Returns list of public keys to encrypt with
	 * DEPRECATED
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

 	/**
 	 * Decrypt a document given the following
 	 * @param  string $encDoc Encrypted document, obtainable from target model's encrypted field
 	 * @param  string $vaultKeyDoc Encrypted vault's private key document, obtainable from Lapis.Requester's vault_private_key field
 	 * @param  string $documentKey Encrypted document key, obtainable from Lapis.Document's key field
 	 * @param  string $requesterAccessorKey Encrypted requester accessor key, obtainable from Lapis.Accessor's key field
 	 * @param  string $requesterPrivateKey Unencrypted clear requester's identity private key
 	 * @return array Successfully decrypted document, or false
 	 */
 	protected function _decryptDocument($encDoc, $vaultKeyDoc, $documentKey, $requesterAccessorKey, $requesterPrivateKey) {
		$vaultPrivate = Lapis::docDecrypt($vaultKeyDoc, $requesterAccessorKey, $requesterPrivateKey);
		if (empty($vaultPrivate)) {
			return false;
		}

		$document = Lapis::docDecrypt($encDoc, $documentKey, $vaultPrivate, $this->docSecret);
		return $document;
 	}
}
