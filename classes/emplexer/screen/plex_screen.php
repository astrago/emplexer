    <?php

class PlexScreen extends BaseScreen implements ScreenInterface, TemplateCallbackInterface
{

    private $cachemanager;

    function __construct($key=null, $func=null) {
        parent::__construct($key);
        // hd_print("teste ==== $key");
        // echo "teste\n";
        $this->cachemanager =  new CacheManager('/tmp/cache');
        if (isset($func)){
            $a= explode("||", $func);
            // var_dump($a);
            $this->generatePlayList($a[1]);
        }
    }


	public function generateScreen(){


		$viewGroup = (string)$this->data->attributes()->viewGroup;

        if ((isset($this->data->attributes()->content)
            &&$this->data->attributes()->content == "plugins")
            ||(isset($this->data->attributes()->identifier)
            && !strstr((string)$this->data->attributes()->identifier, "library"))){
            $viewGroup = 'plugins';
        }

		if (!$viewGroup && strstr($this->path, 'metadata')){
			$viewGroup = 'play';
		}
		$data = $this->getTemplateByType($viewGroup);
        //download images
        // $this->cachemanager->exec();
        // $this->cachemanager->clear();
        return $data;
	}


    public function generatePlayList($key)
    {
        $url = Client::getInstance()->getUrl(null, "/library/metadata/$key/children");
        // $url .= "?unwatched=1";

        // var_dump($url);

        $xml = Client::getInstance()->getAndParse($url);
        // var_dump($xml);
        foreach ($xml as $value) {
            // hd_print($value->Video->attributes()->title);
        }

    }

	/**
	 * Exec the media with default dune player and refresh screen after the playback stops
	 */
	public function templatePlay(){
        $item = isset($this->data->Video[0]) ? $this->data->Video[0] : null;
        $item = isset($this->data->Track[0]) && is_null($item)? $this->data->Track[0] : $item;
        $item = isset($this->data->Photo[0]) && is_null($item)? $this->data->Photo[0] : $item;

		$url=Client::getInstance()->getUrl(null, (string)$item->Media->Part->attributes()->key);
		$parentUrl =  Client::getInstance()->getUrl(null, (string)$item->attributes()->parentKey . "/children") ;
		$invalidate =  ActionFactory::invalidate_folders(array($parentUrl));
        // hd_print(__METHOD__ . ":" . print_r($this->data->Video[0]->attributes()->ratingKey, true));

        if (!$this->isPlexSync){
            $key = $this->data->Video[0]->attributes()->ratingKey;
            $viewOffset = isset($this->data->Video[0]->attributes()->viewOffset) ? $this->data->Video[0]->attributes()->viewOffset : 0 ;
            Client::getInstance()->startMonitor($key, $viewOffset);
        }

		return ActionFactory::launch_media_url($url,$invalidate);

	}

	public function getField($name, $item){

    	if (strstr($name, "gui_skin") || strstr($name, "cut_icon") ){
            // hd_print("gui_skin or cut_icon detected returning the nam $name");
    		return $name;
    	} else {
    		$fields = explode("||", $name);
    	}

		$currentPath = $this->path;
        foreach ($fields as $value) {
            $field =  explode(":", $value);
            if (count($field) <=1){
                // hd_print("single value that's not plex_field returning name $name");
                return $name;
          }

            if ($field[0] === "plex_field"){
                if (!isset($this->data->attributes()->{$field[1]})) continue;
                $ret = $this->data->attributes()->{$field[1]};
            } else if ($field[0] === "plex_thumb_field") {
                if (!isset($this->data->attributes()->{$field[1]})) continue;
                $ret = Client::getInstance()->getThumbUrl($this->data->attributes()->{$field[1]}, isset($field[2])? $field[2]:null, isset($field[3])? $field[3]:null);
            } else  if ($field[0] === "plex_image_field"){
                if (!isset($this->data->attributes()->{$field[1]})) continue;
                $ret = Client::getInstance()->getUrl($currentPath, $this->data->attributes()->{$field[1]},$this->isPlexSync);
            }
            if (isset($item)){

				if ($field[0] === "plex_thumb_item_field") {
	                if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = Client::getInstance()->getThumbUrl($item->attributes()->{$field[1]}, isset($field[2])? $field[2]:null, isset($field[3])? $field[3]:null);
	            } else  if ($field[0] === "plex_image_item_field"){
                    if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = Client::getInstance()->getUrl($currentPath, $item->attributes()->{$field[1]}, $this->isPlexSync);
                    // hd_print("name $name, item $ret currentPath $currentPath" );
	            } else if ($field[0] === "plex_item_field"){
	                if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = $item->attributes()->{$field[1]};
	            } else if ($field[0] === "xpath"){
                    $ret = "teste";
                    //TODO: implement xpath replacement
                }
	        }

            if (strstr($field[0], "thumb")){
                $ret = $this->cachemanager->addSession($ret);
                $ret = $ret;
            }
	        if (isset($ret)){
                $a = gettype($ret) == "object" ? TranslationManager::getInstance()->getTranslation((string)$ret):TranslationManager::getInstance()->getTranslation($ret);
                // hd_print("returning plex_fiel value $a");
	        	return $a;
	        }
        }
	}

    public function getData(){
    	return $this->data;
    }

    public function getMediaUrl($data){
    	return Client::getInstance()->getUrl($this->path , (string)$this->data->attributes()->key, $this->isPlexSync);
    }

}


 ?>