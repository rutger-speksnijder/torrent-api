# Transmission API

## Installation
Create a composer.json file with:
```
{
    "minimum-stability": "dev"
}
```

Then run the following command:
```
composer require rutger/transmission-api "dev-master"
```

This installs all required libraries.

After installation is done, copy the index.php, .htaccess and the two config files to the root of the location from which you want to run the API.
Edit the configuration files if needed.

You will need to enter the transmission username and password in the transmission-config.php file,
as well as the whitelist for the IP addresses you would like to have access to the API.

## Package "transmission-php" fixes
A few fixes need to be applied to the transmission-php package for it to work correctly,
since I couldn't include my edited version of that repository in this package.

In "vender/kleiram/transmission-php/lib/Transmission/Model/Torrent.php, replace:

```php
$this->eta = (integer) $eta;
```

with:

```php
$this->eta = (double) $eta;
```

and:

```php
$this->size = (integer) $size;
```

with:

```php
$this->size = (double) $size;
```

and:

```php
return $this->status->getValue();
```

with:

```php
if ($this->status !== null) {
    return $this->status->getValue();
}
return null;
```

In "vender/kleiram/transmission-php/lib/Transmission/Model/File.php, replace:

```php
$this->size = (integer) $size;
```

with:

```php
$this->size = (double) $size;
```

## Endpoints
 - GET: /torrent. Returns an array of all torrents. Optional parameter: minimal, 1/0 whether to retrieve minimal data. Defaults to 0.
 - GET: /torrent/[0-9]. Returns a single torrent by id. Optional parameter: minimal, 1/0 whether to retrieve minimal data. Defaults to 0.
 - GET: /torrent/[a-zA-Z0-9]. Returns a single torrent by hash. Optional parameter: minimal, 1/0 whether to retrieve minimal data. Defaults to 0.
 - POST: /torrent. Adds a torrent from a magnet uri. Required parameter: uri, should contain the magnet uri.
 - DELETE: /torrent/[0-9]. Deletes a torrent by id. Optional parameter: files, 1/0 whether to delete files from disk.
 - DELETE: /torrent/[a-zA-Z0-9]. Deletes a torrent by hash. Optional parameter: files, 1/0 whether to delete files from disk.
 - GET: /torrent/[0-9]/start. Starts a torrent by id. Optional parameter: now, 1/0 whether to start immediately regardless of queue.
 - GET: /torrent/[a-zA-Z0-9]/start. Starts a torrent by hash. Optional parameter: now, 1/0 whether to start immediately regardless of queue.
 - GET: /torrent/[0-9]/stop. Stops a torrent by id.
 - GET: /torrent/[a-zA-Z0-9]/stop. Stops a torrent by hash.
 - GET: /torrent/[0-9]/verify. Verifies a torrent by id.
 - GET: /torrent/[a-zA-Z0-9]/verify. Verifies a torrent by hash.
 - GET: /torrent/[0-9]/reannounce. Reannounces a torrent by id.
 - GET: /torrent/[a-zA-Z0-9]/reannounce. Reannounces a torrent by hash.
