<?php

class AutoMake
{
    const KEY_CONNECTIONS = [
        O_NAME_KEY => 'NAME',
        O_HEADERS_KEY => 'HEADERS',
        O_SOURCES_KEY => 'SOURCES',
        O_DEPENDENCIES_KEY => 'DEPENDENCIES',
        O_LIBRARY_KEY => 'LIBRARIES',
        O_GCC_FLAGS_KEY => 'GCC_FLAGS',
    ];

    const PATTERNS = [
        'HEADERS' => '\.h$',
        'SOURCES' => '\.c$',
        'DEPENDENCIES' => '^Makefile$',
    ];

    private string $currentDirectoryHashKey = '';
    private string $makefileName = '';
    private array $core = [
        'NAME' => '',
        'HEADERS' => [],
        'SOURCES' => [],
        'DEPENDENCIES' => [],
        'LIBRARIES' => [],
        'GCC_FLAGS' => '',
    ];

    public function __construct(array $argv)
    {
        $this->currentDirectoryHashKey = hash('md5', rand());

        $this->optionsParsing($argv);

        foreach ($this->core as $key => $value) {
            if ($key == 'LIBRARIES' || $key == 'NAME' || $key == 'GCC_FLAGS') {
                continue;
            }

            $this->filesParsing($this->core[$key], AutoMake::PATTERNS[$key]);
        }

        if (empty($this->core['SOURCES'])) {
            die();
        }
    }

    /**
     * Парсим входящие значения
     */
    private function optionsParsing(array $argv) : bool
    {
        $currentOption = false;

        foreach ($argv as $option) {
            if (isset(AutoMake::KEY_CONNECTIONS[$option])) {
                $currentOption = $option;
                continue;
            }

            if ($currentOption == O_NAME_KEY || $currentOption == O_GCC_FLAGS_KEY) {
                $this->core[AutoMake::KEY_CONNECTIONS[$currentOption]] = trim($option); 
            } else if ($currentOption !== false) {
                $this->core[AutoMake::KEY_CONNECTIONS[$currentOption]][] = rtrim($option, DIRECTORY_SEPARATOR);
            }
        }

        return (true);
    }

    private function filesParsing(array &$files, string $pattern) : void
    {
        $files = array_flip($files);

        foreach ($files as $directory => $directoryFiles) {
            $files[$directory] = [];
            $this->recursiveReading(realpath($directory), $files[$directory], $pattern);
        }
    }


    private function recursiveReading(string $directory, array &$arDirectory, string $pattern) : void
    {
        $opened = opendir($directory);

        if ($opened) {
            while ($fname = readdir($opened)) {
                if ($fname == '.' || $fname == '..') {
                    continue;
                }

                $nextDirectory = $directory . DIRECTORY_SEPARATOR . $fname;
                if (is_dir($nextDirectory)) {
                    $arDirectory[$fname] = [];
                    $this->recursiveReading($nextDirectory, $arDirectory[$fname], $pattern);
                } else if (preg_match('/' . $pattern . '/', $fname, $matches)) {
                    $arDirectory[$this->currentDirectoryHashKey][] = $fname;
                }
            }
        }
    }

    public function createMakefile()
    {
        $includeToObjects = $this->getIncludesToObjects($this->core['HEADERS']);
        file_put_contents(_MAKEFILE_, MAKEFILE_COPYRIGHT);
        file_put_contents(_MAKEFILE_, str_replace('#NAME#', $this->core['NAME'], MAKEFILE_NAME) . PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, MAKEFILE_GCC . PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, str_replace('#GCC_FLAGS#', $this->core['GCC_FLAGS'], MAKEFILE_GCC_FLAGS) . PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, MAKEFILE_OBJ_PATH . PHP_EOL, FILE_APPEND);
        $this->writeIncludeConstants($this->core['HEADERS']);

        $this->writeSourcePaths($this->core['SOURCES'], '');
        $this->writeSourceFiles($this->core['SOURCES'], '');
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeSourceObjCore($this->core['SOURCES'], '');
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, MAKEFILE_OBJECTS, FILE_APPEND);
        $this->writeObjectCores($this->core['SOURCES'], '');
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeDependencies($this->core['DEPENDENCIES']);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeAllRule($this->core['LIBRARIES'], $this->core['DEPENDENCIES']);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeCleanRule($this->core['DEPENDENCIES']);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeFCleanRule($this->core['DEPENDENCIES']);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeReRule($this->core['DEPENDENCIES']);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        file_put_contents(_MAKEFILE_, MAKEFILE_OBJECTS_RULE, FILE_APPEND);
        file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        $this->writeSourceToObject($this->core['SOURCES'], '', $includeToObjects);
    }

    private function writeIncludeConstants($headers)
    {
        foreach ($headers as $directory => $files) {
            if (!empty($files)) {
                $concat = str_replace(
                    '#INCLUDE_UPPER#',
                    str_replace(DIRECTORY_SEPARATOR, '_', strtoupper($directory)),
                    MAKEFILE_INCLUDE_PATH
                );
                $concat = str_replace(
                    '#INCLUDE_LOWER#',
                    $directory . '/',
                    $concat
                );

                file_put_contents(_MAKEFILE_, $concat . PHP_EOL, FILE_APPEND);
            }
        }

        if (isset($concat)) {
            file_put_contents(_MAKEFILE_, PHP_EOL, FILE_APPEND);
        }
    }

    private function getIncludesToObjects($headers)
    {
        $h = [];

        if (!empty($headers)) {
            $h[] = ' -I';

            foreach ($headers as $directory => $files) {
                if (!empty($files)) {
                    $h[] = '$(INCLUDE_' . str_replace(DIRECTORY_SEPARATOR, '_', strtoupper($directory)) . ')';
                }
            }
        }

        return (implode(' ', $h));
    }

    private function writeSourceToObject($currentDirectory, $fullPath, $includeToObjects)
    {
        foreach ($currentDirectory as $directory => $files) {
            if ($directory != $this->currentDirectoryHashKey) {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);
                $sourcePath = str_replace(
                    '#DIRECTORY_UPPER#', 
                    strtoupper(str_replace(DIRECTORY_SEPARATOR, '_', $fullPath)) . '_PATH', 
                    MAKEFILE_OBJECTS_O
                );
                $sourcePath = str_replace('#INCLUDES#', $includeToObjects, $sourcePath);
                file_put_contents(_MAKEFILE_, $sourcePath, FILE_APPEND);
                
                $this->writeSourceToObject($currentDirectory[$directory], $fullPath, $includeToObjects);
            }
        }
    }

    private function writeObjectCores($currentDirectory, $fullPath)
    {
        foreach ($currentDirectory as $directory => $files) {
            if ($directory != $this->currentDirectoryHashKey) {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);

                file_put_contents(
                    _MAKEFILE_, 
                    '$(OBJ_' . strtoupper(str_replace(DIRECTORY_SEPARATOR, '_', $fullPath)) . '_CORE) ', 
                    FILE_APPEND
                );
                $this->writeObjectCores($currentDirectory[$directory], $fullPath);
            }
        }
    }

    private function writeAllRule($libraries, $dependencies)
    {
        $concat = '';
        if (!empty($libraries)) {
            $concat = '-L ' . implode(' -L ', $libraries);
        }
        $allRule = str_replace('#LIBRARIES#', $concat, MAKEFILE_ALL_NAME);
        //file_put_contents(_MAKEFILE_, str_replace('#LIBRARIES#', $concat, MAKEFILE_ALL_NAME), FILE_APPEND);

        $depDirectories = [];
        foreach ($dependencies as $directory => $files) {
            if (!empty($files)) {
                $depDirectories[] = 'make -C ' . $directory;
            }
        }

        $concat = PHP_EOL;
        if (!empty($depDirectories)) {
            $concat = PHP_EOL . "\t\t" . implode(PHP_EOL . "\t\t", $depDirectories) . PHP_EOL;
        }
        $allInstructions = str_replace(
            '#DEPENDENCIES#', 
            $concat,
            $allRule
        );
        file_put_contents(_MAKEFILE_, $allInstructions, FILE_APPEND);
    }

    private function writeSourcePaths($currentDirectory, $fullPath)
    {
        foreach ($currentDirectory as $directory => $files) {
            if ($directory != $this->currentDirectoryHashKey) {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);
                $sourcePath = str_replace(
                    '#DIRECTORY_UPPER#', 
                    strtoupper(str_replace(DIRECTORY_SEPARATOR, '_', $fullPath)), 
                    MAKEFILE_SOURCES_PATH
                );
                $sourcePath = str_replace('#DIRECTORY_LOWER#', $fullPath, $sourcePath);
                file_put_contents(_MAKEFILE_, $sourcePath, FILE_APPEND);
                
                $this->writeSourcePaths($currentDirectory[$directory], $fullPath);
            }
        }
    }

    private function writeSourceFiles($currentDirectory, $fullPath)
    {
        foreach ($currentDirectory as $directory => $files) {
            if ($directory == $this->currentDirectoryHashKey) {
                $filesStr = "\\" . PHP_EOL . "\t\t\t" . implode("\\" . PHP_EOL . "\t\t\t", $files);
                $sourceFiles = str_replace(
                    '#DIRECTORY_UPPER#', 
                    strtoupper(str_replace(DIRECTORY_SEPARATOR, '_', $fullPath)), 
                    MAKEFILE_SOURCES_FILES
                );
                $sourceFiles = str_replace('#DIRECTORY_FILES#', $filesStr, $sourceFiles);
                file_put_contents(_MAKEFILE_, $sourceFiles, FILE_APPEND);
            } else {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);
                $this->writeSourceFiles($currentDirectory[$directory], $fullPath);
            }
        }
    }

    private function writeSourceObjCore($currentDirectory, $fullPath)
    {
        foreach ($currentDirectory as $directory => $files) {
            if ($directory != $this->currentDirectoryHashKey) {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);
                $sourcePath = str_replace(
                    '#DIRECTORY_UPPER#', 
                    strtoupper(str_replace(DIRECTORY_SEPARATOR, '_', $fullPath)), 
                    MAKEFILE_OBJ_CORE
                );
                file_put_contents(_MAKEFILE_, $sourcePath, FILE_APPEND);
                
                $this->writeSourceObjCore($currentDirectory[$directory], $fullPath);
            }
        }
    }

    /**
     * DEPRECATED
     */
    private function writeDependencies($dependencies)
    {
        /*$depDirectories = [];
        foreach ($dependencies as $directory => $files) {
            if (!empty($files)) {
                $depDirectories[] = 'make -C ' . $directory;
            }
        }

        $concat = PHP_EOL;
        if (!empty($depDirectories)) {
            $concat = PHP_EOL . "\t\t" . implode(PHP_EOL . "\t\t", $depDirectories) . PHP_EOL;
        }
        $allInstructions = str_replace(
            '#DEPENDENCIES#', 
            $concat,
            MAKEFILE_ALL
        );
        */
        file_put_contents(_MAKEFILE_, MAKEFILE_ALL, FILE_APPEND);
    }

    private function writeCleanRule($dependencies)
    {
        $depDirectories = [];
        foreach ($dependencies as $directory => $files) {
            if (!empty($files)) {
                $depDirectories[] = 'make clean -C ' . $directory;
            }
        }

        $concat = '';
        if (!empty($depDirectories)) {
            $concat = "\t\t" . implode(PHP_EOL . "\t\t", $depDirectories) . PHP_EOL;
        }
        $allInstructions = str_replace(
            '#DEPENDENCIES#', 
            $concat,
            MAKEFILE_CLEAN
        );
        file_put_contents(_MAKEFILE_, $allInstructions, FILE_APPEND);
    }

    private function writeFCleanRule($dependencies)
    {
        $depDirectories = [];
        foreach ($dependencies as $directory => $files) {
            if (!empty($files)) {
                $depDirectories[] = 'make fclean -C ' . $directory;
            }
        }

        $concat = '';
        if (!empty($depDirectories)) {
            $concat = "\t\t" . implode(PHP_EOL . "\t\t", $depDirectories) . PHP_EOL;
        }
        $allInstructions = str_replace(
            '#DEPENDENCIES#', 
            $concat,
            MAKEFILE_FCLEAN
        );
        file_put_contents(_MAKEFILE_, $allInstructions, FILE_APPEND);
    }

    private function writeReRule($dependencies)
    {
        file_put_contents(_MAKEFILE_, MAKEFILE_RE, FILE_APPEND);
    }

    public function writeLog()
    {
        echo "\t\e[97m" . '>--------AUTO_MAKE--------<' . "\e[0m" . PHP_EOL;
        echo "\e[96m" . 'FILE_TYPE: ' . "\e[91m" . 'EXECUTABLE' . "\e[91m" . PHP_EOL;
        echo "\e[96m" . 'FILE_NAME: ' . "\e[91m" .$this->core['NAME'] . "\e[91m" . PHP_EOL;
        echo "\e[96m" . 'GCC_FLAGS: ' . "\e[91m" .$this->core['GCC_FLAGS'] . "\e[91m" . PHP_EOL;
        echo "\e[96m" . 'LIBRARIES: ' . "\e[91m" . implode(' ', $this->core['LIBRARIES']) . "\e[91m" . PHP_EOL . PHP_EOL;
        echo "\e[96m" . 'SOURCES:' . "\e[0m" . PHP_EOL;
        
        $this->writeSourcesLog($this->core['SOURCES'], '', 1);
        echo PHP_EOL;
        $this->writeIncludesLog($this->core['HEADERS']);
        echo PHP_EOL;
        $this->writeDependenciesLog($this->core['DEPENDENCIES']);
        echo "\t\e[97m" . '>--------AUTO_MAKE--------<' . "\e[0m" . PHP_EOL;
    }
    
    private function writeSourcesLog($currentDirectory, $fullPath, $depth)
    {
        foreach ($currentDirectory as $directory => $files) {
            $depthTemp = $depth;
            $newLine = "";
            while ($depthTemp--) {
                $newLine .= "   ";
            }
            if ($directory == $this->currentDirectoryHashKey) {
                $filesImploded = $newLine . implode(PHP_EOL . $newLine, $files) . PHP_EOL;
                echo "\e[93m";
                echo $filesImploded;
                echo "\e[0m";
            } else {
                $fullPath = ltrim($fullPath . DIRECTORY_SEPARATOR . $directory, DIRECTORY_SEPARATOR);
                if ($depth > 1) {
                    echo PHP_EOL;
                }
                echo $newLine;
                echo "\e[92m" . $fullPath . "\e[0m" . PHP_EOL;
                $this->writeSourcesLog($currentDirectory[$directory], $fullPath, $depth + 1);
            }
        }
    }

    private function writeIncludesLog($headers)
    {
        if (!empty($headers)) {
            echo "\e[96m" . 'INCLUDES:' . "\e[0m" . PHP_EOL;
            foreach ($headers as $directory => $files) {
                echo '   ' . "\e[92m" . $directory . "\e[0m" . PHP_EOL;

                if (isset($headers[$directory][$this->currentDirectoryHashKey])) {
                    echo "\e[93m";
                    $filesImploded = '      ' . implode(PHP_EOL . '      ', $headers[$directory][$this->currentDirectoryHashKey]) . PHP_EOL;
                    echo $filesImploded;
                    echo "\e[0m";
                }
            }
        }
    }

    private function writeDependenciesLog($dependencies)
    {
        if (!empty($dependencies)) {
            echo "\e[96m" . 'DEPENDENCIES:' . "\e[0m" . PHP_EOL;
            foreach ($dependencies as $directory => $files) {
                echo '   ' . "\e[92m" . $directory . "\e[0m" . PHP_EOL;

                if (isset($dependencies[$directory][$this->currentDirectoryHashKey])) {
                    echo "\e[93m";
                    $filesImploded = '      ' . implode(PHP_EOL . '      ', $dependencies[$directory][$this->currentDirectoryHashKey]) . PHP_EOL;
                    echo $filesImploded;
                    echo "\e[0m";
                }
            }
        }
    }
    
}