<?php

namespace MovingImage;

use MovingImage\Service;
use MovingImage\Exception;
use MovingImage\Config;

require __DIR__ . '/vendor/autoload.php';

try {

    if (preg_match('/\.(?:png|jpg|jpeg|gif)$/', $_SERVER["REQUEST_URI"])) {
        return false;    // serve the requested resource as-is.
    }

    $videoId = !empty($_GET['video_id'])? $_GET['video_id'] : null;
    $offset = !empty($_GET['offset'])? $_GET['offset'] : null;
    $width = !empty($_GET['width'])? $_GET['width'] : null;
    $height = !empty($_GET['height'])? $_GET['height'] : null;

    // Check if executed in command line
    if (php_sapi_name() == "cli") {

        if (count($argv) < 3) {
            throw new Exception\InvalidArgumentException('Wrong number of arguments');
        }

        $videoId = $argv[1];
        $offset = $argv[2];
        $width = $argv[3];
        $height = $argv[4];
    }

    if (empty($videoId) || empty($offset)) {
        throw new Exception\InvalidArgumentException('Invalid value for arguments.');
    }

    $config = Config\Config::get();

    // Todo: wrap in json response class.
    header('Content-Type: application/json');
    echo (new Service\ImageGenerator($config))
        ->getImage(
            $videoId,
            $offset,
            $width,
            $height
        );
} catch (Exception\InvalidArgumentException $e) {

    if (php_sapi_name() == "cli") {
        echo 'Error: ', $e->getMessage();
        return;
    }

    echo json_encode(['error' => $e->getMessage()]);
}