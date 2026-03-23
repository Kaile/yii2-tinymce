<?php

namespace moonland\tinymce;

use Yii;
use yii\web\AssetBundle;

/**
 * Asset for languages of TinyMCE editor
 *
 * @author Mikhail Kornilov <mikh.kornilov@gmail.com>
 * @created 27.10.2021 15:00:00
 *
 * @since 1.0.0
 */
class TinyMCELangAsset extends AssetBundle
{
    /**
     * @inheritDoc
     */
    public $sourcePath = '@npm/tinymce-i18n/langs5';

    /**
     * @inheritDoc
     */
    public $depends = [
        TinyMCEAsset::class,
    ];

    public function init()
    {
        parent::init();

        $locale = preg_replace('/-/', '_', Yii::$app->language);
        $langParts = explode('_', $locale);
        $lang = $langParts[0] ?? $locale;
        $langFile = "{$locale}.js";
        $sourcePath = Yii::getAlias($this->sourcePath);

        if (!file_exists($sourcePath . '/' . $langFile)) {
            $langFile = "{$lang}.js";
        }

        $this->js[] = $langFile;
    }
}
