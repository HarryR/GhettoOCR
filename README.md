## GhettoOCR

This project was hacked together in a day to extract data tables from images,
because of the quick and rather naive implementation it only handles fixed-width
non-antialiased "Courier New" at 12pt, but manages to do a reasonable job of it.

### Problems / Features

 * Written in PHP / Very Portable
 * Extremely slow / Easy to Debug
 * Nearly no testing / Open-source
 * Does nothing fancy / Easy to understand
 * Cannot differentiate between '0' (zero) and 'O' in Courier New 12pt.

### Design Concepts

The software uses an automatically generated 'possibility intersection table' (made-up term)
to logically deduce which character exists within the search area. 

The table is built by counting which letters have white or black pixels at each
position within the fixed sized font area. The 'possible letters' are eliminated
by performing an intersection of all letters which have a black or white pixel
against the previous list of possible letters.

Example:
```
Letter E  | Letter L
          |
######    | ###
 #   #    |  #
 # #      |  #
 ###      |  #
 #        |  #
```

Would build a table with:
```
0x0: E,L
1x0: E,L
2x0: E,L
3x0: E
```

The starts of lines are identified by performing a brute force search for the first
letter, afterwards the whole lines are scanned from left to right to produce the text
in the correct output order.

### Copyright

The code should be considered public domain, fonts and included images/text may not
be used with modified versions of the software as they are for demonstration purposes only.

### Why?

Because I went through 6 commercially available pieces of OCR software which were 
unable to extract the demo data with reasonable accuracy (even with extensive training
and manual tweaking).

Big respect to the developers PrimeOCR: the only comercially available software which 
was able to achieve this deceptively simple task.

http://primerecognition.com/augprime/prime_ocr.htm
