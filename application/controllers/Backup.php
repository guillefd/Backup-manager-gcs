<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Backup extends CI_Controller {

	public $gcs;
	public $dbutil;
	public $CFG = array();

	public function __construct()
	{
		parent::__construct();
		# load gcs library 
		$this->load->library('googlecloudstorage');
		$this->gcs =& $this->googlecloudstorage;
        # config
        $this->config->load('backup_settings', true);
        $this->CFG['bkp'] = $this->config->item('backup_settings');
	}

	public function index()
	{
		$this->load->view('backup');
	}

	public function set_backup_file()
	{
var_dump($this->CFG);	
	}

	public function upload_backup_files()
	{
		$this->gcs->set_client();
		$this->gcs->media_file_upload('backup_eytwhmcs', 'small.zip', 'db/file_1.zip');			
	}


}

/* End of file backup.php */
/* Location: ./application/controllers/backup.php */