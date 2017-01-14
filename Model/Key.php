<?php
/**
 * User public and private keys
 */
App::uses('AppModel', 'Model');

class Key extends AppModel {
	public $tablePrefix = 'lapis_';
	public $name = 'Key';

	/**
	 * Return a list of ancestor IDs including self
	 */
	public function getAncestorIDs($ids, $includeSelf = true) {
		$indexedRes = array();
		$parentIDs = $ids;

		while (!empty($parentIDs)) {
			$keys = $this->find('list', array(
				'conditions' => array('Key.id' => $parentIDs),
				'fields' => array('Key.id', 'Key.parent_id')
			));

			$parentIDs = array();
			if (!empty($keys)) {
				foreach ($keys as $selfID => $parentID) {
					if ($includeSelf && !isset($indexedRes[$selfID])) {
						$indexedRes[$selfID] = true;
					}
					if (!empty($parentID)) {
						array_push($parentIDs, $parentID);
						if (!isset($indexedRes[$parentID])) {
							$indexedRes[$parentID] = true;
						}
					}
				}
			}
		}

		return array_keys($indexedRes);;
	}
}
