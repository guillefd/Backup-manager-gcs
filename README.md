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

Copy files
- copy files behind webroot (not accesible via web)

Google Cloud Settings
- open your Google Cloud Console
  (if don't have one, sign in https://cloud.google.com/storage/docs/signup)
- Go to "APIs & Auth > Credentials
	- Create a Client ID (application type: Service account, key type: P12 key)
	- save p12 key file (you´ll use it later)  
	- copy Client ID
	- copy Email address  

Edit gcs_settings.php	
- rename gcs_settings-dist.php > gcs_settings.php 
- open file gcs_settings.php
- complete:
	- gcs_app_name: Give app a name
	- gcs_client_id




Enjoy

