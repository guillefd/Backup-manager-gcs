<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Filesystem_bkp{

	const MSGFILENOEXIST = 'FILENOTFOUND';
	const MSGFAIL = 'FAIL';
	const MSGOK = 'OK';
	const MSGNOEXEC = 'NOEXEC';

	const STEP_COMPRESS = 'compressFolder';
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

	public function run_folder_backup($taskcfg)
	{
		$this->task->settings = $taskcfg;
		$steps = array(self::STEP_COMPRESS, self::STEP_CHECK_INT, self::STEP_SET_SUCCESS);
		for($i=0;$i<count($steps);$i++)
		{
			switch($steps[$i])
			{
				case self::STEP_COMPRESS:
											$this->tasktimer('start', $steps[$i]);
											$this->_compressFolder();
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

	private function _compressFolder()
	{
		$target = $this->get_task_target();
		$destination = $this->get_task_destination();
		$tar_options = $this->get_task_tarOptions();
		$tar_exclude = $this->get_task_tarExclude();
		$command = 'cd '.$target.' && tar '.$tar_options.' '.$destination.' *'.$tar_exclude;		
		$this->log_task_step_command(self::STEP_COMPRESS, $command);
		$exec = shell_exec($command);
		$this->log_task_shell_result(self::STEP_COMPRESS, $exec);	
	}

	private function _checkIntegrity()
	{
		$destination = $this->get_task_destination();
		$command = 'gunzip -c '.$destination.' | tar t > /dev/null && echo '.self::MSGOK.' || echo '.self::MSGFAIL;
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

	private function get_task_target()
	{
		return $this->task->settings['cfg']['fullpath'];
	}

	private function get_task_destination()
	{
		return $this->FPBKP.$this->task->settings['filename'];
	}

	private function get_task_tarOptions()
	{
		return $this->task->settings['cfg']['tar_options'];
	}

	private function get_task_tarExclude()
	{
		$opt = '';
		$excludeArr = $this->task->settings['cfg']['exclude'];
		foreach($excludeArr as $exclude)
		{
			$opt.= ' --exclude='.$exclude;
		}
		return $opt;
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
		$this->task->steps[$step]['shell_result'] = trim($result);
	}

	private function log_task_step_error($step, $error)
	{
		$this->task->steps[$step]['error'] = $error;
	}

	private function log_task_errorcode($errorcode)
	{
		$this->task->errorcodes.= $this->task->errorcodes=='' 
								 ? $errorcode
								 : ','.$errorcode;
	}

	private function set_task_result()
	{
		$integrityStep_shellResult = isset($this->task->steps[self::STEP_CHECK_INT]['shell_result'])
										? $this->task->steps[self::STEP_CHECK_INT]['shell_result']
										: false;
		if($integrityStep_shellResult)
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
				$this->task->error = true;
				$this->log_task_errorcode(self::STEP_CHECK_INT.'_not_executed');
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