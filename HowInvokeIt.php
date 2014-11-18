<?php
require_once 'core/DBSync.config.php';
require_once 'core/DBSync.class.php';

try {
	
	$DBSync= new DBSync(new DBSyncConfig());

	/**
	 * [IT]
	 * Sincronizzazione di una query (Create, Update, Delete) su tutti i db
	 * 
	 * Attenzione: 
	 * Alcuni database si discostano dalla sintassi del sql standard, 
	 * per questo se i nodi master/slave sono di tipo diverso,
	 * si potrebbero riscontrare degli errori nell'esecuzione.
	 * 
	 * Cosa serve per invocare questo metodo?
	 * 1) Una query
	 * 
	 * 2) Una variabile stringa, che rappresenti le tabelle sulle quali richiediamo sia effettuato un backup preventivo separate da ";"
	 * 	questa variabile può anche essere ignorata e non passata, in tal caso però se la backup strategy scelta nelle configurazioni lo 
	 *  prevede, verrà effettuato un backup di tutte le tabelle.	
	 * 
	 * Attenzione: 
	 * L'operazione di backup a seconda della quantità dei dati presenti nelle tabelle può richiedere diverso tempo e risorse.
	 * Se la somma dei dati nelle tabelle di cui richiedi il backup supera il "milione", potrebbe rallentare il tuo sistema fino a 
	 * sollevare eccezioni quali:  Allowed memory size of xxx byte exhausted  o Maximum execution time of xx seconds exceeded
	 * 
	 * 
	 * 
	 * 
	 * 
	 * [EN] 
	 * Synchronization of a query (Create, Update, Delete) on all db 
	 * 
	 * Warning: 
	 * Some databases differ from the syntax of the sql standard 
	 * If the nodes for this master/slave are of different types, 
	 * You may encounter errors in execution. 
	 * 
	 * What you need to invoke this method? 
	 * 1) A query 
	 * 
	 * 2) A string variable that represents the tables on which we require to be a backup separated by ";" 
	 * This variable may also be ignored and not passed, in such a case, however, if the backup strategy choice in the configurations 
	 * provides, will be a backup of all the tables. 
	 * 
	 * Warning: 
	 * The backup operation depending on the amount of data in the tables may take time and resources. 
	 * If the sum of the data in the tables in the backup request exceeds the "million", it could slow down your system up to 
	 * raising an exception such as: Allowed memory size of xxx bytes exhausted or Maximum execution time of xx seconds exceeded
	 */

	$queryToBeSync="INSERT INTO utenti VALUES(null,'Ettore','ettore@email.com')";
	//$backupTable="utenti;commenti";
	$backupTable="";
	
	$result=$DBSync->syncQuery($queryToBeSync,$backupTable);
		echo "<pre>";
		print_r($result);

		
	/**
	 * 
	 * [IT]
	 * Sincronizzazione di un'intera tabella dal master db agli slave
	 * 
	 * Attenzione: Ricordati sempre che la quantità dei dati da trasportare da un db all'altro e direttamente proporzionale alla quantità di risorse 
	 * che lo script richiederà.   
	 *
	 * [EN] 
	 * Synchronizing an entire table from the master to the slave db 
	 * 
	 * Note: Always remember that the amount of data to move from one db to another, and directly proportional to the amount of resources 
	 * The script will take. 
	 * 
	 *  
	 *  
	 */

	$backupTable=false;
	$forceDelete=true;
	$tableToBeSync="utenti";
	$result=$DBSync->syncTable($tableToBeSync,$forceDelete,$backupTable);
	echo "<pre>";
	print_r($result);

} catch (Exception $e) {
	print_r($e->getMessage());
}

