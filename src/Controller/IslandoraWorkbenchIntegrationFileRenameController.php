<?php

namespace Drupal\islandora_workbench_integration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\Event\FileUploadSanitizeNameEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class IslandoraWorkbenchIntegrationFileRenameController extends ControllerBase
{


    /**
     * FileSystem service.
     *
     * @var \Drupal\Core\File\FileSystemInterface
     */
    protected $fileSystem;

    /**
     * EventDispatcher service.
     *
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;


    /**
     * Constructor to inject the Symfony Filesystem service.
     */
    public function __construct(FileSystemInterface $fileSystem, EventDispatcherInterface $event_dispatcher)
    {
        $this->fileSystem = $fileSystem;
        $this->eventDispatcher = $event_dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('file_system'),
            $container->get('event_dispatcher')
        );
    }



    /**
     * Renames a file while keeping the extension unchanged.
     *
     * Expects a URL parameter for the file ID (fid) and a JSON payload with:
     * - new_filename: The new base file name (without extension).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The incoming request.
     * @param Drupal\file\Entity\File                   $file
     *   The file ID from the URL.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   The JSON response.
     */
    public function main(Request $request, $file)
    {
        // Decode JSON payload.

        $data = json_decode($request->getContent(), true);

        // Validate that new_filename is provided.
        if (empty($data['new_filename'])) {
            return new JsonResponse(['error' => 'Missing new_filename parameter']);
        }


        if (!$file) {
            return new JsonResponse(["Error 404" => 'No file found']);
        }


        $pathinfo = pathinfo($file->getFileUri());
        $filename_new = $data['new_filename'];
        $msg = $this->validate($file, $filename_new);
        if ($msg != 'OK') {
            return new JsonResponse(['Error' => $msg]);
        }



        if ($filename_new != $file->getFilename()) {
            $filepath_new = $this->getRenamedFilePath($file, $filename_new);
            rename($file->getFileUri(), $filepath_new);
            // Update file entity.
            $file->setFilename($filename_new);
            $file->setFileUri($filepath_new);
            $status = $file->save();

            return new JsonResponse(['message' => 'File renamed successfully', 'new_filename' => $filename_new]);
        }

    }


    /**
     * Check if the file name is valid
     *
     * @param Drupal\file\Entity\File $file
     *   File that needs to be renamed
     *
     * @param string                  $new_filename
     *   File Name of the new file
     * 
     * @return string
     *   Error Message if not valid else OK
     */
    public function validate($file, $new_filename)
    {
        $pathinfo = pathinfo($file->getFileUri());
        $source_file_uri = $file->getFileUri();

        if (!file_exists($source_file_uri)) {
            // Show an error if no file on disc.
            return 'No file exists in this';
        }

    
        $new_basename = $new_filename;

        if ($new_basename !== $file->getFilename()) {
            // File renamed.
            if ($new_basename !== basename($new_basename)  
                || strpos($new_basename, '\\') !== false
            ) {
                // If filename contains a slash or a backslash.
                return 'Value must be a filename with no path information';
            } else {
                // Dispatching a event to use default filename validation.
                $event = new FileUploadSanitizeNameEvent($new_basename, $pathinfo['extension']);
                $this->eventDispatcher->dispatch($event);

                if ($event->isSecurityRename()) {
                    // If new filename contains forbidden characters.
                    return ('File name is invalid');
                }
            }

            $new_file_path = $this->getRenamedFilePath($file, $new_basename);
            if (file_exists($new_file_path)) {
                // File with given name already on disc.
                return ('File with this name already exists in the directory.');
            }
        }

        return 'OK';
    }

    /**
     * Get Renamed File Path.
     *
     * @param Drupal\file\Entity\File $file
     *   File that needs to be renamed.
     *
     * @param string                  $new_filename
     *   File Name of the new file
     * 
     * @return string
     *   File name after rename.
     */
    protected function getRenamedFilePath($file, $new_filename)
    {
        $pathinfo = pathinfo($file->getFileUri());
        $old_filename = $pathinfo['filename'] . '.' . $pathinfo['extension'];
        // Path after renaming.
        return str_replace($old_filename, $new_filename, $file->getFileUri());
    }
}
