<?php
if (! defined ( 'IN_GAME' )) {
	exit ( 'Access Denied' );
}


function init_item_place()
{
	global $npc_typeinfo,$plsinfo,$gamecfg,$iplacedata;
	//各需要openfile的文件
	$iplacefilelist = array(
		'mapitem' => GAME_ROOT.'/include/modules/base/itemmain/config/mapitem.config.php',
		'mapitem_i8' => GAME_ROOT.'./include/modules/extra/instance/instance8_proud/config/mapitem.config.php',
		'mapitem_i9' => GAME_ROOT.'./include/modules/extra/instance/instance9_rush/config/mapitem.config.php',
		'shopitem' => GAME_ROOT.'./include/modules/base/itemshop/config/shopitem.config.php',
		'shopitem_i8' => GAME_ROOT.'./include/modules/extra/instance/instance8_proud/config/shopitem.config.php',
		'shopitem_i9' => GAME_ROOT.'./include/modules/extra/instance/instance9_rush/config/shopitem.config.php',
		'mixitem' => GAME_ROOT.'./include/modules/base/itemmix/itemmix/config/itemmix.config.php',
		'syncitem' => GAME_ROOT.'./include/modules/base/itemmix/itemmix_sync/config/sync.config.php',
		'overlayitem' => GAME_ROOT.'./include/modules/base/itemmix/itemmix_overlay/config/overlay.config.php',
		'presentitem' => GAME_ROOT.'./include/modules/base/items/boxes/config/present.config.php',
		'ygoitem' => GAME_ROOT.'./include/modules/base/items/boxes/config/ygobox.config.php',
		'fyboxitem' => GAME_ROOT.'./include/modules/base/items/boxes/config/fybox.config.php',
		'npc' => GAME_ROOT.'./include/modules/base/npc/config/npc.data.config.php',
		'npc_i8' => GAME_ROOT.'./include/modules/extra/instance/instance8_proud/config/npc.data.config.php',
		'npc_i9' => GAME_ROOT.'./include/modules/extra/instance/instance9_rush/config/npc.data.config.php',
		'addnpc' => GAME_ROOT.'./include/modules/base/addnpc/config/addnpc.config.php',
		'evonpc' => GAME_ROOT.'./include/modules/extra/club/skills/skill21/config/evonpc.config.php',
	);
	$iplacefiledata = array();
	foreach($iplacefilelist as $ipfkey => $ipfval){
		if($ipfkey == 'mixitem') {
			include $ipfval;
			$iplacefiledata[$ipfkey] = $mixinfo;
		}elseif(strpos($ipfkey, 'npc') !==false){
			include $ipfval;
			if($ipfkey == 'npc') $varname = 'npcinfo';
			elseif($ipfkey == 'npc_i8') $varname = 'npcinfo_instance8';
			elseif($ipfkey == 'npc_i9') $varname = 'npcinfo_instance9';
			elseif($ipfkey == 'addnpc') $varname = 'anpcinfo';
			elseif($ipfkey == 'evonpc') $varname = 'enpcinfo';
			if(!empty($varname)) $iplacefiledata[$ipfkey] = $$varname;
		}else {
			$iplacefiledata[$ipfkey] = openfile($ipfval);
		}
	}
	//地图数据预处理，忽略效、耐的不同
	foreach(array('mapitem', 'mapitem_i8', 'mapitem_i9',) as $val){
		foreach($iplacefiledata[$val] as $ndk => $ndv){
			$ndv_a = explode(',',trim($ndv));
			$ndv_a[5] = 0; $ndv_a[6] = 1;
			$iplacefiledata[$val][$ndk] = implode(',', $ndv_a);
		}
	}
	//商店数据预处理，trim
	foreach(array('shopitem', 'shopitem_i8', 'shopitem_i9') as $val){
		foreach($iplacefiledata[$val] as $ndk => $ndv){
			$iplacefiledata[$val][$ndk] = trim($ndv);
		}
	}
	//writeover('tmp_mapitem.txt', var_export($iplacefiledata['mapitem'],1));
	//地图掉落、商店出售的各模式数据进行差分
	foreach(array('mapitem','shopitem') as $val){
		$basedata = $iplacefiledata[$val];
		$modelist = array('i8','i9');
		foreach($modelist as $mval){
			if(isset($iplacefiledata[$val.'_'.$mval])){
				//由于是字符串，刚好可以用array_diff。返回特殊模式独有的道具数据
				$res = array_diff($iplacefiledata[$val.'_'.$mval], $basedata);
				$iplacefiledata[$val.'_'.$mval] = $res;
				//writeover('tmp_'.$val.'_'.$mval.'.txt', var_export($res,1));
			}
		}
		unset($basedata);
	}
	//生成以道具名为键名的数组
	$iplacedata = array();
	foreach($iplacefiledata as $ipdkey => $ipdval){
		foreach($ipdval as $ipdkey2 => $ipdval2){
			$idata = '';
			//地图掉落
			if(strpos($ipdkey, 'mapitem')===0) {
				if(!empty($ipdval2) && strpos($ipdval2,',')!==false)
				{
					list($iarea,$imap,$inum,$iname,$ikind,$ieff,$ista,$iskind) = explode(',',$ipdval2);
					if ($iarea==99) $idata.="每禁"; else $idata.="{$iarea}禁";
					if ($imap==99) $idata.="全图随机"; else $idata.="于{$plsinfo[$imap]}";
					$idata.="刷新{$inum}个";
					if(strpos($ipdkey,'_')!==false){//如果特殊模式，加标记
						if($ipdkey == 'mapitem_i8') $idata = '荣耀模式：'.$idata;
						elseif($ipdkey == 'mapitem_i9') $idata = '极速模式：'.$idata;
					}
				}
			}
			//商店出售
			elseif(strpos($ipdkey, 'shopitem')===0) {
				if(!empty($ipdval2) && strpos($ipdval2,',')!==false)
				{
					list($kind,$num,$price,$area,$iname)=explode(',',$ipdval2);
					if(empty($iplacedata[$iname])) $iplacedata[$iname] = array();
					$idata.="{$area}禁起在商店中出售({$price}元)";
					if(strpos($ipdkey,'_')!==false){//如果特殊模式，加标记
						if($ipdkey == 'shopitem_i8') $idata = '荣耀模式：'.$idata;
						elseif($ipdkey == 'shopitem_i9') $idata = '极速模式：'.$idata;
					}
				}
			}
			//通常合成
			elseif(strpos($ipdkey, 'mixitem')===0){
				if($ipdval2['class'] != 'hidden') {
					$iname = trim($ipdval2['result'][0]);
					$idata = '通过合成获取：'.implode('+', $ipdval2['stuff']);
				}
			}
			//同调，超量
			elseif(strpos($ipdkey, 'sync')===0 || strpos($ipdkey, 'overlay')===0){
				if(!empty($ipdval2) && strpos($ipdval2,',')!==false)
				{
					list($iname,$kind)=explode(',',$ipdval2);
					if(empty($iplacedata[$iname])) $iplacedata[$iname] = array();
					$idata = strpos($ipdkey, 'sync')===0 ? '通过同调合成获取' : '通过超量合成获取';
				}
			}
			//各类礼品盒
			elseif(strpos($ipdkey, 'present')===0 || strpos($ipdkey, 'ygo')===0 || strpos($ipdkey, 'fybox')===0){
				if(!empty($ipdval2) && strpos($ipdval2,',')!==false)
				{
					list($iname,$kind)=explode(',',$ipdval2);
					if(strpos($ipdkey, 'present')===0) $idata = '打开礼品盒时有概率获得';
					elseif(strpos($ipdkey, 'ygo')===0) $idata = '打开游戏王卡包时有概率获得';
					elseif(strpos($ipdkey, 'fybox')===0) $idata = '打开浮云时有概率获得';
				}
			}
			//NPC
			elseif(strpos($ipdkey, 'npc')!==false){
				
				$nownpclist = array($ipdval2);
				if(isset($ipdval2['sub'])){
					$ipdval2['type'] = $ipdkey2;
					$nownpclist = array();
					foreach ($ipdval2['sub'] as $subval){
						$nownpclist[] = array_merge($ipdval2, $subval);
					}
				}elseif($ipdkey == 'evonpc') {
					$nownpclist = $ipdval2;
					foreach($nownpclist as &$nval){
						$nval['type'] = $ipdkey2;
					}
				}
				
				foreach ($nownpclist as $nownpc){
					foreach(array('wep','arb','arh','ara','arf','art','itm1','itm2','itm3','itm4','itm5','itm6') as $nipval){
						if(!empty($nownpc[$nipval])) {
							$iname = $nownpc[$nipval];
							$idata = '击倒'.$npc_typeinfo[$nownpc['type']].' '.$nownpc['name'].'可拾取';
							//如果标准模式定义了，那么其他模式不重复计算
							if(isset($iplacedata[$iname]) && in_array($idata, $iplacedata[$iname])){
								$idata='';
							}else{
								if(strpos($ipdkey, '_')!==false){
									if($ipdkey == 'npc_i8') $idata = '荣耀模式：'.$idata;
									elseif($ipdkey == 'npc_i9') $idata = '极速模式：'.$idata;
								}
							}
							if(empty($iplacedata[$iname])) $iplacedata[$iname] = array();
							if(!empty($iname) && !empty($idata)) {
								$iplacedata[$iname][] = $idata;
							}
						}
					}
				}
				
			}
			if(empty($iplacedata[$iname])) $iplacedata[$iname] = array();
			if(!empty($iname) && !empty($idata)) {
				//礼品盒的话，只显示1次概率
				if(strpos($ipdkey, 'present')===0 || strpos($ipdkey, 'ygo')===0 || strpos($ipdkey, 'fybox')===0){
					if(!in_array($idata, $iplacedata[$iname])) $iplacedata[$iname][] = $idata;
				}
				//非NPC（NPC在自己的循环里就写了）
				elseif(strpos($ipdkey, 'npc')===false){
					$iplacedata[$iname][] = $idata;
				}
			}
		}
	}
	return $iplacedata;
}

function get_item_place_single($which){
	if ($which=='-')
		return '-';
	global $iplacedata;
	$result = '';
	if(!empty($iplacedata[$which])){
		$result = implode('<br>',$iplacedata[$which] );
	}
	if ($which=="悲叹之种") {
		if(!empty($result)) $result .= '<br>';
		$result.="通过使用『灵魂宝石』强化物品失败获得<br>";
	}
	return $result;
}

function get_item_place($which)
{
	if ($which=='-')
		return '-';
	global $plsinfo,$gamecfg;
	//获取某物品的获取方式，如刷新地点或商店是否有卖等
	$result="";
	$file = GAME_ROOT.'./include/modules/base/itemmain/config/mapitem.config.php';
	$itemlist = openfile($file);
	$in = sizeof($itemlist);
	for($i = 1; $i < $in; $i++) {
		if(!empty($itemlist[$i]) && strpos($itemlist[$i],',')!==false)
		{
			list($iarea,$imap,$inum,$iname,$ikind,$ieff,$ista,$iskind) = explode(',',$itemlist[$i]);
			if ($iname==$which)
			{
				if ($iarea==99) $result.="每禁"; else $result.="{$iarea}禁";
				if ($imap==99) $result.="全图随机"; else $result.="于{$plsinfo[$imap]}";
				$result.="刷新{$inum}个<br>";
			}
		}
	}
	$file = GAME_ROOT.'./include/modules/base/itemshop/config/shopitem.config.php';
	$shoplist = openfile($file);
	foreach($shoplist as $lst){
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($kind,$num,$price,$area,$item)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="{$area}禁起在商店中出售({$price}元)<br>";
			}
		}
	}
	include_once GAME_ROOT.'./include/modules/base/itemmix/itemmix/config/itemmix.config.php';
	global $mixinfo;
	foreach($mixinfo as $lst){
		if ($lst['result'][0]==$which || $lst['result'][0]==$which.' ')
		{
			$result.="通过合成获取<br>";
			break;
		}
	}
	
	$file = GAME_ROOT.'./include/modules/base/itemmix/itemmix_sync/config/sync.config.php';
	$synlist = openfile($file);
	foreach($synlist as $lst){
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="通过同调合成获取<br>";
				break;
			}
		}
	}
	$file = GAME_ROOT.'./include/modules/base/itemmix/itemmix_overlay/config/overlay.config.php';
	$ovllist = openfile($file);
	foreach($ovllist as $lst){
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="通过超量合成获取<br>";
				break;
			}
		}
	}
	$file = GAME_ROOT.'./include/modules/base/items/boxes/config/present.config.php';
	$prslist = openfile($file);
	foreach($prslist as $lst)
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="打开礼品盒时有概率获得<br>";
				break;
			}
		}
	$file = GAME_ROOT.'./include/modules/base/items/boxes/config/ygobox.config.php';
	$boxlist = openfile($file);
	foreach($boxlist as $lst){
		if(!empty($lst) && strpos($lst,',')!==false)
		{
			list($item,$kind)=explode(',',$lst);
			if ($item==$which)
			{
				$result.="打开游戏王卡包时有概率获得<br>";
				break;
			}
		}
	}
	if ($which=="悲叹之种") $result.="通过使用『灵魂宝石』强化物品失败获得<br>";
	return $result;
}
?>
