#!/usr/bin/env php
<?php

require_once __DIR__.'/helper.php';
use \Dropbox as dbx;

list($client, $localPath, $dropboxPath) = parseArgs("upload-file", $argv, array(
        array("local-path", "The local path of the file to upload."),
        array("dropbox-path", "The path (on Dropbox) to save the file to."),
    ));

$pathError = dbx\Path::findErrorNonRoot($dropboxPath);
if ($pathError !== null) {
    fwrite(STDERR, "Invalid <dropbox-path>: $pathError\n");
    die;
}

$metadata = $client->uploadFile($dropboxPath, dbx\WriteMode::add(), fopen($localPath, "rb"), filesize($localPath));

print_r($metadata);
