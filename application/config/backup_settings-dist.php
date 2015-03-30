<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['backup_folder_path'] = '/var/www/home/'; // full path, end with trailing slash
$config['log_folder_path'] = '/var/www/home/backup/logs'; // full path

$config['mailgun'] = array(
							'MGapikey'=>'key-#####',
							'MGapidomain'=>'mg.damain.org',
							'noreply_email'=>'notifications@domain.org',				
							'co_from_name'=>'Backup Manager',
							'co_team'=>'Name',
							'co_fullname'=>'Long name',
							'co_link'=>'http://www.domain.org',
							'co_directphone'=>'+1 (555) 5555 5555',							
							'co_directcontact_email'=>'info@domain.org',
							'co_contact_email'=>'notifications@domain.org',
							'co_adsystem_email'=>'notifications@domain.org',	
							);

$config['subtask_types'] = array(
 								'database'=>array(

 												),
 								'folder'=>array(

 												),
							 );
$config['tasks'] = array(										
						'webapp-full'=>array(
												'description'=>'Full Backup Webapp',
												'send_email_result'=>true,
												'send_email_to'=>'your@email.com',
												'send_email_only_on_fail'=>false,
												'subtasks'=>array(
																array(
																		'type'=>'database',
																		'description'=>'webapp DB',
																		'cfg'=>array(
																					'db_name'=>'dbname',
																					'db_user'=>'dbuser',
																					'db_pass'=>'dbpass',
																					),
																		'filename'=>'dbname.sql.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_webapp',
																		'cloud_folder'=>'db/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																array(
																		'type'=>'folder',
																		'description'=>'webapp webroot',
																		'cfg'=>array(
																					'fullpath'=>'/var/www/home', // tar the webroot
																					'tar_options'=>'-cpvzf',
																					'exclude'=>array(
																									),
																					),
																		'filename'=>'webroot.tar.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_webapp',
																		'cloud_folder'=>'webapp/',
																		'cloud_filename_prefix'=>'%d-%H',
																	)
																),
											),		
						 );
