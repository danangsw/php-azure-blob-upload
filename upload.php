<?php
spl_autoload_register(function ($class) {
    require_once str_replace("\\", "/", $class) . ".php";
});

require_once "settings.php";

use WindowsAzure\Common\ServicesBuilder;
use WindowsAzure\Common\ServiceException;
use WindowsAzure\Blob\Models\Block;
use WindowsAzure\Blob\Models\BlockList;
use WindowsAzure\Blob\Models\BlobBlockType;

$connectionString = "DefaultEndpointsProtocol=" . $settings["protocol"] .
    ";AccountName=" . $settings["account_name"] .
    ";AccountKey=" . $settings["account_key"] . ";";

$blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

$file_name = $argv[1];
$blob_name = basename($file_name);

$block_list = new BlockList();

define('CHUNK_SIZE', 4 * 1024 * 1024); //4 MiB

try {
    // Get the file
    $fptr = fopen($file_name, "rb");
    $index = 1;
    while (!feof($fptr)) {
        // Define block id using Base64 Encode
        $block_id = base64_encode(str_pad($index, 6, "0", STR_PAD_LEFT));
        $block_list->addUncommittedEntry($block_id);
        // Split file in chunks
        $data = fread($fptr, CHUNK_SIZE);
        // Upload block
        $blobRestProxy->createBlobBlock($settings["container"], $blob_name, $block_id, $data);
        ++$index;
    }
    
    // Now committing block list
    $blobRestProxy->commitBlobBlocks($settings["container"], $blob_name, $block_list);
} catch (ServiceException $e) {
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}
?>
