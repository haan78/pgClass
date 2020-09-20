<?php

namespace pgModule {

    use DateTime;
    use ReflectionClass;
    use Exception;

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
                    throw new Exception("Connection is bad!");
                }
            } else {
                throw new Exception("Connection failed!");
            }
        }

        public final static function eveluate($v)
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
                $str .= "NULL";
            } elseif ($v instanceof pgTypeObject) {
                $vars = array_values(get_object_vars($v));
                $str .= "ROW(";
                for ($i = 0; $i < count($vars); $i++) {
                    if ($i > 0) {
                        $str .= ",";
                    }
                    $str .= self::eveluate($vars[$i]);
                }
                $str .= ")";
            } elseif (is_array($v)) {
                $vars = array_values($v);
                $str .= "ARRAY[";
                for ($i = 0; $i < count($vars); $i++) {
                    if ($i > 0) {
                        $str .= ",";
                    }
                    $str .= self::eveluate($vars[$i]);
                }
                $str .= "]";
            } elseif ($v instanceof DateTime) {
                $str .= "'" . $v->format('Y-m-d H:i:s') . "'";
            } elseif ($v instanceof pgTypeArray) {
                $str.= $v->serialize();
            } elseif ($v instanceof pgTypeDate) {
                $str .= $v->serialize();
            } elseif ($v instanceof pgTypeRange) {
                $str .= $v->serialize();
            } else {
                $str .= "NULL";
            }
            return $str;
        }

    }

    class pgTypeDate
    {
        private DateTime $dt;
        private string $format;
        public function __construct(DateTime $dt, string $format = 'Y-m-d H:i:s')
        {
            $this->dt = $dt;
            $this->format = $format;
        }

        public static function create(string $date, string $format = 'Y-m-d H:i:s'): pgTypeDate
        {
            return new pgTypeDate(new DateTime($date), $format);
        }

        public function serialize(): string
        {
            return "'" . $this->dt->format($this->format) . "'";
        }
    }

    class pgTypeRange
    {

        public const RANGE_TYPE_INT = 1;
        public const RANGE_TYPE_BIG = 2;
        public const RANGE_TYPE_NUM = 3;
        public const RANGE_TYPE_TS = 4;
        public const RANGE_TYPE_TSZ = 5;
        public const RANGE_TYPE_DATE = 6;

        private $min;
        private $max;
        private bool $inclusiveMinBound = true;
        private bool $inclusiveMaxBound = true;
        private int $rangeType = self::RANGE_TYPE_INT;
        public function __construct(int $rangeType, $min, $max, bool $minb = true, bool $maxb = false)
        {
            $this->min = $min;
            $this->max = $max;
            $this->inclusiveMinBound = $minb;
            $this->inclusiveMaxBound = $maxb;
            $this->rangeType = $rangeType;
        }

        private function tsformat($v, $format)
        {
            if (is_string($v)) {
                return $v;
            } elseif ($v instanceof DateTime) {
                return $v->format($format);
            } else {
                throw new Exception("Unknown data type for TS range");
            }
        }

        public final function serialize(): string
        {
            $rt = "";
            $min = "";
            $max = "";
            $minb = ($this->inclusiveMinBound ? "[" : "(");
            $maxb = ($this->inclusiveMaxBound ? "]" : ")");
            switch ($this->rangeType) {
                case self::RANGE_TYPE_INT:
                    $rt = "int4range";
                    $min = intval($this->min);
                    $max = intval($this->max);
                    break;
                case self::RANGE_TYPE_BIG:
                    $rt = "int8range";
                    $min = intval($this->min);
                    $max = intval($this->max);
                    break;
                case self::RANGE_TYPE_NUM:
                    $rt = "numrange";
                    $min = floatval($this->min);
                    $max = floatval($this->max);
                    break;
                case self::RANGE_TYPE_TS:
                    $rt = "tsrange";
                    $min = $this->tsformat($this->min, "Y-m-d H:i:s");
                    $max = $this->tsformat($this->max, "Y-m-d H:i:s");
                    break;
                case self::RANGE_TYPE_TSZ:
                    $rt = "tstzrange";
                    $min = $this->tsformat($this->min, "c");
                    $max = $this->tsformat($this->max, "c");
                    break;
                case self::RANGE_TYPE_DATE:
                    $rt = "daterange";
                    $min = $this->tsformat($this->min, "Y-m-d");
                    $max = $this->tsformat($this->max, "Y-m-d");
                    break;
            }
            return "'$minb$min,$max$maxb'::$rt";
        }
    }

    class pgTypeArray {
        private array $list = [];
        private string $type = ""; 
        public function __construct(array $list,string $type = "") {
            $this->list = $list;
            $this->type = $type;
        }

        public function serialize() : string {
            return pgTool::eveluate($this->list).( $this->type != "" ? "::".$this->type."[]" : "" );
        }
    }

    class pgTypeObject
    {
        private final function getClassName()
        {
            $n = "";
            $s = "public";
            $classname = get_class($this);
            if ($n = strrpos($classname, '\\')) {
                $n = substr($classname, $n + 1);
            }

            $cl = (new ReflectionClass($this))->getConstants();
            if (isset($cl["SCHEMA"])) {
                $s = $cl["SCHEMA"];
            }
            if (isset($cl["NAME"])) {
                $n = $cl["NAME"];
            }
            return  "$s.$n";
        }

        public final function serialize()
        {
            echo pgTool::eveluate($this) . "::" . $this->getClassName();
        }
    }

    class pgQuery
    {
        private $conn;

        public function __construct($conn) {
            if ( is_resource($conn) ) {
                if ( get_resource_type($conn)=="pgsql link" || get_resource_type($conn)=='pgsql link persistent') {
                    $this->conn = $conn;
                } else {
                    throw new Exception("Unknown resource type");
                }
            } elseif (is_string($conn)) {
                $this->conn = pgTool::connection($conn);
            } else {
                throw new Exception("Unknown resource type");
            }

        }

        private function exec(string $sql, array $values)
        {
            $result = pg_query_params($this->conn, $sql, $values);
            if ($result !== false) {
                return $result;
            } else {
                throw new Exception(pg_last_error($this->conn));
            }
        }

        public function list(string $sql, array $values = [],$assoc = true) {
            $list = [];
            $result = $this->exec($sql, $values);
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

        public function row(string $sql, array $values = [],$assoc = true) {
            $result = $this->exec($sql, $values);
            if ( $assoc ) {
                return pg_fetch_assoc($result);
            } else {
                return pg_fetch_row($result);
            }
        }

        public function value(string $sql,array $values = []) {
            $result = $this->row($sql,$values,false);
            if (!is_null($result) && isset($result[0]) ) {
                return $result[0];
            } else {
                return null;
            }
        }

        public function close() {
            if (!is_null($this->conn)) {
                pg_close($this->conn);
                $this->conn = NULL;
            }
        }
    }

    class yetki extends pgTypeObject
    {
        public $yetki_id = 1;
        public $yetki = "ADMIN";
        public $deger = 30;
    }

    class paket extends pgTypeObject
    {
        public $kullanici_id = 1001;
        public ?pgTypeDate $tarih = null;
        public ?pgTypeRange $aralik = null;
        public $yetkiler = [3, 2, 1];
    }

    $o = new paket();
    $o->tarih =  pgTypeDate::create("NOW", 'Y-m-d');
    $o->aralik = new pgTypeRange(pgTypeRange::RANGE_TYPE_INT, 3, 7, true, false);
    $o->serialize();
}
