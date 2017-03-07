<?php
App::uses('AppShell', 'Console/Command');
App::uses('Lapis', 'Lapis.Lib');

class KeysShell extends AppShell {
	public $uses = array('Lapis.Requester');

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

	public function generate() {
		// TODO: options
		// --keysize
		// --root
		// --parent_id
		// --file
		// --password

		$options = array(
			'keysize' => 4096,
			'parent' => null,
			'savePrivateToDb' => true,
			'privateKeyLocation' => null,
			'password' => null
		);
		$toAsk = array(
			'parent' => true,
			'save' => true,
		);

		if ($this->params['yes']) {
			$this->out('Interaction free mode.');
			$toAsk['parent'] = false;
			$toAsk['save'] = false;
		}

		if ($toAsk['parent']) {
			$isRoot = $this->in('Is this a root key pair?', array('y', 'n'), 'y');
			if ($isRoot !== 'y') {
				$options['parent'] = $this->in('Enter parent key ID:');
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
		if ($this->Requester->generate($options['password'], $options)) {
			$this->out('Key pair generated and saved successfully with key ID: ' . $this->Requester->getLastInsertID());

			if (!$options['savePrivateToDb']) {
				$this->out('Private key is written successfully to ' . $options['privateKeyLocation']);
			}
		} else {
			$this->out('Failure encountered when generating key pair.');
		}
	}
}

