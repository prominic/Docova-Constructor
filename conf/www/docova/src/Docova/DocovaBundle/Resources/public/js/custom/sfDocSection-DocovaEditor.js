var storedTBarCount = null;

$(document).ready(function(){
	$( "button" ).button().click(function( event ) {		
		event.preventDefault();
	});
	$( ".btngroup" ).buttonset();
});	

function saveDocovaRichText(){
	var sourceDiv;
	var sourceDivName;
	var targetField;
	for(z=0; z<=storedTBarCount; z++){
		sourceDiv = document.getElementById("dEdit" + z);
		sourceDivName = $(sourceDiv).attr("name");
		targetField = document.getElementById(sourceDivName);
		$(targetField).val($(sourceDiv).html()) 
	} 
}

function setTargets() {
	var link, i = 0;
	while (link = document.links[i++]) {
		link.target = '_blank';
	}
}

function showHelp(clickObject){
	window.top.Docova.Utils.messageBox({
		title: "DOCOVA Richtext Editor Help",
		prompt: helpText,
		width: 400,
		icontype : 4,
		msgboxtype : 0		
	});
}

var currEditAction = "";  //global so that it can be set in setColor where colorpicker utilizes a callback funtion to setTextColor
function setTextColor(colorval){
	document.execCommand(currEditAction, "", colorval);
}

function setColor(event){
	var source = event.target;
	var editAction = $(source).attr("editaction");
	currEditAction = editAction;

	window.top.Docova.Utils.colorPicker(event, "", setTextColor);
}


function applyTool(editObj, reset){  
	var editAction = $(editObj).attr("editaction");
	
	if(editAction == "FontSize" || editAction == "FontName"){
		var editValue = $(editObj).val();
		document.execCommand(editAction, "", editValue);
	}else{
		document.execCommand(editAction);
	}
	
	if(reset){$(editObj).val("null");}
}

function loadIcons(toolBarCount)
{
	var spanrepeat = 0;
	setTargets();  //force all links to open in a new window
	if(loadDocovaEditor){
		storedTBarCount = toolBarCount
		var tools = ["sep","Bold","Italic","Underline","StrikeThrough","sep","JustifyLeft","JustifyCenter",
		"JustifyRight","sep","Cut","Copy","Paste","sep","ForeColor","BackColor","sep","InsertUnorderedList",
		"InsertOrderedList","Indent","Outdent","sep","CreateLink","Unlink","sep","Undo","Redo",
		"InsertHorizontalRule","RemoveFormat","sep","Help","FontName","FontSize"];
		for(z=0; z<=toolBarCount; z++){
			//load the toolbar
			tbar = document.getElementById("dToolbar" + [z])
			tbarHTML = "";
			for(x=0; x<tools.length; x++){
				if(tools[x] == "sep"){ //uses sep to make buttonsets
					spanrepeat = spanrepeat + 1;
					if(spanrepeat == 1){
							tbarHTML += "<span class='btngroup'>";
					}
					if(spanrepeat > 1){
						tbarHTML += "</span><span class='btngroup'>";
					}
				}
				else if(tools[x] == "FontName") {
					tbarHTML += "<br><select unselectable='on' class='DropDown' id=" + tools[x]+[z] + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" +
					"<option value='null'>- Select Font -<option value='Arial'>Arial" +
					"<option value='Book Antiqua'>Book Antiqua</font><option value='Comic Sans MS'>Comic Sans<option value='Courier New'>Courier New" + 
					"<option value='Tahoma'>Tahoma<option value='Times New Roman'>Times New Roman<option value='Verdana'>Verdana</select>";
				} 
				else if(tools[x] == "FontSize") {
					tbarHTML += " <select unselectable='on' class='DropDown' id=" + tools[x]+[z] + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" + 
					"<option value='null'>- Font Size -<option value='1'>1 (8pt)" +
					"<option value='2'>2 (10pt)<option value='3'>3 (12pt)<option value='4'>4 (14pt)<option value='5'>5 (18pt)<option value='6'>6 (24pt)<option value='7'>7 (36pt)</select>";
				} 
				else if(tools[x] == "ForeColor" ||tools[x] == "BackColor") { 
					tbarHTML += "<button type='button' type='button' unselectable='on' class='btnDEditor " + "edt" + tools[x] + "' onclick='setColor(event)' id=" + tools[x]+[z] + " editaction='" + tools[x] + "'></button>"
				}
				else if(tools[x] == "Help") {
					tbarHTML += "<button type='button' type='button' unselectable='on' class='btnDEditor " + "edt" + tools[x] + "' onclick='showHelp(this);' id=" + tools[x]+[z] + " editaction='" + tools[x] + "'></button>"
				}
				else {
					tbarHTML += "<button type='button' type='button' unselectable='on' class='btnDEditor dtoolbar " + "edt" + tools[x] + "' onclick='applyTool(this)' id=" + tools[x]+[z] + " editaction='" + tools[x] + "'></button>"
				}
			}
			if(spanrepeat != 0){
				tbarHTML += "</span>"; //close off last span tag for button groups if any
			}
			$(tbar).html(tbarHTML);
			
			//load any existing content into divs from source fields
			targetDiv = document.getElementById("dEdit" + [z]);
			srcField = document.getElementById($(targetDiv).attr("name"));
			targetDiv.innerHTML = $(srcField).val()
		}
	}
}

function fixRelativeImagePaths(){
  	//check if there are any attachments
 	 if (docInfo.DocAttachmentNames ==null || docInfo.DocAttachmentNames ==""){
	 	return;
 	 }
  	var attachList = docInfo.DocAttachmentNames.split("*"); 	 
    jQuery("#spanBody, #dEdit0").find("img").each(function(){
	 	var newSrc= jQuery(this).attr("src");
	 	newSrc = newSrc.replace(/^.*[\\\/]/, '');
		if (attachList.indexOf(newSrc) > -1){
			newSrc = "/" + docInfo.NsfName + "/openDocFile/" + escape(newSrc) + "?OpenElement&doc_id=" + docInfo.DocKey;
			//newSrc = "/" + docInfo.NsfName + "/luAllByDocKey/" + docInfo.DocKey + "/$File/" + newSrc;
			jQuery(this).attr("src", newSrc);
		}
    });
}
