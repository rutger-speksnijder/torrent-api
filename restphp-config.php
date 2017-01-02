<?php
/**
 * Default configuration file for the RestPHP class.
 * Make sure to define your data source when using OAuth2.
 *
 * @author Rutger Speksnijder
 * @since RestPHP 1.0.0
 * @license https://github.com/rutger-speksnijder/restphp/blob/master/LICENSE MIT
 */
return [
    /**
     * A value indicating whether to use authorization for this api.
     * @var boolean
     */
    'useAuthorization' => false,

    /**
     * A value indicating what mode of authorization to use.
     * 1: This mode will allow clients to request an access token using the "/token" endpoint.
     * 2: This mode will allow clients to first request authorization using the "/authorize" endpoint.
     *    This will generate an authorization code which can then be used to generate an access token.
     * 3: Both modes can be used.
     * @var int
     */
    'authorizationMode' => 2,

    /**
     * Whether to redirect authorization requests or to just show the authorization code.
     * @var boolean
     */
    'redirectAuthorization' => false,

    /**
     * The file to use for the authorization form.
     * This file will be loaded when the client
     * navigates to the "/authorize" endpoint. You can overwrite this setting
     * and show another form, as long as you keep the authorized input with values "yes/no".
     * @var string
     */
    'authorizationForm' => dirname(__FILE__) . '/form.php',

    /**
     * The data source name to use when storing OAuth2 related data.
     * @var string
     */
    'dsn' => 'mysql:dbname=YOUR_DATABASE_NAME;host=localhost',

    /**
     * The database username.
     * @var string
     */
    'username' => '',

    /**
     * The database password.
     * @var string
     */
    'password' => '',

    /**
     * The type of response data.
     * Valid types are can be defined in the Response factory.
     * If the client can set the the response type, the value of this
     * setting will be used as a fallback.
     * @var string
     */
    'responseType' => 'application/json',

    /**
     * A value indicating whether to allow the client to set the data response type.
     * If this is enabled the type of response data will be determined by the "Accept" header.
     * Supported types can be defined in the Response factory.
     * If the client provides an unsupported type, the "responseType" setting's value
     * will be used as a fallback.
     * @var boolean
     */
    'clientResponseType' => true,
];
