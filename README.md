# AWS Backup for Scheduler

Task to be used with [Scheduler](https://modmore.com/extras/scheduler/) that backs up site information to an AWS S3 bucket.

Tested with files up to 1GB. 

## Configuration

Handled via system settings.

- scheduler_awsbackup.s3_key
- scheduler_awsbackup.s3_secret
- scheduler_awsbackup.s3_backup_region
- scheduler_awsbackup.s3_backup_bucket
- scheduler_awsbackup.rotate_sync_path

## Tasks

The following tasks are available:

### Rotating File Sync

Takes a directory on your server, and uploads the files in it Amazon S3. 

Repeats itself every 24 hours.

Rotates files, keeping copies for daily and weekly backups, keeping one daily copy for a week, and weekly copies for a year.
 
For example, given a file named `database.sql` and `files.zip`, the following structure will be created in the bucket:

- site_host.name
-- Mon
--- database.sql
--- files.zip
-- Tue
--- database.sql
--- files.zip
-- Wed
--- database.sql
--- files.zip
-- .. Thu, Fri, Sat,Sun
-- week-11
--- database.sql
--- files.zip
-- week-12
--- database.sql
--- files.zip

You can define one path to sync with the `scheduler_awsbackup.rotate_sync_path` system setting. That could be `/abs/path/to/core/export/` or a server level backup directory, for example. If you want to upload several paths, consider using a task that moves them into a single location.
