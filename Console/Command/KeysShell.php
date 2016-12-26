<?php
App::uses('AppShell', 'Console/Command');

class KeysShell extends AppShell {
	public function main() {
		$this->out('hey');
	}

	/**
	 * Generate a pair of private and public keys
	 * Outputs the keys to console by default
	 *
	 * @return void
	 */
	public function generate($options = array()) {
		$keysize = 2048;
		$ssl = openssl_pkey_new(array(
			'private_key_bits' => $keysize
		));

		openssl_pkey_export($ssl, $privkey);
		debug($privkey);

		$pubkey = openssl_pkey_get_details($ssl);
		debug($pubkey['key']);

		// TODO: proper schema compatible return

	}
}

