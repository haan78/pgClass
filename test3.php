<?php

require_once "./pgClass/autoload.php";
use \pgClass\pgQuery;

function pageNum() {
    return ( isset( $_GET["p"] ) ? intval($_GET["p"]) : 1 );
}

function page() {
    $pn = pageNum();
    $limit = 20;
    $start = ($pn - 1) * $limit;

    $sql = "SELECT * FROM kullanici LIMIT $limit OFFSET $start";

    $q = new pgQuery( "host=localhost port=5432 dbname=kurgu user=postgres password=admin" );
    return $q->list($sql);
}

