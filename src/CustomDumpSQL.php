<?php
require_once 'strings.php';

class BackupDB
{
    /**
     * Obtains the instance of connection to DB
     * @var object
     */
    private $db;

    /**
     * Connection values to connect on DB
     * @var array
     */
    private $connectionSettings = array(
        'dbHost' => '',
        'dbName' => '',
        'dbUsername' => '',
        'dbPassword' => ''
    );

    /**
     * Value of the current status connection on DB
     * @var string
     */
    private $connectionStatus;

    /**
     * Name of the backup file to create
     * @var string
     */
    private $fileName;

    /**
     * Obtaint the sql dump to save on file
     * @var string
     */
    private $dumpSql;

    /**
     * Get the data and the select fields of table
     * @var array
     */
    private $tables ;


    public function __construct($params)
    {
        $this->fileName = $params['fileName'];
    }

    /** 
     * Set the values to make a connection on DB
     * @param array $connectionSettings
     * @return void
     */
    public function setDBConnection(array $connectionSettings)
    {
        $this->connectionSettings = $connectionSettings;
        $dsn = 'mysql:dbname=' . $this->connectionSettings['dbName'] . ';host=' . $this->connectionSettings['dbHost'];
        try {
            $this->db = new PDO($dsn, $this->connectionSettings['dbUsername'], $this->connectionSettings['dbPassword']);
            $this->connectionStatus = STRINGS['connectionDBSuccess'];
            echo  $this->connectionStatus . "\n";
            return $this->connectionStatus;
            

        } catch (PDOException $e) {
            $this->connectionStatus = STRINGS['connectionDBError'];
            echo  $this->connectionStatus . $e->getMessage() . "\n";
            return $this->connectionStatus;
            
        }
    }

    /** 
     * @param array $tables
     * @return void
     */
    public function setTablesToExport(array $tables)
    {
        $this->tables = $tables;
    }

    /** 
     * Create the "INSERT INTO tableName" format of the dump
     * @param string $dumpSqlString
     * @param array $table
     * @return string
     */
    private function generateInsertText($table)
    {
        echo "\n Generating backup for table " . $table['name'] . "\n";
        $insertText = '';
        $insertText .= "LOCK TABLES `" . $table['name'] . "` WRITE;\n\n";
        $insertText .= "INSERT INTO `" . $table['name'] . "` (";

        // CREATE INSERT TEXT
        foreach($table['select_many'] as $insertValue){
            $insertText .= "`" . $insertValue . "`,";
        }
        $insertText = substr($insertText, 0, -1);
        $insertText .= ") VALUES ";
        return $insertText;
    }

    private function getItemsFromDB(array $table)
    {
        $queryString = 'SELECT ';

        $selectMany =  implode("," ,$table['select_many']); 
        $queryString .= $selectMany . ' ';

        $queryString .= 'FROM ';
        $queryString .= $table['name'];

        if(array_key_exists('where', $table)){
            $queryString .= ' WHERE ' . $table['where'] . ' ';
        }
        
        $response = $this->db->query($queryString);
        if(!$response){
            echo 'Error when trying to obtain data for table ' . $table['name'] . "\n";
            echo "Check array of tables passed \n";
            exit;
        }
        
        return $response->fetchAll(PDO::FETCH_ASSOC);
    }

    /** 
     * Create the insert values of tables to export
     */
    private function generateInsertValues(array $items)
    {
        $insertText = '';
        foreach($items as $item){
            $insertText .= "(";
            foreach($item as $value){
                if($value == ''){   
                    $insertText .= "NULL" . ",";
                }else{
                    $insertText .= "'". $value ."',";
                }
            }
            $insertText = substr($insertText, 0, -1);
            $insertText .= "),";
        }
        $insertText = substr($insertText, 0, -1).';';
        $insertText .= "\n\nUNLOCK TABLES;\n\n ";
        return $insertText;

    }

    /** 
     * Create the sql dump file
     */
    public function createDump()
    {
        echo "Generando archivo sql ... \n";
        $this->dumpSql = '';
        foreach($this->tables as $table){
            $items = $this->getItemsFromDB($table);
            if(count($items) > 0){
                $this->dumpSql .= $this->generateInsertText($table);
                $this->dumpSql .= $this->generateInsertValues($items);
            }else{
                echo "\n No data found on table " . $table['name'] . "\n";
            }
            echo "-----------------------------------------\n";
        }

       // echo $this->dumpSql;
        
        try {
            fopen($this->fileName, 'w');
            file_put_contents($this->fileName, $this->dumpSql);
        } catch (Exception $e) {
            throw new Exception('Error al intentar generar el archivo de backup');
        }
    }


    public function getConnectionStatus()
    {
        return $this->connectionStatus;
    }

    public function getDumpSql()
    {
        return $this->dumpSql;
    }
}

