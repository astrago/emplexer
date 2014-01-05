<?php

class PlexScreen extends BaseScreen implements ScreenInterface, TemplateCallbackInterface
{
	public function generateScreen(){
        // print_r($this->data);

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
		return  $this->getTemplateByType($viewGroup);
	}


	/**
	 * Exec the media with default dune player and refresh screen after the playback stops
	 */
	public function templatePlay(){
        $item = isset($this->data->Video[0]) ? $this->data->Video[0] : null;
        $item = isset($this->data->Track[0]) && is_null($item)? $this->data->Track[0] : $item;
        $item = isset($this->data->Photo[0]) && is_null($item)? $this->data->Photo[0] : $item;

		$url=Client::getInstance()->getUrl(null, (string)$item->Media->Part->attributes()->key );
		$parentUrl =  Client::getInstance()->getUrl(null, (string)$item->attributes()->parentKey . "/children") ;
		$invalidate =  ActionFactory::invalidate_folders(array($parentUrl));
		return ActionFactory::launch_media_url($url,$invalidate);

	}

	public function getField($name, $item){

    	if (strstr($name, "gui_skin") || strstr($name, "cut_icon") ){
    		return $name;
    	} else {
    		$fields = explode("||", $name);
    	}

		$currentPath = $this->path;
        foreach ($fields as $value) {
            $field =  explode(":", $value);
            if (count($field) <=1){
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
                $ret = Client::getInstance()->getUrl($currentPath, $this->data->attributes()->{$field[1]});
            }
            if (isset($item)){

				if ($field[0] === "plex_thumb_item_field") {
	                if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = Client::getInstance()->getThumbUrl($item->attributes()->{$field[1]}, isset($field[2])? $field[2]:null, isset($field[3])? $field[3]:null);
	            } else  if ($field[0] === "plex_image_item_field"){
	                if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = Client::getInstance()->getUrl($currentPath, $item->attributes()->{$field[1]});
	            } else if ($field[0] === "plex_item_field"){
	                if (!isset($item->attributes()->{$field[1]})) continue;
	                $ret = $item->attributes()->{$field[1]};
	            } else if ($field[0] === "xpath"){
                    $ret = "teste";
                    //TODO: implement xpath replacement
                }
	        }
	        if (isset($ret)){
	        	return gettype($ret) == "object" ? (string)$ret : $ret;
	        }
        }
	}

    public function getData(){
    	return $this->data;
    }

    public function getMediaUrl($data){
    	return Client::getInstance()->getUrl($this->path , (string)$this->data->attributes()->key);
    }

}


 ?>