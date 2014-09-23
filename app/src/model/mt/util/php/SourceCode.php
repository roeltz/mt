<?php

namespace mt\util\php;

class SourceCode {

	private $class;

	function __construct(PHPClass $class) {
		$this->class = $class;
	}

	function __toString() {
		try {
			return $this->generate();
		} catch(\Exception $e) {
			echo $e;
		}
	}

	function generate() {
		$class = $this->renderClass();
		if ($namespace = $this->renderNamespace())
			$namespace .= "\n\n";
		if ($dependencies = $this->renderDependencies())
			$dependencies .= "\n\n";
		return <<<SOURCE
<?php

$namespace$dependencies$class
SOURCE;
	}

	function renderClass() {
		$self = $this;
		$class = $this->class;

		if ($parent = $this->class->parent)
			$extends = " extends {$parent->name}";

		if ($interfaces = $this->class->implementedInterfaces)
			$implements = " implements " . join(", ", array_map(function($i) use($class){ return $class->dependencies->getAlias($i); }, $interfaces));

		if ($docBlock = $this->renderDocBlock($this->class->docBlock))
			$docBlock .= "\n";

		if ($this->class->constants)
			$constants = join("\n", array_map(function($c) use($self){ return $self->renderConstant($c); }, $this->class->constants));

		if ($this->class->properties)
			$properties = join("\n\n", array_map(function($p) use($self){ return $self->renderProperty($p); }, $this->class->properties));

		if ($this->class->methods)
			$methods = join("\n\n", array_map(function($m) use($self){ return $self->renderMethod($m); }, $this->class->methods));

		if ($body = join("\n\n", array_filter(array(@$constants, @$properties, @$methods))))
			$body = "\n" . $this->tab($body) . "\n";

		if (@$constants || @$properties)
			$body = "\n$body";

		return @<<<CLASS
{$docBlock}class {$this->class->name}$extends$implements {{$body}}
CLASS;
	}

	function renderNamespace() {
		if ($this->class->namespace)
			return "namespace {$this->class->namespace};";
	}

	function renderDependencies() {
		$deps = array();
		foreach($this->class->dependencies->dependencies as $alias=>$fqn) {
			$ns = join("\\", array_slice(explode("\\", $fqn), 0, -1));
			if ($ns == $this->class->namespace) continue;

			if (preg_match("/$alias\$/", $fqn))
				$deps[] = "use $fqn;";
			else
				$deps[] = "use $fqn as $alias;";
		}
		return join("\n", $deps);
	}

	function renderDocBlock(DocBlock $docBlock) {
		$self = $this;

		if ($c = $docBlock->comments) {
			$c = join("\n * ", explode("\n", $c));
			$comments = " * $c";
		}

		if ($a = $docBlock->annotations)
			$annotations = " * " . join("\n * ", array_map(function($a) use($self){ return $self->renderAnnotation($a); }, $a));

		if (@$comments && @$annotations)
			$comments = "$comments\n * \n";


		if ($body = @"$comments$annotations")
			$body = "\n$body\n";

		if ($c || $a)
			return @<<<DOCBLOCK
/**$body */
DOCBLOCK;
	}

	function renderAnnotation(Annotation $annotation) {
		$code = "@{$annotation->class}";
		if ($annotation->parameters) {
			$count = count($annotation->parameters);
			if (($count == 1) && isset($annotation->parameters['value'])) {
				$value = $this->renderAnnotationValue($annotation->parameters['value']);
				$code = "$code($value)";
			} elseif ($count >= 1) {
				$parameters = array();
				foreach($annotation->parameters as $key=>$value)
					$parameters[] = "$key = " . $this->renderAnnotationValue($value);
				$code = "$code(" . join(", ", $parameters) . ")";
			}
		}
		return $code;
	}

	function renderAnnotationValue($value) {
		$self = $this;
		switch(gettype($value)) {
			case "NULL":
				return "null";
			case "string":
				return "\"$value\"";
			case "integer":
			case "double":
				return (string) $value;
			case "boolean":
				return $value ? "true" : "false";
			case "array":
				$array = array();
				if (is_assoc($value)) {
					foreach($value as $k=>$v)
						$array[] = "$k = " . $self->renderAnnotationValue($v);
				} else {
					foreach($value as $v)
						$array[] = $self->renderAnnotationValue($v);
				}
				return "{" . join(", ", $array) . "}";
			case "object":
				if ($value instanceof Annotation)
					return $this->renderAnnotation($value);
		}
	}

	function renderConstant(Constant $constant) {
		return "const {$constant->name} = " . str_replace("\\/", "/", json_encode($constant->value)) . ";";
	}

	function renderProperty(Property $property) {
		if ($docBlock = $this->renderDocBlock($property->docBlock))
			$docBlock .= "\n";
		if ($property->isStatic)
			$static = "static ";
		if ($property->hasDefaultValue)
			$defaultValue = " = " . $this->renderDefaultValue($property->defaultValue);
		return @"$docBlock$static{$property->access} \${$property->name}$defaultValue;";
	}

	function renderMethod(Method $method) {
		$self = $this;
		if ($docBlock = $this->renderDocBlock($method->docBlock))
			$docBlock .= "\n";
		if ($method->isStatic)
			$static = " static";
		if ($method->parameters)
			$parameters = join(", ", array_map(function($p) use($self){ return $self->renderParameter($p); }, $method->parameters));
		if ($method->isAbstract)
			$body = ";";
		else
			$body = " {\n" . $this->tab(rtrim($method->body)) . "\n}";
		return @<<<METHOD
$docBlock{$method->access}$static function {$method->name}($parameters)$body
METHOD;
	}

	function renderParameter(Parameter $parameter) {
		if ($typeHint = $parameter->typeHint) {
			$segments = explode("\\", $typeHint);
			$class = array_pop($segments);
			$ns = join("\\", $segments);
			$typeHint = $parameter->method->dependencies->getAvailableAliasedName($class, $ns);
			$typeHint .= " ";
		}
		if ($parameter->hasDefaultValue)
			$defaultValue = " = " . $this->renderDefaultValue($parameter->defaultValue);
		return @"$typeHint\${$parameter->name}$defaultValue";
	}

	function renderDefaultValue($value) {
		if (is_array($value))
			return "array()";
		else
			return str_replace("\\/", "/", json_encode($value));
	}

	function tab($string) {
		$lines = array();
		foreach(explode("\n", $string) as $line)
			$lines[] = $line ? "\t$line" : "";
		return join("\n", $lines);
	}
}
