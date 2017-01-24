<?php
App::uses('AppShell', 'Console/Command');
App::uses('Lapis', 'Lapis.Lib');

class KeysShell extends AppShell {
	public $uses = array('Lapis.Key');

	public function main() {
		$this->out('Lapis keys generator.');
	}

	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->addOption('yes', array(
		  'short' => 'y',
		  'help' => 'Do not prompt for confirmation. Be careful!',
		  'boolean' => true
		));
		return $parser;
	}

	public function create() {
		// TODO: options
		// --size
		// --root
		// --parent_id
		// --file
		// --password

		$options = array(
			'parentID' => null,
			'savePrivateToDb' => true,
			'privateKeyLocation' => null,
			'password' => null
		);
		$toAsk = array(
			'parentID' => true,
			'save' => true,
		);

		if ($this->params['yes']) {
			$this->out('Interaction free mode.');
			$toAsk['parentID'] = false;
			$toAsk['save'] = false;
		}

		if ($toAsk['parentID']) {
			$isRoot = $this->in('Is this a root key pair?', array('y', 'n'), 'y');
			if ($isRoot !== 'y') {
				$options['parentID'] = $this->in('Enter parent key ID:');
			}
		}
		if ($toAsk['save']) {
			$savePrivateToDb = $this->in('Save root private key to database?', array('y', 'n'), 'y');
			if ($savePrivateToDb === 'y') {
				$options['savePrivateToDb'] = true;
				$options['password'] = $this->in("WARNING: It is a good practice to not store private key unencrypted in database.\nEnter password to encrypt private key before storing in database, blank for none (no encryption).");
			} else {
				$options['savePrivateToDb'] = false;
				$options['privateKeyLocation'] = $this->in('Enter private key location to save to:', null, APP . 'private.key');
			}
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
}

