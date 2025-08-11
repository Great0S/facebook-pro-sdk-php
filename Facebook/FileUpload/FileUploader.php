<?php

namespace Facebook\FileUpload;

use Facebook\Logging\FacebookLogger;

class FileUploader
{
    private $uploader;
    private $logger;
    private $progressCallback;
    private $validationRules;
    private $chunkSize = 1024 * 1024; // 1MB default

    public function __construct(FacebookResumableUploader $uploader, FacebookLogger $logger = null)
    {
        $this->uploader = $uploader;
        $this->logger = $logger ?: new FacebookLogger();
        $this->validationRules = $this->getDefaultValidationRules();
    }

    /**
     * Set progress callback
     */
    public function setProgressCallback(callable $callback)
    {
        $this->progressCallback = $callback;
        return $this;
    }

    /**
     * Set validation rules
     */
    public function setValidationRules(array $rules)
    {
        $this->validationRules = array_merge($this->validationRules, $rules);
        return $this;
    }

    /**
     * Set chunk size
     */
    public function setChunkSize($size)
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Upload with advanced features
     */
    public function upload($endpoint, $source, array $metadata = [], $maxTransferTries = 5)
    {
        // Validate file
        $this->validateFile($source);

        // Optimize upload parameters
        $this->optimizeUpload($source);

        $file = new FacebookFile($source);

        $this->logger->info('Starting advanced file upload', [
            'file_size' => $file->getSize(),
            'file_type' => mime_content_type($source),
            'endpoint' => $endpoint
        ]);

        try {
            return $this->uploadWithProgress($endpoint, $file, $metadata, $maxTransferTries);
        } catch (\Exception $e) {
            $this->logger->error('File upload failed', [
                'exception' => get_class($e),
                'message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function validateFile($source)
    {
        if (!file_exists($source)) {
            throw new \InvalidArgumentException("File does not exist: {$source}");
        }

        $fileSize = filesize($source);
        $mimeType = mime_content_type($source);

        // Check file size
        if (isset($this->validationRules['max_size']) && $fileSize > $this->validationRules['max_size']) {
            throw new \InvalidArgumentException("File too large. Max size: " . $this->validationRules['max_size']);
        }

        // Check mime type
        if (
            isset($this->validationRules['allowed_types']) &&
            !in_array($mimeType, $this->validationRules['allowed_types'])
        ) {
            throw new \InvalidArgumentException("File type not allowed: {$mimeType}");
        }

        $this->logger->debug('File validation passed', [
            'file_size' => $fileSize,
            'mime_type' => $mimeType
        ]);
    }

    private function optimizeUpload($source)
    {
        $fileSize = filesize($source);

        // Optimize chunk size based on file size
        if ($fileSize < 1024 * 1024) { // < 1MB
            $this->chunkSize = 256 * 1024; // 256KB chunks
        } elseif ($fileSize < 100 * 1024 * 1024) { // < 100MB
            $this->chunkSize = 1024 * 1024; // 1MB chunks
        } else {
            $this->chunkSize = 4 * 1024 * 1024; // 4MB chunks
        }

        $this->logger->debug('Upload optimized', [
            'file_size' => $fileSize,
            'chunk_size' => $this->chunkSize
        ]);
    }

    private function uploadWithProgress($endpoint, FacebookFile $file, array $metadata, $maxTransferTries)
    {
        $fileSize = $file->getSize();
        $uploaded = 0;

        // Start upload session
        $chunk = $this->uploader->start($endpoint, $file);

        // Upload chunks
        while ($uploaded < $fileSize) {
            $startOffset = $chunk->getStartOffset();
            $endOffset = min($startOffset + $this->chunkSize, $fileSize);

            // Create chunk file
            $chunkFile = $this->createChunkFile($file, $startOffset, $endOffset - $startOffset);
            $chunk = new FacebookTransferChunk(
                $chunkFile,
                $chunk->getUploadSessionId(),
                $chunk->getVideoId(),
                $startOffset,
                $endOffset
            );

            try {
                $chunk = $this->uploader->transfer($endpoint, $chunk, false);
                $uploaded = $chunk->getEndOffset();

                // Call progress callback
                if ($this->progressCallback) {
                    $progress = ($uploaded / $fileSize) * 100;
                    call_user_func($this->progressCallback, $progress, $uploaded, $fileSize);
                }

                $this->logger->debug('Chunk uploaded', [
                    'uploaded' => $uploaded,
                    'total' => $fileSize,
                    'progress' => round(($uploaded / $fileSize) * 100, 2) . '%'
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Chunk upload failed', [
                    'start_offset' => $startOffset,
                    'end_offset' => $endOffset,
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ]);

                if ($maxTransferTries-- <= 0) {
                    throw $e;
                }

                // Retry with exponential backoff
                sleep(pow(2, 5 - $maxTransferTries));
            }
        }

        // Finish upload
        return $this->uploader->finish($endpoint, $chunk->getUploadSessionId(), $metadata);
    }

    private function createChunkFile(FacebookFile $file, $start, $size)
    {
        $handle = fopen($file->getFilePath(), 'rb');
        fseek($handle, $start);
        $chunkData = fread($handle, $size);
        fclose($handle);

        $tempFile = tempnam(sys_get_temp_dir(), 'fb_chunk_');
        file_put_contents($tempFile, $chunkData);

        return new FacebookFile($tempFile);
    }

    private function getDefaultValidationRules()
    {
        return [
            'max_size' => 100 * 1024 * 1024, // 100MB
            'allowed_types' => [
                'video/mp4',
                'video/quicktime',
                'video/avi',
                'image/jpeg',
                'image/png',
                'image/gif'
            ]
        ];
    }
}
