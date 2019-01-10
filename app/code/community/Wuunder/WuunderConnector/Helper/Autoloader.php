<?php

/**
 * PSR-4 Autoloader, based on
 * https://www.integer-net.com/magento-1-magento-2-using-advanced-autoloading/
 * https://github.com/integer-net/solr-magento1/blob/master/src/app/code/community/IntegerNet/Solr/Helper/Autoloader.php
 */
class Wuunder_WuunderConnector_Helper_Autoloader
{

    /**
     * @var array
     */
    protected $prefixes = array();

    /**
     *
     */
    public static function createAndRegister()
    {
        if (self::_getStoreConfig('wuunderconnector/connect/enabled')) {
            $libBaseDir = 'lib/Wuunder';
            // if ($libBaseDir[0] !== '/') {
                $libBaseDir = Mage::getBaseDir() . DS . $libBaseDir;
            // }

            self::createAndRegisterWithBaseDir($libBaseDir);
        }
    }

    /**
     * Load store config first in case we are in update mode, where store config would not be available
     *
     * @param $path
     *
     * @return bool
     */
    protected static function _getStoreConfig($path)
    {
        static $configLoaded = false;
        if (!$configLoaded && Mage::app()->getUpdateMode()) {
            Mage::getConfig()->loadDb();
            $configLoaded = true;
        }

        return Mage::getStoreConfig($path);
    }

    /**
     * @param $libBaseDir
     */
    public static function createAndRegisterWithBaseDir($libBaseDir)
    {
        static $registered = false;
        if (!$registered) {
            $autoloader = new self;
            $autoloader
                ->addNamespace('Wuunder', $libBaseDir . '/connector-php/src/Wuunder')
                ->register();
            $registered = true;
        }

    }

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix   The namespace prefix.
     * @param string $baseDir A base directory for class files in the
     *                         namespace.
     * @param bool   $prepend  If true, prepend the base directory to the stack
     *                         instead of appending it; this causes it to be searched first rather
     *                         than last.
     *
     * @return $this
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }

        return $this;
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class)
    {
        if (strpos($class, '\\') === false) {
            $class = str_replace('_', '\\', $class);
        }
        
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix         The namespace prefix.
     * @param string $relativeClass The relative class name.
     *
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }
}