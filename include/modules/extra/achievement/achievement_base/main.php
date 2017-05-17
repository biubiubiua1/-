<?php

namespace achievement_base
{
	$achtype=array(
		20=>'日常任务',
		10=>'结局成就',
		3=>'战斗成就',
		1=>'道具成就',
		4=>'挑战成就',
		2=>'光辉事迹',
		//0=>'其他成就',
	);
	//生效中的所有成就
	$achlist=array(//为了方便调整各成就的显示顺序放在这里了
		1=>array(300,302,303,304),
		2=>array(308,309,322,323),
		3=>array(310,311,312),
		4=>array(325,313,326),
		10=>array(305,301,306,307),
		20=>array(314,315,316,317,318,319,320,321,324),
	);
	//成就编号=>允许完成的模式，未定义则用0键（只有正常游戏可以完成）
	$ach_allow_mode=array(
		0=>array(0),
	);
	
	function init() {}
	
	function skill_onload_event(&$pa)//技能模块载入时直接加载所有成就
	{
		if (eval(__MAGIC__)) return $___RET_VALUE;
		eval(import_module('sys','achievement_base'));
		$alist = array();
		foreach($achlist as $av){
			$alist = array_merge($alist, $av);
		}
		foreach($alist as $av){
			//只有玩家可以获得成就技能
			if (!$pa['type']
			//确认允许完成成就的模式，未定义则用0键（只有正常游戏可以完成）
				&& ( ( !isset($ach_allow_mode[$av]) && in_array($gametype, $ach_allow_mode[0]) ) || in_array($gametype,$ach_allow_mode[$av]))
				&& !\skillbase\skill_query($av,$pa))
			\skillbase\skill_acquire($av,$pa);
		}
		$chprocess($pa);
	}
	
	function post_gameover_events()
	{
		if (eval(__MAGIC__)) return $___RET_VALUE;
		
		eval(import_module('sys'));
		$result = $db->query("SELECT name,pid FROM {$tablepre}players WHERE type=0");
		while ($udata=$db->fetch_array($result))
		{
			$edata=\player\fetch_playerdata_by_pid($udata['pid']);
			if ($edata===NULL) continue;
			$res = $db->query("SELECT n_achievements FROM {$gtablepre}users WHERE username='{$udata['name']}'");
			if (!$db->num_rows($res)) continue;
			$zz=$db->fetch_array($res); $ach=$zz['n_achievements'];
			$achdata=explode(';',$ach);
			$maxid=count($achdata)-2;
			foreach (\skillbase\get_acquired_skill_array($edata) as $key) //也就是说，允许先进游戏后换每日任务，甚至可以先清场，结束前换每日任务
				if (defined('MOD_SKILL'.$key.'_INFO') && defined('MOD_SKILL'.$key.'_ACHIEVEMENT_ID'))
					if (\skillbase\check_skill_info($key, 'achievement'))
					{
						$id=((int)(constant('MOD_SKILL'.$key.'_ACHIEVEMENT_ID')));
						if ($id>$maxid) $maxid=$id;
						if (isset($achdata[$id])) $s=((string)$achdata[$id]); else $s='';
						$f=false;
						if (!\skillbase\check_skill_info($key, 'daily')) $f=true;
						if (($s!='')&&($s!='VWXYZ')) $f=true;
						if ($f){
							$func='\\skill'.$key.'\\finalize'.$key;
							$achdata[$id]=$func($edata,$s);
						}
					}
			
			$nachdata='';
			for ($i=0; $i<=$maxid; $i++)
				$nachdata.=$achdata[$i].';';
			
			$db->query("UPDATE {$gtablepre}users SET n_achievements = '$nachdata' WHERE username='{$udata['name']}'");	
		}
		
		$chprocess();
	}
	
	function show_achievements($un,$at)
	{
		if (eval(__MAGIC__)) return $___RET_VALUE;
		
		eval(import_module('sys','achievement_base'));
		$res = $db->query("SELECT n_achievements FROM {$gtablepre}users WHERE username='$un'");
		if (!$db->num_rows($res)) return;
		$zz=$db->fetch_array($res); $ach=$zz['n_achievements']; 
		$achdata=explode(';',$ach); 
		$c=0;
		foreach ($achlist[$at] as $key)
			if (defined('MOD_SKILL'.$key.'_INFO') && defined('MOD_SKILL'.$key.'_ACHIEVEMENT_ID'))
				if ((\skillbase\check_skill_info($key, 'achievement'))&&(!\skillbase\check_skill_info($key, 'hidden')))
				{
					$id=((int)(constant('MOD_SKILL'.$key.'_ACHIEVEMENT_ID')));
					if (isset($achdata[$id])) $s=((string)$achdata[$id]); else $s='';
					$f=false;
					if (!\skillbase\check_skill_info($key, 'daily')) $f=true;
					if (($s!='')&&($s!='VWXYZ')) $f=true;
					if ($f){
						$func='\\skill'.$key.'\\show_achievement'.$key;
						$c++;
						if ($c%3==1) echo "<tr>";
						echo '<td width="300" align="left" valign="top">';
						$func($s);
						echo "</td>";
						if ($c%3==0) echo "</tr>";
					}
				}
		while ($c<3){//不足3个的分类补位
			$c++;
			echo '<td width="300" align="left" valign="top" style="border-style:none">';
			echo "</td>";
			if ($c%3==0) echo "</tr>";
		}
		if ($c%3!=0) echo "</tr>";
	}		

	function get_daily_quest($un){
	
		if (eval(__MAGIC__)) return $___RET_VALUE;
	
		eval(import_module('sys','achievement_base'));
		$res = $db->query("SELECT n_achievements FROM {$gtablepre}users WHERE username='$un'");
		if (!$db->num_rows($res)) return;
		$zz=$db->fetch_array($res); $ach=$zz['n_achievements']; 
		$achdata=explode(';',$ach); 
		$maxid=count($achdata)-2;
		$ta=$achlist[20];
		shuffle($ta);
		$ta=array_slice($ta,0,3);
		foreach ($achlist[20] as $key){
			$id=((int)(constant('MOD_SKILL'.$key.'_ACHIEVEMENT_ID')));
			if (isset($achdata[$id])) $s=((string)$achdata[$id]); else $s='';
			if ($id>$maxid) $maxid=$id;
			if (in_array($key,$ta)){
				$achdata[$id]='aaaaa';
			}else{
				$achdata[$id]='VWXYZ';
			}
		}
		$nachdata='';
		for ($i=0; $i<=$maxid; $i++)
			$nachdata.=$achdata[$i].';';
		$db->query("UPDATE {$gtablepre}users SET n_achievements = '$nachdata' WHERE username='$un'");
	}
}

?>
