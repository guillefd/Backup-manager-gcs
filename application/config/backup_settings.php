<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['backup_folder_path'] = '/var/www/home/appco/backup';
$config['log_folder_path'] = '/var/www/home/appco/backup/logs';

$config['mailgun'] = array(
							'MGapikey'=>'key-49n8i0y0t69m4u3g20mkws3irkkatmg1',
							'MGapidomain'=>'mg-bckp.eytech.com.ar',
							'noreply_email'=>'notifications@eytech.com.ar',				
							'co_from_name'=>'Backup Manager',
							'co_team'=>'E&T',
							'co_fullname'=>'Eventos & Tecnologia',
							'co_link'=>'http://www.eytech.com.ar',
							'co_directphone'=>'+54 (11) 5032 1071',							
							'co_directcontact_email'=>'info@eytech.com.ar',
							'co_contact_email'=>'publicaciones@eytech.com.ar',
							'co_adsystem_email'=>'backup@eytech.com.ar',
							'co_logolink'=>'http://www.eytech.com.ar', 
							'co_logoalt'=>'Eventos & Tecnologia', 
							'co_logowidth'=>'143px', 
							'co_logoheight'=>'59px', 
							'co_logosrc'=>'http://cdn.assets.eytech.com.ar/logos/logo_eyt.png',	
							);

$config['subtask_types'] = array(
 								'database'=>array(

 												),
 								'folder'=>array(

 												),
							 );
$config['tasks'] = array(										
						'app-beyt-full'=>array(
												'description'=>'Full Backup BuscadorEyT WebApp',
												'send_email_result'=>true,
												'send_email_to'=>'gfdova@eytech.com.ar',
												'send_email_only_on_fail'=>false,
												'subtasks'=>array(
																array(
																		'type'=>'database',
																		'description'=>'BuscadorEyT DB',
																		'cfg'=>array(
																					'db_name'=>'eytadsite_eytechco',
																					'db_user'=>'eytechco',
																					'db_pass'=>'gsfd#&%@0762',
																					),
																		'filename'=>'eytadsite_eytechco.sql.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_buscadoreyt',
																		'cloud_folder'=>'db/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																array(
																		'type'=>'folder',
																		'description'=>'/assets folder',
																		'cfg'=>array(
																					'fullpath'=>'/var/www/home/eytechco/public_html/assets',
																					'tar_options'=>'-cpvzf',
																					'exclude'=>array(
																									'cache'
																									),
																					),
																		'filename'=>'eytb_assets.tar.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_buscadoreyt',
																		'cloud_folder'=>'webroot/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																array(
																		'type'=>'folder',
																		'description'=>'/uploads folder',
																		'cfg'=>array(
																					'fullpath'=>'/var/www/home/eytechco/public_html/uploads', // tar the webroot
																					'tar_options'=>'-cpvzf',
																					'exclude'=>array(
																									),
																					),
																		'filename'=>'eytb_uploads.tar.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_buscadoreyt',
																		'cloud_folder'=>'webroot/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																array(
																		'type'=>'folder',
																		'description'=>'webroot (exclude /assets /uploads)',
																		'cfg'=>array(
																					'fullpath'=>'/var/www/home/eytechco/public_html', // tar the webroot
																					'tar_options'=>'-cpvzf',
																					'exclude'=>array(
																									'assets',
																									'uploads'
																									),
																					),
																		'filename'=>'eytb_webroot.tar.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_buscadoreyt',
																		'cloud_folder'=>'webroot/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																),
											),	
							'app-beyt-db'=>array(
												'description'=>'Backup BuscadorEyT :: DB',
												'send_email_result'=>true,
												'send_email_to'=>'guillefd@gmail.com',
												'send_email_only_on_fail'=>false,
												'subtasks'=>array(
																array(
																		'type'=>'database',
																		'description'=>'Backup app database',
																		'cfg'=>array(
																					'db_name'=>'eytadsite_eytechco',
																					'db_user'=>'eytechco',
																					'db_pass'=>'gsfd#&%@0762',
																					),
																		'filename'=>'eytadsite_eytechco.sql.gz',
																		'upload'=>true,
																		'delete_after_upload'=>true,
																		'bucket_id'=>'backup_buscadoreyt',
																		'cloud_folder'=>'db/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																),
											),

///////////////////////////////////////////////
// TEST //						             //
///////////////////////////////////////////////

							'TESTDEV'=>array(
												'description'=>'Complete application backup',
												'send_email_result'=>true,
												'send_email_to'=>'guillefd@gmail.com',
												'send_email_only_on_fail'=>false,
												'subtasks'=>array(
																array(
																		'type'=>'folder',
																		'description'=>'Backup app root',
																		'cfg'=>array(
																					'fullpath'=>'/var/www/html', // tar the webroot
																					'tar_options'=>'-cpvzf',
																					'exclude'=>array(
																									),
																					),
																		'filename'=>'webroot.tar.gz',
																		'upload'=>false,
																		'delete_after_upload'=>false,
																		'bucket_id'=>'rafabackups',
																		'cloud_folder'=>'bahcostore/',
																		'cloud_filename_prefix'=>'%d-%H',
																	),
																),
											)	
						 );
