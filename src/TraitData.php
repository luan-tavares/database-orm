<?php

namespace Luan\ORM;

use Closure;
use PDO;

Trait TraitData {

    public function getAll() {

        $exConn = $this->dbConn;

        try {

            $table = $this->table;

            $b = $exConn->query("select * from {$table}");

        } catch (Exception $e) {

            return $e;

        }

        $result = $b->fetchAll(PDO::FETCH_NUM);

        $this->data = $result;

        return $this->data;

    }

    public function find($id) {

        $query = "SELECT * FROM {$this->table} WHERE {$this->indexCol} = ? LIMIT {$this->offset}, {$this->limit}";

        $statment = $this->dbConn->prepare($query);

        try {

            $statment->execute([$id]);

        } catch (Exception $e) {

            return $e;

        }

        $result = $statment->fetchAll(PDO::FETCH_ASSOC);

        $this->data = $result;

        return $this->data;

    }

    public function getCell($rowId, $col) {

        $a = $this->getIndexByCols();

        return $this->data[$rowId][$a[$col]];

    }

    public function addCol($col, Closure $res) {

        $data = $this->table;

        foreach ($this->data as $index => $row) {

            $line = array_combine($this->cols, $row);

            $this->data[$index][] = $res($line);

        }

        $this->cols[] = $col;

        return $this;

    }

    public function dataHandler($data, $field, Closure $func) {

        $a = [];

        $i = 0;

        foreach ($data as $row) {

            $res = $func($row);

            if ($res) {

                foreach ($res as $value) {

                    $a[$i] = $row;

                    $a[$i][$field] = $value;

                    $i++;

                }

            }

        }

        return $a;

    }

}