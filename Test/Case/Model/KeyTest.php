<?php
class ExperienceTest extends CakeTestCase {

	public $fixtures = array('plugin.lapis.key');

	public function setUp() {
		$this->Key = ClassRegistry::init('Lapis.Key');
	}

	public function testGenerate() {
		$password = 'Passphrase to encrypt private key';
		$this->assertTrue($this->Key->generate($password));
		$id = $this->Key->getLastInsertID();
		$this->assertEquals(1, $id);

		$key = $this->Key->find('first', array(
			'conditions' => array('Key.id' => $id)
		));

		$this->assertNull($key['Key']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $key['Key']['public_key']);
		$this->assertNotContains('BEGIN PRIVATE KEY', $key['Key']['private_key']);

		// No password
		$this->assertTrue($this->Key->generate(null, array(
			'parentID' => $id,
			'keysize' => 1024
		)));
		$secondID = $this->Key->getLastInsertID();
		$this->assertEquals(2, $secondID);

		$key = $this->Key->find('first', array(
			'conditions' => array('Key.id' => $secondID)
		));
		$this->assertEquals(1, $key['Key']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $key['Key']['public_key']);
		$this->assertContains('BEGIN PRIVATE KEY', $key['Key']['private_key']);
	}
}
