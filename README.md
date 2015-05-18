# Backup-manager-gcs
Automatic Backup of folder and MySQL databases to Google Cloud Storage

Send  email  notification via  Mailgun API

###Workflow

- folder is zipped in a TAR file
- MySQL database is dumped and zipped in a TAR file
- TAR file is prefixed with date and hour
- TAR file is uploaded to Google Cloud Storage Bucket
- TAR file is deleted
- When finished email notification is sent via mailgun


###Install

1- Extract
- Extract folder behind webroot (not accesible via web)

2- Google Cloud Settings
- open your Google Cloud Console
  (Don't have one? sign in https://cloud.google.com/storage/docs/signup)
  (as cheep as 1 cent per GB/month for bucket type nearline)
- Go to "APIs & Auth > Credentials
	- Create a Client ID (application type: Service account, key type: P12 key)
	- save p12 key file (you´ll use it later)  
	- copy Client ID
	- copy Email address  

3- Upload P12 file
- create folder assets/gcs in app_root_folder
- upload the p12 file of step 2 in 'gcs' folder
- the path of the P12 file should look like this: app_root_folder/assets/gcs/your_P12file_name.p12

4- Edit gcs_settings.php	
- go to folder app_root_folder/application/config/
- rename gcs_settings-dist.php > gcs_settings.php 
- open it for edit
- Fill vars values:
	- gcs_app_name: choose app name
	- gcs_client_id: paste Client ID (from step 2)
	- gcs_service_account_name: paste email address (from step 2)
	- gcs_key_file: paste your P12 file name (ie: myappname-579d43254tef.p12)

5- Create backup folder
- Create a folder in your web root path, and name it 'backup'
(ie: /www/home/youraccount/backup)

6- Create logs folder
- Create a folder in your web root path, and name it 'logs'
(ie: /www/home/youraccount/logs)

7- Mailgun
- Get Maigun api key and domain.
- (don´t have one? open it http://mailgun.com)

8- Edit backup_settings.php
- go to folder app_root_folder/application/config/
- rename backup_settings-dist.php > backup_settings.php 
- open it for edit
- Fill vars values:
	- backup_folder_path: full path to backup folder (step 5)
	- log_folder_path: full path to logs folder (step 6)
	- MGapikey:  get apikey from mailgun (step 7)
	- MGapidomain: paste Mailgun domain you want to use.
	- Personal data: noreply_email, co_from_name, ... ,co_adsystem_email: set the values you prefer.

9- Set Task
- A task defines what databases or folders you want to TAR and UPLOAD to GCS.
In the file you will find an example o task with 2 subtasks: database and folder.
Also you may set more than one task, just create another array.
Complete as needed.

10- RUN TASK
- Open the terminal
- cd to app root path
- run command: php index.php backup cli webapp-full 
	- Please wait until it echoes CRON END
- Verify
	- Verify Google Cloud Storage - Storage browser, and look for uploaded tar files.
	- Verify if you recieved email with task detail.
-  Set CRON JOB to automate task.	

Enjoy

