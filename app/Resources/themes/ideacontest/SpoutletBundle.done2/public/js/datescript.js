/*
** msi_copyright.js
** Version: 004 -- 06.22.00
*/

function include_copyright(startyear) {
	var	blob="";
	var msistartyear = 2013;
	var	today=new Date(); 
	var	thisyear = get_full_year(today);
	if (startyear < thisyear && startyear >= msistartyear) {
		blob += startyear + " - ";
	}
	blob += thisyear;
	blob = "&#169; " + blob + " Platform D. All rights reserved.";
	return blob;
}

/* 
** subroutine: get_full_year
** param1: d -> date.  any date object.
*/
function get_full_year(d) { // d is a date object
	yr = d.getYear();
	if (yr < 1000) {
	 	yr+=1900;
	}
	return yr;
}
