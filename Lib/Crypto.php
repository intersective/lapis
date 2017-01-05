<?php
namespace Lapis\Lib;

class Crypto {
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
