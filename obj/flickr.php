<?php

class flickr{
	const URL_BASE = 'http://api.flickr.com/services/rest/?';
	
	function __construct($api_key){
		$this->params['api_key'] = $api_key;
		$this->params['format'] = 'php_serial';
		}
	
	private function get($params){
		// build url query
		$args = self::encodeParams(array_merge($this->params,$params));
		$url = self::URL_BASE.implode('&',$args);

		// get resonse
		$ret = file_get_contents($url);
		return unserialize($ret);
		}

	static private function encodeParams($params){
		foreach($params as $k => $i)
			$ret[] = urlencode($k).'='.urlencode($i);
		return $ret;
		}

	function getInfo($photoId){
		$p['method'] = 'flickr.photos.getInfo';
		$p['photo_id'] = $photoId;
		return $this->get($p);
		}

	function getSet($id){
		$p['method'] = 'flickr.photosets.getPhotos';
		$p['photoset_id'] = $id;
		return $this->get($p);
		}
	
	function getSetInfo($id){
		$p['method'] = 'flickr.photosets.getInfo';
		$p['photoset_id'] = $id;
		return $this->get($p);
		}
	
	function getSizes($photoId){
		$p['method'] = 'flickr.photos.getSizes';
		$p['photo_id'] = $photoId;
		return $this->get($p);
		}

	function getPublicPhotos($nsid){
		$p['method'] = 'flickr.people.getPublicPhotos';
		$p['user_id'] = $nsid;
		return $this->get($p);
		}
	}

?>
