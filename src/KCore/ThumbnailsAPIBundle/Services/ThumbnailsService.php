<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ThumbnailsService
{
    private $thumbnailMaxParallelProcesses;
    private $expireTime;

    private $appRoot;
    private $thumbnailsRoot;
    private $thumbnailsQueuePath;
    private $thumbnailsLocksPath;
    private $thumbnailsImagesPath;

    /**
     * @var ThumbnailGeneratorInterface[]
     */
    private $generators;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $fs;

    public function __construct($rootDir, $maxParallelProcesses, $expireTime, $generators, $logger, $fs = null)
    {
        $this->fs = $fs ?: new Filesystem();

        $this->thumbnailMaxParallelProcesses = $maxParallelProcesses;
        $this->expireTime = $expireTime;

        $this->appRoot = $rootDir;
        $this->thumbnailsRoot = $rootDir.'/../data/thumbnails/';
        $this->thumbnailsQueuePath = $this->thumbnailsRoot.'queue/';
        $this->thumbnailsLocksPath = $this->thumbnailsRoot.'locks/';
        $this->thumbnailsImagesPath = $this->thumbnailsRoot.'images/';

        $this->generators = $generators;
        $this->logger = $logger;

        $this->createPathsIfNeeded();
    }

    public function getThumbnailsQueuePath()
    {
        return $this->thumbnailsQueuePath;
    }

    public function getThumbnailsLocksPath()
    {
        return $this->thumbnailsLocksPath;
    }

    public function getThumbnailsImagesPath()
    {
        return $this->thumbnailsImagesPath;
    }

    public function getExpireTime()
    {
        return $this->expireTime;
    }

    /**
     * Create the required data/thumbnails sub folders for the Thumbnail generator.
     */
    private function createPathsIfNeeded()
    {
        try {
            if (!$this->fs->exists($this->thumbnailsQueuePath)) {
                $this->fs->mkdir($this->thumbnailsQueuePath);
            }
            if (!$this->fs->exists($this->thumbnailsLocksPath)) {
                $this->fs->mkdir($this->thumbnailsLocksPath);
            }
            if (!$this->fs->exists($this->thumbnailsImagesPath)) {
                $this->fs->mkdir($this->thumbnailsImagesPath);
            }
        } catch (IOExceptionInterface $e) {
            $this->logger->error('Thumbnails: error while creating required folders, "{message}"', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * Completely clean the data/thumbnails sub folders.
     */
    public function clearThumbnailsCache()
    {
        $this->fs->remove([
            $this->getThumbnailsImagesPath(),
            $this->getThumbnailsLocksPath(),
            $this->getThumbnailsQueuePath(),
        ]);
        $this->logger->info('Cleared Thumbnail folders');
        $this->createPathsIfNeeded();
    }

    public function deleteExpiredFiles()
    {
        $this->deleteExpiredFilesFromFolder($this->thumbnailsQueuePath);
        $this->deleteExpiredFilesFromFolder($this->thumbnailsLocksPath);
        $this->deleteExpiredFilesFromFolder($this->thumbnailsImagesPath);
    }

    /**
     * @param $path
     */
    private function deleteExpiredFilesFromFolder($path)
    {
        $finder = new Finder();
        $finder->files()->in($path);

        /** @var File $file */
        foreach ($finder as $file) {
            if ($this->fileIsExpired($file->getFilename())) {
                $this->fs->remove($file);
            }
        }

        $this->logger->info('Thumbnails: removed "{count}" expired files from "{folder}" folder', [
            'count' => count($finder),
            'folder' => basename($path),
        ]);
    }

    /**
     * @param $fileName
     *
     * @return bool
     */
    private function fileIsExpired($fileName)
    {
        $firstUnderscoreIndex = strpos($fileName, '_');
        $timestamp = substr($fileName, 0, $firstUnderscoreIndex);
        $expireTime = intval($timestamp) + ($this->expireTime * 60);
        $now = new \DateTime();

        return $now->getTimestamp() > $expireTime;
    }

    public function deleteThumbnail($path)
    {
        $this->fs->remove($path);
    }

    /**
     * @param $documentID
     * @param $institutionID
     *
     * @return bool
     */
    public function thumbnailExists($documentID, $institutionID)
    {
        $finder = new Finder();
        $finder->name('*'.$documentID.'_'.$institutionID.'_thumbnail.png');
        $finder->files()->in($this->thumbnailsImagesPath);

        return $finder->count() > 0;
    }

    /**
     * @param $localDocumentId
     * @param $institutionId
     *
     * @return bool
     */
    public function thumbnailIsProcessing($localDocumentId, $institutionId)
    {
        $genericQueueName = $this->getQueueFileNameByIDs('*', $localDocumentId, $institutionId, '*');
        $finder = new Finder();
        $finder->name($genericQueueName);
        $finder->files()->in($this->thumbnailsQueuePath);

        $genericLockName = $this->getLockFileName(new SplFileInfo($genericQueueName, '.', '.'));
        $lockFinder = new Finder();
        $lockFinder->name($genericLockName);
        $lockFinder->files()->in($this->thumbnailsLocksPath);

        return ($finder->count() > 0) || ($lockFinder->count() > 0);
    }

    /**
     * @param $documentId
     * @param $institutionId
     *
     * @return mixed
     */
    public function getThumbnailFilenameByIDs($documentId, $institutionId)
    {
        $finder = new Finder();
        $finder->name('*'.$documentId.'_'.$institutionId.'_thumbnail.png');
        $finder->files()->in($this->thumbnailsImagesPath);
        if ($finder->count() == 0) {
            throw new NotFoundHttpException(sprintf(
                'The thumbnail for resource with InstitutionID: "%s" and DocumentID: "%s" was not found',
                $institutionId,
                $documentId
            ));
        }
        $array = iterator_to_array($finder, false);

        return reset($array);
    }

    /**
     * @param $prefix
     * @param $documentID
     * @param $institutionID
     * @param $extension
     *
     * @return string
     */
    private function getQueueFileNameByIDs($prefix, $documentID, $institutionID, $extension)
    {
        //TODO: manage IDs with strange characters?
        return $prefix.'_'.$documentID.'_'.$institutionID.'.'.$extension;
    }

    /**
     * @param File $queueOrLockFile
     *
     * @return string
     */
    private function getImageFileName(\SplFileInfo $queueOrLockFile)
    {
        return $queueOrLockFile->getBasename('.'.$queueOrLockFile->getExtension()).'_thumbnail.png';
    }

    /**
     * @param File $queueOrLockFile
     *
     * @return string
     */
    public function getPartialImageFileName(\SplFileInfo $queueOrLockFile)
    {
        return $queueOrLockFile->getBasename($queueOrLockFile->getExtension()).'temp.png';
    }

    /**
     * @param File $queueFile
     *
     * @return string
     */
    private function getLockFileName(\SplFileInfo $queueFile)
    {
        return $queueFile->getBasename($queueFile->getExtension()).'lock';
    }

    /**
     * @param $documentID
     * @param $institutionID
     * @param $filePath
     * @param null $fileMime
     */
    public function reserveThumbnailCreation($documentID, $institutionID, $filePath, $fileMime = null)
    {
        $file = new SplFileInfo($filePath, '/', $filePath);
        $extension = (($fileMime) ? $this->fileExtensionByMime($fileMime) : $file->getExtension());
        $now = new \DateTime();

        $newFileName = $this->getQueueFileNameByIDs(
            $now->getTimestamp(),
            $documentID,
            $institutionID,
            $extension
        );

        $newFileNameInQueuePath = $this->thumbnailsQueuePath.$newFileName;

        $this->fs->copy($filePath, $newFileNameInQueuePath);
    }

    public function generateNextThumbnailInQueue()
    {
        $finder = new Finder();
        $finder->sortByName();
        $finder->files()->in($this->thumbnailsQueuePath);

        $this->logger->info('Thumbnails: Starting  generating thumbnails from queue');
        foreach ($finder as $file) {
            $f = new File($file);
            $relativeLockFile = $this->thumbnailsLocksPath.$this->getLockFileName($f->getFileInfo());
            $relativeImageFile = $this->thumbnailsImagesPath.$this->getImageFileName($f->getFileInfo());
            if (!$this->fs->exists($relativeLockFile) && !$this->fs->exists($relativeImageFile)) {
                $this->generateThumbnail($f);
                break;
            }
        }
        $this->logger->info('Thumbnails: Finished generating thumbnails from queue');
    }

    /**
     * @param ThumbnailGeneratorRequest $thumbnailGeneratorRequest
     *
     * @return string
     */
    public function generateThumbnailFromGeneratorRequest(ThumbnailGeneratorRequest $thumbnailGeneratorRequest)
    {
        $base64Content = $thumbnailGeneratorRequest->getFileData();
        $fileName = $thumbnailGeneratorRequest->getFileName();
        $fileMime = $thumbnailGeneratorRequest->getFileMime();

        $newFileName = $this->generateRandomFilename($fileName, $fileMime);
        $newFileNameInQueuePath = $this->thumbnailsQueuePath.$newFileName;

        $this->fs->dumpFile($newFileNameInQueuePath, base64_decode($base64Content));

        $imageFileName = new File($newFileNameInQueuePath);
        $thumbnailFilename = $this->generateThumbnail($imageFileName, false);

        return $thumbnailFilename;
    }

    /**
     * @param string $fileName
     * @param string $fileMime
     *
     * @return string
     */
    protected function generateRandomFilename($fileName, $fileMime)
    {
        $now = (new \DateTime())->getTimestamp();
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $extension = ($fileMime) ? $this->fileExtensionByMime($fileMime) : $fileExt;

        return $now.'_'.rand(100000, 999999).'.'.$extension;
    }

    /**
     * Process the given file and returns the generated Thumbnail's filename.
     *
     * @param File $documentFile
     * @param bool $considerMaxLocks
     *
     * @throws \Exception
     *
     * @return null|string
     */
    private function generateThumbnail(File $documentFile, $considerMaxLocks = true)
    {
        $finder = new Finder();
        $finder->name('*.lock');
        $finder->files()->in($this->thumbnailsLocksPath);

        if ($considerMaxLocks && (iterator_count($finder) >= $this->thumbnailMaxParallelProcesses)) {
            $this->logger->alert('Thumbnails: Skipping thumbnail generation, as max-parallel "{count}" has been reached.', [
                'count' => iterator_count($finder),
            ]);

            return null;
        }

        $generator = $this->getGeneratorForExtension($documentFile->getExtension());
        if (!$generator) {
            $this->logger->error('Thumbnails: no Thumbnail generator found for "{ext}" extension', [
                'ext' => $documentFile->getExtension(),
            ]);
            throw new BadRequestHttpException('The extension "'.$documentFile->getExtension().'" is not supported');
        }

        $lockFile = $this->thumbnailsLocksPath.$this->getLockFileName($documentFile->getFileInfo());
        $this->fs->touch($lockFile);

        $tempThumbnailFile = $this->thumbnailsImagesPath.$this->getPartialImageFileName($documentFile->getFileInfo());
        $this->logger->info('Thumbnails: starting thumbnail generation for "{file}"', [
            'file' => basename($tempThumbnailFile),
        ]);

        $thrownException = false;
        try {
            $generator->generateThumbnail($documentFile, $tempThumbnailFile);
        } catch (\Exception $e) {
            $this->logger->error('Thumbnails: Exception while creating thumbnail for "{file}"', [
                'file' => basename($tempThumbnailFile),
                'exception' => $e,
            ]);
            // Save the exception here, to be able to cleanup our files
            $thrownException = $e;
        }

        // Files cleanup
        $this->fs->remove($lockFile);
        $this->fs->remove($documentFile);

        if ($thrownException) {
            throw $thrownException;
        }

        // Move the temporary thumbnail to the final thumbnail
        $thumbnailFilename = $this->thumbnailsImagesPath.$this->getImageFileName($documentFile);
        $this->fs->rename($tempThumbnailFile, $thumbnailFilename);

        $this->logger->info('Thumbnails: Thumbnail generated on {file}.', [
            'file' => basename($thumbnailFilename),
        ]);

        return $thumbnailFilename;
    }

    /**
     * Returns a ThumbnailGenerator for the given extension, if any.
     *
     * @param string $extension The file extension
     *
     * @return ThumbnailGeneratorInterface|null
     */
    private function getGeneratorForExtension($extension)
    {
        if (!array_key_exists($extension, $this->generators)) {
            return null;
        }

        return $this->generators[$extension];
    }

    /**
     * @param $mime
     *
     * @return string
     */
    private function fileExtensionByMime($mime)
    {
        switch ($mime) {
            case 'text/uri-list':
                return 'uri';
            case 'application/pdf':
                return 'pdf';
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                return 'docx';
            case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
                return 'xlsx';
            case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
                return 'pptx';
        }

        $this->logger->error('Thumbnails: mime type not supported "{mime}"', [
            'mime' => $mime,
        ]);

        throw new BadRequestHttpException('The mime type '.$mime.' is not supported');
    }
}
