<?php

namespace pgClass {

    class pgTypeArray implements pgTypeBase {
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
}