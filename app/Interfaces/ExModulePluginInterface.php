<?php

namespace ex_module\App\Interfaces;

interface ExModulePluginInterface {
	public function __construct($params);
	// CSS出力関数
	public function output_css();
	// headタグ内スクリプト出力関数
	public function output_pre_scripts();
	// body終了タグ直前スクリプト出力関数
	public function output_after_scripts();
}