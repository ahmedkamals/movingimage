<?php

namespace MovingImage\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception;
use MovingImage\Exception\AuthException;

/**
 * Class ImageGenerator
 * @package MovingImage\Service
 */
class ImageGenerator
{
    /**
     * @var array
     */
    private $config;

    /**
     * ImageGenerator constructor.
     *
     * @param array  $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $videoId
     * @param string $offset
     * @param int    $width
     * @param int    $height
     *
     * @return string
     */
    public function getImage(
        string $videoId,
        string $offset,
        ?int $width,
        ?int $height
    ): string
    {
        $thumbnailData = [];

        $videoPath = $this->downloadVideo($videoId);

        if (!empty($videoPath) && file_exists($videoPath)) {
            $thumbnailData = $this->generateThumbnail($videoId, $videoPath, $offset);

            if (!is_null($thumbnailData)
                && file_exists($thumbnailData['path'])
                && !is_null($width)
                && !is_null($height)) {
                $thumbnailData = $this->resizeThumbnail(
                    $videoId,
                    $offset,
                    $thumbnailData['path'],
                    $width,
                    $height
                );
            }
        }

        unset($thumbnailData['path']);

        return json_encode($thumbnailData);
    }

    /**
     * Downloads a video and returning its physical path.
     *
     * @param string $videoId
     *
     * @return string
     */
    private function downloadVideo(string $videoId): string
    {
        $accessData = $this->authorize();
        $videoAPIUri = sprintf(
            '%s/%s/videos/%s/download-urls',
            $this->config['base_uri'],
            $accessData['videoManager'],
            $videoId
        );
        $request = new Psr7\Request('GET', $videoAPIUri, [
            'Authorization' => sprintf('Bearer %s', $accessData['accessToken']),
        ]);
        $videoPath = '';

        try {
            $client = new Client();
            $response = $client->send($request);
            $responseContent = json_decode($response->getBody()->getContents(), true);

            if (!empty($responseContent)) {
                $targetVideo = $responseContent[2];
                $videoExtension = $targetVideo['fileExtension'];
                $videoPath = sprintf('%s/%s.%s',
                    $this->config['downloads_path'],
                    $videoId,
                    $videoExtension
                );

                if (!file_exists($videoPath)) {
                    $videoURL = $targetVideo['url'];
                    $this->saveFile($videoURL, $videoPath);
                }
            }
        } catch (Exception\ClientException $e) {

        }

        return $videoPath;
    }

    /**
     * @param string $videoUri
     * @param string $path
     */
    private function saveFile(string $videoUri, string $path)
    {
        try{
            $client = new Client();
            $client->get(
                $videoUri,
                [
                    'save_to' => $path,
                ]);
        } catch (Exception\ClientException $e) {

        }
    }

    /**
     * @return array
     * @throws AuthException
     */
    private function authorize(): array
    {
        $authUri = sprintf(
            '%s/%s',
            $this->config['base_uri'],
            '/auth/login'
        );
        try {
            $client = new Client();
            $response = $client->post($authUri, [
                    'json' => [
                        'username' => $this->config['username'],
                        'password' => $this->config['password'],
                    ]
            ]);
            $responseContent = json_decode($response->getBody()->getContents(), true);
            $accessData = [];
            if (!empty($responseContent)) {
                $accessData['accessToken'] = $responseContent['accessToken'];
                $accessData['refreshToken'] = $responseContent['refreshToken'];
                $accessData['videoManager'] = $responseContent['validForVideoManager'];
            }
        } catch (Exception\ClientException $e) {
            throw new AuthException($e->getMessage());
        }

        return $accessData;
    }

    /**
     * @param string   $videoId
     * @param string   $videoPath
     * @param string   $offset
     *
     * @return array|null
     */
    private function generateThumbnail(
        string $videoId,
        string $videoPath,
        string $offset
    ): ?array
    {
        $flattenedOffset = $this->flattenOffset($offset);

        // Todo: crate directory for every video.
        // Path should be "thumbnails/VIDEO_ID_original_OFFSET.jpg"
        $thumbnailPath = sprintf(
            '%s/%s_original_%s.jpg',
            $this->config['thumbnails_path'],
            $videoId,
            $flattenedOffset
        );
        $pathData = null;

        if (!file_exists($thumbnailPath) && file_exists($videoPath)) {
            `ffmpeg -ss {$offset} -i {$videoPath} -vframes 1 -f image2 {$thumbnailPath}`;
        }

        $pathData = [
            'path' => $thumbnailPath,
            'url' => sprintf(
                '%s/%s_original_%s.jpg',
                $this->config['thumbnails_url'],
                $videoId,
                $flattenedOffset
            )
        ];

        return $pathData;
    }

    /**
     * @param string    $videoId
     * @param string    $offset flattened one "00_00_05"
     * @param string    $thumbnailPath
     * @param int       $width
     * @param int       $height
     *
     * @return array
     */
    private function resizeThumbnail(
        string $videoId,
        string $offset,
        string $thumbnailPath,
        int $width,
        int $height
    ): array
    {
        $resizedThumbnailPath = sprintf(
            '%s/%s_resized_%s_%sX%s.jpg',
            $videoId,
            $this->config['thumbnails_path'],
            $videoId,
            $offset,
            $width,
            $height
        );

        if (!file_exists($resizedThumbnailPath)) {
            `ffmpeg -i ${thumbnailPath} -vf scale={$width}:{$height} {$resizedThumbnailPath}`;
        }

        return [
            'path' => $resizedThumbnailPath,
            'url' => sprintf(
                '%s/%s_resized_%s_%sX%s.jpg',
                $this->config['thumbnails_url'],
                $videoId,
                $this->flattenOffset($offset),
                $width,
                $height
            ),
        ];
    }

    /**
     * @param string $offset
     *
     * @return string
     */
    private function flattenOffset(string $offset): string
    {
        return str_replace(':', '_', $offset);
    }
}