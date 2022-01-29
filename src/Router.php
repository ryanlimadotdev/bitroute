<?php

declare(strict_types=1);

/**
 * @author Ryan Lima <ryanlimati@protonmail.com>
 */

namespace BitRoute\BitRoute;
use \BitRoute\BitRoute\Exception\RouterException;

final class Router
{
	private string $uri;
	private int $fromFileCount = 0;
	/** @var array<string|array> */
	private array $controllers = [];
	/** @var array<string> */
	private array $endpoints = [];
	private string $requestMethod;
	private mixed $response = '';
	/** @var array<string> */
	private array $postForm;
	private Controller $defaultController;


	/** 
	 * @param array<string> $serverParams 
	 * @param array<string> $postForm 
	*/
	public function __construct(array $serverParams, array $postForm)
	{
		$this->requestMethod = $serverParams['REQUEST_METHOD'];
		$this->uri = $serverParams['REQUEST_URI'];
		$this->postForm = $postForm;
	}


	public function addRoutesFromFile(string $file): void
	{
		$this->fromFileCount++;
		if(!is_file($file)) {
			throw new RouterException(1, "Can't locate the file!");
		}
		$routeGroups = json_decode(file_get_contents($file), true);
		try {

			$routeGroup = $routeGroups[$this->requestMethod] ?? [];
			array_map($this->addEndpoint(...), $routeGroup);
			return;

		} catch (\Exception $e) {
			throw new RouterException(2, "Syntax error!");
		}
	}

	public function addEndpoint(array $route): void
	{
		$this->endpoints[$route['endpoint']] = $route['controller']['num_args'] ?? 0;
		$this->controllers[$route['endpoint']] = $route['controller'];
	}

	public function addRoutesFromFolder(string $folder): void
	{
		$files = [];
		$folder = realpath($folder);
		$handle = opendir($folder);
		while (false !== ($file = readdir($handle))) {
			if(is_file("$folder/$file")){
				$files[] = "$folder/$file";
			}
		}
		array_map($this->addRoutesFromFile(...), $files);
		return;
	}

	public function dispatch(): void
	{
		$requestArguments = null;
		if ($this->fromFileCount < 1) {
			throw new Exception(2,'No route added');
		}		
		foreach($this->endpoints as $endpoint => $argsCount) {
			$endpointPos = strpos($this->uri, $endpoint);
			if ($endpointPos === false) {
				continue;
			}
			$nextChar = substr($this->uri, strlen($endpoint), 1);
			if (!( $nextChar === '/' OR $nextChar=== '')) {
				continue;
			}

			if($this->requestMethod === 'GET' OR $this->requestMethod === 'DELETE') {

				$uriEndpointRemoved = str_replace($endpoint, '', $this->uri);
				$arguments = $this->stripArgsToArray($uriEndpointRemoved);

				if (count($arguments) !== $argsCount) {
					continue;
				}
				$this->initController($endpoint, $arguments);
				return;
			}
			if ($this->requestMethod === 'POST' OR $this->requestMethod === 'PUT') {
				$this->initController($endpoint, $this->postForm);
				return;
			}
		}

		$controller = $this->defaultController;
		$this->response = $controller->execute(null);
		return;
	}

	public function initController(string $endpoint, array $arguments): void
	{
		try {
			$controller = new Controller($this->controllers[$endpoint]);
			$this->response = $controller->execute($arguments);
		} catch (\Exception) {
			$this->response = "";
		}
		return;
	}


	public function defaultController(array|string $from, bool $isFromFile = false): void
	{
		if ($isFromFile) {
			$from = json_decode(file_get_contents($from), true);
		}
		$this->defaultController = new Controller($from);
	}
	
	/** @return array<string> */
	public function stripArgsToArray(string $string): array
	{
		return array_values(array_filter(explode('/', $string)));
	}

	public function getResponse(): mixed
	{
		return $this->response;
	}
}
