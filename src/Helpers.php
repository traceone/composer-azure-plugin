<?php

namespace TraceOne\Composer;

/**
 * 
 */
class Helpers
{
    /**
     * Compress the provided directory to a zip archive
     */
    public static function buildArchive(String $root_path)
    {
        $root_path = realpath($root_path);
        $archive = new \ZipArchive();
        $filename = $root_path . '.zip';

        if($archive->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) 
        {
            exit("Impossible d'ouvrir le fichier <$filename>\n");
        }

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root_path), \RecursiveIteratorIterator::LEAVES_ONLY);
        
        foreach($files as $name => $file)
        {
            if(!$file->isDir())
            {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($root_path) + 1);

                $archive->addFile($file_path, $relative_path);
            }
        }

        $archive->close();
    }

    /**
     * Recursively delete a directory
     */
    public static function removeDirectory(String $root_path)
    {
        $dir = opendir($root_path);
        
        while(false !== ($file = readdir($dir)))
        {
            if(($file != '.') && ($file != '..'))
            {
                $full = $root_path . '/' . $file;
                
                if(is_dir($full))
                {
                    self::removeDirectory($full);
                }
                else
                {
                    unlink($full);
                }
            }
        }

        closedir($dir);
        rmdir($root_path);
    }
}