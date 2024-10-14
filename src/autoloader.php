<?php
namespace zot;

\zot\autoloader("zot\\mailer\\", dirname(__FILE__));
\zot\autoloader("zot\\mailer\\transport\\", dirname(__FILE__));

/*
 * Try to use autoloader for libraries on the entry PHP file because it will not 
 * load ALL the required dependency files at that point. This can cause a stall 
 * or use more memory.
 * Auto loaded dependencies are only loaded as you use the library and the classes are 
 * actually used. 
 * NOTE: The `use` keyword at the top does not cause autoload to trigger
 * 
 * Example usage:
 * require_once(PATH."/autoloader.php");
 * PSR4 prefix does not start with \\ and ends with \\
 * \zot\autoloader("libphonenumber\\", APP_PATH."/lib/libphonenumber/src");
*/
function autoloader($namespacePrefix, $paths) {
	if(strlen($namespacePrefix) > 0) {
		if(substr($namespacePrefix, -1) !== '\\') {
			throw new \InvalidArgumentException("Autoloader psr-4 prefix $namespacePrefix must end with a namespace separator"); }
		if($namespacePrefix[0] === '\\') {
			throw new \InvalidArgumentException("Autoloader psr-4 prefix $namespacePrefix must not have a leading namespace separator"); }
	}

	$paths = is_array($paths) ? $paths : array($paths);

	spl_autoload_register(function ($class) use ($namespacePrefix, $paths) {
		$namespacePrefixLen = strlen($namespacePrefix);
		if ($namespacePrefix === null || 
				strncmp($namespacePrefix, $class, $namespacePrefixLen) === 0) {
			$relativeClass = ltrim(str_replace('\\', '/', 
				$namespacePrefix === null ? $class : substr($class, $namespacePrefixLen)), '/'); // Convert namespace separator to path separator.
			foreach($paths as $path) {
				$file = "$path/$relativeClass.php";
				if(is_readable($file)) {
					//print "Auto-loading $file";
					require_once($file);
					// doesn't need a return true, after every registered autoloader function is called
					// probably class_exists is called to check if the class is loaded and exists.
				}
			}
		}
	});
}

?>