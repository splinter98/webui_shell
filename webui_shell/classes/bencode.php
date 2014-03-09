<?php
/*

Copyright (c) 2006, Depthstrike Entertainment.
Module Author - Harold Feit - dwknight@depthstrike.com
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
* Neither the name of the Depthstrike Entertainment nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
* Use of this package or module without this license agreement present is only permitted with the express permission of Depthstrike Entertainment administration or the author of the module, either in writing or electronically with the digital PGP/GPG signature of a Depthstrike Entertainment administrator or the author of the module.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.


2008-09-26 Tjores Maes: Merged two seperate files and made minute layout changes. The program is technically unmodified.


	Programming info

All functions output a small array, which we'll call $return for now.

$return[0] is the data expected of the function
$return[1] is the offset over the whole bencoded data of the next
           piece of data.

numberdecode returns [0] as the integer read, and [1]-1 points to the
symbol that was interprented as the end of the interger (either "e" or
":"). 
numberdecode is used for integer decodes both for i11e and 11:hello there
so it is tolerant of the ending symbol.

decodelist returns $return[0] as an integer indexed array like you would use in C
for all the entries. $return[1]-1 is the "e" that ends the list, so [1] is the next
useful byte.

decodeDict returns $return[0] as an array of text-indexed entries. For example,
$return[0]["announce"] = "http://www.whatever.com:6969/announce";
$return[1]-1 again points to the "e" that ends the dictionary.

decodeEntry returns [0] as an integer in the case $offset points to
i12345e or a string if $offset points to 11:hello there style strings.
It also calls decodeDict or decodeList if it encounters a d or an l.


Known bugs:
- The program doesn't pay attention to the string it's working on.
  A zero-sized or truncated data block will cause string offset errors
  before they get rejected by the decoder. This is worked around by
  suppressing errors.

*/

// Protect our namespace using a class
class BDecode
{

	function numberdecode($wholefile, $start)
	{
		$ret[0] = 0;
		$offset = $start;
	
		// Funky handling of negative numbers and zero
		$negative = false;
		if ($wholefile[$offset] == '-')
		{
			$negative = true;
			$offset++;
		}
		if ($wholefile[$offset] == '0')
		{
			$offset++;
			if ($negative)
				return array(false);
			if ($wholefile[$offset] == ':' || $wholefile[$offset] == 'e')
			{
				$offset++;
				$ret[0] = 0;
				$ret[1] = $offset;
				return $ret;
			}
			return array(false);
		}
		while (true)
		{
	
			if ($wholefile[$offset] >= '0' && $wholefile[$offset] <= '9')
			{
				
				$ret[0] *= 10;
				$ret[0] += ord($wholefile[$offset]) - ord("0");
				$offset++;
			}
			// Tolerate : or e because this is a multiuse function
			else if ($wholefile[$offset] == 'e' || $wholefile[$offset] == ':')
			{
				$ret[1] = $offset+1;
				if ($negative)
				{
					if ($ret[0] == 0)
						return array(false);
					$ret[0] = - $ret[0];
				}
				return $ret;
			}
			else
				return array(false);
		}
	
	}
	
	function decodeEntry($wholefile, $offset=0)
	{
		if ($wholefile[$offset] == 'd')
			return $this->decodeDict($wholefile, $offset);
		if ($wholefile[$offset] == 'l')
			return $this->decodelist($wholefile, $offset);
		if ($wholefile[$offset] == "i")
		{
			$offset++;
			return $this->numberdecode($wholefile, $offset);
		}
		// String value: decode number, then grab substring
		$info = $this->numberdecode($wholefile, $offset);
		if ($info[0] === false)
			return array(false);
		$ret[0] = substr($wholefile, $info[1], $info[0]);
		$ret[1] = $info[1]+strlen($ret[0]);
		return $ret;
	}
	
	function decodeList($wholefile, $start)
	{
		$offset = $start+1;
		$i = 0;
		if ($wholefile[$start] != 'l')
			return array(false);
		$ret = array();
		while (true)
		{
			if ($wholefile[$offset] == 'e')
				break;
			$value = $this->decodeEntry($wholefile, $offset);
			if ($value[0] === false)
				return array(false);
			$ret[$i] = $value[0];
			$offset = $value[1];
			$i ++;
		}
	
		// The empy list is an empty array. Seems fine.
		$final[0] = $ret;
		$final[1] = $offset+1;
		return $final;
	
	
	
	}
	
	// Tries to construct an array
	function decodeDict($wholefile, $start=0)
	{
		$offset = $start;
		if ($wholefile[$offset] == 'l')
			return $this->decodeList($wholefile, $start);
		if ($wholefile[$offset] != 'd')
			return false;
		$ret = array();
		$offset++;
		while (true)
		{	
			if ($wholefile[$offset] == 'e')
			{
				$offset++;
				break;
			}
			$left = $this->decodeEntry($wholefile, $offset);
			if (!$left[0])
				return false;
			$offset = $left[1];
			if ($wholefile[$offset] == 'd')
			{
				// Recurse
				$value = $this->decodedict($wholefile, $offset);
				if (!$value[0])
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset= $value[1];
				continue;
			}
			else if ($wholefile[$offset] == 'l')
			{
				$value = $this->decodeList($wholefile, $offset);
				if (!$value[0] && is_bool($value[0]))
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			}
			else
			{
	 			$value = $this->decodeEntry($wholefile, $offset);
				if ($value[0] === false)
					return false;
				$ret[addslashes($left[0])] = $value[0];
				$offset = $value[1];
			}
		}
		if (empty($ret))
			$final[0] = true;
		else
			$final[0] = $ret;
		$final[1] = $offset;
	   	return $final;
	}

} // End of class declaration.

class BEncode
{

	// Dictionary keys must be sorted. foreach tends to iterate over the order
	// the array was made, so we make a new one in sorted order. :)
	/*
	function makeSorted($array)
	{
		$i = 0;
	
		// Shouldn't happen!
		if (empty($array))
			return $array;
	
		foreach($array as $key => $value)
			$keys[$i++] = stripslashes($key);
		sort($keys);
		for ($i=0 ; isset($keys[$i]); $i++)
			$return[addslashes($keys[$i])] = $array[addslashes($keys[$i])];
		return $return;
	}
	*/
	// Encodes strings, integers and empty dictionaries.
	// $unstrip is set to true when decoding dictionary keys
	function encodeEntry($entry, &$fd, $unstrip = false)
	{
		if (is_bool($entry))
		{
			$fd .= "de";
			return;
		}
		if (is_int($entry) || is_float($entry))
		{
			$fd .= "i".$entry."e";
			return;
		}
		if ($unstrip)
			$myentry = stripslashes($entry);
		else
			$myentry = $entry;
		$length = strlen($myentry);
		$fd .= $length.":".$myentry;
		return;
	}
	
	// Encodes lists
	function encodeList($array, &$fd)
	{
		$fd .= "l";
	
		// The empty list is defined as array();
		if (empty($array))
		{
			$fd .= "e";
			return;
		}
		for ($i = 0; isset($array[$i]); $i++)
			$this->decideEncode($array[$i], $fd);
		$fd .= "e";
	}
	
	// Passes lists and dictionaries accordingly, and has encodeEntry handle
	// the strings and integers.
	function decideEncode($unknown, &$fd)
	{
		if (is_array($unknown))
		{
			if (isset($unknown[0]) || empty($unknown))
				return $this->encodeList($unknown, $fd);
			else
				return $this->encodeDict($unknown, $fd);
		}
		$this->encodeEntry($unknown, $fd);
	}
	
	// Encodes dictionaries
	function encodeDict($array, &$fd)
	{
		$fd .= "d";
		if (is_bool($array))
		{
			$fd .= "e";
			return;
		}
		// NEED TO SORT!
		//$newarray = $this->makeSorted($array);
		ksort($array, SORT_STRING);
	
		foreach($array as $left => $right)
		{
			$this->encodeEntry($left, $fd, true);
			$this->decideEncode($right, $fd);
		}
		$fd .= "e";
		return;
	}

} // End of class declaration.

// Use this function in your own code.
function BEncode($array)
{
	$string = "";
	$encoder = new BEncode;
	$encoder->decideEncode($array, $string);
	return $string;
}

// Use this function. eg:  BDecode("d8:announce44:http://www. ... e");
function BDecode($wholefile)
{
	$decoder = new BDecode;
	$return = $decoder->decodeEntry($wholefile);
	return $return[0];
}

?>