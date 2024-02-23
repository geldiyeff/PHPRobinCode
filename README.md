# phpRobinCode

## Overview
`phpRobinCode` is a PHP utility for recursively downloading web pages and extracting links based on specified configuration settings.

## Project Information

- **Author:** Dovletmammet Geldiyev
- **License:** MIT License
- **Version:** 2.0.0

## Usage

### Installation
1. Clone the repository: `git clone https://github.com/geldiyeff/phpRobinCode.git`
2. Navigate to the project directory: `cd phpRobinCode`

### Configuration
- Modify the `config.json` file to customize link extraction settings.
- Run the script and enter the URL of the target website when prompted.

### Execution
Execute the script using the command: `php phpRobinCode.php`

## Link Types Configuration

The following HTML tags and their corresponding attributes are considered for link extraction:

- `<a>`: href
- `<link>`: href
- `<script>`: src
- `<img>`: src
- `<source>`: src
- `<object>`: data
- `<video>`: src
- `<audio>`: src

## Excluded Links

The following link patterns are excluded during the extraction process:

- `mailto:`
- `tel:`
- `javascript:`
- `+`
- `#`
- `'`
- `?`
- `{href}`
- `email-protection`

## Example Output

Successful downloads are indicated in green, and failed downloads are indicated in red.