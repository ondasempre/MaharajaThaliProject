/**
 *     Tiny Carousel Horizontal Slider
 *     Copyright (C) 2011 - 2014 www.gopiplus.com
 *     http://www.gopiplus.com/work/2012/05/26/tiny-carousel-horizontal-slider-wordpress-plugin/
 * 
 *     License: GPLv2 or later
 *     License URI: http://www.gnu.org/licenses/gpl-2.0.html *	
 *     
 */

function tch_submit()
{
	if((document.tch_form.tch_width.value=="") || (isNaN(document.tch_form.tch_width.value)))
	{
		alert("Please enter the image width. only number.")
		document.tch_form.tch_width.focus();
		return false;
	}
	else if((document.tch_form.tch_height.value=="") || (isNaN(document.tch_form.tch_height.value)))
	{
		alert("Please enter the image height. only number.")
		document.tch_form.tch_height.focus();
		return false;
	}
	else if((document.tch_form.tch_display.value=="") || (isNaN(document.tch_form.tch_display.value)))
	{
		alert("Please enter the display. only number.")
		document.tch_form.tch_display.focus();
		return false;
	}
	else if((document.tch_form.tch_intervaltime.value=="") || (isNaN(document.tch_form.tch_intervaltime.value)))
	{
		alert("Please enter the interval time. only number.")
		document.tch_form.tch_intervaltime.focus();
		return false;
	}
	else if((document.tch_form.tch_duration.value=="") || (isNaN(document.tch_form.tch_duration.value)))
	{
		alert("Please enter the duration. only number.")
		document.tch_form.tch_duration.focus();
		return false;
	}
	else if(document.tch_form.tch_folder.value=="")
	{
		alert("Please enter the image folder location.\nExample: wp-content/plugins/tiny-carousel-horizontal-slider/images/")
		document.tch_form.tch_folder.focus();
		return false;
	}
}

function tch_delete(id)
{
	if(confirm("Do you want to delete this record?"))
	{
		document.frm_tch_display.action="options-general.php?page=tiny-carousel-horizontal-slider&ac=del&did="+id;
		document.frm_tch_display.submit();
	}
}	

function tch_redirect()
{
	window.location = "options-general.php?page=tiny-carousel-horizontal-slider";
}

function tch_help()
{
	window.open("http://www.gopiplus.com/work/2012/05/26/tiny-carousel-horizontal-slider-wordpress-plugin/");
}