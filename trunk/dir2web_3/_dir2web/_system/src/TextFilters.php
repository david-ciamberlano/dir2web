<?php
/*
    Copyright David Ciamberlano (info@dir2web.it)

	This file is part of dir2web 3.
 
 	License adopted: GPL 3

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



class TextFilters 
{
    private $currentLine; // the current line(piece) during the pieces filtering
    //private $numOfLines; // total number of lines (pieces) of the text
    private $currentPath; // is the path to the current obj
    private $filteredText;
    
    function __construct ( $currentPath)
    {
        $this->filteredText =""; 
        $this->currentLine = 0;
        $this->numOflines = 0;
        $this->currentPath = dirname($currentPath);
    }
    
    
    /**
     * Filter used for very short text (like text title or link list)
     */
    public function lightFilter ($unfilteredText)
    {
    	$filteredText = $this->htmlEncode ($unfilteredText);
        $filteredText = $this->accent ($unfilteredText);  
        
        return $filteredText;
    }
    
    
    /**
     * Apply filters to the current text
     */
    public function filter ($unfilteredText)
    {
         /**
          * Filtering starts here 
          */
        $filteredText = "";

        // filter the text line by line 
        
        $unfilteredText = $this->htmlEncode ($unfilteredText);
        
        $unfilteredText = $this->escapeChars($unfilteredText);
        
        // replace all the end-line chars with the sequence "|,..,|";        
        $unfilteredText = str_replace(array ("\r\n", "\r", "\n"), "|,..,|", $unfilteredText);

        //get single paragraph
        $parag = explode(".,|", $unfilteredText);
        
        $filteredText = ""; 
        $lineNum = count($parag);
        
        for ($i=0; $i<$lineNum; $i++)
        {
            $parag[$i] = $this->hrule($parag[$i]);
            $parag[$i] = $this->accent ($parag[$i]);
            //$parag[$i] = $this->title ($parag[$i]);

            $parag[$i] = $this->lists($parag[$i]);

            //$singleLine = $this->shortTextTypography($singleLine);

            // special filters            
            $parag[$i] = $this->images($parag[$i]);
            $parag[$i] = $this->links($parag[$i]);
            
            $parag[$i] = $this->whiteSpaces($parag[$i]);

            $this->filteredText .= str_replace("|,.", "<br/>", $parag[$i]);
            //end of a line
        }
        
        $filteredText = $this->longTextTypography($this->filteredText);
        
        $filteredText = $this->unescapeChars($filteredText);
        $filteredText = $this->wrapper($filteredText);
        
        return $filteredText;
    }
    
    
    private function htmlEncode ($text)
    {
        return htmlentities($text, ENT_COMPAT, "iso-8859-1");
    }
    
    
    /**
     * Detect links within a text
     * syntax: 
     * URL ->   [[description@@http://www.myurl.it]]
     * email -> [[description@@mailto:myemail@email.it]]
     * URL w/o description: http(s)://www.myurl.it
     *
     * @param unknown_type $piece
     * @return unknown
     */
    private function links ($text)
    {
       
        //internal link [[page=###]] description [[.page]]
        //$text = preg_replace('!\[\[page=([0-9a-z]{10})\:\:([a-zA-Z0-9[:space:]\(\)@\.&#;\!\?\'\"-]+)\]\]!', "<a href=\"index.php?wpid=\\1\">\\2</a>", $text);
        $text = preg_replace('!\[@page=&quot;([0-9a-z]{10})&quot;\](.+?)\[\.page\]!', "<a href=\"index.php?wpid=\\1\">\\2</a>", $text);
        
        //link [@link="http://URL"] description [.link] - short = [@l="http://URL"] [.l]
        $text = preg_replace('!\[@link=&quot;(https?:\/\/[a-zA-Z0-9~#%@:\.\/_-]+[a-zA-Z0-9~#%@\/\?=&;_-]+)&quot;\](.+?)\[\.link\]!', "<a href=\"\\1\">\\2</a>", $text);      
        
         //simple link [url]
        $text = preg_replace('!(^|[[:space:]]|\(|\[|&quot;|\'|\*|\;|\:|\.)(https?:\/\/[a-zA-Z0-9~#%@:\.\/_-]+[a-zA-Z0-9~#%@\/\?=&;_-]+)([[:space:]]|\)|\]|&quot;|\'|\*|\�)!', "\\1<a href=\"\\2\">\\2</a><img style=\"border: 0; margin-left: 2px\" src=\"_dir2web/_system/default/images/external_link.png\" alt=\"External Link\" />\\3", $text);
        
        //link [[email=xxx]] description [[.email]]
        $text = preg_replace('!\[@email=&quot;([a-zA-Z0-9~\._-]+@[a-zA-Z0-9~_-]+\.[a-zA-Z]{2,5})&quot;\](.+?)\[\.email\]!', "<a href=\"mailto:\\1\">\\2</a><img style=\"border: 0; margin-left: 2px\" src=\"_dir2web/_system/default/images/mail_link.png\" alt=\"External Link\" />", $text);
        
        return $text;
    }
    
    
    /*
     * rules= --------------- or ================ or ___________________
     */
    private function hrule ($text)
    {

        $text= preg_replace ("!(?:\-{5,}|_{5,}|\={5,}) *(?:\|\,\.)!", "<hr style=\"border: none; color:#aaa; background-color:#aaa; height:1px; width:98%\" />", $text);
        
        return $text;
    }

    
    private function lists ($text)
    {
        //unordered list
        $text = preg_replace ("/^( *\t* *)(\*|\#|\-)(?:\t| )(.+)/", "\\1&bull; \\3", $text);

        //ordered list
        //return preg_replace("/^( *\t* *)(\d\d?|[a-z])(\.|\)|(\.\d\d?){1,5}\)?)( |\t)(.+)/", "<span class=\"ol\">\\1<span class=\"ol_num\">\\2\\3</span> \\6</span>", $text);

        return $text;
    }
    
    
    private function whiteSpaces ($text)
    {
         /*----------------------------------- 
        * White Spaces
       	*-------------------------------------*/              
        $replace = array ("&nbsp;&nbsp;", "&nbsp;&nbsp;&nbsp;&nbsp;");
        $search = array ("  ", "\t");
        return str_replace($search, $replace, $text);
    }
    
    
    /**
     * filters applied to long texts (length > 1 line)
     */
    private function longTextTypography ($text)
    {
                   
        // bold & italics -> **bold**, ""italics""
        // [a-zA-Z0-9.,#%@&\/\=\;\:\'\"_\-\(\)\[\]]

    	/**
         * strip the [...] sign
         */
        $text = str_replace('[@...]', '', $text);
    	
    	
    	$search = array 
    	(
    		"!\*\*\*(.+?)\*\*\*!s",
    		"!\*\*(.+?)\*\*!s",
    		"!\&quot;\&quot;(.+?)\&quot;\&quot;!s",
    		"!\-\-(.+?)\-\-!s",
    		"!\+\+(.+?)\+\+!s",
    		"!\[\[(.+?)\]\]!s",
    		"!\_\_(.+?)\_\_!s",
    		"!\^\^(.+?)\^\^!s",
    		"!\,\,(.+?)\,\,!s",
    		
    		"!\=\=\=\=(.+?)\=\=\=\=(?:\<br *\/\>)?!s",
    		"!\=\=\=(.+?)\=\=\=(?:\<br *\/\>)?!s",
    		"!\=\=(.+?)\=\=(?:\<br *\/\>)?!s",
    	);
    	
    	$replace = array 
    	(
    		"<strong><em>\\1</em></strong>",
    		"<strong>\\1</strong>",
    		"<em>\\1</em>",
    		"<small>\\1</small>",
    		"<big>\\1</big>",
    		"<span style=\"background-color: yellow\">\\1</span>",
    		"<span style=\"text-decoration:underline\">\\1</span>",
    		"<sup>\\1</sup>",
    		"<sub>\\1</sub>",
    	
    		"<h3>\\1</h3>",
    		"<h2>\\1</h2>",
    		"<h1>\\1</h1>",
    	);
        
        $text = preg_replace($search, $replace, $text);
        
        /**
          [[right]]text to right[[.right]]
          [[justify]] text justified [[.justify]]
          [[center]] text centered [[.center]]
         */        
        $search = array
        (
            "[@left]",
            "[.left]",
        	"[@right]",
            "[.right]",
            "[@justify]",
            "[.justify]",
            "[@center]",
            "[.center]"
        );
        
        $replace = array
        (
            "<div style=\"text-align: left;\">",
            "</div>",
        	"<div style=\"text-align: right;\">",
            "</div>",
            "<span style=\"text-align: justify;\">",
            "</span>",
            "<div style=\"text-align: center;\">",
            "</div>"
        );
        
        $text = str_replace($search, $replace, $text);

        /**
         * Text-color 
         * [@color="######"] text [.color]
         */
        $text = preg_replace("!\[\@color=\&quot;(\#[0-9a-fA-F]{6}|white|black|blue|red|green|orange|yellow|gray|purple|maroon|silver)\&quot;\](.+?)\[\.color\]!s", 
        			"<span style=\"color: \\1\">\\2</span>", $text);
        
        /*
         * background color 
         */
        $text = preg_replace("!\[\@bg_color=\&quot;(\#[0-9a-fA-F]{6}|white|black|blue|red|green|orange|yellow|gray|purple|maroon|silver)\&quot;\](.+?)\[\.bg_color\]!s", 
        			"<span style=\"background-color: \\1\">\\2</span>", $text);

        return $text;
    }
    
    
    /**
     * Insert an image into the current text...
     * syntax 
     *
     * @param unknown_type $piece
     * @return unknown
     */
    private function images ($text)
    {
        /**
         * normal image 
         */
        $pattern = "!\[\@image=&quot;([a-zA-Z0-9_\/ -]+\.(jpg|gif|png))&quot; +tip=&quot;(.+?)&quot;\]!i";
        $replace = "<img style=\"border:0px\" src=\"{$this->currentPath}/\\1\" alt=\"\\3\" title=\"\\3\"/>";
        $text = preg_replace($pattern, $replace, $text);

        /**
         * image float left
         */
        $pattern = "!\[\@image_left=&quot;([a-zA-Z0-9_\/ -]+\.(jpg|gif|png))&quot; +tip=&quot;(.+?)&quot;\]!i";
        $replace = "<div style=\"float: left; margin: .5em\"><img style=\"border:0px\" src=\"{$this->currentPath}/\\1\" alt=\"\\3\" title=\"\\3\"/></div>";
        $text = preg_replace($pattern, $replace, $text);

        /**
         * image float right
         */
        $pattern = "!\[@image_right=&quot;([a-zA-Z0-9_\/ -]+\.(jpg|gif|png))&quot; +tip=&quot;(.+?)&quot;\]!i";
        $replace = "<div style=\"float: right; margin: .5em\"><img style=\"border:0px\" src=\"{$this->currentPath}/\\1\" alt=\"\\3\" title=\"\\3\"/></div>";
        $text = preg_replace($pattern, $replace, $text);
        
        return $text;        
    
    }
    
    
    /*
     *  [[wrapper=textfilename.txt]]
     */
    private function wrapper ($text)
    {
        $filenameArray = array ();
        if (preg_match_all('!\[@wrapper=&quot;([_a-zA-Z0-9][a-zA-Z0-9~\/\._[:space:]-]+\.wrapper)&quot;\]!i', $text , $filenameArray)>0)
        {
            foreach ($filenameArray[1] as $filename)
            {
                $filePath = $this->currentPath.'/'.$filename;
                $rawText = "";
    
                // read the file content
                $kernel = new Kernel ();
                $rawText = $kernel->getText ($filePath);
                if ($kernel->getStatus() > 0)
                {
                    $text = preg_replace('!\[@wrapper=&quot;'.$filename.'&quot;\]!i', $rawText, $text);
                }
            }
        }
        
        return $text;      
    }
    
    
    
    private function accent ($text)
    {
    	$search = array ("�", "�", "�", "�", "�", "a'", "e'", "i'", "o'", "u'");
        $replace = array ("&agrave;", "&egrave;", "&igrave;", "&ograve;", "&ugrave;", "&agrave;", "&egrave;", "&igrave;", "&ograve;", "&ugrave;");
        $text = str_replace($search, $replace, $text);

        return str_replace($search, $replace, $text);        
    }
    
    
    private function escapeChars ($text)
    {
        //manage escaped characters (see the end of this function for furter details
        // here each escaped char is incapsulated between \\[[ and ]]\\
        $search = array ("\\\\","\\%","\\&gt;","\\&lt;", "\\=", "\\#", '\\*', '\\&quot;', '\\[', '\\_', '\\]', "\\'", "\\+", "\\-", "\\,", "\\^");
        $replace = array ("|?$|$?|",'|?$%$?|', '|?$))$?|','|?$(($?|', '|?$=$?|', '|?$#$?|', '|?$*$?|', '|?$"$?|', '|?$[$?|', '|?$_$?|', '|?$]$?|', "|?$'$?|", "|?$+$?|", "|?$-$?|", "|?$,$?|", "|?$^$?|");
        return str_replace($search, $replace, $text);
    }
    
    private function unescapeChars ($text)
    {
        $search = array ('|?$*$?|','|?$%$?|','|?$))$?|','|?$(($?|', '|?$=$?|', '|?$#$?|', '|?$"$?|', '|?$[$?|', '|?$_$?|', '|?$]$?|', "|?$'$?|", "|?$+$?|", "|?$-$?|", "|?$|$?|", "|?$,$?|", "|?$^$?|");
        $replace = array ('*','%',"&gt;","&lt;", "=", '#', '&quot;', '[', '_', ']', "'", "+", "-", "\\", ",", "^");
        return str_replace($search, $replace, $text);
    }

}   
?>
