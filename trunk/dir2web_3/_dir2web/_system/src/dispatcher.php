<?php
/*
    Copyright David Ciamberlano (info@dir2web.it)

	This file is part of dir2web version 3 (www.dir2web.it).
	concept and programming: David Ciamberlano (info@dir2web.it)

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

require_once (D2W_SOURCE_PATH."/Kernel_1_2.php");

/**
 * 
 * @author David Ciamberlano
 *
 */
class Dispatcher 
{
    // class variables
    //****************
    private $wpid;
    private $op;
    private $oid;
	
	private $kernel;
	

    /**
     *
     */
    public function __construct ()
    {
        $this->wpid = 'homepage'; // default
        $this->oid = '';
        $this->op = 'wbp';

		// create the new DB
		$this->kernel = new Kernel();
		
        /**
         * check each GET parameter and assign it to a class variable
         */
        if (isset ($_GET['p'] ) && preg_match('/[a-zA-Z0-9]{10}/', $_GET['p']))
        {
            $this->wpid = $_GET['p'];
			$this->op = 'wbp';
        }

        // check if the user has requested an object
        if ( isset ($_GET['o']) && preg_match('/[a-zA-Z0-9]{10}/', $_GET['o'])) // object
        {
            $this->oid = $_GET['o'];
            $this->op = 'obj';
        }        
        else if ( isset ($_GET['d']) && preg_match('/[a-zA-Z0-9]{10}/', $_GET['d'])) // Download
        {
                $this->oid = $_GET['d'];
                $this->op = 'dwn';
        }
		else if ( isset ($_GET['t']) && preg_match('/[a-zA-Z0-9]{10}/', $_GET['t'])) // thumbnail
        {
            $this->oid = $_GET['t'];
            $this->op = 'prv';
        }

    }


    function start ()
    {
        /**
         * Possible combos
         *
         * wpid(unsetted) -> get the homepage
         * op=obj && oid=xxxx -> show the corresponding object (text, image, flash, etc)
         * op=wbp && wpid=xxxx -> build the corresponding webpage
         *
         */
       
        switch ($this->op)
        {
            case 'wbp':
                                              
                require_once (D2W_SOURCE_PATH."/WebPage.php");
                require_once (D2W_SOURCE_PATH."/WebObject.php");
                require_once (D2W_SOURCE_PATH."/TextFilters.php");
                
                $wp = new Webpage ($this->wpid, $this->kernel);
                echo $wp->Show ();
            break;

            case 'obj':
                
                require_once (D2W_SOURCE_PATH."/WebObject.php");
                require_once (D2W_SOURCE_PATH."/TextFilters.php");
                
                $wo = new WebObject ($this->oid, $this->kernel, true );
                switch ($wo->classification)
                {
                    case 'image':
                        echo "<div id=\"_d2w_image_container\"><div id=\"_d2w_obj_close\"><img src=\"_dir2web/_system/default/images/close.png\" alt=\"Close\"/><br/>Close</div><div id=\"_d2w_image_body\">".$wo->html_image_body."</div><p id=\"_d2w_image_caption\">".$wo->html_image_footer."</p>";
                    break;

                    case 'text':
                        echo "<div id=\"_d2w_text_container\"><div id=\"_d2w_obj_close\"><img src=\"_dir2web/_system/default/images/close.png\" alt=\"Close\"/><br/>Close</div><h2 id=\"_d2w_text_title\">".$wo->html_text_title."</h2><div id=\"_d2w_text_body\">".$wo->html_text_body."</div><p id=\"_d2w_text_footer\">".$wo->html_text_footer."</p>";
                    break;
                }

            break;		
			
			case 'prv':
				header('Content-Type: image/jpeg');
                
				$img = $this->kernel->getPreview ($this->oid);
				echo $img[0]['preview'];
                
                flush();
				exit;
				
            break;

            case 'dwn':
                // send the object                

                $fileInfo = $this->kernel->getObjectInfo($this->oid);

                // DOWNLOAD generic file
                header("Pragma: public");
                header("Expires: 0");
                header("Content-Type: application/force-download");
                header("Content-Description: File Transfer");
                header("Content-Transfer-Encoding: binary");
                header("Content-Length: ".filesize($fileInfo['path']));
                header("Content-Disposition: attachment; filename=\"".$fileInfo['name'].".".$fileInfo['type']."\";");
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Cache-Control: private", false);

                readfile($fileInfo['path']);
                
                flush();
                exit;
            break;

        }

    }

}
