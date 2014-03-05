#!/usr/bin/env php
<?php

//Todo: SVN结合

//程序统一错误输出方式匹配正则 ，有且仅有一处 错误信息、错误码，不允许有其他子集
define('ERROR_PATTERN', '/responseFetch\((.*?), (-?\d+)\)/'); 

//错误代码起始值
define('ERROR_CODE_START', 1000); 

if( count($argv) > 1 )
	$paths = array_slice($argv, 1);
else
	exit("Please specify path\n");

foreach($paths as &$dir){

	if( !is_dir($dir) )
		continue;

	parseErrorCode($dir);

}

function parseErrorCode($dir){

	static $code = ERROR_CODE_START;

	$handle = opendir($dir);

	$doc = '';

	while (($file = readdir($handle)) !== false) {

		if( $file == '.' or $file == '..' )
			continue;

		$file_path = $dir . DIRECTORY_SEPARATOR . $file;

		is_dir($file_path) && parseErrorCode($file_path);	//拼接子目录

		if(strtolower(strrchr($file_path, '.')) != '.php')	//仅处理 .php 后缀文件
			continue;

		$content = file_get_contents($file_path);

		$t = 0;
		$start = $code;
		$desc = '';

		$content = preg_replace_callback(ERROR_PATTERN, function($matches) use(&$code, &$t, &$desc){  //执行正则表达式搜搜替换

			if($matches[2] != 0){	//错误码，0正确不处理

				$t++;

				//负数，特殊代码，不处理
				if($matches[2] < 0){


					$desc .= "\t{$matches[2]}\t{$matches[1]}\n";
					return $matches[0];
				}

				$code++;

				$matches[1] = preg_replace("/^['|\"]+|['|\"]$/", '', $matches[1]);

				$desc .= "\t{$code}\t{$matches[1]}\n";

				return str_replace($matches[2], $code, $matches[0]);
			}

			return $matches[0];

		}, $content);

		if($t > 0){
			file_put_contents($file_path, $content);

			$doc = "File: {$file_path}\n{$desc}\n";
			echo $doc;
			//echo "File: {$file_path} ======> Replaced:{$t}  Code:{$start}-{$code}\n";
		}
		
	}

	closedir($handle);
}

//echo "Finished\n";
