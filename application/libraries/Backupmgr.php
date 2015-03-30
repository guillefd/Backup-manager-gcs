<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Mailgun\Mailgun;

class Backupmgr{

	private $gcs;
	private $fsbkp;
	private $dbbkp;
	private $dbutil;
	private $CFG = array();
	private $process;
	private $tasktypes;
	private $taskid;
	private $task;
	private $taskresult;
	private $taskuploadresult;
	private $currentsubtask;
	private $uploadfilestask;
	private $taskdeletefiles;
	private $logger;
    private $LOG_PATH;
    private $MG;

	const UNDEF = 'undefined';
	const PID_SETTASK = 'settask';
	const PID_RUNTASK = 'runtask';
	const PID_RUNSUBTASK = 'runsubtask';
	const PID_DELETEFILE = 'deletetask';
	const INVALID_STR = '<invalid>';
    const ERROR_STR = 'error';
    const SUCCESS_STR = 'OK';

	public function __construct()
	{
		# CI instance
        $this->CI =& get_instance();		
        # config
        $this->initConfig();
        # process
        $this->initProchandler();
        # init log
        $this->initLog();
        # set timezone
        date_default_timezone_set('America/Argentina/Buenos_Aires');
	}


	public function run($task_id = self::UNDEF)
	{
        $this->logInfo('BACKUP_RUN::START');
		$this->setTask($task_id);
		$this->runTask();
		$this->setTaskResult();
		$this->logTaskResult();
		$this->runTaskCloudUpload();
		$this->setTaskUploadResult();
		$this->logTaskUploadResult();
		$this->runTaskDeletion();
		$this->setTaskBrief();
		$this->sendEmail();
        $this->logInfo('BACKUP_RUN::END'.PHP_EOL);        		
	}


	private function runTask()
	{
		# errors
		$PID = self::PID_RUNTASK;
		$this->setProchandler($PID);
		$subProc = array();
		$i = 0;
		# cli
		$this->print_cli(__METHOD__);
		# run
		if($this->task)
		{
			foreach($this->task['subtasks'] as $subtask)
			{
				#set subtask
				$this->currentsubtask = $subtask;
				# run
				unset($result);
				$result = $this->getSubtaskResult();
				#save subtask result
				$subProc[$i] = new stdClass();
				$subProc[$i] = clone $result;
				$i++;
				# set errors
				$this->setProchandlerErrors($PID, $result);
			}
			$pdata = array(
							'subprocess'=>$subProc,
							);
			$this->task['result'] = $this->getProchandler($PID);			
		}
		else
			{
				$pdata = array(
								'errorcode'=>'task_undefined',
								'msg'=>'task is not defined',
								'error'=>true,
								'subprocess'=>$subProc,
								);
	            $this->logError($pdata['msg']);
	            $this->logDebug($pdata['errorcode'], array('taskid'=>$this->taskid));				
			}
		$this->setProchandlerValue($PID, $pdata);
	}

	public function runTaskCloudUpload()
	{
		# cli
		$this->print_cli(__METHOD__);
		# run
        $this->logInfo('task-upload::start');
		$this->setUploadFilesTask();
		$this->runCloudUpload();
		$this->loadGcs();
		$this->task['upload'] = $this->uploadfilestask;
        $this->logInfo('task-upload::end');	
	}

	//////////////////////
	// TASK HELPERS // //
	//////////////////////


	private function getSubtaskResult()
	{	
		$PID = self::PID_RUNSUBTASK;	
		$this->setProchandler($PID);			
		$result = new stdClass();
		# run
		if($this->currentsubtask &&  $this->isCurrentSubtaskValid())
		{
			switch($this->currentsubtask['type'])
			{
				case 'folder':
									# load library 
									$this->loadFilesystemBkp();
									# run
									$result = $this->fsbkp->run_folder_backup($this->currentsubtask);
									break;

				case 'database': 
				                    # load library 
									$this->loadDatabaseBkp();
									# run
									$result = $this->dbbkp->run_db_backup($this->currentsubtask);
									break;																							
			}
		}
		else
			{
                $result->errorcodes = 'subtask_invalid';
			    $result->error = true;
			    $result->msg = 'subtask array is invalid or type undifined ('.$this->currentsubtask['type'].')';
			}
		return $result;
	}

	private function isCurrentSubtaskValid()
	{
		$result = null;
		if(is_array($this->currentsubtask) && array_key_exists($this->currentsubtask['type'], $this->subtasktypes))
		{
			$result = true;
		}		
		return $result;
	}

	private function setUploadFilesTask()
	{	
		$this->uploadfilestask = [];
		$taskresult = $this->getProchandler(self::PID_RUNTASK);
		foreach($taskresult->subprocess as $sp)
		{
			if($sp->error==false && $sp->settings['upload']==true)
			{
				$file = [];
				$file['target'] = $this->getBkpFolderPath().$sp->settings['filename'];
				$file['bucketid'] = $sp->settings['bucket_id'];
				$file['destination'] = $sp->settings['cloud_folder']
									   . $this->getCLoudFilenamePrefix($sp->settings['cloud_filename_prefix'])
									   . $sp->settings['filename'];
				$file['delete'] = $sp->settings['delete_after_upload'];
				# set
				$this->uploadfilestask[] = $file;
			}
		}
	}

    private function setTaskResult()
    {
		# cli
		$this->print_cli(__METHOD__);
		# run
	    if(isset($this->task['result']))
	    {
	        $subtasks = [];
	        $totaltime = 0;
	        foreach($this->task['result']->subprocess as $subp)
	        {
	            $totaltime += $subp->totaltime;
	            $sp = [];
	            $sp['type'] = $subp->settings['type'];
	            $sp['filename'] = $subp->settings['filename'];
	            if($subp->error==true)
	            {
	                $sp['result'] = self::ERROR_STR;
	                $sp['errorcodes'] = $subp->errorcodes;
	                $sp['steps'] = $subp->steps;
	            }
	            else
	                {
	                    $sp['result'] = self::SUCCESS_STR;
	                    $sp['filesize'] = $subp->filesize;
	                }
	            $subtasks[] = $sp;
	        }   
	        $taskresult =   [
	                            'task_result'=>$this->task['result']->error==true ? self::ERROR_STR : self::SUCCESS_STR,
	                            'totaltime'=>$totaltime,
	                            'errorcodes'=>$this->task['result']->errorcodes,
	                            'subtasks'=>$subtasks,
	                        ];
	        $this->taskresult = $taskresult;      
	    }                        
    }

    private function setTaskUploadResult()
    {
		# cli
		$this->print_cli(__METHOD__);
		# run
    	if(isset($this->task['upload']))
    	{
	        $this->taskuploadresult = [];
	        foreach($this->task['upload'] as $upload)
	        {
	            $result = [];
	            $result['target'] = $upload['target'];
	            $result['bucket'] = $upload['bucketid'];
	            $result['destination'] = $upload['destination'];
	            $result['totaltime'] = $upload['upload_result']->totaltime;
	            $result['httpcode'] = $upload['upload_result']->httpcode;
	            if($upload['upload_result']->error==true)
	            {
	                $result['result'] = self::ERROR_STR;
	                $result['errorcode'] = $upload['upload_result']->exception;
	            }
	            else
	                {
	                    $result['result'] = self::SUCCESS_STR;
	                    $result['id'] = $upload['upload_result']->status->id;
	                    $result['kind'] = $upload['upload_result']->status->kind;
	                    $result['name'] = $upload['upload_result']->status->name;
	                    $result['updated'] = $upload['upload_result']->status->updated;
	                }
	            $this->taskuploadresult[] = $result;    
	            # update taskresult[totaltime]
	            $this->taskresult['totaltime']+= $result['totaltime'];
	        }
	    }
    }

    /**
     * delete backup files after beeing uploaded
     * @return void
     */
    private function runTaskDeletion()
    {
		# cli
		$this->print_cli(__METHOD__);
		# run
		$this->taskdeletefiles = [];
    	if(isset($this->task['upload']))
    	{
    		foreach($this->task['upload'] as $fileArr)
    		{
    			if($fileArr['upload_result']->error===false && $fileArr['delete']===true)
    			{
    				$taskdelete = [];
    				$taskdelete['target'] = $fileArr['target'];
    				$taskdelete['command'] = "rm ".$fileArr['target'];
    				if(file_exists($fileArr['target']))
    				{
						system("bash -c '".$taskdelete['command']." ; exit \${PIPESTATUS[0]}'", $return_var);
				    	if($return_var>0)
				    	{
				    		$taskdelete['error'] = true;
				    		$taskdelete['errorcode'] = ' PIPESTATUS[] '.$return_var;
				    	}   
				    	else
					    	{
					    		$taskdelete['error'] = false;
				    			$taskdelete['errorcode'] = '';
					    	}
				    }
				    else
					    {
					    	$taskdelete['error'] = true;
					    	$taskdelete['errorcode'] = 'file_not_exist';
					    } 				
					$this->taskdeletefiles[] = $taskdelete;
    			}
    		}
    	} 
    	$this->logTaskDeleteResult();   	
    }


	//////////////////////
	// GCS UPLOADER  // //
	//////////////////////

	private function runCloudUpload()
	{
		# cli
		$this->print_cli(__METHOD__);
		# load gcs library 
		$this->loadGcs();
		# upload
		foreach($this->uploadfilestask as &$upload)
		{
			$upload['upload_result'] = $this->gcs->media_file_upload(
																	  $upload['bucketid'], 
																	  $upload['target'], 
																	  $upload['destination']
																	);			
		}
	}


	///////////////////////////
	// GETTERS - SETTERS  // //
	///////////////////////////

	public function getTasks()
	{
		return $this->CFG['bkp']['tasks'];
	}

	public function setTask($task_id = self::UNDEF)
	{
		# cli
		$this->print_cli(__METHOD__);
		# errors
		$PID = self::PID_SETTASK;
		$this->setProchandler($PID);
		# set
		if(is_string($task_id) && $task_id!=self::UNDEF)
		{
			$this->taskid = $task_id;
			$tasks = $this->getTasks();		
			if(isset($tasks[$task_id]))
			{
				$this->task = $tasks[$task_id];
			}	
			else
				{
					$errordata = array(
									'errorcode'=>'taskid_undefined',
									'msg'=>'task id is not defined',
									'error'=>true,
									);
				}
		}
		else
			{
				$this->taskid = false;
				$errordata = array(
								'errorcode'=>'taskid invalid',
								'msg'=>'task id is not valid',
								'error'=>true,
								);
			} 
		if(isset($errordata))
		{	
			$taskid = isset($this->taskid) ? $this->taskid : self::INVALID_STR;
	        $this->logError($errordata['msg']);
	        $this->logDebug($errordata['errorcode'], array('taskid'=>$taskid));
			$this->setProchandlerValue($PID, $errordata);
		}
	}

	public function getTask()
	{
		return $this->task;
	}


	/////////////
	// AUX // //
	/////////////

	private function initConfig()
	{
        $this->CI->config->load('backup_settings', true);
        $this->CFG['bkp'] = $this->CI->config->item('backup_settings');
        $this->subtasktypes = $this->CFG['bkp']['subtask_types'];
        $this->mailcfg = $this->CFG['bkp']['mailgun'];
	}

	private function loadFilesystemBkp()
	{
		$params = array('FPBKP'=>$this->getBkpFolderPath());
		$this->CI->load->library('Backupmgr/Filesystem_bkp', $params);
		$this->fsbkp =& $this->CI->filesystem_bkp;
	}

	private function loadDatabaseBkp()
	{
		$params = array('FPBKP'=>$this->getBkpFolderPath());
		$this->CI->load->library('Backupmgr/Database_bkp', $params);
		$this->dbbkp =& $this->CI->database_bkp;
	}	

	private function loadGcs()
	{
		$this->CI->load->library('googlecloudstorage');
		$this->gcs =& $this->CI->googlecloudstorage;
		$this->gcs->set_client();
	}

	public function getBkpFolderPath()
	{
		return $this->CFG['bkp']['backup_folder_path'];
	}

	public function getLogFolderPath()
	{
		return $this->CFG['bkp']['log_folder_path'];
	}

	private function getCloudFilenamePrefix($prefix_string = '')
	{
		$prefix = '';
		$vars = explode('-', $prefix_string);
		foreach($vars as $var)
		{
			$prefix.= mdate($var).'-';
		}
		return $prefix;
	}

	//////////////////////////////////////////////////////////////////////////////////////////
	// PROCESS HANDLER ------------------------------------------------------------------// //
	//////////////////////////////////////////////////////////////////////////////////////////

	private function initProchandler()
	{
		$this->process = array();
	}

	private function setProchandler($pid = self::UNDEF)
	{
		if($pid!=self::UNDEF && is_string($pid))
		{
			$result = new stdClass();
			$result->error = false;
			$result->errorcodes = '';
			$result->data = '';
			$result->msg = '';
			$result->subprocess = array();
			$this->process[$pid] = $result;
		}
	}

	/**
	 * Set error handler process value
	 * @param [type]  $pid
	 * @param string  $code        
	 * @param string  $msg         
	 * @param boolean $error       
	 */
	private function setProchandlerValue($pid = self::UNDEF, $data = array())
	{
		if($this->_prochandlerValidId($pid))
		{
			foreach($data as $var=>$value)
			{
				$this->process[$pid]->{$var} = $value;
			}
		}
	}

	private function getProchandler($pid = self::UNDEF)
	{
		if($this->_prochandlerValidId($pid))
		{
			return $this->process[$pid];
		}
		return null;
	}

	private function getProchandler_success($pid = self::UNDEF)
	{
		if($this->_prochandlerValidId($pid))
		{
			return $this->process[$pid]->error==false ? true : false;
		}
		return null;
	}

	private function _prochandlerValidId($pid = 0)
	{
		return $pid!=self::UNDEF && is_string($pid) && isset($this->process[$pid]);
	}

	private function setProchandlerErrors($pid = 0, $result)
	{
		if(isset($result->error) && $result->error==true)
		{
			$this->process[$pid]->error = $this->process[$pid]->error==false 
					                      ? true
					                      : $this->process[$pid]->error;
			$this->process[$pid]->errorcodes = $this->process[$pid]->errorcodes==''
											   ? $result->errorcodes
											   : ','.$result->errorcodes;
		}
	}


    //////////////////
    // LOG CLASS // //
    //////////////////

    private function initLog()
    {
    	# path to log folder
        $this->LOG_PATH = $this->getLogFolderPath();
        $prefix = $this->CI->input->is_cli_request() ? 'cli_log_' : 'http_log_';
       	$this->logger = new Katzgrau\KLogger\Logger($this->LOG_PATH, Psr\Log\LogLevel::DEBUG, array(
       		'prefix'=>$prefix
       	));
    }

    private function logError($string = '*')
    {
        $this->logger->error($string);
    }

    private function logWarning($string = '*')
    {
        $this->logger->warning($string);
    }    

    private function logInfo($string = '*')
    {
        $this->logger->info($string);
    }

    private function logDebug($string = '*', $debug_data)
    {
    	$debug_data = !isset($debug_data) ? array('{no debug data}') : $debug_data;
        $this->logger->debug($string, $debug_data);
    }

    private function logTaskResult()
    {
        $this->logDebug('::task-result::DEBUG', $this->taskresult);
    }

    private function logTaskUploadResult()
    {
        $this->logDebug('task-upload-result::DEBUG', $this->taskuploadresult);
    }

    private function logTaskDeleteResult()
    {
        $this->logDebug('task-delete-files::DEBUG', $this->taskdeletefiles);
    }

    ////////////////
    // LOG CLI // //
    ////////////////

    /**
     * Echo only in CLI
     * @param  [type] $str [description]
     * @return [type]      [description]
     */
    private function print_cli($str = '')
    {
    	if($this->CI->input->is_cli_request())
    	{
    		echo $str . PHP_EOL;
    	}
    }


    ////////////////////////
    // EMAIL - Mailgun // //
    ////////////////////////

    private function load_mailgun()
    {
        $this->MG = new Mailgun($this->mailcfg['MGapikey']);
    }

    private function sendEmail()
    {     
        if(isset($this->task['result']) && $this->task['send_email_result'])
        {
            //init mailgun
            $this->load_mailgun();    
            // data
            $from      = $this->mailcfg['noreply_email'];
            $from_name = $this->mailcfg['co_from_name'];
            $to        = $this->task['send_email_to'];
            $subject   = 'Backup ('.$this->taskresult['task_result'].') ['.$this->taskid.']'; 
            $html      = '--- TEST ---';                 
            //tags
            $tag1 = 'backup';
            $tag2 = $this->taskid;
            //apiData
            $apiData = array(
                            'from'=> $from_name.'<'.$from.'>', 
                            'to'=> $to, 
                            'subject'=> $subject, 
                            'text'=> '',
                            'html'=> $this->task['brief'],
                            'o:tag'=>array($tag1, $tag2),
                            );                                              
            // Send via MAILGUN
            $apiResponse = $this->MG->sendMessage($this->mailcfg['MGapidomain'], $apiData);
            // save API response
            $apiSendingData = array(
                                    'api_msg_code'=> isset($apiResponse->http_response_code) ? $apiResponse->http_response_code : '', 
                                    'api_msg_id'=> isset($apiResponse->http_response_body->id) ? $apiResponse->http_response_body->id : 0,
                                    'api_msg_txt'=> isset($apiResponse->http_response_body->message) ? $apiResponse->http_response_body->message : '', 
                                    );               
            //merge both arrays
            $apiSendingData = array_merge($apiData, $apiSendingData);
            //implode tags array
            $apiSendingData['o:tag'] = implode(',',$apiSendingData['o:tag']); 
                 
            //verify results for message return
            if($apiSendingData['api_msg_code']!=200)
            {
                $this->logDebug('email_sent::ERROR', $apiSendingData);
            }
            else
	            {
	            	$this->logDebug('email_sent::OK '.$this->task['send_email_to'], $apiSendingData);
	            }
        }
    }

    private function setTaskBrief()
    {
    	if(isset($this->task['result']))
    	{
	    	$msg = '<h4>Backup Mgr</h4>';
	    	$msg .= '<p><b>Task id: '.$this->taskid.' | '.$this->task['description'].'</b></p>';
	    	$result = $this->task['result']->error==true ? 'Errors' : 'OK';	
	    	$msg .= 'Task result: '.$result.'<br>';
	    	$msg .= 'Task errors: '. $this->task['result']->errorcodes.'<br>';
	    	$msg .= 'Task totaltime: '.round($this->taskresult['totaltime'],2).' secs<br>';
	    	for($i=0;$i<count($this->task['subtasks']);$i++)
	    	{
	    		$st = $this->task['subtasks'][$i];
	    		$msg .= "<p>"
	    		     . "<b># subtask(".$i."): [".$st['type']."] ".$st['description']."</b><br>"
	    			 . "filename: ".$st['filename']."<br>";
	    		switch ($st['type']) 
	    		{
	    			case 'database':
					    				$msg.= "cfg:db ".$st['cfg']['db_name']."<br>";
					    				break;

	    			case 'folder':
					    				$msg.= "cfg:path ".$st['cfg']['path']."<br>";
					    				$msg.= "cfg:exclude ".implode(", ", $st['cfg']['exclude'])."<br>";
					    				break;    			
	    			default:
					    				$msg.= "cfg:(task type undefined)<br>";
					    				break;
	    		}
	    		# result
	    		if(isset($this->task['result']->subprocess[$i]))
	    		{
	    			$stresult = $this->task['result']->subprocess[$i];
	    			$error = $stresult->error==true ? 'error' : 'OK';
	    			$msg .= "# FILE<br>";
	    			$msg .= "file generated: ".$error.'<br>';
	    			$msg .= "errorcodes: ".$stresult->errorcodes.'<br>';
	    			$msg .= "totaltime: ".$stresult->totaltime.'<br>';
	    			$msg .= "filesize: ".$stresult->filesize.'<br>';
	    		}
	    		else
		    		{
		    			$msg .= 'NO RESULT DATA<br>';
		    		}
	    		# upload
	    		if(isset($this->taskuploadresult[$i]))
	    		{
	    			$msg .= "# UPLOAD<br>";
	    			$msg .= "file uploaded: ".$this->taskuploadresult[$i]['result'].'<br>';
	    			$msg .= "httpcode: ".$this->taskuploadresult[$i]['httpcode'].'<br>';
	    			$msg .= "totaltime: ".$this->taskuploadresult[$i]['totaltime'].'<br>';
	    			$msg .= "destination: ".$this->taskuploadresult[$i]['name'].'<br>';
	    			$msg .= "bucket: ".$this->taskuploadresult[$i]['bucket'].'<br>';
	    		}
	    		else
		    		{
		    			$msg .= 'NO UPLOAD DATA<br>';
		    		}
	    		# delete
	    		if(isset($this->taskdeletefiles[$i]))
	    		{
	    			$msg .= "# DELETE<br>";
	    			$error = $this->taskdeletefiles[$i]['error']==true ? 'error' : 'OK';
	    			$msg .= "local file deleted: ".$error.'<br>';
	    			$msg .= "errorcode: ".$this->taskdeletefiles[$i]['errorcode'].'<br>';
	    			$msg .= "target: ".$this->taskdeletefiles[$i]['target'].'<br>';
	    		}
	    		else
		    		{
		    			$msg .= 'NO DELETION DATA<br>';
		    		}
	    		$msg .= "</p>";
	    	}
	    	$this->task['brief'] = $msg;
	    }
	    else
		    {
		    	$this->task['brief'] = '';
		    }
    }

}