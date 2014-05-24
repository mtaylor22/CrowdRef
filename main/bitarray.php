<?php
class bitArray {
	var $sizeOfInt; // number of bits in an integer
	var $intArray; // array of chars holding the data

	function bitArray($len) { // constructor, len is number of bits in array
		$this->sizeOfInt = 32;
		// initialize the array
		$n = (int)($len/$this->sizeOfInt)+1;
		for ($i=0;$i<$n;$i++)
			$intArray[$i] = (int)0;
	}

	function setBit($i,$val) {
		// determine which array item to set
		$index = (int)($i/($this->sizeOfInt));
		// determine which bit to set
		$setBit = $i % $this->sizeOfInt;
		// clear the bit
		for ($i=0;$i<$this->sizeOfInt;$i++) {
			if ($i != $setBit)
				$bMask |= (1 << $i); 
			else
				$bMask & (0 << $setBit);
		}
		$this->intArray[$index] &= $bMask;

		// create mask and set the bit
		$bMask = $val << $setBit;

		$this->intArray[$index] = $this->intArray[$index] | $bMask;
	}

	function getBit($i) {
		// determine which array item to set
		$index = (int)($i/($this->sizeOfInt));

		// determine which bit to set
		$setBit = $i % $this->sizeOfInt;

		// create mask and set the bit
		$bMask = (int)0;
		$bMask = 1 << $setBit;

		return abs((int)(($this->intArray[$index] & $bMask) >> $setBit) );

	}

	function setData($data) {
		$this->intArray = $data;
	}

	function getData() {
		return $this->intArray;
	}
}
?>