<?php
/**
 * Configuration for the Transmission api.
 * These are the default values.
 *
 * @author Rutger Speksnijder.
 * @since transmission-api 1.0.
 * @license MIT.
 */
return [
    'host' => '127.0.0.1',
    'port' => 9091,
    'username' => '',
    'password' => '',
    'whitelist' => array(
        'localhost',
        '127.0.01',
        '::1',
    ),
];
