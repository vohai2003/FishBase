<?php
ini_set('mbstring.language', 'Vietnamese');

function db(){
	
	static $db = null;
	
	if(!$db){
		$db = new SQLite3(__DIR__.'/contacts2.db');
		$db->busyTimeout(60*60*1000);
		register_shutdown_function(array($db, 'close'));
	}
	
	return $db;
}

function db_query_result($query, array $args = array()){
	$q = db()->prepare($query);
	
	foreach($args as $n => $v){
		$q->bindValue(':'.$n, $v);
	}
	
	return $q->execute();
}


function db_query_generator($query, array $args = array()){
	
	$r = db_query_result($query, $args);
	
	while(($d = $r->fetchArray(SQLITE3_ASSOC)) !== false){
		
		yield $d;
		
	}
	
}


function db_query_array($query, array $args = array(), $key = '"{$d[\'_id\']}"'){
	
	$dd = array();
	
	foreach(db_query_generator($query, $args) as $d){
		
		$k = eval("return $key;");
		
		$dd[$k] = $d;
		
	}
	
	return $dd;
}

$data = array();


$foreigns = array(
	'data' => array(
		'package' => array('packages', 'package_id'),
		'mimetype' => array('mimetypes', 'mimetype_id'),
		'raw_contact' => array('raw_contacts', 'raw_contact_id'),
	),
	'raw_contacts' => array(
		'contact' => array('contacts', 'contact_id'),
		'account' => array('accounts', 'account_id'),
	),
	'contacts' => array(
		'name_raw_contact' => array('raw_contacts', 'name_raw_contact_id'),
		'photo' => array('data', 'photo_id'),
		'photo_file' => array('photo_files', 'photo_file_id'),
		'status_update' => array('data', 'status_update_id'),
	)
);

foreach(array(
	'contacts',
	'raw_contacts',
	'data',
	'photo_files',
	'accounts',
	'mimetypes',
	'packages',
	'_sync_state',
	'stream_items',
	'stream_item_photos',
	'groups',
	'agg_exceptions',
	'visible_contacts',
	'default_directory',
	'calls',
	'voicemail_status',
	'emergency',
	'directories',
	'v1_settings',
	) as $table){
	/*
	$dir = 'contacts2/'.$table;
	
	@mkdir($dir, 0777, true);
	*/
	//$data[$table] = array();
	
	foreach(db_query_generator('select * from `'.$table.'`') as $d){
		/*
		$f = "{$dir}/{$d['_id']}.php";
		
		$writen = file_put_contents($f, '<?php return '.var_export($d, true).';');
		
		if(!$writen){
			echo "cant write $f\r\n";
			fgets(STDIN);
		}
		*/
		if(!empty($foreigns[$table]))
		foreach($foreigns[$table] as $prop_name => $target_table_and_id_prop_name){
			$d[$prop_name] = null;
			list($target_table, $id_prop_name) = $target_table_and_id_prop_name;
			if($d[$id_prop_name]){
				//$d[$prop_name] = &$data[$target_table][ $d[$id_prop_name] ];
				$idx[$table][$d['_id']][$prop_name] = &$data[$target_table][ $d[$id_prop_name] ];
				$idx[$target_table][ $d[$id_prop_name] ][$table][ $d['_id'] ] = &$data[$table][ $d['_id'] ];
			}
		}
		
		$data[$table][$d['_id']] = $d;
		
	}
	
}

foreach(array_keys($data) as $table){
	$dd = &$data[$table];
	foreach(array_keys($dd) as $id){
		$d = &$dd[$id];
		if(!empty($idx[$table][$id]))
		foreach(array_keys($idx[$table][$id]) as $prop_name){
			$d[$prop_name] = &$idx[$table][$id][$prop_name];
		}
	}
}



function vc_escape_arg($arg){
	if(is_string($arg)){
		return strtr($arg, array("\r\n" => '\\n', "\n" => '\\n', ',' => '\\,', ';' => '\\;', ':' => '\\:', ));
	}elseif(is_float($arg)){
		return sprintf('%.7f', $arg);
	}
	return (string) $arg;
}


function vc_line($type, $args, array $params = array()){
	if(!is_array($args)) $args = array($args);
	
	// v 3.0 charset detecting
	foreach($args as $arg)
	if(is_string($arg) && !isset($params['CHARSET'])){
		$charset = mb_detect_encoding($args[0], 'auto');
		if($charset != 'ASCII') $params['CHARSET'] = $charset;
	}
	/**/
	$s = $type;
	foreach($params as $k => $v){
		$s .= ';'.$k.'='.(is_array($v) ? join(',', $v) : $v);
	}
	$s .= ':'.join(';', array_map('vc_escape_arg', $args));
	return $s;
}


function vc(array $rows){
	
	$rows = array_merge(
		array(
			array('BEGIN','VCARD'),
			array('VERSION','3.0'),
		),
		$rows,
		array(
			array('END','VCARD'),
		)
	);
	
	$s = '';
	
	foreach($rows as $row){
		$s .= call_user_func_array('vc_line', $row)."\r\n";
	}
	
	return $s;
}


$vcs = '';

foreach($data['contacts'] as $contact_id => $d){
	
	$rows = array();
	
	//var_dump($contact);
	
	
	if(!empty($d['name_raw_contact']['_id'])){
		
		//var_dump(array_filter($d['name_raw_contact'], 'is_scalar'));
		
		$rows[] = array('FN', $d['name_raw_contact']['display_name']);
		$rows[] = array('N', explode(', ', $d['name_raw_contact']['display_name_alt']));
		
	}
	
	if(!empty($d['raw_contacts']))
	foreach($d['raw_contacts'] as $rc_id => $rc){
		
		echo "RAW CONTACT:\r\n";
		var_dump(array_filter($rc, 'is_scalar'));
		
		if($rc['data'])
		foreach($rc['data'] as $dt_id => $dt){
			
			
			switch($dt['mimetype']['mimetype']){
				case 'vnd.android.cursor.item/email_v2':
					if($dt['data1']){
						$rows[] = array('EMAIL', $dt['data1'], array('TYPE'=>'INTERNET'));
					}
					break;
				case 'vnd.android.cursor.item/phone_v2':
					if($dt['data4']){
						$rows[] = array('TEL', $dt['data4']/*, array('TYPE'=>'VOICE,CELL,HOME,WORK,MSG')*/);
					}
					break;
				case 'vnd.android.cursor.item/vnd.org.telegram.messenger.android.profile':
					if($dt['data3']){
						$rows[] = array('TEL', $dt['data3'], array('TYPE'=>'MSG'));
					}
					break;
				
				case 'vnd.android.cursor.item/website':
					if($dt['data1']){
						$rows[] = array('URL', $dt['data1']);
					}
					break;
				case 'vnd.android.cursor.item/note':
					if($dt['data1']){
						$rows[] = array('NOTE', $dt['data1']);
					}
					break;
				case 'vnd.android.cursor.item/nickname':
					if($dt['data1']){
						$rows[] = array('NICKNAME', $dt['data1']);
					}
					break;
				case 'vnd.android.cursor.item/photo':
					
					if($dt['data15']){
						
					//	PHOTO;ENCODING=b;TYPE=JPEG:MIICajCCAdOgAwIBAgICBEUwDQYJKoZIhvc
						$it = getimagesizefromstring($dt['data15']);
						
					//	echo "PHOTO INFO:\r\n";
					//	var_dump($it);
						
						$tt = array(
							IMAGETYPE_GIF => 'GIF',
							IMAGETYPE_JPEG => 'JPEG',
							IMAGETYPE_PNG => 'PNG',
						//	IMAGETYPE_GIF => 'GIF',
						);
						
						if($it && isset($tt[ $it[2] ])){
							$rows[] = array('PHOTO', base64_encode($dt['data15']), array(
								'ENCODING' => 'b',
								'TYPE' => $tt[ $it[2] ],
							));
						}
						
					}
					
					break;
				case 'vnd.android.cursor.item/contact_event':
					if(preg_match('#^(\d{4})(\d{2})(\d{2})$#', $dt['data1'], $t)){
						$rows[] = array('BDAY', "{$t[1]}-{$t[2]}-{$t[3]}");
					}
					break;
				case 'vnd.android.cursor.item/organization':
					if($dt['data1']){
						$rows[] = array('ORG', $dt['data1']);
					}
					break;
				case 'vnd.android.cursor.item/postal-address_v2':
					if($dt['data1']){
						$rows[] = array('ADR', array('','',$dt['data4'],$dt['data7'],$dt['data8'],$dt['data9'],$dt['data10']));
						// $dt['data2'] == '2' => TYPE=WORK
					}
					break;
				
				case 'vnd.android.cursor.item/name':
					break;
				case 'vnd.com.google.cursor.item/contact_misc':
					break;
				case 'vnd.android.cursor.item/group_membership':
					break;
				case 'vnd.android.cursor.item/identity':
					break;
				default:
					echo "UNKNOWN DATA $dt_id ({$dt['mimetype']['mimetype']}):\r\n";
					var_dump(array_filter($dt, 'is_scalar'));
					fgets(STDIN);
			}
			
		}
		
		
	}
	
	
	if($rows){
		echo $vc = vc($rows);
		$vcs .= $vc;
	}
	
	//fgets(STDIN);
	
	//break;
}

file_put_contents('contacts2.vcf', $vcs);