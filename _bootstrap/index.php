<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

echo "<pre>";
/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));


/* Namespace */
if (!createObject('modNamespace',array(
    'name' => 'scheduler_awsbackup',
    'path' => $componentPath.'/core/components/scheduler_awsbackup/',
    'assets_path' => $componentPath.'/assets/components/scheduler_awsbackup/',
),'name', false)) {
    echo "Error creating namespace scheduler_awsbackup.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.core_path',
    'value' => $componentPath.'/core/components/scheduler_awsbackup/',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating scheduler_awsbackup_awsbackup.core_path setting.\n";
}

$schedulerPath = $modx->getOption('scheduler.core_path', null, MODX_CORE_PATH . 'components/scheduler/');
if ($scheduler = $modx->getService('scheduler','Scheduler', $schedulerPath . 'model/scheduler/')) {
    $task = $modx->newObject('sTask');
    $task->fromArray([
        'class_key' => 'sProcessorTask',
        'content' => 'rotating_file_sync',
        'namespace' => 'scheduler_awsbackup',
        'reference' => 'rotating_file_sync',
        'description' => 'Synchronises configured files and rotates them keeping a reasonable number of copies on AWS.'
    ]);
    $task->save();
}
else {
    echo "Scheduler not found.\n";
}


if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.s3_key',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Amazon S3',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating setting.\n";
}

if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.s3_secret',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Amazon S3',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating setting.\n";
}

if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.s3_backup_region',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Amazon S3',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating setting.\n";
}
if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.s3_backup_bucket',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Amazon S3',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating setting.\n";
}

if (!createObject('modSystemSetting', array(
    'key' => 'scheduler_awsbackup.rotate_sync_path',
    'value' => '',
    'xtype' => 'textfield',
    'namespace' => 'scheduler_awsbackup',
    'area' => 'Amazon S3',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating setting.\n";
}

echo "Done.";


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = array(), $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        $object = $modx->getObject($className, array($primaryField => $data[$primaryField]));
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                echo "Skipping {$className} {$data[$primaryField]}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
