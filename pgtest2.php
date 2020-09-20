<?php
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
set_error_handler("exception_error_handler");
require_once "./pgClass/autoload.php";

use pgClass\pgQuery;
use pgClass\pgTool;

$conn = pgTool::connection("host=localhost port=5432 dbname=kurgu user=postgres password=admin");

$q = new pgQuery($conn);
$q->param("Selam");
print_r (  $q->row( "SELECT ' 1 de ali' as b,? as a ",false ));

echo $q->getLastSqlText();