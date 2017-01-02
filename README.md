# Transmission API

## Todo
 - files in src folder
 - edit autoload in composer
 - installation instructions on copying index.php, .htaccess, and the two config files to root.

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

## Requirements
 - https://github.com/kleiram/transmission-php (with additional commit from https://github.com/rutger-speksnijder/transmission-php)

## Authentication
Transmission username and password are required for each request.

## Endpoints
 - GET: /torrent. Returns an array of all torrents.
 - GET: /torrent/[0-9]. Returns a single torrent by id.
 - GET: /torrent/[a-zA-Z0-9]. Returns a single torrent by hash.
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
