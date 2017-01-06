<?php
App::uses('AppShell', 'Console/Command');
App::uses('Lapis', 'Lapis.Lib');

class KeysShell extends AppShell {
	public $uses = array('Lapis.Key');

	public function main() {
		$this->out('hey');
	}

	public function test() {
		// TODO: To remove this method

		$str = 'Hello world!!';
		$password = 'kkkkkkkkkkk';
		$pwEnc = Lapis::pwEncrypt($str, $password);
		$this->out('pwEnc: ' . $pwEnc); // ensure diff every time
		$this->out('decrypted: ' . Lapis::pwDecrypt($pwEnc, $password));

		$keypair = $this->Key->find('first');

		$doc = array('test' => 123, 'xmas' => 'tree');
		debug($doc);
		$doc = json_encode($doc); // this should be part of Lapis
		debug($doc);

		$res = Lapis::docEncrypt($doc, $keypair['Key']['public_key']);
		debug($res); // this should also be diff everytime

		$decryptedDoc = Lapis::docDecrypt($res, $keypair['Key']['private_key']);
		debug($decryptedDoc);

	}

	public function generate() {
		// TODO: options
		// --size
		// --root
		// --parent_id
		// --file
		// --password
		// -y input-free

		$options = array(
			'parentID' => null,
			'savePrivateToDb' => true,
			'privateKeyLocation' => null,
			'password' => null
		);

		$isRoot = $this->in('Is this a root key pair?', array('y', 'n'), 'y');
		if ($isRoot !== 'y') {
			$options['parentID'] = $this->in('Enter parent key ID:');
		}
		$savePrivateToDb = $this->in('Save root private key to database?', array('y', 'n'), 'y');
		if ($savePrivateToDb === 'y') {
			$options['savePrivateToDb'] = true;
			$options['password'] = $this->in("WARNING: It is a good practice to not store private key unencrypted in database.\nEnter password to encrypt private key before storing in database, blank for none (no encryption).");
		} else {
			$options['savePrivateToDb'] = false;
			$options['privateKeyLocation'] = $this->in('Enter private key location to save to:', null, APP . 'private.key');
		}

		$this->out('Generating root public key pair...');
		$keys = Lapis::genKeyPair();

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

		$this->Key->create();
		$ok = $this->Key->save($data);

		if ($ok) {
			$this->out('Key pair generated and saved successfully with key ID: ' . $this->Key->getLastInsertID());

			if (!$options['savePrivateToDb']) {
				if (file_put_contents($options['privateKeyLocation'], $keys['private'])) {
					$this->out('Private key is written successfully to ' . $options['privateKeyLocation']);
				} else {
					$this->error('Failed to write private key to ' . $options['privateKeyLocation']);
				}
			}
		}
	}


	/**
	 * Generate a pair of private and public keys
	 * Outputs the keys to console by default
	 *
	 * @return void
	 */
	public function generateOld($options = array()) {
		$options = array_merge(array(
			'keysize' => 4096,
		), $options);

		$ssl = openssl_pkey_new(array(
			'private_key_bits' => $options['keysize']
		));

		openssl_pkey_export($ssl, $privkey);
		debug($privkey);

		$pubkey = openssl_pkey_get_details($ssl);
		debug($pubkey['key']);

		// TODO: proper schema compatible return

	}
}

