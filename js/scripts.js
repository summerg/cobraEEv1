function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
	d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}
function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
 var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
   var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
   if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_reloadPage(init) {  //reloads the window if Nav4 resized
  if (init==true) with (navigator) {if ((appName=="Netscape")&&(parseInt(appVersion)==4)) {
	document.MM_pgW=innerWidth; document.MM_pgH=innerHeight; onresize=MM_reloadPage; }}
  else if (innerWidth!=document.MM_pgW || innerHeight!=document.MM_pgH) location.reload();
}
function MM_openBrWindow(theURL,winName,features) { //v2.0
  window.open(theURL,winName,features);
}
function LaunchCobraGolfUniversity()
{
	MM_openBrWindow('http://www.3point5.com/action/manufacturerLanding?orgKey=cobragolf', 'university', 'fullscreen=yes,scrollbars=yes,resizable=yes,titlebar=no,toolbar=no');	
}
function popup( url, name, xsize, ysize, features ) {
	if( !url )
		return true;
	if( !name )
		name = "popupwindow"
	if( xsize != undefined && ysize != undefined ) {
		if( !features )
			features = ""
		else
			features = "," + features
		window.open( url, name, "height="+ysize+",width="+xsize+features )
	} else
		window.open( url, name, features )
	return false;
}
MM_reloadPage(true);

	var arrMenus = new Array(4);
	function PopupMenu() {
		this.sourceOver = false;
		this.targetOver = false;
		this.menuId = "";
	}

	var countryMenu = new PopupMenu();
	countryMenu.menuId = "country_menu";
	var corporateMenu = new PopupMenu();
	corporateMenu.menuId = "corporate_menu";	
	var fittingMenu = new PopupMenu();
	fittingMenu.menuId = "fitting_menu";		
	var accountsMenu = new PopupMenu();
	accountsMenu.menuId = "accounts_menu";			

	arrMenus[0] = countryMenu;
	arrMenus[1] = corporateMenu;
	arrMenus[2] = fittingMenu;
	arrMenus[3] = accountsMenu;	

	function MouseOverSourceMenu(menuNumber, numPixels)
	{
		var popupMenu = arrMenus[menuNumber];	
		popupMenu.sourceOver = true;
		menuelement = document.getElementById(popupMenu.menuId);
		groupelement = menuelement.parentNode;
		menuelement.style.display = "block";
		numPixels = numPixels + 3;	
		groupelement.style.bottom = numPixels + "px";
	}
	function MouseOverTargetMenu(menuNumber)
	{	
		var popupMenu = arrMenus[menuNumber];	
		popupMenu.targetOver = true;
	}
	function MouseOutTargetMenu(menuNumber)
	{
		var popupMenu = arrMenus[menuNumber];	
		popupMenu.targetOver = false;
		TimeoutPopupMenu(menuNumber)
	}

	function MouseOutSourceMenu(menuNumber)
	{
		var popupMenu = arrMenus[menuNumber];	
		popupMenu.sourceOver = false;
		TimeoutPopupMenu(menuNumber)
	}

	function TimeoutPopupMenu(menuNumber)
	{
		setTimeout('HidePopupMenu(' + menuNumber + ')', 1000);
	}

	function HidePopupMenu(menuNumber)
	{
		var menu, popupMenu
		popupMenu = arrMenus[menuNumber];
		if(! popupMenu.sourceOver && ! popupMenu.targetOver)
		{
			menuelement = document.getElementById(popupMenu.menuId);
			groupelement = menuelement.parentNode;
			menuelement.style.display = "none";
			groupelement.style.bottom = "0px";
		}
	}	

	function highlightFeatureTitle(featureNumber)
	{
		titleAnchor = document.getElementById("subAnchor" + featureNumber);
		titleAnchor.className = "highlight";
	}

	function dullFeatureTitle(featureNumber)
	{
		titleAnchor = document.getElementById("subAnchor" + featureNumber);
		titleAnchor.className = "white";
	}	