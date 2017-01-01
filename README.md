torrent-api

# Authentication
Transmission username and password are required for each request.

# Supported endpoints
 - GET: /all. Returns an array of all torrents.
 - GET: /get/[0-9]. Returns a single torrent by id.
 - GET: /get/[a-zA-Z0-9]. Returns a single torrent by hash.
 - POST: /add. Adds a torrent from a magnet uri. Required parameter: uri, should contain the magnet uri.
 - DELETE: /delete/[0-9]. Deletes a torrent by id. Optional parameter: files, 1/0 whether to delete files from disk.
 - DELETE: /delete/[a-zA-Z0-9]. Deletes a torrent by hash. Optional parameter: files, 1/0 whether to delete files from disk.
 - GET: /start/[0-9]. Starts a torrent by id. Optional parameter: now, 1/0 whether to start immediately regardless of queue.
 - GET: /start/[a-zA-Z0-9]. Starts a torrent by hash. Optional parameter: now, 1/0 whether to start immediately regardless of queue.
 - GET: /stop/[0-9]. Stops a torrent by id.
 - GET: /stop/[a-zA-Z0-9]. Stops a torrent by hash.
 - GET: /verify/[0-9]. Verifies a torrent by id.
 - GET: /verify/[a-zA-Z0-9]. Verifies a torrent by hash.
 - GET: /reannounce/[0-9]. Reannounces a torrent by id.
 - GET: /reannounce/[a-zA-Z0-9]. Reannounces a torrent by hash.

# Requires
 - https://github.com/kleiram/transmission-php (with additional commit from https://github.com/rutger-speksnijder/transmission-php)

# Todo
 - composer file
 - use restphp
