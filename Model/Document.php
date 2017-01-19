<?php
/**
 * Document keys encrypted with user's public key
 */
App::uses('AppModel', 'Model');

class Document extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Document';

	/**
	 * Returns encrypted document password for requester
	 */
	public function getEncryptedPassword($modelID, $keyID) {
		$entry = $this->find('first', array(
			'conditions' => array(
				'Document.model_id' => $modelID,
				'Document.key_id' => $keyID
			),
			'fields' => array('id', 'document_pw')
		));
		if (empty($entry)) {
			return false;
		}
		return $entry[$this->alias]['document_pw'];
	}
}
