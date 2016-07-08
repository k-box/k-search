<?php

namespace KCore\ThumbnailsAPIBundle\Services;

use KCore\ThumbnailsAPIBundle\Entity\ThumbnailGeneratorRequest;
use KCore\ThumbnailsAPIBundle\Library\ThumbnailFromOfficeDocumentX;
use KCore\ThumbnailsAPIBundle\Library\ThumbnailFromOpenDocument;
use KCore\ThumbnailsAPIBundle\Library\ThumbnailFromPDF;
use KCore\ThumbnailsAPIBundle\Library\ThumbnailFromWebpage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ThumbnailsService {

    private $thumbnailMaxParallelProcesses;
    private $expireTime;

    private $appRoot;
    private $thumbnailsRoot;
    private $thumbnailsQueuePath;
    private $thumbnailsLocksPath;
    private $thumbnailsImagesPath;
    private $pdfBoxJarPath;
    
    private $fs;
    
    public function __construct($rootDir, $maxParallelProcesses = 4, $expireTime = 5, $pdfBoxJarPath) {
        
        $this->fs = new Filesystem();
        
        $this->thumbnailMaxParallelProcesses = $maxParallelProcesses;
        $this->expireTime = $expireTime;
        
        $this->appRoot = $rootDir;
        $this->thumbnailsRoot = $rootDir . "/../data/thumbnails/" ;
        $this->thumbnailsQueuePath = $this->thumbnailsRoot . "queue/" ;
        $this->thumbnailsLocksPath = $this->thumbnailsRoot . "locks/" ;
        $this->thumbnailsImagesPath = $this->thumbnailsRoot . "images/" ;
        
        $this->pdfBoxJarPath = $pdfBoxJarPath;
                
        $this->createPathsIfNeeded();
    }
    
    public function getThumbnailsQueuePath () {
        return $this->thumbnailsQueuePath;
    }
    
    public function getThumbnailsLocksPath () {
        return $this->thumbnailsLocksPath;
    }
    
    public function getThumbnailsImagesPath () {
        return $this->thumbnailsImagesPath;
    }
    
    public function getExpireTime() {
        return $this->expireTime;
    }
    
    private function createPathsIfNeeded() {
        try {
            if (! $this->fs->exists($this->thumbnailsQueuePath)) {
                $this->fs->mkdir($this->thumbnailsQueuePath);
            }
            if (! $this->fs->exists($this->thumbnailsLocksPath)) {
                $this->fs->mkdir($this->thumbnailsLocksPath);
            }
            if (! $this->fs->exists($this->thumbnailsImagesPath)) {
                $this->fs->mkdir($this->thumbnailsImagesPath);
            }
        } catch (IOExceptionInterface $e) {
            //TODO: raise a detailed exception?
        }
    }

    /**
     * Completely clean the data/thumbnails subfolders
     */
    public function clearThumbnailsCache() {
        $this->fs->remove(array(
            $this->getThumbnailsImagesPath(),
            $this->getThumbnailsLocksPath(),
            $this->getThumbnailsQueuePath(),
        ));
        $this->createPathsIfNeeded();
    }

    /**
     *
     */
    public function deleteExpiredFiles() {
        $this->deleteExpiredFilesFromFolder($this->thumbnailsQueuePath);
        $this->deleteExpiredFilesFromFolder($this->thumbnailsLocksPath);
        $this->deleteExpiredFilesFromFolder($this->thumbnailsImagesPath);
    }

    /**
     * @param $path
     */
    private function deleteExpiredFilesFromFolder($path) {
        $finder = new Finder();
        $finder->files()->in($path);

        /** @var File $file */
        foreach ($finder as $file) {
            if ($this->fileIsExpired($file->getFilename())) {
                $this->fs->remove($file);
            }
        }
    }

    /**
     * @param $fileName
     * @return bool
     */
    private function fileIsExpired ($fileName) {
        $firstUnderscoreIndex = strpos($fileName, "_");
        $timestamp = substr($fileName, 0, $firstUnderscoreIndex);
        $expireTime = intval($timestamp) + ($this->expireTime * 60);
        $now = new \DateTime();
        return ($now->getTimestamp() > $expireTime);
    }
    
    public function deleteThumbnail($path) {
        $this->fs->remove($path);
    }

    /**
     * @param $documentID
     * @param $institutionID
     * @return bool
     */
    public function thumbnailExists($documentID, $institutionID) {
        $finder = new Finder();
        $finder->name("*" . $documentID . "_" . $institutionID . "_thumbnail.png");
        $finder->files()->in($this->thumbnailsImagesPath);
        return ($finder->count() > 0);
    }

    /**
     * @param $localDocumentId
     * @param $institutionId
     * @return bool
     */
    public function thumbnailIsProcessing($localDocumentId, $institutionId) {
        $genericQueueName = $this->getQueueFileNameByIDs("*", $localDocumentId, $institutionId, "*");
        $finder = new Finder();
        $finder->name($genericQueueName);
        $finder->files()->in($this->thumbnailsQueuePath);
        
        $genericLockName = $this->getLockFileName(new SplFileInfo($genericQueueName, ".", "."));
        $lockFinder = new Finder();
        $lockFinder->name($genericLockName);
        $lockFinder->files()->in($this->thumbnailsLocksPath);
        
        return (($finder->count() > 0) || ($lockFinder->count() > 0));
    }

    /**
     * @param $documentId
     * @param $institutionId
     * @return mixed
     */
    public function getThumbnailFilenameByIDs($documentId, $institutionId) {
        $finder = new Finder();
        $finder->name("*" . $documentId . "_" . $institutionId . "_thumbnail.png");
        $finder->files()->in($this->thumbnailsImagesPath);
        if ($finder->count() == 0) {
            throw new NotFoundHttpException(sprintf('The thumbnail for resource with InstitutionID: \'%s\' and DocumentID:\'%s\' was not found.', $institutionId, $documentId));
        }
        $array = iterator_to_array($finder, false);
        return reset($array);
    }

    /**
     * @param $prefix
     * @param $documentID
     * @param $institutionID
     * @param $extension
     * @return string
     */
    private function getQueueFileNameByIDs($prefix, $documentID, $institutionID, $extension) {
        //TODO: manage IDs with strange characters?
        return $prefix . "_" . $documentID . "_" . $institutionID . "." . $extension;
    }

    /**
     * @param File $queueOrLockFile
     * @return string
     */
    private function getImageFileName (\SplFileInfo $queueOrLockFile) {
        return $queueOrLockFile->getBasename("." . $queueOrLockFile->getExtension()) . "_thumbnail.png";
    }

    /**
     * @param File $queueOrLockFile
     * @return string
     */
    public function getPartialImageFileName (\SplFileInfo $queueOrLockFile) {
        return $queueOrLockFile->getBasename($queueOrLockFile->getExtension()) . "temp.png";
    }

    /**
     * @param File $queueFile
     * @return string
     */
    private function getLockFileName (\SplFileInfo $queueFile) {
        return $queueFile->getBasename($queueFile->getExtension()) . "lock";
    }

    /**
     * @param $documentID
     * @param $institutionID
     * @param $filePath
     * @param null $fileMime
     */
    public function reserveThumbnailCreation($documentID, $institutionID, $filePath, $fileMime = null) {
        $file = new SplFileInfo($filePath, "/", $filePath);
        $extension = (($fileMime) ? $this->fileExtensionByMime($fileMime) : $file->getExtension());
        $now = new \DateTime();

        $newFileName = $this->getQueueFileNameByIDs(
            $now->getTimestamp(),
            $documentID,
            $institutionID,
            $extension
        );

        $newFileNameInQueuePath = $this->thumbnailsQueuePath . $newFileName;
        
        $this->fs->copy($filePath, $newFileNameInQueuePath);
    }

    /**
     *
     */
    public function generateNextThumbnailInQueue() {

        $finder = new Finder();
        $finder->sortByName();
        $finder->files()->in($this->thumbnailsQueuePath);
        
        foreach ($finder as $file) {
            $f = new File($file);
            $relativeLockFile = $this->thumbnailsLocksPath . $this->getLockFileName($f->getFileInfo());
            $relativeImageFile = $this->thumbnailsImagesPath . $this->getImageFileName($f->getFileInfo());
            if (! $this->fs->exists($relativeLockFile) && ! $this->fs->exists($relativeImageFile)) {
                $this->tryToGenerateThumbnail($f);
                break;
            }
        }
    }

    /**
     * @param ThumbnailGeneratorRequest $thumbnailGeneratorRequest
     * @return string
     */
    public function generateThumbnailFromGeneratorRequest(ThumbnailGeneratorRequest $thumbnailGeneratorRequest) {
        
        $base64Content = $thumbnailGeneratorRequest->getFileData();
        $fileName = $thumbnailGeneratorRequest->getFileName();
        $fileMime = $thumbnailGeneratorRequest->getFileMime();

        $file = new SplFileInfo($fileName, getcwd(), $fileName);
        $now = new \DateTime();
        $newFileName =
            $now->getTimestamp()
            . "_" . rand(100000, 999999)
            . "." . (($fileMime)
                        ? $this->fileExtensionByMime($fileMime)
                        : $file->getExtension());

        $newFileNameInQueuePath = $this->thumbnailsQueuePath . $newFileName;
        try {
            $this->fs->dumpFile($newFileNameInQueuePath, base64_decode($base64Content));
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Wrong format for JSON request: filedata is not base64?');
        }

        $imageFileName = new File($newFileNameInQueuePath);
        $this->tryToGenerateThumbnail($imageFileName, false);

        return $this->thumbnailsImagesPath . $this->getImageFileName($imageFileName);
    }

    /**
     * @param File $queueFile
     * @param bool $considerMaxLocks
     */
    private function tryToGenerateThumbnail(File $queueFile, $considerMaxLocks = true) {
        $finder = new Finder();
        $finder->name("*.lock");
        $finder->files()->in($this->thumbnailsLocksPath);
        if (!$considerMaxLocks || (iterator_count($finder) < $this->thumbnailMaxParallelProcesses)) {
            
            $lockFile = $this->thumbnailsLocksPath . $this->getLockFileName($queueFile->getFileInfo());
            $this->fs->touch($lockFile);
            
            $partialImageFile = $this->thumbnailsImagesPath . $this->getPartialImageFileName($queueFile->getFileInfo());
            
            switch ($queueFile->getExtension()) {
                case "uri":
                    $extractor = new ThumbnailFromWebpage($this->appRoot . "/../bin");
                    $extractor->generateThumbnail($queueFile, $partialImageFile);
                    break;
                case "pdf":
                    $extractor = new ThumbnailFromPDF($this->pdfBoxJarPath, $this->getExpireTime() * 60);
                    $extractor->generateThumbnail($queueFile, $partialImageFile);
                    break;
                case "xlsx":
                case "pptx":
                case "docx";
                    $extractor = new ThumbnailFromOfficeDocumentX($this->getExpireTime() * 60);
                    $extractor->generateThumbnail($queueFile, $partialImageFile);
                    break;
                case "odt":
                case "ods":
                case "odp":
                    $extractor = new ThumbnailFromOpenDocument($this->getExpireTime() * 60);
                    $extractor->generateThumbnail($queueFile, $partialImageFile);
                    break;
                default:
                    throw new BadRequestHttpException("The extension " . $queueFile->getExtension() . " is not supported");
            }
            
            $imageFile = $this->thumbnailsImagesPath . $this->getImageFileName($queueFile);
            $this->fs->rename($partialImageFile, $imageFile);
            
            $this->fs->remove($lockFile);
            $this->fs->remove($queueFile);
        }
    }
        
    private function fileExtensionByMime($mime) {
        switch ($mime) {
            case "text/uri-list":
                return "uri";
            case "application/pdf": 
                return "pdf";
            case "application/vnd.openxmlformats-officedocument.wordprocessingml.document":
                return "docx";
            case "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet":
                return "xlsx";
            case "application/vnd.openxmlformats-officedocument.presentationml.presentation":
                return "pptx";
        }
        
        throw new BadRequestHttpException("The mime type " . $mime . " is not supported");
    }
}
