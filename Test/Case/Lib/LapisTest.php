<?php
App::uses('Lapis', 'Lapis.Lib');

class LapisTest extends CakeTestCase {
	public function testGenKeyPair() {
		$keypair = Lapis::genKeyPair();

		$this->assertContains('BEGIN PRIVATE KEY', $keypair['private']);
		$this->assertContains('BEGIN PUBLIC KEY', $keypair['public']);

		// Check that the keys are usable
		$data = 'Hello Lapis';
		$this->assertTrue(openssl_public_encrypt($data, $crypted, $keypair['public']));
		$this->assertNotEquals($data, $crypted);
		$this->assertTrue(openssl_private_decrypt($crypted, $decrypted, $keypair['private']));
		$this->assertEquals($data, $decrypted);

		// Simple way to compare key size
		$shorterKeyPair = Lapis::genKeyPair(512);
		$longerKeyPair = Lapis::genKeyPair(2048);
		$this->assertGreaterThan(strlen($shorterKeyPair['private']), strlen($longerKeyPair['private']));
	}
}
