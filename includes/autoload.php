<?php
require "includes/functions/async.php";
/**
 * Declare and install the autoloader.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
function autoload($class)
{
    // Replace "\" in the filename with "/" to prevent
    // crashes on non-Windows operating systems.
    $filename = str_replace("\\", "/", $class);

    // Scan the file system for the requested module.
    if (file_exists_on_server("modules/{$filename}.php"))
    {
        require "modules/{$filename}.php";
    }
    else if (file_exists_on_server("modules/generated/{$filename}.php"))
    {
        require "modules/generated/{$filename}.php";
    }
    else if ("Zylon/Model/" == substr($filename, 0, 12))
    {
        $file = substr($filename, 13, strlen($filename));

        require "models/$file.php";
    }
    else if ("Zylon/Controller" == substr($filename, 0, 16))
    {
        $file = substr($filename, 17, strlen($filename));

        require "controllers/$file.php";
    }

    // Implement the fake magic method __initStatic
    // for automatically initialising static classses.
    if (method_exists($class, "__initStatic"))
    {
        $class::__initStatic();
    }
}

/**
 * Checks if a file exists, relative to the document root.
 * 
 * This is required because PHP's default behaviour may, but doesn't
 * always, resort to the document root. This is a safer function as a result.
 * 
 * @author Taniko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */
function file_exists_on_server(string $filename): bool
{
    return file_exists($_SERVER["DOCUMENT_ROOT"] . "/" . $filename);
}

spl_autoload_register("autoload");