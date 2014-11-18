<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
* A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
* OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
* SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
		* LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
		* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
* THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
* OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*
* This software consists of voluntary contributions made by many individuals
* and is licensed under the MIT license. For more information, see
* <http://www.doctrine-project.org>.
*/

/**
 * Php Db Sync
*
* This class allows you to execute queries synchronized on various database (also of different types) 
* and\or synchronize entire tables on different databases.
* You'll also get a chance to create backups before every launch.
*
* PHP version 5
*
* @license http://www.opensource.org/licenses/mit-license.html  MIT License
* @author 	Ettore Moretti <ettoremoretti27{at}gmail{dot}com>
* @copyright	Ettore Moretti 2014
* @version	1.0
* @since  	2014
*/

class DBSync extends PDO {
	protected $connections;
	protected $backupStrategy;
	protected $dbConf;
	public $MASTER;
	
	/**
	 * Construct method for DBSync class
	 */
	public function __construct($DbConf = false) {
		if ($DbConf instanceof DBSyncConfig) {
			// Control connections to the various db
			if (! $this->connectionController ( $DbConf::getMaster () ))
				throw new Exception ( "DBSyncError:: Master db unreachable" );
			foreach ( $DbConf::getSlave () as $key => $slavedb ) {
				if (! $this->connectionController ( $slavedb ))
					throw new Exception ( "DBSyncError:: Db slave " . $slavedb ['DBNAME'] . " unreachable" );
			}
			// establish connections to db
			$this->connections ['MASTER'] = $this->dbConnect ( $DbConf::getMaster () );
			$this->MASTER=$this->connections ['MASTER'];
			
			foreach ( $DbConf::getSlave () as $key => $slavedb ) {
				$this->connections ['SLAVE'] [$key] = $this->dbConnect ( $slavedb );
			}
			
			// get backup strategy
			$this->backupStrategy = $DbConf::getBackup ();
			$this->dbConf = $DbConf;
		} else {
			throw new Exception ( "DBSyncError::Db's Configuration have not been retrieved." );
		}
		return 1;
	}
	
	public function syncQuery($query, $backupTable = "") {
		$error = "";
		if ($this->backupStrategy != "0")
			$this->backup ( $backupTable );
		
		$affRowsMaster = $this->connections ['MASTER']->exec ( $query ) or $error = "DBSyncError:: Sync Error at master db (" . $this->connections ['MASTER']->errorInfo ()[2] . ")";
		if ($error != "")
			throw new Exception ( $error );
		
		foreach ( $this->dbConf->getSlave () as $key => $slavedb ) {
			$affRowsSlave [$slavedb ['DBNAME']] = $this->connections ['SLAVE'] [$key]->exec ( $query ) or $error = "DBSyncError:: Sync Error at slave db" . $slavedb ['DBNAME'] . " (" . $this->connections ['SLAVE'] [$key]->errorInfo () . ")";
			if ($error != "") {
				throw new Exception ( $error );
				exit ();
			}
		}
		return array (
				"QUERY" => $query,
				"MasterAffectedRows" => $affRowsMaster,
				"SlavesAffectedRows" => $affRowsSlave 
		);
	}
	
	
	/**
	 * SyncTable method
	 * 
	 * @param unknown $table
	 * @param string $backupTable
	 */
	public function syncTable($table,$forcedelete=false, $backupTable = false) {
		$out=array();
		$delete="";
		
		if ($this->backupStrategy != "0" && $backupTable===true)
			$this->backup ( $table );		
		
		$tDump=$this->getTableDump( $this->connections ['MASTER'], $table);

		if($forcedelete===true)
			$delete="DELETE FROM ". $table."  ";
		
		foreach ( $this->dbConf->getSlave () as $key => $slavedb ) {
			$out[$key]=$this->exeDumpToDb($this->connections ['SLAVE'] [$key], $slavedb['DBNAME'],$tDump,$delete);
		}

		return $out;
	}
	
	private function getTableDump($db,$table){
		$Dump="";
		$tContent = array ();

		foreach ( $db->query ( "SELECT * FROM " . $table, PDO::FETCH_ASSOC ) as $row )
			$tContent [] = $row;
				
		foreach ( $tContent as $content ) {
			$tmp = " INSERT INTO " . $table . " VALUES(";
				foreach ( $content as $key => $value ) {
					$tmp .= " '" . $value . "' ";
						if (end ( $content ) !== $value)
							$tmp .= " , ";
						}
					$tmp .= ");\n";
					$Dump .= $tmp;
					$tmp = "";
		}	
		return $Dump;			
	}
	
	private function exeDumpToDb($db,$dbname,$dump,$forceDelete=""){
		try {
			if($forceDelete!="")
			{
				$stm=$db->prepare($forceDelete);
				$stm->execute();
			}
	
			$stmnt=$db->exec($dump);
			

		} catch ( Exception $e ) {
			return "DBSync::Error  dump db $dbname (".$e->getMessage().")";
		}
		return "Dump run successfully on db : ".$dbname;
	}
	
	/**
	 * Backup tables passed or all db
	 * 
	 * @param string $tables        	
	 */
	private function backup($tables = "") {
		if ($tables == "")
			$tables = "*";
		
		switch ($this->backupStrategy) {
			case "1" :
				$this->exeBackup ( $this->connections ['MASTER'], $this->dbConf->getMaster ()['DBNAME'], $this->dbConf->getMaster ()['TYPE'], $tables );
				break;
			
			case "ALL" :
				$this->exeBackup ( $this->connections ['MASTER'], $this->dbConf->getMaster ()['DBNAME'], $this->dbConf->getMaster ()['TYPE'], $tables );
				foreach ( $this->dbConf->getSlave () as $key => $slavedb ) {
					$this->exeBackup ( $this->connections ['SLAVE'] [$key], $slavedb ['DBNAME'], $slavedb ['TYPE'], $tables );
				}
				break;
		}
	}
	
	private function exeBackup($db, $dbname, $dbType, $tabelle) {
		$t = array ();
		$dumpFile = "";
		
		switch (strtolower ( $dbType )) {
			case "mysql" :
				if ($tabelle == '*')
					foreach ( $db->query ( "SHOW TABLES" ) as $row ) {
						$t [] = $row [0];
					}
				else
					$t = explode ( ';', $tabelle );
				
				foreach ( $t as $tabella ) {
					$tContent = array ();
					foreach ( $db->query ( "SELECT * FROM " . $tabella, PDO::FETCH_ASSOC ) as $row )
						$tContent [] = $row;
					
					$stmt = $db->query ( "SHOW CREATE TABLE " . $tabella );
					$create = $stmt->fetch ()[1];
					$dumpFile .= "--\n-- table structure \n--\n\n" . str_replace ( "CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $create );
					
					$dumpFile .= "\n\n--\n-- Content of table \n--\n\n";
					
					foreach ( $tContent as $content ) {
						$tmp = " INSERT INTO " . $tabella . " VALUES(";
						foreach ( $content as $key => $value ) {
							$tmp .= " '" . $value . "' ";
							if (end ( $content ) !== $value)
								$tmp .= " , ";
						}
						$tmp .= ");\n";
						$dumpFile .= $tmp;
						$tmp = "";
					}
				}
				break;
			
			case "sqlite" :
				if ($tabelle == '*')
					foreach ( $db->query ( "select name from sqlite_master" ) as $row ) {
						$t [] = $row [0];
					}
				else
					$t = explode ( ';', $tabelle );
				
				foreach ( $t as $tabella ) {
					$tContent = array ();
					foreach ( $db->query ( "SELECT * FROM " . $tabella, PDO::FETCH_ASSOC ) as $row )
						$tContent [] = $row;
					
					$stmt = $db->query ( "select sql from sqlite_master where name ='" . $tabella . "'", PDO::FETCH_ASSOC );
					$create = $stmt->fetch ()['sql'];
					$dumpFile .= "--\n-- table structure \n--\n\n" . str_replace ( "CREATE TABLE ", "CREATE TABLE IF NOT EXISTS ", $create );
					
					$dumpFile .= "\n\n--\n-- Content of table \n--\n\n";
					
					foreach ( $tContent as $content ) {
						$tmp = " INSERT INTO " . $tabella . " VALUES(";
						foreach ( $content as $key => $value ) {
							$tmp .= " '" . $value . "' ";
							if (end ( $content ) !== $value)
								$tmp .= " , ";
						}
						$tmp .= ");\n";
						$dumpFile .= $tmp;
						$tmp = "";
					}
				}
				break;
			
			case 'oracle' :
				if ($tabelle == '*')
					foreach ( $db->query ( "select * from user_tables", PDO::FETCH_ASSOC ) as $row ) {
						$t [] = $row ['TABLE_NAME'];
					}
				else
					$t = explode ( ';', $tabelle );
				
				foreach ( $t as $tabella ) {
					$tContent = array ();
					foreach ( $db->query ( "SELECT * FROM " . $tabella, PDO::FETCH_ASSOC ) as $row )
						$tContent [] = $row;
					
					$stmt = $db->query ( "select dbms_metadata.get_ddl( 'TABLE','" . $tabella . "',sys_context( 'userenv', 'current_schema' ))  FROM DUAL" );
					
					$create = stream_get_contents ( $stmt->fetch ()[0] );
					
					$dumpFile .= "--\n-- table structure \n--\n\n" . str_replace ( "CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $create );
					
					$dumpFile .= "\n\n--\n-- Content of table \n--\n\n";
					
					foreach ( $tContent as $content ) {
						$tmp = " INSERT INTO " . $tabella . " VALUES(";
						foreach ( $content as $key => $value ) {
							$tmp .= " '" . $value . "' ";
							if (end ( $content ) !== $value)
								$tmp .= " , ";
						}
						$tmp .= ");\n";
						$dumpFile .= $tmp;
						$tmp = "";
					}
				}
				break;
		}
		// Save dump file 
			$this->file_force_contents ( $this->dbConf->getBackupFolder () . DIRECTORY_SEPARATOR . $dbname . DIRECTORY_SEPARATOR . "backup_" . date ( "dmY_His" ) . ".sql", $dumpFile );
	}
	
	private function file_force_contents($filename, $data, $flags = 0) {
		if (! is_dir ( dirname ( $filename ) ))
			mkdir ( dirname ( $filename ) . '/', 0777, TRUE );
		return file_put_contents ( $filename, $data, $flags );
	}
	
	/**
	 * Get persistence Db connection
	 */
	private function dbConnect($db) {
		switch (strtolower ( $db ['TYPE'] )) {
			case 'mysql' :
				$dsn = $db ['TYPE'] . ":host=" . $db ['HOST'] . ";dbname=" . $db ['DBNAME'];
				break;
			
			case 'oracle' :
				$dsn = "oci:dbname=" . $db ['DBNAME'];
				break;
			
			case 'sqlite' :
				$dsn = "sqlite:" . $db ['HOST'] . $db ['DBNAME'];
				break;
			
			default :
				throw new Exception ( "DBSyncError:: database type is not supported." );
				break;
		}
		
		$username = $db ['USER'];
		$password = $db ['PWD'];
		$options = array (
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_PERSISTENT => true 
		);
		try {
			$connection = new PDO ( $dsn, $username, $password, $options );
		} catch ( Exception $e ) {
			return false;
		}
		
		return $connection;
	}
	
	/**
	 * Check that all databases are accessible
	 */
	private function connectionController($db) {
		switch (strtolower ( $db ['TYPE'] )) {
			case 'mysql' :
				$dsn = $db ['TYPE'] . ":host=" . $db ['HOST'] . ";dbname=" . $db ['DBNAME'];
				break;
			
			case 'oracle' :
				$dsn = "oci:dbname=" . $db ['DBNAME'];
				break;
			
			case 'sqlite' :
				$dsn = "sqlite:" . $db ['HOST'] . $db ['DBNAME'];
				break;
			
			default :
				throw new Exception ( "DBSyncError:: database type is not supported." );
				break;
		}
		
		$username = $db ['USER'];
		$password = $db ['PWD'];
		$options = array (
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION 
		);
		try {
			$connection = new PDO ( $dsn, $username, $password, $options );
		} catch ( Exception $e ) {
			return false;
		}
		$connection = null;
		return true;
	}
}
