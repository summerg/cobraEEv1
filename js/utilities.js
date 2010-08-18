
// Browser Detection
isMac = (navigator.appVersion.indexOf("Mac")!=-1) ? true : false;
NS4 = (document.layers) ? true : false;
IEmac = ((document.all)&&(isMac)) ? true : false;
IE4plus = (document.all) ? true : false;
IE4 = ((document.all)&&(navigator.appVersion.indexOf("MSIE 4.")!=-1)) ? true : false;
IE5 = ((document.all)&&(navigator.appVersion.indexOf("MSIE 5.")!=-1)) ? true : false;
ver4 = (NS4 || IE4plus) ? true : false;
NS6 = (!document.layers) && (navigator.userAgent.indexOf('Netscape')!=-1)?true:false;

// Body onload utility (supports multiple onload functions)
var gSafeOnload = new Array();
function SafeAddOnload(f)
{
	if (IEmac && IE4)  // IE 4.5 blows out on testing window.onload
	{
		window.onload = SafeOnload;
		gSafeOnload[gSafeOnload.length] = f;
	}
	else if  (window.onload)
	{
		if (window.onload != SafeOnload)
		{
			gSafeOnload[0] = window.onload;
			window.onload = SafeOnload;
		}		
		gSafeOnload[gSafeOnload.length] = f;
	}
	else
		window.onload = f;
}

function SafeOnload()
{
	for (var i=0;i<gSafeOnload.length;i++)
		gSafeOnload[i]();
}

// Call the following with your function as the argument
// SafeAddOnload(yourfunctioname);


//inputs:
//	mode =  1,2
//	formObj = e.g. document.<formname>
function enableAutoPopupFields(mode,formObj)	
{
	changeTextMode(formObj.autopopupURL, mode);
	changeTextMode(formObj.autopopupWidth, mode);
	changeTextMode(formObj.autopopupHeight, mode);
}

//used by subfeatures/edit.asp
function enablePopupFields(mode,formObj)	
{
	changeTextMode(formObj.popupWidth, mode);
	changeTextMode(formObj.popupHeight, mode);
}


//inputs:
//	mode =  1,0
//	formObj = e.g. document.<formname>
function enableDealerFields(mode,formObj)	
{
	changeTextMode(formObj.dealername, mode);	
	changeTextMode(formObj.contactname, mode);
	changeTextMode(formObj.phone, mode);
	changeTextMode(formObj.fax, mode);
	changeTextMode(formObj.address1, mode);
	changeTextMode(formObj.address2, mode);
	changeTextMode(formObj.city, mode);
	changeSelectMode(formObj.state, mode);
	//changeSelectMode(formObj.custom, mode);
	//changeSelectMode(formObj.cameron, mode);
	changeTextMode(formObj.zipcode, mode);
}

//inputs:
//	formObj = e.g. document.<formname>
function dealerSelectChange(formObj)	
{
	var val = formObj.dealerID.options[formObj.dealerID.selectedIndex].value;
	var mode;

	if(val == "0")
	{
		mode = 1;
	}
	else
	{
		mode = 0;
	}

	enableDealerFields(mode, formObj);

}


//inputs:
//	field = 
// 	mode = 1,0
function changeTextMode(field, mode)
{
	if(mode != 1)
	{
		field.readOnly = true;
		field.style.backgroundColor = "#E1E1E1";
	}
	else
	{
		field.readOnly = false;
		field.style.backgroundColor = "#ffffff";
	}
}

//inputs:
//	field = 
// 	mode = 1,0
function changeSelectMode(field, mode)
{
	if(mode != 1)
	{
		field.disabled = true;
		field.style.backgroundColor = "#E1E1E1";
	}
	else
	{
		field.disabled = false;
		field.style.backgroundColor = "#ffffff";
	}
}

function reloadSpecPage(selObj, page)
{
	var val;
	val = selObj.options[selObj.selectedIndex].value;
	window.location.href = page + val;
}

function reloadSpecPageAndTop(selObj, page)
{
	var val;
	val = selObj.options[selObj.selectedIndex].value;
	window.location.href = page + val;
	if(val != "")
	{
		parent.frames[1].location.href = "top_mid.asp?id=" + val + "&tab=specifications";
	}
}



