<?php

namespace pgClass {
    use DateTime;

    class pgTool {

        public static function connection(string $cs)
        {
            $conn = pg_connect($cs, PGSQL_CONNECT_ASYNC);
            if ($conn !== FALSE) {
                $poll_outcome = PGSQL_POLLING_WRITING;

                while (true) {
                    $socket = [pg_socket($conn)]; // "Caution: do not assume that the socket remains the same across PQconnectPoll calls."
                    $null = [];

                    if ($poll_outcome === PGSQL_POLLING_READING) {
                        stream_select($socket, $null, $null, 5);
                        $poll_outcome = pg_connect_poll($conn);
                    } else if ($poll_outcome === PGSQL_POLLING_WRITING) {
                        stream_select($null, $socket, $null, 5);
                        $poll_outcome = pg_connect_poll($conn);
                    } else {
                        break;
                    }
                }
                if (pg_connection_status($conn) == PGSQL_CONNECTION_OK) {
                    return $conn;
                } else {
                    throw new pgException("Connection is bad!");
                }
            } else {
                throw new pgException("Connection failed!");
            }
        }

        private static function isAssoc(array $arr) {
            if (array() === $arr) return false;
            return array_keys($arr) !== range(0, count($arr) - 1);
        }

        public final static function eveluate($v) : string
        {
            $str = "";
            if (is_bool($v)) {
                $str .= ($v == true ? '1' : '0') . "::bit";
            } elseif (is_int($v)) {
                $str .= "$v";
            } elseif (is_float($v)) {
                $str .= "$v";
            } elseif (is_double($v)) {
                $str .= "$v";
            } elseif (is_string($v)) {
                $str .= "'" . pg_escape_string($v) . "'";
            } elseif (is_null($v)) {
                $str .= "NULL";
            } elseif (is_long($v)) {
                $str .= $v;
            } elseif ($v instanceof DateTime) {
                $str .= "'" . $v->format('Y-m-d H:i:s') . "'";
            } elseif (is_array($v)) {
                if ( self::isAssoc($v) ) {
                    $vars = array_values($v);
                    $str .= "ROW(";
                    for ($i = 0; $i < count($vars); $i++) {
                        if ($i > 0) {
                            $str .= ",";
                        }
                        $str .= self::eveluate($vars[$i]);
                    }
                    $str .= ")";
                } else {
                    $vars = $v;
                    $str .= "ARRAY[";
                    for ($i = 0; $i < count($vars); $i++) {
                        if ($i > 0) {
                            $str .= ",";
                        }
                        $str .= self::eveluate($vars[$i]);
                    }
                    $str .= "]";
                }
            } elseif ($v instanceof pgTypeBase ) {
                $str.= $v->serialize();
            } else {
                $str .= "NULL";
            }
            return $str;
        }

    }
}