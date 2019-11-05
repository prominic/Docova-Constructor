var de_colPickPopup = null;
var de_helpPopup = null;
var selColor = null;
var currRequest = null;
var storedTBarCount = null;
firstClick = true;

function saveDocovaRichText(){
	for(z=0; z<=storedTBarCount; z++){
		sourceDiv = document.getElementById("dEdit" + [z]);
		targetField = document.getElementById(sourceDiv.name);
		targetField.value = sourceDiv.innerHTML
	} 
}

function setTargets() {
	var link, i = 0;
	while (link = document.links[i++]) {
		link.target = '_blank';
	}
}

function overTool(){
	window.event.srcElement.className = "selTool";
}

function offTool(){
	window.event.srcElement.className = "dTool";
}

function selColor(){
	var request = window.event.srcElement.id;    
	request = request.slice(0, request.length-1); 
}

function onPopupDownload(src){
	de_colPickPopup.document.write(src);
}

function showHelp(clickObject){
	var oPopBody = de_helpPopup.document.body
	oPopBody.style.backgroundColor = "#EFEFEF";
	oPopBody.style.border = "solid gray 1px";
	oPopBody.innerHTML = helpText;
	de_helpPopup.show(-300, 22, 300, 300, clickObject);
	de_helpPopup.document.ParentWindow = window; 
}

function showColorPicker(clickObject){
	var request = window.event.srcElement.id;    
	currRequest = request.slice(0, request.length-1);    
	de_colPickPopup.show(0, 22, 201, 137, clickObject);
	de_colPickPopup.document.ParentWindow = window; 
}

function setColor(colorValue){
	selColor = colorValue;
	de_colPickPopup.hide();
	document.execCommand([currRequest],true,selColor)
}

function closeHelp(){de_helpPopup.hide();}

function applyTool(passedValue, reset){  
	var request = window.event.srcElement.id;    
	request = request.slice(0, request.length-1); 
	if(passedValue){
		document.execCommand([request],true,passedValue)
	}else{   
		document.execCommand([request])
	}   
	if(reset){window.event.srcElement.selectedIndex = 0}    
}

function loadIcons(toolBarCount)
{
	var img_path = (!docInfo.isLockEditor) ? '../../' : '../../../';

	setTargets();  //force all links to open in a new window
	if(loadDocovaEditor){
		storedTBarCount = toolBarCount
		de_colPickPopup = window.createPopup(); //color picker
		de_helpPopup = window.createPopup(); //help area
		document.all.dwn.startDownload(docInfo.PortalWebPath + "/colorPicker.html",onPopupDownload);
		var tools = ["Bold","Italic","Underline","StrikeThrough","sep","JustifyLeft","JustifyCenter",
		"JustifyRight","sep","Cut","Copy","Paste","sep","ForeColor","BackColor","sep","InsertUnorderedList",
		"InsertOrderedList","Indent","Outdent","sep","CreateLink","Unlink","sep","Undo","Redo",
		"InsertHorizontalRule","RemoveFormat","sep","Help","FontName","FontSize"];
	
		for(z=0; z<=toolBarCount; z++){
			//load the toolbar
			tbar = document.getElementById("dToolbar" + [z])
			tbarHTML = "";
			for(x=0; x<tools.length; x++){
				if(tools[x] == "sep"){
					tbarHTML += "<img src=" + img_path + "bundles/docova/images/" + tools[x] + ".gif>"
				}
				else if(tools[x] == "FontName") {
					selFont = "";
					tbarHTML += "<img src=" + img_path + "bundles/docova/images/sep.gif><select unselectable='on' class='DropDown' id=" + tools[x]+[z] + " onchange='applyTool(this.value, true);'>" +
					"<option value='null'>- Select Font -<option value='Arial'>Arial" +
					"<option value='Book Antiqua'>Book Antiqua</font><option value='Comic Sans MS'>Comic Sans<option value='Courier New'>Courier New" + 
					"<option value='Tahoma'>Tahoma<option value='Times New Roman'>Times New Roman<option value='Verdana'>Verdana</select>"
				} 
				else if(tools[x] == "FontSize") {
					tbarHTML += " <select unselectable='on' class='DropDown' id=" + tools[x]+[z] + " onchange='applyTool(this.value, true);'>" + 
					"<option value='null'>- Font Size -<option value='1'>1 (8pt)" +
					"<option value='2'>2 (10pt)<option value='3'>3 (12pt)<option value='4'>4 (14pt)<option value='5'>5 (18pt)<option value='6'>6 (24pt)<option value='7'>7 (36pt)</select>"
				} 
				else if(tools[x] == "ForeColor" ||tools[x] == "BackColor") { 
					tbarHTML += "<img unselectable='on' class='dTool' onmouseover='overTool()' onmouseout='offTool()' onclick='showColorPicker(this)' id=" + tools[x]+[z] + " src=" + img_path + "bundles/docova/images/" + tools[x] + ".gif>"
				}
				else if(tools[x] == "Help") {
					tbarHTML += "<img unselectable='on' class='dTool' onmouseover='overTool()' onmouseout='offTool()' onclick='showHelp(this);' id=" + tools[x]+[z] + " src=" + img_path + "bundles/docova/images/" + tools[x] + ".gif>"
				}
				else {
					tbarHTML += "<img unselectable='on' class='dTool' onmouseover='overTool()' onmouseout='offTool()' onclick='applyTool()' id=" + tools[x]+[z] + " src=" + img_path + "bundles/docova/images/" + tools[x] + ".gif>"
				}
			}
			tbar.innerHTML = tbarHTML
			
			//load any existing content into divs from source fields
			targetDiv = document.getElementById("dEdit" + [z]);
			srcField = document.getElementById(targetDiv.name);
			targetDiv.innerHTML = srcField.value
		}
	}
}
