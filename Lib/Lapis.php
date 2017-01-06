<?php
class Lapis {

	/**
	 * Generate RSA public and private key pair
	 *
	 * @param  integer $keysize Key size in bits
	 * @return array of public and private key pair in PEM format
	 */
	public static function genKeyPair($keysize = 4096) {
		$res = openssl_pkey_new(array(
			'private_key_bits' => $keysize
		));

		openssl_pkey_export($res, $privkey);
		$pubkey = openssl_pkey_get_details($res);

		return array(
			'private' => $privkey,
			'public' => $pubkey['key']
		);
	}

	/**
	 * Encrypt message with $password
	 * Usually for encrypting of private key before storing in database
	 *
	 * @param  [type] $message
	 * @param  [type] $password
	 * @param  string $cipher   Encryption method. For list of supported methods, use openssl_get_cipher_methods()
	 * @return string Encrypted string
	 */
	public static function pwEncrypt($message, $password, $cipher = 'aes-256-ctr') {
		$ivLength = openssl_cipher_iv_length($cipher);
		$iv = openssl_random_pseudo_bytes($ivLength);
		$key = openssl_digest($password, 'sha256', true);
		$ciphertext = openssl_encrypt($message, $cipher, $key, OPENSSL_RAW_DATA, $iv);
		return base64_encode($iv . $ciphertext);
	}

	/**
	 * Decrypt message with $password
	 * Usually for decrypting of private key
	 *
	 * @param  [type] $data Encrypted data
	 * @param  [type] $password
	 * @param  string $cipher   Encryption method. For list of supported methods, use openssl_get_cipher_methods()
	 * @return string Decrypted string
	 */
	public static function pwDecrypt($data, $password, $cipher = 'aes-256-ctr') {
		$key = openssl_digest($password, 'sha256', true);
		$ivLength = openssl_cipher_iv_length($cipher);
		$rawData = base64_decode($data);
		$iv = substr($rawData, 0, $ivLength);
		$ciphertext = substr($rawData, $ivLength);
		return openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
	}

	/**
	 * Symmetrically encrypts a message via $method with securely randomly
	 * generated key and initialization vector (IV)
	 * Key and IV are subsequently encrypted with public key
	 *
	 * @param string $message The message to encrypt.
	 * @param string $publicKey RSA public key.
	 * @param string $cipher Encryption method. For list of supported methods, use openssl_get_cipher_methods()
	 * @return array Array with the following elements:
	 *     'cipher' => $cipher used, in plaintext
	 *     'data' => Symmetrically encrypted string
	 *     'key' => Public key-encrypted key for symmetric encryption
	 *     'iv' => Public key-encrypted iv for symmetric encryption
	 */
	public static function encrypt($message, $publicKey, $options = array()) {
		$options = array_merge(array(
			'cipher' => 'aes-256-cbc',
			'keyLength' => 32,
			'ivLength' => null
		), $options);
		if (is_null($options['ivLength'])) {
			$options['ivLength'] = openssl_cipher_iv_length($cipher);
		}

		$key = openssl_random_pseudo_bytes($options['keyLength']);
		$iv = openssl_random_pseudo_bytes($options['ivLength']);
		$data = openssl_encrypt($message, $options['cipher'], $key, OPENSSL_RAW_DATA, $iv);

		openssl_public_encrypt($key, $encKey, $publicKey);
		openssl_public_encrypt($iv, $encIV, $publicKey);

		return array(
			'cipher' => $options['cipher'],
			'data' => $data,
			'key' => $encKey,
			'iv' => $encIv
		);
   }
}
