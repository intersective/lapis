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
	 * @param string $document Document to encrypt, usually in JSON format.
	 * @param string or array $publicKeys RSA public key(s).
	 * @param string $cipher Encryption method. For list of supported methods, use openssl_get_cipher_methods()
	 * @return array Array with the following elements:
	 *     'cipher' => $cipher used, in plaintext
	 *     'data' => Symmetrically encrypted string
	 *     'key' => Public key-encrypted key for symmetric encryption
	 *     'iv' => Public key-encrypted iv for symmetric encryption
	 */
	public static function docEncrypt($document, $publicKeys, $options = array()) {
		if (is_string($publicKeys)) {
			return static::docEncryptForOne($document, $publicKeys, $options);
		}

		$options = array_merge(array(
			'cipher' => 'aes-256-ctr',
			'keyLength' => 128,
		), $options);

		if (!is_string($document)) {
			$document = json_encode($document);
		}

		$ivLength = openssl_cipher_iv_length($options['cipher']);
		$key = openssl_random_pseudo_bytes($options['keyLength']);
		$iv = openssl_random_pseudo_bytes($ivLength);
		$ciphertext = openssl_encrypt($document, $options['cipher'], $key, OPENSSL_RAW_DATA, $iv);
		$data = $iv . $ciphertext;

		$keys = array();
		foreach ($publicKeys as $i => $publicKey) {
			if (!openssl_public_encrypt($key, $encKey, $publicKey)) {
				return false;
			}
			$keys[$i] = base64_encode($encKey);
		}

		return array(
			'lapis' => 1.0,
			'cipher' => $options['cipher'],
			'data' => base64_encode($data),
			'keys' => $keys
		);
   }

   public static function docEncryptForOne($document, $publicKey, $options = array()) {
   	$results = static::docEncrypt($document, array($publicKey), $options);
   	if (isset($results['keys'][0])) {
	   	$results['key'] = $results['keys'][0];
	   	unset($results['keys']);
	   }
   	return $results;
   }

   public static function docDecrypt($docData, $encDocKey, $privateKey) {
   	if (is_string($docData)) {
   		$docData = json_decode($docData);
   	}
   	$encDocKeyDecoded = base64_decode($encDocKey);
   	$cipher = $docData->cipher;
   	$ivLength = openssl_cipher_iv_length($cipher);
   	$data = base64_decode($docData->data);

		if (!openssl_private_decrypt($encDocKeyDecoded, $key, $privateKey)) {
			return false;
		}
		$iv = substr($data, 0, $ivLength);
		$ciphertext = substr($data, $ivLength);

		$document = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv);

		$docArray = json_decode($document, true);
		if (!$docArray) {
			return $document;
		}
		return $docArray;
   }
}
