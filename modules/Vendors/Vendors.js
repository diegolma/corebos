/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/
document.write("<script type='text/javascript' src='include/js/Mail.js'></script>");
document.write("<script type='text/javascript' src='include/js/Merge.js'></script>");
document.write('<div id="setaddressvendordiv" style="z-index:12;display:none;width:400px;top:30px;left:0;right:0;margin:auto;" class="layerPopup"></div>');

function check4null(form)
{
	var isError = false;
	var errorMessage = "";
	if (trim(form.productname.value) =='') {
		isError = true;
		errorMessage += "\n Product Name";
		form.productname.focus();
	}
	if (isError == true) {
		alert(alert_arr.MISSING_REQUIRED_FIELDS + errorMessage);
		return false;
	}
	return true;
}

function set_return_specific(vendor_id, vendor_name) {
	//getOpenerObj used for DetailView
	var fldName = getOpenerObj("vendor_name");
	var fldId = getOpenerObj("vendor_id");
	fldName.value = vendor_name;
	fldId.value = vendor_id;
}

function set_return_address(vendor_id, vendor_name, street, city, state, code, country,pobox ) {
	jQuery.ajax({
		url: 'index.php?module=Vendors&action=VendorsAjax&file=SelectVendorAddress',
		context: document.body
	})
	.done(function(response) {
			jQuery('#setaddressvendordiv').html(response);
			jQuery('#setaddressvendordiv').show();
			fnvshNrm('setaddressvendordiv');
			jQuery("#vendor_id").val(vendor_id);
			jQuery("#vendor_name").val(vendor_name);
			jQuery("#street").val(street);
			jQuery("#city").val(city);
			jQuery("#state").val(state);
			jQuery("#code").val(code);
			jQuery("#country").val(country);
			jQuery("#pobox").val(pobox);
	});
}

function sva_fillinvalues() {
	var vendor_id = jQuery("#vendor_id").val();
	var vendor_name = jQuery("#vendor_name").val();
	if(typeof(window.opener.document.EditView.vendor_name) != 'undefined')
		window.opener.document.EditView.vendor_name.value = vendor_name;
	if(typeof(window.opener.document.EditView.vendor_id) != 'undefined')
		window.opener.document.EditView.vendor_id.value = vendor_id;
	if (jQuery('#sva_bill').is(':checked')) setReturnAddressBill();
	if (jQuery('#sva_ship').is(':checked')) setReturnAddressShip();
	window.close();
}

function setReturnAddressBill() {
	var street = jQuery("#street").val();
	var city = jQuery("#city").val();
	var state = jQuery("#state").val();
	var code = jQuery("#code").val();
	var country = jQuery("#country").val();
	var pobox = jQuery("#pobox").val();
	if(typeof(window.opener.document.EditView.bill_street) != 'undefined')
		window.opener.document.EditView.bill_street.value = street;
	if(typeof(window.opener.document.EditView.bill_city) != 'undefined')
		window.opener.document.EditView.bill_city.value = city;
	if(typeof(window.opener.document.EditView.bill_state) != 'undefined')
		window.opener.document.EditView.bill_state.value = state;
	if(typeof(window.opener.document.EditView.bill_code) != 'undefined')
		window.opener.document.EditView.bill_code.value = code;
	if(typeof(window.opener.document.EditView.bill_country) != 'undefined')
		window.opener.document.EditView.bill_country.value = country;
	if(typeof(window.opener.document.EditView.bill_pobox) != 'undefined')
		window.opener.document.EditView.bill_pobox.value = pobox;
}

function setReturnAddressShip() {
	var street = jQuery("#street").val();
	var city = jQuery("#city").val();
	var state = jQuery("#state").val();
	var code = jQuery("#code").val();
	var country = jQuery("#country").val();
	var pobox = jQuery("#pobox").val();
	if(typeof(window.opener.document.EditView.ship_street) != 'undefined')
		window.opener.document.EditView.ship_street.value = street;
	if(typeof(window.opener.document.EditView.ship_city) != 'undefined')
		window.opener.document.EditView.ship_city.value = city;
	if(typeof(window.opener.document.EditView.ship_state) != 'undefined')
		window.opener.document.EditView.ship_state.value = state;
	if(typeof(window.opener.document.EditView.ship_code) != 'undefined')
		window.opener.document.EditView.ship_code.value = code;
	if(typeof(window.opener.document.EditView.ship_country) != 'undefined')
		window.opener.document.EditView.ship_country.value = country;
	if(typeof(window.opener.document.EditView.ship_pobox) != 'undefined')
		window.opener.document.EditView.ship_pobox.value = pobox;
}

//MSL
function set_return(product_id, product_name) {
	if(document.getElementById('from_link').value != '') {
		window.opener.document.QcEditView.parent_name.value = product_name;
		window.opener.document.QcEditView.parent_id.value = product_id;
	} else {
		window.opener.document.EditView.parent_name.value = product_name;
		window.opener.document.EditView.parent_id.value = product_id;
	}
}
