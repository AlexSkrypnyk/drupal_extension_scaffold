#!/usr/bin/env php
<?php

/**
 * @file
 * Adjust project repository based on user input.
 *
 * Environment variables:
 * - SCRIPT_RUN_SKIP: Set to '1' to skip running of the script. Useful when
 *   unit-testing or requiring this file from other files.
 * - PROMPTY_*: Set environment variables to pre-fill prompts
 *   (e.g. PROMPTY_NAME, PROMPTY_TYPE, PROMPTY_CI_PROVIDER).
 *
 * Usage:
 * @code
 * php init.php
 * php init.php --help
 * @endcode
 */

declare(strict_types=1);

// phpcs:disable
// @embed-start
/**
 * 🧙Prompty - Zero-dependency interactive CLI prompt library.
 *
 * Copy the contents of this file directly into your script, preserving
 * this header.
 *
 * @license MIT
 * @see LICENSE file for full license text.
 *
 * Copyright (c) 2026 Alex Skrypnyk (alex@drevops.com)
 * https://github.com/AlexSkrypnyk/prompty
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software to deal in it without restriction, including the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies, subject to the following condition:
 *
 * This notice must be included in all copies of this file, including when
 * used as a part of other files.
 */
// @phpstan-ignore-next-line
class Prompty{protected static?self $_p0=NULL;protected?string $_p1=NULL;protected static bool $_p2=FALSE;protected array $_p3=[];protected array $_p4=[];protected $_p5;protected array $_p6=['bar'=>'│','completed'=>'◆','active'=>'◇','intro'=>'┌','outro'=>'└','radio_on'=>'●','radio_off'=>'○','check_on'=>'◼','check_off'=>'◻','hint_arrow'=>'↳',];protected array $_p7=['bar'=>'|','completed'=>'+','active'=>'o','intro'=>'#','outro'=>'#','radio_on'=>'(*)','radio_off'=>'( )','check_on'=>'[x]','check_off'=>'[ ]','hint_arrow'=>'-->',];protected array $_p8=[];protected array $_p9=['reset'=>"\033[0m",'dim'=>"\033[2m",'dim_italic'=>"\033[2;3m",'cyan'=>"\033[36m",'green'=>"\033[32m",'red'=>"\033[31m",'gray'=>"\033[90m",'bold'=>"\033[1m",'white'=>"\033[37m",];protected array $_p10=['indent'=>'  ','hint_indent'=>'    ','hint_cont'=>'      ',];protected array $_p11=['yes'=>'Yes','no'=>'No','cancelled'=>'(cancelled)','none'=>'None','separator'=>'/',];protected?bool $_p12=NULL;protected?bool $_p13=NULL;protected array $_p14=[];protected string $_p15='PROMPTY_';protected array $_p16=['1','true','yes'];protected array $_p17=['0','false','no'];protected function __construct(){if($this->_p12===NULL){$a=getenv('LANG')?:getenv('LC_ALL')?:getenv('LC_CTYPE')?:setlocale(LC_CTYPE,'0')?:'';$this->_p12=stripos($a,'utf')!==FALSE;}$this->_p8=$this->_p12?$this->_p6:$this->_p7;$this->_p14=$this->_p9;if($this->_p13===NULL){$b=getenv('NO_COLOR');$this->_p13=($b!==FALSE&&$b!=='')||getenv('TERM')==='dumb'?FALSE:TRUE;}if($this->_p13===FALSE){$this->_p9=array_fill_keys(array_keys($this->_p9),'');}}protected static function _m0():static{if(!static::$_p0 instanceof Prompty){static::$_p0=new static();}return static::$_p0;}public static function config():array{$c=static::_m0();return['symbols_unicode'=>$c->_p6,'symbols_ascii'=>$c->_p7,'symbols'=>$c->_p8,'colors'=>$c->_p9,'spacing'=>$c->_p10,'labels'=>$c->_p11,'unicode'=>$c->_p12,'ansi'=>$c->_p13,'env_prefix'=>$c->_p15,'truthy'=>$c->_p16,'falsy'=>$c->_p17,];}public static function results():array{return static::_m0()->_p3;}public static function configure(?array $symbols_unicode=NULL,?array $symbols_ascii=NULL,?array $colors=NULL,?array $spacing=NULL,?array $labels=NULL,?bool $unicode=NULL,?bool $ansi=NULL,?string $env_prefix=NULL,?array $truthy=NULL,?array $falsy=NULL):void{$c=static::_m0();if($symbols_unicode!==NULL){$c->_p6=array_replace($c->_p6,$symbols_unicode);}if($symbols_ascii!==NULL){$c->_p7=array_replace($c->_p7,$symbols_ascii);}if($colors!==NULL){$c->_p14=array_replace($c->_p14,$colors);$c->_p9=array_replace($c->_p9,$colors);}if($spacing!==NULL){$c->_p10=array_replace($c->_p10,$spacing);}if($labels!==NULL){$c->_p11=array_replace($c->_p11,$labels);}if($unicode!==NULL){$c->_p12=$unicode;}if($ansi!==NULL){$c->_p13=$ansi;}if($env_prefix!==NULL){$c->_p15=$env_prefix;}if($truthy!==NULL){$c->_p16=$truthy;}if($falsy!==NULL){$c->_p17=$falsy;}$c->_p8=$c->_p12?$c->_p6:$c->_p7;$c->_p9=$c->_p13?$c->_p14:array_fill_keys(array_keys($c->_p14),'');}public static function flow(callable $steps,string|callable|null $intro=NULL,string|callable|null $outro=NULL,string|callable|null $cancelled=NULL,bool $numbering=FALSE,?array $symbols_unicode=NULL,?array $symbols_ascii=NULL,?array $colors=NULL,?array $spacing=NULL,?array $labels=NULL,?bool $unicode=NULL,?bool $ansi=NULL,?string $env_prefix=NULL,?array $truthy=NULL,?array $falsy=NULL):?array{if($symbols_unicode!==NULL||$symbols_ascii!==NULL||$colors!==NULL||$spacing!==NULL||$labels!==NULL||$unicode!==NULL||$ansi!==NULL||$env_prefix!==NULL||$truthy!==NULL||$falsy!==NULL){static::configure($symbols_unicode,$symbols_ascii,$colors,$spacing,$labels,$unicode,$ansi,$env_prefix,$truthy,$falsy);}$c=static::_m0();$c->_p3=[];static::$_p2=TRUE;$steps=$steps();$d=['numbering'=>$numbering,];$e=$c->_p5===NULL?(shell_exec('stty -g 2>/dev/null')?:NULL):NULL;if($e!==NULL){$e=trim($e);shell_exec('stty -echo -icanon min 1 time 0 2>/dev/null');register_shutdown_function(function()use($c,$e):void{$c->_m3($e);$c->_m5();});echo"\033[?25l";}if($intro!==NULL){is_callable($intro)?$intro($c->_p3):$c->_m8($c->_m9($intro));}$f=$c->_m19($steps,0,$d,'');if($f===FALSE){if($cancelled!==NULL){is_callable($cancelled)?$cancelled($c->_p3):$c->_m8($c->_m10($cancelled));}if($e!==NULL){$c->_m3($e);$c->_m5();}static::$_p2=FALSE;return NULL;}if($outro!==NULL){is_callable($outro)?$outro($c->_p3):$c->_m8($c->_m10($outro));}if($e!==NULL){$c->_m3($e);$c->_m5();}static::$_p2=FALSE;return $c->_p3;}public static function text(string $label,string $placeholder='',string $description='',mixed $discovered=NULL,?callable $condition=NULL,array $children=[],?array $ctx=NULL):\Closure|array|string|null{if(static::$_p2&&$ctx===NULL){$g=fn(array $ctx):array|\Closure|string|null=>static::text($label,placeholder:$placeholder,description:$description,discovered:$discovered,ctx:$ctx);if($condition!==NULL||$children!==[]){return['__call'=>$g,'__children'=>$children,'__condition'=>$condition];}return $g;}$c=static::_m0();$ctx??=['depth'=>0,'is_last'=>FALSE,'open'=>[],];$h=!static::$_p2;if($h){$c->_m6();}$i=$ctx['depth']??0;$j=$ctx['is_last']??FALSE;$_p4=$ctx['open']??[];$label=$c->_m16($label,$ctx);$k=$discovered??$ctx['env_value']??NULL;if($k!==NULL){$l=(string)$k;$c->_m8($c->_m17($label,$l,$i,$j,$_p4));if($h){$c->_m7();}return $l;}$m=function(string $n)use($c,$label,$placeholder,$description,$i,$_p4):array{$o=$c->_m1('█','cyan');$l=$n===''?$c->_m1($placeholder,'gray').$o:$c->_m1($n,'white').$o;if($i===0){$lines=[$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description):[$c->_m2()]);$lines[]=$c->_m2().$c->_p10['indent'].$l;$lines[]=$c->_m2();return $lines;}$p=$c->_m2().$c->_m12($i,$_p4);$q=$c->_m13($i,$_p4);$lines=[$p.$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description,$i,$_p4):[$c->_m2().$q]);$lines[]=$c->_m2().$q.$l;$lines[]=$c->_m2().$q;return $lines;};$n='';$r=$c->_m8($m($n));while(TRUE){$s=$c->_m4();if($s==='ctrl-c'||$s==='escape'){$c->_m11($r,$c->_m18($label,$n,$i,$j,$_p4));if($h){$c->_m7();}return NULL;}if($s==='enter'){$l=$n!==''?$n:$placeholder;$c->_m11($r,$c->_m17($label,$l,$i,$j,$_p4));if($h){$c->_m7();}return $l;}if($s==='backspace'){if($n!==''){$n=mb_substr($n,0,-1);}}elseif($s==='space'){$n.=' ';}elseif(mb_strlen($s)===1&&ord($s)>=32){$n.=$s;}$r=$c->_m11($r,$m($n));}}public static function select(string $label,array $options=[],string $description='',array $hints=[],mixed $discovered=NULL,?callable $condition=NULL,array $children=[],?array $ctx=NULL):\Closure|array|string|null{if(static::$_p2&&$ctx===NULL){$g=fn(array $ctx):array|\Closure|string|null=>static::select($label,options:$options,description:$description,hints:$hints,discovered:$discovered,ctx:$ctx);if($condition!==NULL||$children!==[]){return['__call'=>$g,'__children'=>$children,'__condition'=>$condition];}return $g;}$c=static::_m0();$ctx??=['depth'=>0,'is_last'=>FALSE,'open'=>[],];$h=!static::$_p2;if($h){$c->_m6();}$i=$ctx['depth']??0;$j=$ctx['is_last']??FALSE;$_p4=$ctx['open']??[];$label=$c->_m16($label,$ctx);$t=array_keys($options);$u=array_values($options);$v=array_map(fn(int|string $s)=>$hints[$s]??'',$t);$k=$discovered??$ctx['env_value']??NULL;if($k!==NULL){$w=(string)$k;$l=$options[$w]??$w;$c->_m8($c->_m17($label,$l,$i,$j,$_p4));if($h){$c->_m7();}return $w;}$m=function(int $x)use($c,$label,$u,$description,$v,$i,$_p4):array{if($i===0){$lines=[$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description):[$c->_m2()]);foreach($u as $y=>$z){if($y===$x){$lines[]=$c->_m2().$c->_p10['indent'].$c->_m1($c->_p8['radio_on'],'green').' '.$z;if(($v[$y]??'')!==''){$lines=array_merge($lines,$c->_m15($v[$y]));}}else{$lines[]=$c->_m2().$c->_p10['indent'].$c->_m1($c->_p8['radio_off'],'dim').' '.$c->_m1($z,'dim');}}$lines[]=$c->_m2();return $lines;}$p=$c->_m2().$c->_m12($i,$_p4);$q=$c->_m13($i,$_p4);$lines=[$p.$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description,$i,$_p4):[$c->_m2().$q]);foreach($u as $y=>$z){if($y===$x){$lines[]=$c->_m2().$q.$c->_m1($c->_p8['radio_on'],'green').' '.$z;if(($v[$y]??'')!==''){$lines=array_merge($lines,$c->_m15($v[$y],$i,$_p4));}}else{$lines[]=$c->_m2().$q.$c->_m1($c->_p8['radio_off'],'dim').' '.$c->_m1($z,'dim');}}$lines[]=$c->_m2().$q;return $lines;};$x=0;$r=$c->_m8($m($x));while(TRUE){$s=$c->_m4();if($s==='ctrl-c'||$s==='escape'){$c->_m11($r,$c->_m18($label,$u[$x],$i,$j,$_p4));if($h){$c->_m7();}return NULL;}if($s==='enter'){$c->_m11($r,$c->_m17($label,$u[$x],$i,$j,$_p4));if($h){$c->_m7();}return $t[$x];}if($s==='up'||$s==='left'){$x=($x-1+count($u))%count($u);}elseif($s==='down'||$s==='right'){$x=($x+1)%count($u);}$r=$c->_m11($r,$m($x));}}public static function multiselect(string $label,array $options=[],string $description='',array $hints=[],mixed $discovered=NULL,?callable $condition=NULL,array $children=[],?array $ctx=NULL):\Closure|array|null{if(static::$_p2&&$ctx===NULL){$g=fn(array $ctx):array|\Closure|null=>static::multiselect($label,options:$options,description:$description,hints:$hints,discovered:$discovered,ctx:$ctx);if($condition!==NULL||$children!==[]){return['__call'=>$g,'__children'=>$children,'__condition'=>$condition];}return $g;}$c=static::_m0();$ctx??=['depth'=>0,'is_last'=>FALSE,'open'=>[],];$h=!static::$_p2;if($h){$c->_m6();}$i=$ctx['depth']??0;$j=$ctx['is_last']??FALSE;$_p4=$ctx['open']??[];$label=$c->_m16($label,$ctx);$t=array_keys($options);$u=array_values($options);$v=array_map(fn(int|string $s)=>$hints[$s]??'',$t);$aa=$ctx['env_value']??NULL;$k=$discovered??($aa!==NULL?array_map(trim(...),explode(',',(string)$aa)):NULL);if($k!==NULL){$ab=is_array($k)?$k:[$k];$l=$ab!==[]?implode(', ',array_map(fn($s)=>$options[is_string($s)?$s:'']??(is_string($s)?$s:''),$ab)):$c->_p11['none'];$c->_m8($c->_m17($label,$l,$i,$j,$_p4));if($h){$c->_m7();}return $ab;}$m=function(int $x,array $ac)use($c,$label,$u,$description,$v,$i,$_p4):array{if($i===0){$lines=[$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description):[$c->_m2()]);foreach($u as $y=>$z){$ad=$ac[$y]??FALSE;if($y===$x){$lines[]=$c->_m2().$c->_p10['indent'].$c->_m1($c->_p8[$ad?'check_on':'check_off'],'green').' '.$z;if(($v[$y]??'')!==''){$lines=array_merge($lines,$c->_m15($v[$y]));}}else{$lines[]=$c->_m2().$c->_p10['indent'].$c->_m1($c->_p8[$ad?'check_on':'check_off'],$ad?'green':'dim').' '.($ad?$z:$c->_m1($z,'dim'));}}$lines[]=$c->_m2();return $lines;}$p=$c->_m2().$c->_m12($i,$_p4);$q=$c->_m13($i,$_p4);$lines=[$p.$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description,$i,$_p4):[$c->_m2().$q]);foreach($u as $y=>$z){$ad=$ac[$y]??FALSE;if($y===$x){$lines[]=$c->_m2().$q.$c->_m1($c->_p8[$ad?'check_on':'check_off'],'green').' '.$z;if(($v[$y]??'')!==''){$lines=array_merge($lines,$c->_m15($v[$y],$i,$_p4));}}else{$lines[]=$c->_m2().$q.$c->_m1($c->_p8[$ad?'check_on':'check_off'],$ad?'green':'dim').' '.($ad?$z:$c->_m1($z,'dim'));}}$lines[]=$c->_m2().$q;return $lines;};$x=0;$ac=array_fill(0,count($u),FALSE);$r=$c->_m8($m($x,$ac));while(TRUE){$s=$c->_m4();if($s==='ctrl-c'||$s==='escape'){$c->_m11($r,$c->_m18($label,'',$i,$j,$_p4));if($h){$c->_m7();}return NULL;}if($s==='enter'){$ae=[];$af=[];foreach($u as $y=>$ag){if($ac[$y]){$ae[]=$t[$y];$af[]=$ag;}}$c->_m11($r,$c->_m17($label,$af!==[]?implode(', ',$af):$c->_p11['none'],$i,$j,$_p4));if($h){$c->_m7();}return $ae;}if($s==='space'){$ac[$x]=!$ac[$x];}elseif($s==='up'||$s==='left'){$x=($x-1+count($u))%count($u);}elseif($s==='down'||$s==='right'){$x=($x+1)%count($u);}$r=$c->_m11($r,$m($x,$ac));}}public static function confirm(string $label,bool $default=TRUE,string $description='',mixed $discovered=NULL,?callable $condition=NULL,array $children=[],?array $ctx=NULL):\Closure|array|bool|null{if(static::$_p2&&$ctx===NULL){$g=fn(array $ctx):array|bool|\Closure|null=>static::confirm($label,default:$default,description:$description,discovered:$discovered,ctx:$ctx);if($condition!==NULL||$children!==[]){return['__call'=>$g,'__children'=>$children,'__condition'=>$condition];}return $g;}$c=static::_m0();$ctx??=['depth'=>0,'is_last'=>FALSE,'open'=>[],];$h=!static::$_p2;if($h){$c->_m6();}$i=$ctx['depth']??0;$j=$ctx['is_last']??FALSE;$_p4=$ctx['open']??[];$label=$c->_m16($label,$ctx);$truthy=$ctx['truthy']??['1','true','yes'];$falsy=$ctx['falsy']??['0','false','no'];$ah=$ctx['env_value']??NULL;if($discovered===NULL&&$ah!==NULL){$ai=strtolower((string)$ah);if(in_array($ai,$truthy,TRUE)){$discovered=TRUE;}elseif(in_array($ai,$falsy,TRUE)){$discovered=FALSE;}}if($discovered!==NULL){$c->_m8($c->_m17($label,$discovered?$c->_p11['yes']:$c->_p11['no'],$i,$j,$_p4));if($h){$c->_m7();}return(bool)$discovered;}$m=function(bool $aj)use($c,$label,$description,$i,$_p4):array{$ak=$aj?$c->_m1($c->_p8['radio_on'],'green').' '.$c->_p11['yes'].' '.$c->_m1($c->_p11['separator'],'dim').' '.$c->_m1($c->_p8['radio_off'],'dim').' '.$c->_m1($c->_p11['no'],'dim'):$c->_m1($c->_p8['radio_off'],'dim').' '.$c->_m1($c->_p11['yes'],'dim').' '.$c->_m1($c->_p11['separator'],'dim').' '.$c->_m1($c->_p8['radio_on'],'green').' '.$c->_p11['no'];if($i===0){$lines=[$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description):[$c->_m2()]);$lines[]=$c->_m2().$c->_p10['indent'].$ak;$lines[]=$c->_m2();return $lines;}$p=$c->_m2().$c->_m12($i,$_p4);$q=$c->_m13($i,$_p4);$lines=[$p.$c->_m1($c->_p8['active'],'cyan').$c->_p10['indent'].$label];$lines=array_merge($lines,$description!==''?$c->_m14($description,$i,$_p4):[$c->_m2().$q]);$lines[]=$c->_m2().$q.$ak;$lines[]=$c->_m2().$q;return $lines;};$al=$default;$r=$c->_m8($m($al));while(TRUE){$s=$c->_m4();if($s==='ctrl-c'||$s==='escape'){$c->_m11($r,$c->_m18($label,$al?$c->_p11['yes']:$c->_p11['no'],$i,$j,$_p4));if($h){$c->_m7();}return NULL;}if($s==='enter'){$c->_m11($r,$c->_m17($label,$al?$c->_p11['yes']:$c->_p11['no'],$i,$j,$_p4));if($h){$c->_m7();}return $al;}if(in_array($s,['left','right','up','down','tab'],TRUE)){$al=!$al;}elseif($s==='y'||$s==='Y'){$al=TRUE;}elseif($s==='n'||$s==='N'){$al=FALSE;}$r=$c->_m11($r,$m($al));}}public static function intro(string $message):void{$c=static::_m0();$c->_m8($c->_m9($message));}public static function outro(string $message):void{$c=static::_m0();$c->_m8($c->_m10($message));}public static function output(array $lines):int{return static::_m0()->_m8($lines);}protected function _m1(string $am,string $an):string{return isset($this->_p9[$an])?$this->_p9[$an].$am.$this->_p9['reset']:$am;}protected function _m2():string{return $this->_m1($this->_p8['bar'],'gray');}protected function _m3(string $ao):void{shell_exec('stty '.$ao.' 2>/dev/null');}protected function _m4():string{$ap=$this->_p5??STDIN;$aq=fread($ap,1);if($aq===FALSE||$aq===''){return'';}return match($aq){"\x03"=>'ctrl-c',"\n","\r"=>'enter',"\x7f","\x08"=>'backspace',"\t"=>'tab',' '=>'space',"\x1b"=>match(fread($ap,2)){'[A'=>'up','[B'=>'down','[C'=>'right','[D'=>'left',default=>'escape',},default=>$aq,};}protected function _m5():void{echo"\033[?25h";}protected function _m6():void{$this->_p1=$this->_p5===NULL?(shell_exec('stty -g 2>/dev/null')?:NULL):NULL;if($this->_p1!==NULL){$this->_p1=trim($this->_p1);shell_exec('stty -echo -icanon min 1 time 0 2>/dev/null');echo"\033[?25l";}}protected function _m7():void{if($this->_p1!==NULL){$this->_m3($this->_p1);$this->_m5();$this->_p1=NULL;}}protected function _m8(array $lines):int{echo implode(PHP_EOL,$lines).PHP_EOL;return count($lines);}protected function _m9(string $message):array{return['',$this->_m1($this->_p8['intro'],'gray').$this->_p10['indent'].$this->_m1($message,'bold'),$this->_m2(),];}protected function _m10(string $message):array{return[$this->_m2(),$this->_m1($this->_p8['outro'],'gray').$this->_p10['indent'].$this->_m1($message,'green'),'',];}protected function _m11(int $ar,array $lines):int{if($ar>0){echo"\033[{$ar}A\r\033[J";}return $this->_m8($lines);}protected function _m12(int $i,array $_p4):string{$as='  ';for($at=1;$at<$i;$at++){$as.=($_p4[$at]??FALSE)?$this->_m1($this->_p8['bar'],'gray').'  ':'   ';}return $as;}protected function _m13(int $i,array $_p4):string{$as='  ';for($at=1;$at<=$i;$at++){$as.=($_p4[$at]??FALSE)?$this->_m1($this->_p8['bar'],'gray').'  ':'   ';}return $as;}protected function _m14(string $description,int $i=0,array $_p4=[]):array{$q=$i>0?$this->_m13($i,$_p4):$this->_p10['indent'];$lines=array_map(fn(string $au):string=>$this->_m2().$q.$this->_m1($au,'dim_italic'),explode("\n",$description),);$lines[]=$this->_m2().($i>0?$this->_m13($i,$_p4):'');return $lines;}protected function _m15(string $av,int $i=0,array $_p4=[]):array{$q=$i>0?$this->_m13($i,$_p4):'';$aw=explode("\n",$av);return array_map(fn($au,$y):string=>$this->_m2().$q.($y===0?$this->_p10['hint_indent'].$this->_m1($this->_p8['hint_arrow'],'dim').' '.$this->_m1($au,'dim_italic'):$this->_p10['hint_cont'].$this->_m1($au,'dim_italic')),$aw,array_keys($aw),);}protected function _m16(string $label,array $ctx):string{if(isset($ctx['number'])){$ax=$ctx['number'];return $label.' '.$this->_m1('('.$ax.')','dim');}return $label;}protected function _m17(string $label,string $n,int $i=0,bool $j=FALSE,array $_p4=[]):array{if($i===0){return[$this->_m1($this->_p8['completed'],'cyan').$this->_p10['indent'].$label,$this->_m2().$this->_p10['indent'].$this->_m1($n,'dim'),$this->_m2(),];}$p=$this->_m2().$this->_m12($i,$_p4);$q=$this->_m13($i,$_p4);return[$p.$this->_m1($this->_p8['completed'],'cyan').$this->_p10['indent'].$label,$this->_m2().$q.$this->_m1($n,'dim'),$this->_m2().$q,];}protected function _m18(string $label,string $n,int $i=0,bool $j=FALSE,array $_p4=[]):array{if($i===0){return[$this->_m1($this->_p8['active'],'red').$this->_p10['indent'].$label,$this->_m2().$this->_p10['indent'].$this->_m1($n,'dim').$this->_m1(' '.$this->_p11['cancelled'],'red'),$this->_m2(),];}$p=$this->_m2().$this->_m12($i,$_p4);$q=$this->_m13($i,$_p4);return[$p.$this->_m1($this->_p8['active'],'red').$this->_p10['indent'].$label,$this->_m2().$q.$this->_m1($n,'dim').$this->_m1(' '.$this->_p11['cancelled'],'red'),$this->_m2().$q,];}protected function _m19(array $steps,int $i,array $options,string $ay):bool{$az=0;foreach($steps as $s=>$ba){if(is_callable($ba)){$bb=$ba;$condition=NULL;$children=[];}else{$bb=$ba['__call'];$condition=isset($ba['__condition'])&&is_callable($ba['__condition'])?$ba['__condition']:NULL;$children=is_array($ba['__children']??NULL)?$ba['__children']:[];}if($condition!==NULL&&!$condition($this->_p3)){continue;}$az++;$bc=FALSE;$bd=FALSE;foreach($steps as $be=>$bf){if(!$bd){if($be===$s){$bd=TRUE;}continue;}if(is_callable($bf)){$bc=TRUE;break;}$bg=isset($bf['__condition'])&&is_callable($bf['__condition'])?$bf['__condition']:NULL;if($bg===NULL||$bg($this->_p3)){$bc=TRUE;break;}}$j=$i>0&&!$bc;if($i>0){if($j){unset($this->_p4[$i]);}else{$this->_p4[$i]=TRUE;}}$ax=$ay!==''?$ay.'.'.$az:(string)$az;$aa=getenv($this->_p15.strtoupper((string)$s));$ctx=['depth'=>$i,'is_last'=>$j,'open'=>$this->_p4,'results'=>$this->_p3,'number'=>($options['numbering']??FALSE)?$ax:NULL,'env_value'=>$aa!==FALSE?$aa:NULL,'truthy'=>$this->_p16,'falsy'=>$this->_p17,];$n=$bb($ctx);if($n===NULL){return FALSE;}$this->_p3[$s]=$n;if($children!==[]){$bh=FALSE;foreach($children as $child){if(is_callable($child)){$bj=NULL;}else{$bj=isset($child['__condition'])&&is_callable($child['__condition'])?$child['__condition']:NULL;}if($bj===NULL||$bj($this->_p3)){$bh=TRUE;break;}}if($bh){$bk=$i+1;$bl=$this->_m2().$this->_m12($bk,$this->_p4).$this->_m2();$this->_m8([$bl]);if(!$this->_m19($children,$bk,$options,$ax)){return FALSE;}}}}return TRUE;}}
// @embed-end
// phpcs:enable

/**
 * Main functionality.
 *
 * @param array<string> $argv
 *   Array of arguments.
 */
function main(array $argv): void {
  if (array_intersect(['help', '--help', '-h', '-?'], $argv)) {
    print_help();

    return;
  }

  $results = Prompty::flow(
    fn(): array => [
      'name' => Prompty::text('Extension name', placeholder: 'My Extension'),
      'machine_name' => Prompty::text('Machine name', placeholder: 'my_extension'),
      'type' => Prompty::select('Extension type', options: [
        'module' => 'Module',
        'theme' => 'Theme',
      ]),
      'ci_provider' => Prompty::select('CI provider', options: [
        'gha' => 'GitHub Actions',
        'circleci' => 'CircleCI',
      ]),
      'command_wrapper' => Prompty::multiselect('Command wrapper', options: [
        'ahoy' => 'Ahoy',
        'makefile' => 'Makefile',
      ]),
      'remove_self' => Prompty::confirm('Remove this script'),
      'proceed' => Prompty::confirm('Proceed with project init'),
    ],
    intro: 'Drupal Extension Scaffold',
    outro: fn(array $r): string => sprintf(
      "Name: %s\nMachine name: %s\nType: %s\nCI: %s\nWrapper: %s",
      $r['name'],
      $r['machine_name'],
      $r['type'],
      $r['ci_provider'],
      implode(', ', $r['command_wrapper'] ?: ['None']),
    ),
    cancelled: 'Cancelled.',
    numbering: TRUE,
    env_prefix: 'PROMPTY_',
  );

  if ($results === NULL || ($results['proceed'] ?? FALSE) === FALSE) {
    throw new \Exception('Aborting.');
  }

  $name = (string) $results['name'];
  $machine_name = (string) $results['machine_name'];
  $type = (string) $results['type'];
  $ci_provider = (string) $results['ci_provider'];
  /** @var array<string> $command_wrapper */
  $command_wrapper = array_filter((array) $results['command_wrapper'], static fn($v): bool => $v !== '');
  $remove_self = empty($results['remove_self']) ? 'n' : 'y';

  // Derive machine name from extension name if the user accepted placeholder.
  if ($machine_name === 'my_extension' || $machine_name === '') {
    $machine_name = convert_string($name, 'file_name');
  }

  process($name, $machine_name, $type, $ci_provider, $command_wrapper, $remove_self);
}

/**
 * Print help.
 */
function print_help(): void {
  $script_name = basename(__FILE__);
  $out = <<<EOF
Drupal Extension Scaffold - project initialisation.
----------------------------------------------------

Usage:
  php {$script_name}

Options:
  --help                This help.

Environment variables (to pre-fill prompts):
  PROMPTY_NAME            Extension name.
  PROMPTY_MACHINE_NAME    Extension machine name.
  PROMPTY_TYPE            Extension type: module or theme.
  PROMPTY_CI_PROVIDER     CI provider: gha or circleci.
  PROMPTY_COMMAND_WRAPPER Command wrapper: ahoy, makefile, or both (comma-separated).
  PROMPTY_REMOVE_SELF     Remove this script: true or false.
  PROMPTY_PROCEED         Proceed with init: true or false.

EOF;
  print $out;
}

/**
 * Process the project initialization.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 * @param string $extension_machine_name
 *   The machine name of the extension.
 * @param string $extension_type
 *   The extension type (module or theme).
 * @param string $ci_provider
 *   The CI provider (gha or circleci).
 * @param array<string> $command_wrapper
 *   The selected command wrappers ('ahoy', 'makefile', or both).
 * @param string $remove_self
 *   Whether to remove this script ('y' or 'n').
 */
function process(string $extension_name, string $extension_machine_name, string $extension_type, string $ci_provider, array $command_wrapper, string $remove_self): void {
  // Validate required values.
  if ($extension_name === '') {
    throw new \Exception('Name is required.');
  }
  if ($extension_machine_name === '') {
    throw new \Exception('Machine name is required.');
  }
  if ($extension_type === '') {
    throw new \Exception('Type is required.');
  }
  if ($ci_provider === '') {
    throw new \Exception('CI provider is required.');
  }
  // Remove unwanted CI provider.
  if ($ci_provider === 'circleci') {
    remove_dir('.github/workflows');
  }
  else {
    remove_dir('.circleci');
  }

  // Remove unwanted command wrappers.
  if (!in_array('ahoy', $command_wrapper, TRUE)) {
    @unlink('.ahoy.yml');
  }
  if (!in_array('makefile', $command_wrapper, TRUE)) {
    @unlink('Makefile');
  }

  process_readme($extension_name);

  process_internal($extension_name, $extension_machine_name, $extension_type);

  if ($remove_self !== 'n') {
    @unlink(__FILE__);
  }
}

/**
 * Process README file and download placeholder logo.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 */
function process_readme(string $extension_name): void {
  @rename('README.dist.md', 'README.md');

  $url = 'https://placehold.jp/000000/ffffff/200x200.png?text=' . str_replace(' ', '+', $extension_name) . '&css=%7B%22border-radius%22%3A%22%20100px%22%7D';
  $logo = @file_get_contents($url);
  if ($logo !== FALSE && $logo !== '') {
    file_put_contents('logo.png', $logo);
  }
  @unlink('logo.tmp.png');
}

/**
 * Process internal replacements, renames, and cleanups.
 *
 * @param string $extension_name
 *   The human-readable extension name.
 * @param string $extension_machine_name
 *   The machine name of the extension.
 * @param string $extension_type
 *   The extension type (module or theme).
 */
function process_internal(string $extension_name, string $extension_machine_name, string $extension_type): void {
  $extension_machine_name_class = convert_string($extension_machine_name, 'class_name');

  // Protect the scaffold attribution link from bulk replacements.
  $scaffold_link = '[Drupal Extension Scaffold](https://github.com/AlexSkrypnyk/drupal_extension_scaffold)';
  $scaffold_link_token = '__SCAFFOLD_ATTRIBUTION_LINK__';
  replace_string_content($scaffold_link, $scaffold_link_token);

  replace_string_content('YourNamespace', $extension_machine_name);
  replace_string_content('yournamespace', $extension_machine_name);
  replace_string_content('AlexSkrypnyk', $extension_machine_name);
  replace_string_content('alexskrypnyk', $extension_machine_name);
  replace_string_content('yourproject', $extension_machine_name);
  replace_string_content('Yourproject logo', $extension_name . ' logo');
  replace_string_content('Your Extension', $extension_name);
  replace_string_content('your extension', $extension_name);
  replace_string_content('Your+Extension', $extension_machine_name);
  replace_string_content('your_extension', $extension_machine_name);
  replace_string_content('your-extension', $extension_machine_name);
  replace_string_content('YourExtension', $extension_machine_name_class);
  replace_string_content('Provides your_extension functionality.', 'Provides ' . $extension_machine_name . ' functionality.');
  replace_string_content('drupal-module', 'drupal-' . $extension_type);
  replace_string_content('Drupal module scaffold FE example used for template testing', 'Provides ' . $extension_machine_name . ' functionality.');
  replace_string_content('Drupal extension scaffold', $extension_name);
  replace_string_content('drupal_extension_scaffold', $extension_machine_name);
  replace_string_content('type: module', 'type: ' . $extension_type);
  replace_string_content('[EXTENSION_NAME]', $extension_machine_name);

  // Restore the scaffold attribution link.
  replace_string_content($scaffold_link_token, $scaffold_link);

  remove_string_content('# Uncomment the lines below in your project.');
  uncomment_line('.gitattributes', 'AGENTS.md');
  uncomment_line('.gitattributes', 'CLAUDE.md');
  uncomment_line('.gitattributes', '.ahoy.yml');
  uncomment_line('.gitattributes', '.circleci');
  uncomment_line('.gitattributes', '.devtools');
  uncomment_line('.gitattributes', '.editorconfig');
  uncomment_line('.gitattributes', '.gitattributes');
  uncomment_line('.gitattributes', '.github');
  uncomment_line('.gitattributes', '.gitignore');
  uncomment_line('.gitattributes', '.skip_npm_build');
  uncomment_line('.gitattributes', '.twig-cs-fixer.php');
  uncomment_line('.gitattributes', 'Makefile');
  uncomment_line('.gitattributes', 'composer.dev.json');
  uncomment_line('.gitattributes', 'patches');
  uncomment_line('.gitattributes', 'package-lock.json');
  uncomment_line('.gitattributes', 'package.json');
  uncomment_line('.gitattributes', 'phpcs.xml');
  uncomment_line('.gitattributes', 'phpstan.neon');
  uncomment_line('.gitattributes', 'phpunit.d10.xml');
  uncomment_line('.gitattributes', 'phpunit.xml');
  uncomment_line('.gitattributes', 'rector.php');
  uncomment_line('.gitattributes', 'renovate.json');
  uncomment_line('.gitattributes', 'tests');
  remove_string_content('# Remove the lines below in your project.');
  remove_string_content('.github/FUNDING.yml export-ignore');
  remove_string_content('LICENSE             export-ignore');

  // Rename extension files.
  @rename('your_extension.info.yml', $extension_machine_name . '.info.yml');
  @rename('your_extension.install', $extension_machine_name . '.install');
  @rename('your_extension.links.menu.yml', $extension_machine_name . '.links.menu.yml');
  @rename('your_extension.module', $extension_machine_name . '.module');
  @rename('your_extension.routing.yml', $extension_machine_name . '.routing.yml');
  @rename('your_extension.services.yml', $extension_machine_name . '.services.yml');
  @rename('config/schema/your_extension.schema.yml', 'config/schema/' . $extension_machine_name . '.schema.yml');
  @rename('src/Form/YourExtensionForm.php', 'src/Form/' . $extension_machine_name_class . 'Form.php');
  @rename('src/YourExtensionService.php', 'src/' . $extension_machine_name_class . 'Service.php');
  @rename('tests/src/Unit/YourExtensionServiceUnitTest.php', 'tests/src/Unit/' . $extension_machine_name_class . 'ServiceUnitTest.php');
  @rename('tests/src/Kernel/YourExtensionServiceKernelTest.php', 'tests/src/Kernel/' . $extension_machine_name_class . 'ServiceKernelTest.php');
  @rename('tests/src/Functional/YourExtensionFunctionalTest.php', 'tests/src/Functional/' . $extension_machine_name_class . 'FunctionalTest.php');
  @rename('tests/src/FunctionalJavascript/YourExtensionJsTestBase.php', 'tests/src/FunctionalJavascript/' . $extension_machine_name_class . 'JsTestBase.php');
  @rename('tests/src/FunctionalJavascript/YourExtensionSmokeJsTest.php', 'tests/src/FunctionalJavascript/' . $extension_machine_name_class . 'SmokeJsTest.php');
  @rename('css/your_extension.css', 'css/' . $extension_machine_name . '.css');
  @rename('js/your_extension.js', 'js/' . $extension_machine_name . '.js');
  @rename('js/your_extension.test.js', 'js/' . $extension_machine_name . '.test.js');
  @rename('your_extension.libraries.yml', $extension_machine_name . '.libraries.yml');

  // Remove scaffold files.
  @unlink('LICENSE');
  remove_dir('tests/scaffold');
  foreach (glob('.github/workflows/scaffold*.yml') ?: [] as $file) {
    @unlink($file);
  }
  remove_dir('.scaffold');

  remove_tokens_with_content('META');
  remove_special_comments();

  if ($extension_type === 'theme') {
    remove_dir('tests');
    file_put_contents($extension_machine_name . '.info.yml', 'base theme: false' . PHP_EOL, FILE_APPEND);
  }
}

/**
 * Convert a string to a specific format.
 *
 * @param string $input
 *   The input string to convert.
 * @param string $type
 *   The conversion type.
 *
 * @return string
 *   The converted string.
 */
function convert_string(string $input, string $type): string {
  return match ($type) {
    'file_name', 'route_path', 'deployment_id', 'function_name', 'ui_id', 'cli_command' => strtolower(str_replace(' ', '_', $input)),
    'domain_name', 'package_namespace' => str_replace('-', '', strtolower(str_replace(' ', '_', $input))),
    'namespace', 'class_name' => implode('', array_map(ucfirst(...), array_map(strtolower(...), preg_split('/[-_ ]+/', $input, -1, PREG_SPLIT_NO_EMPTY) ?: []))),
    'package_name' => strtolower(str_replace(' ', '-', $input)),
    'log_entry', 'code_comment_title' => $input,
    default => throw new \InvalidArgumentException('Invalid conversion type: ' . $type),
  };
}

/**
 * Replace string content in all project files recursively.
 *
 * @param string $needle
 *   The string to search for.
 * @param string $replacement
 *   The replacement string.
 */
function replace_string_content(string $needle, string $replacement): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $needle)) {
      continue;
    }
    file_put_contents($file, str_replace($needle, $replacement, $content));
  }
}

/**
 * Remove lines starting with the given token from all project files.
 *
 * @param string $token
 *   The token to match at the start of lines.
 */
function remove_string_content(string $token): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $token)) {
      continue;
    }
    $lines = explode("\n", $content);
    $lines = array_filter($lines, static fn(string $line): bool => !str_starts_with($line, $token));
    file_put_contents($file, implode("\n", $lines));
  }
}

/**
 * Remove blocks between token markers from all project files.
 *
 * Removes all content between lines containing "#;< TOKEN" and "#;> TOKEN"
 * markers, inclusive.
 *
 * @param string $token
 *   The token name used in the markers.
 */
function remove_tokens_with_content(string $token): void {
  $start_marker = '#;< ' . $token;
  $end_marker = '#;> ' . $token;

  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, $end_marker)) {
      continue;
    }
    $lines = explode("\n", $content);
    $result = [];
    $inside = FALSE;
    foreach ($lines as $line) {
      if (str_contains($line, $start_marker)) {
        $inside = TRUE;
        continue;
      }
      if (str_contains($line, $end_marker)) {
        $inside = FALSE;
        continue;
      }
      if (!$inside) {
        $result[] = $line;
      }
    }
    file_put_contents($file, implode("\n", $result));
  }
}

/**
 * Uncomment a line in a file by removing the "# " prefix.
 *
 * @param string $filename
 *   The file to modify.
 * @param string $start_string
 *   The string that follows "# " at the start of the line.
 */
function uncomment_line(string $filename, string $start_string): void {
  if (!file_exists($filename)) {
    return;
  }
  $content = file_get_contents($filename);
  if ($content === FALSE) {
    return;
  }
  $prefix = '# ' . $start_string;
  $lines = explode("\n", $content);
  foreach ($lines as &$line) {
    if (str_starts_with($line, $prefix)) {
      $line = substr($line, 2);
    }
  }
  unset($line);
  file_put_contents($filename, implode("\n", $lines));
}

/**
 * Remove all lines containing special comment markers from project files.
 */
function remove_special_comments(): void {
  foreach (get_files() as $file) {
    $content = file_get_contents($file);
    if ($content === FALSE) {
      continue;
    }
    if (!str_contains($content, '#;')) {
      continue;
    }
    $lines = explode("\n", $content);
    $lines = array_filter($lines, static fn(string $line): bool => !str_contains($line, '#;'));
    file_put_contents($file, implode("\n", $lines));
  }
}

/**
 * Get all non-binary files in the project, excluding specific directories.
 *
 * @return array<string>
 *   Array of file paths.
 */
function get_files(): array {
  $excluded = ['.git', '.idea', 'vendor', 'node_modules'];
  $directory = new \RecursiveDirectoryIterator((string) getcwd(), \FilesystemIterator::SKIP_DOTS);
  $filter = new \RecursiveCallbackFilterIterator($directory, static fn(\SplFileInfo $current): bool => !($current->isDir() && in_array($current->getFilename(), $excluded, TRUE)));
  $iterator = new \RecursiveIteratorIterator($filter);

  $files = [];
  /** @var \SplFileInfo $item */
  foreach ($iterator as $item) {
    if ($item->isFile() && !is_binary_file($item->getPathname())) {
      $files[] = $item->getPathname();
    }
  }

  return $files;
}

/**
 * Check if a file is binary by looking for null bytes.
 *
 * @param string $path
 *   The file path to check.
 *
 * @return bool
 *   TRUE if the file is binary, FALSE otherwise.
 */
function is_binary_file(string $path): bool {
  $handle = fopen($path, 'rb');
  if ($handle === FALSE) {
    return TRUE;
  }
  $chunk = fread($handle, 8192);
  fclose($handle);
  if ($chunk === FALSE) {
    return TRUE;
  }

  return str_contains($chunk, "\0");
}

/**
 * Remove directory recursively with all files.
 *
 * @param string $directory
 *   Path to the directory to remove.
 */
function remove_dir(string $directory): void {
  if (!is_dir($directory)) {
    return;
  }

  $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

  /** @var \SplFileInfo $item */
  foreach ($items as $item) {
    $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
  }

  rmdir($directory);
}

// Entrypoint.
//
// @codeCoverageIgnoreStart
ini_set('display_errors', 1);

if (PHP_SAPI !== 'cli' || !empty($_SERVER['REMOTE_ADDR'])) {
  die('This script can be only ran from the command line.');
}

// Allow to skip the script run.
if (getenv('SCRIPT_RUN_SKIP') != 1) {
  set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if ((error_reporting() & $severity) === 0) {
      // This error code is not included in error_reporting - continue
      // execution with the normal error handler.
      return FALSE;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
  });

  try {
    $argv = is_array($_SERVER['argv'] ?? NULL) ? array_filter($_SERVER['argv'], is_string(...)) : [];
    // The function should not provide an exit code but rather throw exceptions.
    main($argv);
  }
  catch (\ErrorException $exception) {
    if ($exception->getSeverity() <= E_USER_WARNING) {
      print PHP_EOL . 'RUNTIME ERROR: ' . $exception->getMessage() . PHP_EOL;
      exit($exception->getCode() === 0 ? 1 : $exception->getCode());
    }
  }
  catch (\Exception $exception) {
    print PHP_EOL . 'ERROR: ' . $exception->getMessage() . PHP_EOL;
    exit($exception->getCode() == 0 ? 1 : $exception->getCode());
  }
}
// @codeCoverageIgnoreEnd
