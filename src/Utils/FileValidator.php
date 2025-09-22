<?php

namespace Eusouomichel\PhpCommit\Utils;

class FileValidator
{
    /**
     * Check files for prohibited strings using parallel processing
     */
    public static function checkFilesForProhibitedStrings(array $files, array $prohibitedStrings): array
    {
        if (empty($prohibitedStrings)) {
            return [];
        }

        $invalidFiles = [];
        
        // Process files in parallel chunks for better performance
        $chunks = array_chunk($files, 5); // Process 5 files at a time
        
        foreach ($chunks as $chunk) {
            $results = self::processFileChunk($chunk, $prohibitedStrings);
            $invalidFiles = array_merge($invalidFiles, $results);
        }
        
        return $invalidFiles;
    }

    /**
     * Process a chunk of files
     */
    private static function processFileChunk(array $files, array $prohibitedStrings): array
    {
        $invalidFiles = [];
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $issues = self::checkSingleFile($file, $prohibitedStrings);
            if (!empty($issues)) {
                $invalidFiles[$file] = $issues;
            }
        }
        
        return $invalidFiles;
    }

    /**
     * Check a single file for prohibited strings
     */
    private static function checkSingleFile(string $file, array $prohibitedStrings): array
    {
        $issues = [];
        
        // Use SplFileObject for better memory efficiency with large files
        $fileObject = new \SplFileObject($file);
        $lineNumber = 0;
        
        while (!$fileObject->eof()) {
            $lineNumber++;
            $lineContent = $fileObject->fgets();
            
            if ($lineContent === false) {
                break;
            }
            
            foreach ($prohibitedStrings as $string) {
                if (strpos($lineContent, $string) !== false) {
                    $issues[] = [$string, $lineNumber, trim($lineContent)];
                }
            }
        }
        
        return $issues;
    }

    /**
     * Check if file should be ignored based on common patterns
     */
    public static function shouldIgnoreFile(string $file): bool
    {
        $ignoredPatterns = [
            '/vendor/',
            '/node_modules/',
            '.git/',
            '.min.js',
            '.min.css',
            '.map',
            '.lock'
        ];
        
        foreach ($ignoredPatterns as $pattern) {
            if (strpos($file, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
