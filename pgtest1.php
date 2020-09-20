<?php

require_once "./pgClass/autoload.php";

use pgClass\pgQuery;
use pgClass\pgTool;
use pgClass\pgTypeArray;

$conn = pgTool::connection("host=localhost port=5432 dbname=kurgu user=postgres password=admin");

$q = new pgQuery($conn);

echo $q->param(new pgTypeArray([1,2,3,4,5,6],"numeric"))->value("SELECT * FROM ortalama( ?1 )" );

echo $q->getLastSqlText();


