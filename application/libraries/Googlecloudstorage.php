<?php

class Googlecloudstorage{

    public $client;
    public $storageService;
    private $googleapiclientpath;
    private $sourcefilepath;

    public function __construct()
    {
        $this->googleapiclientpath = "application/libraries/google/src/";
        $this->sourcefilepath = "storage/";
        set_include_path($this->googleapiclientpath . PATH_SEPARATOR . get_include_path());     
        require_once $this->googleapiclientpath.'Google/Client.php';      
    } 

    public function set_client()
    {
        $client_id = '205701658724-heil3mmtfne3ltqkc556q9hn69i6r2tn.apps.googleusercontent.com'; //Client ID
        $service_account_name = '205701658724-heil3mmtfne3ltqkc556q9hn69i6r2tn@developer.gserviceaccount.com'; //Email Address
        $key_file_location = $this->googleapiclientpath.'Google/key/BuscadorEyT-0f667123983b.p12'; //key.p12

        $this->client = new Google_Client();
        $this->client->setApplicationName("Google Cloud Storage");

        // if (isset($_SESSION['service_token_2'])) {
        //     $this->client->setAccessToken($_SESSION['service_token_2']);
        // }
        $key = file_get_contents($key_file_location);
        $cred = new Google_Auth_AssertionCredentials(
            $service_account_name,
            array('https://www.googleapis.com/auth/devstorage.full_control'),
            $key
            );
        $this->client->setAssertionCredentials($cred);
        if ($this->client->getAuth()->isAccessTokenExpired()) {
            $this->client->getAuth()->refreshTokenWithAssertion($cred);
        }
        #$_SESSION['service_token_2'] = $this->client->getAccessToken();             
    }

    public function media_file_upload($_bucket = null, $_file = null, $_name = null)
    { 
        $gso = new Google_Service_Storage_StorageObject();
        $gso->setName($_name);
        $gso->setBucket($_bucket);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $this->sourcefilepath.$_file);
        $chunkSizeBytes = 1 * 1024 * 1024;
        
        $this->client->setDefer(true);
        $status = false;

        $filetoupload = array(
                                'name'=>$_name,
                                'uploadType'=>'resumable'    
                            );

        $this->newStorageService();
        $request = $this->storageService->objects->insert($_bucket, $gso, $filetoupload);
        $media = new Google_Http_MediaFileUpload($this->client, $request, $mimetype, null, true, $chunkSizeBytes);
        $media->setFileSize(filesize($this->sourcefilepath.$_file));
        $handle = fopen($this->sourcefilepath.$_file, "rb");

        while(!$status && !feof($handle))
        {
            $chunk = fread($handle, $chunkSizeBytes);
            $status = $media->nextChunk($chunk);
        }

        fclose($handle);
        $this->client->setDefer(false);
        return $status;

    }


    public function newStorageService()
    {
        require_once 'google/src/Google/Service/Storage.php'; 
        $this->storageService = new Google_Service_Storage($this->client);              
    }

}

