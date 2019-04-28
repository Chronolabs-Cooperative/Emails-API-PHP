<?php
/**
 * DNS Zone Propogation REST Services API
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @copyright       Chronolabs Cooperative http://syd.au.snails.email
 * @license         ACADEMIC APL 2 (https://sourceforge.net/u/chronolabscoop/wiki/Academic%20Public%20License%2C%20version%202.0/)
 * @license         GNU GPL 3 (http://www.gnu.org/licenses/gpl.html)
 * @package         emails-api
 * @since           1.0.3
 * @author          Dr. Simon Antony Roberts <simon@snails.email>
 * @version         1.0.3
 * @description		A REST API for the creation and management of emails/forwarders and domain name parks for email
 * @link            http://internetfounder.wordpress.com
 * @link            https://github.com/Chronolabs-Cooperative/Emails-API-PHP
 * @link            https://sourceforge.net/p/chronolabs-cooperative
 * @link            https://facebook.com/ChronolabsCoop
 * @link            https://twitter.com/ChronolabsCoop
 *
 */

class CSV
{
    private $fp;
    private $parse_header;
    private $header;
    private $delimiter;
    private $length;
    
    function __construct($file_name, $parse_header=false, $delimiter="\t", $length=8000)
    {
        $this->fp = fopen($file_name, "r");
        $this->parse_header = $parse_header;
        $this->delimiter = $delimiter;
        $this->length = $length;
        $this->lines = $lines;
        
        if ($this->parse_header)
        {
            $this->header = fgetcsv($this->fp, $this->length, $this->delimiter);
        }
        
    }
    //--------------------------------------------------------------------
    function __destruct()
    {
        if ($this->fp)
        {
            fclose($this->fp);
        }
    }
    //--------------------------------------------------------------------
    function get($max_lines=0)
    {
        //if $max_lines is set to 0, then get all the data
        
        $data = array();
        
        if ($max_lines > 0)
            $line_count = 0;
            else
                $line_count = -1; // so loop limit is ignored
                
                while ($line_count < $max_lines && ($row = fgetcsv($this->fp, $this->length, $this->delimiter)) !== FALSE)
                {
                    if ($this->parse_header)
                    {
                        foreach ($this->header as $i => $heading_i)
                        {
                            $row_new[$heading_i] = $row[$i];
                        }
                        $data[] = $row_new;
                    }
                    else
                    {
                        $data[] = $row;
                    }
                    
                    if ($max_lines > 0)
                        $line_count++;
                }
                return $data;
    }
    //--------------------------------------------------------------------
    
}
?>