<?php

namespace Luan\ORM;

use Exception;
use Luan\ORM\Conexao;
use Luan\ORM\TraitData;
use PDO;
use ReflectionClass;

Abstract Class Model extends Conexao {

    use TraitData;

    protected $configConnect = null;

    private $dbConn;

    protected $table;

    private $cols;

    private $data;

    private $dirty;

    protected $indexCol = "id";

    protected $hiddenCols = ["updated_at", "deleted_at"];

    public $getters = [];

    private $setters = [];

    private $limit = 10000;

    private $offset = 0;

    public function __call($method, $params) {

        if (property_exists($this, $method)) {

            return $this->{$method}($params);

        }

        if (array_key_exists($method, $this->getters)) {
            return "a";
        }

        throw new Exception("Método {$method} não existe.");

    }

    public function __get($attribute) {

        return $this->{"get" . utcfirst($attribute) . "Attribute"}();

    }

    public function __set($attribute, $value) {

        $this->$attribute = $value;

    }

    public function __toString() {

    }

    public function __construct($configConnectTable = null) {

        $this->buildORM($configConnectTable);

    }

    /********

    BEGIN

     *******/

    private function buildORM($configConnectTable = null) {

        if ($configConnectTable) {

            $this->table = $configConnectTable;

        }

        $this->dbConn = ($this->configConnect) ? (parent::getExternalConn($this->configConnect)) : (parent::getConn());

        $class = new ReflectionClass($this);

        if (!isset($this->table)) {
            $this->table = strtolower($class->getShortName());
        }

        try {

            $queryCols = $this->dbConn->prepare("SHOW COLUMNS FROM {$this->table}");

            $queryCols->execute();

        } catch (Exception $e) {

            return $e->getMessage();

        }

        $this->cols = $queryCols->fetchAll(PDO::FETCH_COLUMN, 0);
        $this->cols = array_diff($this->cols, $this->hiddenCols);

        $this->data = [];

        foreach ($this->cols as $value) {

            $this->getters[] = "get" . ucfirst($value) . "Attribute";
            $this->setters[] = "set" . ucfirst($value) . "Attribute";

        }

    }

    public static function all() {

        return (new static())->getAll();

    }

    private function indexRow($index) {

        return array_combine($this->cols, $this->data[$index]);

    }

    private function prepareInsert(array $dataCols) {

        $bindFields = array_fill(0, count($dataCols), "?");

        $textFields     = implode(", ", $dataCols);
        $textBindFields = implode(", ", $bindFields);

        $text = "INSERT INTO {$this->table} ({$textFields}) VALUES({$textBindFields})";

        $statements = $this->dbConn->prepare($text);

        return $statements;

    }

    public function find() {

    }

    private function prepareSelectOne(array $dataCols) {

        $textFields = implode(", ", $dataCols);

        $text = "SELECT {$textFields} FROM {$this->table} WHERE {$this->indexCol} = :{$this->indexCol} LIMIT 1";

        $statements = $this->dbConn->prepare($text);

        return $statements;

    }

    private function isolateCols(Array $data): Array{

        return array_keys($data[0]);

    }

    public function insert(array $colIndexRow) {

        $listIndex = array_keys($colIndexRow);

        $listValues = array_values($colIndexRow);

        $statements = $this->prepareInsert($listIndex);

        try {

            $statements->execute($listValues);

            $id = $this->dbConn->lastInsertId();

            return "Sucesso: Dado {$id} inserido!";

        } catch (PDOException $e) {

            return "Dado não inserido: " . $e->getMessage();

        }

    }

    public function batchInsert(array $data) {

        $this->dbConn->beginTransaction();

        $cols = $this->isolateCols($data);

        $statements = $this->prepareInsert($cols);

        foreach ($data as $row) {

            try {

                $statements->execute(array_values($row));

            } catch (PDOException $e) {

                $this->dbConn->rollBack();

                return "!!! Inserções não executadas !!! - " . $e->getMessage();

            }

            $id = $this->dbConn->lastInsertId();

        }

        $this->dbConn->commit();

        print(count($data) . " dados inseridos com sucesso." . PHP_EOL);

    }

    private function prepareUpdate(array $dataCols, $id) {

        $arrayFields = array_map(function ($value) {

            return $value . " = ?";

        }, $dataCols);

        $textFields = implode(", ", $arrayFields);

        $text = "UPDATE {$this->table} SET {$textFields} WHERE {$this->indexCol} = {$id}";

        $statements = $this->dbConn->prepare($text);

        return $statements;

    }

    public function update(Array $colIndexRow, $id) {

        $listIndex = array_keys($colIndexRow);

        $listValues = array_values($colIndexRow);

        $statements = $this->prepareUpdate($listIndex, $id);

        try {

            $statements->execute($listValues);

            return "Sucesso: Dado {$id} atualizado!";

        } catch (PDOException $e) {

            return "Dado {$id} não atualizado: " . $e->getMessage();

        }

    }

    public function batchUpdateOrInsertIdFirst(Array $data) {

        $log = [];

        $this->dbConn = $this->dbConn;

        $cols = $this->isolateCols($data);

        $statements = $this->prepareSelectOne($cols);

        $statements->bindParam(":id", $id);

        foreach ($data as $row) {

            $virtualRow = $row;

            $id = array_shift($virtualRow);

            $statements->execute();

            try {

                if ($statements->fetchAll()) {

                    $msgExe = $this->update($row, $id);

                } else {

                    $msgExe = $this->insert($row);

                }

            } catch (PDOException $e) {

                $log[] = utf8_encode($e->getMessage());

                continue;

            }

            $log[] = $msgExe;

        }

        return $log;

    }

    /*******************************************************************/

    protected function getData() {

        return $this->data;

    }

    protected function getIndexByCols() {

        return array_flip($this->cols);

    }

    protected function setRows(Array $data) {

        $this->data = $data;

    }

}