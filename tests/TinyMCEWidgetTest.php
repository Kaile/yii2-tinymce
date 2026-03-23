<?php

namespace moonland\tinymce\tests;

use moonland\tinymce\TinyMCE;
use moonland\tinymce\TinyMCEAsset;
use moonland\tinymce\TinyMCELangAsset;
use Yii;
use yii\web\View;
use yii\base\Model;
use PHPUnit\Framework\TestCase;

/**
 * Тесты для виджета TinyMCE
 */
class TinyMCEWidgetTest extends TestCase
{
    use ReflectionHelper;

    /**
     * Тест базовой инициализации виджета
     */
    public function testWidgetInitialization(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
        ]);

        $this->assertEquals('test-content', $widget->name);
        $this->assertEquals('en_US', $widget->language);
        $this->assertEquals(300, $widget->height);
        $this->assertEquals('silver', $widget->theme);
    }

    /**
     * Тест настройки языка
     */
    public function testLanguageConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'language' => 'ru_RU',
        ]);

        $this->assertEquals('ru_RU', $widget->language);
    }

    /**
     * Тест настройки высоты редактора
     */
    public function testHeightConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'height' => 500,
        ]);

        $this->assertEquals(500, $widget->height);
    }

    /**
     * Тест настройки темы
     */
    public function testThemeConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'theme' => 'oxide',
        ]);

        $this->assertEquals('oxide', $widget->theme);
    }

    /**
     * Тест настройки плагинов
     */
    public function testPluginConfiguration(): void
    {
        $customPlugins = ['link', 'image', 'code'];
        $widget = new TinyMCE([
            'name' => 'test-content',
            'plugins' => $customPlugins,
        ]);

        $this->assertEquals($customPlugins, $widget->plugins);
    }

    /**
     * Тест настройки панели инструментов
     */
    public function testToolbarConfiguration(): void
    {
        $customToolbar = ['undo redo', 'bold italic', 'link'];
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toolbar' => $customToolbar,
        ]);

        $this->assertEquals($customToolbar, $widget->toolbar);
    }

    /**
     * Тест удаления элементов панели инструментов
     */
    public function testRemoveToolbarConfiguration(): void
    {
        $removeToolbar = ['print', 'preview'];
        $widget = new TinyMCE([
            'name' => 'test-content',
            'removeToolbar' => $removeToolbar,
        ]);

        $this->assertEquals($removeToolbar, $widget->removeToolbar);
    }

    /**
     * Тест настройки пользовательской конфигурации
     */
    public function testCustomConfig(): void
    {
        $customConfig = [
            'menubar' => false,
            'statusbar' => false,
            'custom_setting' => 'custom_value',
        ];

        $widget = new TinyMCE([
            'name' => 'test-content',
            'config' => $customConfig,
        ]);

        $widget->init();

        $this->assertFalse($widget->config['menubar']);
        $this->assertFalse($widget->config['statusbar']);
        $this->assertEquals('custom_value', $widget->config['custom_setting']);
    }

    /**
     * Тест магического метода __set для пользовательских настроек
     */
    public function testMagicSetter(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->menubar = false;
        $widget->statusbar = true;

        $this->assertFalse($widget->config['menubar']);
        $this->assertTrue($widget->config['statusbar']);
    }

    /**
     * Тест включения режима переключения (toggle)
     */
    public function testToggleConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'tinyStart' => false,
            ],
        ]);

        $widget->init();

        $toggle = self::getPrivateProperty($widget, 'toggle');

        $this->assertTrue($toggle['active']);
        $this->assertFalse($toggle['tinyStart']);
        $this->assertArrayHasKey('id', $toggle);
    }

    /**
     * Тест включения расширенной вкладки изображения
     */
    public function testShowAdvancedImageTab(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'showAdvancedImageTab' => true,
        ]);

        $widget->init();

        $this->assertTrue($widget->showAdvancedImageTab);
        $this->assertEquals('true', $widget->config['image_advtab']);
    }

    /**
     * Тест отключения расширенной вкладки изображения
     */
    public function testDisableAdvancedImageTab(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'showAdvancedImageTab' => false,
        ]);

        $widget->init();

        $this->assertFalse($widget->showAdvancedImageTab);
        $this->assertArrayNotHasKey('image_advtab', $widget->config);
    }

    /**
     * Тест настройки шаблонов
     */
    public function testTemplatesConfiguration(): void
    {
        $templates = [
            ['title' => 'Template 1', 'content' => 'Content 1'],
            ['title' => 'Template 2', 'content' => 'Content 2'],
        ];

        $widget = new TinyMCE([
            'name' => 'test-content',
            'templates' => $templates,
        ]);

        $this->assertEquals($templates, $widget->templates);
    }

    /**
     * Тест настройки селектора
     */
    public function testSelectorConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'selector' => '.custom-editor',
        ]);

        $this->assertEquals('.custom-editor', $widget->selector);
    }

    /**
     * Тест настройки имени функции
     */
    public function testFunctionNameConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'functionName' => 'initMyEditor',
        ]);

        $this->assertEquals('initMyEditor', $widget->functionName);
    }

    /**
     * Тест получения URL языка
     */
    public function testGetLanguageUrl(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);

        $url = @self::invokePrivateMethod($widget, 'getLanguageUrl');
        $this->assertTrue($url === false || is_string($url));
    }

    /**
     * Тест рендеринга textarea без модели
     */
    public function testRenderTextareaWithoutModel(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'value' => 'Initial content',
        ]);

        $widget->init();

        $output = self::invokePrivateMethod($widget, 'renderInput');

        $this->assertStringContainsString('textarea', $output);
        $this->assertStringContainsString('name="test-content"', $output);
        $this->assertStringContainsString('Initial content', $output);
    }

    /**
     * Тест рендеринга textarea с моделью
     */
    public function testRenderTextareaWithModel(): void
    {
        $model = new TestModel();
        $model->content = 'Model content';

        $widget = new TinyMCE([
            'model' => $model,
            'attribute' => 'content',
        ]);

        $widget->init();

        $output = self::invokePrivateMethod($widget, 'renderInput');

        $this->assertStringContainsString('textarea', $output);
        $this->assertStringContainsString('name="TestModel[content]"', $output);
        $this->assertStringContainsString('Model content', $output);
    }

    /**
     * Тест настройки rows по умолчанию
     */
    public function testDefaultRowsConfiguration(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->init();

        $this->assertEquals(10, $widget->options['rows']);
    }

    /**
     * Тест пользовательской настройки rows
     */
    public function testCustomRowsConfiguration(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'options' => ['rows' => 20],
        ]);
        $widget->init();

        $this->assertEquals(20, $widget->options['rows']);
    }

    /**
     * Тест форматирования размера шрифта
     */
    public function testFontSizeFormats(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->init();

        $expectedFormats = "6pt 7pt 8pt 9pt 10pt 11pt 12pt 13pt 14pt 15pt 16pt 18pt 20pt 24pt 28pt 36pt 40pt 48pt";
        $this->assertEquals($expectedFormats, $widget->config['fontsize_formats']);
    }

    /**
     * Тест объединения конфигурации по умолчанию
     */
    public function testDefaultConfigMerge(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->init();

        $this->assertArrayHasKey('theme', $widget->config);
        $this->assertArrayHasKey('plugins', $widget->config);
        $this->assertArrayHasKey('height', $widget->config);
        $this->assertArrayHasKey('language', $widget->config);
        $this->assertArrayHasKey('language_url', $widget->config);
        $this->assertArrayHasKey('toolbar', $widget->config);
        $this->assertArrayHasKey('style_formats_merge', $widget->config);
    }

    /**
     * Тест настройки toolbar в конфигурации
     */
    public function testToolbarInConfig(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->init();

        $this->assertStringContainsString('undo redo', $widget->config['toolbar']);
        $this->assertStringContainsString('bold italic underline', $widget->config['toolbar']);
    }

    /**
     * Тест toggle с кнопками
     */
    public function testToggleWithButtons(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'button' => [
                    'show' => true,
                    'toggle' => ['label' => 'Enable Editor', 'options' => ['class' => 'btn btn-primary']],
                    'unToggle' => ['label' => 'Disable Editor', 'options' => ['class' => 'btn btn-secondary']],
                ],
            ],
        ]);

        $widget->init();

        $toggle = self::getPrivateProperty($widget, 'toggle');

        $this->assertTrue($toggle['active']);
        $this->assertEquals('Enable Editor', $toggle['button']['toggle']['label']);
        $this->assertEquals('Disable Editor', $toggle['button']['unToggle']['label']);
    }

    /**
     * Тест prepareToggle метода
     */
    public function testPrepareToggle(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'addon' => [
                    'before' => '<div class="before">',
                    'after' => '</div>',
                ],
            ],
        ]);

        $widget->init();

        $input = '<textarea name="test"></textarea>';
        $output = self::invokePrivateMethod($widget, 'prepareToggle', [$input]);

        $this->assertStringContainsString('<div class="before">', $output);
        $this->assertStringContainsString('</div>', $output);
        $this->assertStringContainsString('toggleTiny', $output);
        $this->assertStringContainsString('unToggleTiny', $output);
    }

    /**
     * Тест removeToolbar удаляет элементы из toolbar
     */
    public function testRemoveToolbarRemovesFromConfig(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'removeToolbar' => ['print preview media'],
        ]);

        $widget->init();

        $this->assertStringNotContainsString('print preview media', $widget->config['toolbar']);
    }

    /**
     * Тест empty plugins
     */
    public function testEmptyPlugins(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'plugins' => [],
        ]);

        $widget->init();

        $this->assertEquals('', $widget->config['plugins']);
    }

    /**
     * Тест empty toolbar
     */
    public function testEmptyToolbar(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toolbar' => [],
        ]);

        $widget->init();

        $this->assertArrayNotHasKey('toolbar', $widget->config);
    }

    /**
     * Тест toggle с addon содержащим before и after
     */
    public function testToggleWithAddonBeforeAndAfter(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'addon' => [
                    'before' => '<span class="before">',
                    'after' => '</span>',
                ],
            ],
        ]);

        $widget->init();

        $input = '<textarea name="test"></textarea>';
        $output = self::invokePrivateMethod($widget, 'prepareToggle', [$input]);

        $this->assertStringContainsString('<span class="before">', $output);
        $this->assertStringContainsString('</span>', $output);
        $this->assertStringContainsString('toggleTiny', $output);
    }

    /**
     * Тест setToggle через сеттер
     */
    public function testSetToggleMethod(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
        ]);

        $widget->setToggle(['active' => true]);

        $toggle = self::getPrivateProperty($widget, 'toggle');

        $this->assertTrue($toggle['active']);
    }

    /**
     * Тест image_advtab не устанавливается когда showAdvancedImageTab = false
     */
    public function testImageAdvtabNotSetWhenDisabled(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'showAdvancedImageTab' => false,
        ]);

        $widget->init();

        $this->assertArrayNotHasKey('image_advtab', $widget->config);
    }

    /**
     * Тест toggle с tinyStart = true
     */
    public function testToggleWithTinyStart(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'tinyStart' => true,
            ],
        ]);

        $widget->init();

        $toggle = self::getPrivateProperty($widget, 'toggle');

        $this->assertTrue($toggle['tinyStart']);
    }

    /**
     * Тест toggle с кастомным id
     */
    public function testToggleWithCustomId(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'toggle' => [
                'active' => true,
                'id' => 'custom-toggle-id',
            ],
        ]);

        $widget->init();

        $toggle = self::getPrivateProperty($widget, 'toggle');

        $this->assertEquals('custom-toggle-id', $toggle['id']);
    }

    /**
     * Тест selector добавляется в config при вызове registerPlugin
     */
    public function testSelectorAddedToConfig(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'selector' => '.my-editor',
        ]);

        $widget->init();

        try {
            self::invokePrivateMethod($widget, 'registerPlugin');
        } catch (\yii\base\InvalidArgumentException $e) {
            // Игнорируем ошибку публикации assets в тестовом окружении
        }

        $this->assertEquals('.my-editor', $widget->config['selector']);
    }

    /**
     * Тест selector с # prefix когда не задан явно
     */
    public function testSelectorWithHashPrefix(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'options' => ['id' => 'my-editor'],
        ]);

        $widget->init();

        try {
            self::invokePrivateMethod($widget, 'registerPlugin');
        } catch (\yii\base\InvalidArgumentException $e) {
            // Игнорируем ошибку публикации assets
        }

        $this->assertEquals('#my-editor', $widget->config['selector']);
    }

    /**
     * Тест renderInput возвращает textarea без модели
     */
    public function testRenderInputWithoutModel(): void
    {
        $widget = new TinyMCE([
            'name' => 'content',
            'value' => 'default value',
        ]);

        $widget->init();

        $output = self::invokePrivateMethod($widget, 'renderInput');

        $this->assertStringContainsString('<textarea', $output);
        $this->assertStringContainsString('name="content"', $output);
        $this->assertStringContainsString('default value', $output);
    }

    /**
     * Тест merge конфигурации с пользовательскими настройками
     */
    public function testConfigMergePriority(): void
    {
        $widget = new TinyMCE([
            'name' => 'test-content',
            'height' => 500,
            'config' => [
                'height' => 600,
            ],
        ]);

        $widget->init();

        $this->assertEquals(600, $widget->config['height']);
    }

    /**
     * Тест style_formats_merge по умолчанию
     */
    public function testStyleFormatsMergeDefault(): void
    {
        $widget = new TinyMCE(['name' => 'test-content']);
        $widget->init();

        $this->assertTrue($widget->config['style_formats_merge']);
    }
}

/**
 * Тестовая модель для виджета
 */
class TestModel extends Model
{
    public $content;

    public function rules(): array
    {
        return [
            [['content'], 'safe'],
        ];
    }
}
