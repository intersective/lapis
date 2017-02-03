<?php
/**
 * User public and private keys
 */
App::uses('AppModel', 'Model');
App::uses('Lapis', 'Lapis.Lib');

class Requester extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Requester';

	/**
	 * Generate RSA key pair
	 */
	public function generate($password, $options = array()) {
		$options = array_merge(array(
			'keysize' => 4096,
			'parent' => null,
			'savePrivateToDb' => true,
			'privateKeyLocation' => null,
			'hasVault' => null
		), $options);
		$options['password'] = $password;

		if (is_null($options['hasVault'])) {
			if (is_null($options['parent'])) {
				$options['hasVault'] = false; // Root requester should not have vault by default
			} else {
				$options['hasVault'] = true;
			}
		}

		$keys = Lapis::genKeyPair($options['keysize']);

		$data = array(
			'parent_id' => $options['parent'],
			'ident_public_key' => $keys['public'],
		);

		if ($options['savePrivateToDb']) {
			if (!empty($options['password'])) {
				$data['ident_private_key'] = Lapis::pwEncrypt($keys['private'], $options['password']);
			} else {
				$data['ident_private_key'] = $keys['private'];
			}
		}

		$this->create();
		$ok = $this->save($data);

		if ($ok) {
			if ($options['hasVault']) {
				$ok = $this->createVault($this->getLastInsertID());
			}
		}

		if ($ok) {
			if (!$options['savePrivateToDb']) {
				return file_put_contents($options['privateKeyLocation'], $keys['private']);
			}
			return true;
		}

		return false;
	}

	/**
	 * Create a vault for a Requester
	 * @param  string  $id Requester ID
	 * @param  array $options
	 *     - boolean 'overwriteIfExists' By default do not recreate new vaults if an existing one already exists. Set to true to override this behavior.
	 *     - integer 'keysize' Size of keypair
	 * @return boolean Successfully created a new Vault for the said Requester
	 */
	public function createVault($id, $options = array()) {
		$options = array_merge(array(
			'keysize' => 4096,
			'overwriteIfExists' => false,
		), $options);

		$requester = $this->find('first', array(
			'conditions' => array('Requester.id' => $id),
			'fields' => array('Requester.id', 'Requester.vault_public_key')
		));

		if (empty($requester)) {
			return false;
		}
		if (!empty($requester['Requester']['vault_public_key']) && !$options['overwriteIfExists']) {
			return false;
		}

		$keys = Lapis::genKeyPair($options['keysize']);
		return $this->save(array('Requester' => array(
			'vault_public_key' => $keys['public']
		)));
	}

	/**
	 * Return a list of ancestor IDs including self
	 */
	public function getAncestorIDs($ids, $includeSelf = true) {
		$indexedRes = array();
		$parentIDs = $ids;

		while (!empty($parentIDs)) {
			$keys = $this->find('list', array(
				'conditions' => array('Requester.id' => $parentIDs),
				'fields' => array('Requester.id', 'Requester.parent_id')
			));

			$parentIDs = array();
			if (!empty($keys)) {
				foreach ($keys as $selfID => $parentID) {
					if ($includeSelf && !isset($indexedRes[$selfID])) {
						$indexedRes[$selfID] = true;
					}
					if (!empty($parentID)) {
						array_push($parentIDs, $parentID);
						if (!isset($indexedRes[$parentID])) {
							$indexedRes[$parentID] = true;
						}
					}
				}
			}
		}

		return array_keys($indexedRes);;
	}


	/**
	 * Obtain the private key given $requestAs object
	 * @param  array $requestAs can be in one of the following 3 forms:
	 *    i. $this->Book->requestAs = array('id' => 2, 'unencrypted_key' => 'PEM_ENCODED_UNENCRYPTED_PRIVATE_KEY';  // Private key stored externally
	 *   ii. $this->Book->requestAs = array('id' => 23, 'password' => 'PASSWORD_TO_DECRYPT_PVT_KEY');
	 *  iii. $this->Book->requestAs = array('id' => 23); // Private key unencrypted
	 * @return [type]            [description]
	 */
	public function getPrivateKey($requestAs) {
		if (!empty($requestAs['unencrypted_key'])) {
			return $requestAs['unencrypted_key'];
		}

		$entry = $this->find('first', array(
			'conditions' => array('id' => $requestAs['id']),
			'fields' => array('id', 'private_key')
		));
		if (empty($entry)) {
			return false;
		}
		$privateKey = $entry[$this->alias]['private_key'];
		if (preg_match('/-+BEGIN \S*\s?PRIVATE KEY-+/i', $privateKey)) {
			return $privateKey;
		}

		// Attempt to decrypt
		if (empty($requestAs['password'])) {
			return false; // Unable to decrypt, no password provided
		}
		return Lapis::pwDecrypt($privateKey, $requestAs['password']);
	}
}
