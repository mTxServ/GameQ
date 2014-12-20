<?php
/**
 * This file is part of GameQ.
 *
 * GameQ is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * GameQ is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Mincraft Protocol Class
 *
 * Thanks to https://github.com/xPaw/PHP-Minecraft-Query for helping me realize this is
 * Gamespy 3 Protocol.  Make sure you enable the items below for it to work.
 *
 * Information from original author:
 * Instructions
 *
 * Before using this class, you need to make sure that your server is running GS4 status listener.
 *
 * Look for those settings in server.properties:
 *
 * 	enable-query=true
 * 	query.port=25565
 *
 * @author Austin Bischoff <austin@codebeard.com>
 */
class GameQ_Protocols_Minecraft extends GameQ_Protocols_Gamespy4
{
	protected $name = "minecraft";
	protected $name_long = "Minecraft";

	protected $port = 25565;
	
	
    /*
     * Internal methods
     */
	protected function preProcess_all($packets)
    {
    	$return = array();
    	// Get packet index, remove header
        foreach ($packets as $index => $packet)
        {
        	// Make new buffer
        	$buf = new GameQ_Buffer($packet);
        	// Skip the header
            $buf->skip(14);
            
            try {
	            // Get the current packet and make a new index in the array
	            $return[$buf->readInt16()] = $buf->getBuffer();
            } catch (GameQ_ProtocolsException $e) {
            	// fucking hack :x
            }
        }
        unset($buf);
        // Sort packets, reset index
        ksort($return);
        // Grab just the values
        $return = array_values($return);
        // Compare last var of current packet with first var of next packet
        // On a partial match, remove last var from current packet,
        // variable header from next packet
        for ($i = 0, $x = count($return); $i < $x - 1; $i++)
        {
            // First packet
            $fst = substr($return[$i], 0, -1);
            // Second packet
            $snd = $return[$i+1];
            // Get last variable from first packet
            $fstvar = substr($fst, strrpos($fst, "\x00")+1);
            // Get first variable from last packet
            $snd = substr($snd, strpos($snd, "\x00")+2);
            $sndvar = substr($snd, 0, strpos($snd, "\x00"));
            // Check if fstvar is a substring of sndvar
            // If so, remove it from the first string
            if (strpos($sndvar, $fstvar) !== false)
            {
                $return[$i] = preg_replace("#(\\x00[^\\x00]+\\x00)$#", "\x00", $return[$i]);
            }
        }
        // Now let's loop the return and remove any dupe prefixes
        for($x = 1; $x < count($return); $x++)
        {
        	$buf = new GameQ_Buffer($return[$x]);
        	$prefix = $buf->readString();
        	// Check to see if the return before has the same prefix present
        	if($prefix != null && strstr($return[($x-1)], $prefix))
        	{
        		// Update the return by removing the prefix plus 2 chars
        		$return[$x] = substr(str_replace($prefix, '', $return[$x]), 2);
        	}
        	unset($buf);
        }
        unset($x, $i, $snd, $sndvar, $fst, $fstvar);
        // Implode into a string and return
		return implode("", $return);
    }
}
