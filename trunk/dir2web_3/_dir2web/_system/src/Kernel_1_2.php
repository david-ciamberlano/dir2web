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

/**
 *-----------------------------------------------
 * 	kernel version 1.1 (last update 15/08/2011)
 *-----------------------------------------------
 */


class Kernel
{
    // class variables
    private $timeStamp;
    private $question;            
	
    private $kernelStatusCode;
    private $kernelStatusMessage;
    
    private $queryStatusCode;
    private $queryStatusMessage;
    
    private $systemVars;
    
    private $db;
    
    // public property

    
	/**
	 * Initialize the kernel
	 */
    function __construct()
    {	
        $this->timeStamp = time();
				
        // get the system Var
        if (! $this->systemVars = parse_ini_file(D2W_SYSTEM_PATH.'/config/config.ini.php', true))
        {
             $this->printKernelMessage ("cannot get System vars");
             die ();
        }			
		 
        $this->db = null;
        
        // check< the DB existence
        $this->checkDB ();
        
        
    }
    
	
	public function checkDB ()
	{
		
        if (file_exists(D2W_DB_PATH))
        {
            //open DB           
            try
            {
                $this->db = new PDO('sqlite:'.D2W_DB_PATH);	
            }              
            catch (PDOException $pdoe)
            {
                $this->kernelCriticalError ('Kernel Error: DB not found');

                if (!is_readable(D2W_DATA_PATH))
                {
                    $this->kernelCriticalError ('Please set read permission to: '.D2W_DATA_PATH);            
                }
                
                if ( is_writable( D2W_DB_PATH ) )
                {
                    return true;
                }
                
                die();
            }
            
            
        }
        else
        {
            // create the DB
            $sql1 = "CREATE TABLE [structure] (
                [id_node] vaRCHAR(10) DEFAULT '0' NOT NULL,
                [id_ancestor] vARCHAR(10) DEFAULT '0' NOT NULL,
                [distance] iNTEGER DEFAULT '0' NOT NULL,
                PRIMARY KEY ([id_node],[id_ancestor])
                )";

            $sql2 = "CREATE TABLE [webobject] (
                [id] VARCHAR(10)  PRIMARY KEY NULL,
                [name] VARCHAR(40)  NULL,
                [path] VARCHAR(255)  NULL,
                [id_father] VARCHAR(10) DEFAULT '0' NULL,
                [creation_time] DATE DEFAULT '01-01-2000' NULL,
                [exists_at] INTEGER DEFAULT '0' NULL,
                [type] VARCHAR(5) DEFAULT 'other' NULL,
                [position] INTEGER DEFAULT '100' NULL,
                [classification] VARCHAR(6) DEFAULT 'object' NULL,
                [hidden] VARCHAR(1) DEFAULT 'n' NULL,
                [collection] CHAR(10)  NULL,
                [preview] BLOB NULL
                )";

            $sql3 = "CREATE TABLE [webpage] (
                [id] VARCHAR(20)  PRIMARY KEY NULL,
                [title] VARCHAR(60)  NULL,
                [path] VARCHAR(255)  NULL,
                [fingerprint] VARCHAR(32) DEFAULT '0' NULL,
                [exists_at] TIMESTAMP DEFAULT '0' NULL,
                [checked_at] TIMESTAMP DEFAULT '''0''' NULL,
                [hidden] vARCHAR(1) DEFAULT 'n' NULL,
                [position] INTEGER DEFAULT '100' NULL,
                [theme] VARCHAR(30) DEFAULT 'd2w_default' NOT NULL,
                [cache] BLOB NULL
                )";
            
            $sql4 = "CREATE INDEX [idx_webpage] ON [webpage] ([id])";
            
            $sql5 = "CREATE INDEX [idx_webobject] ON [webobject] ([id])";
            
            $sql6 = "CREATE INDEX [idx_structure] ON [structure] ([id_node], [id_ancestor])";

            $sql7 = "insert into webpage (id,title,path) Values ('homepage','homepage','_dir2web/homepage')";

            //create and open DB           
            try
            {
                $this->db = new PDO('sqlite:'.D2W_DB_PATH);	
                
                $this->db->query($sql1);
                $this->db->query($sql2);
                $this->db->query($sql3);
                $this->db->query($sql4);
                $this->db->query($sql5);
                $this->db->query($sql6);
                $this->db->query($sql7);
                
            }              
            catch (PDOException $pdoe)
            {
                $this->kernelCriticalError ('Kernel Error: DB not found');
                die();
            }		
            
            return true;
            
        }
        			
	}
	
    /**
     * set the result of the last operation done
     * @param $code : numeric code of the status
     * @param $message : text message of the status
     * @return unknown_type
     */
    private function setKernelStatus ($code, $message='ok')
    {
        $this->kernelStatusCode = $code;
        $this->kernelStatusMessage = $message;
    }
    
    
    /**
     * get the result of the last operation
     * @return array (int statusCode, string statusMessage)
     */
    public function getStatus ()
    {
    	return $this->kernelStatusCode;
    }
    
    public function getErrorMessage ()
    {
    	return $this->kernelStatusMessage;
    }
    
    
    /**
    * Get System Vars
    */
    public function getImagesLabel ($currentPath)
    {
    	$labels = array();        
     	if ( file_exists($currentPath.'/_image.label'))
		{
			$labels = parse_ini_file($currentPath.'/_image.label');
		}		
				
		return $labels;
    }
   
    
	/**
    * Get System Vars
    */
    public function getSystemVars ()
    {       
    	return $this->systemVars;
    }
    
    
	/**
     * returns a text from a text file
     * page is the current page to retrieve (lenght=system parameter->txt_page_size)
     * page=-1: get the whole page
     */
    public function getText ($filePath)
    {
        $text = "";               

        if ( $text = file_get_contents($filePath, 0,null,0,$this->systemVars['text']['max_txt_length']) )
        {
            $this->setKernelStatus(1);
            return $text;
        }
        else
        {
            $this->setKernelStatus (-1 , "file not found");        
            return ''; 
        }
        
    }
    
    
    /**
     * 
     * @param $wpid: id of the webpage
     * @return unknown_type
     */
	public function getPageTheme ($wpid)
	{

            $themePath = "";

            if ($wpid == "homepage")
            {
                $themePath = D2W_THEME_PATH.'/'.$this->systemVars['global']['theme'].'/'.$this->systemVars['global']['home_theme_file'];
            }
            else
            {
                $themePath = D2W_THEME_PATH.'/'.$this->systemVars['global']['theme'].'/'.$this->systemVars['global']['default_theme_file'];
            }
            
            $template = $this->getText ($themePath);

            $this->setKernelStatus (1);
            return $template;
            

            // error
            $this->setKernelStatus (-1,"theme not found: ".D2W_THEME_PATH."/".$this->systemVars['global']['theme'].'/'.$theme[0]['theme']);
            return -1;
		        
	}
    
    
	/**
     * Returns title, path and parent_id of the current page
     */    
    public function getPageInfo ($pageId)
    {
		
        $this->question = "SELECT title, path, hidden, position, theme FROM webpage WHERE id = '$pageId' LIMIT 1";
        $response = $this->ask (true);
        
         if ($this->queryStatusCode >0 && isset ($response[0]) )
         {	         
         	 $_oinfo = array();
         	 
         	 $_oinfo['title'] = $response[0]['title'];
	         $_oinfo['path']= $response[0]['path'];
	         $_oinfo['hidden'] = $response[0]['hidden'];
	         $_oinfo['position'] = $response[0]['position'];
	         $_oinfo['theme'] = $response[0]['theme'];
	        
         	if ($pageId == "homepage")
	         {
	            $_oinfo['parentid'] = "homepage";                  
	         }
	         else
	         {
	            $this->question = "SELECT id_ancestor FROM structure WHERE id_node = '$pageId' AND distance = 1 LIMIT 1";
	            $ancestor = $this->ask (true);
	            
	            $_oinfo['parentid'] = $ancestor[0]['id_ancestor'];
	         }
	         
	         $this->setKernelStatus (1);
         	 return $_oinfo; 
         }
         else 
         {
         	$this->setKernelStatus (-1, "cannot get Page Infos");
         	return array ();
         }    
    }
	
	
	/**
     * Build a single Object getting  info from the DB
     */      
    public function getObjectInfo ($objectId)
    {
        $this->question = "SELECT name, path, id_father, exists_at, type, position, classification, hidden FROM webobject WHERE id='".$objectId."'";
        $objectInfo = $this->ask (true);
        
        if ($this->queryStatusCode > 0)
        {
        	$this->setKernelStatus (1);			
        	return $objectInfo[0];
        }
        else
        {
        	$this->setKernelStatus (-1, "cannot get Object Infos");
        	return -1;
        }
    }
	
	
	/**
     * Get the node list (node= subpage of a given page)
     * return an array [id=>'title']
     */
    public function getPagesList ($pageId, $order)
    {
        $this->question = "SELECT w.id as wid, w.title as wtitle, w.hidden as whidden FROM structure as s, webpage as w WHERE s.id_ancestor='".$pageId."' AND s.distance=1 AND w.id=s.id_node AND w.hidden <> 'y' ORDER BY ";

        switch ($order)
        {
            case '0-9':
                $this->question .= "w.position ASC";
            break;
            
            case '9-0':
                $this->question .= "w.position DESC";
            break;
            
            case 'a-z':
                $this->question .= "w.title ASC";
            break;
            
            case 'z-a':
                $this->question .= "w.title DESC";
            break;
        }
               
        $nodes = $this->ask (true);
		
        if ($this->queryStatusCode > 0)
        {
	        $nodeList = array ();
	        
	        // nodeList contains an array: [id][title] = title and [id][hidden] = yes|no
	        foreach ($nodes as $node)
	        {
	            $nodeList [$node['wid']]['title']=$node['wtitle'];
	            $nodeList [$node['wid']]['hidden']=$node['whidden'];
	        }
        	
	        $this->setKernelStatus (1);
	        return $nodeList;        	
        }
    	else
        {
        	$this->setKernelStatus (-1, "cannot get Object Infos");
        	return array();
        }        
        
    }
    
    
	/**
     * return a list of Images contained in the current directory
     */
    public function getImagesList ($pageId, $order, $gallery='')
    {
		
    	if ($gallery == '')
    	{
    		$this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND classification='image' AND hidden <> 'y' ";
    	}
    	else 
    	{
    		$this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND classification='image' AND collection='".$gallery."' ";
    	}
    	
    	
    	switch ($order)
        {
            case '0-9':
            	$this->question .= "ORDER BY position ASC";
            break;
                
            case '9-0':
                    $this->question .= "ORDER BY position DESC";
            break;

            case 't1-t2':
                    $this->question .= "ORDER BY creation_time DESC";
            break;

            case 't2-t1':
                    $this->question .= "ORDER BY creation_time ASC";
            break;

            case 'a-z':
                    $this->question .= "ORDER BY name ASC";
            break;

            case 'z-a':
                    $this->question .= "ORDER BY name DESC";
            break;
        }
        
        $returnValues = $this->ask (true);
        if ($this->queryStatusCode >0)
        {
            $this->setKernelStatus (1);
            return $returnValues;
        }
        else
        {
            $this->setKernelStatus (-1, "cannot get Object List");
            return array ();
        }
		
    }
    
    
/**
     * return a list of "WebObject" (files in the current directory)
     */
    public function getObjectsList ($pageId, $order)
    {
		switch ($order)
        {
            case '0-9':
            	$this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY position ASC";
			break;
                
            case '9-0':
                    $this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY position DESC";
            break;

            case 't1-t2':
                    $this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY creation_time DESC";
            break;

            case 't2-t1':
                    $this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY creation_time ASC";
            break;

            case 'a-z':
                    $this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY name ASC";
            break;

            case 'z-a':
                    $this->question = "SELECT id, hidden FROM webobject WHERE id_father='".$pageId."' AND hidden <> 'y' ORDER BY name DESC";
            break;
        }
            
        $return = $this->ask (true);
        if ($this->queryStatusCode >0)
        {
            $this->setKernelStatus (1);
        	return $return;
        }
        else
        {
        	$this->setKernelStatus (-1, "cannot get Object List");
        	return array ();
        }
		
    }
    
    
	/**
     * get the treepath (path from the root to the current webPage)
     * returns an array [id=>title]
     */
    public function getPath ($pageId)
    {
        $path = array ();
    	
    	$this->question = "SELECT title FROM webpage WHERE id='".$pageId."'";
        $currentPage = $this->ask (true);

        if ($this->queryStatusCode > 0)
        {
            $path['last']=$currentPage[0]['title'];
        }
        else $path['last']="";
	    
        $this->question = "SELECT s.id_ancestor, s.distance, w.title FROM structure as s left join webpage as w WHERE s.id_node='".$pageId."' AND w.id=s.id_ancestor ORDER BY s.distance DESC";
        $pathElements = $this->ask (true);        
        
        if ($this->queryStatusCode >0)
        {
	        
            foreach ($pathElements as $page)
            {
                $path [$page['id_ancestor']]=$page['title'];
            }

                $this->setKernelStatus (1);
                    return $path;
        }
    	else
        {
            $this->setKernelStatus (-1, "cannot get the Path");
            return $path;
        }
    
    }

    
	/**
    * return the size of a file...
    * @param String $filePath
    * @return <type>
    */
   public function getFileSize ($filePath)
   {
        $fileSize = filesize($filePath);
        if ($fileSize!== false)
        {        
			$this->setKernelStatus (1);
			return $fileSize;
		}
	   	else
		{
			$this->setKernelStatus (-1, "cannot get Path");
        	return -1;
		}
   }
   
   public function checkFileExists ($filePath)
   {
   		if  (file_exists($filePath))
		{
			$this->setKernelStatus (1);
			return true;
		}
   		else
		{
			$this->setKernelStatus (-1, "file does not exists");
        	return false;
		}
   }
   
   
    
    
    /**
     * Check if there are differences between the node and the corresponding directory     
     * returns: 
     * 1 no change
     * 2 Update
     * -1: ERROR no webpage for the id
     * -2: ERRROR dir does not exists
     */
    public function checkCurrentPage ($pageId)
    {
        
        // get info about the current page	
        $this->question = "SELECT title, path, fingerprint, exists_at, checked_at FROM webpage WHERE id = '$pageId' LIMIT 1";              
		$currentPageInfo = $this->ask (true);
        
        $newFingerprint = '';
		
        if ($this->queryStatusCode <0 || count($currentPageInfo)<1)
        {
            // the id doesn't correspont to an existing webpage...
            $this->setKernelStatus (-1, "ID not found: ".$pageId);
            return -1;
        }
        else // the current page exsists 
        {
            // compute md5 for the contents of the current folder...
                                              
            $handleNode = @openDir ($currentPageInfo[0]['path']);

            if ($handleNode === false)
            {
                $this->setKernelStatus (-2,  "dir not found: ".$currentPageInfo[0]['path']);
            	return -2;
            }

            $_tempFingerprint = $this->systemVars['global']['seed'];
            while (false !== ($element = readdir($handleNode)))
            {
                if ($element != '..' && $element != '.')
                {
                	$_tempFingerprint .= $element;
                }                
            }
            
            $newFingerprint = md5 ($_tempFingerprint);

        }
        
        // if new md5 == old md5 then no change has happend... and a deep check isn't needed         
        if ($currentPageInfo[0]['fingerprint'] == $newFingerprint)
        {
            $this->setKernelStatus (1,"NO changes in the dir");
            return 1;
        }
        else
        {
        	// update the current page            
			if ($this->siteUpdate ($pageId)>0)
			{
				$this->setKernelStatus (1,"Update done");
            	return 2;
			}			
        }
    }// end check
    
    

/**
 * ----------------------------------------------
 *  Private Functions
 *----------------------------------------------
 */  
    
    /**
     * store a new web page in DB and keep up-to-date the structure
     */
    private function storeNewPage ($father_id, $id,  $title, $path,  $fingerprint, $exists_at, $theme  )
    {

        // update the Webpage table
        $hidden = 'n';
        $position = 100;
        
        /*---------------------------------
         *  get special parameters
          ---------------------------------*/

         // hidden '_' & secret '^'
         $firstChar = substr ($title, 0,1);

         if ($firstChar=='_')
         {
            $hidden = 'y';
            $title = substr ($title,1);
         }
         elseif ($firstChar=='^')
         {
            $hidden = 'p';
            $title = substr ($title,1);
         }

        // position !nnn-
        $special = Array ();
        $numSpc = preg_match('/^(!([0-9]{1,3})\-)(.*)/', $title, $special);
        
        if ($numSpc >0 && $special[1]!=null && $special[1]!='')
        {
            $position = $special[2];
            $title = $special[3];
        }  

        $_title = sqlite_escape_string($title);
        $_path = sqlite_escape_string($path);

        // store the new webpage in 'WebPage'
        $sql = "INSERT INTO webpage (id, title, path,fingerprint, exists_at, checked_at, hidden, position, theme) VALUES ('$id', '$_title', '$_path', '$fingerprint',  $exists_at, 0, '$hidden',$position,'$theme')";
        $this->dbUpdate($sql);
        
        // update the Structure table
                
        // insert the current page in Structure
        $sql= "INSERT INTO structure VALUES ('$id', '$father_id', 1 )";
        $this->dbUpdate($sql);
        
        // insert all the sons in Structure.
        
        // retrieve all the ancestor of the father page (that are ancestor of the current page too, but with different distance!)
        $this->question = "SELECT distance, id_ancestor FROM structure WHERE id_node = '$father_id' ";        
        $ancestors= $this->ask(true);

        // for each row [X(=father), Y(=ancestor), N(=distance)] (let Z=current page) 
        // insert in the structure table a row like: [Z, Y, N+1]   
        foreach ($ancestors as $ancestor)
        {
            $sql = "INSERT INTO structure (id_node, id_ancestor, distance) VALUES ('$id', '".$ancestor['id_ancestor']."', ".($ancestor['distance'] + 1).")";
            $this->dbUpdate($sql);
        }

    }

        
        
     /**
      * Store a new object into the database
      */   
    private function storeNewObject ($pageId, $objectId, $name, $path, $timeStamp)
    {
    	$info = pathinfo ($path);
            
        $name = $info['filename'];
        $type = strtolower( $info['extension'] );
        $hidden = 'n';
        $position = 0;  
        $creationTime = filectime ($path);    
        $classification = '';
        $collection = '';
        $imageData = '';
        $preview = 'null';
        
        // get the standard classification & position (analizing the file-type)
        switch ($type)
        {
            case 'png':
            case 'jpg':    
			case 'gif':     
				$classification = 'image';    							                
                $position = 100;                
			break;
            
            case 'swf':
                $classification = 'flash';
                $position = 100;
            break;
			
			case 'webm':
			case 'flv':
			case 'mov':		
			case 'mp4':
				$classification = 'video';
                $position = 100;
			break;
			
			case 'mp3':
				$classification = 'audio';
				$position = 100;
			break;

            case 'txt':
                $classification = 'text';
                $position = 150;
            break;
            
            case 'panel':
                $classification = 'panel';
                $position = 100;
            break;
                        
            default:
                $classification = 'object';
                $position = 10;
        }
        
		
        /**
         * ----------------
         * filters (chain)
         * ----------------
         */

        // get the special prefix of an object
        
        $objFirstChar = substr ($name, 0,1);
        
    	switch ($objFirstChar)
    	{
    		// hidden object	
    		case "_":
    			$hidden='y';
    			$name = substr ($name, 1);
    		break;    			
    			
			// order number	
    		case "!":
                $special_pos = array ();
    			if ( preg_match('/!(\d{1,3})\-(.+)/', $name, $special_pos) )
    			{
    				$position = $special_pos[1];
    				$name = $special_pos[2];
    			}    			
    		break;
    		
			// collection
    		case "#":
    			if ( preg_match('/#(\w{1,10})\-(.+)/', $name, $special_pos) )
    			{
    				$hidden='y';
    				$name = $special_pos[2];
    				$collection = $special_pos[1];
    			}
    		break;
    		
			// private
    		case "[":
    			// private element!
    			$hidden='y';
    			$name = substr ($name, 1);
    		break;
    	}
        
        /*
        
        $special = array ();

        $numOfMatch = preg_match('/(_|!(\d\d\d)\-|#(\w{1,10})\-|\[)?(.+)/', $name, $special_pos);
        
        if ($numOfMatch>0)
        {
            if ($special_pos[1]=='_')
            {
                $hidden='y';            
            }
            elseif ($special_pos[1]=='[')
            {
                $hidden='y';   
            }
            elseif ($special_pos[1]=='!')
            {                
	            if ($special_pos[2]!='')
	            {
	                $position = $special_pos[3];
	            }
            }
            
            $name = $special_pos[4];
            
        
        }
        */
    	
    	
        /**
         * ------------------
         * store in DB
         * ------------------
         */ 
         /*
        $_name = sqlite_escape_string($name);
        $_path = sqlite_escape_string($path);
        $_type = sqlite_escape_string($type);
        */
        $question = "INSERT INTO webobject (id, name, path, id_father, exists_at, creation_time, type, position, classification, hidden, collection, preview) "
        	."values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		
		if ($type == 'png' || $type == 'jpg')
		{
			$preview = $this->makeThumbnail($path, null);
					
		}
		else $preview = 'null';
		
		$params = array
		(
			array ('value'=>$objectId, 'type'=>PDO::PARAM_STR),
			array ('value'=>$name, 'type'=>PDO::PARAM_STR),
			array ('value'=>$path, 'type'=>PDO::PARAM_STR),
			array ('value'=>$pageId, 'type'=>PDO::PARAM_STR),
			array ('value'=>$timeStamp, 'type'=>PDO::PARAM_INT),
			array ('value'=>$creationTime, 'type'=>PDO::PARAM_STR),
			array ('value'=>$type, 'type'=>PDO::PARAM_STR),
			array ('value'=>$position, 'type'=>PDO::PARAM_INT),
			array ('value'=>$classification, 'type'=>PDO::PARAM_STR),
			array ('value'=>$hidden, 'type'=>PDO::PARAM_STR),
			array ('value'=>$collection, 'type'=>PDO::PARAM_STR),
			array ('value'=>$preview, 'type'=>PDO::PARAM_LOB)					
		);
        
        $this->askAdv($question, $params);
		
		
    }
    
    
    private function kernelCriticalError ($msg)
    {
    	$this->showMessage($msg, 'er');
    	die ();
    }


	public function makeThumbnail($src_file, $dest_file)
    {
        				
        $max_x = $this->systemVars['image']['thumb_max_x']; 
        $max_y = $this->systemVars['image']['thumb_max_y'];		
        		
        $image_info = getimagesize($src_file);

        $max_px = 0;
        
        if ($image_info[0]>$image_info[1])
        {
            $max_px = $max_x;
        }
        else
        {
            $max_px = $max_y;
        }
        
        $o_im = null;
        
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
            $t_wd = null;
            $t_ht = null;
            
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
			    imagepng($t_im, null, 70);
			    $imagedata = ob_get_clean();							
            }
            else
            {
                ob_start();
			    imagejpeg($t_im, null, 70);
			    $imagedata = ob_get_clean();

			    // Save file
			    	         	
                //imagejpeg($t_im, null);					
            }
			
            imageDestroy($o_im);
            imageDestroy($t_im);	
							
        }

        return $imagedata;
			
    } 

  
    /**
     * calculate the id (integer (10)) of a page using the path and md5 function
     */
    private function getId ($str)
    {
        return substr( md5 ($str), 1, 10);
    }


	public function getPreview ($oid)
	{
		
        $question = "SELECT preview FROM webobject WHERE id='$oid'";
        $errorText = "";		
		
		// array requested            
        $tmp_statement = $this->db->query($question);

        if ($tmp_statement === false)
        {       
			$this->queryStatusCode = -1;
			$this->queryStatusMessage = $errorText;
            
			return array();	
			
        }
		else
		{
			$this->queryStatusCode = 1;
			$this->queryStatusMessage = 'ok';
						
			$retValue = $tmp_statement->fetchAll();
			
			// close the connection
			$tmp_statement->closeCursor();			
			
			return $retValue;
		}
	}
   
    
    public function getCachedPage ($pageId)
    {
        $this->question = "SELECT cache FROM webpage WHERE id = '$pageId' LIMIT 1";
        
         $cachedPage = $this->ask (true);
         
         return $cachedPage[0];
        
    }
    
    public function setCache ($pageId, $page)
    {
        $this->question = "UPDATE webpage SET cache='$page' WHERE id = '$pageId'";
        
         $cachedPage = $this->ask ();
         
         return $cachedPage;
        
    }

	/*
     * --------------------------------------------------------------
     *      START of deep check (only if check() returns 1)
     *---------------------------------------------------------------
     */     
    private function siteUpdate ($pageId)        
    {
        // get info about the current page
        $this->question = "SELECT title, path, fingerprint, exists_at, checked_at, hidden, theme FROM webpage WHERE id = '$pageId' LIMIT 1";
        $_currentPageInfo = $this->ask (true);
      
        if ( count($_currentPageInfo) < 1)
        {
            // the id doesn't correspont to an existing webpage...
            $this->setStatus (-1,"the id doesn't correspont to an existing webpage...");
            return -1;
        }

		$currentPageInfo = $_currentPageInfo[0];		
		
		$thumbs = array ();
		// get all the thumbnails in the current directory
		// build ad array ['id']=true|false -> true: updated
		if ( $handleNode = @openDir (D2W_DATA_PATH.'/'.$pageId) )
        {
        	while (false !== ($element = readdir($handleNode)))
			{
				// exclude special object '.' & '..'
                if ($element != "." && $element != "..")
                {
                	$thumbs[$element] = false;
            	} 
			}
		} 
        
        //scan the current directory
        if ($handleNode = @openDir ($currentPageInfo['path']))
        {                                   
            //for each son of the current node...
            $fingerprint = $this->systemVars['global']['seed'];
            while (false !== ($element = readdir($handleNode)))
            {                               
                // exclude special object '.' & '..'
                if ($element != "." && $element != "..")
                {      
                    $fingerprint .= $element;
                    
                	$newPath = $currentPageInfo['path']."/".$element;      
                    
                    // if the object is a dir
                    if (is_dir ($newPath))
                    {    
                        /*----------------------------
                         * element is a directory
                         * ----------------------------*/ 

                        // get the id of the current page
                        $newPageId = $this->getId ($newPath);
                        
                        //2.2 if the page doesn't exists in 'WebPage'
                        $this->question = "SELECT id FROM webpage WHERE id = '$newPageId' ";
                        if ( ($this->ask ())<1 ) 
                        {                            
                            //2.2.1 create the current webpage (and update 'Structure')
                            $currentFingerPrint = md5 ($fingerprint);
                            $this->storeNewPage ($pageId, $newPageId, $element, $newPath, $currentFingerPrint, $this->timeStamp, /*$currentPageInfo['theme']*/ 'index.html');
                        }
                        else 
                        { 
                            //2.2.2 the webpage exists
                            // update the 'exists_at' parameter
                            // 'exists_at' is usefull to find wath pages will be deleted 
                            $sql = "UPDATE webpage SET exists_at = '".$this->timeStamp."' WHERE id = '$newPageId'";
                            $this->dbUpdate($sql);
                        }
						
                        
                    } //## end ifdir
                    else 
                    {
                        /*----------------------------
                         * element is a file (object)
                         * ----------------------------*/ 
                        $newObjectId = $this->getId ($newPath);
                        
                        $this->question = "SELECT classification FROM webobject WHERE id = '$newObjectId' ";						
						$objType = $this->ask (true);
										                        
                        // check if object exists in the DB
                        if (count($objType)< 1)						
                        {
                            // store the current webobjeect in the DB                              
                            $this->storeNewObject ($pageId, $newObjectId, $element, $newPath, $this->timeStamp );
                        }
                        else
                        { 
                            // the object exsists -> update its timestamp
                            $sql = "UPDATE webobject SET exists_at='".$this->timeStamp."' WHERE id='".$newObjectId."' ";							
                            $this->dbUpdate($sql);
							
							// check the thumbnail
							if ($objType[0]['classification']=='image')
							{
								if (file_exists(D2W_DATA_PATH.'/'.$pageId.'/'.$newObjectId))
								{
									if (isset ($thumbs[$newObjectId]))
									{
										$thumbs[$newObjectId] = true;
									} 									
								}
							}
                        }
												
						
				
                    }

                } //## end if !.& !..    
            } //## end while    
            
            
            @closedir($handleNode);


            /*-------------------------------------------------
             * update the fingerprint of the current page
             * -------------------------------------------------*/ 
            $newFingerprint = md5 ($fingerprint);					
			
            $sql = "UPDATE webpage SET fingerprint = '$newFingerprint', exists_at=$this->timeStamp, checked_at=$this->timeStamp WHERE id = '$pageId'";
            $this->dbUpdate($sql);

             /*-------------------------------------------------
              * Delete the old pages (that doesn't yet exist in the current directory)
              * 
			  * 0. delete the old thumbnails
              * 1. get the old sons
              * 2. for each old son 
              * 2.1     delete all the descendents from webpage & structure
              * 2.2     delete all the object contained in all descendents
              * 2.3      delete the current old son from webpage and structure  
              * -------------------------------------------------*/
            
            // delete all the old thumbnails			
			foreach ($thumbs as $id => $updated) 
			{				
				if (!$updated && file_exists(D2W_DATA_PATH.'/'.$pageId.'/'.$id))
				{
					unlink(D2W_DATA_PATH.'/'.$pageId.'/'.$id);					
				}
			}
            
            //get all the old sons of the current node
            $this->question ="SELECT id_node FROM  webpage as w, structure as s "
            	 ."WHERE w.id = s.id_node AND s.id_ancestor = '".$pageId."' AND s.distance = 1 AND w.exists_at < ".$this->timeStamp; 
            $oldSons = $this->ask(true);

            if (count($oldSons) > 0)
            {
                foreach ($oldSons as $oldSon)
                {
                    // retrieve all the descendents of the current son 
                    $this->question ="SELECT id_node FROM  webpage as w, structure as s 
                           WHERE w.id = s.id_node AND s.id_ancestor = '".$oldSon['id_node']."'";
                    $descendents = $this->ask(true);
                    
					
                    if (count($descendents)>0)
                    {
                        // delete all the descendent
                        foreach ($descendents as $descendent)
                        {
                            //delete descendents from 'webpage'
                            $this->question = "DELETE FROM webpage WHERE id='".$descendent['id_node']."'";
                            $this->ask();
                            
                            // ...and from "structure"
                            $this->question = "DELETE FROM structure WHERE id_node='".$descendent['id_node']."'";
                            $this->ask();

                            // delete the old webobject (not yet contained in the page)
                            $this->question = "DELETE FROM webobject WHERE id_father='".$descendent['id_node']."'";
                            $this->ask ();
														
							//$this->delDataFolder($descendent['id_node']);
                        }
                    }
            
                    // delete the current page from 'webpage'...
                    $this->question = "DELETE FROM webpage WHERE id='".$oldSon['id_node']."'";
                    $this->ask();
                    
                    // ...and from "structure"
                    $this->question = "DELETE FROM structure WHERE id_node='".$oldSon['id_node']."'";
                    $this->ask();
                    
                    // delete the old webobject (contained in the old Son)
                    $this->question = "DELETE FROM webobject WHERE id_father='".$oldSon['id_node']."'";
                    $this->ask ();
										
					//$this->delDataFolder($oldSon['id_node']);
					
                }//end foreach oldSons
               
            }
						
        }// if dir
        
        // delete the old webobject (not yet contained in the page)
        $this->question = "DELETE FROM webobject WHERE id_father='".$pageId."' AND exists_at < ".$this->timeStamp;
        $this->ask ();
	
		return 1;
	                
    }//end update
    
    
    /*
    private function delDataFolder ($id_node)
	{
		// delete the corresponding folder in the thumb folder
		if ( $handleNode = @openDir (D2W_DATA_PATH.'/'.$id_node) )
        {
        	while (false !== ($element = readdir($handleNode)))
			{
				// exclude special object '.' & '..'
                if ($element != "." && $element != "..")
                {
                	unlink (D2W_DATA_PATH.'/'.$id_node.'/'.$element);
            	}				
			}			
			
			closedir($handleNode);
			
			// remove the current directory
			rmdir(D2W_DATA_PATH.'/'.$id_node);
		} 
		
	}
    */
    
    
	/**
     * exec a query and set $this->response
     * Also get errors!
     * In case of array request with errors return a void array
     */
    
    
    private function dbUpdate ($sql)
    {
    	
		// array requested            
        $numOfRowsAffected = $this->db->exec($sql);

        if ($numOfRowsAffected === false)
        {       
			$this->queryStatusCode = -1;
			$this->queryStatusMessage = "";
            			
			return -1;            
        }
		else
		{		
			return $numOfRowsAffected;
		}
    }
    
    /**
     * 
     * @param $getArray: 
     * @return Array containing the result of the query
     */
    private function ask ($getArray=false)
    {
        		
        $question = $this->question;
        $errorText = "";		
		
		// array requested            
        $tmp_statement = $this->db->query($question);

        if ($tmp_statement === false)
        {       
			$this->queryStatusCode = -1;
			$this->queryStatusMessage = $errorText;
            
			if ($getArray)
			{
				return array();	
			}
			else return -1;
            
        }
		else
		{
			$this->queryStatusCode = 1;
			$this->queryStatusMessage = 'ok';
						
			$retValue = $tmp_statement->fetchAll();
			
			if (!$getArray)			
			{
				$retValue = count($retValue);
			}
			
			// close the connection
			$tmp_statement->closeCursor();
			
			return $retValue;
		}
		
        
    }


	private function askAdv ($preparedStatement, $params, $getArray=false)
    {
        //open DB
        $db = null;   
        try
        {
        	$db = new PDO('sqlite:'.D2W_DB_PATH);
        }              
        catch (PDOException $pdoe)
        {
            $this->kernelCriticalError ($this->kernelStatusMessage);
			die();
        }
		        
        $errorText = "";
		$tmp_response = null;
        
        if (!$getArray)
        {
            // return the number of the results
            $pst = $db->prepare($preparedStatement);
            
            $numOfParams = count($params);
            for ($i=0; $i < $numOfParams; $i++)
			{
				$pst->bindParam($i+1, $params[$i]['value'], $params[$i]['type']);	
				
            }            		
			
            $tmp_response = $pst->execute();			

            if ($tmp_response === false)
            {
                $this->queryStatusCode = -1;
				$this->queryStatusMessage = $errorText;
                return -1;
            }
			
        }
        else
        { 
        	// array requested            
            $tmp_statement = $db->query($question);

            if ($tmp_response === false)
            {       
				$this->queryStatusCode = -1;
				$this->queryStatusMessage = $errorText;
                return array();
            }
			else
			{
				$tmp_response = $tmp_statement->fetchAll();
			}
        
        }
		
        $this->queryStatusCode = 1;
		$this->queryStatusMessage = 'ok';
				
        return $tmp_response;
    }


	public function showMessage ($message, $type)
	{
	    switch ( strtolower($type) )
	    {
	        case "ok":
	          echo ("<div style=\"background-color: green; color: white; text-align: center\">".$message."</div>");
	        break;
	        
	        case "er":
	          echo ("<div style=\"background-color: red; color: white; text-align: center\">".$message."</div>");
	        break;
	        
	        default:
	          echo ("<div style=\"background-color: red; color: white\">?</div>");
	    }
	}
    
    
}// End of class Kernel
    