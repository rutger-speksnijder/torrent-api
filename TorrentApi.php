<?php
/**
 * Class to handle torrent api calls.
 *
 * @author Rutger Speksnijder.
 * @since torrent-api 1.0.
 * @package torrent-api.
 * @license MIT.
 */
class TorrentApi
{
    /**
     * The result of the api call.
     * @var array.
     */
    private $result;

    /**
     * A value indicating whether an error occurred.
     * @var boolean.
     */
    private $error;

    /**
     * A list of headers to output.
     * @var array.
     */
    private $headers;

    /**
     * The request data.
     * @var array.
     */
    private $request;

    /**
     * The transmission object.
     * @var Transmission\Transmission.
     */
    private $transmission;

    /**
     * The transmission's client object.
     * @var Transmission\Client.
     */
    private $client;

    /**
     * Constructs a new isntance of the torrent api class.
     */
    public function __construct()
    {
        // Set properties
        $this->result = [];
        $this->error = false;
        $this->headers = [];

        // Get request data
        $this->request = $this->getRequestData();
        if (empty($this->request['username']) || empty($this->request['password'])) {
            $this->unauthorized();
            exit;
        }

        // Load the configuration file
        $config = require 'config.php';

        // Authenticate
        $this->client = new Transmission\Client($config['host'], $config['port']);
        $this->client->authenticate($this->request['username'], $this->request['password']);

        // Create the transmission object
        $this->transmission = new Transmission\Transmission();
        $this->transmission->setClient($this->client);
    }

    /**
     * Gets the current request data.
     *
     * @return array An array with data.
     */
    private function getRequestData()
    {
        // Check php://input for PUT/PATCH/DELETE requests
        parse_str(file_get_contents('php://input'), $data);
        if (!empty($data)) {
            return $data;
        }

        // Check the POST variable for data
        if (!empty($_POST)) {
            return $_POST;
        }

        // Return the GET variable
        return $_GET;
    }

    /**
     * Adds routes to the router object.
     *
     * @param SimpleRoute\Router $router The router object.
     *
     * @return SimpleRoute\Router The modified router object.
     */
    public function addRoutes($router)
    {
        // Not found
        $router->add('/', [$this, 'notFound']);

        // Torrent routes
        $router->get('/all', [$this, 'all']);
        $router->get('/get/([0-9]+)', [$this, 'getById']);
        $router->get('/get/([a-zA-Z0-9]+)', [$this, 'getByHash']);
        $router->post('/add', [$this, 'add']);
        $router->delete('/delete/([0-9]+)', [$this, 'deleteById']);
        $router->delete('/delete/([a-zA-Z0-9]+)', [$this, 'deleteByHash']);
        $router->get('/start/([0-9]+)', [$this, 'startById']);
        $router->get('/start/([a-zA-Z0-9]+)', [$this, 'startByHash']);
        $router->get('/stop/([0-9]+)', [$this, 'stopById']);
        $router->get('/stop/([a-zA-Z0-9]+)', [$this, 'stopByHash']);
        $router->get('/verify/([0-9]+)', [$this, 'verifyById']);
        $router->get('/verify/([a-zA-Z0-9]+)', [$this, 'verifyByHash']);
        $router->get('/reannounce/([0-9]+)', [$this, 'reannounceById']);
        $router->get('/reannounce/([a-zA-Z0-9]+)', [$this, 'reannounceByHash']);

        // Return the modified router
        return $router;
    }

    /**
     * Handles not found queries.
     *
     * @return $this The current object.
     */
    public function notFound()
    {
        // Output the not found error
        $this->headers[] = 'HTTP/1.1 404 Not Found';
        $this->error = true;
        if (empty($this->result)) {
            $this->result = ['message' => 'Endpoint not found.'];
        }
        return $this->output();
    }

    /**
     * Handles unauthorized queries.
     *
     * @return $this The current object.
     */
    private function unauthorized()
    {
        // Output the unauthorized error
        $this->headers[] = 'HTTP/1.1 401 Unauthorized';
        $this->error = true;
        $this->result = ['message' => 'Unauthorized request.'];
        return $this->output();
    }

    /**
     * Handles bad requests.
     *
     * @return $this The current object.
     */
    private function badRequest()
    {
        // Output the bad request error
        $this->headers[] = 'HTTP/1.1 400 Bad Request';
        $this->error = true;
        if (empty($this->result)) {
            $this->result = ['message' => 'Bad request.'];
        }
        return $this->output();
    }

    /**
     * Handles server errors.
     *
     * @return $this The current object.
     */
    private function serverError()
    {
        // Output the internal server error
        $this->headers[] = 'HTTP/1.1 500 Internal Server Error';
        $this->error = true;
        if (empty($this->result)) {
            $this->result = ['message' => 'Internal Server Error.'];
        }
        return $this->output();
    }

    /**
     * Handles connection errors.
     *
     * @param Exception $exception An exception object.
     *
     * @return $this The current object.
     */
    private function handleErrors($ex)
    {
        // Switch on the exception's message
        switch ($ex->getMessage()) {
            case 'Could not connect to Transmission':
                $this->result = ['message' => 'Internal Server Error. Unable to connect to Transmission.'];
                return $this->serverError();
            case 'Access to Transmission requires authentication':
                return $this->unauthorized();
        }
        return $this;
    }

    /**
     * Outputs all torrents.
     *
     * @return $this The current object.
     */
    public function all()
    {
        // Get all torrents and convert them to arrays
        try {
            $torrents = $this->convertTorrentsToArrays($this->transmission->all());
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }
        $this->result = ['torrents' => $torrents];
        return $this->output();
    }

    /**
     * Finds a torrent by either has or id.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return mixed Torrent object if found, script fails otherwise.
     */
    private function find($identifier)
    {
        // Get the torrent
        $torrent = false;
        try {
            $torrent = $this->transmission->get($identifier);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Check if we found the torrent
        if (!$torrent) {
            $this->result = ['message' => 'Torrent not found.'];
            return $this->notFound();
        }
        return $torrent;
    }

    /**
     * Returns a torrent by either hash or id.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function get($identifier)
    {
        // Get the torrent
        $torrent = $this->find($identifier);
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['torrent' => $torrent];
        return $this->output();
    }

    /**
     * Gets a torrent by id.
     *
     * @param int $id The torrent's id.
     *
     * @return $this The current object.
     */
    public function getById($id)
    {
        return $this->get((int)$id);
    }

    /**
     * Gets a torrent by hash.
     *
     * @param string $hash The torrent's hash.
     *
     * @return $this The current object.
     */
    public function getByHash($hash)
    {
        return $this->get($hash);
    }

    /**
     * Adds a torrent.
     *
     * @return $this The current object.
     */
    public function add()
    {
        // Check if we have a URI
        if (empty($this->request['uri'])) {
            // Send a bad request error
            $this->result = ['message' => 'Invalid arguments: Missing uri parameter.'];
            return $this->badRequest();
        }

        // Add the torrent
        $torrent = false;
        try {
            $torrent = $this->transmission->add($this->request['uri']);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Check if the torrent was added
        if (!$torrent) {
            $this->result = ['message' => 'Torrent could not be added.'];
            return $this->serverError();
        }

        // Return the torrent
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['torrent' => $torrent];
        return $this->output();
    }

    /**
     * Deletes a torrent by either has or id.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function delete($identifier)
    {
        // Get the torrent
        $torrent = $this->find($identifier);

        // Check if we should delete files
        $deleteFiles = isset($this->request['files']) && $this->request['files'] == '1';

        // Delete the torrent
        try {
            $this->transmission->remove($torrent, $deleteFiles);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $this->result = ['message' => 'Torrent deleted.'];
        return $this->output();
    }

    /**
     * Deletes a torrent by id.
     *
     * @param int $id The id.
     *
     * @return $this The current object.
     */
    public function deleteById($id)
    {
        return $this->delete((int)$id);
    }

    /**
     * Deletes a torrent by hash.
     *
     * @param string $hash The hash.
     *
     * @return $this The current object.
     */
    public function deleteByHash($hash)
    {
        return $this->delete($hash);
    }

    /**
     * Starts a torrent by either id or hash.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function start($identifier)
    {
        // Find the torrent
        $torrent = $this->find($identifier);

        // Check if we should start immediately
        $startNow = isset($this->request['now']) && $this->request['now'] == '1';

        // Start the torrent
        try {
            $this->transmission->start($torrent, $startNow);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['message' => 'Torrent started.', 'torrent' => $torrent];
        return $this->output();
    }

    /**
     * Starts a torrent by id.
     *
     * @param int $id The torrent's id.
     *
     * @return $this The current object.
     */
    public function startById($id)
    {
        return $this->start((int)$id);
    }

    /**
     * Starts a torrent by hash.
     *
     * @param string $hash The torrent's hash.
     *
     * @return $this The current object.
     */
    public function startByHash($hash)
    {
        return $this->start($hash);
    }

    /**
     * Stops a torrent by either id or hash.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function stop($identifier)
    {
        // Find the torrent
        $torrent = $this->find($identifier);

        // Stop the torrent
        try {
            $this->transmission->stop($torrent);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['message' => 'Torrent stopped.', 'torrent' => $torrent];
        return $this->output();
    }

    /**
     * Stops a torrent by id.
     *
     * @param int $id The torrent's id.
     *
     * @return $this The current object.
     */
    public function stopById($id)
    {
        return $this->stop((int)$id);
    }

    /**
     * Stops a torrent by hash.
     *
     * @param strign $hash The torrent's hash.
     *
     * @return $this The current object.
     */
    public function stopByHash($hash)
    {
        return $this->stop($hash);
    }

    /**
     * Verifies a torrent by either id or hash.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function verify($identifier)
    {
        // Find the torrent
        $torrent = $this->find($identifier);

        // Verify the torrent
        try {
            $this->transmission->verify($torrent);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['message' => 'Torrent verification started.', 'torrent' => $torrent];
        return $this->output();
    }

    /**
     * Verifies a torrent by id.
     *
     * @param int $id The torrent's id.
     *
     * @return $this The current object.
     */
    public function verifyById($id)
    {
        return $this->verify((int)$id);
    }

    /**
     * Verifies a torrent by hash.
     *
     * @param string $hash The torrent's hash.
     *
     * @return $this The current object.
     */
    public function verifyByHash($hash)
    {
        return $this->verify($hash);
    }

    /**
     * Reannounces a torrent by either id or hash.
     *
     * @param mixed $identifier The torrent's identifier.
     *
     * @return $this The current object.
     */
    private function reannounce($identifier)
    {
        // Find the torrent
        $torrent = $this->find($identifier);

        // Reannounce the torrent
        try {
            $this->transmission->reannounce($torrent);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $torrent = $this->convertTorrentsToArrays([$torrent])[0];
        $this->result = ['message' => 'Torrent reannounce started.', 'torrent' => $torrent];
        return $this->output();
    }

    /**
     * Reannounces a torrent by id.
     *
     * @param int $id The torrent's id.
     *
     * @return $this The current object.
     */
    public function reannounceById($id)
    {
        return $this->reannounce((int)$id);
    }

    /**
     * Reannounces a torrent by hash.
     *
     * @param string $hash The torrent's hash.
     *
     * @return $this The current object.
     */
    public function reannounceByHash($hash)
    {
        return $this->reannounce($hash);
    }

    /**
     * Outputs the current result.
     *
     * @return void.
     */
    public function output()
    {
        // Output headers
        foreach ($this->headers as $header)
        {
            header("{$header}");
        }

        // Output data
        header('Content-Type: application/json');
        $data = ['error' => (int)$this->error] + $this->result;
        echo json_encode($data);
        exit;
    }

    /**
     * Converts torrent objects to arrays.
     *
     * @param array $torrents The torrent objects.
     *
     * @return array An array with torrents converted to arrays.
     */
    private function convertTorrentsToArrays($torrents)
    {
        // Get the mapping for each object type
        $fileMapping = Transmission\Model\File::getMapping();
        $peerMapping = Transmission\Model\Peer::getMapping();
        $torrentMapping = Transmission\Model\Torrent::getMapping();
        $trackerMapping = Transmission\Model\Tracker::getMapping();
        $trackerStatsMapping = Transmission\Model\TrackerStats::getMapping();

        // loop through the torrents
        $data = [];
        foreach ($torrents as $torrent) {
            // Get the torrent data
            $torrentData = $this->convertObjectToArray($torrent, $torrentMapping);

            // Convert files
            foreach ($torrentData['files'] as $key => $file) {
                $torrentData['files'][$key] = $this->convertObjectToArray($file, $fileMapping);
            }

            // Convert peers
            foreach ($torrentData['peers']  as $key => $peer) {
                $torrentData['peers'][$key] = $this->convertObjectToArray($peer, $peerMapping);
            }

            // Convert trackers
            foreach ($torrentData['trackers'] as $key => $tracker) {
                $torrentData['trackers'][$key] = $this->convertObjectToArray($tracker, $trackerMapping);
            }

            // Convert tracker stats
            foreach ($torrentData['trackerStats'] as $key => $trackerStats) {
                $torrentData['trackerStats'][$key] = $this->convertObjectToArray($trackerStats, $trackerStatsMapping);
            }

            // Add the torrent data to the collection
            $data[] = $torrentData;
        }

        // Return the converted torrents
        return $data;
    }

    /**
     * Converts object data to array.
     *
     * @param object $object The object to convert.
     * @param array $mapping The object's mapping array.
     *
     * @return array The data array.
     */
    private function convertObjectToArray($object, $mapping)
    {
        // Check arguments
        if (!is_object($object) || !is_array($mapping) || empty($mapping)) {
            throw new \InvalidArgumentException("Invalid arguments for convertObjectToArray.");
        }

        // Loop through the object's mapping
        $data = [];
        foreach ($mapping as $dest => $source) {
            // Check if the object has a getProperty method
            $method = 'get' . ucfirst($source);
            if (method_exists($object, $method)) {
                $data[$source] = $object->{$method}();
                continue;
            }

            // Check if the object has an isProperty method
            $method = 'is' . ucfirst($source);
            if (method_exists($object, $method)) {
                $data[$source] = $object->{$method}();
            }
        }

        // Return the data array
        return $data;
    }
}
