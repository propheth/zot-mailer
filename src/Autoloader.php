<?php
namespace zot\autoloader;

/*
 * Try to use autoloader for libraries on the entry PHP file because it will not 
 * load ALL the required dependency files at that point. This can cause a stall 
 * or use more memory.
 * Auto loaded dependencies are only loaded as you use the library and the classes are 
 * actually used. 
 * NOTE: Not sure if the `use` keyword at the top actually loads all the classes
 * there and there.
 * 
 * Example usage:
 * require_once(PATH."/Autoloader.php");
 * PSR4 prefix does not start with \\ and ends with \\
 * Autoloader::psr4("libphonenumber\\", APP_PATH."/lib/libphonenumber/src");
*/
class Autoloader {
	private static $_autoloaders = array();
	private $name;
	private $namespacePrefix;
	private $paths;
	private $autoloadCallable;

	static function psr4($psr4Prefix, $paths, $name=null) {
		if(strlen($psr4Prefix) > 0) {
			if(substr($psr4Prefix, -1) !== '\\') {
				throw new \InvalidArgumentException('Autoloader psr-4 prefix must end with a namespace separator'); }
			if($psr4Prefix[0] === '\\') {
				throw new \InvalidArgumentException('Autoloader psr-4 prefix must not have a leading namespace separator'); }
		}

		$key = $name.'|'.$psr4Prefix;
		if(isset(static::$_autoloaders[$key])) {
			// Reuse autoloader
			static::$_autoloaders[$key]->addPaths($paths);
		}
		else {
			$autoloader = new Autoloader($name, $psr4Prefix, $paths); 
			static::$_autoloaders[$key] = $autoloader;
			$autoloader->register();
		}
	}

	private function __construct($name, $namespacePrefix, $paths) {
		$this->name = $name;
		$this->namespacePrefix = $namespacePrefix;
		$this->paths = is_array($paths) ? $paths : array($paths);
		$this->autoloadCallable = array($this, 'psr4Autoload');
	}

	public function addPaths($paths) {
		$paths = is_array($paths) ? $paths : array($paths);
		$this->paths = array_merge($this->paths, $paths);
	}

	public function psr4Autoload($class) {
		$namespacePrefixLen = strlen($this->namespacePrefix);
		if ($this->namespacePrefix === null || 
				strncmp($this->namespacePrefix, $class, $namespacePrefixLen) === 0) {
			$relativeClass = ltrim(str_replace('\\', '/', 
				$this->namespacePrefix === null ? $class : substr($class, $namespacePrefixLen)), '/'); // Convert namespace separator to path separator.
			foreach($this->paths as $path) {
				$file = "$path/$relativeClass.php";
				if(is_readable($file)) {
					//print "Auto-loading $file";
					require_once($file);
					// doesn't need a return true, after every registered autoloader function is called
					// probably class_exists is called to check if the class is loaded and exists.
				}
			}
		}
	}

	public function register() {
		spl_autoload_register($this->autoloadCallable);
	}

	public function unregister() {
		spl_autoload_unregister($this->autoloadCallable);
	}
}

?>