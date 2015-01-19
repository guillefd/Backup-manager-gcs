<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Backup extends CI_Controller {

	public $gcs;

	public function __construct()
	{
		parent::__construct();
		$this->load->library('googlecloudstorage');
		$this->gcs =& $this->googlecloudstorage;
	}

	public function run_backup_db()
	{
		$this->gcs->set_client();
		$this->gcs->media_file_upload('backup_eytwhmcs', 'small.zip', 'db/file.zip');		
	}

	public function run_backup_md()
	{
		$this->gcs->set_client();
		$this->gcs->media_file_upload('backup_eytwhmcs', 'medium.zip', 'db/medfile.zip');		
	}

	public function run_backup_lg()
	{
		$this->gcs->set_client();
		$this->gcs->media_file_upload('backup_eytwhmcs', 'large.zip', 'db/lgfile.zip');		
	}	


}

/* End of file backup.php */
/* Location: ./application/controllers/backup.php */