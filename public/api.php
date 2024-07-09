<?php
/**
 * This file is a simple routing approach allowing to keep a monolithic app to make its intallation easier
 */

use EcospoldExplorer\Database;
use EcospoldExplorer\Extract;
use EcospoldExplorer\Response;
use EcospoldExplorer\Source;
use Symfony\Component\Dotenv\Dotenv;

// Composer autoload
require('../vendor/autoload.php');

// Check if a request has been recieved
if (!isset($_GET['request'])) {
    Response::send(400, "Bad request");
}

// Environment load
if (file_exists('../.env')) {
    $dotenv = new Dotenv();
    $dotenv->loadEnv('../.env');
}

// DB connection
if (isset($_ENV['DB_DSN'])) {
    try {
        Database::connect($_ENV['DB_DSN']);
    } catch(PDOException $e) {
        Response::send(500, "Impossible to connect to database. Please contact an administrator to fix this issue.");
    }
} else {
    Response::send(500, "Database DSN environment variable not declared. Please contact an administrator to fix this issue.");
}

// Simple routing based on switch
switch ($_GET['request']) {

    case "get-sources":
        Response::send(200, null, Source::getList());
        break;

    case "get-indicators":
        if (isset($_GET['source']) && preg_match('#^[0-9]+$#', ($_GET['source']))) {
            $source = new Source($_GET['source']);
            Response::send(200, null, $source->getIndicators());
        }
        break;

    case "get-elementary-flows":
        if (isset($_GET['source']) && preg_match('#^[0-9]+$#', ($_GET['source']))) {
            $source = new Source($_GET['source']);
            Response::send(200, null, $source->getElementaryFlows());
        }
        break;

    case "get-extract":
        set_time_limit(3600);

        $parameters = Extract::parseParameters($_GET);
        $extract = new Extract($parameters);

        Response::send(200, null, ["href" => $extract->extract()]);
        break;

    default:
        Response::send(400, "Bad request");

}

// Default response
Response::send(400, "Bad request");