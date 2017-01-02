<?php
// Composer autoloader
require 'vendor/autoload.php';
require 'TransmissionApi.php';
require 'TransmissionUtil.php';

// Check if we have a request location
if (!isset($_REQUEST['l'])) {
    $_REQUEST['l'] = '';
}

// Load the api configuration
$configuration = (new RestPHP\Configuration)->createFromFile('restphp-config.php');

// Create the api
$api = new \TransmissionApi\TransmissionApi($_REQUEST['l'], $configuration);

// Check if no errors occurred during creation
// If errors did occur they must be fixed before the API will work.
// The errors are most likely OAuth2 related.
if (!$api->hasError()) {
    // Define routes
    $api->setRoutes();

    // Initialize connection to transmission
    $api->initialize();

    // Call the process method
    $api->process();
}
