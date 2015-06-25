/****************************/
/* MRU Research             */
/****************************/
function show(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;
	//e.style.display = 'block';

	if(e.className.indexOf("hide")==-1) {
	if(e.className.indexOf("show")==-1)
		e.className+=" show";
	}
	else e.className=e.className.replace("hide","show");
	return false;
}

function hide(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;
	//e.style.display = 'none';
	if(e.className.indexOf("show")==-1) {
	if(e.className.indexOf("hide")==-1)
		e.className+=" hide";
	}
	else e.className=e.className.replace("show","hide");
	return false;
}

function toggle(elem) {
	var e;
	if(elem == null || elem =="" ) return false;
	e = document.getElementById(elem);
	if(e== null) return false;

	if(e.className.indexOf("hide")>-1) show(elem);
	else hide(elem);
	return false;
}

/**
* for cleaning pastes from Word.
*/
function specialCharCleanup(textbox){
    specialchartext = escape($(textbox).val());
    specialchartext = specialchartext.replace(/%u201C/g, "\"");
    specialchartext = specialchartext.replace(/%u201D/g, "\"");
    specialchartext = specialchartext.replace(/%u2018/g, "'");
    specialchartext = specialchartext.replace(/%u2019/g, "'");
    specialchartext = specialchartext.replace(/%u2026/g, "...");
    specialchartext = specialchartext.replace(/%u2013/g, "&ndash;");
    specialchartext = specialchartext.replace(/%u2014/g, "&mdash;");
    specialchartext = specialchartext.replace(/%A9/g, "&copy;");
    specialchartext = specialchartext.replace(/%AE/g, "&reg;");
    specialchartext = specialchartext.replace(/%u2122/g, "&trade;");
    specialchartext = unescape(specialchartext);
    $(textbox).val(specialchartext);
}

/***********************/
/** CV Item Functions **/
/***********************/
function ToggleRelatedTo() {
    if (jQuery("#relatedto").css('display') == 'none') {
        jQuery("#relatedto").fadeIn(250).fadeOut(250).fadeIn(250).fadeOut(250).fadeIn(250);
    } else {
        jQuery("#relatedto").css("display", "none");
    }
}

function OnSave() {
    cvItemId = $('#cvItemId').val();
    casHeadingId = $('#cas_heading_id').val();

    document.cv_item_form.action = "cv.php?cas_heading_id=" + casHeadingId + "&cv_item_id=" + cvItemId + "&mr_action=save";
    document.cv_item_form.value = "save";
    document.cv_item_form.submit();

    return true;
}

/********************/
/** AJAX Functions **/
/********************/

function AjaxToggle(table,key,id,field,state){
    dataString = 'mr_action=ajax_set_flag&table='+table + '&key=' + key + '&id=' + id + '&field=' + field + '&state=' + state;
    $.ajax({
        type: "GET",
        data: dataString,
        url: "/ajax.php",
        dataType: "json",
        error: function(data) {
            // display the error message in our status area
            $('#ajaxMessage'+id).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (1).</p>').hide().fadeIn(1500);
        },
        success: function(data) {
        }
    });
}

function AjaxHeadingToggle(table,cas_heading_id,field,state){
    dataString = 'mr_action=ajax_bulk_set_flag&table='+table + '&cas_heading_id=' + cas_heading_id + '&field=' + field + '&state=' + state;
    $.ajax({
        type: "GET",
        data: dataString,
        url: "/ajax.php",
        dataType: "json",
        error: function(data) {
            // display the error message in our status area
            $('#ajaxMessage'+id).html('<p>Sorry, an error occurred and the "Show on report" change cannot be saved (1).</p>').hide().fadeIn(1500);
        },
        success: function(data) {
            var selector = '.chk'+data['field'];
            if (data['state'] == 'true'){
                $(selector).attr('checked', true);
            }else{
                $(selector).attr('checked', false);
            }
        }
    });
}
