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

        // FULL DEBUG ##########################
        $this->setFullDump();
        // #####################################     
	}	

	public function index($task_id='undefined')
	{
		echo 'CRON START' . PHP_EOL;
    	$this->load->library('backupmgr');
		$this->backupmgr->run($task_id);
		echo 'CRON END' . PHP_EOL;
	}

	public function cli($task_id='undefined')
	{
		echo 'CRON START' . PHP_EOL;
		if($this->input->is_cli_request())
		{
        	$this->load->library('backupmgr');
			$this->backupmgr->run($task_id);
		}
		else
			{
				echo 'no direct access';
			}
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