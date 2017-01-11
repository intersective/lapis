# Lapis
CakePHP 2.x plugin providing highly-secured public-key encryption to database I/O.

**This is a work-in-progress**. The implementation, documentation and sample code are not only incomplete, they may also change without notice.

3.x plugin will follow soon.

Lapis is named after [Kue lapis](https://en.wikipedia.org/wiki/Kue_lapis), a layered cake from Southeast Asia. Similar to the cake, Lapis plugin incorporates a multi-layered encryption scheme to keep your data safe.

## Motivation

_TODO: why Lapis_

## How

1. Install Lapis plugin to your local CakePHP app.

1. Create the necessary tables by running:

	```bash
	Console/cake schema create --plugin Lapis
	```

1. Generate the root key pair(s) by following the guided key generator.

	```bash
	Console/cake Lapis.keys create    # follow the guided prompts
	```

	It is highly recommended to not store private keys unencrypted at the database. If you provide a password to the private key, take note that the password is not stored anywhere in the system. You would have to store the password safely and separately outside of the system.

## Sample model

```php
<?php
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

## Notes

1. It is highly recommended to not store private keys unencrypted at the database.


