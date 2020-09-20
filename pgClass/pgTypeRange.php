<?php

namespace pgClass {
    use DateTime;

    class pgTypeRange implements pgTypeBase
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
                throw new pgException("Unknown data type for TS range");
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
}