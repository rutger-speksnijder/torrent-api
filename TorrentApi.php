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
        var_dump($this->request);
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
        parse_str(file_get_contents('php://input'), $data);
        if (empty($data) && !empty($_POST)) {
            return $_POST;
        } elseif (empty($data) && empty($_POST)) {
            return $_GET;
        }
        return $data;
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
        $torrents = $this->transmission->all();
        echo '<pre>';
        var_dump($torrents);
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
}
