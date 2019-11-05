var _DOCOVAEdition = "SE";  //-- set this variable to either SE or Domino depending upon where this code is installed

//---------global variables----------------
var NsfName, ServerName, PortalPath, PortalNsfName, PortalWebPath;
var doc=null;
var thingFactory = null;
var DLExtensions = null;
var docInfo=null;
var mouseX=0;
var mouseY=0;
var StatusWin=null; //used by tf progress window
var CancelFlag=true; //used by tf progress window
var statusWin = null;
var loadedScripts = {};	
//-------- calendar objects ------------------
var calYahoo = null;
var curCalField =null;
var dateSessionFormat=null;
var DATE_DELIMITER='/'
var MDY_MONTH_POSITION=0;
var MDY_DAY_POSITION=1;
var MDY_YEAR_POSITION=2;

function InitVars(info){
	doc = document.all;
	thingFactory = doc.thingFactory;
	DLExtensions = doc.DLExtensions;
	try{
		if(!info){alert((typeof prmptMessages == 'undefined' ? "Error: InitVars - info parameter is undefined" : prmptMessages.msgCF001));}
		docInfo=info;
		ServerName = docInfo.ServerName;
		NsfName = docInfo.NsfName;
		PortalWebPath = docInfo.PortalWebPath;
		PortalNsfName = docInfo.PortalNsfName;		
	}catch(e){
		docInfo = null;
		ServerName = "";
		NsfName = "";
		PortalWebPath = "";
		PortalNsfName = "";
	}
}

function recordsetfield(parent, fnode){
	var xmlnode = fnode;
	var objIsland = parent;
	
	this.getValue = function(){
		if ( xmlnode == null) return "";
        return jQuery(xmlnode).text();
	}
	
	this.setValue = function(val){
		if ( xmlnode == null ) return;
        jQuery(xmlnode).text(val);
	}	
}


function recordset(objIsland){
	this.oxml = objIsland.oxml;
	this.boundDataIsland = objIsland;
	this.counter = 0;
	
	this.getDataIsland = function(){
		return this.boundDataIsland;
	}
	
	this.getXMLDocument = function(){
		return this.boundDataIsland.getXMLDocument();
	}
	
	this.AbsolutePosition = function(row){
		if ( row == null || row == undefined) return;
		this.counter = row;
	}
	
	this.getAbsolutePosition = function() {
		return this.counter;
	}
	
	this.MoveFirst = function(){
		this.counter =0;
	}
	this.MoveNext =  function(){
		this.counter ++;
    }
	
	this.getRecordCount = function(){
		
		return this.oxml.documentElement.childNodes.length;
	}
	
	this.EOF = function(){
		if ( this.counter >= this.oxml.documentElement.childNodes.length )
			return true;
		else
			return false;
	}
	
	this.getFIELDSCount = function(){
		var rootnode = this.oxml.documentElement;
		var rec = rootnode.childNodes.item(this.counter);
		return rec.childNodes.length;
	}
	
	
	this.FIELDS = function(fieldname){
		return this.Fields(fieldname);
	}
	
	this.Fields = function (fieldname){
		var rootnode = this.oxml.documentElement;
		var rec = rootnode.childNodes.item(this.counter);
		var fld = new recordsetfield(this.boundDataIsland, null);
		var field = fieldname.toLowerCase();
	    for (var i=0; i < rec.childNodes.length; i++) {
	      if (rec.childNodes.item(i).nodeName.toLowerCase() == field) {
	        found = rec.childNodes.item(i);
	        fld = new recordsetfield ( this.boundDataIsland, found );
	        break;
	      }
	    }
		
		
		return fld;
	}
}

function getFieldDataFromRecord  (rec,field) {
    var found;
    field = field.toLowerCase();
    for (var i=0; i < rec.childNodes.length; i++) {
      if (rec.childNodes.item(i).nodeName.toLowerCase() == field) {
        found = rec.childNodes.item(i).firstChild;
        if (found == null ){
        	return "";
        }else{
        	return found.nodeValue;
        }
      }
    }
 }


function xmlDataIsland()
{
	this.source = "";
	this.xmlhttp = null;
	this.ondatasetcomplete = "";
	this.oxml = null;
	this.templateName = "";
	this.template = null;
	this.recordset =null;
	this.selectdefault = true;
	this.id = "";
	this.async = true;	
	this.XMLDocument = null;	
	
	this.setTemplateName = function(val){
		this.templateName = val;
    	var target = document.getElementById(val);
		this.template = target.cloneNode(true);
	}
	
	this.setXML = function(xmlStringOrObj){
		if(typeof xmlStringOrObj == "string"){
			this.XMLDocument = this.loadXML(xmlStringOrObj);	
		}else if(typeof xmlStringOrObj == "object"){
			this.XMLDocument = xmlStringOrObj;
		}
		this.oxml = this.XMLDocument;
	}
	
	this.setSrc = function(src, dontprocess){
		
		this.source = src;
//    	if ( typeof (this.oxml) != "undefined"  && this.oxml != null)
		if ( dontprocess ) return;
    	this.process();
	}
	
	this.getSrc = function(){
		return this.source;
	}
	
	this.getXMLDocument = function(){
		return this.oxml;
	}
	
	
	this.Refresh = function(callcustom){//Reapplies template to existing loaded xml
		
		this.applyTemplate();
       	if (callcustom && callcustom == true && this.ondatasetcomplete != "" ){
    		this.ondatasetcomplete.call(this);
       	}
	}
	
	this.reload = function(){ //Reloads xml with get request and cache query string to prevent caching
		var currSrc = this.getSrc().split("&cache=")[0]
		var newSrc = currSrc + "&cache=" + Date.now()
		this.setSrc(newSrc)
	}
	
	this.process = function ()
	{
	 
		var self = this;
		if ( this.source == "" ) {
			if ( this.oxml != null ) {
				this.recordset = new recordset(this);
	        	this.XMLDocument = this.oxml;
	        	this.applyTemplate();
	        	if ( this.ondatasetcomplete != "" ){
	        		this.ondatasetcomplete.call(this);
	        	}
			}
			return;
		}
		
		$.ajax({
			'type' : "GET",
			'url' : this.source,
			'async' : this.async,			
			'dataType' : 'xml'
		})
		.done(function(data) 
		{
			self.oxml = data;
        	
			self.recordset = new recordset(self);
			self.XMLDocument = self.oxml;
			self.applyTemplate();
			if ( self.ondatasetcomplete != "" ){
			self.ondatasetcomplete.call(self);
        		}
		})
		
		/*
		if (window.XMLHttpRequest)
		  {// code for IE7+, Firefox, Chrome, Opera, Safari
		  this.xmlhttp=new XMLHttpRequest();
		  }
		else
		  {// code for IE6, IE5
		  this.xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
		  }		
		this.xmlhttp.open("GET", this.source, true);
//		try { this.xmlhttp.responseType = "msxml-document"; } catch (e) { };
		this.xmlhttp.onreadystatechange = function() {
			handler.call(self);
		};
		this.xmlhttp.send();*/
	}
	
	function handler(){
		
		if (this.xmlhttp.readyState == 4 /* complete */) {
	        if (this.xmlhttp.status == 200) {
	        	this.oxml = this.xmlhttp.responseXML;
	        	
	        	this.recordset = new recordset(this);
	        	this.XMLDocument = this.oxml;
	        	this.applyTemplate();
	            this.ondatasetcomplete.call(this);
	        }
	    }
	}
	
	this.getEBArecursive = function (list, node, att) {
	    for (var i=node.childNodes.length-1; i>=0; i--)
	    {
	      var child = node.childNodes.item(i);
	      if ( child.nodeType == 1 ) {
	        if ( child.getAttribute(att) ) {
	          list.push(child);
	        }
	        this.getEBArecursive(list, child, att);
	      }
	    }
	}
	
	this.getElementsByAttribute = function (node, att) {
	    var rv = [];
	    this.getEBArecursive(rv, node, att);
	    return rv;
	 }
	
	
	this.merge = function(target, template , record, index){
		var temptemplate = jQuery(template).clone().get(0);
		var fields   = this.getElementsByAttribute(temptemplate,'datafld');
		if (!fields || fields.length == 0){
			jQuery(temptemplate).remove();
			return;
		}
		
		// update the text for each target field in the template
		for (var k=fields.length-1; k>=0; k--) {
	      var thetag   = fields[k];

	      var thefield = thetag.getAttribute('datafld');
	      var newtext  = getFieldDataFromRecord(record,thefield);   
	      var dataformat = thetag.getAttribute('dataformatas');

	      if ( thetag.tagName.toLowerCase() == "input" &&  (thetag.type.toLowerCase() =="checkbox" || thetag.type.toLowerCase()=="radio"))
	        {
	    	     thetag.setAttribute("record", index );
	        	if (this.selectdefault && newtext=="1") {
	        		thetag.defaultChecked=true; //had to do this for compatibility mode
	        		thetag.checked = true;
	        	} else {
	        		thetag.defaultChecked = false;
	        		thetag.checked = false;
	        	}
	        	
	        }
	    
	      if (thetag.firstChild)            // replace existing text
	      {
	        thetag.firstChild.nodeValue = newtext;
	      }
	      else if ( thetag.value == null )  // not a form element
	      {
	    	if(dataformat == "html"){
	    		jQuery(jQuery.parseHTML(newtext)).appendTo(thetag);   			    		
	    	}else{
		        thetag.appendChild(document.createTextNode(newtext));	    		
	    	}
	      }
	      else                              // a form element
	      {    		        
	        thetag.value = newtext;		    
	      }
	      
		 }
		
		    // put the updated template content back into the page.
	    for (k=0; k < temptemplate.childNodes.length; k++) {
	    	var nd = temptemplate.childNodes.item(k);
	    	if ( nd.nodeName !=  '#text' && nd.nodeName != "#comment"){
	    		var added= target.appendChild(nd.cloneNode(true));
	    		var lst = added.getElementsByTagName('input');
	    		for ( p = 0; p < lst.length; p++){
	    			var inp = lst[p];
	    			if (inp.type.toLowerCase() =="checkbox" || inp.type.toLowerCase()=="radio")
	    				var orig = inp.onclick;
	    				if ( orig != null )
	    					inp.onclick = function () { orig(); UpdateIsland(this);};
	    				else
	    					inp.onclick = function () { UpdateIsland(this);};	
		    		
	    		}
	    		if ( typeof this.onrecordmerge == "function" ){
	        		this.onrecordmerge.call(this, added, record);
	    		}
	    	}
	    }
	    jQuery(temptemplate).remove();
	}
	
	this.applyTemplate = function (){
		
		if ( this.templateName =="" ) return;
		var target = document.getElementById(this.templateName);
		var template = this.template;
		for (var j = target.childNodes.length-1; j>=0; j--) {
	        target.removeChild(target.childNodes.item(j));
	     }
		var island = this.oxml.documentElement;
		for (j = 0; j < island.childNodes.length; j++)
	      {
	        
	        var record = island.childNodes.item(j);
	       
	        if ( record.nodeName == '#text' )
	          continue;

		
	        this.merge(target, template, record, j);
	      }
		
		
		
		
	}

	this.loadXML = function(xmlString){
		var xmlobj = null;
		
		if (window.DOMParser)
		{
			var parser=new DOMParser();
			xmlobj=parser.parseFromString(xmlString,"text/xml");
		}
		else // Internet Explorer
		{
			 if (typeof (ActiveXObject) != "undefined") {
	               xmlobj = new ActiveXObject("Msxml2.DOMDocument.3.0"); 
	               xmlobj.async=false;
			       xmlobj.loadXML(xmlString);		        		
			    }		
		}
		return xmlobj;

	}
	
}
//end XmlDataIsland


//*********************************************************************************************************************
//UTILITY FUNCTIONS
//*********************************************************************************************************************

// helper function that sets the property bound to a checkbox/radiobox 
// when a user clicks it on the UI.
function UpdateIsland(obj){
	var parent = obj.parentNode;
	var attr = parent.getAttribute("datasrc");
	var index = parseInt(obj.getAttribute("record"));
	if ( isNaN(index)) return;
	while ( parent != null && attr == null){
		parent = parent.parentNode;
		if ( parent != null) attr = parent.getAttribute("datasrc");	
	}
	if ( parent == null || attr == null) return;
	var islandStr = attr;
	if ( islandStr == "" ) return;
	islandStr = islandStr.substring(1);
	var island = eval(islandStr);
	var rs = island.recordset;
	rs.MoveFirst()
	var j = 0;
	while (rs.EOF() == false){
		if ( j == index){
			if ( obj.checked ){
				rs.Fields("Selected").setValue("1");
			}else{
				rs.Fields("Selected").setValue("0")
			}
			break;
		}
		j++;
		rs.MoveNext();
	}
}


//-------------- returns position in the passed array of a search string --------------
function ArrayGetIndex(arr, srchString) {
	for(x=0; x <= arr.length; x++) {
		if(arr[x] == srchString) {
			return x;
		}	
	}
	return -1
}

// ----------------------------- returns true if the value can be converted to a number  ------------------------------
function isNumeric(val){return(parseFloat(val,10)==(val*1));}

// ----------------------------- replaces substring within a string with passed substring ------------------------------

function replaceSubstring(fullS,oldS,newS) {
for (var i=0; i<fullS.length; i++) 
{ 
if (fullS.substring(i,i+oldS.length) == oldS) 
{ 
fullS = fullS.substring(0,i)+newS+fullS.substring(i+oldS.length,fullS.length) ;
} 
} 
return fullS ;
}

function leftBack(fullString, startString)
{
	var storeString = fullString;
	var position = fullString.indexOf(startString);
	var tmp = position;
	while (position > -1)
		{
		fullString = fullString.substring(position + 1, fullString.length);
		position = fullString.indexOf(startString);
		tmp = tmp + position + 1;
		}
	return (storeString.substring(0, tmp) )
}

function rightBack(fullString, startString)
{
	startString+= "";
	var position = fullString.indexOf(startString);
	if (startString != "" && position >-1)
	{
	while (position > -1)
	{
	fullString = fullString.substring(position + startString.length, fullString.length);
	position = fullString.indexOf(startString) ;
	}
	return(fullString);
	} else {return (""); }
}

// --- string trimming functions - called like: mystring.trim(), mystring.ltrim(), mystring.rtrim()
String.prototype.trim = function() {
	return this.replace(/^\s+|\s+$/g,"");
}
String.prototype.ltrim = function() {
	return this.replace(/^\s+/,"");
}
String.prototype.rtrim = function() {
	return this.replace(/\s+$/,"");
}

// --- trim an array by removing all empty elements ---
function trim(a){
	var tmp=new Array();
	for(j=0;j<a.length;j++)
		if(a[j]!='')
			tmp[tmp.length]=a[j];
	a.length=tmp.length;
	for(j=0;j<tmp.length;j++)
		a[j]=tmp[j];
	return a;
}

// Removes all characters which appear in string bag from string s.

function stripCharsInBag (s, bag)

{   var i;
    var returnString = "";

    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.

    for (i = 0; i < s.length; i++)
    {   
        // Check that current character isn't whitespace.
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }

    return returnString;
}

// Check whether string s is empty.

function isEmpty(s)
{   return ((s == null) || (s.length == 0))
}

// Returns true if string s is empty or 
// whitespace characters only.

function isWhitespace (s)

{   // Is s empty?
    return (isEmpty(s) || reWhitespace.test(s));
}

// is the string an integer 
function isInteger (s)

{   var i;

    if (isEmpty(s)) 
       if (isInteger.arguments.length == 1) return defaultEmptyOK;
       else return (isInteger.arguments[1] == true);

    return reInteger.test(s)
}

//inserts a character pattern into the string at specified location
function reformat (s)

{   var arg;
    var sPos = 0;
    var resultString = "";

    for (var i = 1; i < reformat.arguments.length; i++) {
       arg = reformat.arguments[i];
       if (i % 2 == 1) resultString += arg;
       else {
           resultString += s.substring(sPos, sPos + arg);
           sPos += arg;
       }
    }
    return resultString;
}

function isMember(textString, textList, delimiter, compCase)
{
	var result = false;
	
	if(textString==null || textString=="" || textList==null || textList=="") {return false;}
	if(delimiter == null) {delimiter=",";}
	if(compCase == null) {compCase=true;}
	
	var listArray = textList.split(delimiter);
	for (var i = 0; i < listArray.length; i++) 	{
		if(compCase){
			if(textString.toString() == listArray[i].toString()){
				result = true;
				break;
			}
		}
		else{
			if(textString.toUpperCase() == listArray[i].toUpperCase()){
				result = true;
				break;
			}
		}
	}
	return result;
}

//-------------------- Returns the object type string ---------------------------
function getObjectType(testobj)
{
return typeof(testobj);
}

// ------------------------------ Utility function to chek if the text string is a member of a text list ------------------------
function fnIsMember(textString, textList, delimiter, compCase)
{
	isMember(textString, textList, delimiter, compCase);
}



// ------------------------------ Utility function to get the DOM object from object reference or id string-----------------------------------

function fnGetObjectByRef(ddobj)
{
		var ddel = null;

		if(getObjectType(ddobj) == "object") //object was passed to the function
			{
			ddel = ddobj; //object passed - no need to get it from DOM
			return ddel;
			}
		else if(getObjectType(ddobj) == "string") //id was passed to the function
			{
			ddel = document.getElementById(ddobj); //get the object from DOM
			return ddel;
			}
		else
			{
			return false; //not a valid parameter
			}
}		

//================================ tabbed table functions ====================================
 var eActiveTab = null; 

function Tab_click(eSrc){
    if (!eSrc) {eSrc = window.event.srcElement};
    var eDiv = document.all[eSrc.id.replace("td","div")];
    if ((eSrc.className.indexOf('clsTab') != -1 || eSrc.className.indexOf('clsTabSel') != -1) && eDiv){
      if (eActiveTab){
       eActiveTab.className = "ui-state-default clsTab";
      }
      eSrc.className = "ui-state-default ui-state-active clsTabSel";
      eActiveTab = eSrc;
      var i=1;
      while((tabDiv = document.all["divTab_" + i]) != null){
	      if(tabDiv == eDiv){
		tabDiv.style.display='';
	      }else{
		tabDiv.style.display='none';
	      }
       i++;
      }
      return true;
     }else{
          return false;
     }
}

function Tab_over(){
    var eSrc = window.event.srcElement;
    if ($(eSrc).attr('width') != 'clsTabFiller') {
    	$(eSrc).addClass('ui-state-hover');
    }
    //eSrc.style.color = "navy";
    return true;
}

function Tab_out(){
    var eSrc = window.event.srcElement;
    $(eSrc).removeClass('ui-state-hover');
    //eSrc.style.color = "";
    return true; 
}


function InitTab(tabNo){
if(!tabNo) {tabNo=1;}
var tabObj = document.all["tdTab_" + tabNo];
if(!tabObj) {tabObj = document.all.tdTab_1;}
if(!tabObj) {return false;}
Tab_click(tabObj);
}


function setDate(targetField,DateFormat,xoffset,yoffset)
{
	//targetField is the where the returned date will land
	//DateFormat is the format to return the date in, valid examples are just about anything Windowws can recognize.
	//Using the text "session" for DateFormat - will provide the browsers current session date format
	//Other examples are: "mm/dd/yyyy", "yyyy/mm/dd", "mmm/dd/yyyy", "dd/mmm/yy"

	if((!targetField) || (targetField == "")){
		alert((typeof prmpMessages == "undefined" ? "A target field for the returned date has not been provided." : prmptMessages.msgCF002));
		return false;
	}

	if((DateFormat == "session") || (!DateFormat)){
		var UserDateFormat = docInfo.SessionDateFormat;
	}else{
		UserDateFormat = DateFormat
	}

	//var selectedDate = thingFactory.ShowCalendar(2, "", UserDateFormat, "Select Document Date")
	//thingFactory.SetHTMLItemValue(targetField, selectedDate)

	xoffset = (xoffset == undefined ? 0 : xoffset);
	yoffset = (yoffset == undefined ? 0 : yoffset);
	setYCalDate(targetField, "img"+targetField, xoffset, yoffset, UserDateFormat)

}



//--------------------------- Progress bar -------------------------------

// xp_progressbar
// Copyright 2004 Brian Gosselin of ScriptAsylum.com

var w3c=(document.getElementById)?true:false;
var ie=(document.all)?true:false;
var N=-1;
var bars=new Array();

function createBar(w,h,bgc,brdW,brdC,blkC,speed,blocks)
{
/* --------------------------------------------------------------------
w- Total width of the entire bar in pixels. 
h- Total height of the entire bar in pixels. 
bgc- Background color of the bar. Use valid CSS color or HEX color code value. 
brdW- The width of the border around the bar, in pixels. 
brC- The color of the border around the bar. Use valid CSS color or HEX color code value. 
blkC- The darkest color of the individual blocks. The color will progressively become more transparent. Use valid CSS color or HEX color code value. 
speed- The delay, in milliseconds, between each scroll step. Use smaller values for faster scroll speeds. 
blocks- The total number of blocks to use. 

Usage example:
Insert the following in-line script where the progress bar should appear:

<script type="text/javascript">
createBar(190,10,'white',1,'#6CAFD3','#6CAFD3',85,10);
</script>

-------------------------------------------------------------------- */

if(ie||w3c){
var t='<div style="position:relative; overflow:hidden; width:'+w+'px; height:'+h+'px; background-color:'+bgc+'; border-color:'+brdC+'; border-width:'+brdW+'px; border-style:solid; font-size:1px;">';
t+='<span id="blocks'+(++N)+'" style="left:-'+(h*2+1)+'px; position:absolute; font-size:1px">';
for(i=0;i<blocks;i++){
t+='<span style="background-color:'+blkC+'; left:-'+((h*i)+i)+'px; font-size:1px; position:absolute; width:'+h+'px; height:'+h+'px; '
t+=(ie)?'filter:alpha(opacity='+(100-i*(100/blocks))+')':'-Moz-opacity:'+((100-i*(100/blocks))/100);
t+='"></span>';
}
t+='</span></div>';
document.write(t);
var bA=(ie)?document.all['blocks'+N]:document.getElementById('blocks'+N);
bA.blocks=blocks;
bA.w=w;
bA.h=h;
bars[bars.length]=bA;
setInterval('startBar('+N+')',speed);
}}

function startBar(bn){
var t=bars[bn];
t.style.left=((parseInt(t.style.left)+t.h+1-(t.blocks*t.h+t.blocks)>t.w)? -(t.h*2+1) : parseInt(t.style.left)+t.h+1)+'px';
}

//---------------------------------------- load subform into a specified target container ------------------------------------
function loadSubform(sfName, targetContainer, readOnly, parentID, qsParams, clearCache)
{
var baseUrl = location.protocol + "//" + ServerName + "/" + NsfName + "/SubformLoader";
if(readOnly)
{
baseUrl += "?ReadForm";
}
else
{
baseUrl += "?OpenForm";
}
baseUrl += "&sf=" + sfName;

if(parentID) {baseUrl += "&ParentUNID=" + parentID;}
if(qsParams) {baseUrl += qsParams;}
if(clearCache) {thingFactory.ClearCachedUrl( baseUrl , 1);}
thingFactory.SetHtmlItemValue(targetContainer, thingFactory.getSubForm(baseUrl , "divSfContent", false));

}

//---- number formatting function -----
function formatNum(expr,decplaces) {
if(!expr) {return "0";}

var str = expr.replace("$", "");
str = str.replace(" ", "");
if(str == "") {str = "0"}	
str = (Math.round(parseFloat(str) * Math.pow(10,decplaces))).toString()
	if(isNaN(str)) { str="0"}
	while (str.length <= decplaces) {
		str = "0" + str;
	} 
	var decpoint = str.length - decplaces;
	if(decplaces == 0)
	{
		return str.substring(0,decpoint) 
	}
	else
	{
		return str.substring(0,decpoint) + "." + str.substring(decpoint,str.length)
	}
}

//============== thingFactory action bar============
function showActionProgress(style, title, text, showCancel , maxCount){
//------------------ if window is open, close it first --------------------------
	if (StatusWin != null){
		CloseStatusWindow();
	}
//------------------- create status window object -------
	StatusWin = thingFactory.NewObject( "statuswindow" );
	StatusWin.EnableCancelButton = showCancel;	
//------------------- create status window ------------------	
	StatusWin.CreateWindow( style, title, false);
	StatusWin.SetStatusText(text);
//----------- set some options-------------------------------
	StatusWin.InitProgressBar(0, 100, 1)
//----------- fade in window --------------------------------
	StatusWin.ShowWindow(true, 1);
//------------update progress bar-------------------------	
	for (var a=1; a <= maxCount; a++){
		//StatusWin.SetStatusText("Counter = " + a);
		if ( (a % 50) == 0){
				StatusWin.IncrementProgressBar();
		}
		if (StatusWin.CancelPressed == true){
			alert((typeof prmptMessages == "undefined" ? "Operation Canceled" : prmptMessages.msgCF003));
			break;
		}
	}
//--------- close window---------------------------------------	
	StatusWin.ShowWindow(false, 1);
	StatusWin.CloseWindow()
	StatusWin = null;
}

// ------- sends data requiest to a specified agent --------------

//====== http object for backend communications =======
function objHTTP()
{
	this.results = new Array();
	this.resultNodes = new Array();
	this.status = "";
	this.error = "";
	this.resultCount = this.results.length;
	this.xmlDocument=null;
	this.supressWarnings=false;
	this.returnxml = false;
	
	this.PostData = function(xmldoc, url, ignoreResponse) 
	{ 
		//clear old request data
		this.results = new Array();
		this.status = "";
		this.error = "";
		var obj = this;
	
		$.ajax({
			'type' : "POST",
			'url' : url,
			'processData' : false,
			'data' : xmldoc,
			'contentType': false,
			'async' : false,
			'dataType' : 'xml'
		})
		.done(function(data) {
			if(!data)
			{
				obj.status="FAILED";
				obj.error = "No data received from server";
				if(!obj.supressWarnings) { alert((typeof prmptMessages == "undefined" ? "Operation did not complete.\rError: No data received from server." : prmptMessages.msgCF004)); }
				return false;
			}
			
			var statusNode = data.selectSingleNode('/Results/Result[@ID="Status"]');
			obj.status = (statusNode)? statusNode.textContent || statusNode.text : "FAILED";
			var errorNode = data.selectSingleNode('/Results/Result[@ID="ErrMsg"]');
			obj.error =  (errorNode)? errorNode.textContent || errorNode.text : ""; 
			var i=1;
			var retNode = data.selectSingleNode('/Results/Result[@ID="Ret' + i + '"]');
			while(retNode)
			{
				obj.resultNodes[obj.resultNodes.length] = retNode;
				if(obj.returnxml){
				
					if ( retNode.xml  ) {
					
						obj.results[obj.results.length] = retNode.xml;
					}else{
						obj.results[obj.results.length] = retNode;
					}
				} else {
					obj.results[obj.results.length] = retNode.text || retNode.textContent || "";
				}
				i++;
				retNode = data.selectSingleNode('/Results/Result[@ID="Ret' + i + '"]');
			}
			obj.resultCount = obj.results.length;
			obj.xmlDocument = data;
		
			if(obj.status == "FAILED" || obj.status == "")
			{
				if(!obj.supressWarnings) {alert(( typeof prmptMessages == "undefined" ? "Operation did not complete. Please contact system administrator." : prmptMessages.msgCF005) + "\r" + obj.error);}
				return false;
			} 
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			if (jqXHR.status != 200) {
				obj.status="FAILED";
				obj.error = jqXHR.status;
				if(!obj.supressWarnings) { alert((typeof prmptMessages == "undefined" ? "Operation did not complete.\rStatus code" : prmptMessages.msgCF006) + ": " + jqXHR.status); }
				return false;
			}
		});
	
		if (obj.status == "FAILED") {
			return false;
		}
		
		this.results = obj.results;
		this.resultNodes = obj.resultNodes;
		this.status = obj.status;
		this.error = obj.error;
		this.resultCount = obj.resultCount;
		this.xmlDocument = obj.xmlDocument;
		this.supressWarnings = obj.supressWarnings;
		return true;
	} //PostData
}//--end objHTTP

//------------------------- Sametime functions ----------------------------------
function toggleSTLinks(dataSection, targetField){
	//dataSection is the div or other section on the page that contains the tags we will convert to ST links.
	//targetField is the field name of the field in the dataset used to form the ST link
	var origDataFld
	var PeopleArray = doc[dataSection].all[targetField]

	//Check to ensure instant messaging is available


	//Check to see if there are any values to get to begin with.  If not, provide error message and return.
	if(PeopleArray == undefined){
		thingFactory.MessageBox((typeof prmptMessages == "undefined" ? "Sorry, no names were found to turn into SameTime links." : prmptMessages.msgCF013), 0 + 16, (typeof prmptMessages == "undefined" ? "Error, no names found for instant messaging." : prmptMessages.msgCF014));
		return
	}

	//Check to see if there is one link to resolve, or an array of links
	if(PeopleArray.length == undefined){
		//PeopleArray is only one value, so do not process it like an array.
		if(PeopleArray.innerHTML.indexOf("SPAN") == -1){
			PeopleArray.innerHTML = prepareSametimeLink(PeopleArray.innerText,PeopleArray.innerText, true, "icon:yes")
		}else{
			origDataFld = PeopleArray.dataFld
			PeopleArray.dataFld = origDataFld
		}		
	}else{
		//PeopleArray has more than one value to process
		for(var i=0; i<PeopleArray.length; i++){
			if(PeopleArray[i].innerHTML.indexOf("SPAN") == -1){
				PeopleArray[i].innerHTML = prepareSametimeLink(PeopleArray[i].innerText,PeopleArray[i].innerText, true, "icon:yes")
			}else{
				origDataFld = PeopleArray[i].dataFld
				PeopleArray[i].dataFld = origDataFld
			}
		}
	}
}

/*Deprecated.  Replaced by Docova.SetCookie, Docova.GetCookie
//------------- super cookie functions ------------------------
function SetCookie(CookieName, CookieValue){
//--------------- set cookie to cookie file ---------------------------------	
	var flag = thingFactory.SetCookieValue( CookieName, CookieValue );
	if (flag == false){
		thingFactory.MessageBox((typeof prmptMessages == "undefined" ? "Unable to update SuperCookie (" + CookieName + ")\n Please contact Administrator." : prmptMessages.msgCF015.replace('%cookie%' + CookieName)), 0+16, (typeof prmptMessages == "undefined" ? "SuperCookie Error" : prmptMessages.msgCF016));
	}
}

function GetCookie(CookieName){
//--------------- get cookie from cookie file -----------------------------
	var CookieValue = thingFactory.GetCookieValue( CookieName );
	return CookieValue		
}*/

//-----------------------------------------------------------------------------------

function RefreshNavFrame(){
//--------------------------------refresh nav window-----------------------------------
	parent.frames['fraLeftFrame'].RefreshFrame();
}

function OpenFolder( FolderID, DoActions ){
	parent.frames['fraLeftFrame'].OpenFolderByID( FolderID, DoActions );
}

//==========================================================================================
//displays the status message
//==========================================================================================

function ShowProgressMessage(text) 
{

	if(window.top.Docova){
		window.top.Docova.Utils.showProgressMessage(text);	
	}
}


//==========================================================================================
//hides the status message
//==========================================================================================

function HideProgressMessage()
{
	if(window.top.Docova){
		window.top.Docova.Utils.hideProgressMessage();		
	}
}

//==========================================================================================
//convert non ascii in string to character entities and vice versa
//==========================================================================================

function Char2Entity (str) {
    str = str.replace(/&/g, '&#38;');
    str = str.replace(/'/g, '&#39;');
    str = str.replace(/"/g, '&#34;');
    str = str.replace(/\\/g, '&#92;');
    str = str.replace(/>/g, '&#62;');
    str = str.replace(/</g, '&#60;');
    var acc = '';
    for (var i = 0; i < str.length; i++) {
        if (str.charCodeAt(i) > 31 && str.charCodeAt(i) < 127) acc += str.charAt(i) 
        else acc += '&#' + str.charCodeAt(i) + ';';
    }
    return acc;
}

function Entity2Char (str) {
    str = str.replace(/(&#[0-9]+;)/g, '\n$1\n');
    str = str.replace(/\n\n/g, '\n');
    spl = str.split('\n');
    for (var i = 0; i < spl.length; i++) {
        if (spl[i].charAt(0) == '&') {
            spl[i] = spl[i].replace(/&#([0-9]+);/g, '$1');
            spl[i] = String.fromCharCode(spl[i]);
        }
    } 
    str = spl.join('');
    return str;
}

//----------------------------------  CALENDAR  ----------------------------------
// ------------ set YUIDate ------------------------
function setYCalDate(targetField, targetButton, xoffset, yoffset, dateformat)
{
	if (calYahoo ==null)
	{
		var navConfig = {strings : { month: "Choose Month", year: "Enter Year", submit: "OK", cancel: "Cancel", invalidYear: "Please enter a valid year"},monthFormat: YAHOO.widget.Calendar.SHORT, initialFocus: "year"};
		if ((dateformat != undefined) && (dateformat != "")){
			dateSessionFormat = dateformat;
		}else{	
		 dateSessionFormat = docInfo.SessionDateFormat;   //get the user date format typye
		}
		if (dateSessionFormat.indexOf('-')>=0)
			DATE_DELIMITER='-';
		else  if (dateSessionFormat.indexOf('.')>=0)
			DATE_DELIMITER='.';	
		var dateSequence=dateSessionFormat.split(DATE_DELIMITER);
		for (var i=0; i<dateSequence.length;i++)
		{
			if(dateSequence[i]=="mm")
				MDY_MONTH_POSITION=(i);
			else if (dateSequence[i]=="dd")
				MDY_DAY_POSITION=(i);
			else if (dateSequence[i]=="yyyy")
				MDY_YEAR_POSITION=(i);
		}
		calYahoo=new YAHOO.widget.Calendar("cal2","calYContainer", {title:"Calendar:", close:true,navigator:navConfig } ); 			//create calendar
		calYahoo.selectEvent.subscribe(getYSelectedDate, calYahoo, true);
		calYahoo.render();
		calYahoo.hide();
		 curCalField = document.getElementById(targetField);
		 showYCal(targetField, targetButton, xoffset, yoffset);
	}
	else
	{
		curCalField = document.getElementById(targetField);
		showYCal(targetField, targetButton, xoffset, yoffset);
	}
}


//--------- shows up calendar ---------------------
function showYCal(fieldObj, btnObj, xoffset, yoffset) 
{ 
  var xy = YAHOO.util.Dom.getXY(btnObj); 
  var date = YAHOO.util.Dom.get(fieldObj).value; 
	if (date) 
	{ 
		 var tempArray = date.split(DATE_DELIMITER);
		 if (tempArray !=null && tempArray.length ==3)
		 {
			 var sMonth=parseFloat(tempArray[MDY_MONTH_POSITION]);
			 var sDay=parseFloat(tempArray[MDY_DAY_POSITION]);
			 var sYear=parseFloat(tempArray[MDY_YEAR_POSITION]);
			 var selectedDate = sMonth+'/'+sDay+'/'+sYear ;
			  calYahoo.cfg.setProperty('selected', selectedDate); 
			  var pageDatetemp = parseFloat(tempArray[MDY_MONTH_POSITION])+'/'+tempArray[MDY_YEAR_POSITION];
			   calYahoo.cfg.setProperty('pagedate',pageDatetemp); 
			  calYahoo.render(); 
		  }
	 } 
		YAHOO.util.Dom.setStyle('calYContainer', 'display', 'block'); 
		var calDiv=  document.getElementById('calYContainer');	 
	   if(xoffset != undefined){xy[0]+=xoffset;};
	   if(yoffset != undefined) {xy[1]+=yoffset;};
	   xy=getCalYCoordinate(xy, calDiv.clientWidth, calDiv.clientHeight , document.body.clientHeight)
	   YAHOO.util.Dom.setXY('calYContainer', xy); 
}


// --------- calculates y coordinate based on cal height  --------    
function getCalYCoordinate(xyCoord, calWidth,calHeight, bodyHeight) 
{
	var xyArray = new Array();
	var bottomAvailArea = bodyHeight - xyCoord[1];
	xyArray[0]= xyCoord[0] - calWidth;	 // x coordinate for cal
	if (bottomAvailArea >= calHeight) 
		xyArray[1]=xyCoord[1]; // y coordinate
	// adjust the height accordingly	
	else
		xyArray[1] = (bodyHeight - calHeight ) - 5; // adjust padding
	return xyArray;
}
//------- get date called on calendar selected date
function getYSelectedDate()
{
        var calDate	 = this.getSelectedDates()[0];
	   var calMonth =((calDate.getMonth() + 1) < 10 ? '0' : '') + (calDate.getMonth()+1);
	   var calDay = (calDate.getDate() < 10 ? '0' : '' ) + calDate.getDate(); 
	   var calYear = calDate.getFullYear();
	  dateSessionFormat = docInfo.SessionDateFormat;
	  if ( dateSessionFormat ==null || dateSessionFormat=="" || dateSessionFormat.length <10)
		dateSessionFormat='mm/dd/yyyy';
	   calDate=formatYCalDate(calMonth,calDay,calYear,dateSessionFormat);
        curCalField.value = calDate;
        calYahoo.hide(); 
}
//--------- format date ----------------
function formatYCalDate(mm,dd,yyyy,dateYFormat)
{
	return dateYFormat.replace(/(yyyy|mm|dd)/gi, function($1){switch ($1.toLowerCase()){case 'yyyy': return yyyy; case 'mm':   return mm; case 'dd':   return dd; }});
}

//*********************************************************************************************************************
// DOCUMENT ATTACHMENT COMPARISON FUNCTIONS
//*********************************************************************************************************************

function CompareWordAttachments(docArr, cb){
	//get attachments from selected documents
	var attInfo = GetAttachmentURLs(docArr, 'doc,docx', 'ALL');
	if(!attInfo){return;}
	
	var fileURL1 = $($.parseXML(attInfo[0])).find("URL").text().split("*")
	var fileName1 = $($.parseXML(attInfo[0])).find("FILENAME").text().split("*")
	var fileURL2 = $($.parseXML(attInfo[1])).find("URL").text().split("*")
	var fileName2 = $($.parseXML(attInfo[1])).find("FILENAME").text().split("*")
	
	if(fileURL1.length > 1 || fileURL2.length > 1){
		dlgParams.length = 0
		dlgParams[0] = fileURL1;
		dlgParams[1] = fileName1;
		dlgParams[2] = fileURL2;
		dlgParams[3] = fileName2;
		//more than one file found, prompt user to select which ones to compare			
		var dlgUrl ="/" + docInfo.PortalNsfName + "/" + "dlgSelectDocsForCompare?OpenForm";
		var dlgDocsForCompare = window.top.Docova.Utils.createDialog({
			id: "divDlgDocsForCompare", 
			url: dlgUrl,
			title: "Select Files",
			height: 300,
			width: 550,
			useiframe: true,
			sourcewindow: window,
			buttons: {
       		"Done": function() {
				var dlgDoc = window.top.$("#divDlgDocsForCompareIFrame")[0].contentWindow.document
				fileURL1 = $("input[name=FileAttach1]:checked", dlgDoc).attr("furl");
				fileName1 = $("input[name=FileAttach1]:checked", dlgDoc).val();
				fileURL2 = $("input[name=FileAttach2]:checked", dlgDoc).attr("furl");
				fileName2 = $("input[name=FileAttach2]:checked", dlgDoc).val();
				//in the case of a revision the attachments may be named the same. Rename one of them to avoid issues.
				fileName2 = "doc1_" + fileName2
				dlgDocsForCompare.closeDialog()
				DoCompareWordAttachments(fileURL1,fileName1,fileURL2,fileName2,cb)
   			},
    			"Cancel": function() {
				dlgDocsForCompare.closeDialog();
    			}
    		}
		})
	}else{
		//in the case of a revision the attachments may be named the same. Rename one of them to avoid issues.
		fileName2 = "doc1_" + fileName2
		DoCompareWordAttachments(fileURL1,fileName1,fileURL2,fileName2,cb)
	}
}

function DoCompareWordAttachments(fileURL1,fileName1,fileURL2,fileName2,cb){	
	//get a local folder and download attachments to be compared
	var folderName = DLExtensions.MyDocumentsFolder()
	var localFileNames = [folderName + fileName1, folderName + fileName2]
	DLExtensions.DownloadFileFromURL(fileURL1, localFileNames[0])
	DLExtensions.DownloadFileFromURL(fileURL2, localFileNames[1])

	var saveCompareDocPath = folderName + "DocCompare_" + fileName1
	CompareWordDocuments(localFileNames, saveCompareDocPath)
	
	//----------------- delete downloaded Word documents  -------------------------------
	var delCode = 'Type objType = Type.GetTypeFromProgID("Scripting.FileSystemObject");'
	delCode += 'dynamic fso = Activator.CreateInstance(objType);'
	for(x=0; x<localFileNames.length; x++) {
		delCode += 'if(fso.FileExists(@"' + localFileNames[x] + '")){'
		delCode += 'dynamic f = fso.GetFile(@"' + localFileNames[x] + '");'
		delCode += 'f.Delete(true);'
		delCode += "}";
	}
	delCode += 'fso = null;'
	delCode += 'return "";'

	var retval = DLExtensions.executeCode(delCode, false, true);
	
	//---- delay the return for 3 seconds to allow time
	//---- for file access to free up (otherwise in some systems file may not launch)
	var start = new Date().getTime();
	for (var i = 0; i < 1e7; i++) {
	   if ((new Date().getTime() - start) > 3000){
	      break;
	   }
	}

	cb(saveCompareDocPath);
}

function getLibraryURL(libraryKey, noWarnings){
	var libURL = "";
	if(libraryKey==""){return libURL};

    if (docInfo == null){
	   InitVars(info);
	}

	//--build the request and get the library NsfName using the LibraryKey.
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>GETNSFNAMEBYLIBRARYKEY</Action>";
	request += "<LibraryKey>" + libraryKey + "</LibraryKey>";
	request += "</Request>";

	var httpObj = new objHTTP()
	httpObj.supressWarnings = (noWarnings) ? true : false ;
	if(httpObj.PostData(request, url))
	{
	 if(httpObj.status=="OK") //all OK
		{
			if(httpObj.results.length > 0)
			{
				LibraryNsfName = httpObj.results[0];
				if(_DOCOVAEdition == "SE"){
					var urlPath = docInfo.NsfName;
					var libURL = docInfo.ServerUrl + "/" + urlPath;
				}else{
					var urlPath = leftBack(docInfo.NsfName ,"/");
					var libURL = docInfo.ServerUrl + "/" + urlPath +  "/" + LibraryNsfName;				
				}
			}
		}
	}
	return libURL;
}	

function GetAttachmentURLs(docids, attType, count, librarykey, flags) {
	
	var tempflags = (flags == null)? "" : flags;
	tempflags = tempflags.split(",");
	if(ArrayGetIndex(tempflags, "NOPROGRESS") == -1){
		Docova.Utils.showProgressMessage("Collecting file information from selected documents...")
	}

	var serverUrl = docInfo.ServerUrl;

	//-- get library information if not already specified
	if(librarykey == null || librarykey == ""){
		var dbPath = docInfo.NsfName;
		var archiveDbPath = docInfo.ArchiveNsfName;
	}else{
		var dbPath = getLibraryURL(librarykey);
		dbPath = replaceSubstring(dbPath, serverUrl, "");
		var archiveDbPath = "";
		if (dbPath != ""){
			var fileExt = rightBack(dbPath, ".");
			var filePath = leftBack(dbPath, ".");
			var archiveDbPath = (filePath == "") ? "" : filePath + "_A." + fileExt; 
		}
	}
	var agentUrl = serverUrl + "/" + dbPath + "/DocumentServices?OpenAgent";
	
	//-- get the urls of the attached docs in each document
	var request="";

	//--build the request
	request += "<Request>";
	request += "<Action>GETATTACHMENTURL</Action>";
	request += "<Unids>" + docids + "</Unids>";
	request += "<FileExtension>" + attType + "</FileExtension>";
	request += "<ElementCount>" + count + "</ElementCount>";
	request += "<ServerUrl>" + serverUrl + "</ServerUrl>";
	request += "<DBPath>" + dbPath + "</DBPath>";
	request += "<ArchiveDBPath>" + archiveDbPath + "</ArchiveDBPath>";
	request += "<Flags>" + flags + "</Flags>";
	request += "</Request>";

	var resultArray = new Array();
	jQuery.ajax({
		type: "POST",
		url: agentUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				Docova.Utils.hideProgressMessage();
				resultArray[0] = "<fileinfo>" + xmlobj.find('Results Result[ID=Ret1]').html() + "</fileinfo>";
				resultArray[1] = "<fileinfo>" + xmlobj.find('Results Result[ID=Ret2]').html() + "</fileinfo>";;
				Docova.Utils.hideProgressMessage();
			}
		},
		error: function(){
			Docova.Utils.hideProgressMessage();
		}
	})
	return resultArray
}

function CompareWordDocuments(localFileNameArr, saveCompareDocPath){

	//localFileNameArr = array with two local file names
	//saveCompareDocPath = local path and file name to save the comparison results to
	
	Docova.Utils.showProgressMessage((typeof prmpMessages == "undefined" ? "Comparing documents...one moment please." : prmptMessages.msgCF007));

	var compareCode = 'Type objType=Type.GetTypeFromProgID("Word.Application");'
	compareCode += 'dynamic WordObj = Activator.CreateInstance(objType);'
	compareCode += 'WordObj.Visible = false;'
	compareCode += 'dynamic WordDoc = WordObj.Documents.Open(@"' + localFileNameArr[0] + '",false, false, false);'
	compareCode += 'WordObj.ActiveDocument.Compare(@"' + localFileNameArr[1] + '","Comparison",2,1,1);'
	compareCode += 'WordObj.ActiveDocument.SaveAs(@"' + saveCompareDocPath + '");'    
    compareCode += 'WordObj.ActiveDocument.Close(0);' //close comparison results doc
	compareCode += 'WordDoc.AttachedTemplate.Saved = true;'  //fix to tell Word not to prompt to save the default .dot template    	
	compareCode += 'WordDoc.Close(0);' //close the source doc and do not save			
	compareCode += 'WordObj.Quit();'
	compareCode += 'WordDoc = null;'
	compareCode += 'return "";'
	
	var retval = DLExtensions.executeCode(compareCode, false, true);
	return saveCompareDocPath;
}

//--------- Add temporary orphan docs info for new documents in case they are not saved -------
//This function records a list of documents, like related document links for documents that are no yet saved
//For example, if a user creates a new document, then relates it to other documents then links are created
//however, the user might choose to cancel and not save this document, leaving the links orphaned.
//This can also happen with linked emails.  In the past the user had to save the document before linking.
//Since we now allow them to create these relationships before saving the document, we need to track them.
function AddTmpOrphan(orphanLibKey, orphanUnid){
	var xmlDoc = tmpOrphanXml;
	var basexml =  "<Libraries></Libraries>";
	var nodeList = xmlDoc.selectNodes( 'Libraries' ); 
	if(nodeList.length ==0){
			xmlDoc.loadXML(basexml);
	}
	
	//Check if Library node with orphanLibKey exists, if not then create it
	var LibraryNode= xmlDoc.selectSingleNode('Libraries/Library[@LibraryKey="' + orphanLibKey + '"]' ); 
	if(LibraryNode == null){
			var LibraryNode = xmlDoc.createElement("Library");
			var LibraryAtt=xmlDoc.createAttribute("LibraryKey");
			LibraryAtt.nodeValue=orphanLibKey;
			LibraryNode.setAttributeNode(LibraryAtt);
			var x = xmlDoc.getElementsByTagName("Libraries")[0];
			x.appendChild(LibraryNode);
	}
	//Check in orphanUnid node exists, if not then create it
	var OrphanEl = xmlDoc.selectSingleNode('Libraries/Library/Unid[.="' + orphanUnid + '"]' );
	if(OrphanEl == null){
		OrphanEl = xmlDoc.createElement("Unid");
		var OrphanText = xmlDoc.createTextNode(orphanUnid);
		OrphanEl.appendChild(OrphanText)
		LibraryNode.appendChild(OrphanEl)
	}

}

function DelTmpOrphan(orphanUnid){
	var xmlDoc = tmpOrphanXml;
	
	var OrphanEl = xmlDoc.selectSingleNode('//Unid[.="' + orphanUnid + '"]' );
	
	if (OrphanEl != null){
		OrphanEl.parentNode.removeChild(OrphanEl)
	}

}
//to debug message in browser console window
function debugBrowserConsole(debugMsg){
	
	if (window.console || typeof console != "undefined") {
			window.console.clear; // clear console
			console.log(debugMsg); // write message
    } 
}
// to check if str ends with "strToFind"
function endsWith(str,strToFind){
	return (str.match(strToFind+"$")==strToFind)
}
//to check if str starts with "strToFind"
function startsWith(str, strToFind){
	return (str.match("^"+strToFind)==strToFind)
}

//encode unicode double-byte chars
function uniEncode(srcTxt) {
  var entTxt = '';
  var c, hi, lo;
  var len = 0;

  for (var i=0, code; code=srcTxt.charCodeAt(i); i++) {
    var rawChar = srcTxt.charAt(i);
    if (code > 255) {
      if (0xD800 <= code && code <= 0xDBFF) {
        hi  = code;
        lo = srcTxt.charCodeAt(i+1);
        code = ((hi - 0xD800) * 0x400) + (lo - 0xDC00) + 0x10000;
        i++; // we already got low surrogate, so don't grab it again
      }
	 else if (0xDC00 <= code && code <= 0xDFFF) {
        hi  = srcTxt.charCodeAt(i-1);
        lo = code;
        code = ((hi - 0xD800) * 0x400) + (lo - 0xDC00) + 0x10000;
      }
      // wrap it up as Hex entity
      c = "" + code.toString(16).toUpperCase() + ";";
    }
    else {
      //c = rawChar;
      c = "" + code.toString(16).toUpperCase() + ";";
    }
    entTxt += c;
    len++;
  }
  return entTxt;
}

function DBLookup(servername, nsfname, viewname, key, columnorfield, delimiter, alloweditmode, secure, failsilent){
	//TODO - fix concatenation of multi values, check name lookup, support other element types
	var result = false;

	delimiter = (delimiter) ? delimiter : ";";
	var column = parseInt(columnorfield);
	var useform = isNaN(column);
	column = (useform) ? -1 : column - 1; //Root xml index starts at 0, but this function will be used where user/developer thinks first column is 1.
    
    var strUrlBase = (secure == "ON") ? "https://" : "http://";
    if(servername == ""){
        strUrlBase += window.location.host;
    }else{
        if (servername.toLowerCase().indexOf("http") > -1){
            var n = servername.toLowerCase().indexOf("//");
            if (n > -1){
                servername = servername.slice(n+2);
            }
        }
        strUrlBase += servername;
    }    
    strUrlBase += (strUrlBase.charAt(strUrlBase.length-1) == "/") ? "" : "/";
     if (nsfname == ""){
         nsfname = window.location.href;
         var n = nsfname.toLowerCase().indexOf(window.location.host.toLowerCase());
         if(n > -1){
             nsfname = nsfname.slice(n + window.location.host.length + 1);
         }
           var n = nsfname.toLowerCase().indexOf(".nsf");
           if(n > -1){
             nsfname = nsfname.slice(0, n+4);
           }
    }
    strUrlBase += nsfname;
    strUrlBase += (strUrlBase.charAt(strUrlBase.length-1) == "/") ? "" : "/";

    var strUrl = strUrlBase + encodeURIComponent(viewname);
    strUrl += "?ReadViewEntries&Count=1&StartKey=";
    strUrl += key;
    
    jQuery.ajax({
        url: strUrl,
        async: false,
        cache: false,
        type: "GET",
        dataType: "xml"
    })
    .done(function( xmldata ) {
        var viewentry = jQuery(xmldata).find("viewentry:first");
        if (! useform){
              result = "";
			if (jQuery(viewentry).find("entrydata:first").text() == key){
     	       result = jQuery(viewentry).find("entrydata[columnnumber='" + column.toString() + "']").text();
			}
		}else{
			var docunid = viewentry.attr("unid");
			
		    var strUrl = strUrlBase + "0/" + docunid + ((alloweditmode) ? "?EditDocument" : "?OpenDocument");
    
		    jQuery.ajax({
		        url: strUrl,
		        async: false,
		        cache: false,
		        type: "GET",
		        dataType: "html"
		    })
		    .done(function( htmldata ) {
		    	   result = "";
		    	   jqhtmldata = jQuery(htmldata);
		    	   var values = jqhtmldata.find("#" + columnorfield);
		    	   if(values.length == 0){
			        values = jqhtmldata.find("[name=" + columnorfield +"]");
			   }
			   if(values.length > 0){
				result = values.text();
			   }
		    })
		    .fail(function(){
		    		if (! failsilent){ alert((typeof prmptMessages == "undefined" ? "An error occurred retrieving data from the server." : prmptMessages.msgCF012)); }  	
		    });			
		}    
    })
    .fail(function(){
    		if (! failsilent){ alert((typeof prmptMessages == "undefined" ? "An error occurred retrieving data from the server." : prmptMessages.msgCF012))}  	
    });

    return result;
}

function DbColumn(servername, nsfname, viewname, key, column, delimiter, secure){
	var result = "";
	if (delimiter === undefined){
		delimiter = "";
	}
	column = column - 1; //Root xml index starts at 0, but this function will be used where user/developer thinks first column is 1.
    
    var strUrl = (secure == "ON") ? "https://" : "http://";
    if(servername == ""){
        strUrl += window.location.host;
    }else{
        if (servername.toLowerCase().indexOf("http") > -1){
            var n = servername.toLowerCase().indexOf("//");
            if (n > -1){
                servername = servername.slice(n+2);
            }
        }
        strUrl += servername;
    }    
    strUrl += (strUrl.charAt(strUrl.length-1) == "/") ? "" : "/";
     if (nsfname == ""){
         nsfname = window.location.href;
         var n = nsfname.toLowerCase().indexOf(window.location.host.toLowerCase());
         if(n > -1){
             nsfname = nsfname.slice(n + window.location.host.length + 1);
         }
           var n = nsfname.toLowerCase().indexOf(".nsf");
           if(n > -1){
             nsfname = nsfname.slice(0, n+4);
           }
    }
    strUrl += nsfname;
    strUrl += (strUrl.charAt(strUrl.length-1) == "/") ? "" : "/";
    strUrl += encodeURIComponent(viewname);
    strUrl += "?ReadViewEntries&Count=500&StartKey=";
    strUrl += key;
    
    jQuery.ajax({
        url: strUrl,
        async: false,
        cache: false,
        type: "GET",
        dataType: "xml"
    })
    .done(function( xmldata ) {
        	jQuery(xmldata).find("viewentries").children().each(function(){
        		if(result == ""){
        			result += $(this).find("entrydata[columnnumber='1']").text()
        		}else{
        			result += delimiter + $(this).find("entrydata[columnnumber='1']").text()
        		}
        	})
    });
    return result;
}

function btoaEx(str)
{
	var result = str;
	
	try{
		var binstr = utf8ToBinaryString(str);
		result = btoa(binstr);
	}catch(err){
	}
	
	return result;
}

function atobEx(b64)
{
	var result = b64;
	
	try{
		var binstr = atob(b64);
		result = binaryStringToUtf8(binstr);
	}catch(err){
	}
	
	return result;
}

function utf8ToBinaryString(str) {
	  var escstr = encodeURIComponent(str);
	  // replaces any uri escape sequence, such as %0A,
	  // with binary escape, such as 0x0A
	  var binstr = escstr.replace(/%([0-9A-F]{2})/g, function(match, p1) {
	    return String.fromCharCode('0x' + p1);
	  });

	  return binstr;
}

function binaryStringToUtf8(binstr) {
	  var escstr = binstr.replace(/(.)/g, function (m, p) {
	    var code = p.charCodeAt(0).toString(16).toUpperCase();
	    if (code.length < 2) {
	      code = '0' + code;
	    }
	    return '%' + code;
	  });

	  return decodeURIComponent(escstr);
}

function base64_decode(data) {
	  //  discuss at: http://phpjs.org/functions/base64_decode/
	  // original by: Tyler Akins (http://rumkin.com)
	  // improved by: Thunder.m
	  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  //    input by: Aman Gupta
	  //    input by: Brett Zamir (http://brett-zamir.me)
	  // bugfixed by: Onno Marsman
	  // bugfixed by: Pellentesque Malesuada
	  // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  //   example 1: base64_decode('S2V2aW4gdmFuIFpvbm5ldmVsZA==');
	  //   returns 1: 'Kevin van Zonneveld'
	  //   example 2: base64_decode('YQ===');
	  //   returns 2: 'a'
	  //   example 3: base64_decode('4pyTIMOgIGxhIG1vZGU=');
	  //   returns 3: '✓ à la mode'

	  var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
	  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
	    ac = 0,
	    dec = '',
	    tmp_arr = [];

	  if (!data) {
	    return data;
	  }

	  data += '';

	  do {
	    // unpack four hexets into three octets using index points in b64
	    h1 = b64.indexOf(data.charAt(i++));
	    h2 = b64.indexOf(data.charAt(i++));
	    h3 = b64.indexOf(data.charAt(i++));
	    h4 = b64.indexOf(data.charAt(i++));

	    bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;

	    o1 = bits >> 16 & 0xff;
	    o2 = bits >> 8 & 0xff;
	    o3 = bits & 0xff;

	    if (h3 == 64) {
	      tmp_arr[ac++] = String.fromCharCode(o1);
	    } else if (h4 == 64) {
	      tmp_arr[ac++] = String.fromCharCode(o1, o2);
	    } else {
	      tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
	    }
	  } while (i < data.length);

	  dec = tmp_arr.join('');

	  return decodeURIComponent(escape(dec.replace(/\0+$/, '')));
}

function base64_encode(data) {
	  //  discuss at: http://phpjs.org/functions/base64_encode/
	  // original by: Tyler Akins (http://rumkin.com)
	  // improved by: Bayron Guevara
	  // improved by: Thunder.m
	  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: Rafał Kukawski (http://kukawski.pl)
	  // bugfixed by: Pellentesque Malesuada
	  //   example 1: base64_encode('Kevin van Zonneveld');
	  //   returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
	  //   example 2: base64_encode('a');
	  //   returns 2: 'YQ=='
	  //   example 3: base64_encode('✓ à la mode');
	  //   returns 3: '4pyTIMOgIGxhIG1vZGU='

	  var b64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
	  var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
	    ac = 0,
	    enc = '',
	    tmp_arr = [];

	  if (!data) {
	    return data;
	  }

	  data = unescape(encodeURIComponent(data));

	  do {
	    // pack three octets into four hexets
	    o1 = data.charCodeAt(i++);
	    o2 = data.charCodeAt(i++);
	    o3 = data.charCodeAt(i++);

	    bits = o1 << 16 | o2 << 8 | o3;

	    h1 = bits >> 18 & 0x3f;
	    h2 = bits >> 12 & 0x3f;
	    h3 = bits >> 6 & 0x3f;
	    h4 = bits & 0x3f;

	    // use hexets to index into b64, and append result to encoded string
	    tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
	  } while (i < data.length);

	  enc = tmp_arr.join('');

	  var r = data.length % 3;

	  return (r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3);
}

function getPluginURL()
{
	var isSSL = false;
	if ( window.location.protocol == "https:")
		isSSL = true;
	
	var infovar = null;
	if(typeof docInfo == "undefined" || docInfo == null){
		infovar = window.top.getinfovar();
	}else{
		infovar = docInfo;
	}
	var systemKey = infovar.SystemKey;
	
	if ( isSSL )
		return "https://localhost.docova.com:50450/docova?systemKey=" + systemKey + "&";
	else
		return "http://localhost:40451/docova?systemKey=" + systemKey + "&";
	
}


function getAll$$Functions(){
	var functions = [];

	for( var x in window) {

	    if(typeof window[x] === "function" && x.indexOf("$$") === 0) {
		    obj = window[x];
			objstr = obj.toString();

			var funcstr = objstr.substring (0, objstr.indexOf("{")-1 );
			funcstr = funcstr.substring(objstr.indexOf("Function ") + 9);
	        functions.push(trim(funcstr));
	    }
	}

	return functions;
}

function loadScript(scriptname){
	var result = false;
	
	if(!scriptname){return result;}
	
	//--check to see if the script has already been loaded if so stop
	if(typeof loadedScripts !== 'undefined' && loadedScripts[scriptname.toUpperCase()] === true){
		return result;
	}

	var urlpath = "";	
	if(_DOCOVAEdition == "SE"){
		appid = docInfo.AppID;
		urlpath =  docInfo.ServerUrl +  docInfo.PortalWebPath + "/LoadJScript/"+ appid + "/" + scriptname;	
	}else{
		if(scriptname.indexOf("/") == -1 && scriptname.indexOf("\\") == -1 && scriptname.toLowerCase().indexOf(".nsf") == -1){
			urlpath = window.location.href;
			n = urlpath.toLowerCase().indexOf(".nsf");
	    		if(n > -1){
	       			urlpath = urlpath.slice(0, n+4);
	    		}
	    		urlpath += (urlpath.indexOf("/", urlpath.length -1) > -1 ? "" : "/");
		}
		urlpath += scriptname;
		if(urlpath.indexOf("?") === -1){
			urlpath = urlpath + "?OpenJavascriptLibrary";
		}	
	}
	
	
	
	jQuery.ajax({
		url: urlpath,
		cache: true,
		dataType: "script",
		async: false
	}).done(function(data){
		loadedScripts[scriptname.toUpperCase()] = true;
		result = true;
	}).fail(function(jqXHR, textStatus, errorThrown ) {
		if (typeof console == "object") {
			console.log("Error loading javascript library " + scriptname + ".\r\nError: " + errorThrown.message);
			console.log("=========================================================================");
			console.log(errorThrown.stack);
			console.log("=========================================================================");			
		};
	});
	return result;
}

function uploadMultiple(options)
{
		var completed = 0;
		var jqxhrarr = [];
		var opts = options;
		var cont = this;
		
		
		this.cancelUploads = function(){
			for ( var cnt = 0; cnt < jqxhrarr.length; cnt ++){
				jqxhrarr[cnt].abort();
			}
		}
		
		this.uploadFile = function(i )
		{
			if ( i >= opts.files.length) return;
			var formData = new FormData();
			var fileind = i;
			
			formData.append('__Click', '0');
			formData.append('mode', "dle")
			formData.append('Subject', opts.files[fileind].name);
			// Main magic with files here
			
			formData.append(opts.inputid, opts.files[fileind]); 
			
			
			
			var jqXHR = $.ajax({
			    url: docInfo.ServerUrl + opts.liburl +'/Document?OpenForm&ParentUNID=' + opts.id + '&mode=dle&typekey=' + opts.defDocType ,
			    data: formData,
			    type: 'POST',
			    contentType: false,
			    processData: false,
			    beforeSend: function() {
					cont.uploadFile(i + 1); // begins next progress bar
					return true;
				},
			    xhr: function () {
			        var xhr = new window.XMLHttpRequest();
			        xhr.upload.addEventListener("loadstart", function(e){
			        	this.progressId =  fileind; 
			        	this.fname= opts.files[fileind].name;
			        	
					});
			        
			        xhr.upload.addEventListener("progress", function (evt) {
			        	 if (evt.lengthComputable) {
			               // var arr = tmparr;
			                
			                var percentComplete =Math.round(evt.loaded / evt.total * 100);
			                var context = window.top.document;
			    			
							var progtitle =  this.fname;
							$("#progress-label"+ this.progressId, context).text(progtitle + "(" + percentComplete+ "%)");
							$("#progressbar" + this.progressId, context).progressbar({ value: percentComplete });
			            }
			        }, false);
			        return xhr;
			    },
			    success: function(inobj) 
			    { 
			    	
					completed++;
					var unid = $(inobj).find("Unid").text();
					if (completed==opts.files.length){
						
						
						opts.onComplete.call(this)
						$("#dlgProgress", window.top.document).remove();
					}
				},
			    error: function(obj) 
			    {
					completed++;
					if ( obj.statusText != "abort" )
						alert ( "Error!  Unable to create " + opts.files[fileind].name + " in Docova.");
			    }
			})
			jqxhrarr.push(jqXHR);
			return true;
		}
	
		this.start = function(){
			$("#dlgProgress", window.top.document).remove();
			var progressDiv = "<div id='dlgProgress' title='Upload Progress'>"
			progressDiv += "<div class='dlgProgress_lblProgressText'><label id='lblProgress_lblProgressText'>Sending Data</label></div>"
			progressDiv +="<div class='dlgProgressDetails'>Uploading " + opts.files.length + " file(s).</div>"
			progressDiv +="<div class='DocovaProgressAll' id='DocovaProgressAll'>"
			for (var ind = 0; ind <opts.files.length; ind ++)
			{
					progressDiv += "<div class='pbar' id='progressbar" + ind + "'><div class='progress-label' id='progress-label" + ind + "'>" + opts.files[ind].name + "</div></div>";
			}
			progressDiv += "</div>"
			
			var dlgheight = 200 + (opts.files.length*25);
			if ( dlgheight > 300)
				dlgheight = 300;
			
			$("body", window.top.document).append(progressDiv);
			
			$("#dlgProgress", window.top.document).dialog({
				autoOpen : true,
				width : 500,
				height : dlgheight,
				position : { my: "top", at: "top+100", of: window.top},
				resizable:false,
				modal: true,
				buttons: {
					"Cancel": function() {
						cont.cancelUploads();
						$( "#dlgProgress", window.top.document ).dialog( "close" );
						
						
					}
				},
				open : function () 
				{
					$(this).parent().find(".ui-dialog-titlebar-close").hide();
					$( ".pbar" , window.top.document).progressbar({value: 0});
					$(this).parent().css({"left": ($(window.top).width()/2) - 250})
					
				}
			});
			
			this.uploadFile(0);
		}
}

function ValidatePathLength(LimitPathLength, FolderPath, Fnames){
	  var result = true;
	  var problemfiles = [];
	  var failed = false;
	  if(LimitPathLength && LimitPathLength > 0){
	      //-- only check folder path length if limit is configured and folder is less than that limit since we cannot 
	      //-- affect the folder path length from the document
	       if(FolderPath.length < LimitPathLength){
				for(var x=0; x< Fnames.length; x++){
							if((FolderPath.length + Fnames[x].length + 1) > LimitPathLength){
							    failed = true;
							    problemfiles.push(Fnames[x]);
							    
							}
				}
				if ( failed ){
					var answer = confirm("The attachment named [" +  problemfiles.join(", ") + "] will cause the folder path to exceed the limit specified by the administrator.  Select OK to continue saving.  Select Cancel to stop the save and then rename or remove the file.");
				    if(answer === false){
				       result = false;
				    }
				}
		  }    
	  }
	  
	  return result;
}
function CheckUserAppAccess(appDocKey){
	var agentName = "WorkspaceServices"
	var request = "<Request><Action>CHECKUSERAPPACCESS</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request += "<Document>"
	request += "<AppDocKey>" + appDocKey + "</AppDocKey>"
	request += "</Document>"
	request += "</Request>"
		
	var resulttext = SubmitRequest(request, agentName);
	if(resulttext == null || resulttext == "undefined"){
		resulttext = "NA"; //NA means not applicable, for example, if trying to get the access level for the Library Repository or App Builder
	}
	return resulttext;
}

function SubmitRequest(request, agentName){
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + agentName  + "?OpenAgent"
	var result;
	var resulttext;
	$.ajax({
		type: "POST",
		url: processUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			result = true;
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				resulttext = xmlobj.find("Result[ID=Ret1]").text();
			}else if (statustext == "FAILED") {
				result = false;
				resulttext = "FAILED";
			}
		},
		error: function(){
			result = false
			resulttext = "FAILED"
			//provide an error message
		}
	})
	return resulttext;
}


/**
 * detect IE
 * returns version of IE or false, if browser is not Internet Explorer
 */
function detectIE() {
  var ua = window.navigator.userAgent;

  // Test values; Uncomment to check result …

  // IE 10
  // ua = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';
  
  // IE 11
  // ua = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
  
  // Edge 12 (Spartan)
  // ua = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36 Edge/12.0';
  
  // Edge 13
  // ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Safari/537.36 Edge/13.10586';

  var msie = ua.indexOf('MSIE ');
  if (msie > 0) {
    // IE 10 or older => return version number
    return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
  }

  var trident = ua.indexOf('Trident/');
  if (trident > 0) {
    // IE 11 => return version number
    var rv = ua.indexOf('rv:');
    return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
  }

  var edge = ua.indexOf('Edge/');
  if (edge > 0) {
    // Edge (IE 12+) => return version number
    return parseInt(ua.substring(edge + 5, ua.indexOf('.', edge)), 10);
  }

  // other browser
  return false;
}

/**
 * Crawl the code to distinguish initialise section and body
 * 
 * @param code
 * @returns {Array}
 */
function fetchCodeSections(code)
{
	//code = code.replace(/\<\?php|\<\?|\?\>/i, '');
	var header = '',
		body = '',
		initializer = '',
		end = '',
		start = code.indexOf('function Initialize()');
	if (start > -1)
	{
		if (start > 1)
			header = code.substring(0, start - 1);
		var endp = code.indexOf('{', start);
		var count = endp > -1 ? 1 : 0;
		while (count != 0 && endp < code.length)
		{
			endp++;
			var ch = code.charAt(endp);
			if (ch == '{')
				count++;
			else if (ch == '}')
				count--;
			if (count != 0) {
				initializer += ch;
			}
		}
		
		if ((endp-1) > code.indexOf('{', start))
		{
			code = code.substring(endp + 1);
		}
	}
	
	start = code.indexOf('public function Terminate()');
	if (start > -1)
	{
		if (start > 1)
			body = code.substring(0, start - 1);
		var endp = code.indexOf('{', start);
		var count = endp > -1 ? 1 : 0;
		while (count != 0 && endp < code.length)
		{
			endp++;
			var ch = code.charAt(endp);
			if (ch == '{')
				count++;
			else if (ch == '}')
				count--;
			if (count != 0) {
				end += ch;
			}
		}
		
		if ((endp - 1) > code.indexOf('{', start))
		{
			body += code.substring(endp + 1);
		}
	}
	else {
		body = code;
	}
	
	var output = {
		'header' : header,
		'init' : initializer,
		'body' : body,
		'end' : end
	};
	return output;
}

/**
 * hashCode
 * Generates a numeric hash code for a string input
 */
function hashCode(inputstring){
	var hash = 0, i, chr;
	if (inputstring.length === 0) return hash;
	for (i = 0; i < inputstring.length; i++) {
	   chr   = inputstring.charCodeAt(i);
	   hash  = ((hash << 5) - hash) + chr;
	   hash |= 0; // Convert to 32bit integer
	}
	return hash;
}

/**
 * multiSplit
 * Splits a string based on one or more delimiters
 * Inputs:
 * str - string - string to split
 * delimiters - string|string[] - delimiter or array of delimiters to split string by
 * noescape - boolean (optional) - set to true to ignore escaping of delimiters - defaults to false
 * Return: string array
 */
function multiSplit(str, delimeters, noescape) {
    var result = [str];
    
    var escapechar = (typeof noescape !== "undefined" && noescape === true ? '' : '\\');
    if (typeof (delimeters) == 'string'){
        delimeters = [delimeters];
    }
    while (delimeters.length > 0) {
        for (var i = 0; i < result.length; i++) {
        	var templist = [];
        	templist.length = 0;
        	
        	var startpos = 0;
        	var endpos = 0;
          var keepgoing = true;
        	while(keepgoing){
        		var performslice = false;
            	var endpos = result[i].indexOf(delimeters[0], endpos);
        		if(endpos > startpos){
        			if(escapechar != "" && result[i].slice(endpos-1, endpos) == escapechar){
        				endpos = endpos + delimeters[0].length;
        			}else{
        				performslice = true;
        			}
        		}else if(endpos == startpos){
        			performslice = true;
        		}else{
            	endpos = result[i].length;
              performslice = true;
              keepgoing = false;
            }
        		if(performslice){
        			templist.push(result[i].slice(startpos, endpos));
        			startpos = endpos + delimeters[0].length;
        			endpos = startpos;
        		}    		
        	}
        	
        	if(Array.isArray(templist) && templist.length > 0){
                result = result.slice(0, i).concat(templist).concat(result.slice(i + 1));
        	}
        }
        delimeters.shift();
    }
    return result;
}



function isMultiValue(key, _htmldoc){ 
	if ( !_htmldoc)
		_htmldoc = document;

	try{
		var fldinfo =getFieldInfoVar();
	}catch(e){
		return false;
	}

	
	if ( key in fldinfo)
		return true;
	else
		return false;


}

function processMultiValue(_htmldoc,  fieldname, fieldvalue){
	var fldinfo =getFieldInfoVar();
	mvsep = fldinfo[fieldname].mvsep;
	
	var newvalstr = fieldvalue;
	
	if(mvsep.trim() !== ""){
		var multivalarr = mvsep.split(" ");
		for ( var j =0; j < multivalarr.length; j ++){
			if ( trim(multivalarr[j]) == "comma" )
				multivalarr[j] = ",";
			else if (trim(multivalarr[j]) == "semicolon"  )
				multivalarr[j] = ";";
			else if (trim(multivalarr[j]) == "space"  )
				multivalarr[j] = " ";
			else if (trim(multivalarr[j]) == "newline"  )
				multivalarr[j] = "\r\n";
			else if (trim(multivalarr[j]) == "blankline"  )
				multivalarr[j] = "\r\n\n";
		}
	
		var newvals = multiSplit(fieldvalue, multivalarr);
		newvalstr = newvals.join(String.fromCharCode(31));
	}

	return newvalstr;
}

function getAllDateTimeFields(_htmldoc) 
{
	var list=[];
	$("[elem=date]", _htmldoc ).each ( function(){
		fname = ($(this).attr("id") || $(this).attr("name") || "");
		if(fname != ""){
			list.push(fname.toLowerCase());
		}
	});
	return list.join(String.fromCharCode(31));

}

function handleDataTablesData(_htmldoc){
	//handle data islands
	$(".datatable", _htmldoc).each ( function () 
	{
		var rowarr = [];
		
		$(this).find("tbody tr").each ( function ()
		{
			if ( ! $(this).hasClass("disland_templ_row")  )
			{
				/*if ( $(this).attr("dtremoved") == "1" )
				{
					var docid = $(this).attr("docid");
					var valobj = {"docid" : docid, "removed": "1"};
					var tmpobj = encodeURIComponent(JSON.stringify (valobj));
					var valstr = tmpobj;
				}else{
					var valstr = $(this).attr("valobj");	
				}*/

				var valstr = $(this).attr("valobj");
				if ( valstr && valstr != "")
				{
					var curvaljson = JSON.parse(decodeURIComponent(valstr));
					curvaljson.status = $(this).attr("dtremoved") == "1" ? "removed" : ( $(this).attr("modified") == "1"  ?  "modified" : "" );
					rowarr.push(curvaljson);
				}
			}	
		})

		var dtid = $(this).attr("id");
		var jsonstr = encodeURIComponent(JSON.stringify (rowarr));
		$("#"+dtid + "_values", _htmldoc).val( jsonstr );
	})
}

function getAllDataTablesNames(_htmldoc)
{
	var namesstr = "";
	$(".datatable", _htmldoc).each ( function () 
	{
		if ( namesstr == ""){
			namesstr = $(this).attr('id');
		}else{
			namesstr = ";" + $(this).attr('id');
		}
	});
	return namesstr;
}

function getAllNumericFields(_htmldoc) 
{
	var list=[];
	$("[textrole=n]", _htmldoc ).each ( function(){
		fname = ($(this).attr("id") || $(this).attr("name") || "");
		if(fname != ""){
			list.push(fname.toLowerCase());
		}
	});
	return list.join(String.fromCharCode(31));

}

function validateRequiredFields ( collection, silent)
{
	var missed = false;

	collection.each(function() 
	{
		var pobj = null;
		if ($(this).get(0).hasAttribute('textrole'))
		{
			$(this).addClass('missed');
			pobj = $(this);
			if ($(this).attr('textrole') == 'n') {
				if ($.trim($(this).val()) == '' || $(this).val() == '0' || $(this).val() == '0.0') {
					missed = true;
				}
				else if (typeof($$IsNumber) == 'function' && !$$IsNumber($(this).val())) {
					missed = true;
				}
				else if (typeof($$IsNumber) != 'function' && isNaN($(this).val())) {
					missed = true;
				}
			}
			else if ($(this).attr('textrole') == 'authors' || $(this).attr('textrole') == 'readers' || $(this).attr('textrole') == 'names') {
				if ($(this).attr('selectiontype') == 'single' && $.trim($(this).val()) == '') {
					missed = true;
				}
				else if ($(this).attr('selectiontype') != 'single' && $.trim($('#'+$(this).attr('target')).val()) == '') {
					missed = true;
				}
			}
			else if ($.trim($(this).val()) == '') {
				missed = true;
			}
		}
		else if ($(this).attr('elem') == 'date') {
			$(this).addClass('missed');
			pobj = $(this);
			if ($.trim($(this).val()) == '') {
				missed = true;
			}
			else if (typeof($$IsDate) == 'function' && !$$IsDate($(this).val())) {
				missed = true;
			}
			else if (typeof($$IsDate) != 'function') {
				var tempdate = Docova.Utils.convertStringToDate($(this).val());
				if(tempdate === null || isNaN(tempdate)){
					missed = true;
				}
			}
		}
		else if ($(this).prop('type') == 'checkbox') {
			var elmname = $(this).attr('name');
			var parentTbl = $(this).closest('table');
			parentTbl.addClass('missed');
			pobj = parentTbl;
			if ($(':input[name="'+ elmname +'"]:checked').length == 0) {
				missed = true;
			}
		}
		else if ($(this).prop('type') == 'radio') {
			var elmname = $(this).attr('name');
			var parentTbl = $(this).closest('table');
			parentTbl.addClass('missed');
			pobj =  parentTbl;
			if ($(':input[name="'+ elmname +'"]:checked').length == 0) {
				missed = true;
			}
		}
		else if ($(this).attr('elem') == 'select') {
			var selected = false;
			var customBox = $('span[sourceelem="'+ $(this).prop('id') +'"]').find('input.custom-combobox-input');
			customBox.addClass('missed');
			pobj = customBox;
			$(this).children('option').each(function() {
				if ($(this).is(':selected')) {
					selected = $(this).prop('value');
					return false;
				}
			});
			
			if (selected === false || !selected) {
				missed = true;
			}
		}
		else if ($(this).prop('type') == 'hidden' && $(this).attr('kind') == 'text') {
			var att_container = $('div[refid="'+ $(this).prop('id').replace(/(\_FileNames)$/, '') +'"]').find('div[id^="attachDisplay"]');
			att_container.addClass('missed');
			pobj = att_container;
			if (typeof(_targetwin.DLIUploader1) != "undefined" && _targetwin.DLIUploader1.GetAllFileNames() == '') {
				missed = true;
			}
		}
		else if ($(this).is('textarea') && $(this).parent().attr('elemtype') == 'richtext' && $(this).is(':hidden')) {
			if ($('div[id="dEdit'+ $(this).prop('id') +'"]').length && $('div[id="dEdit'+ $(this).prop('id') +'"]').attr('processmceeditor') == '1') {
				var id = "dEdit"+ $(this).prop('id');
				var html = tinyMCE.get(id).getContent();
				$('#dEdit'+ $(this).prop('id') +'_ifr').addClass('missed');
				pobj = $('#dEdit'+ $(this).prop('id') +'_ifr');
				if (!html) {
					missed = true;
				}
			}
			else if ($('div[name="'+ $(this).prop('id') +'"]').length) 
			{
				$('div[name="'+ $(this).prop('id') +'"]').addClass('missed');
				pobj = $('div[name="'+ $(this).prop('id') +'"]');
				if ($.trim($('div[name="'+ $(this).prop('id') +'"]').text()) == '') {
					missed = true;
				}
			}
		}
		else {
			$(this).addClass('missed');
			pobj = $(this);
			if ($.trim($(this).val()) == '') {
				missed = true;
			}
		}

		if ( pobj && !missed ){
			pobj.removeClass("missed");
		}
	});
	
	if (missed !== false && !silent) {
		window.top.Docova.Utils.messageBox({
			'prompt': "Please provide the required highlighted information.",
			'icontype': 1,
			'msgboxtype': 0,
			'title': "Form Validation",
			'width': 400
		});
		return false;
	}
	return true;
}