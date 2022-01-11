<?php

declare(strict_types=1);

namespace BitRoute\BitRoute;

final class Controller
{
	/** @param array<string|int> $controller  */
	public function __construct(
		private array $controller
	) 
	{ 
	}

	/** @param array<string> $arguments [The request uri params] */
	public function execute(array $arguments): mixed
	{
		return match ($this->controller['type']) {
			'object_method' => $this->responseFromObjectMethod($arguments),
			'callable' => $this->responseFromCallable($arguments),
			'static_method' => $this->responseFromStaticMethod($arguments),
			default => null,
		};
	}

	public function responseFromObjectMethod(array $arguments): mixed
	{
		// Extract the array values as variable named as they key
		extract($this->controller, EXTR_OVERWRITE);
		$object = new $class(...$constructor);
		return $object->$method(...$arguments);
	}

	public function responseFromCallable(array $arguments): mixed
	{
		extract($this->controller, EXTR_OVERWRITE);
		require_once $file;
		return $name(...$arguments);
	}

	public function responseFromStaticMethod(array $arguments): mixed
	{
		extract($this->controller, EXTR_OVERWRITE);
		return $class::$method(... $arguments);
	}

}