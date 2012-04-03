<?php
    

/*
    Copyright David Ciamberlano (info@dir2web.it)

	This file is part of dir2web version 3 (www.dir2web.it).
	concept and programming: David Ciamberlano (info@dir2web.it)

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


class dbHelper 
{
	
	
	
	public function askAdv ($preparedStatement, $params)
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


	public function create ($preparedStatement, $params)
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
	
}    
    
?>