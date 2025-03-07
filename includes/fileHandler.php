<?php

class fileHandler {
    private $filePath;

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function readJson() {
        if (!file_exists($this->filePath)) {
            return [];
        }

        if (!is_readable($this->filePath)) {
            throw new Exception("File is not readable.");
        }

        $json = file_get_contents($this->filePath);
        if ($json === false) {
            throw new Exception("Error reading JSON file.");
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON format: " . json_last_error_msg());
        }

        return $data;
    }

    public function writeJson($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new Exception("Error encoding JSON: " . json_last_error_msg());
        }

        if (!is_writable($this->filePath)) {
            throw new Exception("File is not writable.");
        }

        $result = file_put_contents($this->filePath, $json);
        if ($result === false) {
            throw new Exception("Error writing to JSON file.");
        }
    }

    public function updateJson($index, $newData) {
        $data = $this->readJson();

        if (!isset($data[$index])) {
            throw new Exception("Index $index not found in JSON file.");
        }

        $data[$index] = array_merge($data[$index], $newData);
        $this->writeJson($data);
    }
}

?>
