<?php

defined('BASEPATH') OR exit('No direct script access allowed');

$config['backup_targets'] = array(
									array(
											'type'=>'database',
											'cfg'=>array(
														'format'=>'gzip',
														),
											'filename'=>'db/%Y%-%m%-%d%-%h%_buscadoreyt.sql.gz',
										),	
								 );
