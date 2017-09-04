<?php

namespace MovingImage\Config;

/**
 * Class Config
 * @package MovingImage\Config
 */
class Config
{
    /**
     * @return array
     */
    public static function get(): array
    {
        // Todo: load from configuration file
        return [
            'username' => 'arge1234@superrito.com',
            'password' => 'GaSq7=t!',
            'thumbnails_url' => 'http://localhost:8000/thumbnails',
            'base_uri' => 'https://api-qa1.video-cdn.net/v1/vms',
            'downloads_path' => __DIR__ . '/../../../downloads',
            'thumbnails_path' => __DIR__ . '/../../../thumbnails',
        ];
    }
}