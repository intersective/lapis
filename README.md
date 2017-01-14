# Lapis
CakePHP 2.x plugin providing highly-secured public-key encryption to database I/O.

**This is a work-in-progress**. The implementation, documentation and sample code are not only incomplete, they may also change without notice.

3.x plugin will follow soon.

Lapis is named after [Kue lapis](https://en.wikipedia.org/wiki/Kue_lapis), a layered cake from Southeast Asia. Similar to the cake, Lapis plugin incorporates a multi-layered encryption scheme to keep your data safe.

## Motivation

_TODO: why Lapis_

## Set up

1. Install Lapis plugin to your local CakePHP app.

1. Create the necessary tables by running:

	```bash
	Console/cake schema create --plugin Lapis
	```

1. Generate the root key pair(s) by following the guided key generator. You would need at least 1 root key pair to use Lapis.

	```bash
	Console/cake Lapis.keys create    # follow the guided prompts
	```

	It is highly recommended to not store private keys unencrypted at the database. If you provide a password to the private key, take note that the password is not stored anywhere in the system. You would have to store the password safely and separately outside of the system.

## Sample model

1. To prepare a model for Lapis secured document, add a text field named `document` to the associated table.

	```sql
	ALTER TABLE `table_name` ADD `document` TEXT NULL DEFAULT NULL;
	```

	On top of `document` field, you are free to still include other conventional fields such as `id`, `created`, or custom fields such as `title`, etc. Take note that data in conventional fields will not be encrypted, but being native, they would continue to enjoy database-level privilege such as indexing, etc.

1. Update your Model to include `Lapis.SecDoc` behavior and define document schema. Lapis supports the following JSON data types: `string`, `number`, or `boolean`. If you prefer to not enforce data type, you can either specify a document field as `inherit` or use a non-associated array.

	For illustration, a Book model with Lapis secured document.

	```php
	class Book extends AppModel {
		public $name = 'Book';
		public $actsAs = array('Lapis.SecDoc');

		/**
		 * Either number, string or boolean
		 */
		public $documentSchema = array(
			'author' => 'string',
			'pages' => 'number',
			'available' => 'boolean'
		);

		// or if you prefer to not enforce JSON data types, you can list the schema as such
		// public $documentSchema = array('author', 'pages', 'available');
	}
	```

1. To save to a secured document model, you would specify the _lowest key(s)_ you would want to provide access privilege to. Lapis would sign the document for all the specified public keys and their respective ancestors all the way to root key(s).

	```php
	$data = array(
		// Conventional database fields
		'title' => 'Book Title',

		// Secured document
		'author' => 'John Doe',
		'pages' => 488,
		'available' => true
	);

	$this->Book->forKeys = 2;
	$this->Book->forKeys = array(2, 5); // for multiple lowest keys

	$this->Book->create();
	$this->Book->save($data);
	```

	Assuming the key hierarchy is as illustrated:

	```php
	/*
	 * 1 (root) => 2 => 9
	 * 3 (root) => 4 => 5
	 **/

	$this->Book->forKeys = 2;
	// would provide access to keys with IDs: 2 and 1 (its ancestors), but not 3 (even though it is a root key)

	$this->Book->forKeys = array(2, 5);
	// would provide access to keys with IDs: 2, 1; and 5, 4, 3.
	```

	If no valid `forKeys` are provided, Lapis would sign for _all_ root (parent-less) keys.

1. To query a secured document model, you would have to provide either the unencrypted private key that has privileged access to the document, or the password to the encrypted private key that has privileged access to the document.

	```php
	// Specifying unencrypted private key in PEM encoded format, including header and footer.
	$this->Book->privateKey = array('id' => 2, 'unencrypted_key' => 'PEM_ENCODED_UNENCRYPTED_PRIVATE_KEY';

	// or, the password to an encrypted private key in `keys` table
	$this->Book->privateKey = array('id' => 23, 'password' => 'PASSWORD_TO_DECRYPT_PVT_KEY');

	// if private key is stored unencrypted in database (not recommended), id is all that is required.
	$this->Book->privateKey = array('id' => 23);

	$this->Book->find('first', array(
		'conditons' => array('Book.id' => 2)
	));

	// If the supplied private key has privileged access to the document, unencrypted document fields would be returned normally just like a normal database fields.
	// Otherwise, only database fields would be returned encrypted.
	```


## Notes

1. It is highly recommended to not store private keys unencrypted at the database.


