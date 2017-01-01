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
        $router->post('/add', [$this, 'add']);
        $router->get('/get/([0-9]+)', [$this, 'getById']);
        $router->get('/get/([a-zA-Z0-9]+)', [$this, 'getByHash']);
        $router->delete('/delete/([0-9]+)', [$this, 'deleteById']);
        $router->delete('/delete/([a-z-A-Z0-9]+)', [$this, 'deleteByHash']);

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
        $this->result = ['message' => 'Not found.'];
        return $this->output();
    }

    /**
     * Handles unauthorized queries.
     *
     * @return $this The current object.
     */
    public function unauthorized()
    {
        // Output the unauthorized error
        $this->headers[] = 'HTTP/1.1 403 Forbidden';
        $this->error = true;
        $this->result = ['message' => 'Unauthorized request.'];
        return $this->output();
    }

    /**
     * Outputs all torrents.
     *
     * @return $this The current object.
     */
    public function all()
    {
        // Get all torrents and convert them to arrays
        $torrents = $this->convertTorrentsToArrays($this->transmission->all());

        // Output the data
        $this->result = ['torrents' => $torrents];
        return $this->output();
    }

    /**
     * Outputs the current result.
     *
     * @return $this The current object.
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

        // Return the object
        return $this;
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
