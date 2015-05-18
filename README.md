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
- Go to "APIs & Auth > Credentials
	- Create a Client ID (application type: Service account, key type: P12 key)
	- save p12 key file (you´ll use it later)  
	- copy Client ID
	- copy Email address  

3- Edit gcs_settings.php	
- rename gcs_settings-dist.php > gcs_settings.php 
- open it for edit
- Fill vars values:
	- gcs_app_name: choose app name
	- gcs_client_id: paste Client ID (from step 2)
	- gcs_service_account_name: paste email address (from step 2)
	




Enjoy

