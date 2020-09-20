<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$conn = null;
try {
	$conn = pg_connect("host=localhost port=5432 dbname=kurgu user=postgres password=admin");

	pg_set_error_verbosity($conn, PGSQL_ERRORS_VERBOSE);

	if (!pg_connection_busy($conn)) {
		pg_send_query($conn, "SELECT * FROM kul-lanici");
	}
	$result = pg_get_result($conn);
	var_dump($result);
    echo pg_result_error($result);

	//$result2 = pg_query_params($conn,'INSERT INTO yetki (yetki,deger) VALUES ($1,$2)',['B',20] );
	//var_dump($result2);

	
	
} catch (\Exception $ex) {

	echo "HATA: ".$ex->getMessage();

}
pg_close($conn);
