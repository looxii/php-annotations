<?php
namespace mindplay\test\lib;


use mindplay\test\lib\ResultPrinter\CliResultPrinter;
use mindplay\test\lib\ResultPrinter\ResultPrinter;
use mindplay\test\lib\ResultPrinter\WebResultPrinter;

/**
 * This class implements a very simple test suite runner and code
 * coverage benchmarking (where supported by the xdebug extension).
 */
class xTestRunner
{
    protected $rootPath;

    /**
     * Code coverage information tracker.
     *
     * @var \PHP_CodeCoverage
     */
    protected $coverage;

    /**
     * Result printer.
     *
     * @var ResultPrinter
     */
    protected $resultPrinter;

    /**
     * Creates result printer based on environment.
     *
     * @return ResultPrinter
     */
    public static function createResultPrinter()
    {
        if (PHP_SAPI == 'cli') {
            return new CliResultPrinter(new Colors());
        }

        return new WebResultPrinter();
    }

    /**
     * Creates test runner instance.
     *
     * @param string        $rootPath      The absolute path to the root folder of the test suite.
     * @param ResultPrinter $resultPrinter Result printer.
     * @throws \Exception
     */
    public function __construct($rootPath, ResultPrinter $resultPrinter)
    {
        if (!is_dir($rootPath)) {
            throw new \Exception("{$rootPath} is not a directory");
        }

        $this->rootPath = $rootPath;
        $this->resultPrinter = $resultPrinter;

        try {
            $this->coverage = new \PHP_CodeCoverage();
            $this->coverage->filter()->addDirectoryToWhitelist($rootPath);
        } catch (\PHP_CodeCoverage_Exception $e) {
            // can't collect coverage
        }
    }

    /**
     * Returns library root path.
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * Starts coverage information collection for a test.
     *
     * @param string $testName Test name.
     * @return void
     */
    public function startCoverageCollector($testName)
    {
        if (isset($this->coverage)) {
            $this->coverage->start($testName);
        }
    }

    /**
     * Stops coverage information collection.
     *
     * @return void
     */
    public function stopCoverageCollector()
    {
        if (isset($this->coverage)) {
            $this->coverage->stop();
        }
    }

    /**
     * Runs a suite of unit tests
     *
     * @param string $pattern A filename pattern compatible with glob()
     * @return boolean
     * @throws \Exception When invalid test found.
     */
    public function run($pattern)
    {
        $this->resultPrinter->suiteHeader($this, $pattern);

        $passed = true;
        foreach (glob($pattern) as $path) {
            $test = require($path);

            if (!$test instanceof xTest) {
                throw new \Exception("'{$path}' is not a valid unit test");
            }

            $test->setResultPrinter($this->resultPrinter);
            $passed = $passed && $test->run($this);
        }

        $this->resultPrinter->createCodeCoverageReport($this->coverage);
        $this->resultPrinter->suiteFooter($this);

        return $passed;
    }
}
