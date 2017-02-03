<?php
class RequesterTest extends CakeTestCase {

	public $fixtures = array('plugin.lapis.requester');

	public function setUp() {
		$this->Requester = ClassRegistry::init('Lapis.Requester');
	}

	public function testGenerate() {
		$password = 'Passphrase to encrypt private key';
		$this->assertTrue($this->Requester->generate($password));
		$id = $this->Requester->getLastInsertID();

		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $id)
		));

		$this->assertNull($requester['Requester']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $requester['Requester']['ident_public_key']);
		$this->assertNotEmpty($requester['Requester']['ident_private_key']);
		$this->assertNotContains('BEGIN PRIVATE KEY', $requester['Requester']['ident_private_key']);

		// No password
		$this->assertTrue($this->Requester->generate(null, array(
			'parent' => $id,
			'keysize' => 1024
		)));
		$secondID = $this->Requester->getLastInsertID();
		$this->assertNotEquals($id, $secondID);

		$requester = $this->Requester->find('first', array(
			'conditions' => array('Requester.id' => $secondID)
		));
		$this->assertEquals($id, $requester['Requester']['parent_id']);
		$this->assertContains('BEGIN PUBLIC KEY', $requester['Requester']['ident_public_key']);
		$this->assertContains('BEGIN PRIVATE KEY', $requester['Requester']['ident_private_key']);
	}
}
