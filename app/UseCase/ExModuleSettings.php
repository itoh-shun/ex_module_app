<?php

namespace ex_module\App\Interfaces;

use framework\SpiralConnecter\SpiralDB;
use framework\SpiralConnecter\SpiralRedis;

class ExModuleSettings {
    // private $settingCode;
    private array $settings = [];
    private array $modules = [];
    private string $settingCode = '';
    private $settingInfo;

    public function __construct(string $settingCode, $page_type = 6)
    {
        $this->settingCode = $settingCode;

        $this->settings = $this->getSettings(
            $settingCode,
            $page_type
        );

        // 設定情報からモジュールクラスを生成

        if (is_array($this->settings)) {
            foreach ($this->settings as $setting) {
                include_once 'ex_module_pulgins/exmod_' . $setting['moduleCode'] . '/index.php';
                if (
                    is_object($setting['settingParam1']) ||
                    is_array($setting['settingParam1'])
                ) {
                    $params = json_decode(
                        json_encode($setting['settingParam1']),
                        true
                    );
                } else {
                    $params = json_decode(
                        $this->convertEOL(
                            $setting['settingParam1'] .
                                $setting['settingParam2'],
                            '\n'
                        ),
                        true
                    );
                }
                // 実装ページタイプの値渡し
                $params['page_type'] = $page_type;
                $this->modules[$setting['settingID']] = new $setting[
                    'moduleCode'
                ]($params);
            }
        }
    }

    // CSS出力関数
    public function output_css()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_css')) {
                $module_class->output_css();
            }
        }
    }

    // headタグ内スクリプト出力関数
    public function output_pre_scripts()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_pre_scripts')) {
                $module_class->output_pre_scripts();
            }
        }
    }

    // header前出力関数
    public function output_before_header()
    {
        $this->output_global_menu();
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_before_header')) {
                $module_class->output_before_header();
            }
        }
    }
    // header出力関数
    public function output_header()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_header')) {
                $module_class->output_header();
            }
        }
    }

    // コンテンツ関数
    public function output_contents()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_contents')) {
                $module_class->output_contents();
            }
        }
        $this->output_SPIRAL_seal();
    }

    // footer出力関数
    public function output_footer()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_footer')) {
                $module_class->output_footer();
            }
        }
    }

    // body終了タグ直前スクリプト出力関数
    public function output_after_scripts()
    {
        foreach ($this->modules as $settingID => $module_class) {
            if (method_exists($module_class, 'output_after_scripts')) {
                $module_class->output_after_scripts();
            }
        }
    }

    private function convertEOL($string, $to = "\n")
    {
        return trim(preg_replace("/\r\n|\r|\n/", $to, $string));
    }
    private function output_global_menu()
    {
        // グローバルメニュー出力関数
        // global $SPIRAL;
        // $cache = $SPIRAL->getCache();
        // echo "<header><li>aaa</li><li>bbb</li></header>";

        // if(($this->settings)['sealID']){
        // 	if(!$cache->exists(($this->settings)['sealID'])){
        // 		$seal_record = $this->SPIRAL_select('ExMod_sealDB',array('return'=>'single','equal_condition'=>array('sealID'=>($this->settings)['sealID'])));

        // 		if(is_array($seal_record)){
        // 			echo($seal_record['sealScript']);
        // 			$cache->set(($this->settings)['sealID'], $seal_record['sealScript']);
        // 		}
        // 	}else{
        // 		echo($cache->get(($this->settings)['sealID']));
        // 	}
        // }
    }

    // SPIRALシール出力関数
    private function output_SPIRAL_seal()
    {
        $cache = new SpiralRedis();

        if ($this->settings['sealID']) {
            if (!$cache->exists($this->settings['sealID'])) {
                
                $fields = $this->getDBFields('ExMod_sealDB');
                $seals = SpiralDB::title('ExMod_sealDB')
                ->fields($fields)
                ->where('sealID', $this->settings['sealID'])->get();

                if (is_array($seals)) {
                    echo $seals['sealScript'];
                    $cache->set(
                        $this->settings['sealID'],
                        $seals['sealScript']
                    );
                }
            } else {
                echo $cache->get($this->settings['sealID']);
            }
        }
    }

    // キャッシュがあれば、キャッシュを参照し、なければDB参照する関数
    private function getSettings($settingCode, $page_type)
    {
        $cache = new SpiralRedis();
        // ページ設定情報を参照
        if (!$cache->exists($settingCode)) {
            // 設定情報をSelect
            $args = [
                'return' => 'single',
                'equal_condition' => [
                    'settingCode' => $settingCode,
                ],
            ];

            $fields = $this->getDBFields('ExMod_pageDB');
            var_dump($fields);

            $settings = SpiralDB::title('ExMod_pageDB')->fields($fields)->where('settingCode' , $settingCode)->get();
            $settings = $settings->first();
            $cache->set($settingCode, json_encode($settings));
            $this->settingInfo = $settings->all();
        } else {
            $this->settingInfo = json_decode(
                $cache->get($settingCode),
                true
            );
        }

        // モジュール設定情報を参照
        $key = $settingCode . '-' . $page_type;
        if (!$cache->exists($key)) {
            // 設定情報をSelect
            $fields = $this->getDBFields('ExMod_moduleDB');
            $settings = SpiralDB::title('ExMod_moduleDB')
            ->fields($fields)
            ->where('settingCode' , $settingCode)->where('status' , '1')->where('pageType',$page_type, '||')->orderBy('priority' , 'asc')
            ->get();
            $cache->set($key, json_encode($settings));
        }
        return json_decode($cache->get($key), true);
    }

    public function reset_cache()
    {
        global $SPIRAL;
        $cache = $SPIRAL->getCache();

        for ($i = 1; $i <= 6; $i++) {
            $key = $this->settingCode . '-' . $i;
            $cache->delete($key);
        }
        if ($this->settingCode) {
            $cache->delete($this->settingCode);
        }
        if ($this->settings['sealID']) {
            $cache->delete($this->settings['sealID']);
        }
    }

    // DB情報参照関数
    private function getDBFields($db)
    {
        $response = [];
        $schema = SpiralDB::title($db)->schema();
        if (is_array($schema->fieldList)) {
            foreach ($schema->fieldList as $field) {
                $response[] = $field->title;
            }
        }
        return $response;
    }
}