<?php

require_once "./exception_error_handler.php";
require_once "./PgConnect.php";

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");


try {
	$conn = PgConnect::get("dbname=kurgu password=admin user=postgres");

	echo get_resource_type($conn);
	echo get_resource_type($conn);

	//var_dump(PgConnect::asValue($conn,"SELECT to_json((ROW(1,'ADMIN',30)::yetki))::json as veri "));
	//print_r( json_decode( PgConnect::asValue($conn,"SELECT to_json((ROW(1,'ADMIN',30)::yetki))::json as veri ") ) );
	pg_close($conn);
	
} catch (\Exception $ex) {
	echo "HATA: ".$ex->getMessage();
}