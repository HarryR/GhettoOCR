<?php
class GhettoOCR {
	public $font_table_black;
	public $font_table_white;
	public $font_width;
	public $font_height;

	function char_files() {
		$normal_chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";
		$chars = array();
		for( $i = 0; $i < strlen($normal_chars); $i++ ) {
			$c = $normal_chars[$i];
			$chars[$c] = "font/$c.png";
		}
		$chars['&'] = 'font/ampersand.png';
		$chars['/'] = 'font/forward-slash.png';
		$chars['-'] = 'font/hyphen.png';
		$chars['.'] = 'font/period.png';
		$chars['+'] = 'font/plus.png';
		return $chars;
	}

	/**
	 * Build a table of pixel positions for all characters.
	 */
	function build_font_table() {
		$chars = $this->char_files();
		// Font intersection table
		$black_table = array();
		$white_table = array();
		$max_w = 0;
		$max_h = 0;
		foreach( $chars AS $c => $file ) {
			$img = imagecreatefrompng($file);
			$width = imagesx($img);
			$height = imagesy($img);

			for( $x = 0; $x < $width; $x++ ) {
				for( $y = 0; $y < $height; $y++ ) {
					$rgb = imagecolorat($img, $x, $y);
					$color = imagecolorsforindex($img, $rgb);
					$black = ($color['red'] == 0 && $color['green'] == 0 && $color['blue'] == 0);

					$idx = sprintf("%d,%d", $x, $y);
					if( $black ) {						
						if( ! isset($black_table[$idx]) ) {
							$black_table[$idx] = array();
						}
						$black_table[$idx][$c] = TRUE;
					}
					else {
						if( ! isset($white_table[$idx]) ) {
							$white_table[$idx] = array();
						}
						$white_table[$idx][$c] = TRUE;
					}
					if( $x > $max_w ) $max_w = $x;
					if( $y > $max_h ) $max_h = $y;
				}
			}

			imagedestroy($img);
		}
		$this->font_table_black = $black_table;
		$this->font_table_white = $white_table;
		$this->font_width = $max_w + 1;
		$this->font_height = $max_h + 1;
	}

	function identify_character($img, $start_x, $start_y) {
		$width = imagesx($img);
		$height = imagesy($img);
		$stop_x = $start_x + $this->font_width;
		$stop_y = $start_y + $this->font_height;

		if( ($stop_x) > $width ) return FALSE;
		if( ($stop_y) > $width ) return FALSE;

		$char_files = $this->char_files();

		for( $x = $start_x; $x < $stop_x; $x++ ) {
			for( $y = $start_y; $y < $stop_y; $y++ ) {
				$idx = sprintf("%d,%d", $x - $start_x, $y - $start_y);				
				$rgb = imagecolorat($img, $x, $y);
				$color = imagecolorsforindex($img, $rgb);
				$black = ($color['red'] == 0 && $color['green'] == 0 && $color['blue'] == 0);
				// Eliminate every $char_files key which isn't in the table for this position
				if( $black ) {
					if( ! isset($this->font_table_black[$idx]) ) return FALSE;		
					$char_files = array_intersect_key($char_files, $this->font_table_black[$idx]);
				}
				else {
					if( ! isset($this->font_table_white[$idx]) ) return FALSE;
					$char_files = array_intersect_key($char_files, $this->font_table_white[$idx]);
				}
				if( ! count($char_files) ) {
					return FALSE;
				}
			}
		}
		if( count($char_files) != 1 ) return FALSE;
		
		$keys = array_keys($char_files);
		return array_pop($keys);
	}

	function identify_lines($img) {
		$width = imagesx($img);
		$height = imagesy($img);

		$start_y = 0;
		$start_x = 0;
		$lines = array();
		$max_x = 250;	// Lines must start within first 250px
		for( $y = $start_y; $y < ($height - $this->font_height); $y ++ ) {
			for( $x = $start_x; $x < $max_x; $x++ ) {
				$char = $this->identify_character($img, $x, $y);
				if( $char !== FALSE ) {
					$lines[] = array(
						'c' => $char,
						'x' => $x,
						'y' => $y,
						);
					break;
				}
			}
		}
		return $lines;
	}

	function read_line($img, $line) {
		$str = '';
		$start_x = $line['x'];
		$start_y = $line['y'];
		$width = imagesx($img);
		$stop_x = ($width - $this->font_width);

		$last_char_x = 0;
		$x = $start_x;
		while( $x < $stop_x ) {
			$c = $this->identify_character($img, $x, $start_y);
			if( $c !== FALSE ) {
				$str .= str_repeat(' ', floor(($x - $last_char_x) / $this->font_width) );
				$str .= $c;
				$x += $this->font_width;				
				$last_char_x = $x;
			}
			else {
				$x++;
			}
		}
		return $str;
	}
}

$x = new GhettoOCR();
$x->build_font_table();

//$img = imagecreatefrompng("vehicle_symbol_table/VST Images-276.png");
$img = imagecreatefrompng("ocr-test.png");

$id_start = time();
$lines = $x->identify_lines($img);
$id_end = time();
$i = 0;

$lines_start = time();
$line_total = 0;
$line_count = 0;
foreach( $lines AS $line ) {
	$line_str = $x->read_line($img, $line);
	printf("%s\n", $line_str);
	$line_count++;
}
$lines_end = time();

printf("\n\n");
printf("Scan Layout: %d seconds\n", $id_end - $id_start);
printf("  OCR Lines: %d seconds\n", $lines_end - $lines_start);
printf("   Avg Line: %.2fs\n", ($lines_end - $lines_start) / $line_count);