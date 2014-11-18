<?php
require_once 'core/DBSync.config.php';
require_once 'core/DBSync.class.php';

if (isset ( $_GET ['act'] ))
	$action = strtolower ( $_GET ['act'] );
else
	$action = "home";

if ($action == "signup") {
	
	try {
		$DBSync = new DBSync ( new DBSyncConfig () );
		$queryToBeSync = "INSERT INTO users VALUES(" . rand ( 0, 1500 ) . ",'" . $_GET ['username'] . "','" . $_GET ['email'] . "')";
		// $backupTable="users;anotherTable";
		$backupTable = "users";
		$resultQuery = $DBSync->syncQuery ( $queryToBeSync, $backupTable );
	} catch ( Exception $e ) {
		print_r ( $e->getMessage () );
	}
	
	$action = "register";
} elseif ($action == "tablesync1") {
	
	try {
		$DBSync = new DBSync ( new DBSyncConfig () );
		$tables = array ();
		foreach ( $DBSync->MASTER->query ( "SHOW TABLES" ) as $row ) {
			$tables [] = $row [0];
		}
	} catch ( Exception $e ) {
		print_r ( $e->getMessage () );
	}
} elseif ($action == "tablesync2") {
	

	try {
		$DBSync = new DBSync ( new DBSyncConfig () );
		$tables = array ();
		foreach ( $DBSync->MASTER->query ( "SHOW TABLES" ) as $row ) {
			$tables [] = $row [0];
		}
		
		$backupTable = false;
		$forceDelete = true;
		$tableToBeSync = $_GET['SelectedTable'];
		$resultSync = $DBSync->syncTable ( $tableToBeSync, $forceDelete, $backupTable );
	} catch ( Exception $e ) {
		print_r ( $e->getMessage () );
	}
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>PhpDbSync :: Example</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet"
	href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/css/bootstrap.min.css">

<style type="text/css">
body {
	padding-top: 50px;
}

.starter-template {
	padding: 40px 15px;
	text-align: center;
}

.footer {
	position: absolute;
	bottom: 0;
	width: 100%;
	/* Set the fixed height of the footer here */
	height: 60px;
	background-color: #f5f5f5;
}

.form-signin {
	max-width: 330px;
	padding: 15px;
	margin: 0 auto;
}

.form-signin .form-signin-heading, .form-signin .checkbox {
	margin-bottom: 10px;
}

.form-signin .checkbox {
	font-weight: normal;
}

.form-signin .form-control {
	position: relative;
	height: auto;
	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
	padding: 10px;
	font-size: 16px;
}

.form-signin .form-control:focus {
	z-index: 2;
}

.form-signin input[type="email"] {
	margin-bottom: -1px;
	border-bottom-right-radius: 0;
	border-bottom-left-radius: 0;
}

.form-signin input[type="password"] {
	margin-bottom: 10px;
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

footer {
	background-color: #333;
	color: grey;
	padding: 5px 5px 5px 5px;
	width: 100%;
	bottom: 0;
	position: fixed;
}
</style>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script
	src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script
	src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.0/js/bootstrap.min.js"></script>

</head>
<body>

	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed"
					data-toggle="collapse" data-target="#navbar" aria-expanded="false"
					aria-controls="navbar">
					<span class="sr-only">Nav</span> <span class="icon-bar"></span> <span
						class="icon-bar"></span> <span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="?act=home">Php DbSync</a>
			</div>
			<div id="navbar" class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li <?php ($action=='register')? print_r ("class='active'"):''; ?>><a
						href="?act=register">Example::Registration User</a></li>
					<li
						<?php ($action=='tablesync1' || $action=='tablesync2')? print_r ("class='active'"):''; ?>><a
						href="?act=tablesync1">Example::Table Sync</a></li>
				</ul>
			</div>
			<!--/.nav-collapse -->
		</div>
	</nav>
<?php if($action=='home'){?>
	<div class="container">
		<div class="starter-template">
			<h1>Php DbSync v1.0</h1>
			<p class="lead">Quick start use.</p>
		</div>
	</div>
	<!-- /.container -->
	<h2>What you need :</h2>
	<ol>
		<li>To run the examples you will need to create the db as follows.<br />
			<b>Note: it is not mandatory to use all of the db, but remember to
				update your configuration file appropriately</b><br /> <br /> <br />
			Mysql DB MASTER NAME : <b>master</b><br /> Mysql DB SLAVE NAME : <b>slave</b><br />
			Sqlite DB SLAVE NAME : <b>slavelite</b><br /> Oracle DB SLAVE NAME :
			<b>slave (remember to insert in you tnsnames.ora this connection &
				edit the configuration file)</b><br /> <br /> Create these tables in
			each of the db:<br /> Table "Users":<br /> <br />
			<h3>Mysql</h3> <b><i>CREATE TABLE IF NOT EXISTS `users` (<br /> `ID`
					int(11) ,<br /> `NAME` varchar(50) ,<br /> `EMAIL` varchar(250) ,<br />
					PRIMARY KEY (`ID`)<br /> )
			</i></b><br /> <br />
			<h3>Sqlite</h3> <b><i>CREATE TABLE IF NOT EXISTS `users`<br /> (<br />
					id INTEGER PRIMARY KEY,<br /> name TEXT,<br /> email TEXT<br /> )
			</i></b><br /> <br />
			<h3>Oracle</h3> <b><i>CREATE TABLE users<br /> ( id number(11) ,<br />
					name varchar2(50) ,<br /> email varchar2(250)<br /> );
			</i></b><br /> <br />
		</li>
		<br />
		<li>Edit the configuration file <b>"DBSync.config.php"</b>
			<ul>
				<li>Edit your Master database connection configuration</li>
				<li>Edit your slaves database connection configuration</li>
				<li>Edit your backup strategy <br /> <i>"0" => no backup</i><br /> <i>"1"
						=> master only backup</i><br /> <i>"ALL" => backup all db</i></li>
				<li>Edit your backup folder</li>
			</ul>

		</li>

	</ol>
	<br/><br/><br/><br/><br/>
<?php }elseif($action=="register"){ ?>

 <div class="container">

		<form class="form-signin" role="form">
			<h2 class="form-signin-heading">Sign up</h2>
			<input type="text" name="username" class="form-control"
				placeholder="User Name" required autofocus> <input type="email"
				name="email" class="form-control" placeholder="Email address"
				required autofocus> <input type="hidden" name="act" value="signup" />
			<button class="btn btn-lg btn-primary btn-block" type="submit">Sign
				up</button>
		</form>

	</div>
	<!-- /container -->


<?php
	
if (isset ( $resultQuery )) {
		echo "<pre>Query result: ";
		print_r ( $resultQuery );
		echo "</pre>";
	}
} else {
	?>

 <div class="container">
		<form class="form-signin" role="form" method="get">
			<h2 class="form-signin-heading">Select the table you want sync:</h2>
			<select class="checkbox" name="SelectedTable">
      	<?php
	
foreach ( $tables as $table ) {
		echo "<option>$table</option>";
	}
	?>
      </select> <input type="hidden" name="act" value=tablesync2 />
			<button class="btn btn-lg btn-primary btn-block" type="submit">Sync
				this table</button>
		</form>
	</div>
	<!-- /container -->



<?php if(isset($resultSync)){echo "<pre>Table Sync Result:"; print_r($resultSync);echo "</pre>";} }?>

	<footer>
		&copy;2014 PhpDbSync created by <a
			href="http://www.phpclasses.org/browse/author/1167622.html">Ettore
			Moretti</a>
	</footer>
</body>
</html>
