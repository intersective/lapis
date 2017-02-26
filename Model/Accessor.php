<?php
App::uses('AppModel', 'Model');
App::uses('Lapis', 'Lapis.Lib');

/**
 * Vault accessors
 *
 * `key` field in this table is to be combined with the `vault_private_key` field from Lapis.Requester
 */
class Accessor extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Accessor';

	/**
	 * Checks if a requester has access to a vault
	 * @param  string/array  $vaultIDs Vault owner's requester ID or array of IDs for multiple
	 * @param  string  $requesterID Accessor's requester ID
	 * @return boolean True for having access to all, false for not having access to at least 1 vault
	 */
	public function hasAccessTo($vaultIDs, $requesterID) {
		if (!is_array($vaultIDs)) {
			$vaultIDs = array($vaultIDs);
		}

		$count = $this->find('count', array(
			'conditions' => array(
				'vault_id' => $vaultIDs,
				'requester_id' => $requesterID
			)
		));

		if (!empty($count) && $count === count($vaultIDs)) {
			return true;
		}
		return false;
	}

	// public function changeVaultIdent
}

