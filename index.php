<?php
/**
 * Main script that should be loaded for each request.
 * The .htaccess file points to this script.
 *
 * @author Rutger Speksnijder.
 * @since transmission-api 1.0.
 * @license MIT.
 */

// Composer autoloader
require 'vendor/autoload.php';

// Load the api configuration
$configuration = (new \RestPHP\Configuration)->createFromFile('restphp-config.php');

// Create the api
$api = new \TransmissionApi\TransmissionApi($_SERVER['REQUEST_URI'], $configuration);

// Check if no errors occurred during creation
// If errors did occur they must be fixed before the API will work.
// The errors are most likely OAuth2 related.
if (!$api->hasError()) {
    // Define routes
    $api->setRoutes();

    // Initialize connection to transmission
    $api->initialize('transmission-config.php');

    // Call the process method
    $api->process();
}
