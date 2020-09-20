<?php

class PgConnect
{
    public static function get(string $cs)
    {
        $conn = pg_connect($cs,PGSQL_CONNECT_ASYNC);
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
            if ( pg_connection_status($conn) == PGSQL_CONNECTION_OK ) {
                return $conn;
            } else {
                throw new \Exception("Connection is bad!");
            }
        } else {
            throw new \Exception("Connection failed!");
        }
    }

    public static function asList($conn, string $sql,bool $assoc = true) : array {
        $result = pg_query($conn,$sql);
        if ($result !==FALSE) {
            $list = [];
            if ( $assoc ) {
                while ($row = pg_fetch_assoc($result)) {
                    array_push($list,$row);
                }
            } else {
                while ($row = pg_fetch_row($result)) {
                    array_push($list,$row);
                }
            }
            return $list;
        }
    }

    public static function asRow($conn, string $sql,bool $assoc = true) : ?array {
        $result = pg_query($conn,$sql);
        if ($result !==FALSE) {
            $list = [];
            if ( $assoc ) {
                return pg_fetch_assoc($result);
            } else {
                return pg_fetch_row($result);
            }
            return $list;
        }
    }

    public static function asValue($conn, string $sql) {
        $result = self::asRow($conn,$sql,false);
        if (!is_null($result) && isset($result[0]) ) {
            return $result[0];
        } else {
            return null;
        }
    }
}

