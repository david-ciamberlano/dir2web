<?php

/*
    Copyright David Ciamberlano (info@dir2web.it)

	This file is part of dir2web version 3 (www.dir2web.it).
	concept and programming: David Ciamberlano (info@dir2web.it)
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

class WebPage
{
    private $wpid;

    private $kernel;
    private $systemVars;

    private $title;
	private $siteName;
    private $path;
    private $position;
    private $theme;

    public function __construct ($wpid, $kernel)
    {
        $this->wpid = $wpid;

        $this->kernel = $kernel;//new Kernel();
        $this->systemVars = $this->kernel->getSystemVars ();

        $wpinfo = $this->kernel->getPageInfo($wpid);		
		
		if ( count($wpinfo)>0)
		{
			$this->title = $wpinfo['title'];
			$this->siteName = $this->systemVars['global']['site_name'];
	        $this->path = $wpinfo['path'];
	        $this->position = $wpinfo['position'];
	        $this->theme = $this->systemVars['global']['theme'];			
		}
		else
		{
			// todo: show error page	
            $this->kernel->showMessage ('Error: webpage', 'er');
			die();
		}
        
    }


    /**
     *
     */
    private function getSections ($code)
    {
    	
        $content = $code[1];
		
		$filter = new TextFilters ("");

        // get the sections
        $sectionsList = $this->kernel->getPagesList('homepage', $this->systemVars['page']['pages_order']);

        $finalList="";

        if ( count($sectionsList)>0 )
        {
            $sectionGeneric="";
            $sectionSelected="";

            preg_match("|@default\=\{(.+?)\}|is", $content, $sectionGeneric);
			
            preg_match("|@current=\{(.+?)\}|is", $content, $sectionSelected);
			
			// add the homepage
			/*
			$sr = array("@#ref","@#name" );
			$rp = array("index.php?p=homepage", "home");
			
			
            // if the current key is the page that is current visualized
            if ($this->wpid != "homepage")
            {                					
				$finalList .= str_replace($sr, $rp, $sectionGeneric[1]);					
            }
            else
            {                						
				$finalList .= str_replace($sr, $rp, $sectionSelected[1]);	                    
            }          				            
            */
                      
            // set the sections
            foreach ($sectionsList as $sl_key => $sl_info)
            {            	
                $filtered_sl_title = $filter->lightFilter($sl_info['title']);				

				$sr = array("@#ref","@#name" );
				$rp = array("index.php?p=".$sl_key, $filtered_sl_title);

                // if the current key is the page that is current visualized
                if ($sl_key != $this->wpid)
                {                					
					$finalList .= str_replace($sr, $rp, $sectionGeneric[1]);					
                }
                else
                {                						
					$finalList .= str_replace($sr, $rp, $sectionSelected[1]);	                    
                }               
            }
        }

        return $finalList;
    }

	
    private function getNavs ($code)
    {
        $finalNav = '';

        if ($this->wpid != 'homepage')
        {
            $content = $code[1];

            $nodesList = $this->kernel->getPagesList($this->wpid, $this->systemVars['page']['pages_order']);

            $contentOpen = null;
            $contentClose = null;
            
            if (count($nodesList)>0)
            {                
				// set the open and the close tab				
                preg_match ("|@open\=\{(.+?)\}|is", $content, $contentOpen);          
				preg_match ("|@close=\{(.+?)\}|is", $content, $contentClose);	
				
				if (isset ($contentOpen[1]))
				{
					$finalNav .= $contentOpen[1];	
				}				
				
				$filter = new TextFilters ("");
				
				$navGeneric="";				
	
	            preg_match("|@default\=\{(.+?)\}|is", $content, $navGeneric);
				
                foreach ($nodesList as $nid=>$ninfo)
                {
                    $filteredTitle =  $filter->lightFilter ($ninfo['title']);
					
					$sr = array("@#ref","@#name" );
					$rp = array("index.php?p=".$nid, $filteredTitle);
										
					$finalNav .= str_replace ($sr, $rp ,$navGeneric[1]);
                                        
                }
				
				if (isset ($contentClose[1]))
				{
					$finalNav .= $contentClose[1];	
				}				
            }
        }

        return $finalNav;
    }

    /**
     * get the breeadcrumbs of the current page
     */
    private function getPath ($code)
    {

        $content = $code[1];

        $treePath = $this->kernel->getPath($this->wpid);

        $finalPath = "";

        $filter = new TextFilters ("");
        $page_title = '';
        $html_default = '';

		preg_match("|@default\=\{(.+?)\}|is", $content, $html_default);
				
        foreach ($treePath as $tp_id=>$tp_title)
        {
            if ($tp_id!='last')
            {
                $page_title = $filter->lightFilter($tp_title);
				
				//$finalPath .= preg_replace("|@default=\{(.+?)\}|is", "<a href=\"index.php?p=".$tp_id."\" title=\"".LINK_BACK_TO." &quot;$page_title&quot;\" >".$page_title."</a>\\1", $content);
				
				$sr = array("@#ref","@#name" );
				$rp = array("index.php?p=".$tp_id, $page_title);
				$finalPath .= str_replace($sr, $rp, $html_default[1]);
				
                
            }
        }
		
		// get the last element
        $page_title = $filter->lightFilter($treePath['last']);
		$html_last = '';
		preg_match("|@last\=\{(.+?)\}|is", $content, $html_last);
		
		$sr = array("@#ref","@#name" );
		$rp = array("index.php?p=".$tp_id, $page_title);
		$finalPath .= str_replace($sr, $rp, $html_last[1]);

        return $finalPath;
    }


    /**
     *
     */
    private function getContents ($code)
    {
        $content = $code[1];

        $text = array ();
        $image = array ();
        $download = array ();
        $flash = array ();
        $ilink = array ();
		$video = array ();
		$audio = array ();

        preg_match("|@text=\{(.+?)\}|is", $content, $text);
        
        preg_match("|@image=\{(.+?)\}|is", $content, $image);
       
        preg_match("|@download=\{(.+?)}|is", $content, $download);
        
        preg_match("|@flash=\{(.+?)\}|is", $content, $flash);
		
		preg_match("|@video=\{(.+?)\}|is", $content, $video);
		
		preg_match("|@audio=\{(.+?)\}|is", $content, $audio);
		
		preg_match("|@link=\{(.+?)\}|is", $content, $ilink);		
        
        $objectsList = $this->kernel->getObjectsList ($this->wpid, $this->systemVars['page']['objects_order']);

        $body = "";
            // if there are many objects in this web page...
        if ( count ($objectsList) > 0)
        {
            foreach ($objectsList as $object)
            {
                $wobj = new WebObject ($object['id'], $this->kernel);

                if ($wobj->classification=='text' && array_key_exists(1, $text))
                {
                    $search = array ("|@#title|", "|@#content|", "|@#footer|");
                    $replace = array ($wobj->html_text_title_linked, $wobj->html_text_body, $wobj->html_text_footer);

                    $body .= preg_replace($search, $replace, $text[1]);
                }
                else if ($wobj->classification=='image' && array_key_exists(1, $image))
                {
                    $search = array ("|@#image|", "|@#caption|");
                    $replace = array ($wobj->html_image_body,  $wobj->html_image_footer);

                    $body .= preg_replace($search, $replace, $image[1]);
                }
                else if ($wobj->classification=='object' && array_key_exists(1, $download))
                {
                    $search = array ("|@#download|");
                    $replace = array ($wobj->html_download_body);

                    $body .= preg_replace($search, $replace, $download[1]);
                }
                else if ($wobj->classification=='flash' && array_key_exists(1, $flash) )
                {
                    $search = array ("|@#flash|");
                    $replace = array ($wobj->html_flash_body);

                    $body .= preg_replace($search, $replace, $flash[1]);
                }
				else if ($wobj->classification=='video' && array_key_exists(1, $video) )
                {
                    $search = array ("|@#video|");
                    $replace = array ($wobj->html_video_body);

                    $body .= preg_replace($search, $replace, $video[1]);
                }
				else if ($wobj->classification=='audio' && array_key_exists(1, $audio) )
                {
                    $search = array ("|@#audio|");
                    $replace = array ($wobj->html_audio_body);

                    $body .= preg_replace($search, $replace, $audio[1]);
                }
				
            }

        }
        else if ( array_key_exists(1, $ilink) )// no object in the page
        {
            $pagesList = $this->kernel->getPagesList ($this->wpid, $this->systemVars['page']['pages_order']);

            foreach ($pagesList as $pageId => $pageInfo)
            {
                $search = array ("|@#ref|", "|@#name|");
                $replace = array ("index.php?p=".$pageId, $pageInfo['title']);

                $body .= preg_replace($search, $replace, $ilink[1]);
            }
        }
		


        return $body;
    }


    private function getImageGallery ($code)
    {    		    
        $content = $code[1];
		
		$imageGal = "";
		
		preg_match("|@images=\{(.+?)\}|is", $content, $imageGal);   				

        $image = array ();

        $imagesList = $this->kernel->getImagesList ($this->wpid, $this->systemVars['page']['objects_order'], "gallery");

        $body = "";

            // if there are many objects in this web page...
        if ( count ($imagesList) > 0)
        {
            foreach ($imagesList as $image)
            {
                $wobj = new WebObject ($image['id'], $this->kernel);

                $search = array ("|@#image|", "|@#caption|");
                $replace = array ($wobj->html_image_body,  $wobj->html_image_footer);

                $body .= preg_replace($search, $replace, $imageGal[1]);

            }

        }

        return $body;
    }



    /**
     * build the current web page
     *
     * @param object $wpid
     * @return
     */
    public function show ()
    {
        
        $dirCheck = $this->kernel->checkCurrentPage($this->wpid);

        if ($dirCheck<0)
        {
            // if the page doesn't exists... you will be redirect to the homepage
            if ($this->kernel->checkCurrentPage('homepage')<0)
            {
                echo "Page not found";
                echo $this->kernel->getErrorMessage();
            }
            else $this->wpid = 'homepage';
        }
        else if ($dirCheck == 1)
        {
            $cachedPage = $this->kernel->getCachedPage ($this->wpid);
            if ($cachedPage['cache'] != null)
            {
                return base64_decode($cachedPage['cache']);
            }
        }
        else if ($dirCheck == 2)
        {
            // get the template
            $template = $this->kernel->getPageTheme ($this->wpid);

            if ($this->kernel->getStatus ()<0)
            {
                //d2w_showMessage ($this->kernel->getErrorMessage(), "er");
                die ();
            }

            // get the dir2web html head (script and css)
            $head = "";
            $options = null;

            if (preg_match("/<\?d2w:system_head *\?>/",$template, $options)>0)
            {
                $head ="<!-- start dir2web system heads-->\n" 
                ."<link href=\"".D2W_DEFAULT_PATH."/css/d2w-objects.css\" rel=\"stylesheet\" type=\"text/css\" media=\"screen\" />\n"
                ."<script type=\"text/javascript\" src=\"".D2W_SYSTEM_PATH."/js/jquery.min.js\" ></script>\n"
                ."<script type=\"text/javascript\" src=\"".D2W_SYSTEM_PATH."/js/d2w_controller.js\" ></script>\n"            	
                ."<script type=\"text/javascript\" src=\"".D2W_SYSTEM_PATH."/js/jwplayer/jwplayer.js\"></script>\n"
                ."<!-- end dir2web system heads-->";

                $template = preg_replace("|<\?d2w:system_head *\?>|" , $head, $template);
            }


            // get the section list
            $template = preg_replace_callback ("|<\?d2w:sections(.+?)\?>|is" , array ($this, "getSections"), $template);

            // get the nav list
            $template = preg_replace_callback ("|<\?d2w:nav(.+?)\?>|is" , array ($this, "getNavs"), $template);

            // get the contents
            $template = preg_replace_callback ("|<\?d2w:contents(.+?)\?>|is" , array ($this, "getContents"), $template);

            // get the images
            $template = preg_replace_callback ("|<\?d2w:imagegallery(.+?)\?>|is" , array ($this, "getImageGallery"), $template);

            // get the breadcrumbs (path)
            $template = preg_replace_callback ("|<\?d2w:path(.+?)\?>|is" , array ($this, "getPath"), $template);

            // get the special files (fixed place + default value)
            $template = preg_replace_callback ("|<\?d2w:specialtext(.+?)\?>|is" , array ($this, "getSpecialText"), $template);

            // get the special images (fixed place + default value)
            $template = preg_replace_callback ("|<\?d2w:specialimage(.+?)\?>|is" , array ($this, "getSpecialImage"), $template);


            $template  = preg_replace ("|<\?d2w:title *\?>|is" , $this->title, $template);
            $template  = preg_replace ("|<\?d2w:site_name *\?>|is" , $this->siteName, $template);
            $template  = preg_replace ("|<\?d2w:page_id *\?>|is" , $this->wpid, $template);
            $template  = preg_replace ("|<\?d2w:dir2web_logo *\?>|is" , '<img src="'.D2W_DEFAULT_PATH.'/d2w.png" alt="dir2web logo" height="15" width="80" />', $template);

            $template = preg_replace ("/<\?d2w:theme_path *\?>/is", D2W_THEME_PATH."/".$this->theme, $template);
            $template = preg_replace ("/<\?d2w:default_path *\?>/is", D2W_DEFAULT_PATH, $template);


            // put the page in the cache
            if ($cachedPage['cache'] == null)
            {
                $this->kernel->setCache ( $this->wpid, base64_encode($template) );
            }

            //==================================================
            // send the web page to the browser

            return $template;
        }
        
    }

    /**
     * Get an object (text, image, download)
     *
     * @param object $oid
     * @param object $wpid
     * @param object $page
     * @return
     *
    private function buildObject ($oid, $wpid, $page)
    {
        $WebObject = new WebObject ($oid);
        $object = $WebObject->showFullScreen(1);

        $finalHtml = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">\n"
        ."<html xmlns=\"http://www.w3.org/1999/xhtml\">\n"
        ."<head><title>Dir2web</title>"
        ."<link href=\"_dir2web/system/default/css/d2w_style.css\" rel=\"stylesheet\" type=\"text/css\" media=\"screen\" />"
        ."</head>\n"
        ."<body>\n"
        ."<div id=\"d2w-backlink\"><a href=\"index.php?p=".$wpid."\" title=\"Back\">Indietro</a></div>\n"
        .$object
        ."</body>\n"
        ."</html>\n";

        return $finalHtml;
    }*/


    /**
     * get the special text of the current page
     */
    private function getSpecialText ($code)
    {
		$specialText = "";		
		preg_match("|@text=\{(.+?)\}|is", $code[1], $specialText);  
		
		$fname ="";
        preg_match("|@#([a-zA-Z0-9_]+\.txt)|is", $specialText[1], $fname);
		
		if(!isset($fname[1]))
		{return "";}
		
		$fileName = $fname[1];
        $filePath = $this->path.'/'.$fileName;

        // check if the text file exists in the current dir
        if (!$this->kernel->checkFileExists($filePath))
        {
            if ($this->kernel->checkFileExists(D2W_DEFAULT_PATH.'/'.$fileName))
            {
            	$filePath = D2W_DEFAULT_PATH.'/'.$fileName;
            }
            else
            {
                return "";
            }
        }

        $text = $this->kernel->getText($filePath);
        $filter = new TextFilters ($filePath);

        $filteredText= $filter->filter($text);
		
		$sr = array("@#".$fname[1] );
		$rp = array($filteredText);
										
		$finalText = str_replace ($sr, $rp ,$specialText[1]);

        return $finalText;
    }


    /**
     * get the special text of the current page
     */
    private function getSpecialImage ($code)
    {
		$specialImage = "";		
		preg_match("|@image=\{(.+?)\}|is", $code[1], $specialImage);  
				
		$fname ="";
        preg_match("|@#([a-zA-Z0-9_]+\.jpg)|is", $specialImage[1], $fname);
				
		if(!isset($fname[1]))
		{return "";}
				
		$fileName = $fname[1];
        $filePath = $this->path.'/'.$fileName;

        // check if the text file exists in the current dir
        if (!$this->kernel->checkFileExists($filePath))
        {
            if ($this->kernel->checkFileExists(D2W_DEFAULT_PATH.$fileName))
            {
                    $filePath = D2W_DEFAULT_PATH.'/'.$fileName;
            }
            else
            {
                    return "";
            }
        
		}
	
		$finalImg= "<img src=\"".$filePath."\" alt=\"".$fileName."\" />";
	
	
		$sr = array("@#".$fname[1] );
		$rp = array($finalImg);
										
		$finalText = str_replace ($sr, $rp ,$specialImage[1]);

        return $finalText;

        

        return $finalText;
    }

}//# end of class Main 
?>
