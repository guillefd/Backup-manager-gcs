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
- rename gcs_settings-dist.php > gcs_settings.php 
- open to edit gcs_settings.php




Enjoy

