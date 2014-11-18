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
* This class is used as the configuration for the DBSync class
*
* PHP version 5
*
* @license http://www.opensource.org/licenses/mit-license.html  MIT License
* @author 	Ettore Moretti <ettoremoretti27{at}gmail{dot}com>
* @copyright	Ettore Moretti 2014
* @version	1.0
* @since  	2014
*/

class DBSyncConfig{
	private static $MasterDb = array (
			"TYPE" => "mysql",
			"HOST" => "localhost",
			"PORT" => "",
			"DBNAME" => "master",
			"USER" => "root",
			"PWD" => "" 
	);

	private static $SlaveDbs = array (
			0 => array (
					"TYPE" => "sqlite",
					"HOST" => "Db/lite/",
					"PORT" => "",
					"DBNAME" => "slaveLite.db3",
					"USER" => "",
					"PWD" => "" 
			),
			1 => array (
					"TYPE" => "mysql",
					"HOST" => "localhost",
					"PORT" => "",
					"DBNAME" => "slave",
					"USER" => "root",
					"PWD" => "" 
			)/*,
			2 => array (
					"TYPE" => "oracle",
					"HOST" => "",
					"PORT" => "",
					"DBNAME" => "<SID IN YOUR TNSNAMES>",
					"USER" => "<USER>",
					"PWD" => "<PASSWORD>"
			)*/
	);

	/* BACKUP TYPE
	 * 
	 * "0" 		=> no backup
	 * "1" 		=> master only backup
	 * "ALL"	=> backup all db
	 */ 
	private static $BackupType="ALL";
	private static $BackupFolder="dbBackup/";
	
	public function __construct(){}

	public static function getMaster(){
		return self::$MasterDb;
	}
	public static function getSlave(){
		return self::$SlaveDbs;
	}
	public static function getBackup(){
		return self::$BackupType;
	}
	public static function getBackupFolder(){
		return self::$BackupFolder;
	}
}
