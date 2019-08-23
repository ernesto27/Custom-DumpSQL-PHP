<?php 
use PHPUnit\Framework\TestCase;

require_once 'src/CustomDumpSQL.php';
require_once 'strings.php';


final class CustomDumpSQLTest extends TestCase
{   

 
    protected function setUp(): void
    {
        $this->dumpSqlName = 'tests/testdump.sql';
        $this->backup = new BackupDB(array('fileName' => $this->dumpSqlName));
        $this->connectionSettings = array(
            'dbHost' => getenv("DB_HOST"),
            'dbName' => 'ot',
            'dbUsername' => 'root',
            'dbPassword' => '1234'
        );
    }

    /** @test */
    public function it_should_connect_to_db()
    {
        $this->backup->setDBConnection($this->connectionSettings);
        $this->assertEquals(STRINGS['connectionDBSuccess'], $this->backup->getConnectionStatus());
    }

    /** @test */
    public function it_should_fail_to_db_if_not_valid_credential_passed()
    {
        $this->connectionSettings['dbName'] = 'fakeDB';
        $this->connectionSettings['dbUsername'] = 'fakeUsername';
        $this->backup->setDBConnection($this->connectionSettings);
        $this->assertEquals(STRINGS['connectionDBError'], $this->backup->getConnectionStatus());
    }
    
    /** @test */
    public function it_should_generate_a_valid_sql_dump_file()
    {
        $this->backup->setDBConnection($this->connectionSettings);

        $tables = array(
            array(
                'name' => 'articulo',
                'select_many' => array('codigo', 'descripcion')
            ),
            array(
                'name' => 'rubro',
                'select_many' => array('id', 'descripcion')
            )
        );
        $this->backup->setTablesToExport($tables);
        $this->backup->createDump();
        
        $this->assertFileEquals('tests/dumpSQLExpected.sql', $this->dumpSqlName);

    }

}
