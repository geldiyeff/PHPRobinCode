<?php

class PHPRobinCode
{
    private $url; // The URL of the website to be downloaded
    private $config; // Configuration settings loaded from 'config.json'
    private $templateDir; // Directory where downloaded files are stored
    private $fileName = []; // Queue of file names to be downloaded
    private $downloadedPages = []; // List of successfully downloaded pages
    private $couldntDownloadPages = []; // List of pages that couldn't be downloaded

    public function __construct()
    {
        $this->config = $this->loadConfig('config.json'); // Load configuration settings

        $this->url = $this->getUserInput('Enter the URL of the website: '); // Get user input for the website URL

        $this->templateDir = "templates/" . parse_url($this->url, PHP_URL_HOST); // Create a directory based on the host name

        $this->run(); // Start the downloading process

        echo "Done!\n"; // Display a completion message
    }

    public function run()
    {
        $path = parse_url($this->url, PHP_URL_PATH);
        $file = basename($path);

        if (!empty($file)) {
            $this->url = str_replace($file, '', $this->url);
            array_push($this->fileName, $file); // Add the initial file to the download queue
        } else {
            array_push($this->fileName, "index.html"); // Use "index.html" as the default file if no file is specified in the URL
        }

        while (!empty($this->fileName)) {
            $file = array_shift($this->fileName);
            $this->downloadPage($this->url . $file, $this->templateDir . '/' . $file);
        }

        if (!file_exists($this->templateDir . "/README.txt")) {
            $this->createReadmeFile($this->url);
        }
    }

    private function downloadPage($url, $filePath)
    {
        $this->createDirectory(dirname($filePath));
        if (strpos($filePath, '.') === false) {
            $filePath .= ".html";
        }
        if (!file_exists($filePath)) {
            if (file_put_contents($filePath, file_get_contents($url))) {
                echo "\033[0;32m$url\033[0m\n"; // Display a success message in green
                array_push($this->downloadedPages, $filePath); // Add the downloaded page to the list
                $this->getLinks($filePath); // Extract links from the downloaded page
            } else {
                echo "\033[0;31m$url\033[0m\n"; // Display a failure message in red
                array_push($this->couldntDownloadPages, $filePath); // Add the failed page to the list
            }
        }
    }

    private function createDirectory($directory)
    {
        $parts = explode("/", $directory);
        $path = "";

        foreach ($parts as $part) {
            $path .= $part . "/";
            if (!file_exists($path)) {
                mkdir($path, 0777, true); // Create directories as needed with full permissions
            }
        }
    }

    private function getLinks($file)
    {
        $dom = new DOMDocument;
        @$dom->loadHTML(file_get_contents($file));

        foreach ($this->config['linkTypes'] as $tag => $attribute) {
            $elements = $dom->getElementsByTagName($tag);
            foreach ($elements as $element) {
                $link = $element->getAttribute($attribute);
                if (!in_array($link, $this->downloadedPages) && !in_array($link, $this->fileName) && $this->isValidLink($link) && !in_array($link, $this->couldntDownloadPages)) {
                    array_push($this->fileName, $link); // Add valid links to the download queue
                }
            }
        }
    }

    private function isValidLink($link): bool
    {
        foreach ($this->config["blockNames"] as $blockName) {
            if (strpos($link, $blockName) !== false) {
                return false; // Check if the link contains any blocked names
            }
        }

        return true;
    }

    private function createReadmeFile($url)
    {
        $readmeContent = "/*\n\n";
        $readmeContent .= "@author: " . $this->config['author'] . "\n";
        $readmeContent .= "@license: " . $this->config['license'] . "\n";
        $readmeContent .= "@version: " . $this->config['version'] . "\n";
        $readmeContent .= "@project: " . $this->config['project'] . "\n\n";
        $readmeContent .= "Web Site URL: $url\n";
        $readmeContent .= "*/\n";

        file_put_contents($this->templateDir . "/README.txt", $readmeContent); // Create a README.txt file with project information
    }

    private function loadConfig($path): ?array
    {
        if (file_exists($path)) {
            $config = json_decode(file_get_contents($path), true);
            if ($config === null) {
                throw new Exception("Invalid JSON in config file"); // Handle invalid JSON in the config file
            }
            return $config;
        } else {
            throw new Exception("Config file not found"); // Handle missing config file
        }
    }

    private function getUserInput($msg): string
    {
        echo $msg;
        $url = trim(fgets(STDIN));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo "Invalid URL\n"; // Display an error message for an invalid URL
            return (string) $this->getUserInput($msg); // Recursively ask for a valid URL
        } else {
            return (string) $url;
        }
    }
}

// Start the application
new PHPRobinCode();
