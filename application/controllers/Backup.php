<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User controller for the users module (frontend)
 *
 * @author		Guillermo Dova
 * @author		
 * @package		
 */
class Backup extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

        $this->lang->load('backup');
        $this->load->helper('date');
        // FULL DEBUG ##########################
        $this->setFullDump();
        // #####################################     
	}	

	public function index($taskid='undefined')
	{
		$this->cron($taskid);
	}

	public function cli($taskid='undefined')
	{
		echo 'CRON START' . PHP_EOL;
		if($this->input->is_cli_request())
		{
        	$this->cron($taskid);
		}
		else
			{
				echo 'no direct access';
			}
		echo 'CRON END' . PHP_EOL;
	}	

	private function cron($taskid)
	{
		echo 'CRON START' . PHP_EOL;
    	$this->load->library('backupmgr');
		$this->backupmgr->run($taskid);
		echo 'CRON END' . PHP_EOL;		
	}

    //////////////
    // DEBUG // //
    //////////////

    private function setFullDump()
    {
        ini_set('xdebug.var_display_max_depth', 10);
        ini_set('xdebug.var_display_max_children', 256);
        ini_set('xdebug.var_display_max_data', 1024);       
    }
}