<?php
namespace TransmissionApi;

/**
 * Main class to handle API calls.
 *
 * @author Rutger Speksnijder.
 * @since transmission-api 1.0.
 */
class TransmissionApi extends \RestPHP\BaseAPI
{
    /**
     * The transmission object.
     * @var Transmission\Transmission.
     */
    private $transmission;

    /**
     * The transmission's client object.
     * @var Transmission\Client.
     */
    private $transmissionClient;

    /**
     * Sets the routes for the API.
     *
     * @return $this The current object.
     */
    public function setRoutes()
    {
        // Not found
        $this->router->add('/', [$this, 'notFound']);

        // Torrent routes
        $this->router->get('/torrent', [$this, 'all']);
        $this->router->get('/torrent/([0-9]+)', [$this, 'getById']);
        $this->router->get('/torrent/([a-zA-Z0-9]+)', [$this, 'getByHash']);
        $this->router->post('/torrent', [$this, 'add']);
        $this->router->delete('/torrent/([0-9]+)', [$this, 'deleteById']);
        $this->router->delete('/torrent/([a-zA-Z0-9]+)', [$this, 'deleteByHash']);
        $this->router->get('/torrent/([0-9]+)/start', [$this, 'startById']);
        $this->router->get('/torrent/([a-zA-Z0-9]+)/start', [$this, 'startByHash']);
        $this->router->get('/torrent/([0-9]+)/stop', [$this, 'stopById']);
        $this->router->get('/torrent/([a-zA-Z0-9]+)/stop', [$this, 'stopByHash']);
        $this->router->get('/torrent/([0-9]+)/verify', [$this, 'verifyById']);
        $this->router->get('/torrent/([a-zA-Z0-9]+)/verify', [$this, 'verifyByHash']);
        $this->router->get('/torrent/([0-9]+)/reannounce', [$this, 'reannounceById']);
        $this->router->get('/torrent/([a-zA-Z0-9]+)/reannounce', [$this, 'reannounceByHash']);

        // Return the object
        return $this;
    }

    /**
     * Initializes the connection to the Transmission server.
     *
     * @param string $configFile The location to the configuration file for Transmission.
     *
     * @return $this The current object.
     */
    public function initialize($configFile)
    {
        // Check request data for username and password
        if (empty($this->data['username']) || empty($this->data['password'])) {
            return $this->unauthorized();
        }

        // Load the Transmission configuration file
        $config = require $configFile;

        // Authenticate
        $this->transmissionClient = new \Transmission\Client($config['host'], $config['port']);
        $this->transmissionClient->authenticate($this->data['username'], $this->data['password']);

        // Create the transmission object
        $this->transmission = new \Transmission\Transmission();
        $this->transmission->setClient($this->transmissionClient);
    }

    /**
     * Handles not found queries.
     *
     * @return $this The current object.
     */
    public function notFound()
    {
        // Output the not found error
        $this->setStatusCode(404);
        if (empty($this->response['message'])) {
            $this->response = ['message' => 'Requested endpoint could not be found.'];
        }
        $this->output(true);
        exit;
    }

    /**
     * Handles unauthorized queries.
     *
     * @return $this The current object.
     */
    private function unauthorized()
    {
        // Output the unauthorized error
        $this->setStatusCode(401);
        if (empty($this->response['message'])) {
            $this->response = ['message' => 'Unauthorized request.'];
        }
        $this->output(true);
        exit;
    }

    /**
     * Handles bad requests.
     *
     * @return $this The current object.
     */
    private function badRequest()
    {
        // Output the bad request error
        $this->statusCode = 400;
        if (empty($this->response['message'])) {
            $this->response = ['message' => 'Bad request.'];
        }
        $this->output(true);
        exit;
    }

    /**
     * Handles server errors.
     *
     * @return $this The current object.
     */
    private function serverError()
    {
        // Output the internal server error
        $this->statusCode = 500;
        if (empty($this->response['message'])) {
            $this->response = ['message' => 'Internal Server Error.'];
        }
        $this->output(true);
        exit;
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
                $this->response = ['message' => 'Internal Server Error. Unable to connect to Transmission.'];
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
            $torrents = TransmissionUtil::convertTorrentsToArrays($this->transmission->all());
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }
        $this->response = ['torrents' => $torrents];
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
            $this->response = ['message' => 'Torrent not found.'];
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
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$identifier}/start");
        $this->addHypertextRoute('stop', "/torrent/{$identifier}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$identifier}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$identifier}/reannounce");
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
        if (empty($this->data['uri'])) {
            // Send a bad request error
            $this->response = ['message' => 'Invalid arguments: Missing uri parameter.'];
            return $this->badRequest();
        }

        // Add the torrent
        $torrent = false;
        try {
            $torrent = $this->transmission->add($this->data['uri']);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Check if the torrent was added
        if (!$torrent) {
            $this->response = ['message' => 'Torrent could not be added.'];
            return $this->serverError();
        }

        // Return the torrent
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$torrent['hash']}/start");
        $this->addHypertextRoute('stop', "/torrent/{$torrent['hash']}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$torrent['hash']}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$torrent['hash']}/reannounce");
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
        $deleteFiles = isset($this->data['files']) && $this->data['files'] == '1';

        // Delete the torrent
        try {
            $this->transmission->remove($torrent, $deleteFiles);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $this->response = ['message' => 'Torrent deleted.'];
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
        $startNow = isset($this->data['now']) && $this->data['now'] == '1';

        // Start the torrent
        try {
            $this->transmission->start($torrent, $startNow);
        } catch (\Exception $ex) {
            $this->handleErrors($ex);
        }

        // Output the result
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['message' => 'Torrent started.', 'torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$torrent['hash']}/start");
        $this->addHypertextRoute('stop', "/torrent/{$torrent['hash']}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$torrent['hash']}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$torrent['hash']}/reannounce");
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
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['message' => 'Torrent stopped.', 'torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$torrent['hash']}/start");
        $this->addHypertextRoute('stop', "/torrent/{$torrent['hash']}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$torrent['hash']}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$torrent['hash']}/reannounce");
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
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['message' => 'Torrent verification started.', 'torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$torrent['hash']}/start");
        $this->addHypertextRoute('stop', "/torrent/{$torrent['hash']}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$torrent['hash']}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$torrent['hash']}/reannounce");
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
        $torrent = TransmissionUtil::convertTorrentsToArrays([$torrent])[0];
        $this->response = ['message' => 'Torrent reannounce started.', 'torrent' => $torrent];
        $this->addHypertextRoute('start', "/torrent/{$torrent['hash']}/start");
        $this->addHypertextRoute('stop', "/torrent/{$torrent['hash']}/stop");
        $this->addHypertextRoute('verify', "/torrent/{$torrent['hash']}/verify");
        $this->addHypertextRoute('reannounce', "/torrent/{$torrent['hash']}/reannounce");
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
}
