<?php

class Googlecloudstorage{

    private $client;
    private $storageService;
    private $googleapiclientpath;
    private $sourcefilepath;
    private $CI; 
    private $GCS_CFG;

    const STORAGE_OBJECT = 'storage#object';
    const STEP_UPLOAD = 'upload_file';

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->googleapiclientpath = FCPATH."vendor/google/apiclient/src/";
        $this->googleapikeyfile = FCPATH."assets/gcs/";
        $this->sourcefilepath = "storage/";
        set_include_path($this->googleapiclientpath . PATH_SEPARATOR . get_include_path());     
        require_once $this->googleapiclientpath.'Google/Client.php';    
        # config
        $this->CI->config->load('gcs_settings', true);
        $this->GCS_CFG = $this->CI->config->item('gcs_settings');
    } 

    public function set_client()
    {
        $client_id = $this->GCS_CFG['gcs_client_id']; //Client ID
        $service_account_name = $this->GCS_CFG['gcs_service_account_name'];  //Email Address
        $key_file_location = $this->googleapikeyfile.$this->GCS_CFG['gcs_key_file']; //key.p12

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
        # init
        $result = new stdClass();
        $result->error = null;
        $result->status = null;
        $result->exception = null;
        # timer
        $result->starttime = microtime();
        # init gcs api
        $gso = new Google_Service_Storage_StorageObject();
        $gso->setName($_name);
        $gso->setBucket($_bucket);      
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $_file);
        $chunkSizeBytes = 1 * 1024 * 1024;
        $this->client->setDefer(true);
        $filetoupload = array(
                                'name'=>$_name,
                                'uploadType'=>'resumable'    
                            );       
        # service
        $this->newStorageService();
        $status = false;
        # try
        try
        {
            $request = $this->storageService->objects->insert($_bucket, $gso, $filetoupload);
            $media = new Google_Http_MediaFileUpload($this->client, $request, $mimetype, null, true, $chunkSizeBytes);
            $media->setFileSize(filesize($_file));
            $handle = fopen($_file, "rb");
            # loop chunks
            while(!$status && !feof($handle))
            {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }
            fclose($handle);
            $this->client->setDefer(false);
            $result->status = $status;
        }catch(Exception $e)
            {
                $result->error = true;
                $result->status = 'GCS upload failed';
                $result->exception = $e;
            }
        # timer
        $result->endtime = microtime();   
        $result->totaltime = $this->get_totaltime($result);
        # verify response
        $result->httpcode = http_response_code();
        $result->error = isset($status->kind) && $status->kind==self::STORAGE_OBJECT ? false : true;        
        return $result;
    }

    public function newStorageService()
    {
        require_once 'Google/Service/Storage.php'; 
        $this->storageService = new Google_Service_Storage($this->client);              
    }


    ////////////
    // AUX // //
    ////////////

    public function get_client()
    {
        return $this->client;
    }

    public function get_client_access_token()
    {
        return $this->client->getAccessToken();
    }


    /**
     * Calculate a precise time difference.
     * @param string $start result of microtime()
     * @param string $end result of microtime(); if NULL/FALSE/0/'' then it's now
     * @return flat difference in seconds, calculated with minimum precision loss
     */
    private function get_totaltime($result)
    {
        list($start_usec, $start_sec) = explode(" ", $result->starttime);
        list($end_usec, $end_sec) = explode(" ", $result->endtime);
        $diff_sec = intval($end_sec) - intval($start_sec);
        $diff_usec = floatval($end_usec) - floatval($start_usec);
        $totaltime = floatval($diff_sec) + $diff_usec;
        return $totaltime;
    }   

}

