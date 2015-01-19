<?php

class Googlecloudstorage{

    public $client;
    public $storageService;
    private $googleapiclientpath;
    private $sourcefilepath;
    private $CI; 
    private $GCS_CFG;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->googleapiclientpath = "application/libraries/google/src/";
        $this->sourcefilepath = "storage/";
        set_include_path($this->googleapiclientpath . PATH_SEPARATOR . get_include_path());     
        require_once $this->googleapiclientpath.'Google/Client.php';    
        # config
        $this->CI->config->load('gcs', true);
        $this->GCS_CFG = $this->CI->config->item('gcs');
    } 

    public function set_client()
    {
        $client_id = $this->GCS_CFG['gcs_client_id']; //Client ID
        $service_account_name = $this->GCS_CFG['gcs_service_account_name'];  //Email Address
        $key_file_location = $this->googleapiclientpath.'Google/key/'.$this->GCS_CFG['gcs_key_file']; //key.p12

        $this->client = new Google_Client();
        $this->client->setApplicationName($this->GCS_CFG['gcs_app_name']);

        // if (isset($_SESSION['service_token_2'])) {
        //     $this->client->setAccessToken($_SESSION['service_token_2']);
        // }
        $key = file_get_contents($key_file_location);
        $cred = new Google_Auth_AssertionCredentials(
            $service_account_name,
            array($this->GCS_CFG['gcs_oauth_scope']),
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

