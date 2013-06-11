<?php

// NOTE: You should be using Composer's global autoloader.  But just so these examples
// work for people who don't have Composer, we'll use the library's "autoload.php".
require_once __DIR__.'/../lib/Dropbox/autoload.php';

use \Dropbox as dbx;

$appInfoFile = __DIR__."/web-file-browser.app";

session_start();

$req = $_SERVER['SCRIPT_NAME'];

if ($req == "/") {
    $dbxClient = getClient();

    if ($dbxClient === false) {
        header("Location: /oauth-start");
        exit;
    }

    $path = "/";
    if (isset($_GET['path'])) $path = $_GET['path'];

    if (isset($_GET['dl'])) {
        passFileToBrowser($dbxClient, $path);
    }
    else {
        $entry = $dbxClient->getMetadataWithChildren($path);

        if ($entry['is_dir']) {
            echo renderFolder($entry);
        }
        else {
            echo renderFile($entry);
        }
    }
}
else if ($req == "/oauth-start") {
    $dbxConfig = getAppConfig();

    $webAuth = new dbx\WebAuth($dbxConfig);
    $callbackUrl = getBaseUrl()."/oauth_callback";
    list($requestToken, $authorizeUrl) = $webAuth->start($callbackUrl);

    $_SESSION['requestToken'] = $requestToken->serialize();

    header("Location: $authorizeUrl");
    exit;
}
else if ($req == "/oauth_callback") {
    $dbxConfig = getAppConfig();
    $webAuth = new dbx\WebAuth($dbxConfig);

    if (isset($_GET['not_approved']) && $_GET['not_approved'] == 'true') {
        echo renderHtmlPage("Not Authorized", "This app was not given access to this folder");
        exit;
    }

    if (!isset($_GET['oauth_token'])) {
        echo renderHtmlPage("Error", "Dropbox didn't give us an 'oauth_token' parameter.");
        exit;
    }
    $requestToken = dbx\RequestToken::deserialize($_SESSION['requestToken']);
    if (!$requestToken->matchesKey($_GET['oauth_token'])) {
        echo renderHtmlPage("Error", "Request token mismatch.");
        exit;
    }

    list($accessToken, $dropboxUserId) = $webAuth->finish($requestToken);

    $_SESSION['accessToken'] = $accessToken->serialize();

    echo renderHtmlPage("Authorized!", "Auth complete, <a href='/'>click here</a> to browse");
    exit;
}
else if ($req == "/upload") {
    if (empty($_FILES['file']['name'])) {
        echo renderHtmlPage("Error", "Please choose a file to upload");
        exit;
    }

    if (!empty($_FILES['file']['error'])) {
        echo renderHtmlPage("Error", "Error ".$_FILES['file']['error']." uploading file.  See <a href='http://php.net/manual/en/features.file-upload.errors.php'>the docs</a> for details");
        exit;
    }

    $dbxClient = getClient();

    $remoteDir = "/";
    if (isset($_POST['folder'])) $remoteDir = $_POST['folder'];

    $remotePath = rtrim($remoteDir, "/")."/".$_FILES['file']['name'];

    $result = $dbxClient->uploadFile($remotePath, dbx\WriteMode::add(), fopen($_FILES['file']['tmp_name'], "rb"));
    $str = print_r($result, TRUE);
    echo renderHtmlPage("Uploading File", "Result: <pre>$str</pre>");
}
else {
    echo renderHtmlPage("Bad URL", "No handler for $req");
    exit;
}

function renderFolder($entry)
{
    // TODO: Add a token to counter CSRF attacks.
    $form = <<<HTML
        <form action='/upload' method='post' enctype='multipart/form-data'>
        <label for='file'>Upload file:</label> <input name='file' type='file'/>
        <input type='submit' value='Upload'/>
        <input name='folder' type='hidden' value='$entry[path]'/>
        </form>
HTML;

    $listing = '';
    foreach($entry['contents'] as $child) {
        $cp = $child['path'];
        $cn = basename($cp);
        if ($child['is_dir']) $cn .= '/';

        $cp = urlencode($cp);
        $listing .= "<div><a style='text-decoration: none' href='/?path=$cp'>$cn</a></div>";
    }

    return renderHtmlPage("Folder: $entry[path]", $form.$listing);
}

function getAppConfig()
{
    global $appInfoFile;

    try {
        $appInfo = dbx\AppInfo::loadFromJsonFile($appInfoFile);
    }
    catch (dbx\AppInfoLoadException $ex) {
        error_log("Unable to load \"$appInfoFile\": " . $ex->getMessage());
        die;
    }

    $userLocale = null;
    $dbxConfig = new dbx\Config($appInfo, "examples-web-file-browser", $userLocale);

    return $dbxConfig;
}

function getClient()
{
    if(!isset($_SESSION['accessToken'])) {
        return false;
    }

    $dbxConfig = getAppConfig();

    try {
        $accessToken = dbx\AccessToken::deserialize($_SESSION['accessToken']);
        $dbxClient = new dbx\Client($dbxConfig, $accessToken);
    }
    catch (Exception $e) {
        error_log("Error in getClient: ".$e->getMessage());
        return false;
    }

    return $dbxClient;
}

function renderFile($entry)
{
    $metadataStr = print_r($entry, TRUE);
    $path = urlencode($entry['path']);
    $body = <<<HTML
        <pre>$metadataStr</pre>
        <a href="/?path=$path&dl=true">Download this file</a>
HTML;

    return renderHtmlPage( "File: $entry[path]", $body );
}

function passFileToBrowser(dbx\Client $dbxClient, $path)
{
    $fd = tmpfile();
    $metadata = $dbxClient->getFile($path, $fd);

    header("Content-type: $metadata[mime_type]");
    fseek($fd, 0);
    fpassthru($fd);
    fclose($fd);
}

function renderHtmlPage($title, $body)
{
    return <<<HTML
    <html>
        <head>
            <title>$title</title>
        </head>
        <body>
            <h1>$title</h1>
            $body
        </body>
    </html>
HTML;
}

function getBaseUrl()
{
    return "http://$_SERVER[SERVER_NAME]:$_SERVER[SERVER_PORT]";
}
