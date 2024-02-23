<?php

namespace Geldiyeff\PHPRobincode;

error_reporting(0);

use Exception;
use DOMDocument;
use InvalidArgumentException;

/**
 * Class PHPRobinCode
 *
 * A utility class for recursively downloading web pages and their local links
 * based on specified configuration settings.
 */
class PHPRobinCode
{
    const CONFIG_FILE = 'config.json';
    const TEMPLATES_DIR = 'templates';

    private string $templateDir;
    private string $url;
    private string $domain;
    private string $fileURL;
    private array $config;
    private array $files = [];
    private array $executedFiles = [];

    /**
     * PHPRobinCode constructor.
     *
     * Initializes the PHPRobinCode instance, loads configuration settings,
     * prompts the user for a website URL, and sets up initial parameters.
     */
    public function __construct()
    {
        $this->config = $this->loadJson(self::CONFIG_FILE);
        $this->url = $this->getUserInput('Enter the URL of the website: ');
        $this->domain = parse_url($this->url, PHP_URL_HOST);
        $this->templateDir = self::TEMPLATES_DIR . '/' . $this->domain . '/';
        $this->files[] = $this->url;
        $this->run();
        echo "Done!\n";
    }

    /**
     * Initiates the recursive process of downloading files and extracting links.
     *
     * @return void
     */
    public function run(): void
    {
        while (!empty($this->files)) {
            $file = array_shift($this->files);
            $this->fileURL = dirname($file) . '/';
            $this->executedFiles[] = $file;
            $this->getLinks($file);

            if ($this->downloadFile($file)) {
                $msg = "\033[0;32m{%url%}\033[0m\n";
            } else {
                $msg = "\033[0;31m{%url%}\033[0m\n";
            }
            echo str_replace('{%url%}', $file, $msg);
        }

        $this->createReadMeFile($this->url, $this->templateDir);
    }


    /**
     * Downloads a file from a given URL and saves it based on the specified rules.
     *
     * @param string $url The URL of the file to be downloaded.
     * @return bool Returns true on successful download, false otherwise.
     * @throws InvalidArgumentException if the URL or file path is invalid.
     */
    private function downloadFile(string $url): bool
    {
        // Validation
        if (!isset($this->url) || empty($url)) {
            throw new InvalidArgumentException("Invalid URL or file path.");
        }

        // Set up directory structure
        $dir = dirname(parse_url($url, PHP_URL_PATH));
        $dir = $this->templateDir . $dir;

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Set up file name
        $fileName = basename($url);

        if ($fileName === $this->domain) {
            $fileName = 'index.html';
        } elseif (pathinfo($fileName, PATHINFO_EXTENSION) === '') {
            $fileName .= '.html';
        }

        // Download and save the file
        $file = file_get_contents($url);

        if ($file === false) {
            return false;
        } else {
            $this->saveFile($dir . '/' . $fileName, $file);
            return true;
        }
    }

    /**
     * Extracts links from a given HTML file based on specified tag and attribute settings.
     *
     * @param string $file The path to the HTML file.
     */
    private function getLinks(string $file): void
    {
        $dom = new DOMDocument();
        @$dom->loadHTMLFile($file);

        foreach ($this->config['linkTypes'] as $tag => $attr) {
            foreach ($dom->getElementsByTagName($tag) as $element) {
                $link = $element->getAttribute($attr);

                if ($this->isLocalLink($link) && !in_array($link, $this->files) && !$this->excludedLink($link) && !in_array($link, $this->executedFiles)) {
                    $this->files[] = $link;
                }
            }
        }
    }


    /**
     * Checks if a given link is local or external.
     *
     * @param string $link The link to be checked.
     * @return bool Returns true if the link is local, false if it's external.
     */
    private function isLocalLink(string &$link): bool
    {
        if (!preg_match('/^http[s]?:\/\//', $link)) {
            $link = $this->fileURL . $link;
            return true;
        } else {
            return (parse_url($link, PHP_URL_HOST) === $this->domain) ? true : false;
        }
    }

    /**
     * Checks if a given link is excluded based on the configuration settings.
     *
     * @param string $link The link to be checked.
     * @return bool Returns true if the link should be excluded, false otherwise.
     */
    private function excludedLink(string $link): bool
    {
        foreach ($this->config['excludedLinks'] as $excludedLink) {
            if (strpos($link, $excludedLink) !== false || empty($link)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Loads a JSON file and returns its content as an associative array.
     *
     * @param string $filePath The path to the JSON file.
     * @return array|null Returns the content of the JSON file as an associative array.
     * @throws Exception if the JSON file is invalid or not found.
     */
    private function loadJson(string $filePath): ?array
    {
        if (file_exists($filePath)) {
            $json = json_decode(file_get_contents($filePath), true);
            if ($json === null) {
                throw new Exception('Invalid JSON file.');
            }
            return $json;
        } else {
            throw new Exception('File not found.');
        }
    }

    /**
     * Saves content to a file.
     *
     * @param string $fileName The name of the file to be saved.
     * @param string $content The content to be saved.
     * @throws Exception if there is an error creating the file.
     */
    private function saveFile(string $fileName, string $content): bool
    {
        return (file_put_contents($fileName, $content) === false) ? false : true;
    }

    /**
     * Prompts the user for input and validates the entered URL.
     *
     * @param string $message The message to be displayed to the user.
     * @return string Returns a valid URL entered by the user.
     */
    private function getUserInput(string $message): string
    {
        echo $message;
        $url = trim(fgets(STDIN));

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            echo 'Invalid URL. Please enter a valid URL.' . PHP_EOL;
            return (string) $this->getUserInput($message);
        } else {
            return $url;
        }
    }


    /**
     * Creates a README file for the project with essential information.
     *
     * @param string $url The URL of the website being processed.
     * @return void
     */
    private function createReadMeFile(string $url, string $path): void
    {
        $content = "/*\n\n";
        $content .= "@author: " . $this->config["author"] . "\n";
        $content .= "@license: " . $this->config['license'] . "\n";
        $content .= "@version: " . $this->config['version'] . "\n";
        $content .= "@project: " . $this->config['project'] . "\n\n";
        $content .= "Web Site URL: $url\n";
        $content .= "*/\n";

        $this->saveFile($path . 'README.md', $content);
    }
}

new PHPRobinCode();
