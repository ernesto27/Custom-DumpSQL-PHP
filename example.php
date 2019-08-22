<?php

require_once 'src/CustomDumpSQL.php';

$backup = new BackupDB(array('fileName' => 'mydump.sql'));

$connectionSettings = array(
    'dbHost' => '127.0.0.1',
    'dbName' => 'yourDatabaseName',
    'dbUsername' => 'yourUsername',
    'dbPassword' => 'yourPassword'
);

$backup->setDBConnection($connectionSettings);

$tables = array(
    array(
        'name' => 'yourtablename',
        'select_many' => array('id', 'name'),
        'where' => 'active = 1'
    )
);
$backup->setTablesToExport($tables);
$backup->createDump();