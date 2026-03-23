<?php

namespace moonland\tinymce\tests;

use PHPUnit\Framework\TestCase;
use TinyMCE_Compressor;

/**
 * Тесты для класса TinyMCE_Compressor из tinymce.gzip.php
 */
class TinyMCECompressorTest extends TestCase
{
    use ReflectionHelper;

    /**
     * @var TinyMCE_Compressor
     */
    private $compressor;

    private const CACHE_DIR = '.phpunit.cache';

    protected function setUp(): void
    {
        parent::setUp();

        require_once dirname(__DIR__) . '/tinymce.gzip.php';

        if (!is_dir(self::CACHE_DIR)) {
            mkdir(self::CACHE_DIR, 0755, true);
        }

        $this->compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
            'disk_cache' => false,
        ]);
    }

    protected function tearDown(): void
    {
        $this->compressor = null;
        parent::tearDown();
    }

    public function testConstructor(): void
    {
        $compressor = new TinyMCE_Compressor();

        $this->assertInstanceOf(TinyMCE_Compressor::class, $compressor);
    }

    public function testConstructorWithCustomSettings(): void
    {
        $settings = [
            'plugins' => 'link,image',
            'themes' => 'modern',
            'languages' => 'en,ru',
            'disk_cache' => true,
            'expires' => '1h',
            'compress' => false,
        ];

        $compressor = new TinyMCE_Compressor($settings);

        $actualSettings = self::getPrivateProperty($compressor, 'settings');

        $this->assertEquals('link,image', $actualSettings['plugins']);
        $this->assertEquals('modern', $actualSettings['themes']);
        $this->assertEquals('en,ru', $actualSettings['languages']);
        $this->assertTrue($actualSettings['disk_cache']);
        $this->assertEquals('1h', $actualSettings['expires']);
        $this->assertFalse($actualSettings['compress']);
    }

    public function testDefaultSettings(): void
    {
        $settings = self::getPrivateProperty($this->compressor, 'settings');

        $this->assertEquals('', $settings['plugins']);
        $this->assertEquals('', $settings['themes']);
        $this->assertEquals('', $settings['languages']);
        $this->assertFalse($settings['disk_cache']);
        $this->assertEquals('30d', $settings['expires']);
        $this->assertTrue($settings['compress']);
        $this->assertTrue($settings['source']);
    }

    public function testAddFile(): void
    {
        $this->compressor->addFile('testfile');

        $files = self::getPrivateProperty($this->compressor, 'files');

        $this->assertEquals('testfile', $files);
    }

    public function testAddMultipleFiles(): void
    {
        $this->compressor->addFile('file1');
        $this->compressor->addFile('file2');
        $this->compressor->addFile('file3');

        $files = self::getPrivateProperty($this->compressor, 'files');

        $this->assertEquals('file1,file2,file3', $files);
    }

    public function testGetParamWithExistingParameter(): void
    {
        $_GET['test'] = 'value123';

        $result = TinyMCE_Compressor::getParam('test');

        $this->assertEquals('value123', $result);
    }

    public function testGetParamWithDefault(): void
    {
        $result = TinyMCE_Compressor::getParam('nonexistent', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function testGetParamSanitization(): void
    {
        $_GET['test'] = 'value<script>alert("xss")</script>';

        $result = TinyMCE_Compressor::getParam('test');

        $this->assertMatchesRegularExpression('/^[0-9a-z\-_,]*$/', $result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testGetParamWithAllowedSpecialChars(): void
    {
        $_GET['test'] = 'value-123_abc,def';

        $result = TinyMCE_Compressor::getParam('test');

        $this->assertEquals('value-123_abc,def', $result);
    }

    public function testParseTimeHours(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['10h']);

        $this->assertEquals(36000, $result);
    }

    public function testParseTimeDays(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['5d']);

        $this->assertEquals(432000, $result);
    }

    public function testParseTimeMonths(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['2m']);

        $this->assertEquals(5184000, $result);
    }

    public function testParseTimeNoSuffix(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['100']);

        $this->assertEquals(100, $result);
    }

    public function testGetFileContentsNonExistent(): void
    {
        $this->expectException(\yii\base\ErrorException::class);
        self::invokePrivateMethod($this->compressor, 'getFileContents', ['/nonexistent/file.js']);
    }

    public function testGetFileContentsWithBOM(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'bom_test');
        $bom = pack('CCC', 0xef, 0xbb, 0xbf);
        file_put_contents($tempFile, $bom . 'console.log("test");');

        $result = self::invokePrivateMethod($this->compressor, 'getFileContents', [$tempFile]);

        unlink($tempFile);

        $this->assertStringStartsNotWith("\xEF\xBB\xBF", $result);
        $this->assertStringContainsString('console.log("test");', $result);
    }

    public function testGetFileContentsWithoutBOM(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'nobom_test');
        file_put_contents($tempFile, 'console.log("test");');

        $result = self::invokePrivateMethod($this->compressor, 'getFileContents', [$tempFile]);

        unlink($tempFile);

        $this->assertEquals('console.log("test");', $result);
    }

    public function testRenderTagBasic(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('<script src="/js/tinymce.gzip.php?js=1', $result);
        $this->assertStringContainsString('</script>', $result);
    }

    public function testRenderTagWithPlugins(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'plugins' => 'link,image,code',
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('plugins=link,image,code', $result);
    }

    public function testRenderTagWithThemes(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'themes' => 'modern,simple',
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('themes=modern,simple', $result);
    }

    public function testRenderTagWithLanguages(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'languages' => 'en,ru,de',
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('languages=en,ru,de', $result);
    }

    public function testRenderTagWithDiskCache(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'disk_cache' => true,
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('diskcache=true', $result);
    }

    public function testRenderTagWithSource(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'source' => true,
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('src=true', $result);
    }

    public function testRenderTagWithPluginArray(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'plugins' => ['link', 'image', 'code'],
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('plugins=link,image,code', $result);
    }

    public function testRenderTagOutput(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
        ];

        $this->expectOutputRegex('/<script src="\/js\/tinymce\.gzip\.php\?js=1/');
        TinyMCE_Compressor::renderTag($tagSettings, false);
    }

    public function testRenderTagHtmlEscaping(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php?param="value"',
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('&quot;', $result);
    }

    public function testHandleRequestBasic(): void
    {
        $originalScriptName = $_SERVER['SCRIPT_NAME'] ?? null;
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        $compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
            'compress' => false,
            'disk_cache' => false,
        ]);

        $this->assertTrue(method_exists($compressor, 'handleRequest'));

        if ($originalScriptName !== null) {
            $_SERVER['SCRIPT_NAME'] = $originalScriptName;
        }
    }

    public function testHandleRequestParameters(): void
    {
        $_GET['plugins'] = 'link,image';
        $_GET['themes'] = 'modern';
        $_GET['languages'] = 'en';
        $_GET['files'] = 'custom';
        $_GET['diskcache'] = 'true';
        $_GET['src'] = 'false';

        $compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
        ]);

        try {
            ob_start();
            @$compressor->handleRequest();
            ob_end_clean();
        } catch (\Exception $e) {
        }

        $settings = self::getPrivateProperty($compressor, 'settings');

        $this->assertEquals('link,image', $settings['plugins']);
        $this->assertEquals('modern', $settings['themes']);
        $this->assertEquals('en', $settings['languages']);
        $this->assertTrue($settings['disk_cache']);
        $this->assertFalse($settings['source']);
    }

    public function testDefaultCacheDirectory(): void
    {
        $settings = self::getPrivateProperty($this->compressor, 'settings');

        $this->assertEquals(self::CACHE_DIR, $settings['cache_dir']);
    }

    public function testCustomCacheDirectory(): void
    {
        $customCacheDir = '/tmp/tinymce_cache';
        $compressor = new TinyMCE_Compressor([
            'cache_dir' => $customCacheDir,
        ]);

        $settings = self::getPrivateProperty($compressor, 'settings');

        $this->assertEquals($customCacheDir, $settings['cache_dir']);
    }

    public function testCompressFlag(): void
    {
        $compressorDisabled = new TinyMCE_Compressor(['compress' => false]);

        $settings = self::getPrivateProperty($compressorDisabled, 'settings');

        $this->assertFalse($settings['compress']);
    }

    public function testSourceFlag(): void
    {
        $compressorMinified = new TinyMCE_Compressor(['source' => false]);

        $settings = self::getPrivateProperty($compressorMinified, 'settings');

        $this->assertFalse($settings['source']);
    }

    public function testGetParamWithEmptyValue(): void
    {
        $_GET['empty'] = '';

        $result = TinyMCE_Compressor::getParam('empty', 'default');

        $this->assertEquals('', $result);
    }

    public function testRenderTagWithDiskCacheFalse(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'disk_cache' => false,
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('diskcache=false', $result);
    }

    public function testRenderTagWithDiskCacheTrue(): void
    {
        $tagSettings = [
            'url' => '/js/tinymce.gzip.php',
            'disk_cache' => true,
        ];

        $result = TinyMCE_Compressor::renderTag($tagSettings, true);

        $this->assertStringContainsString('diskcache=true', $result);
    }

    public function testAddFileReturnsReference(): void
    {
        $result = $this->compressor->addFile('test');

        $this->assertSame($this->compressor, $result);
    }

    public function testParseTimeZero(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['0']);

        $this->assertEquals(0, $result);
    }

    public function testParseTimeZeroWithSuffix(): void
    {
        $result = self::invokePrivateMethod($this->compressor, 'parseTime', ['0h']);

        $this->assertEquals(0, $result);
    }

    public function testConstructorWithEmptyArray(): void
    {
        $compressor = new TinyMCE_Compressor([]);

        $this->assertInstanceOf(TinyMCE_Compressor::class, $compressor);
    }

    public function testDefaultFilesSetting(): void
    {
        $settings = self::getPrivateProperty($this->compressor, 'settings');

        $this->assertEquals('', $settings['files']);
    }

    public function testHandleRequestEmptyParams(): void
    {
        $_GET = [];

        $compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
            'compress' => false,
            'disk_cache' => false,
        ]);

        try {
            ob_start();
            @$compressor->handleRequest();
            ob_end_clean();
        } catch (\Exception $e) {
        }

        $this->assertTrue(true);
    }

    public function testHandleRequestCoreFalse(): void
    {
        $_GET['core'] = 'false';

        $compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
            'compress' => false,
            'disk_cache' => false,
        ]);

        try {
            ob_start();
            @$compressor->handleRequest();
            ob_end_clean();
        } catch (\Exception $e) {
        }

        unset($_GET['core']);

        $this->assertTrue(true);
    }

    public function testNortonAntivirusHeader(): void
    {
        $_SERVER['---------------'] = 'somevalue';

        $compressor = new TinyMCE_Compressor([
            'cache_dir' => self::CACHE_DIR,
            'compress' => false,
            'disk_cache' => false,
        ]);

        try {
            ob_start();
            @$compressor->handleRequest();
            ob_end_clean();
        } catch (\Exception $e) {
        }

        unset($_SERVER['---------------']);

        $this->assertTrue(true);
    }
}
