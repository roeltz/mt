<?php

namespace mt\exporter\pgsql;
use mt\Attribute;
use mt\Exporter;
use mt\Entity;
use mt\Model;
use mt\Relation;
use mt\Type;
use mt\output\BufferOutput;

class PostgreSQLExporter implements Exporter {

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

		foreach($entity->attributes as $attribute)
			if ($attribute->type->isEnum())
				$this->renderEnum($attribute, $output);

		if ($this->dropTables)
			$output->append("DROP TABLE IF EXISTS \"{$entity->name}\" CASCADE;\n");

		$output->append("CREATE TABLE \"{$entity->name}\" (\n");

		foreach(array_values($entity->attributes) as $i=>$attribute) {
			if ($i > 0) $output->append(",\n");
			$this->renderAttribute($attribute, $output);
		}

		if (count((array) $entity->pk)) {
			$output->append(",\n");
			$pk = join(', ', array_map(function($pk){ return "\"$pk\""; }, $entity->pk));
			$output->append("\tPRIMARY KEY ($pk)");
		} else {
			$output->append("\n");
		}

		if (count($entity->uniqueTuples)) {
			foreach($entity->uniqueTuples as $tuple) {
				$output->append(",\n");
				$unique = join(', ', array_map(function($f){ return "\"$f\""; }, $tuple));
				$output->append("\tUNIQUE ($unique)\n");
			}
		} else {
			$output->append("\n");
		}

		$output->append(") WITHOUT OIDS;\n\n");
	}

	private function renderAttribute(Attribute $attribute, BufferOutput $output) {

		if ($attribute->autoincrement) {
			if ($attribute->isPrimaryKey()) {
				$type = "serial";
			} else {
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
					$type = "integer";
					break;
				case Type::INTEGER:
				case Type::SMALLINT:
					$type = "integer";
					break;
				case Type::BIG_INTEGER:
					$type = "bigint";
					break;
				case Type::DOUBLE:
					$type = "double precision";
					break;
				case Type::DATETIME:
				case Type::DATE:
					$type = "timestamp without time zone";
					break;
				case Type::BOOLEAN:
					$type = "boolean";
					break;
				case Type::CUSTOM:
					$type = $attribute->type->arguments;
					break;
			}
		}

		$output->append("\t\"{$attribute->name}\" $type");

		if (!$attribute->nullable)
			$output->append(" NOT NULL");

		if ($type != "serial" && !is_null($attribute->defaultValue))
			$output->append(" DEFAULT ".$this->escapeValue($attribute->defaultValue));
	}

	private function renderEnum(Attribute $attribute, BufferOutput $output) {
		$self = $this;
		$values = join(', ', array_map(
			function($v) use($self){ return $self->escapeValue($v); },
			$attribute->type->arguments
		));
		$name = "{$attribute->entity->name}_{$attribute->name}_enum";
		$output
			->append("DROP TYPE IF EXISTS $name;\n")
			->append("CREATE TYPE $name AS ENUM ($values);\n\n")
		;
		$attribute->type->name = Type::CUSTOM;
		$attribute->type->arguments = $name;
	}

	private function renderRelation(Relation $relation, BufferOutput $output) {
		$local = join(', ', array_keys($relation->keys));
		$foreign = join(', ', $relation->keys);
		$output->append("ALTER TABLE \"{$relation->localEntity->name}\" ADD FOREIGN KEY (\"$local\") REFERENCES \"{$relation->foreignEntityName}\" ($foreign) ON DELETE {$relation->deletion} ON UPDATE {$relation->update};\n\n");
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
