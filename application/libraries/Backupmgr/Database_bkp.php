<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Database_bkp{

	const MSGFILENOEXIST = 'FILENOTFOUND';
	const MSGFAIL = 'FAIL';
	const MSGOK = 'OK';
	const MSGNOEXEC = 'NOEXEC';
	const MSGDBDUMPFAILED = 'DBDUMP_FAILED';

	const STEP_DUMPDB = 'dumpDatabase';
	const STEP_CHECK_INT = 'checkIntegrity';
	const STEP_SET_SUCCESS = 'setSuccess';

	private $FPBKP;
	private $task;

	public function __construct($params)
	{
		$this->FPBKP = $params['FPBKP']; // Folder Path Backup
		$this->task = new stdClass();
		$this->task->error = null;
		$this->task->errorcodes = '';
		$this->task->totaltime = 0;
		$this->task->filesize = null;				
		$this->task->steps = array();
		$this->task->settings = null;		
	}

	public function run_db_backup($taskcfg)
	{
		$this->task->settings = $taskcfg;
		$steps = array(self::STEP_DUMPDB, self::STEP_CHECK_INT, self::STEP_SET_SUCCESS);
		for($i=0;$i<count($steps);$i++)
		{
			switch($steps[$i])
			{
				case self::STEP_DUMPDB:
											$this->tasktimer('start', $steps[$i]);
											$this->_dumpDatabase();
											$this->tasktimer('end', $steps[$i]);
											$this->set_tasktimer_totaltime($steps[$i]);
											break;

				case self::STEP_CHECK_INT:
											$this->tasktimer('start', $steps[$i]);
											$this->_checkIntegrity();
											$this->tasktimer('end', $steps[$i]);
											$this->set_tasktimer_totaltime($steps[$i]);
											break;

				case self::STEP_SET_SUCCESS:
											$this->tasktimer('start', $steps[$i]);
											$this->set_task_result();
											$this->tasktimer('end', $steps[$i]);
											$this->set_tasktimer_totaltime($steps[$i]);
											break;							
			}
		}	
		return $this->task;
	}

	/////////////////
	// HELPERS // //
	/////////////////

	private function _dumpDatabase()
	{
		$dbname = $this->get_task_dbname();
		$dbuser = $this->get_task_dbuser();
		$dbpass = $this->get_task_dbpass();
		$destination = $this->get_task_destination();
		$command = "mysqldump -u ".$dbuser." -p'\''".$dbpass."'\'' ".$dbname." | gzip -c > ".$destination;		
		$this->log_task_step_command(self::STEP_DUMPDB, $command);
		system("bash -c '".$command." ; exit \${PIPESTATUS[0]}'", $return_var);
    	$this->log_task_shell_result(self::STEP_DUMPDB, $return_var);	
    	if($return_var>0)
    	{
    		$this->log_task_step_error(self::STEP_DUMPDB, true);
    		$this->log_task_step_errorcode(self::STEP_DUMPDB, self::MSGDBDUMPFAILED);
    		# delete failed file
    		exec('rm '.$destination);
    	}
	}

	private function _checkIntegrity()
	{
		$destination = $this->get_task_destination();
		$command = 'gunzip -c '.$destination.' > /dev/null && echo '.self::MSGOK.' || echo '.self::MSGFAIL;
		if(file_exists($destination))
		{
			# check
			$exec = shell_exec($command);
			# get size
			$this->task->filesize = $this->formatSizeUnits(filesize($destination));		
		}
		else
			{
				$exec = self::MSGNOEXEC;
				$this->log_task_step_error(self::STEP_CHECK_INT, self::MSGFILENOEXIST);
				$this->log_task_errorcode(self::MSGFILENOEXIST);
			}				
		$this->log_task_step_command(self::STEP_CHECK_INT, $command);
		$this->log_task_shell_result(self::STEP_CHECK_INT, $exec);	
	}


	/////////////
	// AUX // //
	/////////////

	private function get_task_dbname()
	{
		return $this->task->settings['cfg']['db_name'];
	}

	private function get_task_dbuser()
	{
		return $this->task->settings['cfg']['db_user'];
	}

	private function get_task_dbpass()
	{
		return $this->task->settings['cfg']['db_pass'];
	}

	private function get_task_filename()
	{
		return $this->task->settings['filename'];
	}

	private function get_task_destination()
	{
		return $this->FPBKP.$this->task->settings['filename'];
	}



	/**
	 * Set task command
	 * @param [type] $command [description]
	 * @param [type] $step    [description]
	 */
	private function log_task_step_command($step, $command)
	{
		$this->task->steps[$step]['command'] = $command;
	}

	/**
	 * lgo task shell result
	 * @param  [type] $step   [description]
	 * @param  [type] $result [description]
	 * @return [type]         [description]
	 */
	private function log_task_shell_result($step, $result)
	{
		if(is_array($result))
		{
			$this->task->steps[$step]['shell_result'] = implode('|', $result);
		}
		else
			{
				$this->task->steps[$step]['shell_result'] = trim($result);
			}
	}

	private function log_task_step_error($step, $error)
	{
		$this->task->steps[$step]['error'] = $error;
	}

	private function log_task_step_errorcode($step, $error)
	{
		$this->task->steps[$step]['errorcode'] = $error;
	}

	private function log_task_errorcode($errorcode)
	{
		$this->task->errorcodes.= $this->task->errorcodes=='' 
								 ? $errorcode
								 : ','.$errorcode;
	}

	private function set_task_result()
	{
		$dumpDatabase_shellResult = isset($this->task->steps[self::STEP_DUMPDB]['shell_result'])
										? $this->task->steps[self::STEP_DUMPDB]['shell_result']
										: false;	
		$integrityStep_shellResult = isset($this->task->steps[self::STEP_CHECK_INT]['shell_result'])
										? $this->task->steps[self::STEP_CHECK_INT]['shell_result']
										: false;				
		if($dumpDatabase_shellResult==0 && $integrityStep_shellResult)
		{
			if($integrityStep_shellResult == self::MSGOK)
			{
				$this->task->error = false;
			}
			else
				{
					$this->task->error = true;
					$this->log_task_errorcode(self::STEP_CHECK_INT.'_failed');
				}
		}
		else
			{
				$errorcode = $dumpDatabase_shellResult==0
							 ? self::STEP_CHECK_INT.'_not_executed'
							 : $this->task->steps[self::STEP_DUMPDB]['errorcode'];
				$this->task->error = true;
				$this->log_task_errorcode($errorcode);
			}
	}


	/**
	 * task timer
	 * @param  boolean $do   [description]
	 * @param  boolean $step [description]
	 * @return [type]        [description]
	 */
	private function tasktimer($do = false, $step = false)
	{
		if($do && $step)
		{
			switch($do)
			{
				case 'start':
								$this->task->steps[$step]['timerstart'] = microtime();
								break;
				case 'end':
								$this->task->steps[$step]['timerend'] = microtime();
								break;	
			}
		}
	}


	/**
	 * Calculate a precise time difference.
	 * @param string $start result of microtime()
	 * @param string $end result of microtime(); if NULL/FALSE/0/'' then it's now
	 * @return flat difference in seconds, calculated with minimum precision loss
	 */
	private function set_tasktimer_totaltime($step)
	{
		list($start_usec, $start_sec) = explode(" ", $this->task->steps[$step]['timerstart']);
		list($end_usec, $end_sec) = explode(" ", $this->task->steps[$step]['timerend']);
		$diff_sec = intval($end_sec) - intval($start_sec);
		$diff_usec = floatval($end_usec) - floatval($start_usec);
		$totaltime = floatval($diff_sec) + $diff_usec;
		# add to step timer
		$this->task->steps[$step]['totaltime'] = $totaltime;
		# add to global timer
		$this->task->totaltime += $totaltime;
	}	


    private function formatSizeUnits($bytes, $decimals = 2)
    {
	    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
	    $factor = floor((strlen($bytes) - 1) / 3);
	    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' '.@$size[$factor];
	}	


}