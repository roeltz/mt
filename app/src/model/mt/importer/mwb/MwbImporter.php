<?php

namespace mt\importer\mwb;
use SimpleXMLElement;
use mt\Importer;
use mt\Model;
use mt\Entity;
use mt\Attribute;
use mt\Relation;
use mt\Type;

class MwbImporter implements Importer {

	static $TYPES = array(
		'com.mysql.rdbms.mysql.datatype.tinyint'=>Type::TINYINT,
		'com.mysql.rdbms.mysql.datatype.smallint'=>Type::SMALLINT,
		'com.mysql.rdbms.mysql.datatype.int'=>Type::INTEGER,
		'com.mysql.rdbms.mysql.datatype.bigint'=>Type::BIG_INTEGER,
		'com.mysql.rdbms.mysql.datatype.double'=>Type::DOUBLE,
		'com.mysql.rdbms.mysql.datatype.char'=>Type::CHAR,
		'com.mysql.rdbms.mysql.datatype.varchar'=>Type::VARCHAR,
		'com.mysql.rdbms.mysql.datatype.text'=>Type::TEXT,
		'com.mysql.rdbms.mysql.datatype.longtext'=>Type::TEXT,
		'com.mysql.rdbms.mysql.datatype.datetime'=>Type::DATETIME,
		'com.mysql.rdbms.mysql.datatype.date'=>Type::DATE,
		'com.mysql.rdbms.mysql.datatype.time'=>Type::TIME,
		'com.mysql.rdbms.mysql.datatype.boolean'=>Type::BOOLEAN,
		'com.mysql.rdbms.mysql.datatype.bool'=>Type::BOOLEAN
	);

	static $UPDATE_ACTIONS = array(
		'NO ACTION'=>Relation::NO_ACTION,
		'CASCADE'=>Relation::CASCADE,
		'RESTRICT'=>Relation::RESTRICT,
		'SET NULL'=>Relation::SET_NULL
	);

	function getModel($path) {
		$xml = $this->getXMLDocument($this->getZippedContent($path));
		$schemaNode = current($xml->xpath("//value[@struct-name='db.mysql.Schema']"));
		$model = $this->surveyModel($schemaNode);
		return $model;
	}

	function surveyModel($schemaNode) {
		$model = new Model(
			(string) current($schemaNode->xpath("value[@key='name']")),
			(string) current($schemaNode->xpath("value[@key='comment']"))
		);

		$tables = $schemaNode->xpath(".//value[@struct-name='db.mysql.Table']");

		foreach($tables as $tableNode) {
			$entity = $this->surveyEntity($tableNode);
			$model->addEntity($entity);
		}

		return $model;
	}

	function surveyEntity($tableNode) {
		$entity = new Entity(
			(string) current($tableNode->xpath("value[@key='name']")),
			(string) current($tableNode->xpath("value[@key='comment']"))
		);

		$columns = $tableNode->xpath(".//value[@struct-name='db.mysql.Column']");
		$foreignKeys = $tableNode->xpath(".//value[@struct-name='db.mysql.ForeignKey']");
		$indexes = $tableNode->xpath(".//value[@struct-name='db.mysql.Index']");

		foreach($columns as $columnNode) {
			$attribute = $this->surveyAttribute($columnNode);
			$entity->addAttribute($attribute);
		}

		foreach($foreignKeys as $fkNode) {
			$relation = $this->surveyRelation($fkNode);
			$entity->addRelation($relation);
		}

		foreach($indexes as $indexNode) {
			$indexType = (string) current($indexNode->xpath("value[@key='indexType']"));
			if ($indexType == "PRIMARY") {
				$fields = array();
				$columns = $indexNode->xpath(".//value[@struct-name='db.mysql.IndexColumn']");
				foreach($columns as $column) {
					$columnId = (string) current($column->xpath("link[@key='referencedColumn']"));
					$fields[] = (string) current($column->xpath("/.//value[@struct-name='db.mysql.Column'][@id='$columnId']/value[@key='name']"));
				}
				$entity->setPrimaryKey($fields);
			} elseif ($indexType == "UNIQUE") {
				$fields = array();
				$columns = $indexNode->xpath(".//value[@struct-name='db.mysql.IndexColumn']");
				foreach($columns as $column) {
					$columnId = (string) current($column->xpath("link[@key='referencedColumn']"));
					$fields[] = (string) current($column->xpath("/.//value[@struct-name='db.mysql.Column'][@id='$columnId']/value[@key='name']"));
				}
				$entity->addUniqueTuple($fields);
			}
		}

		return $entity;
	}

	function surveyAttribute($columnNode) {
		$attribute = new Attribute(
			$name = (string) current($columnNode->xpath("value[@key='name']")),
			$this->surveyType($columnNode),
			(string) current($columnNode->xpath("value[@key='isNotNull']")) != "1",
			count(array_filter(array_map(function($i){ return (string) $i; }, $columnNode->xpath("value[@key='flags']/value")), function($i){ return $i == "UNSIGNED"; })) == 1,
			(string) current($columnNode->xpath("value[@key='autoIncrement']")) == "1",
			json_decode(preg_replace('/^\'|\'$/', '"', (string) current($columnNode->xpath("value[@key='defaultValue']")))),
			(string) current($columnNode->xpath("value[@key='comment']"))
		);
		return $attribute;
	}

	function surveyType($columnNode) {
		if ($node = current($columnNode->xpath("link[@key='simpleType']"))) {
			$id = trim($node);
			$length = (integer) current($columnNode->xpath("value[@key='length']"));
			$parameters = (string) current($columnNode->xpath("value[@key='datatypeExplicitParams']"));

			if ($id == "com.mysql.rdbms.mysql.datatype.enum") {
				$type = Type::enum($this->parseEnumValues($parameters));
			} else {
				$type = new Type(self::$TYPES[$id], $length, $parameters);
			}

		} elseif ($node = current($columnNode->xpath("link[@key='userType']"))) {

			if (preg_match('/bool(ean)?$/', $node)) {
				$type = new Type(Type::BOOLEAN);
			} else {
				$userTypeNode = current($columnNode->xpath("/.//value[@struct-name='db.UserDatatype'][@id='$node']"));
				$sql = (string) current($userTypeNode->xpath("value[@key='sqlDefinition']"));

				if (preg_match('/^ENUM\([^)]+\)/i', $sql, $m)) {
					$type = Type::enum($this->parseEnumValues($m[1]));
				}
			}
		}

		return $type;
	}

	function surveyRelation($fkNode) {
		$tableId = (string) current($fkNode->xpath("link[@key='referencedTable']"));
		$table = current($fkNode->xpath("/.//value[@struct-name='db.mysql.Table'][@id='$tableId']"));
		$foreignEntityName = (string) current($table->xpath("value[@key='name']"));
		$comments = (string) current($fkNode->xpath("value[@key='comment']"));
		$relation = new Relation(
								$foreignEntityName,
								$comments,
								self::$UPDATE_ACTIONS[(string) current($fkNode->xpath("value[@key='deleteRule']"))],
								self::$UPDATE_ACTIONS[(string) current($fkNode->xpath("value[@key='updateRule']"))]
					);

		$local = array();
		$foreign = array();
		$localColumns = $fkNode->xpath("value[@key='columns']/link");
		$foreignColumns = $fkNode->xpath("value[@key='referencedColumns']/link");

		foreach($localColumns as $column)
			$local[] = (string) $column;

		foreach($foreignColumns as $column)
			$foreign[] = (string) $column;

		foreach($local as $i=>$localKey) {
			$localKey = (string) current($fkNode->xpath("/.//value[@struct-name='db.mysql.Column'][@id='$localKey']/value[@key='name']"));
			$foreignKey = (string) current($fkNode->xpath("/.//value[@struct-name='db.mysql.Column'][@id='{$foreign[$i]}']/value[@key='name']"));
			$relation->addKey($localKey, $foreignKey);
		}

		return $relation;
	}

	function parseEnumValues($definition) {
		$values = preg_split('/\s*,\s*/', preg_replace('/^(?:ENUM)?\(|\)$/i', "", $definition));
		foreach($values as &$value) {
			if (preg_match('/^\'.+\'$/', $value))
				$value = trim($value, "'");
			elseif (is_numeric($value))
				$value = (double) $value;
		}

		return $values;
	}

	function getZippedContent($path) {
		$zip = zip_open($path);
		while($entry = zip_read($zip)) {
			if (zip_entry_name($entry) == "document.mwb.xml") {
				zip_entry_open($zip, $entry);
				$content = zip_entry_read($entry, zip_entry_filesize($entry));
				zip_entry_close($entry);
				return $content;
			}
		}
	}

	function getXMLDocument($content) {
		return new SimpleXMLElement($content);
	}

}
