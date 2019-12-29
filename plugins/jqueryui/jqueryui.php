<?php

/**
 * jQuery UI
 *
 * Provide the jQuery UI library with according themes.
 *
 * @version 1.12.0
 * @author Cor Bosman <chronomail@wa.ter.net>
 * @author Thomas Bruederli <chronomail@gmail.com>
 * @author Aleksander Machniak <alec@alec.pl>
 * @license GNU GPLv3+
 */
class jqueryui extends chronomail_plugin
{
    public $noajax = true;
    public $version = '1.12.0';

    private static $features = array();
    private static $ui_theme;
    private static $skin_map = array(
        'larry' => 'larry',
        'default' => 'elastic',
    );

    public function init()
    {
        $chronomail = chronomail::get_instance();

        // the plugin might have been force-loaded so do some sanity check first
        if ($chronomail->output->type != 'html' || self::$ui_theme) {
            return;
        }

        $this->load_config();

        // include UI scripts
        $this->include_script("js/jquery-ui.min.js");

        // include UI stylesheet
        $skin     = $chronomail->config->get('skin');
        $ui_map   = $chronomail->config->get('jquery_ui_skin_map', self::$skin_map);
        $skins    = array_keys($chronomail->output->skins);
        $skins[]  = 'elastic';

        foreach ($skins as $skin) {
            self::$ui_theme = $ui_theme = $ui_map[$skin] ?: $skin;

            if (self::asset_exists("themes/$ui_theme/jquery-ui.css")) {
                $this->include_stylesheet("themes/$ui_theme/jquery-ui.css");
                break;
            }
        }

        // jquery UI localization
        $jquery_ui_i18n = $chronomail->config->get('jquery_ui_i18n', array('datepicker'));
        if (count($jquery_ui_i18n) > 0) {
            $lang_l = str_replace('_', '-', substr($_SESSION['language'], 0, 5));
            $lang_s = substr($_SESSION['language'], 0, 2);

            foreach ($jquery_ui_i18n as $package) {
                if (self::asset_exists("js/i18n/jquery.ui.$package-$lang_l.js", false)) {
                    $this->include_script("js/i18n/jquery.ui.$package-$lang_l.js");
                }
                else if ($lang_s != 'en' && self::asset_exists("js/i18n/jquery.ui.$package-$lang_s.js", false)) {
                    $this->include_script("js/i18n/jquery.ui.$package-$lang_s.js");
                }
            }
        }

        // Date format for datepicker
        $date_format = $date_format_localized = $chronomail->config->get('date_format', 'Y-m-d');
        $date_format = strtr($date_format, array(
                'y' => 'y',
                'Y' => 'yy',
                'm' => 'mm',
                'n' => 'm',
                'd' => 'dd',
                'j' => 'd',
        ));

        $replaces = array('Y' => 'yyyy', 'y' => 'yy', 'm' => 'mm', 'd' => 'dd', 'j' => 'd', 'n' => 'm');

        foreach (array_keys($replaces) as $key) {
            if ($chronomail->text_exists("dateformat$key")) {
                $replaces[$key] = $chronomail->gettext("dateformat$key");
            }
        }

        $date_format_localized = strtr($date_format_localized, $replaces);

        $chronomail->output->set_env('date_format', $date_format);
        $chronomail->output->set_env('date_format_localized', $date_format_localized);
    }

    public static function miniColors()
    {
        if (in_array('miniColors', self::$features)) {
            return;
        }

        self::$features[] = 'miniColors';

        $ui_theme = self::$ui_theme;
        $chronomail    = chronomail::get_instance();
        $script   = 'plugins/jqueryui/js/jquery.minicolors.min.js';
        $css      = "themes/$ui_theme/jquery.minicolors.css";

        if (!self::asset_exists($css)) {
            $css = "themes/larry/jquery.minicolors.css";
        }

        $colors_theme = $chronomail->config->get('jquery_ui_colors_theme', 'default');
        $config       = array('theme' => $colors_theme);
        $config_str   = chronomail_output::json_serialize($config);

        $chronomail->output->include_css('plugins/jqueryui/' . $css);
        $chronomail->output->add_header(html::tag('script', array('type' => 'text/javascript', 'src' => $script)));
        $chronomail->output->add_script('$.fn.miniColors = $.fn.minicolors; $("input.colors").minicolors(' . $config_str . ')', 'docready');
        $chronomail->output->set_env('minicolors_config', $config);
    }

    public static function tagedit()
    {
        if (in_array('tagedit', self::$features)) {
            return;
        }

        self::$features[] = 'tagedit';

        $script   = 'plugins/jqueryui/js/jquery.tagedit.js';
        $chronomail    = chronomail::get_instance();
        $ui_theme = self::$ui_theme;
        $css      = "themes/$ui_theme/tagedit.css";

        if (!array_key_exists('elastic', (array) $chronomail->output->skins)) {
            if (!self::asset_exists($css)) {
                $css = "themes/larry/tagedit.css";
            }

            $chronomail->output->include_css('plugins/jqueryui/' . $css);
        }

        $chronomail->output->add_header(html::tag('script', array('type' => "text/javascript", 'src' => $script)));
    }

    /**
     * Checks if an asset file exists in specified location (with assets_dir support)
     */
    protected static function asset_exists($path, $minified = true)
    {
        $chronomail = chronomail::get_instance();

        return $chronomail->find_asset('/plugins/jqueryui/' . $path, $minified) !== null;
    }
}
