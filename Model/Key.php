<?php
/**
 * User public and private keys
 */
App::uses('AppModel', 'Model');
App::uses('Lapis', 'Lapis.Lib');

class Key extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Key';

	/**
	 * Generate RSA key pair
	 */
	public function generate($password, $options = array()) {
		$options = array_merge(array(
			'keysize' => 4096,
			'parentID' => null,
			'savePrivateToDb' => true,
			'privateKeyLocation' => null
		), $options);
		$options['password'] = $password;

		$keys = Lapis::genKeyPair($options['keysize']);

		$data = array(
			'parent_id' => $options['parentID'],
			'public_key' => $keys['public'],
		);

		if ($options['savePrivateToDb']) {
			if (!empty($options['password'])) {
				$data['private_key'] = Lapis::pwEncrypt($keys['private'], $options['password']);
			} else {
				$data['private_key'] = $keys['private'];
			}
		}

		$this->create();
		$ok = $this->save($data);

		if ($ok) {
			if (!$options['savePrivateToDb']) {
				return file_put_contents($options['privateKeyLocation'], $keys['private']);
			}
			return true;
		}

		return false;
	}

	/**
	 * Return a list of ancestor IDs including self
	 */
	public function getAncestorIDs($ids, $includeSelf = true) {
		$indexedRes = array();
		$parentIDs = $ids;

		while (!empty($parentIDs)) {
			$keys = $this->find('list', array(
				'conditions' => array('Key.id' => $parentIDs),
				'fields' => array('Key.id', 'Key.parent_id')
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
