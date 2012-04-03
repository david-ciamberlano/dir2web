<?php
/*
 Copyright David Ciamberlano (info@dir2web.it)

 This file is part of dir2web version 3.
 concept and programming: David Ciamberlano
 * 
 *  	License adopted: GPL 3

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

 
 
class WebObject
{
    // public vars
    public $name;
    public $type;
    public $id;
    public $path;
    public $position;
    public $hidden;
    public $classification;
    public $fatherNodeId;
    public $objectSize;
    public $exists;

    //public html
    public $html_text_title_linked;
	public $html_text_title;
    public $html_text_body;
    public $html_text_footer;

    public $html_image_body;
    public $html_image_footer;

    public $html_download_body;

    public $html_flash_body;
    public $html_flash_footer;
	
	public $html_video_body;
	
	public $html_audio_body;

    // private vars
    private $systemVars;
	
	private $kernel;


    /**
     *
     * @param $id
     * @param $full
     * @param $special : 0 no special; 1 - special text; 2-special Image
     * @return unknown_type
     */
    function __construct($id, $kernel, $full=false, $special=0)
    {
        $this->kernel = $kernel; //new Kernel ();
        $this->systemVars = $this->kernel->getSystemVars();

        $this->id = $id;

        $oInfo = $this->kernel->getObjectInfo ($id);

        //if kernel returns -1 then the object doesn't exist...
        if (count($oInfo) < 0)
        {
            $this->exists = false;
            return false;
        }

        $this->exists = true;

        $this->name = $oInfo['name'];
        $this->type = $oInfo['type'];
        $this->path =  $oInfo['path'];
        $this->position = $oInfo['position'];
        $this->hidden = $oInfo['hidden'];
        $this->classification = $oInfo['classification'];
        $this->fatherNodeId = $oInfo['id_father'];
        $this->objectSize = 0;//$this->kernel->getFileSize ($this->path);

        $this->html_text_title = "";
		$this->html_text_title_linked = "";
        $this->html_text_body = "";
        $this->html_text_footer = "";

        $this->hmtl_image_body = "";
        $this->html_image_footer = "";

        $this->html_download_body = "";

        $this->html_flash_body = "";
        $this->html_flash_footer = "";
		
		$this->html_audio_body = "";
		$this->html_video_body = "";


        //select the correct operation for the object
        switch ($this->classification)
        {
            case "text":
                    $this->getText ($full);
            break;

            case "image":
                    $this->getImage ($full);
            break;

            case "flash":
                    $this->getFlash ();
            break;
			
			case "video":
				$this->getVideo ();
			break;
			
			case "audio":
				$this->getAudio ();
			break;

            case "object":
                $this->getDownload();
            break;					

            default:
                $this->getDownload();

        }
    }



    /**
     *
     * @param object $full [optional]
     * @return
     */
    private function getText ($fulltext=false)
    {
        $filter = new TextFilters ($this->path);

        $rawText = $this->kernel->getText ($this->path);
        $title = $filter->lightFilter ($this->name);
        $match = array();
        if (preg_match('/^\[\@show\](\r\n|\n|\r)/', $rawText, $match)>0 )
        {
            $fulltext = true;

            $ret = strpos($rawText, $match[1]);
            $rawText = substr ($rawText, $ret+strlen($match[1]) );
        }

        $this->html_text_title_linked = "<a href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\">".$title."</a>";
		$this->html_text_title = $title;

        // no wrap specified by the user
        if ( $fulltext )
        {
            $this->html_text_body = $filter->filter ( $rawText );
        }
        else if ( ($wrapPosition = strpos ($rawText, "[@...]")) !== false) // check if exists a wrap code ([...]) in the text
        {
            $this->html_text_footer = "<a name=\"".$this->id."\" href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\">Read</a>";
            $this->html_text_body = $filter->filter (substr ($rawText,0, $wrapPosition));
        }
        elseif ( (strlen($rawText) > $this->systemVars['text']['txt_wrap']) ) // check if the text is longer than the wrap-settings
        {
            // truncate the text
            // get the first space after #WRAP char (it doesn't truncate a word')
            $CRPos = strpos($rawText, " ", $this->systemVars['text']['txt_wrap']);
            $this->html_text_body = $filter->filter (substr($rawText, 0, $CRPos))." [...]";

            $this->html_text_footer = "<a name=\"".$this->id."\" href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\">Read</a>";
        }
        else // text is smaller than WRAP
        {
            $this->html_text_body = $filter->filter ( $rawText );
        }


    }



    /**
     * Get a singe image
     * @param object $full [optional]
     * @return
     */
    private function getImage ($fullImage=false)
    {
		
        $enc_picPath = $this->d2w_urlencode($this->path);

        // get the thumb path
        //$thumbPath = preg_replace("|(.+)\/(?:![0-9]{1,3}_)?(.+\.[0-9a-zA-Z]+)$|", "\\1/_mini-\\2", $this->path);
        //$thumbPath = D2W_DATA_PATH.'/'.$this->fatherNodeId.'/'.$this->id;
        //$enc_thumbPath = $this->d2w_urlencode($thumbPath);
        $imageInfo=getimagesize($this->path);	

        $picture = "";

        // check if thumb exists
        if ($fullImage OR ($imageInfo[0]<=$this->systemVars['image']['thumb_max_x'] AND $imageInfo[1]<=$this->systemVars['image']['thumb_max_y']) )
        {
            	
			// the image is small (or the user has requested a full image)	
            $picture .= "<img src=\"".$enc_picPath."\" alt=\"".$this->name."\" title=\"".$this->name."\" {$imageInfo[3]}/>\n";
        }
		else
        {
            //The Thumbnail exists. show the thumb and create the link thumb -> big
            
            $picture .= "<a name=\"".$this->id."\" title=\"".$this->name."\" href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\" >\n<img src=\"index.php?t=".$this->id."\" alt=\"".$this->name."\" title=\"".$this->name."\" />\n</a>\n";
        }
		/*
        else
        {
        	                  
			$kernel->makeThumbnail($this->path, $thumbPath );
			$thumbInfo=getimagesize($thumbPath);
			
			//$picture .= "<a title=\"".$this->name."\" href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\" >\n<img src=\"".$enc_thumbPath."\" ".$thumbInfo[3]." alt=\"".$this->name."\" title=\"".$this->name."\" />\n</a>\n";
			$picture .= "<a title=\"".$this->name."\" href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\" >\n<img src=\"".$thumbPath."\" alt=\"".$this->name."\" title=\"".$this->name."\" />\n</a>\n";
		               
            //$picture .= "<a href=\"index.php?p=".$this->fatherNodeId."&amp;o=".$this->id."\" rel=\"#overlay\">\n<img src=\"_dir2web/_system/default/images/_no_thumb.png\" alt=\"no thumbnail\" title=\"".$this->name."\" />\n</a>\n";                           
        }
		*/

        $pictureCaption = '';

		// check if exists a file named _images.label
		$labels = $this->kernel->getImagesLabel( dirname($this->path));
		
		if (array_key_exists($this->name, $labels))
		{
			// filter the text
            $filter = new TextFilters ($this->path);
            $pictureCaption = $filter->filter ($labels[$this->name]);
		
		}		

        // check if exists a text file named "_label-*.txt". (it's the label for the current image)
        // search a file with the same name of the current picture with prefix "_label-"
        $labelPath = preg_replace("|(.+)/(![0-9]{1,3}-)?(.+)(\.[0-9a-zA-Z]+)$|", "\\1/_label-\\3.txt", $this->path);

        if (file_exists ($labelPath))
        {
            
            $rawPictureTitle = $this->kernel->getText ($labelPath);

            if ( $this->kernel->getStatus() > 0 AND strlen($rawPictureTitle)>0)
            {
                // filter the text
                $filter = new TextFilters ($this->path);
                $pictureCaption = $filter->filter ($rawPictureTitle);
            }
        }
        else if ($pictureCaption=='')
        {
            if ($this->systemVars['image']['show_caption'] =='1')
            {
                if ($this->systemVars['image']['show_info'] =='1')
                {
                    $pictureCaption = $this->name." (".$imageInfo[0]."x".$imageInfo[1]." - ".$this->type.")";
                }
                else
                {
                    $pictureCaption = $this->name;
                }
            }

        }
								
        $this->html_image_body = $picture;
        $this->html_image_footer = $pictureCaption;
    }




    /**
     * Get a download Object
     * @return
     */
    private function getDownload ()
    {
            $downloadName = $this->name;

            if ($this->systemVars['download']['show_info']=='1')
            {
                    $downloadName = $downloadName." (".$this->type.")";
            }

            $bodyElement ="";
            $enc_dwnPath = $this->d2w_urlencode($this->path);

            switch (strToLower ($this->type))
            {
                    case "pdf":
                    case "rtf":
                    case "odt":
                    case "ods":
                    case "odp":
                    case "doc":
                    case "ppt":
                    case "xls":
                    case "zip":
                    case "rar":
                    case "html":
                    case "htm":
                            $bodyElement = "<a href=\"".$enc_dwnPath."\"><img src=\"_dir2web/_system/default/images/download.png\" alt=\"download the file\"> ".$downloadName."</a>\n";
                            break;

                    default:
                            // the others files... maybe they are dangerous. They will be downloaded in a "special" way
                            $bodyElement = "<a href=\"index.php?p=".$this->fatherNodeId."&amp;d=".$this->id."\">"."<img src=\"_dir2web/_system/default/images/download.png\" alt=\"download the file\"> ".$this->name." (".$this->type.")</a>\n";
            }
            $this->html_download_body = $bodyElement;
    }



    private function getFlash ()
    {
            $picPath = $this->path;
            $enc_picPath = $this->d2w_urlencode ($this->path);

            $flashInfo=getimagesize($picPath);
            $multimedia = "<object data=\"".$enc_picPath."\" type=\"application/x-shockwave-flash\" ".$flashInfo[3].">\n" .
			    "<param name=\"movie\" value=\"".$enc_picPath."\"/>\n" .
			    "<param name=\"quality\" value=\"high\"/>\n" .
			    "<p>flash object</p>".
			    "</object>\n";

            $this->html_flash_body = $multimedia;
            $this->html_flash_footer = $this->name;
    }
	
	
	private function getVideo ()
    {    		           
        $enc_videoPath = $this->d2w_urlencode ($this->path);

		$multimedia =				
				'<video src="'.$enc_videoPath.'" id="d2w_video_'.$this->id.'" >Video not supported</div>'
				.'<script type="text/javascript">jwplayer("d2w_video_'.$this->id.'").setup('
				.'{flashplayer: "'.D2W_SYSTEM_PATH.'/js/jwplayer/player.swf", 
					"stretching": "fill",
					"file": "'.$enc_videoPath.'", 
					"controlbar":"bottom", 										
					"width": "100%",
    				"height": "100%" 
    			  });</script>';
				/*'<div id="mediaspace" >Video not supported</div>'
				."<script type='text/javascript'>
  jwplayer('mediaspace').setup({
    'flashplayer': '".D2W_SYSTEM_PATH."/js/jwplayer/player.swf',
    'file': '".$enc_videoPath."',
    'backcolor': 'CCCCCC',
    'controlbar': 'bottom',
    'width': '400',
    'height': '300'
  });
</script>";*/

        $this->html_video_body = $multimedia;            
    }
	
	private function getAudio ()
    {
    	$enc_audioPath = $this->d2w_urlencode ($this->path);
		    		           
        $multimedia = "<div id='d2w_audio_".$this->id."' >".$this->name."</div><div class='d2w_audio_caption'>".$this->name."</div>"
        ."<script type='text/javascript'>
		  jwplayer('d2w_audio_".$this->id."').setup({
		    'flashplayer': '".D2W_SYSTEM_PATH."/js/jwplayer/player.swf',
		    'file': '".$enc_audioPath."',
		    'duration': '33',
		    'controlbar': 'bottom',
		    'width': '100%',
		    'height': '24'
		  });
		</script>";
		
		$this->html_audio_body = $multimedia;
			/*
		    
		
			$multimedia = '<div id= >audio</div>'
				.'<div class="d2w_audio_caption">'.$this->name.'</div>'			
				.'<script type="text/javascript">jwplayer("d2w_audio_'.$this->id.'").setup('
				.'{file: "http://www.longtailvideo.com/jw/upload/bunny.mp3", duration:"33", flashplayer: "'.D2W_SYSTEM_PATH.'/js/jwplayer/player.swf", stretching: "fill", controlbar:"bottom", autostart: false, provider: "http", width:"400", height: "94"});</script>';					
			
            
			 
			 */        
    }


    //===================================================================================================
    //===================================================================================================
    //===================================================================================================

    private function d2w_urlencode ($url)
    {
            $en_url = urlencode ($url);

            $search = array ('à', 'è', 'é', 'ì', 'ò', 'ù', ' ', '+', '#');
            $replace = array ('%e0','%e8', '%e9', '%ec', '%f2', '%f9', '%20', '%20', '%23');
            $en_url = str_replace($search, $replace, $url);

            return $en_url;
    }
	
	
/*
    private function makeThumbnail($src_file, $dst_file, $max_x, $max_y )
    {
        $image_info = getimagesize($src_file);

        if ($image_info[0]>$image_info[1])
        {
            $max_px = $max_x;
        }
        else
        {
            $max_px = $max_y;
        }

        switch ($image_info['mime'])
        {

            case 'image/jpeg':
                if (imagetypes() & IMG_JPG) $o_im = imageCreateFromJPEG($src_file) ;
                else $stri_err = "err29=immagine JPG non supportata";
                break;

            case 'image/png':
                if (imagetypes() & IMG_PNG) $o_im = imageCreateFromPNG($src_file) ;
                else $stri_err = "err29=immagine PNG non supportata";
                break;

            default:
                $stri_err = "err29=immagine ".$image_info['mime']." non supportata";

        }
		
		
		$imagedata = "";

        if (!isset($stri_err))
        {
            $o_wd = imagesx($o_im) ;
            $o_ht = imagesy($o_im) ;
            if ($o_wd >= $o_ht)
            {
                $t_wd = $max_px;
                $t_ht = round((($max_px*$o_ht)/$o_wd));
            }
            else
            {
                $t_ht = $max_px;
                $t_wd = round((($max_px*$o_wd)/$o_ht));
            }

            $t_im = imageCreateTrueColor($t_wd,$t_ht);
            imagealphablending($t_im, false);
            imagesavealpha($t_im,true);
            $transparent = imagecolorallocatealpha($t_im, 255, 255, 255, 127);
            imagefilledrectangle($t_im, 0, 0, $t_wd, $t_ht, $transparent);

            imageCopyResampled($t_im, $o_im, 0, 0, 0, 0, $t_wd, $t_ht, $o_wd, $o_ht);
            if ($image_info['mime']=='image/png')
            {
            	ob_start();
				imagepng($t_im);					
				$imagedata = ob_get_contents(); // read from buffer
				ob_end_clean();
                
            }
            else
            {
            	ob_start();				
                imagejpeg($t_im);
				$imagedata = ob_get_contents(); // read from buffer					
				ob_end_clean();
				
            }
			
            imageDestroy($o_im);
            imageDestroy($t_im);							
        }

        return $imagedata;
			
    } //chiusura funzione
*/

}// end of class


?>
