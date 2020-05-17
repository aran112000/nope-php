<?php

namespace App\Traits;

/**
 * Trait AssetDetection
 *
 * @package App\Traits
 */
trait StaticFileDetection
{

    /**
     * @var string[][]
     */
    private $staticFileTypes = [
        'Image' => [
            'image/png',
            'image/jpeg',
            'image/gif',
            'image/webp',
            'image/svg',
            'image/x-icon',
        ],
        'JavaScript' => [
            'application/javascript',
        ],
        'CSS' => [
            'text/css',
        ],
        'Font' => [
            'font/woff2',
            'font/woff',
            'font/ttf',
            'font/otf',
            'font/opentype',
        ],
        'General' => [
            'application/octet-stream',
        ],
        'PDF' => [
            'application/pdf',
        ],
    ];

    /**
     * @return string|false
     */
    protected function isStaticFile()
    {
        if (!$mimeType = $this->logLine->getMimeType()) {
            // Unknown mime type

            return false;
        }

        foreach ($this->staticFileTypes as $fileType => $mimeTypes) {
            if (in_array($mimeType, $mimeTypes)) {
                return $fileType;
            }
        }

        return false;
    }
}
