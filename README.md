# VLib

This is by no means stable, bla bla bla. It works for what I wrote it for, and will be updated as I need more features, or if you need more features, just submit an issue.

Capabilities
========

* Opening VPK Archives and extracting data
* Opening BSP maps and extracting entities and pakfiles
* Easy usage of Language files/keys via LangWrapper

Installation
============

Install it using Composer:

`composer require nikkiii/vlib`

Usage
=====

To open a BSP file:

```php
<?php
require_once 'vendor/autoload.php';

use VLib\BSP\BSPFile;

$fh = fopen('path/to/map.bsp', 'r');

$bsp = new BSPFile($fh);

// $bsp->version = format version

// $bsp->entities = entities

// $bsp->pakfile = pak file, use openArchive() to get a ZipArchive.
```

To open a VPK file (Uses Flysystem for universal filesystem access):

```php
<?php
require_once 'vendor/autoload.php';

use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

use VLib\VPK\VPKArchive;

$filesystem = new Filesystem(new Local('/path/to/dir/with/vpks'));

$vpk = new VPKArchive($filesystem, 'pak01_dir.vpk');

$entry = $vpk->get('resource/l4d360ui_english.txt');

// To get a stream:
$stream = $entry->stream();

// To automatically call stream_get_contents:
$contents = $entry->getData();
```

Using LangWrapper (Ideally you'd load this data from VPKArchive using KeyValues, Warning: You'll probably need to use mb_convert_encoding from UCS-2LE to UTF-8 on language files)

```php
<?php
require_once 'vendor/autoload.php';

use VLib\LangWrapper;

$tokens = [
	'Something' => 'Blabla'
];

$data = [
	'nested' => [
		'key' => '#Something'
	]
];

$lang = new LangWrapper($tokens, $data);

echo $lang->get('nested.key'); // Blabla
```