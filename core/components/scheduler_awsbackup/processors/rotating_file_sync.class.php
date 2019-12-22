<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Aws\S3\S3Client;
use League\Flysystem\AdapterInterface;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;

class SchedulerAwsBackupRotatingFileSync extends modProcessor
{
    /** @var sTask */
    private $task;
    /** @var sTaskRun */
    private $run;

    public function initialize()
    {
        $this->task = $this->getProperty('task');
        $this->run = $this->getProperty('run');
        return parent::initialize();
    }

    /**
     * Run the processor and return the result. Override this in your derivative class to provide custom functionality.
     * Used here for pre-2.2-style processors.
     *
     * @return mixed
     */
    public function process()
    {
        $tomorrow = strtotime('+1 day');
        $this->task->schedule($tomorrow - 60); // -60 si it executes at the start of the minute if it started later

        $messages = [];
        $copied = 0;
        try {
            $filesystem = $this->getFilesystem();
            $files = $this->getFiles();
            if (empty($files)) {
                throw new \RuntimeException('No files found to backup.');
            }

            $host = $this->modx->getOption('http_host');
            foreach ($files as $targetFile) {
                $stream = fopen($targetFile, 'rb+');
                $filename = basename($targetFile);

                $messages[] = $this->_ts() . 'Backing up ' . $filename;

                // Daily backups
                $day = date('D');
                $daily = "{$host}/{$day}/{$filename}";
                if ($filesystem->has($daily)) {
                    $filesystem->updateStream($daily, $stream, ['visibility' => AdapterInterface::VISIBILITY_PRIVATE]);
                } else {
                    $filesystem->writeStream($daily, $stream, ['visibility' => AdapterInterface::VISIBILITY_PRIVATE]);
                }
                fclose($stream);

                $messages[] = $this->_ts() . '- Uploaded ' . $daily;

                // Copy to weekly - should save us some upload time
                $weekNr = date('W');
                $weekly = "{$host}/week-{$weekNr}/{$filename}";
                if ($filesystem->has($weekly)) {
                    $filesystem->delete($weekly);
                }
                $filesystem->copy($daily, $weekly);

                $messages[] = $this->_ts() . '- Uploaded ' . $weekly;
                $copied++;
            }

            $message = $copied . ' files backed up to Amazon S3.<br><br>' . implode('<br>', $messages);
            return $this->success($message);
        } catch (\Exception $e) {
            $this->run->addError(get_class($e), array(
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ));

            $message = 'Received ' . get_class($e) . ' after backing up ' . $copied . ' files to Amazon S3.<br><br>' . implode('<br>', $messages);
            return $this->failure($message);
        }
    }

    private function getFilesystem(): FilesystemInterface
    {
        $client = new S3Client([
            'credentials' => [
                'key' => $this->modx->getOption('scheduler_awsbackup.s3_key'),
                'secret' => $this->modx->getOption('scheduler_awsbackup.s3_secret'),
            ],
            'region' => $this->modx->getOption('scheduler_awsbackup.s3_backup_region'),
            'version' => 'latest',
        ]);

        $bucket = $this->modx->getOption('scheduler_awsbackup.s3_backup_bucket');
        $adapter = new AwsS3Adapter($client, $bucket);
        return new Filesystem($adapter);
    }

    private function getFiles()
    {
        $path = $this->modx->getOption('scheduler_awsbackup.rotate_sync_path');
        $files = array_diff(scandir($path), ['..', '.']);
        foreach ($files as &$file) {
            $file = $path . $file;
        }

        return $files;
    }

    private function _ts(): string
    {
        return '[' . date('H:i:s') . '] ';
    }
}

return SchedulerAwsBackupRotatingFileSync::class;