<?php
$topic_no = get_start_topic_no();
if ( ! $topic_no ) {
	echo "fail to load start.txt\n";
	return;
}
$last_no = get_last_topic_no();
$answer_list = get_current_topis( $topic_no, $last_no );
show_top_10_answer( $answer_list );
file_put_contents( 'start.txt', $last_no + 1 );

function get_start_topic_no() {
	$fp = fopen('start.txt', 'r');
	$topic_no = false;

	if ( $fp ){
		if ( flock( $fp, LOCK_SH ) ){
			$topic_no = (int) fgets( $fp );
			flock( $fp, LOCK_UN );
		} else {
			print( "Fail to lock file\n" );
		}
	}

	$flag = fclose( $fp );
	return $topic_no;
}

function get_last_topic_no() {
	$rss = file_get_contents( 'http://ja.forums.wordpress.org/rss/' );
	$array = xml_to_array( $rss );
	$link = $array['channel']['item'][0]['link'];
	$link = explode( 'topic/', $link );
	$link = explode( '?', $link[1] );
	$last_topic_no = explode( '#', $link[0] );
	return $last_topic_no[0];
}

function show_top_10_answer( $answer_list ) {
	$i = 1;
	echo "Forum answer count\n";
	foreach ( $answer_list as $name => $answer_cnt ) {
		echo "{$name}:{$answer_cnt}\n";
		$i++;
		if ( $i > 10 ) {
			break;
		}
	}
}

function get_current_topis( $topic_no, $last_no ) {
	$answer_list = array();
	for ( $num = $topic_no; $num<$last_no ; $num++ ) {
		echo "{$num}\n";
		$url = 'http://ja.forums.wordpress.org/rss/topic/' . $num;
		$xml = file_get_contents( $url );
		if ( ! $xml ) {
			continue;
		}
		$array = xml_to_array($xml);
		$items = $array['channel']['item'];
		if ( isset( $items['title'] ) ) {
			// Nobody answered Topis is here .
			// This script only count answer.
			continue;
		} else {
			foreach ( $items as $item ) {
				if ( isset( $answer_list[ $item['dc_creator'] ] ) ) {
					$answer_list[ $item['dc_creator'] ] += 1;
				} else {
					$answer_list[ $item['dc_creator'] ] = 1;
				}
			}
		}
	}
	arsort( $answer_list, SORT_NUMERIC );
	return $answer_list;
}

function xml_to_array( $xml ) {
	$xml = preg_replace( "/<([^>]+?):([^\/;]+?)>/", "<$1_$2>", $xml );
	$obj_xml = simplexml_load_string( $xml, NULL, LIBXML_NOCDATA );
	xml_expand_attributes( $obj_xml );
	$json = json_encode( $obj_xml, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	$json = preg_replace('/\\\\\//', '/', $json );
	$array = json_decode( $json, true );
	return $array;
}

function xml_expand_attributes( $node ) {
	if ( ! $node ) {
		return false;
	}
	if ( $node->count() > 0 ) {
		foreach( $node->children() as $child ) {
			foreach( $child->attributes() as $key => $val ) {
				$node->addChild( $child->getName()."@".$key, $val );
			}
			xml_expand_attributes( $child );
		}
	}
}
