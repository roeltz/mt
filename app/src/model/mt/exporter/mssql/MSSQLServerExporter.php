<?php

namespace mt\exporter\mssql;
use mt\Attribute;
use mt\Exporter;
use mt\Entity;
use mt\Model;
use mt\Relation;
use mt\Type;
use mt\output\BufferOutput;

class MSSQLServerExporter implements Exporter {

	protected $dropTables;

	function __construct($dropTables = false) {
		$this->dropTables = $dropTables;
	}

	function getOutput(Model $model) {
		$output = new BufferOutput;

		foreach($model->entities as $entity)
			$this->renderEntity($entity, $output);

		foreach($model->entities as $entity)
			foreach($entity->relations as $relation)
				$this->renderRelation($relation, $output);

		return $output;
	}

	function getDefaultOuputPath($path) {
		return "$path.sql";
	}

	private function renderEntity(Entity $entity, BufferOutput $output) {

		if ($this->dropTables)
			$output->append("DROP TABLE IF EXISTS [{$entity->name}] CASCADE;\n");

		$output->append("CREATE TABLE [{$entity->name}] (\n");

		foreach(array_values($entity->attributes) as $i=>$attribute) {
			if ($i > 0) $output->append(",\n");
			$this->renderAttribute($attribute, $output);
		}

		if (count((array) $entity->pk)) {
			$output->append(",\n");
			$pk = join(', ', array_map(function($pk){ return "[$pk]"; }, $entity->pk));
			$output->append("\tPRIMARY KEY ([$pk])");
		} else {
			$output->append("\n");
		}

		if (count($entity->uniqueTuples)) {
			foreach($entity->uniqueTuples as $tuple) {
				$output->append(",\n");
				$unique = join(', ', array_map(function($f){ return "[$f]"; }, $tuple));
				$output->append("\tUNIQUE ([$unique])\n");
			}
		} else {
			$output->append("\n");
		}

		$output->append(");\n\n");
	}

	private function renderAttribute(Attribute $attribute, BufferOutput $output) {

		$check = false;

		if ($attribute->autoincrement) {
			if ($attribute->isPrimaryKey()) {
				$type = "bigint";
			}
		} else {
			switch($attribute->type->name) {
				case Type::CHAR:
					$type = "char({$attribute->type->length})";
					break;
				case Type::VARCHAR:
					$type = "varchar({$attribute->type->length})";
					break;
				case Type::TEXT:
					$type = "text";
					break;
				case Type::TINYINT:
					$type = "tinyint";
					break;
				case Type::INTEGER:
					$type = "int";
					break;
				case Type::SMALLINT:
					$type = "smallint";
					break;
				case Type::BIG_INTEGER:
					$type = "bigint";
					break;
				case Type::DOUBLE:
					$type = "float";
					break;
				case Type::DATETIME:
					$type = "datetime";
					break;
				case Type::DATE:
					$type = "date";
					break;
				case Type::BOOLEAN:
					$type = "boolean";
					break;
				case Type::ENUM:
					$length = max(array_map('strlen', $attribute->type->arguments));
					$values = join(', ', array_map(array($this, 'escapeValue'), $attribute->type->arguments));
					$check = "[{$attribute->name}] IN ($values)";
					$type = "varchar($length)";
					break;
				case Type::CUSTOM:
					$type = $attribute->type->arguments;
					break;
			}
		}

		$output->append("\t[{$attribute->name}] $type");

		if (!$attribute->nullable)
			$output->append(" NOT NULL");
		
		if ($check)
			$output->append(" CHECK ($check)");

		if ($attribute->autoincrement)
			$output->append(" IDENTITY");
		elseif (!is_null($attribute->defaultValue))
			$output->append(" DEFAULT ".$this->escapeValue($attribute->defaultValue));
	}

	private function renderRelation(Relation $relation, BufferOutput $output) {
		$local = join(', ', array_keys($relation->keys));
		$foreign = join(', ', $relation->keys);
		$output->append("ALTER TABLE [{$relation->localEntity->name}] ADD FOREIGN KEY ([$local]) REFERENCES [{$relation->foreignEntityName}] ($foreign) ON DELETE {$relation->deletion} ON UPDATE {$relation->update};\n\n");
	}

	private function escapeValue($value) {
		if (is_string($value))
			return "'".str_replace("'", "''", $value)."'";
		elseif (is_bool($value))
			return $value ? "TRUE" : "FALSE";
		else
			return $value;
	}

}
