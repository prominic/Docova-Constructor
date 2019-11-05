var labelcount = 1;
var attachmentcount = 0;
var ctextcount = 1;	
var outlinecount = 1;
var buttoncount = 1;
var framesetcount = 1;
var framecount = 1;
var imagecount = 1;
var sectioncount = 2;
var htmlcodecount = 1;
var tabscount = 1;
var openPassThru = false;
var openParElem = false;
var passThruHtmlCode = "";
var cancelImport = false;
var pardefArr = [];
var importFromDocova = false;



var tabcellarray = [];
var base64images=[];
var responseforms=[];

function getRandomID(){
	var rID = parseInt((Math.random() * 1000), 10)
	return rID
}

function _getCode(obj, parseResult, event, leavetagsalone){
	var actionNode = obj;
	var formula = "";
	var codetype = "";
	var codeevent = "";
	
	//-- check if we have been passed a code node and if so whether it matches the even type restrictions or is one of the excluded events
	if(actionNode.nodeName=="code"){
		if(typeof event !== 'undefined' && event !== null ? actionNode.getAttribute("event") != event : (actionNode.getAttribute("event")=="windowtitle" ? true : false)){
			return formula;			
		}
		codeevent = actionNode.getAttribute("event");		
	}
	
	//-- look at the children of the passed node 
	for ( var k = 0; k < actionNode.childNodes.length; k ++){
		var tmpnode = actionNode.childNodes[k];
		var resolve = ((tmpnode.nodeName=="code" || tmpnode.nodeName == "formula" || tmpnode.nodeName == "lotusscript" || tmpnode.nodeName == "javascript") &&  (typeof event !== 'undefined' && event !== null ? tmpnode.getAttribute("event") == event : (tmpnode.getAttribute("event")=="windowtitle" ? false : true)));

		if ( resolve){
			var formulanode = null;
			if ( tmpnode.nodeName == "code"){
				codeevent = tmpnode.getAttribute("event");
					
				for(var c=0; c<tmpnode.childNodes.length; c++){
					switch(tmpnode.childNodes[c].nodeName){
					case "javascript":
					case "formula":
					case "lotusscript":
					case "simpleaction":
						formulanode = tmpnode.childNodes[c];
						break;
					}
				}
			}else{ 
				formulanode = tmpnode;
			}
			
			if ( formulanode && formulanode.nodeName=="formula"){
				codetype = "formula";
				for ( var c = 0; c < formulanode.childNodes.length; c++){
					if(formulanode.childNodes[c] && formulanode.childNodes[c].nodeType == 3){
						formula += formulanode.childNodes[c].textContent || formulanode.childNodes[c].text || "";
					}
				}
				break;
			}else if(formulanode && formulanode.nodeName=="lotusscript"){
				codetype = "lotusscript";
				for ( var c = 0; c < formulanode.childNodes.length; c++){
					if(formulanode.childNodes[c] && formulanode.childNodes[c].nodeType == 3){
						formula += formulanode.childNodes[c].textContent || formulanode.childNodes[c].text || "";
					}
				}
				if(tmpnode.getAttribute("event") == "options" || tmpnode.getAttribute("event") == "declarations"){
					//-- keep looping since we want more than just options and declarations
				}else{
					break;
				}
			}else if(formulanode && formulanode.nodeName=="javascript"){
				codetype = "javascript";
				//--check to see if this js is only for client
				var tmpfor = tmpnode.getAttribute("for") || "";
				if(tmpfor != "client"){
					for ( var c = 0; c < formulanode.childNodes.length; c++){
						if(formulanode.childNodes[c] && formulanode.childNodes[c].nodeType == 3){
							formula += formulanode.childNodes[c].textContent || formulanode.childNodes[c].text || "";
						}
					}
				}
			}else if(formulanode && formulanode.nodeName=="simpleaction"){				
				var actionname = formulanode.getAttribute("action");
				codetype = "formula";
				if(actionname == "delete"){
					formula = '@Command([EditClear])';
				}else if(actionname == "runagent"){
					formula = '@Command([ToolsRunMacro]; "' + formulanode.getAttribute("agent") + '")';
				}else if(actionname == "modifyfield"){
					formula = '@SetFieldValue(@DocumentUniqueID; "' + formulanode.getAttribute("field") + '"; "' + formulanode.getAttribute("value") + '")';
				}else{
					formula = '""';
				}
				break;				
			}
		}
	}
	if(formula && formula != ""){
		if ( parseResult ){		
			var prependcode = "";
			var appendcode = "";

			if(codetype == "formula" || codetype == "javascript"){
				switch((codeevent ? codeevent.toLowerCase() : "")){
				case "queryopen":
					prependcode = "function Queryopen(Source, Mode, Isnewdoc) {\r";
					prependcode += "var return_queryopen = true;\r";
					//converted formula will end up here
					appendcode = "return return_queryopen;\r";
					appendcode += "}\r";
					break;
				case "webqueryopen":
					prependcode = "function Webqueryopen() {\r";
					prependcode += "var return_webqueryopen = true;\r";
					//converted formula will end up here
					appendcode = "return return_webqueryopen;\r";
					appendcode += "}\r";
					break;					
				case "postopen":
					prependcode = "function Postopen(Source) {\r";
					//converted formula will end up here
					appendcode = "}\r";					
					break;
				case "querymodechange":
					prependcode = "function Querymodechange(Source) {\r";
					prependcode += "var return_querymodechange = true;\r";
					//converted formula will end up here
					appendcode = "return return_querymodechange;\r";
					appendcode += "}\r";								
					break;
				case "postmodechange":
					prependcode = "function Postmodechange(Source) {\r";
					//converted formula will end up here
					appendcode = "}\r";										
					break;
				case "querysave":
					prependcode = "function Querysave(Source) {\r";
					prependcode += "var return_querysave = true;\r";
					//converted formula will end up here
					appendcode = "return return_querysave;\r";
					appendcode += "}\r";													
					break;
				case "webquerysave":
					prependcode = "function Webquerysave(Source) {\r";
					prependcode += "var return_webquerysave = true;\r";
					//converted formula will end up here
					appendcode = "return return_webquerysave;\r";
					appendcode += "}\r";													
					break;					
				case "postsave":
					prependcode = "function Postsave(Source) {\r";
					//converted formula will end up here
					appendcode = "}\r";					
					break;
				case "queryclose":
					prependcode = "function Queryclose(Source) {\r";
					prependcode += "var return_queryclose = true;\r";
					//converted formula will end up here
					appendcode = "return return_queryclose;\r";
					appendcode += "}\r";																		
					break;
				case "queryrecalc":
					prependcode = "function Queryrecalc(Source) {\r";
					prependcode += "var return_queryrecalc = true;\r";
					//converted formula will end up here
					appendcode = "return return_queryrecalc;\r";
					appendcode += "}\r";																		
					break;
				case "postrecalc":
					prependcode = "function Postrecalc(Source) {\r";
					//converted formula will end up here
					appendcode = "}\r";										
					break;
				}				
			}
			
			if(codetype=="formula"){				
				var LexerObj = new Lexer(); 
				var tempformula = LexerObj.convertCode(formula, "JS");
				formula = "";
				formula += prependcode;
				formula += "try{\r";
				formula += tempformula;
				formula += (tempformula.slice(-1) != ";" && tempformula.slice(-1) != "}") ? ";" : "";
				formula += "\r}catch(e){if(e instanceof _HandleReturnValue){finalreturnvalue = _lastreturnvalue;}else{throw e;}}";
				formula += appendcode;
				
				formula = beautify(formula);
			}else if(codetype=="lotusscript"){
				var LexerObj = new LexerLS(); 
				
				var striptext = "Sub Click(Source As Button)";
				var pos = formula.indexOf(striptext);
				if(pos > -1){
					formula=formula.slice(0, pos) + formula.slice(pos + striptext.length);
					
					striptext = "End Sub";
					if(formula.indexOf(striptext, formula.length - striptext.length)>-1){
						formula = formula.slice(0, formula.length - striptext.length);
					}					
				}
				
				var striptext = "Sub Onchange(Source As Field)";
				var pos = formula.indexOf(striptext);
				if(pos > -1){
					formula=formula.slice(0, pos) + formula.slice(pos + striptext.length);
					
					striptext = "End Sub";
					if(formula.indexOf(striptext, formula.length - striptext.length)>-1){
						formula = formula.slice(0, formula.length - striptext.length);
					}					
				}				

				formula = LexerObj.convertCode(formula, "JS");
				formula = beautify(formula);
			}else if(codetype == "javascript"){
				var tempformula = formula;
				formula = "";
				formula += prependcode;
				formula += tempformula;
				formula += (tempformula.slice(-1) != ";" && tempformula.slice(-1) != "}") ? ";" : "";
				formula += "\r";
				formula += appendcode;
				
				formula = beautify(formula);
			}
			if(!leavetagsalone){
				formula = safe_quotes_js(safe_tags(formula), false);
			}
		}
	}

	
	return formula;
}

function getObjectHideWhen(obj, containertype)
{
	var hidewhenstr = "hidewhen='";
	var custhidestr = "";
	
	var hidenode = null;

	var parid = "";
	if(obj.nodeName == "sectiontitle"){
		try{
			parid = (obj.getAttribute("pardef") || "");
		}catch(e){}
		var cnode = null
		try{
			cnode = pardefArr[parid];
		}catch(exception){}
		if (cnode && cnode.nodeName=="pardef") {
			var pardefid = cnode.getAttribute("id");
			if ( pardefid == parid) {
				hidenode = cnode;
			}
		}		
	}
	
	if(!hidenode){
		//-- look to see if the current object has any hide when code/settings associated with it
		var hidewhen = "";
		try{
			hidewhen = obj.getAttribute("hide");
		}catch(e){};
		if(hidewhen && hidewhen != ""){
			hidenode = obj;
		}else{
			if(obj.childNodes){
				//look for any custom hide when formula
				for(var h=0; h<obj.childNodes.length; h++){
					var codenode = obj.childNodes[h];
					if(codenode.nodeName == "code" && codenode.getAttribute("event") == "hidewhen"){
						hidenode = obj;
					}
				}
			}
		}

		//-- if no hide when code/settings on the current node look to the parent 
		if(! hidenode){
			var par = null;
			if ( obj.parentNode.nodeName == "run" ){
				par = obj.parentNode.parentNode;
			}else if(obj.nodeName == "par"){
				par = obj;
			}else{ 
				par = obj.parentNode;
			}
			if ( par){
				parid = par.getAttribute("def");
				var cnode = null
				try{
					cnode = pardefArr[parid];
				}catch(exception){}
				if (cnode && cnode.nodeName=="pardef") {
					var pardefid = cnode.getAttribute("id");
					if ( pardefid == parid) {
						hidenode = cnode;
					}
				}
			}
		}
	}

	//-- did we find any hide when code/settings on the current node or its parent
	if(hidenode){
		//look for edit and read hide when attributes
		var hidewhen = hidenode.getAttribute("hide");
		if  ( hidewhen ){
			var hidewhenarray = hidewhen.split(" ");
			if ( hidewhenarray.indexOf("notes") != -1){
				hidewhenstr += "R;E";
			}else{
				hidewhenstr += ((hidewhenarray.indexOf("read") != -1 ) ? "R" : "");
				//ignore hide in edit mode for view actions
				if(!(typeof containertype !== "undefined" && containertype == "view")){
					hidewhenstr += ((hidewhenarray.indexOf("edit") != -1 ) ? (hidewhenstr != "" ? ";" : "") + "E" : "");
				}
			}	
		}	
		//look for any custom hide when formula
		for(var h=0; h<hidenode.childNodes.length; h++){
			var codenode = hidenode.childNodes[h];
			if(codenode.nodeName == "code" && codenode.getAttribute("event") == "hidewhen"  && codenode.getAttribute("enabled") !== "false"){
				for(var f=0; f<codenode.childNodes.length; f++){
					var formulanode = codenode.childNodes[f];
					if(formulanode.nodeName == "formula"){
						hidewhenstr += (hidewhenstr != "" ? ";" : "") + "C";
						for(var l=0; l<formulanode.childNodes.length; l++){
							if(formulanode.childNodes[l].nodeValue != null && formulanode.childNodes[l].nodeValue.trim().length > 0){
								custhidestr += formulanode.childNodes[l].nodeValue;	
							}
						}						
					}
				}							
			}
		}
	}
	
	var temphidestring = safe_tags(custhidestr);
	//special case for views need to double encode & char
	if((typeof containertype !== "undefined" && containertype == "view")){
		temphidestring = temphidestring.replace(/&/g,'&amp;')
	}
	hidewhenstr += "' customhidewhen='" + safe_quotes_js(temphidestring, false) + "'";
	
	return hidewhenstr;
	
}


function getObjectStyleStr(fontnode){
	var style = "";
	var sz = fontnode.getAttribute("size");
	var tempstyle = (fontnode.getAttribute("style") || "").split(" ");
	var fw = (tempstyle.indexOf("bold") > -1 ? "bold" : "");
	var fs = (tempstyle.indexOf("italic") > -1 ? "italic" : "");
	var td = (tempstyle.indexOf("underline") > -1 ? "underline " : "");
	td += (tempstyle.indexOf("strikethrough") > -1 ? "line-through " : "");		
	var color = fontnode.getAttribute("color");
	var borderwidth = fontnode.getAttribute("borderwidth");
	var bgcolor = fontnode.getAttribute("bgcolor")
	if ( sz && sz != ""){
		style += "font-size:" + (parseInt(sz)+2) + "px; ";
	}
	if ( fw && fw != ""){
		style += "font-weight:" + fw + "; ";
	}
	if (fs && fs != ""){
		style += "font-style:" + fs + "; ";		
	}
	if (td && td != ""){
		style += "text-decoration:" + td + "; ";		
	}	
	if ( color && color != ""){
		style += "color:" + color + "; ";
	}
	if ( bgcolor && bgcolor != ""){
		style += "background-color:" + bgcolor + "; ";
	}
	if ( borderwidth && borderwidth != ""){
		style += "border-width:" + borderwidth + "; ";
	}
	return style;
}
function getObjectStyle(obj){
	var style = "";
	
	if ( obj.nodeName == "tablecell"){
		style = getObjectStyleStr(obj);
	}else if  (obj.parentNode.firstChild && obj.parentNode.firstChild.nodeName=="font"){
		var fontnode = obj.parentNode.firstChild;
		style = getObjectStyleStr(fontnode);
		
	}
	return style;
}



function _convertSizeAttrib(inpsize){
	var result = "";
	
	var tempsize = inpsize;
	if(tempsize.indexOf("in") > -1){
		tempsize = parseInt(Number(inpsize.replace("in", "")) * 96, 10);
		result = tempsize.toString() + "px";
	}else if(tempsize.indexOf("cm") > -1){
		tempsize = parseInt(Number(tempsize.replace("cm", "")) * 38, 10);
		result = tempsize.toString()+ "px";		
	}else if(tempsize.indexOf("%") > -1){
		result = tempsize;
	}else if(tempsize.indexOf("px") > -1){
		tempsize = parseInt(Number(tempsize.replace("px", "")), 10);
		result = tempsize.toString()+ "px";
	}
	return result;
}


function _previousElementSibling( el ) {
	var prevEl = el;
    do { 
    	prevEl = prevEl.previousSibling;
    } while ( prevEl && prevEl.nodeType !== 1 );
    
    return prevEl;
}

function _nextElementSibling( el ) {
	var nextEl = el;
    do { 
    	nextEl = nextEl.nextSibling;
    } while ( nextEl && nextEl.nodeType !== 1 );
    
    return nextEl;
}



function getElementHtml(obj, options)
{
	//text node
	var str = "";
		
	if (obj.nodeType == 3 ) {
		if ( obj.nodeValue != null && obj.nodeValue.trim().length > 0 && obj.parentNode && (obj.parentNode.nodeName== "par" || obj.parentNode.nodeName=="run")){
						
			if(obj.parentNode.nodeName== "run" && obj.parentNode.getAttribute("html") === "true"){
				passThruHtmlCode += obj.nodeValue;				
			}else{
				//check if this is an action hotspot
				var formula = "";
				var parentNode = obj.parentNode;
				if(parentNode.nodeName == "run"){
					parentNode = parentNode.parentNode;
				}
				if ( parentNode.nodeName == "actionhotspot"){				
					formula = _getCode(parentNode, true);
				}
				
				//--check if this element has a hotspot defined
				if(jQuery.trim(formula) != ""){
					str += "<label style='" + getObjectStyle(obj) + "' id='label_" + labelcount + "' name='label_" + labelcount + "' elem='label' elemtype='field' contenteditable=true class='selectable' fonclick='" + formula + "' >";
					str += obj.nodeValue;		
					str += "</label>";
					
					labelcount++;
				}else{
					str += "<font style='" + getObjectStyle(obj) + "' >";
					str += obj.nodeValue;		
					str += "</font>";					
				}			
			}			
		}
	}else if (obj.nodeName == "par"){
		if(!openPassThru){
			str += "<p class='selectable' elemtype='par' elem='par' " + getObjectHideWhen(obj) + ">"
			openParElem = true;
		}else{
			passThruHtmlCode += "\n";
		}
	}else if ( obj.nodeName == "computedtext"){	
		var formula = "";
		var formula = _getCode(obj, false);
		str += "<input elemtype='field' class='selectable' elem='ctext' placeholder='@ComputedText' style='" + getObjectStyle(obj) + "' id='computed_text_" + ctextcount + "' fformula='" + safe_quotes_js(formula, false) + "' ></input>";
		ctextcount++;
	}else if ( obj.nodeName == "subformref"){
		var subformname = obj.getAttribute("name");
		var formula = "";
		formula = _getCode(obj, false);
		if(formula == ""){
			if(subformname && subformname != ""){
				formula = '"' + jQuery.trim(subformname.split("|")[0]) + '"';
			}else{
				formula = '""';  //TODO - add an error trap or notification
			}
		}
		
		str += "<div id='section_" + sectioncount + "' class='selectable noselect ui-sortable-handle' elem='subform' elemtype='field' subform='Computed' insertmethod='Computed' subformformula='" + safe_tags(formula) + "' htmlclass='' htmlother='' additional_style='' style='' elem_index='' fonclick='' fonchange='' fonfocus='' fonblur='' hidewhen='' customhidewhen='' contenteditable='false' >";
		str += "<div class='item'>";
		str += "<i class='fa fa-indent fa-3x' style='color:gray'></i>";
		str += "<span class='caption noselect' contenteditable='false'>";
		str += "Custom Subform: COMPUTED";
		str += "</span>";
		str += "</div>";
		str += "</div>";
		sectioncount ++;
	}else if ( obj.nodeName == "embeddedoutline"){
		var name = obj.getAttribute("outline");
		var expand = obj.getAttribute("expand");
		var style = getObjectStyle(obj);
		style += ';width:200px; height:600px; border-width: 0px;';
		var hidewhenstr = getObjectHideWhen(obj);
		
		str += "<div id='outline_" + outlinecount.toString() + "' width='100%' height='600' class='selectable noselect' elem='outline' elemtype='field' contenteditable='false' expand='" + expand + "' elem_index='' style='" + style + "' " + hidewhenstr + " outlinename='" + name + "' contenteditable='false'>";
		str += "<div class='item'>";
		str += "<i class='fa fa-bars fa-3x' style='color:gray'/>";
		str += "<span class='caption noselect' contenteditable='false'>Outline: " + name + "</span>";
		str += "</div>";
		str += "</div>";		
		
		outlinecount ++;	
	}else if ( obj.nodeName == "embeddedview"){
		var height = obj.getAttribute("height") || "";
		if(height.indexOf("in") > -1){
			height = parseInt(Number(height.replace("in", "")) * 96, 10);
		}else if(height.indexOf("cm") > -1){
			height = parseInt(Number(height.replace("cm", "")) * 38, 10);
		}
		if(isNaN(height) || height == ""){
			height = "200";
		}
				
		var id = "section_" + sectioncount;
		var singlecatformula = safe_quotes(safe_tags(_getCode(obj, false, "showsinglecategory")));
		var viewformula = safe_quotes(safe_tags(_getCode(obj, false, "value")));
		
		var viewname = obj.getAttribute("name");
		viewname = (viewname === null ? "" : viewname);
		
		var hidewhenstr = getObjectHideWhen(obj);
		str += '<div id="'+ id + '" lunsfname="" class="selectable noselect ui-sortable-handle" style="height:' + height + 'px" elem="embeddedview" embviewheight="' + height + '" elemtype="field" embviewname="' + viewname + '" fformula="' + singlecatformula + '" fdefault="' + viewformula + '" luservername="" ' + hidewhenstr +' contenteditable="false" bc="0">';
		str += '<div class="item">';
		str += '<i class="fa fa-list-ul fa-3x" style="color:gray"></i>';
		str += '<span class="caption noselect">';
		str += 'Embedded View';
		str += '</span>';
		str += '</div>';
		str += '</div>';
		sectioncount ++;
		
	}else if (obj.nodeName == "button"){
		var title = "";
		var formula = "";
		for ( var k = 0; k < obj.childNodes.length; k ++){
			var tmpnode = obj.childNodes[k];
			if(tmpnode.nodeType == 3){
				title = tmpnode.textContent || tmpnode.text || "";
				break;
			}
		}
		
		formula = _getCode(obj,true, "click");
		formula = safe_tags(formula );
		
		var rethtml = "";
		rethtml += "<button id='button_" + buttoncount + "' contenteditable='false' class='btn selectable ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' elem='button' elemtype='field' btnlabel ='" + title.replace(/&/g,'&amp;') + "' fonclick='" + formula + "' title='" + title.replace(/&/g,'&amp;amp;') + "'>"
		rethtml += "<span class='ui-button-text'>" + title.replace(/&/g,'&amp;') + "</span>"
		rethtml += "</button>"
			
		str += rethtml;
		buttoncount ++;		

	}else if ( obj.nodeName == "field"){
		var name = obj.getAttribute("name");
		var type = obj.getAttribute("type");
		var kind = obj.getAttribute("kind");
		var height = _convertSizeAttrib(obj.getAttribute("height")||"");		
		var width = _convertSizeAttrib(obj.getAttribute("width")||"");
		
		if(type == "text" && kind == "editable" && options && options.largetextfields){
			if(options.largetextfields.indexOf(name.toLowerCase())>-1){
				type = "textarea";
			}
		}
		
		var mv = obj.getAttribute("allowmultivalues");
		mv = (typeof mv === 'undefined' ? "" : mv);
		var mvsep = obj.getAttribute("listinputseparators");
		mvsep = (mv !== "true" || typeof mvsep === 'undefined' ? "" : mvsep.toLowerCase());

		var style = getObjectStyle(obj);
		var widthstyle = "";
		var numformatattrib = "";
		var heightstyle = (height == "" ? "" : "height:" + height + ";");
		var formula = "";
		var typelabel = "texttype";
		var otherattrib = "";
		var fdefault = "";
		var ftranslate = "";
		var fonchange = "";
		
		if ( type=="text" || type=="datetime" || type=="number" || type=="names" || type=="readers" || type=="authors"){
			var textrole = "t";
			var selem = "text";
			widthstyle = "width:" + (width == "" ? "200px" : width) + ";";
						
			if ( type=="datetime"){
				selem = "date";
				typelabel = "datetype";
				widthstyle = "width:" + (width == "" ? "100px" : width) + ";";
				for(var c=0; c<obj.childNodes.length; c++){
					if(obj.childNodes[c].nodeName == "datetimeformat"){
						if(obj.childNodes[c].getAttribute("show") === "datetime"){
							otherattrib = "  displaytime='true' ";
						}else if(obj.childNodes[c].getAttribute("show") === "time"){
							otherattrib = "  displayonlytime='true' ";
						}
					}
				}
			}else if(type == "number"){
				textrole = "n";
				widthstyle = "width:" + (width == "" ? "50px" : width) + ";";
				for(var c=0; c<obj.childNodes.length; c++){
					if(obj.childNodes[c].nodeName == "numberformat"){
						if(obj.childNodes[c].getAttribute("format") === "fixed"){
							numformatattrib = " numformat='auto' textnumdecimals='" + (obj.childNodes[c].getAttribute("digits")||0) + "' ";
						}
					}
				}				
			}else if(type == "readers"){
				textrole = "r";
			}else if(type == "authors"){
				textrole = "a";
			}else if(type == "names"){
				textrole = "names";
			}
			
			if ( kind == "editable"){	
				fdefault = safe_quotes_js(safe_tags(_getCode(obj, false, "defaultvalue"), false));					
				ftranslate = _getCode(obj, false, "inputtranslation");
				fonchange = safe_quotes_js(safe_tags(_getCode(obj, true, "onchange")), false);
				
				if(type =="names" || type == "authors" || type == "readers"){
					str += "<input class='selectable' id='" + name + "' name='" + name + "' placeholder=" + name + " ondrop='return false;' value='' style='width:200px;' elem='text' elemtype='field' fdefault='" + fdefault + "' + textrole='"+ textrole +"' texttype='e' textmv='"+ (mv === "true" ? "true" : "") + "' textnumdecimals='0' textalign='left' numformat='auto' elem_index='' ftranslate='"+ ftranslate + "' textnp='' textmvsep='" + mvsep + "' fonchange='" + fonchange + "' fonfocus='' fonblur='' >";
				}else{
					str += "<input elemtype='field' style='" + widthstyle + heightstyle + style + "' class='selectable' ondrop='return false;' elem='" + selem + "' textrole='" + textrole + "' texttype='e'  type=text id='" + name + "' name='" + name + "' placeholder='" + name + "' " + otherattrib + numformatattrib + " fdefault='" + fdefault + "' ftranslate='" + ftranslate + "' fonchange='" + fonchange + "' textmv='" + mv + "' textmvsep='" + mvsep +"' >";
				}
			}else if (kind == "computedfordisplay" || kind=="computed" || kind=="computedwhencomposed"){
				formula = safe_quotes_js(safe_tags(_getCode(obj, false, "defaultvalue"), false));
				var dtype = "";
				if ( kind == "computedfordisplay"){
					dtype = "cfd";
				}else if ( kind == "computed"){
					dtype = "c";
				}else{
					dtype="cwc";
				}
									   
				str += "<input elemtype='field' " + typelabel + "='" + dtype + "' fdefault='" + formula + "' style='" + widthstyle + heightstyle + style + "' class='selectable' elem='" + selem + "' textrole='" + textrole + "'  type=text id='" + name + "' name='" + name + "' placeholder='" + name + "' " + otherattrib + numformatattrib + " textmv='" + mv + "' textmvsep='" + mvsep +"' >";
			}	
		}else if ( type == "keyword"){
			var otype = obj.firstChild.getAttribute("ui")
			var defaultNode = null;
			var keywordNode = null;
			var child = obj.firstChild;
			var defval = "";
			var formulaval = "";
			var formula = "";
			var refreshonchange = "";
			var fonchange = "";
			var allownew = "";
			var elem = "";
			var tblwidth = "";
			var fonchange = "";
			
			while ( child){
				if(child.nodeName == "keywords"){
					keywordNode = child;
				}
				child = child.nextSibling;
			}
			
			defval = "fdefault='" + safe_quotes_js(safe_tags(_getCode(obj,false, "defaultvalue"), false)) + "'"
			fonchange = safe_quotes_js(safe_tags(_getCode(obj, true, "onchange")), false);
			
			if ( keywordNode){
				formula = safe_quotes_js(safe_tags(_getCode(keywordNode, false), false));
				formulaval = "fformula='" + formula + "'"
				
				refreshonchange = keywordNode.getAttribute("recalconchange") || "";
				if(refreshonchange == "true"){
					if(fonchange != ""){
						fonchange += "\r";
					}
					fonchange += safe_quotes_js(safe_tags("$$Command('[ViewRefreshFields]');"), false); 
				}
				allownew = (keywordNode.getAttribute("allownew") || "");
				if(allownew == "true"){
					allownew = "allownewvals='1'";
				}else{
					allownew = "";
				}
			}
			
			widthstyle = "width:200px;";
			if ( otype == "radiobutton" || otype == "checkbox" ){
				var tpe = otype == "radiobutton" ? "radio" : "checkbox";
				elem = otype == "radiobutton"? "rdbutton" : "chbox";
				tblwidth = "tblwidth='200'";
			}else{
				elem = "select";
			}		
			var choicetype = (formula != "" ? "formula" : "manual");
			
			str += "<input " + defval + " style='" + widthstyle + heightstyle + style + "' class='selectable' ondrop='return false;' optionmethod='" + choicetype + "' elem='" + elem + "' texttype='e' id='" + name + "' name='" + name + "' placeholder='" + name + "' " + formulaval + " fonchange='" + fonchange + "' " + tblwidth + " " + allownew;
			if(choicetype == "manual"){
				str += " optionlist='";
				var tlist = obj.firstChild.firstChild;
				if (tlist) {
					for ( var p =0; p < tlist.childNodes.length; p++){
						var val = tlist.childNodes[p].firstChild;
						var sval = val.nodeValue;
						str += (p>0 ? ";" : "") + sval;
					}
				}
				str += "' ";
			}
			str += ">";
			str += "</input>";	
		}else if ( type=="richtext"){
			style += ";height:150px;width:740px;";			
			str += "<textarea elemtype='field' placeholder='" + name + "' readonly='' style='" + style + "' class='selectable' elem='tarea' editortype='R' editorheight='150'  id='" + name + "' name='" + name + "'></textarea>";
		}else if ( type=="textarea"){
			style += ";height:100px;width:740px;";			
			str += "<textarea elemtype='field' placeholder='" + name + "' readonly='' style='" + style + "' class='selectable' elem='tarea' editortype='P' limittextlength='1' editorheight='100'  id='" + name + "' name='" + name + "'></textarea>";			
		}else if ( type=="richtextlite"){
			var onlyallow = obj.getAttribute("onlyallow") || "";
			if(onlyallow == "attachment"){
				str += getAttachmentHtml(name);
			}else{
				style += ";height:150px;width:740px;";			
				str += "<textarea elemtype='field' placeholder='" + name + "' readonly='' style='" + style + "' class='selectable' elem='tarea' editortype='D' editorheight='150'  id='" + name + "' name='" + name + "'></textarea>";			
			}
		}
	}
		
	return str;
}

function getAttachmentHtml(fieldname){
	attachmentcount ++;
	var rethtml = "";
    rethtml += "<div id='att_" + ((typeof fieldname !== 'undefined' && fieldname !== "") ? fieldname : attachmentcount.toString()) +  "' class='selectable noselect' elem='attachments' elemtype='field' style='width: 99%; height: 100px;' attachmentsheightvalue_prop='100' attachmentswidthvalue_prop='99%' listtype_prop='I' contenteditable=false><div class='item'><i class='far fa-paperclip fa-3x' style='color:gray'></i><span class='caption noselect'>Attachments</span></div></div>";
		
    return rethtml;
}

function getAgentHtml(obj, codetype)
{

	if(codetype === undefined){
		var codetype = "lotusscript";
	}
	var rethtml = "";
	for ( var j = 0; j < obj.childNodes.length; j++)
	{
		child = obj.childNodes[j];
		if ( child.nodeName == "code")
		{
			for ( var p = 0; p < child.childNodes.length; p++){
				var scriptNode = child.childNodes[p];
				if ( scriptNode.nodeName == codetype)
				{
					rethtml += scriptNode.textContent || scriptNode.text || "";
					rethtml += "\n";
				}
			}
		}
		
	}
	return rethtml;
}

function getPropertiesHtml(obj, elementtype)
{
	var rethtml = "";
	
	if(elementtype === undefined){
		return rethtml;
	}
	
	if(elementtype == "page"){
		rethtml = "<PageBackgroundColor>";
		var bgcolor = obj.getAttribute("bgcolor");
		if(bgcolor){
			bgcolor = colorToHex(bgcolor)
			rethtml += bgcolor;
		}
		rethtml += "</PageBackgroundColor>";
	}

	return rethtml;
}



function getJSHeaderHtml(obj)
{
	var rethtml = "";
		
	for ( var j = 0; j < obj.childNodes.length; j++){
		child = obj.childNodes[j];
		var tempcode = "";		
		if(child.nodeName == "globals"){
			if(child.childNodes){
				for(var k=0; k<child.childNodes.length; k++){
					tempcode = _getCode(child.childNodes[k], true, null, true);	
					if(tempcode !== ""){
						rethtml += "\n" + tempcode + "\n";
					}					
				}
			}
		}else{
			tempcode = _getCode(child, true, null, true);
			if(tempcode !== ""){
				rethtml += "\n" + tempcode + "\n";
			}			
		}		
	}
	return rethtml;
}


function getViewJSCode(obj)
{
	var rethtml = "";
		
	for ( var j = 0; j < obj.childNodes.length; j++){
		child = obj.childNodes[j];
		var tempcode = "";		
		if(child.nodeName == "globals"){
			if(child.childNodes){
				for(var k=0; k<child.childNodes.length; k++){
					tempcode = _getCode(child.childNodes[k], true, null, true);	
					if(tempcode !== ""){
						rethtml += "\n" + tempcode + "\n";
					}					
				}
			}
		}else if(child.nodeName == "code" && child.getAttribute("event") !== "selection" && child.getAttribute("event") !== "form"){
			tempcode = _getCode(child, true, null, true);
			if(tempcode !== ""){
				rethtml += "\n" + tempcode + "\n";
			}			
		}		
	}

	return rethtml;
}


function DXLTree( obj, options)
{

	var str = "";

	//--opening tags
	if (obj.nodeType != 3 ) 
	{
		if ( obj.nodeName == "table" && obj.getAttribute("rowdisplay") != "tabs"){
			//--normal table not a tabbed table
			if(openPassThru == true){
				str += btoa(encodeURIComponent(passThruHtmlCode));  //--store the encoded pass thru html
				str += "</div>";   //-- close the pass thru html since we don't support table in html area
				openPassThru = false;
				passThruHtmlCode = "";
			}			
			str += "<table elem='table' elemtype='field' style='border-spacing:0px;border-collapse: collapse;' contenteditable='false'>";
			str += "<thead style='visibility:visible; line-height:0'>";
			str += "<tr>";
			
			//get the td widths
			var parent = obj;			
			var colcount = 0;
			for ( var t=0; t < parent.childNodes.length; t++){
				var tc = parent.childNodes[t];
				if ( tc.nodeName == "tablecolumn"){
					colcount ++;
				}
			}
			var curcol = 0;
			var widthcount = 0;			
			var lengthArr=[];
			for ( var t=0; t < parent.childNodes.length; t++){
				var tc = parent.childNodes[t];
				if ( tc.nodeName == "tablecolumn"){
					curcol ++;
					var wdth = tc.getAttribute("width");
					//inches to pixels
					if ( wdth.indexOf("in") > 0 && !(curcol == colcount && widthcount == (colcount - 1))){
						wdth = parseInt(parseFloat(wdth) * 96, 10).toString() + "px";
						str += "<th style='width:" + wdth + ";' class='ui-resizable'></th>";
						widthcount ++;
					}else if ( wdth.indexOf("%") > 0){
						str += "<th style='width:" + wdth + ";' class='ui-resizable'></th>";						
					}else{
						str += "<th class='ui-resizable'></th>"
					}
				}
			}
			str += "</tr>";
			str += "</thead>";
			str += "<tbody>";
		}
		else if ( obj.nodeName == "tablerow" && obj.parentNode.getAttribute("rowdisplay") != "tabs"){
			//-- row in a normal table - not tabbed table
			str += "<tr>";
		}
		else if ( obj.nodeName == "tablecell" && obj.parentNode.parentNode.getAttribute("rowdisplay") != "tabs") {
			//-- table cell in a normal table - not tabbed table
			var colspan = obj.getAttribute("columnspan");
			var colstr = "";
			if (colspan && colspan != "") 
			{
				colstr = " colspan='" + colspan + "'";
			}			
			var style = getObjectStyle(obj);					
			str += "<td " + colstr + "class='selectable' contenteditable='true' style='" + style +"' >";
		}
		else if ( obj.nodeName == "table" && obj.getAttribute("rowdisplay") == "tabs"){			
			//-- tabbed table
			var eIDName = "tabset_" + tabscount;
			str = "<div class='selectable' id='" + eIDName + "' elem='tabset' elemtype='field'>";
			str += "<ul>";
			tabscount ++;
			var parent = obj;
			tabcellindex= 0;
			var tabcellarray = [];
			tabcellarray.length = 0;
			
			var thcode = "<table contenteditable='false' class='bordertop0 borderright0 borderbottom0 borderleft0' style='border-style: solid; border-width: 0px;'><thead style='visibility:visible; line-height:0'><tr>";
	
			var colcount = 0;
			for ( var t=0; t < parent.childNodes.length; t++){
				var tc = parent.childNodes[t];
				if ( tc.nodeName == "tablecolumn"){
					colcount ++;
				}
			}
			var curcol = 0;
			var widthcount = 0;						
	
			for ( var t=0; t < parent.childNodes.length; t++){
				var tc = parent.childNodes[t];
				
				if ( tc.nodeName == "tablecolumn"){
					curcol ++;
					var wdth = tc.getAttribute("width");
					//inches to pixels
					if ( wdth.indexOf("in") > 0 && !(curcol == colcount && widthcount == (colcount - 1))){
						wdth = parseInt(parseFloat(wdth) * 96, 10).toString() + "px";
						thcode += "<th style='width:" + wdth + ";' class='ui-resizable'></th>";
						widthcount ++;
					}else if ( wdth.indexOf("%") > 0){
						thcode += "<th style='width:" + wdth + ";' class='ui-resizable'></th>";						
					}else{
						thcode += "<th class='ui-resizable'></th>"
					}					
				}else if ( tc.nodeName == "tablerow"){
					var rID1 = getRandomID();
					var title = tc.getAttribute("tablabel");
					str += "<li><a href='#" + rID1 + "'>" + title + "</a></li>";
					tabcellarray[tabcellarray.length] = "<div id='" + rID1 + "' style='display: block; padding: 0px;' >" + thcode + "</tr></thead><tbody><tr>";
					for ( var cellind= 0; cellind < tc.childNodes.length; cellind++){
						var cellnode = tc.childNodes[cellind];
						if ( cellnode.nodeName == "tablecell"){
							var cellcontent =  DXLTree(cellnode, options);
							tabcellarray[tabcellarray.length] = "<td class='selectable' contenteditable='true' class='bordertop0 borderright0 borderbottom0 borderleft0' style='border-style: solid; border-width: 0px;'>";
							tabcellarray[tabcellarray.length] = cellcontent;
							tabcellarray[tabcellarray.length] = "</td>";							
						}						
					}
					tabcellarray[tabcellarray.length] = "</tr></tbody></table></div>";
					tabcellindex ++;
				}
			}
			str += "</ul>"
			for ( var x=0; x < tabcellarray.length; x++){
				str += tabcellarray[x];			
			}
			return str;
		}
		else if ( obj.nodeName == "computedtext"){
			if(openPassThru == true){
				str += btoa(encodeURIComponent(passThruHtmlCode));  //--store the encoded pass thru html
				str += "</div>";   //-- close the pass thru html since we don't support computed text in html area
				openPassThru = false;
				passThruHtmlCode = "";
			}			
			str += getElementHtml(obj, options);
		}
		else if ( obj.nodeName == "subformref"){
			str += getElementHtml(obj, options);
		}
		else if ( obj.nodeName == "pardef"){
			var parid = obj.getAttribute ("id");
			pardefArr[parid] = obj; 
		}else if ( obj.nodeName == "section"){
			var titlenode = null;
			var title = "";
			var sectionaccessformula = "";
			var sectionfieldname = "";
			var sectionfieldtype = "";
			var accessfieldformula = "";
			var accessfieldtype = "";
			var hidewhenstr = "";
			var collapsible = true;
			
			var expanded = obj.getAttribute("expanded");
			if(obj.getAttribute("onread") == "expand" || obj.getAttribute("onedit") == "expand"){
				expanded = "";
			}else if(obj.getAttribute("onread") == "collapse" || obj.getAttribute("onedit") == "collapse"){
				expanded = "cacop='1'";
			}else if( expanded && expanded === "false")
				expanded = "cacop='1'"
			else{
				expanded = "";
			}
			
			try{
				sectionfieldname = obj.getAttribute("accessfieldname");
			}catch(e){}
			if(sectionfieldname === null){
				sectionfieldname = "";
			}
			
			try{
				accessfieldtype = obj.getAttribute("accessfieldkind");
				accessfieldformula = _getCode(obj,false, "defaultvalue");
			}catch(e){}
			
			if(typeof accessfieldtype == "undefined" || accessfieldtype === null){
				accessfieldtype = "";
			}
			if(typeof accessfieldformula == "undefined" || accessfieldformula === null){
				accessfieldformula = "";
			}			

			if(accessfieldtype != ""){
				sectionaccessformula = "enablecasection = '1' sectionformula = '" + sectionfieldname + "'"
			}
			
			if(typeof sectionaccessformula == "undefined" || sectionaccessformula === null){
				sectionaccessformula = "";
			}
			
			for ( var j = 0; j < obj.childNodes.length; j++)
			{
				var childnode = obj.childNodes[j];
				if ( childnode.nodeName === "sectiontitle" ){
					titlenode = childnode;
					title = titlenode.textContent || titlenode.text || "";
					hidewhenstr = getObjectHideWhen(titlenode);

					//special case of a collapsible section that is expanded but has a hide when on the title
					//we don't want to keep the hide when as it will apply to the entire section contents
					if(hidewhenstr != "" && hidewhenstr != "hidewhen='' customhidewhen=''" && expanded == ""){			
						//so we discard the hide formula
						hidewhenstr = "hidewhen='' customhidewhen=''";
						//and we disable any expand collapse setting since the title was hidden
						collapsible = false;
					}
					break;
				}				
			}
			
			//close the fields section first
			str += "</div>";
			
			//insert the section
			str += "<div id='section_" + sectioncount + "' sectiontitle = '" + title + "' " + expanded + " " + sectionaccessformula + " expandcollapse='" + (collapsible ? "1" : "0") + "' class='selectable ui-sortable-handle' style='overflow:hidden;' elem='fields' elemtype='section' contenteditable='true'  htmlclass='' htmlother='' additional_style='' style='' fonclick='' fonchange='' fonfocus='' fonblur='' " + hidewhenstr + ">";
			
			//if a controlled access section insert the field in a hidden row
			if(accessfieldtype != ""){
				str += "<p class='selectable' elemtype='par' elem='par' hidewhen='R;E'>";
				
				var dtype = "";
				if(accessfieldtype == "editable"){
					dtype = "e";						
				}else if(accessfieldtype == "computedfordisplay" || accessfieldtype=="computed" || accessfieldtype=="computedwhencomposed"){
					
					if ( accessfieldtype == "computedfordisplay"){
						dtype = "cfd";
					}else if ( accessfieldtype == "computed"){
						dtype = "c";
					}else{
						dtype="cwc";
					}					
				}
				str += "<input id='" + sectionfieldname + "' name='" + sectionfieldname + "' placeholder='" + sectionfieldname + "' class='selectable' ondrop='return false;' style='width: 100px; margin: 5px; font-size: 10px;' elem='text' elemtype='field' texttype='" + dtype + "' fdefault='" + accessfieldformula + "' hidewhen='R;E' customhidewhen='' textrole='names' type=text textmv='true' textmvsep='semicolon' htmlclass='' htmlother='' maxlength='' ftranslate='' additional_style='' fonclick='' fonchange='' fonfocus='' fonblur=''>";										

				str += "</p>";
			}
									
			sectioncount ++;
			
		}else if ( obj.nodeName == "picture"){
			var picwidth = obj.getAttribute("width");
			var picheight = obj.getAttribute("height");
			var scaledwidth = obj.getAttribute("scaledwidth");
			var scaledheight = obj.getAttribute("scaledheight");
			
			if ( scaledwidth != ""){
				picwidth = (parseFloat(scaledwidth) * 96) + "px"
			}	
			if ( scaledheight != ""){
				picheight = (parseFloat(scaledheight) * 96) + "px"
			}
			var picchildnode = obj.firstChild
			//check if this is an action hotspot
			var parentNode = obj.parentNode;
			var formula = "";
			if ( parentNode.nodeName == "actionhotspot"){				
				formula = _getCode(parentNode, true);
			}
			var fonclick = "";
			if ( formula != ""){
				fonclick = "fonclick='" + formula + "' ";
			}
			
			if ( picchildnode.nodeName == "imageref"){
				var style = "style='width:" + picwidth + ";height:" + picheight + "'"
				var imgname = picchildnode.getAttribute("name");
				var captionnode = picchildnode.nextSibling
				var caption = "";
				if ( captionnode !=null && captionnode.nodeName=="caption"){
					caption = captionnode.textContent;
				}
					
				if ( caption != "" ){
					str += "<span class='hotspotimage'>";
				}
				str += '<img class="selectable" id="image_' + imagecount + '" src="' +  "/"+ options.appPath  + "/" + imgname + '" imagename="' + imgname + '" elem="image" elemtype="field" htmlclass="" htmlother="" additional_style=""' + style + ' ' +  fonclick + ' fonchange="" fonfocus="" fonblur="" ' + getObjectHideWhen(obj) + '/>'
				if ( caption != "" ){
					str +=  "<span class='hotspotimagetext'>" + caption + "</span>" 
					str += "</span>"
				}
				imagecount ++;
			}else if ( picchildnode.nodeName.toLowerCase() == "gif" || picchildnode.nodeName.toLowerCase() == "jpeg"){
				//get the base 64 encoded string for the image					
				if (picchildnode.firstChild != null){
					var imgtype = picchildnode.nodeName;
					var base64img = picchildnode.firstChild.nodeValue
					var imagename = "image_" + getRandomID() + "." + picchildnode.nodeName.toLowerCase();
					var captionnode = picchildnode.nextSibling
					var caption = "";
					
					if ( captionnode !=null && captionnode.nodeName=="caption"){
						caption = captionnode.textContent;
					}
					
					base64images.push( { imagename: imagename, imagetype: imgtype, encoding: base64img});
					if ( caption != "" ){
						str += "<span class='hotspotimage'>";
					}
					str += '<img class="selectable" id="image_' + imagecount + '" src="' +  "/"+ options.appPath  + "/" + imagename + '" imagename="' + imagename + '" elem="image" elemtype="field" htmlclass="" htmlother="" additional_style=""' + style + ' ' +  fonclick + ' fonchange="" fonfocus="" fonblur="" ' + getObjectHideWhen(obj) + '/>';
						
					if ( caption != "" ){
						str +=  "<span class='hotspotimagetext'>" + caption + "</span>";
						str += "</span>";
					}
					imagecount ++;
				}
			}
		}else if ( obj.nodeName == "run"){
			if(obj.getAttribute("html") === "true"){
				openPassThru = true;
				var contpassthru = false;
				var prevNode = _previousElementSibling(obj.parentNode);
				if(prevNode){
					if (prevNode.nodeName == "par"){
						if(prevNode.childNodes.length > 0){
							for ( var j = 0; j < prevNode.childNodes.length; j++)
							{
								var childnode = prevNode.childNodes[j];
								if (childnode.nodeType != 3 && childnode.nodeName === "run" && childnode.getAttribute("html") === "true"){
									contpassthru = true;
									break;
								}
							}
						}
					}
				}			
				if(! contpassthru){
					if(openParElem){
						str += "</p>"; //-- close p element since it cannot wrap next div element
						openParElem = false;
					}
					str += "<div id='html_code_" + htmlcodecount + "' class='selectable' elem='htmlcode' " + getObjectHideWhen(obj) + " ondrop='return false;' elemtype='field' contenteditable='false' >";
					passThruHtmlCode = "";
					htmlcodecount++;					
				}					
			}							
		}
		else if ( obj.nodeName == "field"){
			if(openPassThru == true){
				str += btoa(encodeURIComponent(passThruHtmlCode));  //--store the encoded pass thru html
				str += "</div>";   //-- close the pass thru html since we don't support fields in html area
				openPassThru = false;
			}
			str += getElementHtml(obj, options);	
			passThruHtmlCode = "";
		}
		else if ( obj.nodeName == "embeddedview"){
			str += getElementHtml(obj, options);
			return str;
		}		
		else if (obj.nodeName == "button"){
			str += getElementHtml(obj, options);
			return str;
		}
		else if ( obj.nodeName == "embeddedoutline"){
			str += getElementHtml(obj, options);
			return str;
		}
		else if ( obj.nodeName == "par" ){
			str += getElementHtml(obj, options);
		}
	}else{
		str += getElementHtml(obj, options);			
	}
	
	
	//-- recurse through inner content
	var retval = "";		 
	for ( var j = 0; j < obj.childNodes.length; j++)
	{
		child = obj.childNodes[j];
		retval = DXLTree(child, options);
		if ( retval != "" )
			str +=retval;
		
	}
	
	//--closing tags
    if (obj && obj.nodeType != 3 ) 
	{
    	if ( obj.nodeName == "table" &&  obj.getAttribute("rowdisplay") != "tabs"){
			str += "</tbody>";
			str += "</table>";
    	}
		else if ( obj.nodeName == "tablerow" && obj.parentNode.getAttribute("rowdisplay") != "tabs"){
			str += "</tr>";
		}else if ( obj.nodeName == "section"){
			//close off section
			str += "</div>";
			
			//create a new fields section after a controlled access section
			str += '<div id="section_' + sectioncount + '" class="selectable ui-sortable-handle ui-widget" elem="fields" elemtype="section" bc="4">';
			sectioncount++;			
		}else if ( obj.nodeName == "tablecell" && obj.parentNode.parentNode.getAttribute("rowdisplay") != "tabs"){
			str += "</td>";
		}
		else if ( obj.nodeName == "par" ){
			if(!openPassThru){
				if(openParElem){
					str += "</p>";
					openParElem = false;
				}
			}			
		} else if ( obj.nodeName == "run"){
			if(obj.getAttribute("html") === "true"){
				var morepassthru = false;
				var nextNode = _nextElementSibling(obj.parentNode);
				if(nextNode){
					if (nextNode.nodeName == "par"){
						if(nextNode.childNodes.length > 0){
							for ( var j = 0; j < nextNode.childNodes.length; j++)
							{
								var childnode = nextNode.childNodes[j];
								if (childnode.nodeType != 3 && childnode.nodeName === "run" && childnode.getAttribute("html") === "true"){
									morepassthru = true;
									break;
								}
							}
						}
					}
				}			
				if(! morepassthru){
					str += btoa(encodeURIComponent(passThruHtmlCode));  //--store the encoded pass thru html
					str += "</div>";
					openPassThru = false;
					passThruHtmlCode = "";
				}				
			}				
		} 
		else if ( obj.nodeName == "field"){
			var type = obj.getAttribute("type");
			if ( type=="text" || type=="datetime" || type=="number"){
				str += "</input>";
			}
		}	
	}
    
    return str;
}

function _getActionHTML(actionNode, useonclick, doubleescape, containertype){
	
	var rethtml= "";
	var syscommand = actionNode.getAttribute("systemcommand");
	var showinbar = actionNode.getAttribute("showinbar");
	
	if ( showinbar && showinbar == "false") return "";
	
	var hidewhenstr = getObjectHideWhen(actionNode, containertype);
		
	var formula = "";
	if(syscommand && syscommand != ""){
		codetype = "formula";
		if(syscommand == "categorize"){
			formula = "$$Command('[ToolsCategorize]')";
		}else if(syscommand == "edit"){
			formula = "$$Command('[EditDocument]')";
			hidewhenstr = "hidewhen=';E' customhidewhen=''";
		}else if(syscommand == "send"){
			formula = "$$Command('[MailSend]')";
		}else if(syscommand == "forward"){
			formula = "$$Command('[MailForward]')";
		}else if(syscommand == "movetofolder"){
			formula = "$$Command('[MoveToFolder]')";
		}else if(syscommand == "removefromfolder"){
			formula = "$$Command('[RemoveFromFolder]')";
		}else if(syscommand == "openinnewwindow"){
			formula = "$$Command('[OpenInNewWindow]')";
		}else if(syscommand == "print"){
			formula = "$$Command('[FilePrint]')";
		}else if(syscommand == "delete"){
			formula = "$$Command('[Clear]')";
		}
		formula = safe_quotes_js(safe_tags(formula), false);
	}else{
		formula = _getCode(actionNode, true);
	}		
	
	
	if ( doubleescape){
		formula = safe_tags(formula );
	}
	
	var fonclick = "fonclick"
	if ( useonclick)
			fonclick = "onclick"
	
	var title = actionNode.getAttribute("title");
	title = title && title != ""? title  : "Button";
	if(title.indexOf("_") === 0){
		title = title.slice(1);
	}
	
	rethtml += "<button id='button_" + buttoncount + "' contenteditable='false' class='btn selectable ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' " + hidewhenstr + " elem='button' elemtype='field' btnlabel ='" + title.replace(/&/g,'&amp;') + "' " + fonclick + " ='" + formula + "' title='" + title.replace(/&/g,'&amp;amp;') + "'>"
	rethtml += "<span class='ui-button-text'>" + title.replace(/&/g,'&amp;') + "</span>"
	rethtml += "</button>"
	buttoncount ++;
	return rethtml;
}

function getActionHtml(obj,useonclick,doubleescape, containertype)
{
	var codetype = "";
	var rethtml = "";
	for ( var j = 0; j < obj.childNodes.length; j++)
	{
		child = obj.childNodes[j];
		if ( child.nodeName == "actionbar" && !(child.getAttribute("showinbar") === "false" && child.getAttribute("showinmenu") === "false"))
		{
			for ( var p = 0; p < child.childNodes.length; p++){
				var actionNode = child.childNodes[p];
				if ( actionNode.nodeName == "action")
				{
					rethtml +=_getActionHTML (actionNode, useonclick, doubleescape, containertype);
				}else if ( actionNode.nodeName == "sharedactionref"){
					for (var actnindex = 0; actnindex < actionNode.childNodes.length; actnindex ++){
						var sharedActnNode = actionNode.childNodes[actnindex];
						if ( sharedActnNode.nodeName == "action"){
							rethtml += _getActionHTML( sharedActnNode, useonclick, doubleescape, containertype);
						}
					}
				}
			}
			return rethtml;
		}
		
	}
	return rethtml;
}




/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: DXLOutline
 * Generates DOCOVA App Builder outline HTML from Notes database Outline DXL
 * Inputs: obj - XML DOM object containing outline DXL to be parsed
 *         options - value pair object (optional) - additional data to be passed to function
 *                   apppath: string - path to target application
 * Returns: string - html string containing App Builder outlilne
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function DXLOutline(obj, options)
{
	var addall = false;
	var processedviews = [];
	
	var str = "<ul class='OutlineItems' style='list-style-type: none; margin-left: 20px; padding: 0px; border: 0px solid rgb(128, 128, 128); border-radius: 0px;' ostyle='Basic' >";
	var ularry = [];
	ularry.push("<ul>")
	var oldlevel =0;

	for ( var j = 0; j < obj.childNodes.length; j++)
	{
		var skipentry = false;
		
		child = obj.childNodes[j];
		if ( child.nodeName == "outlineentry"){
			var type = child.getAttribute("type");
			var label = child.getAttribute("label");
			var level = child.getAttribute("level");
			var entryType="";
			var entryTypeVal="";
			var emenuitemtype = type == "none"? "emenuitemtype='H'" : "emenuitemtype='M'"
			var headerinfo = "";
			var elementsource = "";
			var targetframe = "";
			var classStr = "class='d-ui-state-default'";
			var liStyle = "style='margin:0px 3px 3px 3px; padding:0.4em; padding-left:1.5em; text-align:left;'";
			var spanClass = "";
			var spanStyle = "style='position: relative; padding-right: 0.5em; padding-left: 0.3em; padding-top: 0px;'";
			var divStyle = "style='font-weight: " + (type == "none" ? "bold" : "normal") + "; display: inline-block; font-style: normal; font-size: 12px;'";
			
			targetframe = (child.getAttribute("targetframe") || "")
			
			//-- try and locate a label formula for this entry
			if(typeof label == "undefined" || label === null || label === ""){
				label = _getCode(child, false, "label");
			}
			
			
			
			//-- check the outline entry type
			if(type == "otherviews" || type == "otherfolders"){
				addall = true;
				skipentry = true;
			}else if(type == "otherprivateviews" || type == "otherprivatefolders"){
				skipentry = true;
			}else if (typeof type === "undefined" || type === null){
				skipentry = true;
			}else if(type === "none"){
				headerinfo = " einitiallyexpanded='0' ";
				classStr = "class='d-ui-widget-header'";
				spanClass = "class='fa fa-caret-down fa-1x expandable' icontitle='fa-caret-down'";				
			}else{				
				//try and find which element it is linked to
				var entryTypeNode = null;	
				for ( var t=0; t< child.childNodes.length; t++){
					var cnode = child.childNodes[t];					
					//-- try and determine the type of link
					if ( cnode.nodeName=="namedelementlink" || cnode.nodeName == "urllink") {
						entryTypeNode = cnode;
						break;
					}else if(cnode.nodeName == "code" && cnode.getAttribute("event") == "value" ){
						for (var f=0; f<cnode.childNodes.length; f++){
							var fnode = cnode.childNodes[f];
							if(fnode.nodeName == "formula"){
								entryTypeNode = fnode;
								break;
							}
						}
						break;
					}
				}				
				
				if ( entryTypeNode &&  entryTypeNode.nodeName == "namedelementlink" ){
					entryType = entryTypeNode.getAttribute("type");
					var sname = entryTypeNode.getAttribute("name");
					entryTypeVal = sname;
					if(entryType == "view"){
						processedviews.push(entryTypeVal);
					}
				}else if ( entryTypeNode && entryTypeNode.nodeName=="urllink"){
					entryType = "url"
					entryTypeVal = entryTypeNode.getAttribute("href");
					entryTypeVal = encodeURIComponent(entryTypeVal);
				}else if ( entryTypeNode && entryTypeNode.nodeName == "formula"){
					entryType = "js";
					formula = _getCode( cnode, true);
					entryTypeVal = safe_quotes_js(safe_tags(formula), false);
				}else if ( entryTypeNode && entryTypeNode.nodeName == "lotusscript"){
					entryType = "js";
					formula = _getCode( cnode, true);
					entryTypeVal = safe_quotes_js(safe_tags(formula), false);
				}
			}
			
			if(!skipentry){
				if ( entryType != ""){
					elementsource = " etype='" + entryType + "' eelement='" + entryTypeVal + "' eviewtype='' ";
				}
			
				if ( level && (parseInt(level) > oldlevel)) {
					str += "<ul>";
					ularry.push("<ul>");				
				}else if ( level && (parseInt(level) < oldlevel )){
					var diff = oldlevel - level;
					for ( var ind = 0; ind < diff; ind ++){
						str += "</ul>";
						ularry.pop();
					}
				}
				
				if ( level && level != ""){
					oldlevel = parseInt(level);
				}
				
				var hidewhen = getObjectHideWhen(child);
			
				str += "<li " + hidewhen + " " +  headerinfo + " " + liStyle + " " +  emenuitemtype + elementsource + " etarget='" + targetframe + "' " + classStr + "><span " + spanClass + " " + spanStyle + "></span><div class='itemlabel' " + divStyle + ">" + label + "</div></li>";		
			}
		}		
	}
	
	if(addall){
		str += getDefaultOutlineHtml({'fragmentonly' : true, 'apppath' : (typeof options !== "undefined" && typeof options.apppath !== "undefined" ? options.apppath : ""), 'excludedviews' : processedviews})	
	}
	
	for ( var p=0; p < ularry.length; p++){
		str += "</ul>";
	}
	
    return str;
}

function DXLView (obj, coltypes)
{
	var persXml = "";
	var viewname = "";
	var viewalias = "";
	var showresponsesinhierarchy = "0";
	var viewselectionformula = "";
	var autocollapse = "";
	var index = 0;
	var hiddenIndex = 0;
	var cfIndex = 0;
	var countcolspan = false;
	var colspancount = 0;
	var respFormula = "";
	var isresp = false;
	var isfolder = false;
	var isprivate = false;
	var toolbarcode = "";
	if ( obj.nodeName == "view" || obj.nodeName == "folder"){
		viewname = $.trim(obj.getAttribute("name"));
		viewalias = $.trim(obj.getAttribute("alias"));
		var showresphier = obj.getAttribute("showresponsehierarchy")
		if ( showresphier && showresphier == "true"){
			showresponsesinhierarchy = "1";
		}
		autocollapse = obj.getAttribute("opencollapsed");
		persXml += '<viewperspective>';
		if(autocollapse == "true"){
			persXml += '<autocollapse>1</autocollapse>';
		}
		persXml += '<viewsettings><viewproperties><type>system</type><id>system_default_folder</id><Unid/><name>Built-in perspective</name><description/><createdby/><createddate/><modifiedby/><modifieddate/><autocollapse>0</autocollapse><responseColspan></responseColspan><isSummary/><showSelectionMargin>1</showSelectionMargin><allowCustomization>1</allowCustomization><extendLastColumn/><categoryBorderStyle>border-bottom : solid 2px #aaccff;</categoryBorderStyle></viewproperties><columns>'			
	}
	if ( obj.nodeName == "folder"){
		isfolder = true;
		var privateflag = obj.getAttribute("privatefirstuse");
		if(privateflag && privateflag == "true"){
			isprivate = true;
		}
	}
	
	
	var childNode;
	
	for ( var p = 0; p < obj.childNodes.length;p++)
	{
		childNode = obj.childNodes[p];
		
		//-- if this is a shared column jump down to get the actual column
		if(childNode.nodeName == "sharedcolumnref"){
			for(var n=0; n<childNode.childNodes.length; n++){
				if(childNode.childNodes[n].nodeName === "column"){
					childNode = childNode.childNodes[n];
					break;
				}
			}
		}
		
		if ( childNode.nodeName == "actionbar"){
			toolbarcode = getActionHtml (childNode.parentNode, true, true, "view");
		}else if ( childNode.nodeName == "code"){
			var evt = childNode.getAttribute("event");
			if ( evt == "selection"){
				for(var s=0; s<childNode.childNodes.length; s++){
					var selectformulanode = childNode.childNodes[s];
					if ( selectformulanode.nodeName == "formula"){
						viewselectionformula = selectformulanode.textContent || selectformulanode.text || "";
						break;
					}
				}
			}
		}else if ( childNode.nodeName == "column"){
			var colheadernode = null;
			var codenode = null;
			var datatype = "text"
			var columntitle = "";
			var codetxt = "";
			var colformula = "";
			var showmulti = childNode.getAttribute("separatemultiplevalues") == "true" ? "1" : "";
			var ishidden = childNode.getAttribute("hidden") == "true"? "1" : "";
			var totaltype = childNode.getAttribute("totals");
			if(!totaltype){ totaltype = ""};
			var sortorder = childNode.getAttribute("sort")
			if ( !sortorder || sortorder == "") sortorder = "none"
			var iscategorized = childNode.getAttribute("categorized") == "true" ? "1" : "";
			var width = childNode.getAttribute("width");
			width = parseInt(width)*10;
			var isResponseColumn = childNode.getAttribute("responsesonly") == "true" ? true : false;
			
			for ( var j = 0; j < childNode.childNodes.length; j++)
			{
				child = childNode.childNodes[j];
				if ( child.nodeName == "columnheader"){
					colheadernode = child;
				}else if ( child.nodeName == "code" && child.getAttribute("event") == "value"){
					codenode = child;
				}else if ( child.nodeName == "numberformat"){
					//datatype = "number"
				}else if ( child.nodeName == "datetimeformat"){
					//datatype = "datetime"
				}
			}
			if (colheadernode)
			{
				columntitle = colheadernode.getAttribute("title");
			}
		
			if ( codenode ){
				var formulanode = codenode.firstChild;
				if ( formulanode.nodeName == "formula"){
					colformula = formulanode.textContent || formulanode.text || "";
				}
			}
		
			if ( colformula == "")
				colformula = childNode.getAttribute("itemname")
				
			columntitle = columntitle ? columntitle :  "";
			columntitle = columntitle.replace("'", "").replace("\"", "").replace("&", "").replace("<", "").replace(">", "");
			colformula = colformula ? colformula : "";
			
			if(coltypes && Array.isArray(coltypes) && cfIndex < coltypes.length){
				datatype = coltypes[cfIndex];
			}
			
			if(totaltype == "total"){
				//if totaling is enabled this must be a numeric column
				datatype = "number";
			}
			
			var align = "left";
			if(datatype == "number"){
				align = "right";
			}			
			
			if ( !isResponseColumn ){
				var nodeName = "";
				nodeName = "CF" + cfIndex;
				cfIndex++;
				
				persXml += '<column><title><![CDATA[' + columntitle + ']]></title>'
				persXml += '<columnFormula><![CDATA[' + colformula + ']]></columnFormula>'
				persXml += '<xmlNodeName>' + nodeName + '</xmlNodeName>'
				persXml += '<width>' + width + '</width>';
				persXml += '<align>' + align + '</align>';
				persXml += '<alignT>' + (totaltype == "total" ? align : "") + '</alignT>';
				persXml += '<showResponses>' + ( isresp ? '1' : '' ) + '</showResponses>';
				persXml += '<responseFormula>' + ( isresp && respFormula != "" ? '<![CDATA[' + respFormula + ']]>' : '') + '</responseFormula>';
				persXml += '<showMultiAsSeparate>' + showmulti + '</showMultiAsSeparate>';
				persXml += '<isHidden>' + ishidden + '</isHidden>';
				persXml += '<dataType>' + datatype + '</dataType>';
				persXml += '<sortOrder>' + sortorder + '</sortOrder>';
				persXml += '<isCategorized>' + iscategorized + '</isCategorized>';
				persXml += '<totalType>' + (totaltype == "total" ? "1" : "0") + '</totalType>';
				persXml += '</column>';
				index ++;
				isresp = false;
				respFormula = "";
			}else{
				isresp = true;
				respFormula = colformula;
				countcolspan = true;
			}
			
			if(countcolspan){
				colspancount ++;
			}
		}
	
	}
	persXml += "</columns></viewsettings></viewperspective>"
	
	var obj = {
            "viewname":  viewname,
            "viewalias" : viewalias,
            "perspectiveXml" : persXml,
            "showresponsesinhierarchy": showresponsesinhierarchy,
            "responsecolspan" : colspancount.toString(),
            "titlenode": "CF0",
            "viewformula": viewselectionformula,
            "toolbar" : toolbarcode,
            "emulatefolder" : (isfolder ? "1" : ""),
            "privateonfirstuse" : (isprivate ? "1" : ""),
            "autocollapse" : (autocollapse == "true" ? "1" : "")
       }
	return obj;
}

function DXLFrameset(obj)
{
	var str = "";
	var content = "";
	var child;
	
	
	if ( obj.nodeName == "frameset")
	{
		var name = obj.getAttribute("name");
		var rows = obj.getAttribute("rows");
		var columns = obj.getAttribute("columns");
		var framespacing = obj.getAttribute("spacing");
		var color = obj.getAttribute("bordercolor");
		if ( ! name || name == "" ){
			name = "frameset_" + framesetcount
			framesetcount ++;
		}
		
		
		var layoutstr = "";
		var tmparray;
		if ( rows && rows != ""){
			tmparray = rows.split(" ");
			for(var i=0; i<tmparray.length; i++){
				if(tmparray[i].indexOf("%") == -1 && tmparray[i].indexOf("px") == -1){
					tmparray[i] = tmparray[i] + "%";
				}
			}
			layoutstr += " rows='" + tmparray.join() + "'"; 			
		}
		if ( columns && columns != ""){
			tmparray = columns.split(" ");
			for(var i=0; i<tmparray.length; i++){
				if(tmparray[i].indexOf("%") == -1 && tmparray[i].indexOf("px") == -1){
					tmparray[i] = tmparray[i] + "%";
				}
			}			
			layoutstr += " cols='" + tmparray.join() + "'";			
		}
			
		str += "<frameset id='" + name + "' name='" + name + "' " +  layoutstr + ">"
		
	}else if ( obj.nodeName == "frame")
	{
		var name = obj.getAttribute("name");
		var noresize = obj.getAttribute("noresize")
		var scrolling = obj.getAttribute("scrolling")
		var targetFrame = obj.getAttribute("targetframe")
		var contentypestr = "";
		var contentstr = "";
		var eurlstr = "";
		var openintab = "";
		if ( ! name || name == "" ){
			name = "frame_" + framecount
			framecount ++;
		}
		
		targetFrame = targetFrame && targetFrame != "" ? "target='" + targetFrame + "'" : "";
		
		noresize = noresize && noresize == "true"? "dnoresize='1' noresize='1'" : "dnoresize=''"
		scrolling = scrolling && scrolling == "never" ? "dnoscroll='1' scrolling='no'" : "dnoscroll=''"
		
			
		var namedelemnode = null;	
		for ( var t=0; t< obj.childNodes.length; t++){
			var cnode = obj.childNodes[t];
			if ( cnode.nodeName=="namedelementlink") {
				namedelemnode = cnode;
			}
		}
		
		if ( namedelemnode && namedelemnode.nodeName == "namedelementlink"){
			var stype = namedelemnode.getAttribute("type");
			var sname = namedelemnode.getAttribute("name");
			if ( stype && stype == "view"){
				contentypestr = "contenttype='View'";
				content = "content='" + sname + "'";
				openintab = "openintab='1'";
				var relurl = "wViewFrame?readForm&title=" + sname + "&surl=" + "AppViewsAll/" + sname + "?OpenDocument";
				eurlstr = "dsrc='" + relurl + "' src='" + relurl + "'";
			}else if ( stype && stype == "page"){
				contentypestr =  "contenttype='Page'";
				content = "content='" + sname + "'";
				var relurl = sname + "?OpenPage";
				eurlstr = "dsrc='" + relurl + "' src='" + relurl + "'";
			}else if ( stype && stype == "form"){
				contentypestr =  "contenttype='Form'";
				content = "content='" + sname + "'";
				var relurl = sname + "?OpenForm";
				eurlstr = "dsrc='" + relurl + "' src='" + relurl + "'";
			}else if ( stype && stype == "outline"){
				contentypestr =  "contenttype='Outline'";
				content = "content='" + sname + "'";
				var relurl = "luOutline/" + sname + "?openDocument";
				eurlstr = "dsrc='" + relurl + "' src='" + relurl + "'";				
			}else if ( stype && stype == "frameset"){
				contentypestr = "contenttype='Layout'";
				content = "content='" + sname + "'";
				var relurl = "LayoutLoader?readForm&amp;LayoutName=" + sname;
				eurlstr = "dsrc='" + relurl + "' src='" + relurl + "'";
			}
			
		}
		
		str += "<frame id='" + name + "' name='" + name + "' " + noresize + " " + targetFrame + " " + content + " " + scrolling + " " + contentypestr + " " + eurlstr + " " + openintab + ">"
		
	}
		
	
	
	var retval = "";	
	 
	for ( var j = 0; j < obj.childNodes.length; j++)
	{
		child = obj.childNodes[j];
		retval = DXLFrameset(child);
		if ( retval != "" )
			str +=retval;
		
	}
	
	if ( obj.nodeName == "frameset")
	{
		str += "</frameset>"
	}else if ( obj.nodeName == "frame")
	{
		str += "</frame>"
	}
	
	return str;
}

function setTableRowHideWhens(initialhtml){
	var result = "";
	
	var tempdiv$ = jQuery('<div id="tempdiv"></div>').html(initialhtml);
	
/*	
	//remove any empty sections...these will happen if a controlled access section is there on the form
	tempdiv$.find("div[elem='fields'][elemtype='section']").each ( function(){
		var hasdata = false;
		$(this).find("td").each ( function() {
			if ( $(this).children().length > 0 ) {
				hasdata = true;
				return;
			}
		});
		if ( !hasdata )
			$(this).remove();
	});
*/

	//-- loop through each table
	tempdiv$.find("table").each(function(){
		var tableobj$ = jQuery(this);
		//-- loop through each row in the table
		tableobj$.find("tbody:first").children("tr").each(function(){
			var last_hw_base = null;
			var check_base = true;
			var last_hw_cust = null;
			var check_cust = true;
			
			var rowobj$ = jQuery(this);
			//-- loop through each td in the row
			rowobj$.children("td").each(function(){
				var tdobj$ = jQuery(this);
				//-- loop through each element in the td
				tdobj$.children().each(function(){
					var tdchild$ = jQuery(this);
					
					var temp_hw_base = "";					
					if(check_base){
						if(tdchild$[0].hasAttribute("hidewhen") && tdchild$.attr("hidewhen") != ""){
							temp_hw_base = tdchild$.attr("hidewhen") || "";
						}
						if(last_hw_base !== null && last_hw_base !== temp_hw_base){
							//-- if different we need to clear previous values 
							last_hw_base = "";
							//--  and flag to stop checking this hide when type								
							check_base = false;
						}else{
							last_hw_base = temp_hw_base;
						}
					}
					
					var temp_hw_cust = "";															
					if(check_cust){
						if(tdchild$[0].hasAttribute("customhidewhen") && tdchild$.attr("customhidewhen") != ""){
							temp_hw_cust = tdchild$.attr("customhidewhen") || "";
						}
						if(last_hw_cust !== null && last_hw_cust !== temp_hw_cust){
							//-- if different we need to clear previous values
							last_hw_cust = "";
							//--  and flag to stop checking this hide when type
							check_cust = false;
						}else{
							last_hw_cust = temp_hw_cust;
						}
					}
					
					//--exit early if both hide when types are different
					if(!check_base && !check_cust){
						return;
					}
					
				});
				
				//--exit early if both hide when types are different
				if(!check_base && !check_cust){
					return;
				}
			});
			if(last_hw_base !== null && last_hw_base != ""){
				rowobj$.attr("hidewhen", last_hw_base);
			}
			if(last_hw_cust !== null && last_hw_cust != ""){
				rowobj$.attr("customhidewhen", last_hw_cust);
			}	
		});
	});
	result = tempdiv$.html();
    tempdiv$.remove();
    
	return result;
}

//check to unsure this is a design element we can update
function checkDesignElementEx(type, elementname, appPath, detailedFeedback){
	var result;
	
	var request = "<Request><Action>CHECKDESIGNELEMENT</Action>"
	request += "<Document>"
	request += "<ElementType><![CDATA[" + type + "]]></ElementType>"	
	request += "<ElementName><![CDATA[" + elementname + "]]></ElementName>"
	request += "<AppPath>" + appPath + "</AppPath>"
	request += "</Document>"
	request += "</Request>"
	
	result = SubmitRequest(encodeURIComponent(request), "DesignServices", detailedFeedback)	
	
	return result
	
}

function getDXL(obj, objtype, target, options){
	var formhtml = "";
	var request = "";
	if ( objtype == "Form"){
		request += '<div id="workflowsection" elem="workflow" elemtype="section" style="display: none;" initialstatus_prop="Draft" finalstatus_prop="Released" supersededstatus_prop="Superceded" discardedstatus_prop="Discarded" archivedstatus_prop="Archived" deletedstatus_prop="Deleted" enablelifecycle_prop="0" class="ui-sortable-handle" enableversions_prop="" restrictlivedrafts_prop="" strictversioning_prop="" allowretract_prop="" restrictdrafts_prop="" updatebookmarks_prop="" attachmentoptions_prop="0" showheaders_prop="" workflowlist_prop="" hideworkflow_prop="" disabledeleteinworkflow_prop="" wfcustomstartbuttonlabel_prop="" wfcustomreleasebuttonlabel_prop="" wfhidebuttons_prop="" wfcustombuttonshidewhen_prop="" wfcustomreviewbuttonlabel_prop=""></div>';		
		request += '<div id="pageProperties" class="ui-sortable-handle" style="display:none;" leftMargin="4.0" rightMargin="4.0" topMargin="2.0" bottomMargin="2.0"></div>';		
	}
	request += '<div id="divToolBar" style="min-height:25px; border: 1px solid #cfcfcf;margin-bottom:5px;" ondrop="actionbuttondropped()" contenteditable="true" class="ui-sortable-handle">';
	request += getActionHtml(obj, false, true);
	request += '</div>';
	request += '<div id="section_1" class="selectable ui-sortable-handle" style="overflow:hidden;" elem="fields" elemtype="section" contenteditable="true" >';
	request += DXLTree(obj, {
		'appPath': target, 
		'largetextfields': (options && options.largetextfields ? options.largetextfields : null)
	});
	if(options && options.includeattachments){
		if(attachmentcount < 1){
			request += getAttachmentHtml("DocovaDoc");
		}
	}
	request += '</div>';	
	request += '<div id="designversion" style="display:none;">' + designversion.toString() + '</div>';

	formhtml = setTableRowHideWhens(request);
	return formhtml;
}

function updateHTML(dehtml, elemtype)
{

	
	dehtml = "<div id='tmpwrapper'>" + $.trim(dehtml) + "</div>";
	var deobj = $(dehtml);

	var secarray = [];
	deobj.find("[elemtype='section']").each(function(index)
	{  


		if ( $(this).attr("id") != "workflowsection"){
			
	    	secarray.push($(this));
	    	$(this).remove();
	    }
	});
  
  	var targetNode = deobj.find("#divToolBar");

  	
  
  	var newnode = $("<div class='grid-stack'><div class='grid-stack-item selectable' elem='panel' data-gs-x='0' data-gs-y='0' data-gs-width='12' data-gs-height='40'><div class='grid-stack-item-content'></div></div></div>").insertAfter(targetNode);
 	
  	for (p = 0;p < secarray.length; p++){
  		deobj.find(".grid-stack-item-content").append(secarray[p]);
  	}

  	deobj.find(".grid-stack-item-content").append($('<i title="Move Panel" class="dhgrip fas fa-grip-horizontal"></i><i title="Delete Panel" class="rmpanel far fa-times"></i></div>'));


  	var prop = deobj.find("#pageProperties");
  	if ( prop.length > 0){
  			prop.attr("leftmargin", "2");
			prop.attr("rightmargin", "2");
			prop.attr("toptmargin", "2");
			prop.attr("bottomtmargin", "2");

			
  	}




	//imbedded outlines
	deobj.find("iframe").each(function (){
		var elm = $(this).attr("elem");
		if ( elm && elm == "outline"){
			var olname = $(this).attr('outlinename');
			var newsrc = "wViewOutline/"+olname+"?AppID="+docInfo.AppID;
			$(this).attr("src", newsrc);
			$(this).attr("osrc", "wViewOutline/"+olname);
			
		}
	});

	//img tags
	deobj.find("[elem=image]").each(function (){
		var name = $(this).attr("imagename");
		var newsrc = "/Symfony/web/bundles/docova/images/" + docInfo.AppID + "/" + name;
		$(this).attr("src", newsrc);
		
	});


	dehtml = $(deobj[0]).html();
	return dehtml;

}

function jsonTraverser(outlineJson){
   var menu_items = outlineJson.Items;
   for (var item = 0; item < menu_items.length; item++)
	{
		var hidewhen = menu_items[item].hidewhen;
		if ( hidewhen && hidewhen != ""){
				menu_items[item].hidewhen = menu_items[item].hidewhen.replace(/@/g, "$$$$");
		}

		menu_items[item].expandicon = "fas fa-caret-down";
		menu_items[item].collapseicon = "fas fa-caret-right";
	
		if (menu_items[item].Items && menu_items[item].Items.length) {
			jsonTraverser(menu_items[item]);
			
		}
		
	}
}

/* helper function to change the @ formulas in menu hide/whens to $$ */
function fixOutlineJSON ( injson)
{
	if ( !injson || injson == "" ) return "";

	var jsonobj =  JSON.parse(injson);

	jsonTraverser(jsonobj);
	return JSON.stringify(jsonobj);
}

function ImportElement( server, path, type, name, targetpath, silent, noviewrefresh, targettype, options)
{
	var result = false;

	base64images = [];
	base64images.length = 0;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}	
	
	openPassThru = false;
	passThruHtmlCode = "";
	
	var elemtype = type.toLowerCase();
	
	if(elemtype == "lotusscript library" || elemtype == "scriptlibrary"){
		elemtype = "lslibrary";
	}else if(elemtype == "javascript library" || elemtype == "javascriptlibrary"){
		elemtype = "jslibrary";
	}
	
	var namesuffix = "";
	var targettype = (typeof targettype === 'undefined' ? "" : targettype.toLowerCase());
	if(targettype == "jslibrary" || targettype == "javascript library" || targettype == "javascriptlibrary"){
		targettype = "jslibrary";
		namesuffix = "JS";
	}else{
		targettype = "";
	}	
	
	
	var action = (importFromDocova ? "GETELEMENTHTML" : "GETDXL");

	
	var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<server>" + server + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemtype>" + elemtype + "</elemtype>";
	request += "<elemname><![CDATA[" + name + "]]></elemname>";
	request += "<direct>" + (importFromDocova ? "" : "1") + "</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	//httpObj.returnxml = true;


	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {return false; }

	if ( httpObj.results[0] )
	{
		var dxl = httpObj.results[0];
		if (importFromDocova) {
			var find = '&lt;';
			var re = new RegExp(find, 'g');

			dxl = dxl.replace(re, "<");

			find = "&gt;";
			re = new RegExp(find, 'g');
			dxl = dxl.replace(re, ">");
			
		}
		
		var xmlDoc = (new DOMParser()).parseFromString(dxl, "text/xml");
		if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
	  		var errorText = Sarissa.getParseErrorText(xmlDoc);
			alert("Error loading element DXL: " + errorText);
			xmlDoc = null;
			return false;
		}

		if ( importFromDocova){
			xmlDoc = xmlDoc.documentElement
			var tmpnode = xmlDoc.selectSingleNode("DENAME");
			var dename = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEALIAS");
			var dealias = tmpnode && (tmpnode.textContent || tmpnode.text ) ? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEALIAS");
			var dealias = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEHTML");
			var dehtml = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEADDCSS");
			var deaddcss = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEADDJS");
			var deaddjs = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEADDJS");
			var deaddjs = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DECSS");
			var decss = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DECODE");
			var decode = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			decode = decodeURIComponent(decode);
			var dehead = decode;
			tmpnode = xmlDoc.selectSingleNode("DESUBTYPE");
			var desubtype = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEPROPERTIES");
			var deproperties = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			if ( elemtype == 'outline'){
				deproperties = decodeURIComponent(deproperties);
			}
		}else{
			var dename = xmlDoc.firstChild.getAttribute("name");
			var dealias = xmlDoc.firstChild.getAttribute("alias");
			var deproperties = getPropertiesHtml(xmlDoc.firstChild, (targettype !== "" ? targettype : elemtype));
			var decode =  btoa(encodeURIComponent(getJSHeaderHtml(xmlDoc.firstChild)));
			
			//-- check for shared fields and try to replace them with the real field content
			if(elemtype == "form" || elemtype == "subform"){
				var sharedFieldNodes =  xmlDoc.getElementsByTagName("sharedfieldref");
				if(typeof sharedFieldNodes != "undefined" && sharedFieldNodes !== null && sharedFieldNodes.length > 0){
					for(var s=sharedFieldNodes.length-1; s>-1; s--){
						var sharedFieldNode = sharedFieldNodes[s];
						var fieldname = sharedFieldNode.getAttribute("name");
		
						var sfrequest="";
						sfrequest += "<Request>";
						sfrequest += "<Action>GETDXL</Action>";
						sfrequest += "<server>" + server + "</server>";
						sfrequest += "<path>" + path + "</path>";
						sfrequest += "<elemtype>sharedfield</elemtype>";
						sfrequest += "<elemname><![CDATA[" + fieldname + "]]></elemname>";
						sfrequest += "<direct>1</direct>";
						sfrequest += "</Request>";	
						
						var httpObjSF = new objHTTP();
						httpObjSF.supressWarnings = true;
						if(httpObjSF.PostData(encodeURIComponent(sfrequest), url) && httpObjSF.status != "FAILED") {
							if ( httpObjSF.results[0] )
							{
								var sfDxl = httpObjSF.results[0];
								
								var sfXmlDoc = (new DOMParser()).parseFromString(sfDxl, "text/xml");
								if(Sarissa.getParseErrorText(sfXmlDoc) == Sarissa.PARSED_OK){  
							  		var fNodes = sfXmlDoc.getElementsByTagName("field");
							  		if(typeof fNodes != "undefined" && fNodes !== null && fNodes.length > 0){
							  			var newFnode = xmlDoc.importNode(fNodes[0],true);
							  			var parentNode = sharedFieldNode.parentNode;
							  			parentNode.replaceChild(newFnode, sharedFieldNode);
							  		}
								}
							}
						}					
					}
				}
			}			
		}

		var elemname = dename;
		if ( !elemname || elemname == "" ){ return;}

		if ( elemtype == "agent"){
			elemname = elemname.replace(/\(/g, "HDN");
			elemname = elemname.replace(/\)/g, "");
		}
		if ( elemname.indexOf ("\\") > -1){
			elemname = elemname.replace(/\\/g, "-");
		}

		var elemalias = dealias;
		if ( !elemalias){ elemalias = "";}		
		
		var pre = "";	

		request = "<Request>";
		request += "<Action>SAVEDESIGNELEMENTHTML</Action>";				
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<Document>";
		request += "<Name><![CDATA[" + elemname + namesuffix + "]]></Name>";
		request += "<Alias><![CDATA[" + elemalias + (elemalias !== "" ? namesuffix : "") + "]]></Alias>";
		request += "<Unid></Unid>";
		request += "<AppID><![CDATA[" + (docInfo.AppID ? docInfo.AppID : docInfo.DocKey) + "]]></AppID>";
		request += "<AddCSS><![CDATA[" + deaddcss + "]]></AddCSS>";
		request += "<AddJS><![CDATA[" + deaddjs + "]]></AddJS>";
		if ( elemtype == 'outline'){
			request += "<Properties>" + deproperties + "</Properties>";
		}else{
			request += "<Properties><![CDATA[" + deproperties + "]]></Properties>";
		}
		request += "<ProhibitDesignUpdate></ProhibitDesignUpdate>";
		
		if(elemtype == "form"){
			//--check if form has attachments
			var dxloptions = {includeattachments: false};
			if ( importFromDocova){
				dehtml = parseFormulaCode ( dehtml);
				dehtml = updateHTML(dehtml, elemtype);
			}else{
				//--check if form has attachments				
				var forminfo = GetDesignElementInfo( server, path, elemtype, elemname, elemalias);
				if(forminfo){
					if(jQuery(forminfo).find("hasattachments").text() == "1"){
						dxloptions.includeattachments = true;
					}
					var ltextfields = jQuery(forminfo).find("largetextfields").text();
					if(ltextfields){
						dxloptions.largetextfields = ltextfields.slice(",");
					}
				}	
				
				dehtml = getDXL(xmlDoc.firstChild, "Form", targetpath, dxloptions);
				dehtml = parseFormulaCode ( dehtml, true);
				dehtml = updateHTML(dehtml, elemtype);	
			}
			
			if (decode) {
				dehead = decode;
				var jscode = decodeURIComponent(atob(decode));
				var eventcode = '';
				var eventlist = ["Initialize","Queryopen","Postopen","Querymodechange","Queryrecalc","Postrecalc","Querysave","Postsave","Queryclose","Terminate"];
				for(var x=0; x<eventlist.length; x++){
					var searchfor = "function " + eventlist[x] + "(";
					var funcName = dename.replace(/\/|\\/g, '').replace(/\-/g, '').replace(/\s+/g, '');
					var replacewith = "function " + eventlist[x] + "_" + funcName + "(";
					if(jscode.indexOf(searchfor)>-1){
						var re = new RegExp(searchfor.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"),"g");
						jscode = jscode.replace(re, replacewith);		
						eventcode += '\r\n' + 'FormEventList.' + eventlist[x].toLowerCase() + '.push("' + eventlist[x] + '_' + funcName + '");' 		
					}		
				}
				eventcode += '\r\n';

				decode = btoa(encodeURIComponent(eventcode + jscode));
			}

			request += "<Type><![CDATA[Form]]></Type>";				
			request += "<DoMobile>" + "0" + "</DoMobile>";
			request += "<HTML><![CDATA[";
			request += dehtml;
			request += "]]></HTML>";			
			request += "<HeadCode><![CDATA[";
			request += dehead
			request += "]]></HeadCode>";	
			request += "<Code><![CDATA[";
			request += decode
			request += "]]></Code>";	
		}else if(elemtype == "subform"){		
			if (decode) {
				dehead = decode;
				var jscode = decodeURIComponent(atob(decode));
				var eventcode = '';
				var eventlist = ["Initialize","Queryopen","Postopen","Querymodechange","Queryrecalc","Postrecalc","Querysave","Postsave","Queryclose","Terminate"];
				for(var x=0; x<eventlist.length; x++){
					var searchfor = "function " + eventlist[x] + "(";
					var funcName = dename.replace(/\/|\\/g, '').replace(/\-/g, '').replace(/\s+/g, '');
					var replacewith = "function " + eventlist[x] + "_" + funcName + "(";
					if(jscode.indexOf(searchfor)>-1){
						var re = new RegExp(searchfor.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"),"g");
						jscode = jscode.replace(re, replacewith);		
						eventcode += '\r\n' + 'FormEventList.' + eventlist[x].toLowerCase() + '.push("' + eventlist[x] + '_' + funcName + '");' 		
					}		
				}
				eventcode += '\r\n';

				decode = btoa(encodeURIComponent(eventcode + jscode));
			}

			if(importFromDocova){
				dehtml = parseFormulaCode ( dehtml);
			}else{
				dehtml = getDXL(xmlDoc.firstChild, "Subform", targetpath);
				dehtml = parseFormulaCode ( dehtml, true);
			}
			
			request += "<Type><![CDATA[Subform]]></Type>";		
			request += "<HTML><![CDATA[";			
			request += dehtml;
			request += "]]></HTML>";			
			request += "<HeadCode><![CDATA[";
			request += dehead
			request += "]]></HeadCode>";	
			request += "<Code><![CDATA[";
			request += decode;
			request += "]]></Code>";												
		}else if(elemtype == "page"){
			if(importFromDocova){
				dehtml = parseFormulaCode ( dehtml);
				dehtml = updateHTML(dehtml, elemtype);
			}else{
				dehtml = getDXL(xmlDoc.firstChild, "Page", targetpath);
				dehtml = parseFormulaCode ( dehtml, true);
				dehtml = updateHTML(dehtml, elemtype);
			}
			
			var pagecssjson = '{"FormBackgroundColor":"#ffffff","PanelsHorizontalSpacing":"0","PanelsVerticalSpacing":"0","PanelsPadding":"0","PanelsBackgroundColor":"#ffffff","PanelsShadow":"0","PanelsBorderStyle":"none","PanelsBorderColor":"#d3d3d3","PanelsBorderSize":"1"}';
			var pagecss = pagecssjson+"!!----!!#layout, #inner-center, #divDocPage { background : #ffffff; }.grid-stack > .grid-stack-item > .grid-stack-item-content  { background-color : #ffffff ;box-shadow: none; border:1px none #d3d3d3;left:0px !important;right:0px !important;padding:0px !important;";
			
			request += "<CSS><![CDATA[";
			request += pagecss;
			request += "]]></CSS>";	
			request += "<Type><![CDATA[Page]]></Type>";		
			request += "<HTML><![CDATA[";			
			request += dehtml;
			request += "]]></HTML>";			
			request += "<Code><![CDATA[";
			request += btoa(encodeURIComponent(getJSHeaderHtml(xmlDoc.firstChild)));
			request += "]]></Code>";
		}else if ( elemtype == "outline"){
			if(importFromDocova){
				dehtml = parseFormulaCode ( dehtml);
			}else{
				dehtml = getDXL(xmlDoc.firstChild, "Form", targetpath);
				dehtml = parseFormulaCode ( dehtml, true);
			}
			
			request += "<Type><![CDATA[Outline]]></Type>";				
			request += "<CSS><![CDATA["+ decss + "]]></CSS>";				
			request += "<HTML><![CDATA[";
			request += dehtml
			request += "]]></HTML>";		
			decode = fixOutlineJSON(decode);
			request += "<Code><![CDATA[";
			request += decode.replace(/\/\/|\\\\/g, '-');
			request += "]]></Code>";				
		}else if(elemtype == "agent"){
			request += "<Type><![CDATA[Agent]]></Type>";		
			request += "<SubType>LotusScript</SubType>";

			decode = $.trim(decode);
			
			//var libpath = '__DIR__."/../../ScriptLibraries/A' + docInfo.AppID.replace(/-/g, "") + "/php/" ;
			var libpath = "ScriptLibraries/"


			var LexerObj = new LexerPHP(libpath);
			var unenc = atob(decode);
			//replace windows CRLF with LF on UNIX
			unenc = unenc.replace(/%0D%0A?/g, '%0A');
			try{
				unenc = decodeURIComponent(unenc);
				var outputTxt = LexerObj.convertCode(unenc, "PHP");
				request += "<usercode><![CDATA["+ btoa(encodeURIComponent(outputTxt)) +"]]></usercode>";

			
				var editorCode = fetchCodeSections(outputTxt);
				dsnHead = btoa(encodeURIComponent(editorCode.header));
				dsnInit = btoa(encodeURIComponent(editorCode.init));
				dsnCode = btoa(encodeURIComponent(editorCode.body));
				dsnEnd = btoa(encodeURIComponent(editorCode.end));
			}catch ( Exception){
				if(silent !== true){
					alert ( "Error importing " + elemname + "\n" + Exception);
				}
				return false;
			}
			request += "<InitCode><![CDATA[" + dsnInit + "]]></InitCode>";

			request += "<Code><![CDATA[";
			request += (importFromDocova ? dsnCode : btoa(encodeURIComponent(getAgentHtml(xmlDoc.firstChild, "lotusscript"))));
			request += "]]></Code>";	
			request += "<agentschedule></agentschedule>";
			request += "<runas></runas>";
			request += "<runtimesecuritylevel></runtimesecuritylevel>";
			request += "<HeadCode><![CDATA[" + dsnHead + "]]></HeadCode>";
			request += "<InitCode><![CDATA[" + dsnInit + "]]></InitCode>";
			request += "<Code><![CDATA[" + dsnCode + "]]></Code>";
			request += "<EndCode><![CDATA[" + dsnEnd + "]]></EndCode>";
		}else if(elemtype == "lslibrary" || elemtype == "lotusscript library" || elemtype == "scriptlibrary"){
			var origcode = (importFromDocova ? decode : getAgentHtml(xmlDoc.firstChild, "lotusscript"));
			
			if(targettype == "jslibrary"){
				var LexerObj = new LexerLS(); 
				var newcode = LexerObj.convertCode(origcode, "JS");
				var newcode = beautify(newcode);
				
				request += "<Type><![CDATA[JS]]></Type>";			
				request += "<Code><![CDATA[";
				request += btoa(encodeURIComponent(newcode));
				request += "]]></Code>";											
			}else{
				request += "<Type><![CDATA[ScriptLibrary]]></Type>";			
				request += "<SubType>LotusScript</SubType>";
				request += "<Code><![CDATA[";

				//convert the code to php
				decode = $.trim(origcode);
				var LexerObj = new LexerPHP();
				var unenc = (importFromDocova ? atob(decode) : decode);
				//replace windows CRLF with LF on UNIX
				unenc = unenc.replace(/%0D%0A?/g, '%0A');
			
				unenc = (importFromDocova ? decodeURIComponent(unenc) : unenc);
				var outputTxt = LexerObj.convertCode(unenc, "PHP");
				outputTxt = btoa(encodeURIComponent(outputTxt));

				request +=  outputTxt;
				request += "]]></Code>";	


			}
		}else if(elemtype == "jslibrary" || elemtype == "javascript library" || elemtype == "javascriptlibrary"){
			request += "<Type><![CDATA[JS]]></Type>";			
			request += "<Code><![CDATA[";
			request += (importFromDocova ? decode : btoa(encodeURIComponent(getAgentHtml(xmlDoc.firstChild, "javascript"))));
			request += "]]></Code>";							
		}else{
			HideProgressMessage();
			return false;
		}
		request += "</Document>";
		request += "</Request>";
		
		
		//handle inline images...make them into image resources in the app
		//all found inline images are stored in the base64images array
		if (base64images.length > 0 && !importFromDocova)
		{
			createImageResourceFromImageData(servername, targetpath)
		}
					
		//send the request to server	
		var httpObj = new objHTTP();
		if(silent && silent == true){
			httpObj.supressWarnings = true;
		}
		if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED"){
			HideProgressMessage();
				return false;
			}
		if ( httpObj.results[0] != "" ){
			if(silent !== true){
				alert ( "Element " + elemname + " imported successfully!");
			}
			result = {status: true, docid: httpObj.results[0]};
			if(noviewrefresh !== true){
				Docova.getUIView().refresh({
					loadxml: true,
					loadxsl: true,
					highlightentryid: httpObj.results[0]
				});
			}
		}
	}
	return result;
}

function parseFormulaCode ( inhtml, skipdecode)
{
	var dehtmlUnencTxt = "<code>" + (typeof skipdecode != "undefined" && skipdecode === true ? inhtml : decodeURIComponent(inhtml))  + "</code>";
	var dehtmlUnenc = $(dehtmlUnencTxt);
	$(dehtmlUnenc).find ("[fdefault]").each ( function ()
	{
		var obj = $(this);
		var formula = obj.attr("fdefault");
		if ( formula  && formula != ''){
			//var LexerObj = new Lexer();	
			//var outputTxt = LexerObj.convertCode(formula, "TWIG");	
			outputTxt = formula.replace(/@/g, "$$$$");
			obj.attr("fdefault", outputTxt);
		}
	})

	//computed formulas
	$(dehtmlUnenc).find ("[fformula]").each ( function ()
	{
		var obj = $(this);
		var formula = obj.attr("fformula");
		if ( formula  && formula != ''){
			//var LexerObj = new Lexer();	
			//var outputTxt = LexerObj.convertCode(formula, "TWIG");	
			outputTxt = formula.replace(/@/g, "$$$$");
			
			obj.attr("fformula", outputTxt);
		}
	})

	//custom hide/whens
	$(dehtmlUnenc).find ("[customhidewhen]").each ( function ()
	{
		var obj = $(this);
		var formula = obj.attr("customhidewhen");
		if ( formula  && formula != ''){
			//var LexerObj = new Lexer();	
			//var outputTxt = LexerObj.convertCode(formula, "TWIG");	
			outputTxt = formula.replace(/@/g, "$$$$");
			
			obj.attr("customhidewhen", outputTxt);
		}
	})
	
	//input translation formulas
	$(dehtmlUnenc).find ("[ftranslate]").each ( function ()
	{
		var obj = $(this);
		var formula = obj.attr("ftranslate");
		if ( formula  && formula != ''){
			//var LexerObj = new Lexer();	
			//var outputTxt = LexerObj.convertCode(formula, "TWIG");	
			outputTxt = formula.replace(/@/g, "$$$$");
			
			var LexerObj = new Lexer(); 
			var tempformula = LexerObj.convertCode(outputTxt, "JS");
			var sformula = "";
			sformula += "try{\r";
			sformula += tempformula;
			sformula += "\r}catch(e){if(e instanceof _HandleReturnValue){finalreturnvalue = _lastreturnvalue;}else{throw e}}";
			
			
			sformula = beautify(sformula);
			sformula = safe_quotes_js(safe_tags(sformula), false);
			
			obj.attr("ftranslate", sformula);
		}
	})
	
	
	$(dehtmlUnenc).find ("[subformformula]").each ( function ()
	{
		var obj = $(this);
		var formula = obj.attr("subformformula");
		if ( formula  && formula != ''){
			//var LexerObj = new Lexer();	
			//var outputTxt = LexerObj.convertCode(formula, "TWIG");	
			outputTxt = formula.replace(/@/g, "$$$$");
			
			obj.attr("subformformula", outputTxt);
		}
	})
	//subform formulas

	var out = dehtmlUnenc.first().html();
	return out;
}

function translateColumnFormulas(xml)
{
	var xml_obj = $.parseXML(xml);
	$(xml_obj).find('columnFormula').each(function() {
		var newnode = '<translatedFormula><![CDATA[';
		if ($.trim($(this).text()) != '')
		{
			var LexerObj = new Lexer();
			//replace the $REF to $$REF
			var formula = $.trim($(this).text());//.replace(/\$/g, "$$$$")); 
			//now convert all @ formula's into $$
			formula = $.trim(formula.replace(/@/g, "$$$$"));
			var tmpCdata = xml_obj.createCDATASection(formula);
			$(this).text ('');
			$(this)[0].appendChild(tmpCdata);
			formula = LexerObj.convertCode(formula, "TWIG");
			newnode += ('{% docovascript "variable:array" %}' + formula + '{% enddocovascript %}');
		}
		newnode += ']]></translatedFormula>';
		newnode = newnode.replace(/\n/g, '').replace(/\r/g, '')
		$(this).before(newnode);
	});
	
	$(xml_obj).find('responseFormula').each(function() {
		var newnode = '<translatedFormula><![CDATA[';
		if ($.trim($(this).text()) != '')
		{
			var LexerObj = new Lexer();
			//replace the $REF to $$REF
			var formula = $.trim($(this).text());//.replace(/\$/g, "$$$$")); 
			//now convert all @ formula's into $$
			formula = $.trim(formula.replace(/@/g, "$$$$"));
			var tmpCdata = xml_obj.createCDATASection(formula);
			$(this).text ('');
			$(this)[0].appendChild(tmpCdata);			
			formula = LexerObj.convertCode(formula, "TWIG");
			newnode += ('{% docovascript "variable:array" %}' + formula + '{% enddocovascript %}');
		}
		newnode += ']]></translatedFormula>';
		newnode = newnode.replace(/\n/g, '').replace(/\r/g, '')
		$(this).before(newnode);
	});
	
	xml = (new XMLSerializer()).serializeToString(xml_obj);
	return xml;
}

function translateColumnsScript(xml)
{
	var xml_obj = $.parseXML(xml);
	var returnString = '<columnsscript><![CDATA[{ ';
	var root = $(xml_obj).find('viewsettings');

	$(xml_obj).find('columnFormula').each(function() {
		var LexerObj = new Lexer(); 
		if (LexerObj && typeof LexerObj != typeof undefined) {
			var twigFormula = $.trim($(this).text()); 
			var outputTxt = LexerObj.convertCode(twigFormula, "TWIG");
			returnString += '{% docovascript "raw:array" %}' + outputTxt.replace(/[\n\r]+/g, '')  + '{% enddocovascript %}';
			returnString += '"' + $(this).siblings('xmlNodeName').first().text() + '" : {{ __dexpreresraw|serialize|json_encode() }} ,';
		}
		else {
			returnString += '"' + $(this).siblings('xmlNodeName').first().text() + '" : "'+ $.trim($(this).text()) +'" ,';
		}
	});

	$(xml_obj).find('responseFormula').each(function() {
		if ($.trim($(this).text()) != '')
		{
			var LexerObj = new Lexer(); 
			if (LexerObj && typeof LexerObj != typeof undefined) {
				var twigFormula = $.trim($(this).text()); 
				var outputTxt = LexerObj.convertCode(twigFormula, "TWIG");
				returnString += '{% docovascript "raw:array" %}' + outputTxt.replace(/[\n\r]+/g, '')  + '{% enddocovascript %}';
				returnString += '"RESP_' + $(this).siblings('xmlNodeName').first().text() + '" : {{ __dexpreresraw|serialize|json_encode() }} ,';
			}
			else {
				returnString += '"RESP_' + $(this).siblings('xmlNodeName').first().text() + '" : "'+ $.trim($(this).text()) +'" ,';
			}
		}
	});
	
	if (returnString.length > 1) {
		returnString = returnString.slice(0, -1);
	}
	
	returnString += ' }]]></columnsscript>';
	returnString = returnString.replace(/\n/g, '').replace(/\r/g, '');
	root.prepend(returnString);
	
	xml = (new XMLSerializer()).serializeToString(xml_obj);
	return xml;
}

/*
 * uses  the base64encoded data for the inline image stored in the base64images array
 * and turns them into image resources
*/
function createImageResourceFromImageData(servername, path){
	var imagesurl =  docInfo.PortalWebPath + "DesignServices?OpenAgent"
	var request="";
	var uname = docInfo.UserNameAB
	request += "<Request>";
	request += "<Action>SAVEBASE64IMAGES</Action>";
	request += "<server>" + servername + "</server>";
	request += "<path>" + path + "</path>";
	request += "<UserName><![CDATA[" + uname +"]]></UserName>";
	request += "<images>";
	for ( var p =0;p < base64images.length; p ++){
		request += "<image name='" + base64images[p].imagename + "'>";
			var imgDXL = "<?xml version='1.0' encoding='UTF-8'?><database><imageresource name='" +  base64images[p].imagename + "' noreplace='true' comment='APPBUILDER'>";
			imgDXL +=  "<" +base64images[p].imagetype + ">"
			imgDXL += base64images[p].encoding
			imgDXL +=  "</" +base64images[p].imagetype + ">"
			imgDXL += "</imageresource>"
			imgDXL += "</database>"
			
		request += "<![CDATA[" + imgDXL + "]]></image>"
	}
	request +="</images>";
	request += "</Request>";	
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), imagesurl) || httpObj.status=="FAILED") {return false; }

	if ( httpObj.results[0] )
	{
		return true;
	}
}

function doImportOutline(servername, path, type, name, target, silent, noviewrefresh, options)
{
	var result = false;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}	
	
	
	var elemtype = type.toLowerCase();
	
	var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent";
	
	var request="";
	request += "<Request>";
	request += "<Action>" + (importFromDocova ? "GETELEMENTHTML" : "GETDXL") + "</Action>";
	request += "<server>" + servername + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemtype>" + elemtype + "</elemtype>";
	request += "<elemname><![CDATA[" + name + "]]></elemname>";
	request += "<direct>" + (importFromDocova ? "" : "1") + "</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;

	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {return false; }

	if ( httpObj.results[0] )
	{
		var dxl = httpObj.results[0];
		if (importFromDocova) {
			var find = '&lt;';
			var re = new RegExp(find, 'g');

			dxl = dxl.replace(re, "<");

			find = "&gt;";
			re = new RegExp(find, 'g');
			dxl = dxl.replace(re, ">");
			
		}
		
		var xmlDoc = (new DOMParser()).parseFromString(dxl, "text/xml");
		if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
	  		var errorText = Sarissa.getParseErrorText(xmlDoc);
	  		if(silent !== true){
	  			alert("Error loading element DXL: " + errorText);
	  		}
			xmlDoc = null;
			return false;
		}

		if ( importFromDocova){
			xmlDoc = xmlDoc.documentElement
			var tmpnode = xmlDoc.selectSingleNode("DENAME");
			var dename = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEALIAS");
			var dealias = tmpnode && (tmpnode.textContent || tmpnode.text ) ? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DEALIAS");
			var dealias = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DECSS");
			var decss = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("DECODE");
			var decode = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			decode = decodeURIComponent(decode);
			tmpnode = xmlDoc.selectSingleNode("DEPROPERTIES");
			var deproperties = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			deproperties = decodeURIComponent(deproperties);

		}else{
			var dename = xmlDoc.firstChild.getAttribute("name");
			var dealias = xmlDoc.firstChild.getAttribute("alias");
			var deproperties = getPropertiesHtml(xmlDoc.firstChild, elemtype);
			
			var html = DXLOutline(xmlDoc.firstChild, {'apppath' : target})
			var jsondata = generateJsonOutline({}, jQuery(html));
			var code = "";
			if(jsondata){
				jsondata.Style = "Basic";
				jsondata.designversion = designversion.toString();
				jsondata.menuStyle = {
					"MenuBackground": "#ffffff",
					"HeaderBackground": "#ffffff",
					"HeaderPadding": "18px",
					"HeaderFontColor": "#000000",
					"HeaderFontSize": "12px",
					"SubHeaderBackground": "#ffffff",
					"SubHeaderPadding": "18px",
					"SubHeaderFontColor": "#000000",
					"SubHeaderFontSize": "12px",
					"catIconHeader": "fa-caret-right:fa-caret-down",
					"catIconSubHeader": "fa-caret-right:fa-caret-down",
					"ExpIconPlacementHeader": "Left",
					"ExpIconPlacementSubHeader": "Left",
					"HeaderIconSize": "1.2em",
					"SubHeaderIconSize": "1.2em",
					"ItemsBackground": "#ffffff",
					"ItemsFontColor": "#000000",
					"ItemsPadding": "18px",
					"border_style": "solid",
					"bordercolor": "#d7d7d7",
					"ItemsFontSize": "12px",
					"MenuHoverColor": "#5c9ccc"					
				};
				code = JSON.stringify(jsondata);
			}			
			
			var decode =  code;			
		}

		decode = fixOutlineJSON(decode);
		decode = decode.replace(/\/\/|\\\\/g, '-');
		
		var elemname = dename;
		if ( !elemname || elemname == "" ){ return;}


		if ( elemname.indexOf ("\\") > -1){
			elemname = elemname.replace(/\\/g, "-");
		}

		var elemalias = dealias;
		if ( !elemalias){ elemalias = "";}		

		request = "<Request>";
		request += "<Action>SAVEDESIGNELEMENTHTML</Action>";				
		request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<Document>";
		request += "<Name><![CDATA[" + elemname  + "]]></Name>";
		request += "<Alias><![CDATA[" + elemalias  + "]]></Alias>";
		request += "<Unid></Unid>";
		request += "<AppID><![CDATA[" + (docInfo.AppID ? docInfo.AppID : docInfo.DocKey) + "]]></AppID>";
		request += "<AddCSS></AddCSS>";
		request += "<AddJS></AddJS>";
		request += "<Properties>" + deproperties + "</Properties>";
		request += "<ProhibitDesignUpdate></ProhibitDesignUpdate>";		
		request += "<Type><![CDATA[Outline]]></Type>";				
		request += "<CSS><![CDATA["+ decss + "]]></CSS>";				
		request += "<HTML></HTML>";		
		request += "<Code><![CDATA[";
		request += decode;
		request += "]]></Code>";
		request += "</Document>";
		request += "</Request>";
		
		
		//send the request to server	
		var httpObj = new objHTTP();
		if(silent && silent == true){
			httpObj.supressWarnings = true;
		}
		if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED"){
			HideProgressMessage();
				return false;
			}
		if ( httpObj.results[0] != "" ){
			if(silent !== true){
				alert ( "Element " + elemname + " imported successfully!");
			}
			result = {status: true, docid: httpObj.results[0]};
			if(noviewrefresh !== true){
				Docova.getUIView().refresh({
					loadxml: true,
					loadxsl: true,
					highlightentryid: httpObj.results[0]
				});
			}
		}
	}
	return result;
}

function getDefaultOutlineHtml(options){
	var outlinehtml = "";
	
	var defaultOptns = {
			apppath : "",
			fragmentonly : false,
			excludedviews : null
		};
	var opts = $.extend({}, defaultOptns, options);	
	
	if(opts.apppath == ""){
		opts.apppath = (docInfo && docInfo.AppFilePath ? docInfo.AppFilePath : (docInfo && docInfo.AppPath && docInfo.AppFileName ? (docInfo.AppPath + "/" + docInfo.AppFileName) : ""));		
	}
	if(opts.apppath == ""){
		return outlinehtml;
	}
	
	var viewlist = Docova.Utils.dbColumn({
		'servername' : "",
		'nsfname' : opts.apppath,
		'viewname' : "AppViews",
		'column' : 7,
		'secure' : (docInfo && docInfo.SSLState ? docInfo.SSLState : "")
	});
	if(viewlist == "404"){
		return outlinehtml;
	}
	viewlist = jQuery.unique(viewlist.split(";").sort());
	
	if(!opts.fragmentonly){
		outlinehtml += '<ul class="OutlineItems" style="list-style-type: none; margin-left: 20px; padding: 0px; border: 0px solid rgb(0, 0, 0); border-radius: 0px;"  ostyle="Themed" >';		
	}
	
	var categories = [];
	for(var i=0; i<viewlist.length; i++){
		if(viewlist[i].length != "" && viewlist[i].slice(0,1) != "("){
			var viewnameparts = viewlist[i].split("|");
			var viewalias = viewnameparts[viewnameparts.length-1];
			var viewcomp = viewnameparts[0].split("\\");
			var viewname = viewcomp.pop();
			if(!(opts.excludedviews !== null && Array.isArray(opts.excludedviews) && opts.excludedviews.length > 0 && (opts.excludedviews.indexOf(viewname)>-1 || opts.excludedviews.indexOf(viewalias)>-1))){
				
				//-- deal with any unclosed levels
				while(categories.length > viewcomp.length){
					outlinehtml += "</ul>";
					categories.pop();
				}
				
				//-- check the current view to see if it matches existing categories
				for(var t=viewcomp.length-1; t>=0; t--){
					if(categories.length > 0){
						//-- check if we match the existing categories or not
						if(categories.join("\\") != viewcomp.slice(0, t+1).join("\\")){						
							//-- close off the unclosed levels
							outlinehtml += "</ul>";					
							categories.pop();
							break;
						}
					}
				}
				
				//-- insert any new levels
				for(var v=0; v<viewcomp.length; v++){
					//-- check if we match the existing categories or not
					if(!(categories.length > 0 && (categories.join("\\") == viewcomp.slice(0, v+1).join("\\")))){						
						//-- insert new level
						outlinehtml += '<li class="ui-state-default" style="margin:0px 3px 3px 3px; padding:0.4em; padding-left:1.5em; text-align:left;" etype="none" eelement="" etarget="" einitiallyexpanded="0" emenuitemtype="H" einitiallyselected="0">';
							outlinehtml += '<span class="" style="position: relative; padding-right: 0.5em; padding-left: 0.3em; padding-top: 0px;" icontitle=""></span>';
							outlinehtml += '<div class="itemlabel" style="font-weight: normal; display: inline-block; font-style: normal; font-size: 12px;">' + safe_tags(viewcomp[v]) + '</div>';
						outlinehtml += '</li>';
						outlinehtml += "<ul>";						
				
						categories.push(viewcomp[v]);				
					}
				}
				
				var viewnameoralias = "";
				var viewparts = viewlist[i].split("|");
				for(var va=viewparts.length-1; va>=0; va--){
					viewnameoralias = jQuery.trim(viewparts[va] || "");					
					if(viewnameoralias != ""){
						break;
					}
				}
				
				outlinehtml += '<li class="ui-state-default" style="margin:0px 3px 3px 3px; padding:0.4em; padding-left:1.5em; text-align:left;" etype="view" eelement="' + safe_tags(viewnameoralias) +'" etarget="" eviewtype="" einitiallyexpanded="0" emenuitemtype="M" einitiallyselected="0">';
		 			outlinehtml += '<span class="" style="position: relative; padding-right: 0.5em; padding-left: 0.3em; padding-top: 0px;" icontitle=""></span>';
		 			outlinehtml += '<div class="itemlabel" style="font-weight: normal; display: inline-block; font-style: normal; font-size: 12px;">' + safe_tags(viewname) + '</div>';
				outlinehtml += '</li>';		
			}
		}
	}

	for ( var p=0; p < categories.length; p++){
		outlinehtml += "</ul>";								
	}
	
	if(!opts.fragmentonly){		
		outlinehtml += '</ul>';	
	}
	
	return outlinehtml;
}


function doInstallAssets() {
	var result = false;
	var url = docInfo.PortalWebPath + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>INSTALLASSET</Action>";
	request += "<AppID>" + docInfo.AppID + "</AppID>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
		HideProgressMessage();
		return false; 
	}
	else {
		result = true;
	}
	return result;
}

function createDefaultOutline(options){
	var result = false;
	
	var defaultOptns = {
			appid : "",
			apppath : "",
			silent : false,
			fragmentonly : false
		};
	var opts = $.extend({}, defaultOptns, options);	
	
	var uname = docInfo.UserNameAB;
	if(opts.appid == ""){
		opts.appid = (docInfo && docInfo.AppID ? docInfo.AppID : "");
	}
	if(opts.apppath == ""){
		opts.apppath = (docInfo && docInfo.AppFilePath ? docInfo.AppFilePath : (docInfo && docInfo.AppPath && docInfo.AppFileName ? (docInfo.AppPath + "/" + docInfo.AppFileName) : ""));		
	}
	if(opts.appid == "" || opts.apppath == ""){
		return result;
	}
	
	
	var outlinehtml = getDefaultOutlineHtml(opts);
	var jsondata = generateJsonOutline({}, jQuery(outlinehtml));
	var code = "";
	if(jsondata){
		jsondata.Style = "Basic";
		jsondata.menuStyle = {
				"MenuBackground": "#ffffff",
				"HeaderBackground": "#ffffff",
				"HeaderPadding": "18px",
				"HeaderFontColor": "#000000",
				"HeaderFontSize": "12px",
				"SubHeaderBackground": "#ffffff",
				"SubHeaderPadding": "18px",
				"SubHeaderFontColor": "#000000",
				"SubHeaderFontSize": "12px",
				"catIconHeader": "fa-caret-right:fa-caret-down",
				"catIconSubHeader": "fa-caret-right:fa-caret-down",
				"ExpIconPlacementHeader": "Left",
				"ExpIconPlacementSubHeader": "Left",
				"HeaderIconSize": "1.2em",
				"SubHeaderIconSize": "1.2em",
				"ItemsBackground": "#ffffff",
				"ItemsFontColor": "#000000",
				"ItemsPadding": "18px",
				"border_style": "solid",
				"bordercolor": "#d7d7d7",
				"ItemsFontSize": "12px",
				"MenuHoverColor": "#5c9ccc"					
			};		
		code = JSON.stringify(jsondata);
	}		
	
	var request = "<Request>";
	request += "<Action>SAVEDESIGNELEMENTHTML</Action>";
	request += "<UserName><![CDATA[" + uname +"]]></UserName>";
	request += "<Document>";
	request += "<AppID><![CDATA[" + opts.appid + "]]></AppID>";
	request += "<Unid></Unid>";
	request += "<Type><![CDATA[Outline]]></Type>";		
	request += "<SubType><![CDATA[]]></SubType>";	
	request += "<Name><![CDATA[ViewListing]]></Name>";
	request += "<Alias><![CDATA[]]></Alias>";	
	request += "<ProhibitDesignUpdate></ProhibitDesignUpdate>";	
	request += "<DoMobile></DoMobile>";
	request += "<Code><![CDATA[" + code +"]]></Code>";
	request += "<HTML><![CDATA[]]></HTML>";	
	request += "<CSS><![CDATA[]]></CSS>";	
	request += "<AddCSS><![CDATA[]]></AddCSS>";
	request += "<AddJS><![CDATA[]]></AddJS>";
	request += "<Properties><![CDATA[]]></Properties>";
	request += "</Document>";
	request += "</Request>";
	
	
	var formunid = SubmitRequest(encodeURIComponent(request), "DesignServices");			
	if ( ! formunid || formunid == "" || formunid == "FAILED" ) {
		if(! opts.silent){
			window.top.Docova.Utils.messageBox({
				width: 400,
				title: "Error Generating Default Outline",
				prompt: "An error occurred while generating the default view outline.",
				icontype: 1,
				msgboxtype: 0
			});
		}
	}else{
		result = true;
	}
	
	return result;
}//--end createDefaultOutline


function doImportImage(servername, path, type, name, target, silent, noviewrefresh, options)
{
	var result = false;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}			
	
	var uname = docInfo.UserNameAB;

	
	
	var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>GETIMAGEDATA</Action>";
	request += "<UserName><![CDATA[" + uname + "]]></UserName>"
	request += "<server>" + servername + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemname>" + name + "</elemname>";
	request += "<direct>" + (importFromDocova ? "" : "1") + "</direct>";	
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
		HideProgressMessage();
		return false; 
	}



	if ( httpObj.results[0] != "" ){
		var ext = name.split('.');
		var request = "<Request><Action>SAVEIMAGERESOURCE</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "<AppID>" + docInfo.AppID + "</AppID>";
		request += "<FileUNID></FileUNID>";
		request += "<Document>";
		request += "<imgName><![CDATA[" + name + "]]></imgName>";
		request += "<imgContent><![CDATA[" + httpObj.results[0] + "]]></imgContent>";
		request += '<imgExtension><![CDATA['+ (ext.length > 1 ? ext.pop() : 'gif') +']]></imgExtension>';
		request += "</Document>"
		request += "</Request>";
		var httpObj = new objHTTP();
		httpObj.supressWarnings = true;
		if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
			HideProgressMessage();
			return false; 
		}


		if(silent !== true){		
			alert ( "Element " + name + " imported successfully!");
		}
		result = true;
		if(noviewrefresh !== true){
			Docova.getUIView().refresh({
				loadxml: true,
				loadxsl: true,
				highlightentryid: httpObj.results[0]
			});
		}
	}	

	return result;
}


function doImportFrameset(servername, path, type, name, target, silent, noviewrefresh, setasdefault, options)
{
	var result = false;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}		
	
	var action = importFromDocova ? "GETELEMENTHTML" : "GETDXL";

	
	var url = docInfo.PortalWebPath + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<server>" + servername + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemtype>" + type.toLowerCase() + "</elemtype>";
	request += "<elemname><![CDATA[" + name + "]]></elemname>";
	request += "<direct>" + (importFromDocova ? "" : "1") + "</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {return false; }

	if ( httpObj.results[0] )
	{
		var dxl = httpObj.results[0];

		if ( importFromDocova) {
			var find = '&lt;';
			var re = new RegExp(find, 'g');

			dxl = dxl.replace(re, "<");

			find = "&gt;";
			re = new RegExp(find, 'g');
			dxl = dxl.replace(re, ">");
			
		}
		
		var xmlDoc = (new DOMParser()).parseFromString(dxl, "text/xml");
		if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
	  		var errorText = Sarissa.getParseErrorText(xmlDoc);
	  		if(silent !== true){	
	  			alert("Error loading element DXL: " + errorText);
	  		}
			xmlDoc = null;
			return false;
		}
		
		if ( importFromDocova ){
			xmlDoc = xmlDoc.documentElement
			var tmpnode = xmlDoc.selectSingleNode("LayoutHTML");
			var html = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			var tmpnode = xmlDoc.selectSingleNode("name");
			var elemname = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			if(elemname.indexOf("|") > -1){
				elemname = elemname.split("|")[0];
			}			
			var tmpnode = xmlDoc.selectSingleNode("alias");
			var elemalias = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";			
			if(! elemalias){
				elemalias = "";
			}
			var tmpnode = xmlDoc.selectSingleNode("isdefault");
			var isdefault = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			if ( isdefault == "1"){
				setasdefault = true;
			}
			html = html.replace(/frameset/g,'DFRMSET');
			html = html.replace(/frame/g,'DFRM')
			html = $.trim(html);
			var objhtml = $(html);
			objhtml.find("dfrm").each( function () {
				var ctype = $(this).attr("contenttype");
				if ( ctype && ctype == "Page"){
					var olname = $(this).attr('content');
					var newsrc = "{{ path(\'docova_homepage\') }}wViewPage/"+olname+"?AppID={{ appId }}";

					$(this).attr("src", newsrc);
					$(this).attr("dsrc", newsrc);
				}else if ( ctype && ctype == "View"){
					var viewname = $(this).attr('content');
					viewname = viewname.replace(/\/|\\/g, '-').replace(/\s\s+/g, ' ');
					var newsrc = "{{ path(\'docova_homepage\') }}wViewFrame?readForm&title=" + viewname + "&surl=AppViewsAll/" + viewname + "?openDocument%26AppID={{ appId }}";
					$(this).attr("src", newsrc);
					$(this).attr("dsrc", newsrc);
				}else if ( ctype && ctype == "Form"){

					var olname = $(this).attr('content');
					var newsrc = "{{ path(\'docova_homepage\') }}wViewForm/"+olname+"?AppID={{ appId }}";

					$(this).attr("src", newsrc);
					$(this).attr("dsrc", newsrc);
				}else if (ctype && ctype == "Outline") {
					var menuName = $(this).attr('content');
					var newsrc = "{{ path(\'docova_homepage\') }}wViewOutline/"+menuName+"?AppID={{ appId }}";

					$(this).attr("src", newsrc);
					$(this).attr("dsrc", newsrc);
				}else if (ctype && ctype == "Layout") {
					var layoutName = $(this).attr('content');
					var newsrc = "{{ path(\'docova_homepage\') }}wLoadLayout/"+layoutName+"?AppID={{ appId }}";

					$(this).attr("src", newsrc);
					$(this).attr("dsrc", newsrc);					
				}else{
					var src = $(this).attr("src");
					if ( src && src != "")
						src = src + "&AppID={{ appId }}";

					$(this).attr("src", src);
					$(this).attr("dsrc", src);
				}

			})
			html = $("<div></div>").append(objhtml).html();
			html = html.replace(/dfrmset/g,'frameset');
			html = html.replace(/dfrm/g,'frame')
			

		}
		else{
			var elemname = xmlDoc.firstChild.getAttribute("name");
			if(elemname.indexOf("|") > -1){
				elemname = elemname.split("|")[0];
			}
			var elemalias = xmlDoc.firstChild.getAttribute("alias");
			if(! elemalias){
				elemalias = "";
			}

			var html = DXLFrameset(xmlDoc.firstChild)
		}
		
		
		
		
		var uname = docInfo.UserNameAB
		var agentName = "DesignServices"
	
		var dsnFormDivHTML =html;  //just get the layout html

	
		if ( !elemname || elemname == "" ) return false;
		if ( importFromDocova ) {
			if (elemname.indexOf('|') > -1) {
				elemname = elemname.split('|');
				if(elemalias == ""){
					if(elemname.length > 1){
						elemalias = elemname[elemname.length - 1];
					}
				}
				elemname = elemname[0];
			}
		}
				
		dsnFormJSHeader= "";
		var request = "<Request><Action>SAVELAYOUTHTML</Action><UserName><![CDATA[" + uname + "]]></UserName>";
		request += "<Document>";
		request += "<LayoutName><![CDATA[" + elemname + "]]></LayoutName>";
		request += "<LayoutAlias><![CDATA[" + elemalias + "]]></LayoutAlias>";		
		request += "<AppID><![CDATA[" + docInfo.AppID + "]]></AppID>";
		request += "<TargetApplication><![CDATA[" + (target === undefined ? "" : target) + "]]></TargetApplication>";
		request += "<isDefault>" + (setasdefault ? "1" : "") + "</isDefault>";
		request += "<LayoutHTML><![CDATA[" + dsnFormDivHTML + "]]></LayoutHTML>";
		request += "</Document>";
		request += "</Request>";
			
		//send the request to server
		var processUrl =  docInfo.PortalWebPath + "DesignServices?OpenAgent";
		
		var httpObj = new objHTTP();
			if(!httpObj.PostData(encodeURIComponent(request), processUrl) || httpObj.status=="FAILED"){
				HideProgressMessage();
					return false;
				}
			if ( httpObj.results[0] != "" ) {
				if(silent !== true){	
					alert ( "Element " + elemname + " imported successfully!");
				}
				result = {status: true, docid: httpObj.results[0]};
				if(noviewrefresh !== true){
					Docova.getUIView().refresh({
						loadxml: true,
						loadxsl: true,
						highlightentryid: httpObj.results[0]
					});
				}
			}
		
	}
	return result;
}




function doImportView( server, path, type, name, target, silent, noviewrefresh, options)
{
	
	var result = false;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}			
	
	var action = importFromDocova ? "GETELEMENTHTML" : "GETDXL";

	var url = docInfo.PortalWebPath + "DesignServices?OpenAgent"

	var request="";
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<server>" + server + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemtype>" + type.toLowerCase() + "</elemtype>";
	request += "<elemname><![CDATA[" + name + "]]></elemname>";
	request += "<direct>" + (importFromDocova ? "" : "1") + "</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {return false; }

	if ( httpObj.results[0] )
	{
		var dxl = httpObj.results[0];
		if ( importFromDocova) {
			var find = '&lt;';
			var re = new RegExp(find, 'g');

			dxl = dxl.replace(re, "<");

			find = "&gt;";
			re = new RegExp(find, 'g');
			dxl = dxl.replace(re, ">");
			
		}


		
		var xmlDoc = (new DOMParser()).parseFromString(dxl, "text/xml");
		if(Sarissa.getParseErrorText(xmlDoc) != Sarissa.PARSED_OK){  
	  		var errorText = Sarissa.getParseErrorText(xmlDoc);
	  		if(silent !== true){	
	  			alert("Error loading element DXL: " + errorText);
	  		}
			xmlDoc = null;
			return false;
		}
		var elemname = xmlDoc.firstChild.getAttribute("name");
		var viewobj = null;

		if ( importFromDocova)
		{
			xmlDoc = xmlDoc.documentElement
			var tmpnode = xmlDoc.selectSingleNode("ViewName");
			var viewname = tmpnode && (tmpnode.textContent || tmpnode.text) ? tmpnode.textContent || tmpnode.text : "";
			viewname = viewname.replace(/\/|\\/g, '-').replace(/\s\s+/g, ' ');
			tmpnode = xmlDoc.selectSingleNode("ViewAlias");
			var viewalias = tmpnode && (tmpnode.textContent || tmpnode.text ) ? tmpnode.textContent || tmpnode.text : "";
			viewalias = viewalias.replace(/\/|\\/g, '-').replace(/\s\s+/g, ' ');
			tmpnode = xmlDoc.selectSingleNode("ViewSettings");
			var viewsettings = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			viewsettings = translateColumnFormulas(decodeURIComponent(viewsettings));
			viewsettings = translateColumnsScript(viewsettings);
			tmpnode = xmlDoc.selectSingleNode("Toolbar");
			var toolbar = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("ViewSelectionFormula");
			var viewselectionformula = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("ViewJavascriptTxt");
			var viewJavascriptTxt = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("RespHierarchy");
			var resphierarchy = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("RespColspan");
			var respcolspan = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("ShowSelection");
			var showselection = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";			
			tmpnode = xmlDoc.selectSingleNode("EmulateFolder");
			var emulatefolder = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("PrivateOnFirstUse");
			var privateonfirstuse = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";
			tmpnode = xmlDoc.selectSingleNode("titleNode");
			var titlenode = tmpnode && (tmpnode.textContent || tmpnode.text)? tmpnode.textContent || tmpnode.text : "";

			if(jQuery.trim(viewJavascriptTxt) !== ""){
				viewJavascriptTxt = atob(decodeURIComponent(viewJavascriptTxt)); 
				viewJavascriptTxt = btoa(encodeURIComponent(viewJavascriptTxt));
			}
			
			viewobj = {};
			viewobj.viewname = viewname;
			viewobj.viewalias = viewalias;
			viewobj.perspectiveXml = viewsettings;
			viewobj.toolbar =  decodeURIComponent(toolbar);
			
			viewobj.toolbar = parseFormulaCode ( toolbar);//<<-- Javad - passing the decoded toolbar cause to double decode and malforming the URI
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;quot;/g, "&quot;")); 
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;apos;/g, "&apos;")); 
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;amp;/g, "&amp;"));
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;lt;/g, "&lt;"));
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;gt;/g, "&gt;"));
			var toolbardata = $("<div></div>").html($(viewobj.toolbar));


			var toolbarTWIG = GetViewToolbarTwig(toolbardata);
			var toolbarTWIG_m = GetViewToolbarMobileTwig(toolbardata);

			viewobj.viewformula = viewselectionformula;
			viewobj.viewJavascriptTxt = viewJavascriptTxt;
			viewobj.showSelection = showselection;
			viewobj.showresponsesinhierarchy = resphierarchy;
			viewobj.responsecolspan = respcolspan;
			viewobj.emulatefolder = emulatefolder;
			viewobj.privateonfirstuse = privateonfirstuse;
			viewobj.titlenode = titlenode;

		}else{
			//-- try and retrieve column data types for this view
			var coltypes = "";
			var request2="";
			request2 += "<Request>";
			request2 += "<Action>GETVIEWCOLUMNTYPES</Action>";
			request2 += "<server>" + server + "</server>";
			request2 += "<path>" + path + "</path>";
			request2 += "<elemname><![CDATA[" + elemname + "]]></elemname>";
			request2 += "</Request>";	

			var httpObj2 = new objHTTP();
			httpObj2.supressWarnings = true;
			if(httpObj2.PostData(encodeURIComponent(request2), url) && httpObj2.status=="OK") {
				if ( httpObj2.results[0] )
				{
					coltypes = httpObj2.results[0];	
				}
			}		
			if(coltypes!= ""){
				coltypes = coltypes.split(",");
			}
		
			var viewobj = DXLView(xmlDoc.firstChild, coltypes);
			
			viewobj.viewname = viewobj.viewname.replace(/\/|\\/g, '-').replace(/\s\s+/g, ' ');
			viewobj.viewalias = viewobj.viewalias.replace(/\/|\\/g, '-').replace(/\s\s+/g, ' ');
			viewobj.perspectiveXml = translateColumnFormulas(viewobj.perspectiveXml);
			
			var viewJavascriptTxt =  getViewJSCode(xmlDoc.firstChild);
			if(jQuery.trim(viewJavascriptTxt) !== ""){
				viewobj.viewJavascriptTxt = btoa(encodeURIComponent(viewJavascriptTxt));
			}else{
				viewobj.viewJavascriptTxt = "";
			}
			
			viewobj.toolbar = parseFormulaCode (viewobj.toolbar, !importFromDocova);
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;quot;/g, "&quot;")); 
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;apos;/g, "&apos;")); 
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;amp;/g, "&amp;"));
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;lt;/g, "&lt;"));
			viewobj.toolbar = $.trim(viewobj.toolbar.replace(/\&amp;gt;/g, "&gt;"));			
			var toolbardata = $("<div></div>").html($(viewobj.toolbar));

			var toolbarTWIG = GetViewToolbarTwig(toolbardata);
			var toolbarTWIG_m = GetViewToolbarMobileTwig(toolbardata);
			
		}
		
		if (viewobj){
			actionUrl = docInfo.PortalWebPath + "NotesView?openForm&mode=build&AppID=" + docInfo.AppID;
			
			var selectionFormula = $.trim(viewobj.viewformula.replace(/\$/g, "$$$$")); 
			selectionFormula = $.trim(selectionFormula.replace(/@/g, "$$$$"));
			selectionFormula = selectionFormula != '' ? selectionFormula : 'true';

			var fd = new FormData();
			var formData = [];
			formData.push({ name: "ViewName", value: viewobj.viewname });
			formData.push({ name: "ViewAlias", value: viewobj.viewalias });
			formData.push({ name: "ViewSettings", value: viewobj.perspectiveXml });
			formData.push({ name: "Toolbar", value: viewobj.toolbar });
			formData.push({ name: "ViewToolbarTxt", value: viewobj.toolbar });
			formData.push({ name: "ViewPerspectiveTxt", value: encodeURIComponent(viewobj.perspectiveXml) });
			formData.push({ name: "ViewJavascriptTxt", value: viewobj.viewJavascriptTxt });
			formData.push({ name: "ViewToolbarTWIG", value: toolbarTWIG });
			formData.push({ name: "ViewToolbarTWIG_m", value: toolbarTWIG_m });		
			formData.push({ name: "ViewSelectionType", value: "S" });
			formData.push({ name: "ViewSelectionFormula", value: selectionFormula });
			formData.push({ name: "ShowSelection", value: viewobj.showSelection });			
			formData.push({ name: "RespHierarchy", value: viewobj.showresponsesinhierarchy });
			formData.push({ name: "RespColspan", value: viewobj.responsecolspan });
			formData.push({ name: "EmulateFolder", value: viewobj.emulatefolder });
			formData.push({ name: "PrivateOnFirstUse", value: viewobj.privateonfirstuse });			
			formData.push({ name: "AutoCollapse", value: viewobj.autocollapse});
			formData.push({ name: "ViewSearch", value: "1"});
			formData.push({ name: "titleNode", value:  viewobj.titlenode });
			formData.push({ name: "isSave", value:  "1" });

			var LexerObj = new Lexer(); 	
			selectionFormula = LexerObj.convertCode(selectionFormula, "TWIG");
			formData.push({ name: "TranslatedSelectionFormula", value: selectionFormula });
		   	
			$.ajax({
				url : actionUrl,
				type : "POST",
				data : jQuery.param(formData),
				async: false,
				dataType : "xml"
			}).done(function (data) {
				if (data) {
					var retnode = jQuery(data).find("Result");
					var tempresult = retnode[0].textContent || retnode[0].text || "";
					if ( tempresult == "SUCCESS") {
						if(silent !== true){		
							alert ( "Element " + name + " imported successfully!");
						}
						result = true;
						if(noviewrefresh !== true){
							Docova.getUIView().refresh({
								loadxml: true,
								loadxsl: true,
								highlightentryid: httpObj.results[0]
							});
						}
					}else{
						var errornode = jQuery(data).find("Error");
						if(silent !== true){		
							if ( errornode.length > 0 ){
								var errortxt = errornode[0].textContent || errornode[0].text || "";
								alert ( "Problem importing the view \r\n" + errortxt);
							}else{
								alert ( "Problem importing the view");
							}
						}
						return false;
					}
				} else {
					if(silent !== true){		
						alert ( "Problem importing the view");
					}
					return false;
				}
			})
			.fail(function () {
				if(silent !== true){		
					alert ( "Problem importing the view");
				}
				return false;
			});
		}
		return result;
	}
	return result;
}

function GetDesignElementInfo(servername, path, type, name, alias){
	var result = false;
	
	var url =   docInfo.PortalWebPath  + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>GETDESIGNELEMENTINFO</Action>";
	request += "<server>" + servername + "</server>";
	request += "<path>" + path + "</path>";
	request += "<elemtype>" + type + "</elemtype>";
	request += "<elemname>" + name + "</elemname>";	
	request += "<elemalias>" + alias + "</elemalias>";
	request += "<direct>1</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	httpObj.returnxml = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {return false; }
	if ( httpObj.results[0] )
	{
		result = httpObj.results[0];		
	}

	return result;	
}

function GetDesignElementNames( apppath, type, options)
{
	var result = false;
	
	if(typeof options == "object"){
		if(options.hasOwnProperty("docovadomino")){
			if(options.docovadomino == "1" || options.docovadomino == "true" || options.docovadomino == 1 || options.docovadomino == true){
				importFromDocova = true;
			}				
		}
	}else if(typeof options == "string"){
		var tempoptions = options.split(",");
		if(tempoptions.indexOf("docovadomino")>-1){
			importFromDocova = true;
		}
	}	

	
	var url =   docInfo.PortalWebPath  + "DesignServices?OpenAgent"
	var request="";
	request += "<Request>";
	request += "<Action>GETDESIGNELEMENTS</Action>";
	request += "<path>" + apppath + "</path>";
	request += "<elemtype>" + type + "</elemtype>";
	request += "<direct>" + (importFromDocova ? "" : "1") +"</direct>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") 
	{
		result = false;	
	}else{
		result = httpObj.results[0];
		if(result != "Error retrieving design elements"){
			result = result.split(":");
			for(var i=0; i<result.length; i++){
				result[i] = result[i].replace(/%3A/g, ":");
			} 
		}
	}
	return result;
}

function ImportDesignElement(designtype) {
    var dlgUrl = "/" + docInfo.NsfName   +  "/dlgImportDesignElementDXL?OpenForm&Type=" + designtype;
    var dlgImportElement = window.top.Docova.Utils.createDialog({
    	id: "dlgImportElement",
        url: dlgUrl,
        title: "Import " + designtype,
        height: 300,
        width: 500,
        useiframe: true,
        sourcedocument: document,
        sourcewindow: window,
        buttons: {
                    "Import": function() {
                        var dlgDoc = window.top.$("#dlgImportElementIFrame")[0].contentWindow.document
                        var servername = ($("#ServerName", dlgDoc).val() || "");
                       
                        var path = $("#Path", dlgDoc).val();

                        var opts = {};
                    	//get a list of response forms that might be needed later
                    	var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent";
                    	var request="";
                    	request += "<Request>";
                    	request += "<Action>GETRESPONSEFORMS</Action>";
                    	request += "<server><![CDATA[" + servername + "]]></server>";
                    	request += "<path><![CDATA[" + path + "]]></path>";
                    	request += "</Request>";	

                    	var httpObj = new objHTTP();
                    	httpObj.supressWarnings = true;
                    	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
                    		responseforms = [];
                    	}else if( httpObj.results[0] ){
                    		responseforms = (httpObj.results[0]).split(",");
                    	}                        
                        designtype = designtype.toLowerCase();
                        if(designtype == "javascriptlibrary" || designtype == "javascript library"){
                        	designtype = "jslibrary";
                        }else if(designtype == "lotusscriptlibrary" || designtype == "lotusscript library" || designtype == "scriptlibrary"){
                        	designtype = "lslibrary";
                        }
                     
						var sourcedesigntype = $("#ElementType", dlgDoc).val();             
						var name = $("#Name", dlgDoc).val().trim();

						var importoptions = jQuery("input[name=ImportOptions]:checked",dlgDoc).map(function(){return $(this).val()}).get().join(",");
						
						var namesuffix = "";
                        
						var result = false;
						if(name && name != ""){
							jQuery(this).parent().find(":button:contains('Import')").prop("disabled", true).addClass("ui-state-disabled");							
							
							var builderform = "";
							if(designtype == "image"){
								builderform = "AppFile";
							}else if(designtype == "jslibrary"){
								builderform = "AppJSBuilder";
								if(sourcedesigntype.toLowerCase() != "lslibrary"){
									namesuffix = "JS";
								}
							}else if(designtype == "lslibrary"){
								builderform = "AppScriptLibraryBuilder";
							}else if(designtype == "agent"){
								builderform = "AppAgentBuilder";
							}else if(designtype == "view" || designtype == "folder"){
								builderform = "NotesView";
							}else if(designtype == "subform"){
								builderform = "AppSubformBuilder";
							}else if(designtype == "form"){
								builderform = "AppFormBuilder";
							}else if(designtype == "page"){
								builderform = "AppPageBuilder";
							}else if(designtype == "outline"){
								builderform = "OutlineBuilder";
							}else if(designtype == "frameset"){
								builderform = "AppLayout";
							}else if(designtype == "assets"){
								builderform = "Assets";
							}
							
							
						//	var elementOK = checkDesignElementEx(designtype.toLowerCase(), name, docInfo.NsfName)
					//		if(elementOK == "NOTOK"){
					//			var msg = "This " + designtype + " name is already in use, please use a different name.";
				//				alert(msg);
				//				
				//				return ;
				//			}
						
							if(designtype == "outline"){
								result = doImportOutline( servername, path, designtype, name, docInfo.NsfName, false, false, importoptions);	
								if(result){
									var designdocurl =  docInfo.PortalWebPath  + builderform +"?openForm&oID=" + result.docid + "&AppID=" + docInfo.AppID + "&TriggerDesignCreation=importElement";								
									jQuery('<iframe style="visibility:hidden;" id="importElement" name="importElement" src="' + designdocurl + '">', dlgDoc).appendTo(jQuery('body', dlgDoc));																		
								}
							}else if(designtype == "view" || designtype == "folder"){
								result = doImportView( servername, path, sourcedesigntype, name, docInfo.NsfName, false, false, importoptions );								
							}else if(designtype == "frameset"){
								result = doImportFrameset( servername, path, designtype, name, docInfo.NsfName, false, false, false, importoptions);
								if(result){
									var designdocurl =  docInfo.PortalWebPath  + builderform +"?openForm&FormUNID=" + result.docid + "&AppID=" + docInfo.AppID + "&TriggerDesignCreation=importElement";
									jQuery('<iframe style="visibility:hidden;" id="importElement" name="importElement" src="' + designdocurl + '">', dlgDoc).appendTo(jQuery('body', dlgDoc));									
								}
							}else if(designtype == "image"){
								result = doImportImage( servername, path, designtype, name, docInfo.NsfName, false, false, importoptions);
							}else if ( designtype == "agent" || designtype == "lslibrary" || designtype == "scriptlibrary"){
								result = ImportElement( servername, path, designtype, name, docInfo.NsfName, false, false, designtype, importoptions);							
							}else if (designtype == "assets") {
								result = doInstallAssets();
							}
							else{						
								result = ImportElement( servername, path, sourcedesigntype, name, docInfo.NsfName, false, false, designtype, importoptions);
								if(result){
									var designdocurl =  docInfo.PortalWebPath  + builderform +"?openForm&FormUNID=" + result.docid + "&AppID=" + docInfo.AppID + "&TriggerDesignCreation=importElement";									
									jQuery('<iframe style="visibility:hidden;" id="importElement" name="importElement" src="' + designdocurl + '">', dlgDoc).appendTo(jQuery('body', dlgDoc));									
								}
							}
							jQuery(this).parent().find(":button:contains('Import')").prop("disabled", false).removeClass("ui-state-disabled");
	
						}else{
							window.top.Docova.Utils.messageBox({icontype: 4, msgboxtype : 0, prompt: "Please choose a design element to be imported.", title: "Select Design Element"});
						}
							
                    },
                    "Close/Cancel": function() {
                        dlgImportElement.closeDialog()
                    }
                }
      });
}


function ImportApp(){
	cancelImport = false; //-- global variable
	
    var dlgUrl = "/" + docInfo.NsfName   + "/dlgImportApp?OpenForm";
    var appid = docInfo.DocID;
    var dlgImportApp = window.top.Docova.Utils.createDialog({																																													
    	id: "dlgImportApp",
        url: dlgUrl,
        title: "Import Application",
        height: 500,
        width: 700, 
        useiframe: true,
        sourcedocument: document,
        sourcewindow: window,
        buttons: {
                    "Import": function() {
                        var dlgDoc = window.top.jQuery("#dlgImportAppIFrame")[0].contentWindow.document
                        var servername = (jQuery("#ServerName", dlgDoc).val() || "");                       
                        var path = jQuery("#Path", dlgDoc).val();
                       
                        var statuselement = jQuery("#importStatus", dlgDoc).get(0);
                        var statusdetailselement = jQuery("#importStatusDetails", dlgDoc).get(0);
                        var progressbarelement = jQuery("#importProgress", dlgDoc).get(0);

						var importoptions = jQuery("input[name=ImportOptions]:checked", dlgDoc).map(function(){return $(this).val()}).get().join(",");
                        
						if(path && path != ""){
							jQuery(this).parent().find(":button:contains('Import')").prop("disabled", true).addClass("ui-state-disabled");							
							doImportApp({'dialogdoc' : dlgDoc, 'servername' : servername, 'appid' : appid,   'path' : path, 'statuselement' : statuselement, 'statusdetailselement' : statusdetailselement,  'progressbarelement' : progressbarelement, 'importoptions' : importoptions});						
						}else{
							window.top.Docova.Utils.messageBox({icontype: 4, msgboxtype : 0, prompt: "Please choose a source application to import.", title: "Select Application"});
						}
							
                    },
                    "Close/Cancel": function() {
                    	cancelImport = true;  //-- toggle global variable in case import is still running
                        dlgImportApp.closeDialog()
                    }
                }
      });	
}

function doImportApp(options){
	if(cancelImport == true){return false;}
	
	var defaultOptns = {
			dialogdoc : null,
			servername : "",
			path : "",
			statuselement : "",
			statusdetailselement : "",
			progressbarelement : "",
			categories : [
			   {"type": "image", "sourcetype": "", "label": "Image", "statustxt" : "Importing Images", "builderform":"AppFile"},
			   {"type": "jslibrary", "sourcetype": "", "label": "JavaScript Library", "statustxt" : "Importing JavaScript Libraries", "builderform":"AppJSBuilder"},   	  
			   {"type": "jslibrary", "sourcetype": "lslibrary", "label": "JavaScript Library from LotusScript", "statustxt" : "Importing JavaScript Libraries from LotusScript", "builderform":"AppJSBuilder"},   	  			   
			   {"type": "lslibrary", "sourcetype": "", "label": "LotusScript Library", "statustxt" : "Importing LotusScript Libraries", "builderform":"AppScriptLibraryBuilder"},			   
			   {"type": "agent", "sourcetype": "", "label": "Agent", "statustxt" : "Importing Agents", "builderform":"AppAgentBuilder"},
			   {"type": "view", "sourcetype": "", "label": "View", "statustxt" : "Importing Views", "builderform":"NotesView"},
			   {"type": "folder", "sourcetype": "", "label": "Folder", "statustxt" : "Importing Folders", "builderform":"NotesView"},			   
			   {"type": "subform", "sourcetype": "", "label": "Subform", "statustxt" : "Importing Subforms", "builderform":"AppSubformBuilder"},	                 
			   {"type": "form", "sourcetype": "", "label": "Form", "statustxt" : "Importing Forms", "builderform":"AppFormBuilder"},
			   {"type": "page", "sourcetype": "", "label": "Page", "statustxt" : "Importing Pages", "builderform":"AppPageBuilder"},
			   {"type": "outline", "sourcetype": "", "label": "Outline", "statustxt" : "Importing Outlines", "builderform":"OutlineBuilder"},
			   {"type": "frameset", "sourcetype": "", "label": "Layout", "statustxt" : "Importing Layouts", "builderform":"AppLayout"},
			   {"type": "assets", "sourcetype": "", "label": "Assets", "statustxt": "Installing Assets", "builderform":"Assets"}
		   	],
		   	curcategory : "",
		   	itemsloaded : false,
		   	itemcount : 0,
		   	itemsprocessed : 0,
		   	curitem : "",
			appid : "",		   	
			apppath : "",
			statushtml : "",
			statusdetailshtml : "",
			pendinghtml : "",
			iframecleanup : [],
			importoptions : ""
		};
	var opts = $.extend({}, defaultOptns, options);	
	
	if(opts.apppath == ""){
		opts.apppath = jQuery("#NotesNsfName").text();
	}
	if(opts.appid == ""){
		var appobj = Docova.getApplication({filepath: opts.apppath});
		if(appobj){
			opts.appid = appobj.appID;
		}
	}
	
	var stopnow = false;
	
	if(!opts.itemsloaded){
		if(typeof opts.importoptions == "object"){
			if(opts.importoptions.hasOwnProperty("docovadomino")){
				if(opts.importoptions.docovadomino == "1" || opts.importoptions.docovadomino == "true" || opts.importoptions.docovadomino == 1 || opts.importoptions.docovadomino == true){
					importFromDocova = true;
				}				
			}
		}else if(typeof opts.importoptions == "string"){
			var tempoptions = opts.importoptions.split(",");
			if(tempoptions.indexOf("docovadomino")>-1){
				importFromDocova = true;
			}
		}			
		
		if(!importFromDocova){
			//get some application properties we may need later
			var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent";
			var request="";
			request += "<Request>";
			request += "<Action>GETDATABASEPROPERTIES</Action>";
			request += "<server><![CDATA[" + opts.servername + "]]></server>";
			request += "<path><![CDATA[" + opts.path + "]]></path>";
			request += "</Request>";	

			var httpObj = new objHTTP();
			httpObj.supressWarnings = true;
			if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
				//do nothing
			}else if( httpObj.results[0] ){
				opts.defaultframeset = httpObj.results[0];
			}
		
			//get a list of response forms that might be needed later
			var url =  docInfo.PortalWebPath + "DesignServices?OpenAgent";
			var request="";
			request += "<Request>";
			request += "<Action>GETRESPONSEFORMS</Action>";
			request += "<server><![CDATA[" + opts.servername + "]]></server>";
			request += "<path><![CDATA[" + opts.path + "]]></path>";
			request += "</Request>";	

			var httpObj = new objHTTP();
			httpObj.supressWarnings = true;
			if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
				responseforms = [];
			}else if( httpObj.results[0] ){
				responseforms = (httpObj.results[0]).split(",");
			}		
		}
		
		//retrieve a list of selected items
		for(var i=0; i<opts.categories.length; i++){
			opts.itemcount ++;
			var findtype = opts.categories[i].type;
			var tempnamesarray = [];
			if (opts.categories[i].type.toLowerCase() == 'assets'){
				tempnamesarray = ['assets'];
			}else{
				if(opts.categories[i].sourcetype !== ""){
					findtype = opts.categories[i].sourcetype;
				}
				tempnamesarray = GetDesignElementNames(opts.path, findtype);
				tempnamesarray.sort();
			}
			if(typeof tempnamesarray == "undefined" || tempnamesarray === null || tempnamesarray === false){
				tempnamesarray = [];
			}

			opts.categories[i].itemnames = [];
			for(var j=0; j<tempnamesarray.length; j++){
				if(jQuery.trim(tempnamesarray[j]) != ""){
					if(tempnamesarray[j] == "$DBIcon"){
						//ignore this element
					}else{
						opts.categories[i].itemnames.push(tempnamesarray[j].trim());
						opts.itemcount ++;
					}
				}
			}
		}	
		opts.itemsloaded = true;
		jQuery(opts.progressbarelement).progressbar({
			max: opts.itemcount,
			complete: function() {
				jQuery(opts.progressbarelement).find(".progress-label").text('Done');
			}
		});
	}
	
	if(opts.statushtml == ""){
		opts.statushtml = "<table style='table-layout: fixed;'><thead><tr><th style='width: 350px; word-wrap: break-word; word-break: break-all;'></th><th  style='width: 100px;'></th></tr></thead><tbody>";
	}
	
	if(opts.curitem && jQuery.trim(opts.curitem) != ""){
		var result = false;					
		var errortext = "";

		//var checkdata = checkDesignElementEx(opts.curcategory.type, opts.curitem, opts.apppath, true)
		var checkdata={};
		checkdata.status = true;
		checkdata.data = "OK";
		if(checkdata && checkdata.status && checkdata.status === true && checkdata.data && checkdata.data === "OK"){
			if(opts.curcategory.type == "view" || opts.curcategory.type == "folder"){
				result = doImportView(opts.docovahomeurl, opts.path, opts.curcategory.type, opts.curitem, opts.apppath, true, true, opts.importoptions);					
			}else if(opts.curcategory.type == "image"){	
				result = doImportImage(opts.docovahomeurl, opts.path, opts.curcategory.type, opts.curitem, opts.apppath, true, true, opts.importoptions);													
			}else if(opts.curcategory.type == "outline"){	
				result = doImportOutline(opts.docovahomeurl, opts.path, opts.curcategory.type, opts.curitem, opts.apppath, true, true, opts.importoptions);
				if(result){
					var iframeid = "importElement" + opts.iframecleanup.length.toString();
					opts.iframecleanup.push(iframeid);					
					var designdocurl =  docInfo.PortalWebPath  + opts.curcategory.builderform +"?openForm&oID=" + result.docid + "&AppID=" + opts.appid + "&TriggerDesignCreation=" + iframeid;								
					jQuery('<iframe id="' + iframeid + '" name="' + iframeid + '" style="visibility:hidden;" src="' + designdocurl + '">', opts.dialogdoc).appendTo(jQuery('body', opts.dialogdoc));																		
				}				
			}else if(opts.curcategory.type == "frameset"){	
				var setasdefault = false;
				if(opts.defaultframeset && opts.defaultframeset.toLowerCase().split("|").some(function(currentvalue){return (this.indexOf(currentvalue) > -1)}, opts.curitem.toLowerCase().split("|"))){
					setasdefault = true;
				}
				result = doImportFrameset(opts.docovahomeurl, opts.path, opts.curcategory.type, opts.curitem, opts.apppath, true, true, setasdefault);	
				if ( result ){
					var iframeid = "importElement" + opts.iframecleanup.length.toString();
					opts.iframecleanup.push(iframeid);					
					var designdocurl = docInfo.PortalWebPath  + opts.curcategory.builderform + "?openForm&FormUNID=" + result.docid + "&AppID=" + opts.appid + "&TriggerDesignCreation=" + iframeid;
					jQuery('<iframe id="' + iframeid + '" name="' + iframeid + '" style="visibility:hidden;" src="' + designdocurl + '">', opts.dialogdoc).appendTo(jQuery('body', opts.dialogdoc));	
				}
			}else if ( opts.curcategory.type == "agent"){
				result = ImportElement(opts.docovahomeurl, opts.path, opts.curcategory.type, opts.curitem, opts.apppath, true, true, opts.importoptions);				
			}else if (opts.curcategory.type == "assets") {
				result = doInstallAssets();
			}
			else{
				var sourcetype = (opts.curcategory.sourcetype !== "" ? opts.curcategory.sourcetype : opts.curcategory.type);
				var targettype = (opts.curcategory.sourcetype !== "" && opts.curcategory.sourcetype !== opts.curcategory.type ? opts.curcategory.type : "");
				
				result = ImportElement(opts.docovahomeurl, opts.path, sourcetype, opts.curitem, opts.apppath, true, true, targettype, opts.importoptions);					
				if(result){				
					var iframeid = "importElement" + opts.iframecleanup.length.toString();
					opts.iframecleanup.push(iframeid);
					var designdocurl = docInfo.PortalWebPath  + opts.curcategory.builderform + "?openForm&FormUNID=" + result.docid + "&AppID=" + opts.appid + "&TriggerDesignCreation=" + iframeid;
					if (opts.curcategory.type == "outline" ){
						designdocurl =  docInfo.PortalWebPath  + opts.curcategory.builderform +"?openForm&oID=" + result.docid + "&AppID=" + opts.appid + "&TriggerDesignCreation=" + iframeid;;
										
									}
					jQuery('<iframe id="' + iframeid + '" name="' + iframeid + '" style="visibility:hidden;" src="' + designdocurl + '">', opts.dialogdoc).appendTo(jQuery('body', opts.dialogdoc));									
				}			
			}			
		}else{
			if(checkdata && checkdata.status && checkdata.status === false && checkdata.data && checkdata.data != ""){
				errortext = checkdata.data;
			}else{
				errortext = "Existing " + opts.curcategory.type + " design element [" + opts.curitem + (opts.curcategory.type == "jslibrary" && opts.curcategory.sourcetype == "lslibrary" ? "JS" : "") + "] found.";
			}
		}
		if(!result){
			if(errortext == ""){
				errortext = "Problems were encountered importing " + opts.curcategory.type + " design element [" + opts.curitem + (opts.curcategory.type == "jslibrary" && opts.curcategory.sourcetype == "lslibrary" ? "JS" : "") + "].";	
			}
			opts.statusdetailshtml += "<p>" + errortext + "</p>";	
		}
		opts.itemsprocessed ++;
		
		opts.statushtml += "<tr>";
		opts.statushtml += "<td style='width: 350px; word-wrap: break-word; word-break: break-all;'>" + opts.curitem + (opts.curcategory.type == "jslibrary" && opts.curcategory.sourcetype == "lslibrary" ? "JS" : "") + "</td>";
		opts.statushtml += "<td><span style='color:" + (result ? "green" : "red") + ";'>" + (result ? "COMPLETE" : "NEEDS REVIEW") + "</span></td>";
		opts.statushtml += "</tr>";		

		opts.curitem = "";
		opts.pendinghtml = "";
	}else if(opts.curcategory && opts.curcategory.itemnames && opts.curcategory.itemnames.length > 0){
		opts.curitem = opts.curcategory.itemnames.shift();
		if(jQuery.trim(opts.curitem) != ""){
			opts.pendinghtml = "<tr>";
			opts.pendinghtml += "<td style='width: 350px; word-wrap: break-word; word-break: break-all;'>" + opts.curitem + (opts.curcategory.type == "jslibrary" && opts.curcategory.sourcetype == "lslibrary" ? "JS" : "") + "</td>";
			opts.pendinghtml += "<td><img src='" + docInfo.imgPath + "jstree/themes/throbber.gif' height='9px' width='9px'></img><span style='color: blue;'>PROCESSING</span></td>";							
			opts.pendinghtml += "</tr>";
		}
	}else if (opts.categories && opts.categories.length > 0){
		opts.curcategory = opts.categories.shift();
		opts.itemsprocessed ++;
		
		opts.statushtml += "<tr><td style='width: 350px; word-wrap: break-word; word-break: break-all;'></td><td></td></tr>";
		opts.statushtml += "<tr>";
		opts.statushtml += "<td style='width: 350px; word-wrap: break-word; word-break: break-all;'><b>" + opts.curcategory.statustxt + ":</b></td>";
		opts.statushtml += "<td></td>";		
		opts.statushtml += "</tr>";

		opts.curitem = "";
		opts.pendinghtml = "";
	}else{
		opts.statushtml += "</tbody></table>";
		stopnow = true;
	}

	jQuery(opts.progressbarelement).progressbar({value: opts.itemsprocessed});
	if (parseInt((opts.itemsprocessed/opts.itemcount * 100), 10) < 100)
		jQuery(opts.progressbarelement).find(".progress-label").text( parseInt((opts.itemsprocessed/opts.itemcount * 100), 10).toString() + "%" );
	else 
		jQuery(opts.progressbarelement).find(".progress-label").text('Done');
	
	//-- update status message html
	jQuery(opts.statuselement).html(opts.statushtml + opts.pendinghtml);
	opts.statuselement.scrollTop = opts.statuselement.scrollHeight;
	
	jQuery(opts.statusdetailselement).html(opts.statusdetailshtml);
	opts.statusdetailselement.scrollTop = opts.statusdetailselement.scrollHeight;
	
	if(stopnow){
		createDefaultOutline({'appid' : opts.appid, 'apppath' : opts.apppath});
		createDefaultLayout({'appid' : opts.appid, 'apppath' : opts.apppath, 'isdefault' : !(opts.defaultframeset && opts.defaultframeset != "")});
		return true;
	}else{
		//recursively call this function with a delay to allow screen refreshes to render
		return setTimeout(function(){doImportApp(opts)}, 150);
	}
}


function ImportAppData() {
//-----Temp function to import data into example application----
    var dlgUrl = docInfo.PortalWebPath + "dlgImportAppData?OpenForm&Type=View";
    var dlgImportData = window.top.Docova.Utils.createDialog({
                id: "dlgImportData",
                url: dlgUrl,
                title: "Import Data",
                height: 400,
                width: 750,
                useiframe: true,
                sourcedocument: document,
                sourcewindow: window,
                buttons: {
                    "Import": function() {
                        var dlgDoc = window.top.$("#dlgImportDataIFrame")[0].contentWindow.document
                        var parameters = [];                       
                        var appid = $("#AppID", dlgDoc).val();
                        $("input,select", dlgDoc).each(function() {
                        	parameters[$(this).attr('name')] = $(this).val();
                        });

						if(appid && parameters['DominoUser'] != ''){
							window.top.Docova.Utils.showProgressMessage("Starting data migrator app on the back-end...");							
							jQuery(this).parent().find(":button:contains('Import')").prop("disabled", true).addClass("ui-state-disabled");							
							setTimeout(function() { doImportAppData(parameters, appid); }, 300);
							dlgImportData.closeDialog();
						}else{
							window.top.Docova.Utils.messageBox({icontype: 4, msgboxtype : 0, prompt: "Please fill out all the form inputs.", title: "Select Application"});
						}                                                
                    },
                    "Close/Cancel": function() {
                        dlgImportData.closeDialog()
                    }
                }
      });
}

function doImportAppData(parameters, appid){
	var result = false;
	
	var url = docInfo.PortalWebPath + "DesignServices?OpenAgent"

//	var appPath = $("#NotesNsfName").text();
	
	var request = "<Request><Action>IMPORTAPPDATA</Action>"
	request += "<Document>"
	request += "<WebServiceUrl><![CDATA[" + parameters['DWSUrl'] + "]]></WebServiceUrl>";
	request += "<DominoUser><![CDATA[" + parameters['DominoUser'] + "]]></DominoUser>";
	request += "<DominoPass><![CDATA[" + parameters['DominoPass'] + "]]></DominoPass>";
	request += "<DominoPath><![CDATA[" + parameters['DocovaHome'] + "]]></DominoPath>";
	request += "<Library><![CDATA[" + parameters['Application'] + "]]></Library>";
	request += "<DocovaHost><![CDATA[" + parameters['DocovaHost'] + "]]></DocovaHost>";
	request += "<DocovaUser><![CDATA[" + parameters['DocovaUser'] + "]]></DocovaUser>";
	request += "<DocovaPass><![CDATA[" + parameters['DocovaPass'] + "]]></DocovaPass>";
	request += "<AttPath><![CDATA[" + parameters['DocovaAtt'] + "]]></AttPath>";
	request += "<DocovaWebPath><![CDATA[" + parameters['DocovaWeb'] + "]]></DocovaWebPath>";
	request += "<AppId><![CDATA[" + appid + "]]></AppId>"
//	request += "<AppPath><![CDATA[" + appPath + "]]></AppPath>"
	request += "</Document>"
	request += "</Request>"
	
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED") {
		window.top.Docova.Utils.hideProgressMessage();
		alert("Data Import Failed: An error occurred attempting to import data. Please check the server and database path.");
	}else if( httpObj.results[0] ){
		window.top.Docova.Utils.hideProgressMessage();
		var job_id = httpObj.results[1];
		result = true;
		var dlgImportProgressBar = window.top.Docova.Utils.createDialog({
            id: "dlgImportProgressBar",
            url: docInfo.PortalWebPath + "dlgImportProgressBar?AppId=" + appid + '&job=' + job_id,
            title: "Importing Data",
            height: 313,
            width: 450,
            useiframe: true,
            buttons: {
            	"Close": function() {
            		dlgImportProgressBar.closeDialog();
            	}
            }
		})
		//alert("Data Import Complete: " + httpObj.results[0] + " documents were imported.");
	}		
	
	return result
}	

function convertDoclinks(){
//---function to call Import Services agent which converts DocLink type data in RT fields so a format that can be used when importing to DOCOVA SE
	delmsgtxt = "You are about to convert all Doclinks in this app for conversion to DOCOVA SE:<br><br>Are you sure?"
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: delmsgtxt, 
		icontype: 2, 
		title: "Convert Doclinks", 
		width:400, 
		msgboxtype: 4,
		onNo: function() {return},
		onYes: function() {
			doConvertDoclinks();
		}
	})
}

function doConvertDoclinks(){
	var result = false;
	var url = docInfo.ServerUrl + "/" + docInfo.AppNsfPath + "/ImportServices?OpenAgent"
	var request = "<Request><Action>CONVERTDOCLINKS</Action>"
	request += "<Document>"
	request += "</Document>"
	request += "</Request>"
	
	var httpObj = new objHTTP();
	httpObj.supressWarnings = true;
	
	if(httpObj.PostData(encodeURIComponent(request), url)) {
		console.log("Status: " + httpObj.status)
		if(httpObj.status == "OK"){
			window.top.Docova.Utils.messageBox({
	 		title: "Doclinks converted.",
	   		prompt: "Doclinks in this app have been converted for import into DOCOVA SE.",
	   		icontype: 4,
	   		msgboxtype: 0,
	   		width: 400
			});
		}else{
			window.top.Docova.Utils.messageBox({
	 		title: "Error convertiing doclinks.",
	   		prompt: "There was at least one issue converting doclinks. Goto the error log for more information.",
	   		icontype: 1,
	   		msgboxtype: 0,
	   		width: 400
			});		
		}
	}
}

function colorToHex(colorname){
   var colorcodes = {
	   white: "#FFFFFF",
	   yellow: "#FFFF00",
	   lime: "#00FF00",
	   aqua: "#00FFFF",
	   blue: "#0000FF",
	   fuchsia: "#FF00FF",
	   red: "#FF0000",
	   silver: "#C0C0C0",
	   black: "#000000",
	   olive: "#808000",
	   green: "#008000",
	   teal: "#008080",
	   navy: "#000080",
	   purple: "#800080",
	   maroon: "#800000",
	   gray: "#808080",
	   system: "#F0F0F0",
	   transparent: "#00FFFFFF"
   }
   
   var result = colorname;
   if(colorcodes.hasOwnProperty(colorname.toLowerCase())){
	   result = colorcodes[colorname.toLowerCase()];
   }
   return result;
}



/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Class: Lexer 
 * Analyzer and formatter for @Formula code to convert it to other formats such as JavaScript
 * If the beautify.js file is loaded, the code can be formatted by calling beautify() on the result
 * Example: 	var LexerObj = new Lexer(); 
 *              var outputTxt = LexerObj.convertCode(formulacode, "JS");
 *              outputTxt = beautify(outputTxt); 	
 *------------------------------------------------------------------------------------------------------------------------------------------- */
 function Lexer() {
	this.pos = 0;
	this.buf = null;
	this.buflen = 0;
	this.depth = 0;
	this.functionarray = [];
	this.currentFieldName =null;
	this.treeonly = false;
	
	// Operator table, mapping operator -> token name
	this.optable = {
		'+' : {name: 'PLUS'},
		'-' : {name: 'MINUS'},
		'*' : {name: 'MULTIPLY'},
		'=' : {name: 'EQUALS'},		
		'/' : {name: 'DIVIDE'},
		'.' : {name: 'PERIOD'},
		'\\' : {name: 'BACKSLASH'},
		':' : {name: 'COLON', 
			followedby : {'=' : {name: 'ASIGNMENT'}}
		},
		'%' : {name: 'PERCENT'},
		'|' : {name: 'PIPE'},
		'!' : {name: 'EXCLAMATION',
			followedby : {'=' : {name: 'NOT_EQUALS'}}		
		},
		'&' : {name: 'AMPERSAND'},
		';' : {name: 'SEMI'},
		',' : {name: 'COMMA'},
		'(' : {name: 'L_PAREN'},
		')' : {name: 'R_PAREN'},
		'<' : {name: 'LESS_THAN', 
			followedby : {'=' : {name: 'LESS_THAN_EQUALS'}}
		},
		'>' : {name: 'GREATER_THAN',
			followedby : {'=' : {name: 'GREATER_THAN_EQUALS'}}					
		},
//		'{' : {name: 'L_BRACE'},
//		'}' : {name: 'R_BRACE'},
		'[' : {name: 'L_BRACKET'},
		']' : {name: 'R_BRACKET'}
	};
	
	this.kwtable = [
		'DEFAULT',
		'ENVIRONMENT',
		'FIELD',
		'REM',
		'SELECT'
	];


	this.functable = {
	    'ACOS'     : { replacewith: 'ACos'},	
	    'ADJUST'     : { replacewith: 'Adjust'},
	    'ASCII'     : { replacewith: 'Asii'},
	    'ASIN'     : { replacewith: 'Asin'},
	    'ATAN'     : { replacewith: 'ATan'},
	    'ATAN2'     : { replacewith: 'ATan2'},
	    'BEGINS'     : { replacewith: 'Begins'},
	    'CHAR'     : { replacewith: 'Char'},
	    'CLIENTTYPE'     : { replacewith: 'ClientType'},
	    'COMPARE'     : { replacewith: 'Compare'},
	    'CONTAINS'     : { replacewith: 'Contains'},
	    'COS'     : { replacewith: 'Cos'},
	    'COUNT'     : { replacewith: 'Count'},
	    'DATE'     : { replacewith: 'Date'},
        'DAY'     : { replacewith: 'Day'},
        'DBCALCULATE'     : { replacewith: 'DbCalculate'},
        'DBLOOKUP'     : { replacewith: 'DbLookup'},
        'DBCOLUMN'     : { replacewith: 'DbColumn'},
        'DBNAME'     : { replacewith: 'DbName'},
        'DOCUMENTUNIQUEID'     : { replacewith: 'DocumentUniqueID'},
        'ELEMENTS'     : { replacewith: 'Elements'},
        'ENDS'     : { replacewith: 'Ends'},
        'ERROR'     : { replacewith: 'Error'},
        'EXP'     : { replacewith: 'Exp'},
        'EXPLODE'     : { replacewith: 'Explode'},
        'FAILURE'     : { replacewith: 'Failure'},
        'FALSE'     : { replacewith: 'False'},
        'GETDOCFIELD'     : { replacewith: 'GetDocField'},
        'GETFIELD'     : { replacewith: 'GetField'},
        'GETPROFILEFIELD'     : { replacewith: 'GetProfileField'},
        'HOUR'     : { replacewith: 'Hour'},
        'IMPLODE'     : { replacewith: 'Implode'},
        'ISERROR'     : { replacewith: 'IsError'},
        'ISNEWDOC'     : { replacewith: 'IsNewDoc'},
        'ISNULL'     : { replacewith: 'IsNull'},
        'ISNUMBER'     : { replacewith: 'IsNumber'},
        'ISTEXT'     : { replacewith: 'IsText'},
        'ISTIME'     : { replacewith: 'IsTime'},
        'SETRETURNVAL'     : { replacewith: 'SetReturnVal'},
        'GETRETURNVAL'     : { replacewith: 'GetReturnVal'},
        'ISUNAVAILABLE'     : { replacewith: 'IsUnavailable'},
        'LEFT'     : { replacewith: 'Left'},
        'LENGTH'     : { replacewith: 'Length'},
        'LN'     : { replacewith: 'Ln'},
        'LOWERCASE'     : { replacewith: 'LowerCase'},
        'MINUTE'     : { replacewith: 'Minute'},
        'MONTH'     : { replacewith: 'Month'},
        'NAME'     : { replacewith: 'Name'},
        'NEWLINE'     : { replacewith: 'NewLine'},
        'NO'     : { replacewith: 'No'},
        'NOTHING'     : { replacewith: 'Nothing'},
        'NOW'     : { replacewith: 'Now'},
        'PI'     : { replacewith: 'Pi'},
        'POWER'     : { replacewith: 'Power'},
	    'PROPERCASE'     : { replacewith: 'ProperCase'},
	    'RANDOM'     : { replacewith: 'Random'},
		'REPEAT'     : { replacewith: 'Repeat'},
		'REPLACE'     : { replacewith: 'Replace'},
		'REPLACESUBSTRING'     : { replacewith: 'ReplaceSubstring'},
		'ROUND'     : { replacewith: 'Round'},
		'SECOND'     : { replacewith: 'Second'},
		'SETDOCFIELD'     : { replacewith: 'SetDocField'},
		'SETFIELD'     : { replacewith: 'SetField'},
		'SETPROFILEFIELD'     : { replacewith: 'SetProfileField'},
		'SIGN'     : { replacewith: 'Sign'},
		'SIN'     : { replacewith: 'Sin'},
		'SQRT'     : { replacewith: 'Sqrt'},
		'SUBSET'     : { replacewith: 'Subset'},
		'SUCCESS'     : { replacewith: 'Success'},
		'TAN'     : { replacewith: 'Tan'},
		'TEXT'     : { replacewith: 'Text'},
		'TEXTTONUMBER'     : { replacewith: 'TextToNumber'},
		'TODAY'     : { replacewith: 'Today'},
		'TOMORROW'     : { replacewith: 'Tomorrow'},
		'TONUMBER'     : { replacewith: 'ToNumber'},
		'TRIM'     : { replacewith: 'Trim'},
		'TRUE'     : { replacewith: 'True'},
		'UNAVAILABLE'     : { replacewith: 'Unavailable'},
		'UPPERCASE'     : { replacewith: 'UpperCase'},
		'USERNAME'     : { replacewith: 'UserName'},
		'V3USERNAME'     : { replacewith: 'V3UserName'},
		'WEEKDAY'     : { replacewith: 'Weekday'},
		'YEAR'     : { replacewith: 'Year'},
		'YES'     : { replacewith: 'Yes'},
		'YESTERDAY'     : { replacewith: 'Yesterday'}
	};

	this.constanttable = [
	    "[A]",
		"[ABBREVIATE]",
		"[ADDRESS821]",
		"[ACCENTSENSITIVE]",
		"[ACCENTINSENSITIVE]",
		"[ACCESSLEVEL]",
		"[ALLINRANGE]",
		"[C]",
		"[CANONICALIZE]",
		"[CASESENSITIVE]",
		"[CASEINSENSITIVE]",
		"[CHOOSEDATABASE]",
		"[CLEAR]",
		"[CLOSEWINDOW]",
		"[CN]",
		"[COMPOSE]",
		"[COMPOSERESPONSE]",		
		"[CREATEDOCUMENTS]",
		"[CREATEPERSONALAGENTS]",
		"[CREATEPERSONALFOLDERSANDVIEWS]",
		"[CREATELOTUSSCRIPTJAVAAGENTS]",
		"[CREATESHAREDFOLDERSANDVIEWS]",
		"[CUSTOM]",
		"[DESCENDING]",
		"[DELETEDOCUMENTS]",
		"[EDITCLEAR]",
		"[EDITDOCUMENT]",
		"[EDITGOTOFIELD]",
		"[EDITPROFILE]",
		"[EDITPROFILEDOCUMENT]",
		"[FAILSILENT]",
		"[FILEPRINT]",
		"[FILECLOSEWINDOW]",
		"[FILESAVE]",
		"[G]",
		"[HIERARCHYONLY]",
		"[I]",
		"[INCLUDEDOCLINK]",
		"[LOCALBROWSE]",
		"[LP]",
		"[MAILADDRESS]",
		"[MAILSEND]",
		"[MAILFORWARD]",
		"[MOVETOFOLDER]",
		"[NAVIGATENEXT]",
		"[NAVIGATEPREV]",
		"[NOSORT]",
		"[O]",
		"[OK]",
		"[OKCANCELCOMBO]",
		"[OKCANCELEDIT]",
		"[OKCANCELEDITCOMBO]",
		"[OKCANCELLIST]",
		"[OKCANCELLISTMULT]",
		"[OPENDOCUMENT]",
		"[OPENINNEWWINDOW]",
		"[OPENPAGE]",
		"[OPENVIEW]",
		"[OU1]",
		"[OU2]",
		"[OU3]",
		"[OU4]",
		"[P]",
		"[PASSWORD]",
		"[PHRASE]",
		"[PITCHSENSITIVE]",
		"[PITCHINSENSITIVE]",
		"[PRIORITYHIGH]",
		"[Q]",
		"[READPUBLICDOCUMENTS]",
		"[REFRESHFRAME]",
		"[RELOADWINDOW]",
		"[REMOVEFROMFOLDER]",
		"[REPLICATEORCOPYDOCUMENTS]",
		"[RETURNDOCUMENTUNIQUEID]",
		"[S]",
		"[SIGN]",
		"[SINGLE]",
		"[TEXTONLY]",
		"[TOAT]",
		"[TODATATYPE]",
		"[TOFIELD]",
		"[TOFORM]",
		"[TOKEYWORD]",
		"[TOOC]",
		"[TOSYNTAX]",
		"[TOOLSCATEGORIZE]",
		"[TOOLSRUNMACRO]",
		"[VIEWREFRESHFIELDS]",
		"[VIEWEXPANDALL]",
		"[VIEWCOLLAPSEALL]",
		"[WRITEPUBLICDOCUMENTS]",
		"[YESNO]",
		"[YESNOCANCEL]"
	];
	

	/* 
	 * sets the current field name on the parser such that @ThisName can be translated properly
	 *
	*/

	this.setCurrentFieldName = function ( fieldname ){
		this.currentFieldName = fieldname;
	}



	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: input 
	 * Initialize the Lexer's buffer. This resets the lexer's internal state and subsequent tokens will be returned 
	 * starting with the beginning of the new buffer.
	 * Inputs: buf - string - input string containing source code
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this.input = function (buf) {
		this.pos = 0;
		this.buf = (buf ? buf : "");
		this.buflen = (buf ? buf.length : 0);
		this.depth = 0;
	}

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: tokenize
	 * Converts the input buffer to a token stream
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this.tokenize = function() {
		var tokenList = [];	

		var tokenObj = null;
		do{
			tokenObj = this._token();
			if(tokenObj){
				tokenList.push(tokenObj);
			}
		}while (tokenObj); 	
		
		return tokenList;
	}

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: _token
	 * Get the next token from the current buffer. A token is an object with the following properties:
	 *    - name: name of the pattern that this token matched (taken from rules).
	 *    - value: actual string value of the token.
	 *    - pos: offset in the current buffer where the token starts.
	 * 	If there are no more tokens in the buffer, returns null. In case of an error throws Error.
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this._token = function () {
		var curpos = this.pos;
		
		this._skipnontokens();
		if (this.pos >= this.buflen) {
			return null;
		}

		// The char at this.pos is part of a real token. Figure out which.
		var c = this.buf.charAt(this.pos);
		if ( c== "$"){
			this.pos++;
			if (this.buf.charAt(this.pos) == '$')
				c += this.buf.charAt(this.pos);
			else
				c = this.buf.charAt(this.pos);
		}
		// Look it up in the table of operators
		var op = this.optable[c];
		if (typeof op !== 'undefined') {
			if(op.followedby){
				for (var childop in op.followedby) {
   						if (op.followedby.hasOwnProperty(childop)) {
   							if(this.buf.charAt(this.pos + 1) === childop){
    								c += childop;
    								break;
    							}
    						}
					}					
			}
			
			//check for special case of constant
			if(c === "[" && this._isconstant()){
				return this._process_constant();
			//check for special case of literal date				
			}else if( c === "[" && this._isdate()){
				return this._process_date();
			}else if(c === "("){
				this.depth ++;
			}else if(c === ")"){
				if(this.depth > 0){
					this.depth --;
				}
			}
				
			this.pos = this.pos + c.length;
				
			return {
				name : "OPERATOR",
				value : c,
				pos : curpos,
				depth : this.depth
			};
		} else {
				// Not an operator - so it's the beginning of another token.
				if (c == '$$' || c == '@'){
					return this._process_function();
				}else if(this._isalpha(c)) {
					return this._process_identifier();
				} else if (this._isdigit(c)) {
					return this._process_number();
				} else if (c === '"' || c === "'") {
					return this._process_quote(c);
				} else if (c === '{') {
					return this._process_quote('}');					
				} else {
					if(console){
 						console.log("Error in Lexer>tokenize>_token: unidentified token at buffer position [" + this.pos + "] character [" + c + "] character code [" + c.charCodeAt(0) + "].");
 					}
				}
		}
	}

	this._isnewline = function (c) {
		var charcode = c.charCodeAt(0);
 		return (c === '\r' || c === '\n' || charcode == 10 || charcode == 12 || charcode == 13 );
	}

	this._isdigit = function (c) {
		return c >= '0' && c <= '9';
	}

	this._isalpha = function (c) {
		var exceptions = [215, 247, 697, 698, 699, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716, 717, 718, 719, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732, 733, 734, 735, 736, 737, 738, 739, 740 ,750, 757, 758, 760, 884, 890, 894];
		return (c >= 'a' && c <= 'z') ||
		(c >= 'A' && c <= 'Z') ||
		c === '_' || c === '$' ||
		(c.charCodeAt(0) > 192 && exceptions.indexOf(c.charCodeAt(0)) == -1);
	}

	this._isalphanum = function (c) {	
		var exceptions = [215, 247, 697, 698, 699, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716, 717, 718, 719, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732, 733, 734, 735, 736, 737, 738, 739, 740 ,750, 757, 758, 760, 884, 890, 894];
		return (c >= 'a' && c <= 'z') ||
		(c >= 'A' && c <= 'Z') ||
		(c >= '0' && c <= '9') ||		
		c === '_' || c === '$' ||
		(c.charCodeAt(0) > 192 && exceptions.indexOf(c.charCodeAt(0)) == -1);	
	}
	
	this._isconstant = function () {
		var result = false;
		if(this.buf.charAt(this.pos) != "["){ return result;}
		
		var endpos = this.pos;	
		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
			endpos++;
		}
		if(endpos === this.pos){return result;}	
		
		var searchconst = this.buf.substring(this.pos, endpos + 1);
		if(this.constanttable.indexOf(searchconst.toUpperCase()) > -1){
			result = true;
		}

		return result;		
	}
	
	this._isdate = function () {
		var result = false;
		if(this.buf.charAt(this.pos) != "["){ return result;}
		
		var endpos = this.pos;	
		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
			endpos++;
		}
		if(endpos === this.pos){return result;}	
		
		var searchdate = this.buf.substring(this.pos + 1, endpos);
		//-- look for mm/dd/yyyy or mm/dd/yy format			
		if(searchdate.match(/^(0?[1-9]|1[0-2])\/(0?[1-9]|[1-2][0-9]|3[0-1])\/(\d{2}|\d{4})$/g)){
			result = true;
		//-- look for dd/mm/yyyy or dd/mm/yy format
		}else if(searchdate.match(/^(0?[1-9]|[1-2][0-9]|3[0-1])\/(0?[1-9]|1[0-2])\/(\d{2}|\d{4})$/g)){
			result = true;
		//-- look for yyyy/mm/dd format
		}else if(searchdate.match(/^(\d{4})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[1-2][0-9]|3[0-1])$/g)){
			result = true;
		}

		return result;		
	}	
	
	
	this._process_constant = function () {
		var tok = null;
		if(this.buf.charAt(this.pos) != "["){ return tok;}
		
		var endpos = this.pos;	
		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
			endpos++;
		}
		if(endpos === this.pos){return tok;}
		
		var searchconst = this.buf.substring(this.pos, endpos + 1);
		if(this.constanttable.indexOf(searchconst.toUpperCase()) > -1){
			tok = {
				name : 'CONSTANT',
				value : '"' + searchconst + '"',
				pos : this.pos
			};
			this.pos = endpos + 1;
		}

		return tok;		
	}	
	
	this._process_date = function () {	
		var tok = null;

		if(this.buf.charAt(this.pos) != "["){ return tok;}
		var endpos = this.pos;	
		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
			endpos++;
		}
		if(endpos === this.pos){return tok;}	
		
		var searchdate = this.buf.substring(this.pos + 1, endpos);
		//-- look for mm/dd/yyyy or mm/dd/yy format
		if(searchdate.match(/^(0?[1-9]|1[0-2])\/(0?[1-9]|[1-2][0-9]|3[0-1])\/(\d{2}|\d{4})$/g)){
			var matches = searchdate.split("/");
			tok = {
				name : 'DATE',
				value : ("2000").slice(0, Math.max(0, 4-matches[2].length)) + matches[2] + '-' + matches[0] + '-' + matches[1],
				pos : this.pos
			};
			this.pos = endpos + 1;			
		//-- look for dd/mm/yyyy or dd/mm/yy format			
		}else if(searchdate.match(/^(0?[1-9]|[1-2][0-9]|3[0-1])\/(0?[1-9]|1[0-2])\/(\d{2}|\d{4})$/g)){
			var matches = searchdate.split("/");
			tok = {
				name : 'DATE',
				value : ("2000").slice(0, Math.max(0, 4-matches[2].length)) + matches[2] + '-' + matches[1] + '-' + matches[0],
				pos : this.pos
			};
			this.pos = endpos + 1;
		//-- look for yyyy/mm/dd format
		}else if(searchdate.match(/^(\d{4})\/(0?[1-9]|1[0-2])\/(0?[1-9]|[1-2][0-9]|3[0-1])$/g)){
			var matches = searchdate.split("/");
			tok = {
				name : 'DATE',
				value :	("2000").slice(0, Math.max(0, 4-matches[0].length)) + matches[0] + '-' + matches[1] + '-' + matches[2],
				pos : this.pos
			};
			this.pos = endpos + 1;
		}
		
		return tok;
	}

	this._process_number = function () {
		var endpos = this.pos + 1;
		while (endpos < this.buflen &&
			this._isdigit(this.buf.charAt(endpos))) {
			endpos++;
		}

		var tok = {
			name : 'NUMBER',
			value : this.buf.substring(this.pos, endpos),
			pos : this.pos
		};
		this.pos = endpos;
		return tok;
	}

	this._process_comment = function () {
		var endpos = this.pos + 2;
		// Skip until the end of the line
		var c = this.buf.charAt(this.pos + 2);
		while (endpos < this.buflen &&
			!this._isnewline(this.buf.charAt(endpos))) {
			endpos++;
		}

		var tok = {
			name : 'COMMENT',
			value : this.buf.substring(this.pos, endpos),
			pos : this.pos
		};
		this.pos = endpos + 1;
		return tok;
	}

	this._process_identifier = function () {
		var endpos = this.pos + 1;
		while (endpos < this.buflen &&
			this._isalphanum(this.buf.charAt(endpos))) {
			endpos++;
		}
		
		var tempval = this.buf.substring(this.pos, endpos);
		var tempname = (this.kwtable.indexOf(tempval) === -1 ? "IDENTIFIER" : "KEYWORD");

		var tok = {
			name : tempname,
			value : tempval,
			pos : this.pos
		};
		this.pos = endpos;
		return tok;
	}
	
	this._process_function = function () {
		var endpos = this.pos + 1;
		while (endpos < this.buflen &&
			this._isalphanum(this.buf.charAt(endpos))) {
			endpos++;
		}

		var tok = {
			name : 'FUNCTION',
			value : this.buf.substring(this.pos + 1, endpos),
			pos : this.pos
		};
		this.pos = endpos;
		return tok;
	}	

	this._process_quote = function (quotechar) {
		var tok = false;
		var lastpost = this.pos;
		var curpos = this.pos;
		var end_index = -1;
		var keeplooking = true;

		do {
			//Find the next quote.
			lastpos = curpos;
			curpos = this.buf.indexOf(quotechar, curpos + 1);
			if (curpos === -1) {
				keeplooking = false;
				if(console){
					console.log('Lexer Error: Unterminated quote at ' + this.pos);	
				}
			} else {
				//check for string literal identifier before the quote
				var isliteral = false;				
				if(this.buf.charAt(curpos - 1) == "\\"){
					var backslashcount = 0;
					var temppos = curpos - 1;
					while(this.buf.charAt(temppos) == "\\"){
						backslashcount ++;
						temppos --;
					}
					//--check if we have an odd number of backslashes which would cause the quote to be a literal
					if((backslashcount % 2) > 0){
						isliteral = true;
					}
				}
				if(!isliteral){
					var tempstring = "";
					if(this.pos + 1 <= curpos){
						tempstring = this.buf.substring(this.pos+1, curpos);
					}
					var isapos = quotechar == "'" ? true : false;
					keeplooking = false;
					tok = {
						name : 'QUOTEDSTRING',
						value : tempstring,
						isapos : isapos,
						pos : this.pos
					};								
					this.pos = curpos + 1; //increment buffer position					
				}
			}		
		} while(keeplooking);	
		
		return tok;
	}

	this._skipnontokens = function () {
		while (this.pos < this.buflen) {
			var c = this.buf.charAt(this.pos);
			if (c == ' ' || c == '\t' || c == '\r' || c == '\n') {
				this.pos++;
			} else {
				break;
			}
		}
	}

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: tokenListToTree
	 * Converts a token list to a tree such that child operations are nested beneath the parent operation
	 * Enables easier parsing of content within the same level (eg. function parameters, vs. child functions).
	 * Inputs: tokenList - array - array of token objects
	 * Returns: rootToken - token object at top of tree
	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
	this.tokenListToTree = function(tokenList) {
		var depth = 0;
		
		var rootToken = {
			name: "ROOT",
			value: "",
			children: [],
			parent : null
		};
		var parentToken = rootToken;
	
		for(var i=0; i<tokenList.length; i++){
			var token = tokenList[i];
			var newtoken = {
				name: token.name, 
				value: token.value,
				children : null,
				isapos : token.isapos,
				parent : null
			}
			
			if(newtoken.name == "OPERATOR"){			
				 if(newtoken.value == ")"){
					var temptoken = (parentToken.parent ? parentToken.parent : parentToken);		
					depth --;									 	
					var parentToken = temptoken; //-- pop up one level so that this parenthesis is at the same level as its match		
				}
			}

			newtoken.parent = parentToken;
			if(parentToken.children === null){
				parentToken.children = new Array();
			}					
			parentToken.children.push(newtoken);

			//console.log(("     ").repeat(depth) + " -> " + newtoken.value);

			//-- reset parent token for functions and brackets  
			if(newtoken.name == "FUNCTION" || (newtoken.name == "OPERATOR" && newtoken.value == "(")){
				depth ++;
				parentToken = newtoken;
			}else if(newtoken.name == "OPERATOR" && newtoken.value == ")" && parentToken.name == "FUNCTION"){
				depth --;
				parentToken = (parentToken.parent ? parentToken.parent : parentToken);
			}							
		}	
		
		return rootToken;
	}//--end tokenListToTree

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: _walkTokenTree
	 * Walks the token tree from top to bottom, left to right, returning either the original content, or 
	 * if a convertFunction parameter is passed, the converted code.  A recursive function.
	 * Inputs: tokenNode - current token node to be traversed
	 *              convertFunction - function (optional) - function to call to perform processing on the token node
	 *                                              and possibly its children.  Function must accept the token node as its input
	 *              depth - integer (optional) - counter used to guage how many recursive calls have been made
	 * Returns: string - original code or converted code in text format
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this._walkTokenTree = function (tokenNode, convertFunction, depth){	
		var tempdepth = (typeof depth == 'undefined' ? 0 : depth);
		var tempresult = "";
		var returnresult = "";

		if(typeof tokenNode != 'undefined' && tokenNode != null){
			if(typeof convertFunction === 'function'){
				convertFunction.call(this, tokenNode);
			} 

			if(this.treeonly){
				tempresult += "\n" + ("    ").repeat(depth) + "-> " + tokenNode.value;
			}

			if(tokenNode.children != null){
				tempdepth ++;				
				for(var c=0; c<tokenNode.children.length; c++){
					tempresult  += this._walkTokenTree(tokenNode.children[c], convertFunction, tempdepth);
				} 
			}
			returnresult = (this.treeonly ? "" : tokenNode.value) + tempresult;			
		}
		return returnresult;
	}
	
	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: _getIdentifiers
	 * Walks the token list from left to right, returning a list of identifiers.  
	 * Inputs: tokenList - array of tokens
	 * Returns: object array - array of identifiers and their type (field or variable)
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this._getIdentifiers = function (tokenList){	
		var idlist = {};
		
		//-- loop through the tokens looking for variables referenced by the @Set function 		
		for (var i=0; i<tokenList.length - 2; i++){
			if(tokenList[i].name == "FUNCTION" && tokenList[i].value.toUpperCase() == "SET"){
				if(tokenList[i+2].name == "QUOTEDSTRING"){
					var id = tokenList[i+2].value.toUpperCase();	
					if(id != ""){
						if(! idlist[id]){
							idlist[id] = {'type' : ""}; 
						}
						//-- no type assigned yet
						if(idlist[id].type == ""){
							idlist[id].type = "VARIABLE";
						}
					}				
				}
			}	
		}				
		
		//-- loop through tokens looking for identifiers
		for (var i=0; i<tokenList.length; i++){
			if(tokenList[i].name == "IDENTIFIER"){
				var id = tokenList[i].value.toUpperCase();
				if(id != ""){
					if(! idlist[id]){
						idlist[id] = {'type' : ""}; 
					}
					//-- no type assigned yet
					if(idlist[id].type == ""){
						//-- check if this is a field assignment
						if(i>0 && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value == "FIELD"){
							idlist[id].type = "FIELD";
						}
					}
				}		
			}	
		}

		//-- loop through the tokens a second time looking for anything being assigned without the FIELD identifier (we can assume these are variables)		
		for (var i=0; i<tokenList.length; i++){
			if(tokenList[i].name == "IDENTIFIER"){
				var id = tokenList[i].value.toUpperCase();	
				//-- no type assigned yet
				if(idlist[id].type == ""){
					if((i<tokenList.length - 1) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == ":="){
							idlist[id].type = "VARIABLE";						
					}
				}
			}	
		}

		//-- loop through the identifiers and look for any that have not yet been assigned a type
		for(idtag in idlist){
			if(idlist.hasOwnProperty(idtag) && idlist[idtag].type == ""){
				//-- if not assigned a type yet, assume it is a field
				idlist[idtag].type = "FIELD";
			}
		}	
				
		return idlist;
	}//--end _getIdentifiers

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: _adjustTokenList
	 * Walks the token list from left to right, and makes some adjustments that are needed before the tree
	 * can be generated.  eg. converts field references to function calls, inserts missing function brackets
	 * Inputs: tokenList - array of tokens
	 *              idList - object list of identifiers (field or variable)
	 * Returns: token object array - array of token objects
	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
	this._adjustTokenList = function(tokenList, idList){
		var lparencount = 0;
		var rparencount = 0;
		var assignlparencount = 0;
 		var assignrparencount = 0;
		var assignmentactive = false;
		
		for(var i=0; i<tokenList.length; i++){
			var token = tokenList[i];
			
			if(token.name == "IDENTIFIER"){			
				var idname = token.value;
				if(idList && idList[idname.toUpperCase()]){
					if(idList[idname.toUpperCase()].type == "FIELD"){
						var isassignment = false;
				
						//-- look for prior token to see if this is a field assignment
						if(!assignmentactive && i>0 && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "FIELD"){
							assignmentactive = true;
							isassignment = true;
							assignlparencount = 0;
					 		assignrparencount = 0;
						}
						
						tokenList[i].name = "QUOTEDSTRING";
						tokenList[i].value =  idname;
					
						tokenList.splice(i, 0, {
							name: "FUNCTION",
							value: (isassignment ? "SetDocField" : "GetDocField"),
							pos: -1,
							depth : -1
						});
						i++;
						
						tokenList.splice(i, 0, {
							name: "OPERATOR",
							value: "(",
							pos: -1,
							depth : -1
						});
						i++;
						
						tokenList.splice(i, 0, {
							name: "FUNCTION",
							value: "DocumentUniqueID",
							pos: -1,
							depth : -1
						});
						i++;
	
						tokenList.splice(i, 0, {
							name: "OPERATOR",
							value: "(",
							pos: -1,
							depth : -1
						});
						i++;			

						tokenList.splice(i, 0, {
							name: "OPERATOR",
							value: ")",
							pos: -1,
							depth : -1
						});
						i++;		
						
						tokenList.splice(i, 0, {
							name: "OPERATOR",
							value: ";",
							pos: -1,
							depth : -1
						});
						i++;																	
						
						
						//-- this is where the existing identity turned field name should be positioned

						
						//-- only add closing parenthesis if getfield, otherwise we need to wait
						if(! isassignment){
							tokenList.splice(i+1, 0, {
								name: "OPERATOR",
								value: ")",
								pos: -1,
								depth : -1
							});
							i++;
						}
					}
				}
			}else if(token.name == "FUNCTION"){
				//-- check that function is followed by parenthesis (as some @functions may not have them yet)
				var addparens = true;
				if(i+1 < tokenList.length){
					if(tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
						addparens = false;
					}
				}
				if(addparens){
						tokenList.splice(i+1, 0, {
							name: "OPERATOR",
							value: "(",
							pos: -1,
							depth : -1
						});
						i++;				
				
						tokenList.splice(i+1, 0, {
							name: "OPERATOR",
							value: ")",
							pos: -1,
							depth : -1
						});
						i++;									
				}		
				//Convert some function names
				if(token.value.toUpperCase() == "POSTEDCOMMAND"){
					token.value = "Command";
				}else if(token.value.toUpperCase() == "V2IF"){
					token.value = "If";
				}
			}else if(token.name == "CONSTANT"){
				//-- need to adjust @Command compose for responses
				if(token.value.toUpperCase() == '"[COMPOSE]"'){
					//--responseforms is a global variable that may or may not be set to the names of response forms
					if(typeof responseforms !== "undefined" && Array.isArray(responseforms) && responseforms.length > 0){
						var a=i+2;
						var cont = true;					
						while(a<tokenList.length && tokenList[a].name !== "NEWLINE" && cont){
							if(tokenList[a].name == "ASSIGNMENT"){
								cont = false;
							}else if(tokenList[a].name == "OPERATOR" && tokenList[a].value == ")"){
								cont = false;
							}
														
							if(tokenList[a].name == "QUOTEDSTRING"){
								var formname = tokenList[a].value;
								if(formname.slice(0,1) == '"' && formname.slice(-1) == '"'){
									formname = formname.slice(1, formname.length-1);
								}						
								if(responseforms.indexOf(formname) >-1){						
									token.value = '"[ComposeResponse]"';
									cont = false;
								}
							}
							a++;
						}					
					}
				}
			}else if(token.name == "KEYWORD"){
				//-- in the case of the REM keyword we want to switch the following string to a comment
				if(token.value == "REM"){
					if(i+1 < tokenList.length){
						if(tokenList[i+1].name == "QUOTEDSTRING"){
							tokenList[i+1].name = "COMMENT";
						}
					}					
				}
			}else if(token.name == "OPERATOR"){
				//-- check for parenthesis so we can keep a running count
				if(token.value == "("){
					lparencount ++;
					assignlparencount ++;
				}else if(token.value == ")"){
					rparencount ++;
					assignrparencount ++;
				}
				
				//-- special checks to trap for redefining FIELD fieldname structure to a function call
				if(assignmentactive){
					//-- check for assignment operator
					if(tokenList[i].value == ":="){
						tokenList[i].value = ";"; //remap to a formula separator
					//-- check for separator character
					}else if(tokenList[i].value == ";"){
						//-- check that we are not inside a function call
						if(assignlparencount == assignrparencount){
							//-- insert a closing parenthesis to close of setfield function call
							tokenList.splice(i, 0, {
								name: "OPERATOR",
								value: ")",
								pos: -1,
								depth : -1
							});
							i++;

							assignmentactive = false;
						}
					//-- check for end of function
					}else if(tokenList[i].value == ")"){
						//-- check that we are ending a function call
						if(assignlparencount == (assignrparencount - 1)){
							//-- insert a closing parenthesis to close of setfield function call
							tokenList.splice(i, 0, {
								name: "OPERATOR",
								value: ")",
								pos: -1,
								depth : -1
							});
							i++;

							assignmentactive = false;
						}
					}							
				} 
			} 
		}//--end of loop

		return tokenList;
	}//--end _adjustTokenList


	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: translateToJS
	 * Performs conversion on a token node and its immediate children to generate JavaScript equivalent
	 * Inputs: tokenNode - token - current token in the tree to proces
	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
	this.translateToJS = function(tokenNode) {	
			var lparen = 0;
			var rparen = 0;
			var paramcount = 0;
			var arrayopen = false;
			var priortoken = null;

			//-- special case of @If or @Do functions which needs its parameters passed as an array of functions
			if(tokenNode.name == "FUNCTION" && (tokenNode.value == "If" || tokenNode.value == "Do" || tokenNode.value == "DoWhile" || tokenNode.value == "While" || tokenNode.value == "For" )){	
				//-- need to insert an array start bracket after the first open bracket
				tokenNode.children[0].children.splice(0, 0, {
					name: "OPERATOR", 
					value: "[", 
					parent: tokenNode, 
					children: null
				});

				//-- need to insert an anonymous function
				tokenNode.children[0].children.splice(1, 0, {
					name: "SPECIAL", 
					value: "function(){ return (", 
					parent: tokenNode, 
					children: null
				});				

				for(var c=2; c<tokenNode.children[0].children.length - 1; c++){
					//-- look for a semi-colon as this will be a parameter separator
					if(tokenNode.children[0].children[c].value == ";"){
						//-- need to close off previous anonymous function
						tokenNode.children[0].children.splice(c, 0, {
							name: "SPECIAL", 
							value: ");}", 
							parent: tokenNode, 
							children: null
						});
						c++;  //iterate by one since we have inserted a record into the array								
						
						//-- need to insert an anonymous function
						tokenNode.children[0].children.splice(c+1, 0, {
							name: "SPECIAL", 
							value: "function(){ return (", 
							parent: tokenNode, 
							children: null
						});
						c++;  //iterate by one since we have inserted a record into the array
					}			
				}				
				
				//-- need to close off previous anonymous function
				tokenNode.children[0].children.push({
					name: "SPECIAL", 
					value: ");}", 
					parent: tokenNode, 
					children: null
				});
							
				//-- need to insert an array end bracket before the close bracket
				tokenNode.children[0].children.push({
					name: "OPERATOR", 
					value: "]", 
					parent: tokenNode, 
					children: null
				});			
			}//--end of @If @Do @DoWhile @While @For function check

			
			//-- special case of @Select function which needs its parameters passed as an array
			if(tokenNode.name == "FUNCTION" && tokenNode.value == "Select"){	
				//-- need to insert an array start bracket after the first open bracket
				tokenNode.children[0].children.splice(0, 0, {
					name: "OPERATOR", 
					value: "[", 
					parent: tokenNode, 
					children: null
				});
				//-- need to insert an array end bracket before the close bracket
				tokenNode.children[0].children.push({
					name: "OPERATOR", 
					value: "]", 
					parent: tokenNode, 
					children: null
				});	
			}//--end of @Select
			
			
			//-- loop through the immediate children of the current node to do things like checking for lists				
			if(tokenNode.children !== null){		
				for(var c=0; c<tokenNode.children.length; c++){				
					if(tokenNode.children[c].name == "OPERATOR" && tokenNode.children[c].value == ":"){
						if(!arrayopen){
							//-- need to insert an array identifier to handle this as an array
							tokenNode.children.splice(c-1, 0, {
								name: "FUNCTION", 
								value: "ArrayConcat", 
								parent: tokenNode, 
								children: null
							});
							c++;  //iterate by one since we have inserted a record into the array
							tokenNode.children.splice(c-1, 0, {
								name: "OPERATOR", 
								value: "(", 
								parent: tokenNode, 
								children: null
							});			
							c++;  //iterate by one since we have inserted a record into the array							
							tokenNode.children.splice(c-1, 0, {
								name: "OPERATOR", 
								value: "[", 
								parent: tokenNode, 
								children: null
							});			
							c++;  //iterate by one since we have inserted a record into the array														
							arrayopen = true;			
						}
					//-- look to see if array needs closing
					}else if (priortoken && (priortoken.name != "OPERATOR" && priortoken.value != ":" && priortoken.value != "(" && priortoken.value != ")")){
						if(arrayopen){
							//-- need to insert an array identifier to handle this as an array
							tokenNode.children.splice(c, 0, {
								name: "OPERATOR", 
								value: "]", 
								parent: tokenNode, 
								children: null
							});
							c++;  //iterate by one since we have inserted a record into the array
							tokenNode.children.splice(c, 0, {
								name: "OPERATOR", 
								value: ")", 
								parent: tokenNode, 
								children: null
							});
							c++;  //iterate by one since we have inserted a record into the array							
							arrayopen = false;			
						}					
					}
					
					priortoken = tokenNode.children[c];
				}//--end of loop

				//-- check to see if any unterminated arrays	
				if(arrayopen){
					//-- need to insert an array identifier to handle this as an array
					tokenNode.children.push({
						name: "OPERATOR", 
						value: "]", 
						parent: tokenNode, 
						children: null
					});
					//-- need to insert an array identifier to handle this as an array
					tokenNode.children.push({
						name: "OPERATOR", 
						value: ")", 
						parent: tokenNode, 
						children: null
					});
					
					arrayopen = false;			
				}					
				
			}//--end of check for children
			


			if(tokenNode.name == "OPERATOR"){
				if(tokenNode.value == ";"){
					tokenNode.value = (tokenNode.parent && tokenNode.parent.name == "ROOT" ? ";\r" : ",");
				}else if(tokenNode.value == ":"){
					tokenNode.value = ",";					
				}else if(tokenNode.value == "="){
					tokenNode.value = "==";
				}else if(tokenNode.value == ":="){
					tokenNode.value = "=";				
				}else if(tokenNode.value == "&"){
					tokenNode.value = "&&";
				}else if(tokenNode.value == "|"){
					tokenNode.value = "||";
				}
			}else if(tokenNode.name == "KEYWORD"){
				tokenNode.value = "";   //blank out any other unsupported keywords
			}else if(tokenNode.name == "QUOTEDSTRING"){
				var tempval = tokenNode.value;
				tempval = '"' + tempval + '"';
				tokenNode.value = tempval;
			}else if(tokenNode.name == "COMMENT"){
				var tempval = tokenNode.value;
				tempval = '/*' + tempval + '*/';
				tokenNode.value = tempval;
			}else if(tokenNode.name == "FUNCTION"){
				tokenNode.value = "$$" + tokenNode.value;				
			}else if(tokenNode.name == "DATE"){
				var dateparts = tokenNode.value.split("-");
				tokenNode.value = "$$Date(" + dateparts[0] + "," + dateparts[1] + "," + dateparts[2] + ")";
			}
	}//--end translateToJS
	

	this._addDelimiters = function ( tokenList, index, isAssignment)
	{
		var found = false;
		var lparencount = 0;
		var rparencount = 0;
		while ( !found && index > 0 ){
			index--;
			if(tokenList[index].name == "OPERATOR" && tokenList[index].value == "("){
				lparencount ++;
			}else if(tokenList[index].name == "OPERATOR" && tokenList[index].value == ")"){
				rparencount ++;
			}
			
			if ( tokenList[index].name == "OPERATOR" && tokenList[index].value == ";" && lparencount == rparencount )
				found = true;
		}
		
		if ( isAssignment ){
			tokenList.splice(index, 0, {
				name: "OPERATOR",
				value: "{% ",
				pos: -1,
				depth : -1
			});
			index++;
			//variables...for twig..need to add a set in front of name
			tokenList.splice(index, 0, {
				name: "OPERATOR",
				value: " set ",
				pos: -1,
				depth : -1
			});
			return 2;
		}else{
			tokenList.splice(index, 0, {
				name: "OPERATOR",
				value: "{{ ",
				pos: -1,
				depth : -1
			});
			return 1;
		}
		
	}



	this._adjustTokenListTWIG = function(tokenList, idList){
		var lparencount = 0;
		var rparencount = 0;
		var assignmentactive = false;
		var lastdelm = "";
		var isvar = false;
		var iscomment = false;
		for(var i=0; i<tokenList.length; i++){
			
			var token = tokenList[i];
			
			if(token.name == "IDENTIFIER"){			
				var idname = token.value;
				if(idList && idList[idname.toUpperCase()]){
					
					
					
					if(idList[idname.toUpperCase()].type == "FIELD"){
						var isassignment = false;
						
						//-- look for prior token to see if this is a field assignment
						if(!assignmentactive && i>0 && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "FIELD"){
							assignmentactive = true;
							isassignment = true;
						}
						
						tokenList[i].name = "QUOTEDSTRING";
						tokenList[i].value =  idname;
						
						tokenList.splice(i, 0, {
							name: "FUNCTION",
							value: (isassignment ? "SetField" : "GetField"),
							pos: -1,
							depth : -1
						});
						i++;
						
						tokenList.splice(i, 0, {
							name: "OPERATOR",
							value: "(",
							pos: -1,
							depth : -1
						});
						i++;
						
						
						//-- this is where the existing identity turned field name should be positioned

						
						//-- only add closing parenthesis if getfield, otherwise we need to wait
						if(! isassignment){
							tokenList.splice(i+1, 0, {
								name: "OPERATOR",
								value: ")",
								pos: -1,
								depth : -1
							});
							i++;
						}
					}else{
						if (( i < ( tokenList.length -1 )) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == ":=" )
						{
							//make sure we are not inside a do or for function
							//i.e f_DO(j := 1 ) 
							if ( lparencount == rparencount){
								isvar = true;
								tokenList.splice(i, 0, {
									name: "SPECIAL",
									value: "set ",
									pos: -1,
									depth : -1
								});
								i++;
							}
						
						}
						
							
						
					}
					
						
					
					
				}
			}else if(token.name == "FUNCTION"){
				//-- check that function is followed by parenthesis (as some @functions may not have them yet)
				var addparens = true;
				
				var func = this.functable[token.value.toUpperCase()];
 		 		if(typeof func !== 'undefined'){
 		 			token.value = func.replacewith;
 		 		}

 		 		//convert this name to the name of the field currenlty executing this code
 		 		if ( token.value.toUpperCase() == "THISNAME" && this.currentFieldName != null)
 		 		{
 		 			
 		 			token.name = "QUOTEDSTRING";
 		 			token.value = this.currentFieldName;
 		 			addparens = false;
 		 		}else if ( token.value.toUpperCase() == "THISVALUE" && this.currentFieldName != null)
 		 		{
 		 			
 		 			token.name = "IDENTIFIER";
 		 			token.value = "f_GetField('" + this.currentFieldName + "')";
 		 			addparens = false;
 		 		}else if ( token.value.toUpperCase() == "CONFLICT"){
 		 			token.name = "QUOTEDSTRING";
 		 			token.value = "$$Conflict";
 		 			addparens = false;
 		 		}else if ( token.value.toUpperCase() == "ISAVAILABLE" || token.value.toUpperCase() == "ISUNAVAILABLE"  ){
 		 			if ( i + 2 < tokenList.length ){
 		 				if ( tokenList[i+2].name == "IDENTIFIER")
 		 					tokenList[i+2].name = "QUOTEDSTRING";
 		 			}
 		 		}
 		 		
 		 		
				
				if(i+1 < tokenList.length){
					if(tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
						addparens = false;
					}
				}
				if(addparens){
						tokenList.splice(i+1, 0, {
							name: "OPERATOR",
							value: "(",
							pos: -1,
							depth : -1
						});
						i++;						
				
						tokenList.splice(i+1, 0, {
							name: "OPERATOR",
							value: ")",
							pos: -1,
							depth : -1
						});
						i++;											
				}	
			}else if ( token.name ==  "COMMENT"){
				
				iscomment = true;
			}else if(token.name == "KEYWORD"){
				//-- in the case of the REM keyword we want to switch the following string to a comment
				if(token.value == "REM"){
					if(i+1 < tokenList.length){
						if(tokenList[i+1].name == "QUOTEDSTRING"){
							tokenList[i+1].name = "COMMENT";
						}
					}					
				}
			}else if(token.name == "OPERATOR"){
				
				
				if(tokenList[i].value == "("){
					lparencount ++;
				}else if(tokenList[i].value == ")"){
					rparencount ++;
				}
				
				
				//-- special checks to trap for redefining FIELD fieldname structure to a function call
				if(assignmentactive){
					//-- check for assignment operator
					if(tokenList[i].value == ":="){
						tokenList[i].value = ";"; //remap to a formula separator
					//-- check for separator character
					}else if(tokenList[i].value == ";"){
						//-- check that we are not inside a function call
						if(lparencount == rparencount){
							//-- insert a closing parenthesis to close of setfield function call
							tokenList.splice(i, 0, {
								name: "OPERATOR",
								value: ")",
								pos: -1,
								depth : -1
							});
							i++;
							
							//lastdelm = "";
							isvar = false;
							//iscomment = false;
							//-- reset flag and counters now that things are closed off
							lparencount = 0;
							rparencount = 0;
							assignmentactive = false;
						}
					}	
				}else
				{    //only add following if semicolon outside of function is encountered
					if(tokenList[i].value == ";" && lparencount == rparencount)
					{
							lastdelm = "";
							isvar = false;
					}
				}
				
			
			}
			
			
			
		}//--end of loop
		
	
	
		return tokenList;
	}//--end _adjustTokenListTWIG
	
	this.handleDoDefinition = function ( tokenNode){
		var insertat=0;
		var isset=false;
		var totallength=0;
		var insertobj = [{start:0, end: null, isset: false}];
	
		if (tokenNode.children[0].children != null ){

			var lastchild =  tokenNode.children[0].children[tokenNode.children[0].children.length-1];
			if (lastchild.name != "OPERATOR" && lastchild.value != ";"){
				tokenNode.children[0].children.splice(tokenNode.children[0].children.length, 0, {
					name: "OPERATOR",
					value: String.fromCharCode(31),
					parent:tokenNode,
					children:null
				})
			}else{
				lastchild.value = String.fromCharCode(31);
			}

			//insert a line delimiter as do doesn't have any parameters


			tokenNode.children[0].children.splice(0, 0, {
					name: "OPERATOR",
					value: String.fromCharCode(31),
					parent:tokenNode,
					children:null
				})

			for(var g=0; g<tokenNode.children[0].children.length - 1; g++){
				
				var cnode = tokenNode.children[0].children[g];
				if(cnode.value == ";"){
					
					cnode.value = String.fromCharCode(31);
					
				}else if( cnode.value == ":="){
					

					tokenNode.children[0].children.splice(g-1, 0, {
						name: "SPECIAL",
						value: "set ",
						parent:tokenNode,
						children:null
					});
					g++;
				}
			}
		}
	}
	
	this.handleForDefinition = function (tokenNode){
		var insertat=0;
		var isset=false;
		var totallength=0;
		
		var param = 0;
		if (tokenNode.children[0].children != null ){
			if ( tokenNode.children[1].value == ")")
				tokenNode.children[1].value = "";

			var lastchild =  tokenNode.children[0].children[tokenNode.children[0].children.length-1];
			if (! (lastchild.name == "OPERATOR" && lastchild.value == ";")){
				tokenNode.children[0].children.splice(tokenNode.children[0].children.length, 0, {
					name: "OPERATOR",
					value: String.fromCharCode(31),
					parent:tokenNode,
					children:null
				})
			}else{
				lastchild.value = String.fromCharCode(31);
			}

			//skip the parameters until we get to the body
			for(var t=0; t<tokenNode.children[0].children.length - 1; t++){
				var cnode = tokenNode.children[0].children[t];
				if(cnode.value == ";")
					param++;
				if ( param == 3){
					cnode.value = ")" + String.fromCharCode(31);
					break;
				}
			}
			
			for(var g=t+1; g<tokenNode.children[0].children.length - 1; g++){
				
				var cnode = tokenNode.children[0].children[g];
				if(cnode.value == ";"){
					cnode.value =  String.fromCharCode(31);
				}else if(  cnode.value == ":="){
					isset = true;
					tokenNode.children[0].children.splice(g-1, 0, {
						name: "SPECIAL",
						value: "set ",
						parent:tokenNode,
						children:null
					});
					g++;
				}
			}
			
		}
	}

	this.handleDoWhileDefinition = function (tokenNode){
		var insertat=0;
		var isset=false;
		var totallength=0;
		
		if (tokenNode.children[0].children != null ){

			if ( tokenNode.children[1].value == ")")
				tokenNode.children[1].value = "";

			//back track through the body until we get to the start of the condition
			for(var t=tokenNode.children[0].children.length - 1; t>0; t--){
				var cnode = tokenNode.children[0].children[t];
				if(cnode.value == ";"){						
					break;
				}
			}
			
			//move the condition to the start of the statement
			var m = tokenNode.children[0].children.length - t;
			var moveitems = tokenNode.children[0].children.splice(t, 1);
			tokenNode.children[0].children.splice(0, 0, moveitems[0]);
			var moveitems = tokenNode.children[0].children.splice(t+1, m-1);
			for(var i=moveitems.length-1; i>=0; i--){
				tokenNode.children[0].children.splice(0, 0, moveitems[i]);				
			}

			var param = 0;
			//skip the parameters until we get to the body
			for(var t=0; t<tokenNode.children[0].children.length - 1; t++){
				var cnode = tokenNode.children[0].children[t];
				if(cnode.value == ";")
					param++;
				if ( param == 1){
					cnode.value = ")" + String.fromCharCode(31);
					break;
				}
			}

			var lastchild =  tokenNode.children[0].children[tokenNode.children[0].children.length-1];
			if (! (lastchild.name == "OPERATOR" && lastchild.value == ";")){
				tokenNode.children[0].children.splice(tokenNode.children[0].children.length, 0, {
					name: "OPERATOR",
					value: String.fromCharCode(31),
					parent:tokenNode,
					children:null
				})
			}else{
				lastchild.value = String.fromCharCode(31);
			}

			for(var g=t+1; g<tokenNode.children[0].children.length - 1; g++){
				
				var cnode = tokenNode.children[0].children[g];
				if(cnode.value == ";"){
					cnode.value =  String.fromCharCode(31);
				}else if(  cnode.value == ":="){
					isset = true;
					tokenNode.children[0].children.splice(g-1, 0, {
						name: "SPECIAL",
						value: "set ",
						parent:tokenNode,
						children:null
					});
					g++;
				}
			}			
			
			
		}
	}

	this.handleWhileDefinition = function (tokenNode){
		var insertat=0;
		var isset=false;
		var totallength=0;
		
		var param = 0;
		if (tokenNode.children[0].children != null ){

			if ( tokenNode.children[1].value == ")")
				tokenNode.children[1].value = "";

			var lastchild =  tokenNode.children[0].children[tokenNode.children[0].children.length-1];
			if (! (lastchild.name == "OPERATOR" && lastchild.value == ";")){
				tokenNode.children[0].children.splice(tokenNode.children[0].children.length, 0, {
					name: "OPERATOR",
					value: String.fromCharCode(31),
					parent:tokenNode,
					children:null
				})
			}else{
				lastchild.value = String.fromCharCode(31);
			}


			//skip the condition until we get to the body
			for(var t=0; t<tokenNode.children[0].children.length - 1; t++){
				var cnode = tokenNode.children[0].children[t];
				if(cnode.value == ";")
					param++;
				if ( param == 1){			
					cnode.value = ")" + String.fromCharCode(31);
					break;
				}
			}

			for(var g=t+1; g<tokenNode.children[0].children.length - 1; g++){
				
				var cnode = tokenNode.children[0].children[g];
				if(cnode.value == ";"){
					cnode.value =  String.fromCharCode(31);
				}else if(  cnode.value == ":="){
					isset = true;
					tokenNode.children[0].children.splice(g-1, 0, {
						name: "SPECIAL",
						value: "set ",
						parent:tokenNode,
						children:null
					});
					g++;
				}
			}
			
			
		}
	}

	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: translateToTWIG
	 * Performs conversion on a token node and its immediate children to generate JavaScript equivalent
	 * Inputs: tokenNode - token - current token in the tree to proces
	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
	this.translateToTWIG = function(tokenNode) {	
			var lparen = 0;
			var rparen = 0;
			var paramcount = 0;
			var arrayopen = false;
			var priortoken = null;
			var secondsemi = false;
			//-- special case of @If function which needs its parameters passed as an array of functions
			if(tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "if"){	
				
				
				var proceed = false
				
				//check if this a if within another if block
				if ( tokenNode.parent && tokenNode.parent.parent && tokenNode.parent.parent.name == "SPECIAL" && tokenNode.parent.parent.value.indexOf("_IF_")>-1 )
				{
						//check if there are any parameters for this if..if not then remove the if
						var paramcounht =0;
						for ( var ind = 0; ind < tokenNode.children[0].children.length; ind++){
							if ( paramcount > 1)
								break;

							if ( tokenNode.children[0].children[ind].value == ";")
								paramcount ++;
						}

						if ( paramcount ==0)
						{

							//we can safely remove the child IF
							var ifcond = tokenNode.children[0].children;
							tokenNode.children = ifcond;
							tokenNode.value = "";
							tokenNode.name = "SPECIAL";
						}else{
							proceed = true;
						}
				}else{
				
					proceed = true;
				}

				if ( proceed ){
					var funcid = this.getFunctionID("IF");
					var tmpnode =  {name: tokenNode.name, value: "ifdef", parent: tokenNode.parent, children: tokenNode.children, funcid: funcid}
					this.functionarray.push({id: funcid, nodes:tmpnode});
					tokenNode.name = "SPECIAL";
					tokenNode.value = funcid;
					tokenNode.children = null;
				}

			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "ifdef"){
				tokenNode.value = 'If "' + tokenNode.funcid + '" ';
			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "select"){
				var tmpchildren = tokenNode.children[0].children;
				var parenadded = false;
				for ( l = 0;l< tmpchildren.length; l++){
					if ( tmpchildren[l].name == "OPERATOR" &&  tmpchildren[l].value == ";")
					{
						tokenNode.children[0].children.splice(l+1,0,{
							name: "OPERATOR", 
							value: '[', 
							parent: tokenNode, 
							children: null
						});	
						parenadded = true;
						break;
					}

				}
				if (parenadded){
					tokenNode.children[0].children.splice(tokenNode.children[0].children.length,0,{
								name: "OPERATOR", 
								value: ']', 
								parent: tokenNode, 
								children: null
					});	
				}

			}else if (tokenNode.name == "FUNCTION" && ( tokenNode.value.toLowerCase() == "do" || tokenNode.value.toLowerCase() == "dowhile" || tokenNode.value.toLowerCase() == "for" || tokenNode.value.toLowerCase() == "while" )){
				
				
				var funcid = "";
				var funcdefid = "";
				if ( tokenNode.value.toLowerCase() == "do" ){
					 this.handleDoDefinition(tokenNode);
					 funcid = this.getFunctionID("DO");
					 funcdefid = "dodef";
				}else if ( tokenNode.value.toLowerCase() == "dowhile" ){
					 this.handleDoWhileDefinition(tokenNode);
					 funcid = this.getFunctionID("DOWHILE");
					 funcdefid = "dowhiledef";
				}else if ( tokenNode.value.toLowerCase() == "while" ){
					 this.handleWhileDefinition(tokenNode);
					 funcid = this.getFunctionID("WHILE");
					 funcdefid = "whiledef";					 
				}else{
					this.handleForDefinition(tokenNode);
					funcid = this.getFunctionID("FOR");
					funcdefid = "fordef";
					
				}
				var tmpnode =  {name: tokenNode.name, value: funcdefid, parent: tokenNode.parent, children: tokenNode.children, funcid: funcid};
				
		
				this.functionarray.push({id: funcid, nodes:tmpnode, type: funcdefid});
				tokenNode.name = "SPECIAL";
				tokenNode.value = funcid;
				tokenNode.children = null;	
			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "dodef")
			{
				tokenNode.name = "SPECIAL";
				tokenNode.value = "";
				var tmpchildren = tokenNode.children[0].children;
				tokenNode.children = tmpchildren;
				
			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "fordef"){
				tokenNode.name = "SPECIAL";
				tokenNode.value = "";
				//skip past the for parameters to get to the body of the for
				var param = 0;
				var body= [];
				for ( p=0; p < tokenNode.children[0].children.length; p++){
					var tnode = tokenNode.children[0].children[p];
					if ( tnode.value == ";"){
						param++;
						if ( param == 3 )
							tnode.value = ");"
					}
					
				}
			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "dowhiledef"){
				tokenNode.name = "SPECIAL";
				tokenNode.value = "";
				//skip past the dowhile parameters to get to the body
				var param = 0;
				var body= [];
				for ( p=0; p < tokenNode.children[0].children.length; p++){
					var tnode = tokenNode.children[0].children[p];
					if ( tnode.value == ";"){
						param++;
						if ( param == 1 )
							tnode.value = ") %}\n";
					}
					
				}				
			}else if ( tokenNode.name == "FUNCTION" && tokenNode.value.toLowerCase() == "whiledef"){
				tokenNode.name = "SPECIAL";
				tokenNode.value = "";
				//skip past the while condition to get to the body
				var param = 0;
				var body= [];
				for ( p=0; p < tokenNode.children[0].children.length; p++){
					var tnode = tokenNode.children[0].children[p];
					if ( tnode.value == ";"){
						param++;
						if ( param == 1 )
							tnode.value = ") %}\n";
					}
					
				}
			}			


			if(tokenNode.name == "OPERATOR"){
				if(tokenNode.value == ";"){
					tokenNode.value = (((tokenNode.parent &&  tokenNode.parent.name == "ROOT") )  ? String.fromCharCode(31) : ",");
				}else if(tokenNode.value == ":"){
					tokenNode.value = "::";					
				}else if(tokenNode.value == "="){
					tokenNode.value = "===";
				}else if(tokenNode.value == ">"){
					tokenNode.value = ">>>";
				}else if(tokenNode.value == ">="){
					tokenNode.value = ">==";
				}else if(tokenNode.value == "<"){
					tokenNode.value = "<<<";
				}else if(tokenNode.value == "<="){
					tokenNode.value = "<==";
				}else if (tokenNode.value == "+") {
					tokenNode.value = "++"
				}else if (tokenNode.value == "-") {
					tokenNode.value = "--"
				}else if (tokenNode.value == '*') {
					tokenNode.value = '***';
				}else if(tokenNode.value == '/') {
					tokenNode.value = '///';
				}else if(tokenNode.value == ":="){
					tokenNode.value = "=";		
				}else if(tokenNode.value == "!="){
					tokenNode.value = "!==";							
				}else if(tokenNode.value == "&"){
					tokenNode.value = " and ";
				}else if(tokenNode.value == "|"){
					tokenNode.value = " or ";
				}else if (tokenNode.value == "!"){
					tokenNode.value = " not ";
				}
			}else if(tokenNode.name == "KEYWORD"){
				tokenNode.value = "";   //blank out any other unsupported keywords
			}else if(tokenNode.name == "QUOTEDSTRING"){
				var tempval = tokenNode.value;
				//tempval = safe_quotes_js(tempval, true);
				if (! tokenNode.isapos)
					tempval = '"' + tempval + '"';
				else
					tempval = "'" + tempval + "'";
				tokenNode.value = tempval;
			}else if(tokenNode.name == "COMMENT"){
				var tempval = tokenNode.value;
				tempval = '{# ' + tempval + ' #}\n';
				tokenNode.value = tempval;
			}else if(tokenNode.name == "FUNCTION"){
				
				
				tokenNode.value = "f_" + tokenNode.value;				
			}
	}//--end translateToTWIG
	
	this.getFunctionID = function(typestr){
		return '_' + typestr + '_' +  Math.random().toString(36).substr(2, 9);
	}
	
	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
	 * Method: convertCode
	 * Converts source code to a new language format.
	 * Inputs: fcode - string - source code to be converted
	 *              convertto - string  - output format.  JS - for JavaScript (default)
	 * Returns: string - converted code in string format, or unaltered code if no conversion specified
	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
	this.convertCode = function(fcode, convertto){
		var outputTxt = "";
		var tokenTreeRoot = null;
		if(typeof fcode == 'undefined' || fcode === ""){return outputTxt;}
		
		var convtype = (typeof convertto == 'undefined' || convertto == "" ? "JS" : convertto.toUpperCase());
		//if ( convtype == "TWIG"){
	//		fcode = "@ClearReturnVal();" + fcode;
	//	}
		
		this.input(fcode);	
		var tokenList = this.tokenize();	
		var idList = this._getIdentifiers(tokenList);
	
		if(convtype == "JS"){
			tokenList = this._adjustTokenList(tokenList, idList);
			tokenTreeRoot = this.tokenListToTree(tokenList);				
			outputTxt = this._walkTokenTree(tokenTreeRoot, this.translateToJS);
		}else if (convtype == "TWIG") {
			tokenList = this._adjustTokenListTWIG(tokenList, idList);
			tokenTreeRoot = this.tokenListToTree(tokenList);				
			outputTxt = this._walkTokenTree(tokenTreeRoot, this.translateToTWIG);
			var functionslist = "";
			for ( k = 0; k < this.functionarray.length; k++){
				if ( this.functionarray[k].type == "dodef"){ //delimeters have already been added
					var beginnode = 'docovado "' +this.functionarray[k].id  + '" ';
					var bodyCode = this._walkTokenTree(this.functionarray[k].nodes,this.translateToTWIG);				
					var endnode = "enddocovado  "+  String.fromCharCode(31);;
					functionslist +=  beginnode + "\n" + bodyCode + "\n" + endnode ;
				}else if(this.functionarray[k].type == "fordef"){ //delimeters have already been added
					var beginnode = 'docovafor "' +this.functionarray[k].id  + '" ';
					var bodyCode = this._walkTokenTree(this.functionarray[k].nodes,this.translateToTWIG);				
					var endnode = "enddocovafor " +  String.fromCharCode(31);
					functionslist +=  beginnode + bodyCode + "\n" + endnode ;
				}else if(this.functionarray[k].type == "dowhiledef"){ //delimeters have already been added
					var beginnode = 'docovadowhile "' +this.functionarray[k].id  + '" ';
					var bodyCode = this._walkTokenTree(this.functionarray[k].nodes,this.translateToTWIG);				
					var endnode = "enddocovadowhile "  +  String.fromCharCode(31);
					functionslist +=  beginnode + bodyCode + "\n" + endnode ;
				}else if(this.functionarray[k].type == "whiledef"){ //delimeters have already been added
					var beginnode = 'docovawhile "' +this.functionarray[k].id  + '" ';
					var bodyCode = this._walkTokenTree(this.functionarray[k].nodes,this.translateToTWIG);				
					var endnode = "enddocovawhile  " +  String.fromCharCode(31);
					functionslist +=  beginnode + bodyCode + "\n" + endnode ;					
				}else{ //if function
					var bodyCode = this._walkTokenTree(this.functionarray[k].nodes,this.translateToTWIG);
					functionslist +=    bodyCode + String.fromCharCode(31);
				}
			}
			outputTxt = functionslist  + outputTxt
		}else{
			tokenTreeRoot = this.tokenListToTree(tokenList);		
			outputTxt = this._walkTokenTree(tokenTreeRoot);		
		}
    	
    	

		//add twig delimiters
		var codeArray = outputTxt.split(String.fromCharCode(31));
		var codeline = "";
		for ( var k = 0; k < codeArray.length; k ++ ){
			codeline = codeArray[k].replace(/\n/g, '').trim();

			if ( codeline[0] != "{")
			{
				if ( codeline.indexOf("docovado") > -1 || codeline.indexOf("docovafor") > -1  || codeline.indexOf("docovawhile") > -1  || codeline.substring(0, 3) == "set" || codeline.substring(0,4) == "f_If")
					codeArray[k] = "{% " + codeline + " %}";
				else
					codeArray[k] = "{{ " + codeline + " }}";
			}
		}

		outputTxt = codeArray.join("\n");
		outputTxt = outputTxt.replace(/{{\s*}}/g, ''); //<< - Added this code to replace all empty curly bracket to avoid any twig compile exceptions
		

		return outputTxt;
	}//--end convertCode
	
}//--end Lexer


 /*-------------------------------------------------------------------------------------------------------------------------------------------- 
  * Class: LexerLS 
  * Analyzer and formatter for LotusScript code to convert it to other formats such as JavaScript
  * If the beautify.js file is loaded, the code can be formatted by calling beautify() on the result
  * Inputs:     
  * Example: 	var LexerObj = new LexerLS(); 
  *              var outputTxt = LexerObj.convertCode(formulacode, "JS");
  *              outputTxt = beautify(outputTxt); 	
  *------------------------------------------------------------------------------------------------------------------------------------------- */
  function LexerLS() {
 	this.pos = 0;
 	this.buf = null;
 	this.buflen = 0;
 	this.depth = 0;
 	lasttoken = null;

 	// Operator table, mapping operator -> token name
 	this.optable = {
 			'+' : {name: 'PLUS'},
 			'-' : {name: 'MINUS'},
 			'*' : {name: 'MULTIPLY'},
 			'=' : {name: 'EQUALS'},		
 			'/' : {name: 'DIVIDE'},
 			'.' : {name: 'PERIOD'},
 			'\\' : {name: 'BACKSLASH'},
 			':' : {name: 'COLON'}, 
// 			'%' : {name: 'PERCENT'},
// 			'|' : {name: 'PIPE'},
 			'#' : {name: 'POUND'},
 			'!' : {name: 'EXCLAMATION'},
 			'&' : {name: 'AMPERSAND'},
 			';' : {name: 'SEMI'},
 			',' : {name: 'COMMA'},
 			'(' : {name: 'L_PAREN'},
 			')' : {name: 'R_PAREN'},
 			'<' : {name: 'LESS_THAN', 
 				followedby : {
 					'=' : {name: 'LESS_THAN_EQUALS'},
 					'>' : {name: 'NOT_EQUAL'}
 				}
 			},
 			'>' : {name: 'GREATER_THAN',
 				followedby : {'=' : {name: 'GREATER_THAN_EQUALS'}}					
 			},
 	//		'{' : {name: 'L_BRACE'},
 	//		'}' : {name: 'R_BRACE'},
 			'[' : {name: 'L_BRACKET'},
 			']' : {name: 'R_BRACKET'},
 			'_' : {name: 'UNDERSCORE'},
 			'~' : {name: 'TILDE'}
 	};
 		
 	this.kwtable = {
 		    'ABBREVIATED' : {isproperty: true, replacewith: 'abbreviated'},			
 			'ABS' : {replacewith: '$$Abs'},
 			'ACOS' : {replacewith: '$$ACos'},
			'ADDDOCUMENT' : {ismethod: true, replacewith: 'addEntry'},
 			'ADDNEWLINE' : {ismethod: true, replacewith: 'addNewLine'},
 			'ADDRESSBOOKS' : {ismethod : true, replacewith: 'addressBooks'},
 			'ADDTAB' : {ismethod: true, replacewith: 'addTab', followedby: "(", terminator: ")"},
 			'ADJUSTYEAR' : {ismethod : true, replacewith: 'adjustYear', followedby: "(", terminator: ')'}, 	
 			'ADJUSTMONTH' : {ismethod : true, replacewith: 'adjustMonth', followedby: "(", terminator: ')'},
 			'ADJUSTDAY' : {ismethod : true, replacewith: 'adjustDay', followedby: "(", terminator: ')'},
 			'ADJUSTHOUR' : {ismethod : true, replacewith: 'adjustHour', followedby: "(", terminator: ')'},
 			'ADJUSTMINUTE' : {ismethod : true, replacewith: 'adjustMinute', followedby: "(", terminator: ')'},
 			'ADJUSTSECOND' : {ismethod : true, replacewith: 'adjustSecond', followedby: "(", terminator: ')'},			
 			'AND' : {replacewith: '&&'},		
 			'APPENDDOCLINK' : {ismethod : true, replacewith : 'appendDocLink'},
 			'APPENDPARAGRAPHSTYLE' : {ismethod: true, replacewith: 'appendParagraphStyle'}, 			
 			'APPENDRTITEM' : {ismethod : true, replacewith : 'appendRTItem'},
 			'APPENDSTYLE' : {ismethod: true, replacewith: 'appendStyle'},
 			'APPENDTABLE' : {ismethod: true, replacewith: 'appendTable'},
 			'APPENDTEXT' : {ismethod: true, replacewith: 'appendText', followedby: "(", terminator: ")"},
 			'APPENDTOTEXTLIST' : {ismethod: true, replacewith: 'appendToTextList'},
 			'ARRAYAPPEND' : {replacewith: '$$ArrayAppend'},
 			'ARRAYGETINDEX' : {ismethod : true, replacewith: '$$ArrayGetIndex'},
 			'ARRAYUNIQUE' : {replacewith: '$$ArrayUnique', followedby: "(", terminator: ')'},
 			'AS' : {replacewith: '='},
 			'ASIN' : {replacewith: '$$ASin'},
 			'ATN' : {replacewith: '$$ATan'},
 			'ATN2' : {replacewith: '$$ATan2'}, 
 			'BEEP' : {replacewith: '/* beep */'},
 			'BEGININSERT' : {ismethod: true, replacewith: 'beginInsert'},
 			'BINARY' : {},
 			'BOLD' : {isproperty: true, replacewith: 'bold'},
 			'BOOLEAN' : {replacewith: 'null'},
 			'BYVAL' : {replacewith: ''},
 			'CALL' : {replacewith: ''},
 		    'CANONICAL' : {isproperty: true, replacewith: 'canonical'},			
 			'CASE' : {replacewith: 'case'},
 			'CASE ELSE' : {replacewith: 'default'},
 			'CBOOL' : {replacewith: 'Boolean'},
 			'CDAT' : {replacewith: '$$Date'},
 			'CDBL' : {replacewith: 'parseFloat'}, 	
 			'CHR' : {replacewith: '$$Char'},
 			'CHR$' : {replacewith: '$$Char'},
 			'CHR' : {replacewith: '$$Char'},
 			'CINT' : {replacewith: 'parseInt'},
 		    'CLASS' : {replacewith: 'function '},
 		    'CLOSE' : {ismethod : true, replacewith: 'close', followedby: "(", terminator: ')'},
 			'COLOR' : {isproperty: true, replacewith: 'color'},
 		    'COLUMNS' : {isproperty: true, replacewith: 'columns'},
 		    'COMMON' : {isproperty: true, replacewith: 'common'},
 		    'COMMONUSERNAME' : {isproperty: true, replacewith: 'commonUserName'},
 		    'COMPARE' : {replacewith: 'compare'},
 		    'COMPOSEDOCUMENT' : {ismethod : true, replacewith: 'composeDocument', followedby: "(", terminator: ')'},
 		    'COMPOSERESPONSEDOCUMENT' : {ismethod : true, replacewith: 'composeResponseDocument', followedby: "(", terminator: ')'}, 		    
 		    'COMPUTEWITHFORM' : {ismethod : true, replacewith: 'computeWithForm', followedby: "(", terminator: ')'}, 		    
 		    'CONST' : {replacewith: 'var'},
 		    'COPYALLITEMS' : {ismethod : true, replacewith: 'copyAllItems', followedby: "(", terminator: ')'},
 		    'COPYITEMTODOCUMENT' : {ismethod: true, replacewith: 'copyItemToDocument'},
 		    'COS' : {replacewith: '$$Cos'},
 		    'COUNT' : {isproperty: true, replacewith: 'count'},
 			'COLUMNVALUES' : {isproperty: true, replacewith: 'columnValues'},
 			'CONTAINS' : {ismethod: true, replacewith: 'contains'},
 			'COPYITEM' : {ismethod: true, replacewith: 'copyItem'},
 		    'COUNTRY' : {isproperty: true, replacewith: 'country'},						
 			'CREATED' : {isproperty: true, replacewith: 'created'}, 					    
 		    'CREATECOLOROBJECT' : {ismethod : true, replacewith: 'createColorObject', followedby: "(", terminator: ")", flag: true},
 		    'CREATEDATERANGE' : {ismethod: true, replacewith: 'createDateRange', followedby: "(", terminator: ")"},
 		    'CREATEDOCUMENT' : {ismethod : true, replacewith: 'createDocument', followedby: "(", terminator: ')'},
 		    'CREATENAME' : {ismethod : true, replacewith: 'createName'},
 		    'CREATENAVIGATOR' : {ismethod : true, replacewith: 'createNavigator', followedby: "(", terminator: ')'},
			'CREATERANGE' : {ismethod: true, replacewith: 'createRange', followedby: "(", terminator: ')'}, 				 
 		    'CREATERICHTEXTITEM' : {ismethod : true, replacewith: 'createRichTextItem'},
 		    'CREATERICHTEXTSTYLE' : {ismethod : true, replacewith: 'createRichTextStyle', followedby: "(", terminator: ')'},
 			'CREATERICHTEXTPARAGRAPHSTYLE' : {ismethod : true, replacewith: 'createRichTextParagraphStyle', followedby: "(", terminator: ')'}, 		    
 			'CREATEVIEWNAV' : {ismethod : true, replacewith: 'createViewNav'},
 			'CREATEVIEWNAVFROMCATEGORY' : {ismethod : true, replacewith: 'createViewNavFromCategory'},
 		    'CSTR' : {replacewith: '$$Text'},
 		    'CURRENTAGENT' : {isproperty: true, replacewith: 'currentAgent'},
 			'CURRENTACCESSLEVEL' : {isproperty: true, replacewith: 'currentAccessLevel'},	
 			'CURRENTCALENDARDATETIME' : {isproperty: true, replacewith: 'currentCalendarDateTime'},
 		    'CURRENTDATABASE' : {isproperty: true, replacewith: 'currentDatabase'}, 	
 		    'CURRENTDOCUMENT' : {isproperty: true, replacewith: 'currentDocument'},
 		    'CURRENTVIEW' : {isproperty: true, replacewith: 'currentView'},
 		    'CVAR' : {replacewith: ''},
 		    'CVDAT' : {replacewith: '$$Date'},
 		    'DATATYPE' : {replacewith: 'typeof'},
 		    'DATE' : {replacewith: '$$Date'},
 		    'DATENUMBER' : {replacewith: '$$Date'},
 		    'DATETIMEVALUE' : {isproperty: true, replacewith: 'dateTimeValue'},
 		    'DATEONLY' : {isproperty: true, replacewith: 'dateOnly'},
 		    'DAY' : {replacewith: '$$Day'},
 		    'DECLARE' : {replacewith: ''},
 		    'DELETE' : {replacewith: '// delete'},
 		    'DELETEDOCUMENT' : {ismethod: true, replacewith: 'deleteDocument'},
 		    'DESELECTALL' : {ismethod: true, replacewith: 'deselectAllEntries', followedby: '(', terminator: ')'},
 		    'DIM' : {replacewith: 'var'},
		    'DIALOGBOX' : {ismethod: true, replacewith: 'dialogBox', flag: true}, 			
 		    'DOCUMENT' : {isproperty: true, replacewith: 'document'}, 		
 		    'DOCUMENTS' : {isproperty: true, replacewith: 'documents'}, 		    
 		    'DO' : {replacewith: 'do{'},
 		    'DO WHILE' : {replacewith: 'while(', terminator: '){'},
 		    'DO UNTIL' : {replacewith: 'while(!(', terminator: '){'},
 		    'DOUBLE' : {replacewith: '0'},
 		    'EDITDOCUMENT' : {ismethod: true, replacewith: 'editDocument', followedby: '(', terminator: ')'},
 		    'EDITPROFILE' : {ismethod: true, replacewith: 'editProfile', followedby: '(', terminator: ')'}, 		    
			'EDITMODE' : {isproperty: true, replacewith: 'editMode', flag: true},		    
 		    'EFFECTIVEUSERNAME' : {isproperty: true, replacewith: 'effectiveUserName'},
 		    'ELSE' : {replacewith: '}else{'},
 		    'ELSEIF' : {replacewith: '}else if('},
 		    'ENDDATETIME' : {isproperty : true, replacewith: 'endDateTime'},
 			'ENDINSERT' : {ismethod: true, replacewith: 'endInsert', followedby: "(", terminator: ")"},		    
 		    'END' : {replacewith: 'return'},
 		    'END CLASS' : {replacewith: '}'},
 		    'END FORALL' : {replacewith: '}'},
 		    'END FUNCTION' : {replacewith: '}'},
 		    'END IF' : {replacewith: '}'}, 		     		    
 		    'END PROPERTY' : {replacewith: '},configurable: true})'},
 		    'END SELECT' : {replacewith: '}'},
 		    'END SUB' : {replacewith: '}'}, 		
 		    'END TYPE' : {replacewith: '}'}, 		
 		    'END WITH' : {replacewith: ''},
 		    'ENDREM' : {replacewith: '*/'},
 		    'ERROR' : {replacewith: 'error'},
 		    'ERR' : {replacewith: '0', flag: true},
 		    'EVALUATE' : {replacewith: 'Docova.Utils.evaluateFormula'},
 		    'EXECUTE' : {replacewith: 'eval'},
 		    'EXIT' : {replacewith: 'return'},
 		    'EXIT DO' : {replacewith: 'break'},
 		    'EXIT FOR' : {replacewith: 'break'},
 		    'EXIT FORALL' : {replacewith: 'break'},
 		    'EXIT FUNCTION' : {replacewith: 'return'},
 		    'EXIT PROPERTY' : {replacewith: 'return'},		    
 		    'EXIT SUB' : {replacewith: 'return'},	
 		    'EXPLICIT' : {replacewith: ''}, 		    
 		    'FALSE' : {replacewith: 'false'},
 		    'FIELDGETTEXT' : {ismethod: true, replacewith: 'getField'},
		    'FIELDSETTEXT' : {ismethod: true, replacewith: 'setField'},		    
			'FILEPATH' : {isproperty: true, replacewith: 'filePath'},
 			'FINDFIRSTELEMENT' : {ismethod: true, replacewith: 'findFirstElement'}, 	
			'FINDLASTELEMENT' : {ismethod: true, replacewith: 'findLastElement'}, 				 			
 			'FINDNEXTELEMENT' : {ismethod: true, ismethod: true, replacewith: 'findNextElement'},
			'FINDNTHELEMENT' : {ismethod: true, replacewith: 'findNthElement'}, 				  			
 			'FIRSTLINELEFTMARGIN' : {ismethod: true, replacewith: 'firstLineLeftMargin'},
 			'FONT' : {isproperty: true, replacewith: 'font'},
 			'FONTSIZE' : {isproperty: true, replacewith: 'fontSize'},		    
 		    'FOR' : {replacewith: 'for(', terminator: '){'},
 		    'FORALL' : {replacewith : 'for(', terminator: '){'},
 		    'FORMAT' : {replacewith: '$$Format'},
 		    'FORMAT$' : {replacewith: '$$Format'},
 		    'FTSEARCH' : {ismethod : true, replacewith: 'FTSearch'},
 		    'FULLTRIM' : {replacewith: '$$Trim'},
 		    'FUNCTION' : {replacewith: 'function'},
 		    'GET' : {replacewith: ''},
 		    'GETAGENT' : {ismethod: true, replacewith: 'getAgent'},
 		    'GETALLDOCUMENTS' : {ismethod : true, replacewith: 'getAllDocuments'}, 		    
 		    'GETALLDOCUMENTSBYKEY' : {ismethod : true, replacewith: 'getAllDocumentsByKey'},
 		    'GETDATABASE' : {ismethod : true, replacewith: 'getDatabase'},
 		    'GETDOCUMENT' : {ismethod : true, replacewith: 'getDocument'},
 		    'GETDOCUMENTBYKEY' : {ismethod : true, replacewith: 'getDocumentByKey'}, 
 		    'GETDOCUMENTBYUNID' : {ismethod : true, replacewith: 'getDocument'},
 		    'GETDOCUMENTBYID' : {ismethod : true, replacewith: 'getDocument'},
 		    'GETENTRYBYKEY' : {ismethod : true, replacewith: 'getDocumentByKey'},
 		    'GETELEMENT' : {ismethod : true, replacewith: 'getElement', followedby: "(", terminator : ")"},
 		    'GETENVIRONMENTVAR' : {ismethod : true, replacewith: 'getEnvironmentVar'},	
 			'GETENVIRONMENTVALUE' : {ismethod : true, replacewith: 'getEnvironmentVar'}, 	
			'GETENVIRONMENTSTRING' : {ismethod : true, replacewith: 'getEnvironmentVar'},  
			'GETFIRST' : {ismethod : true, replacewith: 'getFirstDocument', followedby: "(", terminator: ")"},
 		    'GETFIRSTDOCUMENT' : {ismethod : true, replacewith: 'getFirstDocument', followedby: "(", terminator : ")"}, 
 		    'GETFIRSTITEM' : {ismethod : true, replacewith: 'getFirstItem'},
 		    'GETITEMVALUE' : {ismethod : true, replacewith: 'getField'},
 		    'GETNEXT' : {ismethod : true, replacewith: 'getNext'},
 		    'GETNEXTDOCUMENT' : {ismethod : true, replacewith: 'getNextDocument'},  	
 		    'GETNEXTSIBLING' : {ismethod : true, replacewith: 'getNextSibling'},
 		    'GETNTHDOCUMENT' : {ismethod : true, replacewith: 'getNthDocument'},  
 		    'GETLASTDOCUMENT' : {ismethod : true, replacewith: 'getLastDocument', followedby: "(", terminator : ")"},  	 		    
 		    'GETPREV' : {ismethod : true, replacewith: 'getPrev'},
 		    'GETPREVDOCUMENT' : {ismethod : true, replacewith: 'getPrevDocument'},  	
 		    'GETPROFILEDOCUMENT' : {ismethod : true, replacewith: 'getProfileDocument'},
 			'GETPROFILEDOCCOLLECTION' : {ismethod : true, replacewith: 'getProfileDocCollection'},
 			'GETUNFORMATTEDTEXT' : {ismethod : true, replacewith: 'getUnformattedText', followedby: "(", terminator : ")"},
 		    'GETVIEW' : {ismethod : true, replacewith: 'getView'},
 		    'GIVEN' : {isproperty: true, replacewith: 'given'},			
 		    'GMTTIME' : {isproperty: true, replacewith: 'GMTTime'},
 		    'GOSUB' : {replacewith: '/* TODO - rework this code as it contains a gosub */', terminator: '*/', flag: true}, 		    
 		    'GOTO' : {replacewith: '/* TODO - rework this code as it contains a goto ', terminator: '*/', flag: true},
 		    'GOTOFIELD' : {ismethod: true, replacewith: 'goToField'},
 		    'HASITEM' : {ismethod : true, replacewith: 'hasItem'},
 		    'HTTPURL' : {ismethod : true, replacewith: 'httpURL'},
 		    'HOUR' : {replacewith : "$$Hour"},
 		    'IF' : {replacewith: 'if('},
 		    'IMPLODE' : {replacewith: '$$Implode'},
 		    'IN' : {replacewith: 'in'},
 		    'INCLUDE' : {replacewith: '/* TODO - rework this code as it contains an INCLUDE statement ', terminator: '*/', flag: true}, 		    
 		    'INITIALS' : {isproperty: true, replacewith: 'initials'},	
 		    'INPREVIEWPANE' : {isproperty: true, replacewith: 'inPreviewPane'},
 		    'INSTR' : {replacewith: '$$InStr'},
 		    'IS' : {replacewith: '==='},
 			'ISAUTHORS' : {isproperty: true, replacewith: 'isAuthors'},
 			'ISDATE' : {replacewith: '$$IsDate'},
 			'ISDELETED' : {isproperty: true, replacewith: 'isDeleted'},
 		    'ISHIERARCHICAL' : {isproperty: true, replacewith: 'isHierarchical'},			 			
 			'ISNAMES' : {isproperty: true, replacewith: 'isNames'},
 			'ISNEWDOC' : {isproperty: true, replacewith: 'isNewDoc'},
 			'ISNEWNOTE' : {isproperty: true, replacewith: 'isNewDocument'},
 			'ISPROFILE' : {isproperty: true, replacewith: 'isProfile'},
 			'ISREADERS' : {isproperty: true, replacewith: 'isReaders'},
 			'ISRESPONSE' : {isproperty: true, replacewith: 'isResponse'},
 			'ISUIDOCOPEN' : {isproperty: true, replacewith: 'isUIDocOpen'},
 			'ISVALID' : {isproperty: true, replacewith: 'isValid'},		  
 			'ITALIC' : {isproperty: true, replacewith: 'italic'},
 			'ITEMS' : {isproperty: true, replacewith: 'items'},
 		    'INTEGER' : {replacewith: '0'},
 		    'INT' : {replacewith: '$$Integer'},
 		    'ISARRAY' : {replacewith: 'Array.isArray'},
 		    'ISELEMENT' : {replacewith: '$$IsElement'},
 		    'ISEMPTY' : {replacewith: '$$IsEmpty'},
 		    'ISNULL' : {replacewith: '$$IsNull'},
 		    'ISNUMERIC' : {replacewith: '$$IsNumeric'},
 		    'ISONSERVER' : {isproperty: true, replacewith: 'isOnServer'},
 		    'JOIN' : {replacewith: '$$Implode'},
 		    'LBOUND' : {replacewith: '$$LBound'}, 
 		    'LCASE' : {replacewith: '$$LowerCase'},
 		    'LEFT' : {replacewith: '$$Left'},
 			'LEFTMARGIN' : {isproperty: true, replacewith: 'leftMargin'},
 			'LEN' : {replacewith: '$$Length'},
 		    'LIST' : {replacewith: '', flag: true},
 		    'LISTTAG' : {replacewith: 'ListTag'},
 		    'LOCALTIME' : {isproperty: true, replacewith: 'localTime'},
 		    'LONG' : {replacewith: '0'},
 		    'LOOP' : {replacewith: '}while(true)'},
 		    'LOOP UNTIL' : {replacewith: '}while(!', terminator: ')'},
 		    'LSGMTTIME' : {isproperty: true, replacewith: 'GMTTime'}, 		    
 		    'LSLOCALTIME' : {isproperty: true, replacewith: 'localTime'}, 
 		    'MANAGERS' : {isproperty: true, replacewith: 'managers'},
 		    'MAKERESPONSE' : {ismethod : true, replacewith: 'makeResponse'},
 		    'MID' : {replacewith: '$$Mid'},
 		    'MID$' : {replacewith: '$$Mid'},
 		    'MINUTE' : {replacewith: '$$Minute'},
 		    'MOD' : {replacewith: '%'},
 		    'MONTH' : {replacewith: '$$Month'},
 		    'MESSAGEBOX' : {replacewith: '$$MessageBox', followedby: '(', terminator: ')', flag: true}, 		    
 		    'MSGBOX' : {replacewith: '$$MessageBox', followedby: '(', terminator: ')'},
 		    'NEW' : {replacewith: 'new'},
 		    'NEXT' : {replacewith: '}'},
 		    'NOCASE' : {},
 		    'NOT' : {replacewith: '!'},
			'NOTEID' : {isproperty: true, replacewith: 'universalID'},
			'NOTESAGENT' : {replacewith: 'null'},
 			'NOTESCOLOR' : {isproperty: true, replacewith: 'color'},	
 			'NOTESCOLOROBJECT' : {replacewith: 'null'},
 		    'NOTESDATABASE' : {replacewith: 'DocovaApplication()'},
 		    'NOTESDATETIME' : {replacewith: 'DocovaDateTime', followedby: '(', terminator: ')'},
 		    'NOTESDATERANGE' : {replacewith: 'DocovaDateRange', followedby: '(', terminator: ')'},
 		    'NOTESDOCUMENT' : {replacewith: 'DocovaDocument()'},
 		    'NOTESDOCUMENTCOLLECTION' : {replacewith: 'DocovaCollection()'},
 		    'NOTESINTERNATIONAL' : {replacewith: '/* TODO - rework code to handle NotesInternational ', terminator: '*/', flag: true},
 		    'NOTESITEM' : {replacewith: 'DocovaField', followedby: '(', terminator: ')'},
 		    'NOTESNAME' : {replacewith: 'DocovaName', followedby: '(', terminator: ')'},
 		    'NOTESRICHTEXTITEM' : {replacewith: 'DocovaRichTextItem', followedby: '(', terminator: ')'},
 		    'NOTESRICHTEXTNAVIGATOR' : {replacewith: 'null', flag: true}, 		    
 		    'NOTESRICHTEXTPARAGRAPHSTYLE' : {replacewith: 'null'},
 		    'NOTESRICHTEXTRANGE' : {replacewith: 'null'},
 		    'NOTESRICHTEXTSTYLE' : {replacewith: 'DocovaRichTextStyle', followedby: '(', terminator: ')'}, 	 		    
 		    'NOTESRICHTEXTTABLE' : {replacewith: 'null'},
 		    'NOTESSESSION' : {replacewith: 'DocovaSession()'},
 		    'NOTESUIDOCUMENT' : {replacewith: 'DocovaUIDocument()'},
 		    'NOTESUIWORKSPACE' : {replacewith: 'DocovaUIWorkspace()'},
 		    'NOTESVIEW' : {replacewith: 'DocovaView()'},
 		    'NOTESVIEWCOLUMN' : {replacewith: 'null'},
 		    'NOTESVIEWENTRY' : {replacewith: 'null'}, 		     		    
 		    'NOTESVIEWNAVIGATOR' : {replacewith: 'null', flag: true}, 		    
 		    'NOTHING' : {replacewith: 'null'},
 		    'NOW' : {replacewith: '$$Now', followedby: '(', terminator: ')'},
 		    'NULL' : {replacewith: 'null'},
 		    'ON' : {replacewith: 'on'}, 	
 		    'ON ERROR' : {replacewith: '/* TODO - rework code to handle on error ', terminator: '*/', flag: true},
 		    'ON ERROR GOTO' : {replacewith: '/* TODO - rework code to handle on error goto ', terminator: '*/', flag: true},
 		    'OPENDATABASE' : {ismethod: true, replacewith: 'openDatabase'},
 		    'OPTION' : {replacewith: ''},
 		    'OPTION COMPARE' : {replacewith: ''},
 		    'OPTION COMPARE BINARY' : {replacewith: ''}, 
 		    'OPTION COMPARE NOCASE' : {replacewith: ''},  		    
 		    'OPTION DECLARE' : {replacewith: ''}, 		    
 		    'OPTION EXPLICIT' : {replacewith: ''}, 
 		    'OR' : {replacewith: '||'},
 		    'ORGANIZATION' : {isproperty: true, replacewith: 'organization'},			 		   
 		    'ORGUNIT1' : {isproperty: true, replacewith: 'orgUnit1'},			 		    
 		    'ORGUNIT2' : {isproperty: true, replacewith: 'orgUnit2'},			 		    
 		    'ORGUNIT3' : {isproperty: true, replacewith: 'orgUnit3'},			 		    
 		    'ORGUNIT4' : {isproperty: true, replacewith: 'orgUnit4'},			 		    
 		    'PARENTDATABASE' : {isproperty: true, replacewith: 'parentApp'},
 			'PARENTDOCUMENTUNID' : {isproperty: true, replacewith: 'parentDocumentUNID'},
 			'PARENTVIEW' : {isproperty: true, replacewith: 'parentView'},
 			'PICKLISTCOLLECTION' : {ismethod: true, replacewith: 'picklistCollection', flag: true},	
 			'PICKLISTSTRINGS' : {ismethod: true, replacewith: 'picklistStrings', flag: true},	 			
 			'PUTINFOLDER' : {ismethod: true, replacewith: 'putInFolder'},
 			'PRESERVE' : {replacewith: ''},
 		    'PRINT' : {replacewith: 'console.log(', terminator: ')'},
 		    'PROMPT' : {ismethod : true, replacewith: 'prompt', followedby: '(', terminator: ')', flag: true},
 		    'PROPERTY' : {replacewith: ''},
 		    'PUBLIC' : {replacewith: ''},
		    'PRIVATE' : {replacewith: ''},    		    
 		    'REDIM' : {replacewith: '/* TODO - check this value redefinition ', terminator: '*/'},
		    'REFRESH' : {ismethod : true, replacewith: 'refresh', followedby: '(', terminator: ')', flag: true},
		    'REFRESHHIDEFORMULAS' : {ismethod : true, replacewith: 'refresh', followedby: '(', terminator: ')', flag: true},		    
		    'RELOAD' : {ismethod : true, replacewith: 'reload', followedby: '(', terminator: ')'},
 		    'REM' : {replacewith: '//'},
 		    'REMOVE' : {ismethod : true, replacewith: 'remove', followedby: '(', terminator: ')'},
 			'REMOVEFROMFOLDER' : {ismethod: true, replacewith: 'removeFromFolder'}, 		    
 		    'REMOVEITEM' : {ismethod : true, replacewith: 'deleteField', followedby: '(', terminator: ')'},
 		    'RENDERTORTITEM' : {ismethod: true, replacewith: 'renderToRTItem'},
 		    'REPLACEITEMVALUE' : {ismethod : true, replacewith: 'setField', followedby: '(', terminator: ')'},
			'RESPONSES' : {isproperty: true, replacewith: 'responses'},
 		    'RESUME' : {replacewith: ''},
 		    'RESUME NEXT' : {replacewith: ''},
 		    'RETURN' : {replacewith: 'return'},
 		    'RIGHT' : {replacewith: '$$Right'},
 			'RIGHTMARGIN' : {isproperty: true, replacewith: 'rightMargin'},
 			'RUN' : {ismethod: true, replacewith: 'run'},
 			'RUNONSERVER' : {ismethod: true, replacewith: 'runOnServer'},
 			'SAVE' : {ismethod : true, replacewith: 'save', followedby: '(', terminator: ')', flag: true},
 			'SEARCH' : {ismethod : true, replacewith: 'search'},
 			'SECOND' : {replacewith: '$$Second'},
		    'SELECT' : {},
 		    'SELECT CASE' : {replacewith: 'switch(', terminator: '){'},
 		    'SEND' : {ismethod: true, replacewith: 'send', followedby: '(', terminator: ')'},
 			'SERVER' : {isproperty: true, replacewith: 'server'},
 			'SERVERNAME' : {isproperty: true, replacewith: 'serverName'},
 			'SETENVIRONMENTVALUE' : {ismethod: true, replacewith: 'setEnvironmentValue'},
 		    'SET' : {replacewith: ''},
 		    'SETALTERNATECOLOR' : {ismethod : true, replacewith: 'setAlternateColor'},
 		    'SETBEGIN' : {ismethod : true, replacewith: 'setBegin'}, 		    
 		    'SETCOLOR' : {ismethod : true, replacewith: 'setColor'},
 		    'SETEND' : {ismethod : true, replacewith: 'setEnd'}, 		    
 		    'SETENVIRONMENTVAR' : {ismethod : true, replacewith: 'setEnvironmentVar'},
 			'SETNOW' : {ismethod : true, replacewith: 'setNow', followedby: '(', terminator: ')'},
 		    'SETPOSITIONATEND' : {ismethod : true, replacewith: 'setPositionAtEnd'}, 		    
 		    'SPLIT' : {replacewith: '$$Explode'},
 		    'STAMPALL' : {ismethod : true, replacewith: 'stampAll'},
 		    'STARTDATETIME' : {isproperty : true, replacewith: 'startDateTime'},
 		    'STATIC' : {replacewith: 'var'},
 		    'STEP' : {replacewith: ''},
 		    'STRING' : {replacewith: "''"},
 		    'STRLEFT' : {replacewith: '$$Left'},
 		    'STRRIGHT' : {replacewith: '$$Right'},
			'STYLE' : {isproperty: true, replacewith: 'style'},			 
 		    'SUB' : {replacewith: 'function'},
 		    'SURNAME' : {isproperty: true, replacewith: 'surname'},		
 		    'TEXT' : {isproperty: true, replacewith: 'text'},
 		    'THEN' : {replacewith: '){'}, 	
 			'TIMEDIFFERENCE' : {ismethod : true, replacewith: 'timeDifference'}, 		    
 		    'TIMEONLY' : {isproperty: true, replacewith: 'timeOnly'},
 		    'TIMEZONE' : {isproperty: true, replacewith: 'timeZone'}, 	
 		    'TRIM' : {replacewith: '$$Trim'},
 		    'TYPE' : {replacewith: '', terminator: '= {'},
 		    'TO' : {replacewith: ';'},
 		    'TRUE' : {replacewith: 'true'},
 		    'UCASE' : {replacewith: '$$UpperCase'},
 		    'UBOUND' : {replacewith: '$$UBound'},
 			'UNDERLINE' : {isproperty: true, replacewith: 'underline'},
 			'UNIVERSALID' : {isproperty: true, replacewith: 'universalID'},
 			'UNPROCESSEDDOCUMENTS' : {isproperty: true, replacewith: 'unprocessedDocuments'},
 		    'UNTIL' : {replacewith: 'while(!', terminator: ')'},
 		    'USE' : {replacewith: 'loadScript(', terminator: ')'},
 		    'USELSX' : {replacewith: '//Uselsx', flag: true },
 			'USERNAME' : {isproperty: true, replacewith: 'userName'},
 			'USERNAMELIST' : {isproperty: true, replacewith: 'userNameList'},
 			'USERNAMEOBJECT' : {isproperty: true, replacewith: 'userNameObject'},
 			'USERGROUPNAMELIST' : {isproperty: true, replacewith: 'userGroupNameList'},
 			'USTRING' : {replacewith: '$$UString'},
 			'VALUES' : {isproperty: true, replacewith: 'values'},
 		    'VARIANT' : {replacewith: 'null'},
 		    'VIEWREFRESH' : {ismethod : true, replacewith: 'viewRefresh', followedby: '(', terminator: ')'},
 		    'WEND' : {replacewith: '}'},
 		    'WHILE' : {replacewith: 'while', followedby: '(', terminator: '){'},
 		    'WITH' : {replacewith: '//with', flag: true},
 		    'YEAR' : {replacewith: '$$Year'},
 		    '%END' : {},
 		    '%INCLUDE' : {replacewith: '/* TODO - rework this code as it contains an INCLUDE statement ', terminator: '*/', flag: true},
 		    '%REM' : {replacewith: '/*' },
 		    '%END REM' : {replacewith: '*/'},
 		    '%ENDREM' : {replacewith: '*/'}
 	};
 	
 	this.otherconvtable = {
 	
 	};
 	
 	this.constanttable = [
    ];				

 	

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: input 
 	 * Initialize the Lexer's buffer. This resets the lexer's internal state and subsequent tokens will be returned 
 	 * starting with the beginning of the new buffer.
 	 * Inputs: buf - string - input string containing source code
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
 	this.input = function (buf) {
 		this.pos = 0;
 		this.buf = (buf ? buf : "");
 		this.buflen = (buf ? buf.length : 0);
 		this.depth = 0;
 	}

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: tokenize
 	 * Converts the input buffer to a token stream
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
 	this.tokenize = function() {
 		var tokenList = [];	

 		var tokenObj = null;
 		do{
 			tokenObj = this._token();
 			lasttoken = tokenObj;
 			if(tokenObj){
 				tokenList.push(tokenObj);
 			}
 		}while (tokenObj); 	
 		
 		return tokenList;
 	}

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: _token
 	 * Get the next token from the current buffer. A token is an object with the following properties:
 	 *    - name: name of the pattern that this token matched (taken from rules).
 	 *    - value: actual string value of the token.
 	 *    - pos: offset in the current buffer where the token starts.
 	 * 	If there are no more tokens in the buffer, returns null. In case of an error throws Error.
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
 	this._token = function () {
 		var curpos = this.pos;
 		
 		this._skipnontokens();
 		if (this.pos >= this.buflen) {
 			return null;
 		}

 		// The char at this.pos is part of a real token. Figure out which.
 		var c = this.buf.charAt(this.pos);

 		// Look it up in the table of operators
 		var op = this.optable[c];
 		if (typeof op !== 'undefined') {
 			if(op.followedby){
 				for (var childop in op.followedby) {
    						if (op.followedby.hasOwnProperty(childop)) {
    							if(this.buf.charAt(this.pos + 1) === childop){
     								c += childop;
     								break;
     							}
     						}
 					}					
 			}
 			
 			if(c === "("){
 				this.depth ++;
 			}else if(c === ")"){
 				if(this.depth > 0){
 					this.depth --;
 				}
 			}
 				
 			this.pos = this.pos + c.length;
 				
 			return {
 				name : "OPERATOR",
 				value : c,
 				pos : curpos,
 				depth : this.depth
 			};
 		} else {
 				// Not an operator - so it's the beginning of another token.
 				if(this._iscomment(c)){
 					return this._process_comment(c);
 				}else if(this._isnewline(c)){
 					return this._process_newline();
 				}else if(this._isfunction(c)){
 					return this._process_function();
 				}else if(this._isclass(c)){
 					return this._process_class();
 				}else if(this._isproperty(c)){
 					return this._process_property();
 				}else if(this._isalpha(c)) {
 					return this._process_identifier();
 				} else if (this._isdigit(c)) {
 					return this._process_number();
 				} else if (c === '|') {
 					return this._process_quote('|');
 				} else if (c === '"' || c === "'") {
 					return this._process_quote(c);
 				} else if (c === '{') {
 					return this._process_quote('}');					
 				} else {
 					if(console){
 						console.log("Error in LexerLS>tokenize>_token: unidentified token at buffer position [" + this.pos + "] character [" + c + "] character code [" + c.charCodeAt(0) + "].")
 					}
 				}
 		}
 	}

 	
 	this._isnewline = function (c) {
 		var charcode = c.charCodeAt(0);
 		return (c === '\r' || c === '\n' || charcode == 10 || charcode == 12 || charcode == 13 );
 	}

 	this._isdigit = function (c) {
 		return c >= '0' && c <= '9';
 	}

 	this._isalpha = function (c) {
		var exceptions = [215, 247, 697, 698, 699, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716, 717, 718, 719, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732, 733, 734, 735, 736, 737, 738, 739, 740 ,750, 757, 758, 760, 884, 890, 894];
		return (c >= 'a' && c <= 'z') ||
		(c >= 'A' && c <= 'Z') ||
 		c === '_' || c === '$' || c === '%' ||
		(c.charCodeAt(0) > 192 && exceptions.indexOf(c.charCodeAt(0)) == -1);		
 	}

 	this._isalphanum = function (c) {
		var exceptions = [215, 247, 697, 698, 699, 700, 701, 702, 703, 704, 705, 706, 707, 708, 709, 710, 711, 712, 713, 714, 715, 716, 717, 718, 719, 720, 721, 722, 723, 724, 725, 726, 727, 728, 729, 730, 731, 732, 733, 734, 735, 736, 737, 738, 739, 740 ,750, 757, 758, 760, 884, 890, 894];
		return (c >= 'a' && c <= 'z') ||
		(c >= 'A' && c <= 'Z') ||
		(c >= '0' && c <= '9') ||		
		c === '_' || c === '$' || c === '%' || c === '&' ||
		(c.charCodeAt(0) > 192 && exceptions.indexOf(c.charCodeAt(0)) == -1);	 		
 		
 	}	
 	
 	this._isconstant = function () {
 		var result = false;
 		if(this.buf.charAt(this.pos) != "["){ return result;}
 		
 		var endpos = this.pos;	
 		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
 			endpos++;
 		}
 		if(endpos === this.pos){return result;}	
 		
 		var searchconst = this.buf.substring(this.pos, endpos + 1);
 		if(this.constanttable.indexOf(searchconst) > -1){
 			result = true;
 		}

 		return result;		
 	}
 	
 	this._iscomment = function (c) {
 		var result = false;
 		
 		if(c === "'"){
 			result = true;
 		}else if(c === "R" && this.buflen > this.pos + 4 && this.buf.substring(this.pos, this.pos + 3) === "REM"){
			var tempchar = this.buf.substring(this.pos + 3, this.pos + 4);
 			if(tempchar === " " || this._isnewline(tempchar)){
 				result = true;
 			}
 		}else if(c === "%" && this.buflen > this.pos + 5 && this.buf.substring(this.pos, this.pos + 4) === "%REM" ){
			var tempchar = this.buf.substring(this.pos + 4, this.pos + 5);
			if(tempchar === " " || this._isnewline(tempchar)){
				result = true;
			}
 		}
 		
 		return result;
 	}
 	
 	this._isfunction = function () {
 		var result = false;
 		
 		var firstchar = this.buf.charAt(this.pos).toUpperCase();
 		if(firstchar === "F" && this.buflen < this.pos + 9 ){ return result;}
 		if(firstchar === "S" && this.buflen < this.pos + 4 ){ return result;}

		var tempstring = this.buf.substring(this.pos, this.pos + (firstchar === "F" ? 9 : 4));
 		if(tempstring.toUpperCase() === "FUNCTION " || tempstring.toUpperCase() === "SUB "){
 			//--check to see if this is a function end or exit
 			if(lasttoken && lasttoken.name == "KEYWORD" && (lasttoken.value.toUpperCase() == "END" || lasttoken.value.toUpperCase() == "EXIT")){
 				//not a function declaration
 			}else{
 				result = true;
 			}
 		}
 		
 		return result;		
 	}	
 	
	this._isclass = function () {
 		var result = false;
 		
 		var firstchar = this.buf.charAt(this.pos).toUpperCase();
 		if(firstchar === "C" && this.buflen < this.pos + 6 ){ return result;}

		var tempstring = this.buf.substring(this.pos, this.pos + 6);
 		if(tempstring.toUpperCase() === "CLASS "){
 			//--check to see if this is a class end
 			if(lasttoken && lasttoken.name == "KEYWORD" && lasttoken.value.toUpperCase() == "END"){
 				//not a class declaration
 			}else{
 				result = true;
 			}
 		}
 		
 		return result;		
 	}		
	
	this._isproperty = function () {
 		var result = false;
 		
 		var firstchar = this.buf.charAt(this.pos).toUpperCase();
 		if(firstchar === "P" && this.buflen < this.pos + 13 ){ return result;} 		

		var tempstring = this.buf.substring(this.pos, this.pos + 13);
 		if(tempstring.toUpperCase() === "PROPERTY GET " || tempstring.toUpperCase() === "PROPERTY SET "){
 			result = true;
 		}
 		
 		return result;		
 	}	
 	
 	this._process_constant = function () {
 		var tok = null;
 		if(this.buf.charAt(this.pos) != "["){ return tok;}
 		
 		var endpos = this.pos;	
 		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
 			endpos++;
 		}
 		if(endpos === this.pos){return tok;}
 		
 		var searchconst = this.buf.substring(this.pos, endpos + 1);
 		if(this.constanttable.indexOf(searchconst) > -1){
 			tok = {
 				name : 'CONSTANT',
 				value : '"' + searchconst + '"',
 				pos : this.pos
 			};
 			this.pos = endpos + 1;
 		}

 		return tok;		
 	}	

 	this._process_number = function () {
 		var endpos = this.pos + 1;
 		while (endpos < this.buflen &&
 			this._isdigit(this.buf.charAt(endpos))) {
 			endpos++;
 		}

 		var tok = {
 			name : 'NUMBER',
 			value : this.buf.substring(this.pos, endpos),
 			pos : this.pos
 		};
 		this.pos = endpos;
 		return tok;
 	}
 	
	this._process_newline = function () {
 		var tok = {
 			name : 'NEWLINE',
 			value : this.buf.charAt(this.pos),
 			pos : this.pos
 		};
 		this.pos = this.pos + 1;
 		return tok;
 	}	

 	this._process_comment = function (c) {
 		var endpos = this.pos;
 		var tok = {
 				name: '',
 				value: '',
 				pos: -1
 		};
 		
 		if(c === "'"){
 	 		endpos = this.pos + 1;
 	 		// Skip until the end of the line
 	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
 	 			endpos++;
 	 		}	 		
 			tok = {
 		 			name : 'COMMENT',
 		 			value : this.buf.substring(this.pos + 1, endpos),
 		 			pos : this.pos
 		 	};
 		 	this.pos = endpos; 	 		
 		}else if(c === "R" && this.buflen > this.pos + 3 && this.buf.substring(this.pos, this.pos + 3) === "REM"){
 	 		endpos = this.pos + 4;
 	 		// Skip until the end of the line
 	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
 	 			endpos++;
 	 		}
			tok = {
 		 			name : 'COMMENT',
 		 			value : this.buf.substring(this.pos + 4, endpos),
 		 			pos : this.pos
 		 	};
 		 	this.pos = endpos; 	 		 	 		
 		}else if(c === "%" && this.buflen > this.pos + 4 && this.buf.substring(this.pos, this.pos + 4) === "%REM" ){
 	 		endpos = this.pos + 5;
 	 		// Skip until the end remark identifier found. search for the first of two forms
 	 		var endpos1 = this.buf.indexOf("%END REM", endpos);
 	 		var endpos2 = this.buf.indexOf("%ENDREM", endpos);
 	 		var offset = 0;
 	 		if(endpos1 > -1 || endpos2 > -1){
 	 			if(endpos1 == -1){
 	 				endpos = endpos2;
 	 				offset = 7;
 	 			}else if(endpos2 == -1){
 	 				endpos = endpos1;
 	 				offset = 8;
 	 			}else if(endpos1 < endpos2){
 	 				endpos = endpos1;
 	 				offset = 8;
 	 			}else{
 	 				endpos = endpos2;
 	 				offset = 7;
 	 			}
	 			
 	 		}else{
 	 	 		// Just skip until the end of the line since no end of remark statement found
 	 	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
 	 	 			endpos++;
 	 	 		}	 			
 	 		}

			tok = {
 		 			name : 'COMMENT',
 		 			value : this.buf.substring(this.pos + 5, endpos),
 		 			pos : this.pos
 		 	};
 		 	this.pos = endpos + offset; 	 		
 	 		
 		}		
 		
 		return tok;
 	}

 	this._process_identifier = function () {
 		var endpos = this.pos + 1;
 		while (endpos < this.buflen &&
 			this._isalphanum(this.buf.charAt(endpos))) {
 			endpos++;
 		}
 		
 		var tempval = this.buf.substring(this.pos, endpos);
 		var kw = this.kwtable[tempval.toUpperCase()];
 		var tempname = "IDENTIFIER";
 		if(typeof kw !== 'undefined'){
 			if((kw.ismethod || kw.isproperty) && (this.pos > 0) && (this.buf.charAt(this.pos -1) === ".")){
 				tempname = "KEYWORD";
 			}else if(!kw.ismethod && !kw.isproperty && (this.pos > 0) && (this.buf.charAt(this.pos -1) === ".")){
				tempname = "IDENTIFIER";
 			}else{
 				tempname = "KEYWORD"; 	
 			}
 		} 

 		var tok = {
 			name : tempname,
 			value : tempval,
 			pos : this.pos
 		};
 		this.pos = endpos;
 		return tok;
 	}
 	
 	this._process_function = function () {
 
 		var firstchar = this.buf.charAt(this.pos).toUpperCase();
 		var charcount = (firstchar === "F" ? 9 : 4 );
 		
 		var startpos = this.pos + charcount;
 		var endpos = this.pos + charcount;
 		while (endpos < this.buflen &&
 			this._isalphanum(this.buf.charAt(endpos))) {
 			endpos++;
 		}

 		var tok = {
 			name : 'FUNCTION',
 			value : this.buf.substring(startpos, endpos),
 			pos : startpos
 		};
 		this.pos = endpos;
 		return tok;
 	}	
 	
 	
	this._process_class = function () {
 		var charcount = 6;
 		var startpos = this.pos + charcount;
 		var endpos = this.pos + charcount;
 		while (endpos < this.buflen &&
 			this._isalphanum(this.buf.charAt(endpos))) {
 			endpos++;
 		}

 		var tok = {
 			name : 'CLASS',
 			value : this.buf.substring(startpos, endpos),
 			pos : startpos
 		};
 		this.pos = endpos;
 		return tok;
 	}	 	
	
	this._process_property = function () {		 
 		var charcount = 13;

 		var tempstring = this.buf.substring(this.pos, this.pos + charcount).toUpperCase().trim();
 		
 		var startpos = this.pos + charcount;
 		var endpos = this.pos + charcount;
 		while (endpos < this.buflen &&
 			this._isalphanum(this.buf.charAt(endpos))) {
 			endpos++;
 		}

 		var tok = {
 			name : tempstring,
 			value : this.buf.substring(startpos, endpos),
 			pos : startpos
 		};
 		this.pos = endpos;
 		return tok;
 	}		

 	this._process_quote = function (quotechar) {
 		var tok = false;
 		var curpos = this.pos;
 		var end_index = -1;
 		var keeplooking = true;

 		do {
 			//Find the next quote.
 			curpos = this.buf.indexOf(quotechar, curpos + 1);
 			if (curpos === -1) {
 				keeplooking = false;
 				if(console){
 	 				console.log('LexerLS Error: Unterminated quote at ' + this.pos); 					
 				}
 			} else {
 				//check for double quote indicating a literal
 				if((curpos + 1) < this.buflen && this.buf.charAt(curpos + 1) === quotechar){
 					curpos ++;
 				}else{
 					var tempstring = "";
 					if(this.pos + 1 <= curpos){
 						tempstring = this.buf.substring(this.pos+1, curpos);
 					}
 	
 					//--replace single slash with double slash
 			 		tempstring = tempstring.replace(/\\/g, "\\\\");
 			 											
 					keeplooking = false;
 					tok = {
 						name : 'QUOTEDSTRING',
 						value : tempstring,
 						pos : this.pos
 					};								
 					this.pos = curpos + 1; //increment buffer position					
 				}
 			}		
 		} while(keeplooking);	
 		
 		return tok;
 	}

 	this._skipnontokens = function () {
 		while (this.pos < this.buflen) {
 			var c = this.buf.charAt(this.pos);
 			var charcode = c.charCodeAt(0);
 			if (c == ' ' || c == '\t' || (charcode > 126 && charcode < 161) || (charcode < 10) || (charcode > 13 && charcode < 33)) {
 				this.pos++;
 			} else {
 				break;
 			}
 		}
 	}

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: tokenListToTree
 	 * Converts a token list to a tree such that child operations are nested beneath the parent operation
 	 * Enables easier parsing of content within the same level (eg. function parameters, vs. child functions).
 	 * Inputs: tokenList - array - array of token objects
 	 * Returns: rootToken - token object at top of tree
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
 	this.tokenListToTree = function(tokenList) {
 		var depth = 0;
 		
 		var rootToken = {
 			name: "ROOT",
 			value: "",
 			children: [],
 			parent : null
 		};
 		var parentToken = rootToken;
 	
 		for(var i=0; i<tokenList.length; i++){
 			var token = tokenList[i];
 			var newtoken = {
 				name: token.name, 
 				value: token.value,
 				children : null,
 				parent : null,
 				flags : (typeof token.flags !== "undefined" ? token.flags : null)
 			}
 			
 			if(newtoken.name == "OPERATOR"){			
 				 if(newtoken.value == ")"){
 					var temptoken = (parentToken.parent ? parentToken.parent : parentToken);		
 					depth --;									 	
 					var parentToken = temptoken; //-- pop up one level so that this parenthesis is at the same level as its match		
 				}
 			}

 			newtoken.parent = parentToken;
 			if(parentToken.children === null){
 				parentToken.children = new Array();
 			}					
 			parentToken.children.push(newtoken);

 			//console.log(("     ").repeat(depth) + " -> " + newtoken.value);

 			//-- reset parent token for functions and brackets  
 			if(newtoken.name == "FUNCTION" || newtoken.name == "CLASS" || (newtoken.name == "OPERATOR" && newtoken.value == "(")){
 				depth ++;
 				parentToken = newtoken;
 			}else if(newtoken.name == "OPERATOR" && newtoken.value == ")" && (parentToken.name == "FUNCTION" || parentToken.name == "CLASS")){
 				depth --;
 				parentToken = (parentToken.parent ? parentToken.parent : parentToken);
 			}							
 		}	
 		
 		return rootToken;
 	}//--end tokenListToTree

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: _walkTokenTree
 	 * Walks the token tree from top to bottom, left to right, returning either the original content, or 
 	 * if a convertFunction parameter is passed, the converted code.  A recursive function.
 	 * Inputs: tokenNode - current token node to be traversed
 	 *              convertFunction - function (optional) - function to call to perform processing on the token node
 	 *                                              and possibly its children.  Function must accept the token node as its input
 	 *              depth - integer (optional) - counter used to guage how many recursive calls have been made
 	 *              lastNode - last token node traversed (optional)
 	 * Returns: string - original code or converted code in text format
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
 	this._walkTokenTree = function (tokenNode, convertFunction, depth, lastNode){	
 		var tempdepth = (typeof depth == 'undefined' ? 0 : depth);
 		var tempresult = "";
 		var returnresult = "";

 		if(typeof tokenNode != 'undefined' && tokenNode != null){
 			if(typeof convertFunction === 'function'){
 				convertFunction.call(this, tokenNode, lastNode);
 			} 

 			//console.log(("     ").repeat(depth) + " -> " + tokenNode.value);
 			lastNode = tokenNode;
 			if(tokenNode.children != null){
 				tempdepth ++;				
 				for(var c=0; c<tokenNode.children.length; c++){
 					tempresult  += this._walkTokenTree(tokenNode.children[c], convertFunction, tempdepth, lastNode);
 					lastNode = tokenNode.children[c];
 				} 
 			}
 			returnresult = tokenNode.value + tempresult;			
 		}
 		return returnresult;
 	}
 	
 
 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: _adjustTokenList
 	 * Walks the token list from left to right, and makes adjustments that are needed before the tree
 	 * can be generated.  eg. converts field references to function calls, inserts missing function brackets
 	 * Inputs: tokenList - array of tokens
 	 * Returns: token object array - array of token objects
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
 	this._adjustTokenList = function(tokenList){
 		var lparencount = 0;
 		var rparencount = 0;
 		var hascomment = false;
 		var functiondef = false;
 		var classdef = false;
 		var propertydef = false;
 		var functionname = "";
 		var propertyname = "";
 		var usestatement = false;
 		var classstatement = false;
 		var ifcondition = false;
 		var ifstatement = false;
 		var forstatement = false;
 		var forallstatement = false;
 		var forvar = "";
 		var forallvals = [];
 		var inwith = false;
 		var withname = [];
 		var casestatement = false;
 		var casebreak = [];
 		var assignmentfound = false;
 		var assignlparencount = 0;
 		var assignrparencount = 0;
 		var anonclosureneeded = 0;
 		var termneeded = "";
 		var globalvars = [];
 		var globalvarsorig = [];
 		var functionvars = [];
 		var functionvarsorig = [];
 		
 		//-- main pass through the list
 		for(var i=0; i<tokenList.length; i++){
 			var token = tokenList[i];
 			//-- check if we are in a forall declaration and need to keep track of array source tokens
 			if(forallstatement && forvar !== "" && !((token.name == "KEYWORD" && token.value.toUpperCase() == "IN") || token.name == "NEWLINE")){
 				forallvals.push({
 					name: token.name,
 					value: token.value,
 					pos: -1,
 					depth: -1
 				});
 			}
 			
 			//--1 COMMENT
 			if(token.name == "COMMENT"){
 				hascomment = true;
 			//--2 KEYWORD
 			}else if(token.name == "KEYWORD"){		
 				//-- check if token is ListTag call if so we need to adjust it
 				if(token.value.toUpperCase() == "LISTTAG"){
 					//-- remove the parenthesis and variable name following ListTag 
 					tokenList.splice(i+1, 3);				
 				//-- check if token is equality
 				}else if(token.value.toUpperCase() == "IS"){
 					token.name = "EQUALITY";
 					token.value = "===";
 				//-- check if we are in a for statement and have a variable that needs to be applied
 				}else if(token.value.toUpperCase() == "TO" && forstatement && forvar !== ""){
	 		 		//-- insert operator
	 		 		tokenList.splice(i+1, 0, {
	 		 			name: "OPERATOR",
	 		 			value: "<=",
	 		 			pos: -1,
	 		 			depth: -1
	 		 		}); 					
 					//-- insert variable
 	 		 		tokenList.splice(i+1, 0, {
	 		 			name: "IDENTIFIER",
	 		 			value: forvar,
	 		 			pos: -1,
	 		 			depth: -1
	 		 		});
	 		 		termneeded = "; " + forvar + "++" + termneeded;
	 		 		forvar = "";	
	 		 	//-- check if this is a true/false/null statement
 				}else if(token.value.toUpperCase() == "TRUE" || token.value.toUpperCase() == "FALSE" || token.value.toUpperCase() == "NULL"){
 					//-- if the prior token is equality operator then make more specific
 					if(i>0 && tokenList[i-1].name === "EQUALITY"){
 						tokenList[i-1].value = "===";					
 					//-- check if the prior token is not equals, if so make it more specific						
 					}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
 						tokenList[i-1].value = "!==";
 					}
  				//-- check if this is a variable declaration
 				}else if(token.value.toUpperCase() == "AS"){
 					//-- if this is a list declaration then just convert to an array
 					if((i-1 > -1) && (i+1 < tokenList.length) && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "LIST"){
 						tokenList[i+1].value = "[]";
 					}else if(i+1 < tokenList.length && tokenList[i+1].name == "KEYWORD" && tokenList[i+1].value.toUpperCase() !== "NEW"){
 						//-- since this is a declaration without a new keyword we can clear it out
 						if(tokenList[i+1].value.toUpperCase() == "STRING"){
 							tokenList[i+1].value = "''";
 						}else if(tokenList[i+1].value.toUpperCase() == "INTEGER" || tokenList[i+1].value.toUpperCase() == "DOUBLE"){
 							tokenList[i+1].value = "0";	
 						}else{
 							tokenList[i+1].value = "null";
 						}
 					}
 				//-- check if two keywords follow each other and may combine into a single keyword
 				}else if((i+1 < tokenList.length) && (tokenList[i+1].name == "KEYWORD" || tokenList[i+1].name == "FUNCTION" || tokenList[i+1].name == "CLASS")){
 					var testkw = tokenList[i].value + " " + tokenList[i+1].value;
 					testkw = testkw.trim();
 					var kw = this.kwtable[testkw.toUpperCase()];
 					if(typeof kw !== 'undefined'){
 						tokenList[i].value = testkw;
 						tokenList.splice(i+1, 1);
 					}
 				}
 				
				//-- check if this is an if or elseif statement
				if(token.value.toUpperCase() == "IF"){
					ifstatement = true;
					ifcondition = true;
					assignlparencount = 0;
	 				assignrparencount = 0;
	 				assignmentfound = false;
				}else if(token.value.toUpperCase() == "THEN"){
					ifcondition = false;
					assignlparencount = 0;
	 				assignrparencount = 0;
	 				assignmentfound = false;
				}else if(token.value.toUpperCase() == "ELSE"){
					ifcondition = false;
					assignlparencount = 0;
	 				assignrparencount = 0;
	 				assignmentfound = false;						 				
				}else if(token.value.toUpperCase() == "ELSEIF"){
					ifstatement = false;
					ifcondition = true;
					assignlparencount = 0;
	 				assignrparencount = 0;
	 				assignmentfound = false;					
				}else if(token.value.toUpperCase() == "END IF"){
					ifstatement = false;
					ifcondition = false;
					assignlparencount = 0;
	 				assignrparencount = 0;
	 				assignmentfound = false;	
				//-- check if this is the start of a for loop
				}else if(token.value.toUpperCase() == "FOR"){
					forstatement = true;
					forvar = "";
				//-- check if this is the start of a forall loop
				}else if(token.value.toUpperCase() == "FORALL"){
					forallstatement = true;
					forvar = "";
					forallvals = [];
				//-- check if this is the start of a with statement
				}else if(token.value.toUpperCase() == "WITH"){
					inwith = true;
					withname.push("");
				//-- check if this is the end of a with statement
				}else if(token.value.toUpperCase() == "END WITH"){
					inwith = false;
					withname.pop();
				//-- keep track of select case statements
				}else if(token.value.toUpperCase() == "SELECT CASE"){
					casebreak.push(false);
				//-- keep track of end select statements
				}else if(token.value.toUpperCase() == "END SELECT"){
					if(casebreak && casebreak.length > 0){
						if(casebreak[casebreak.length - 1]){
							//-- insert a break statement
	 	 					tokenList.splice(i, 0, {
	 	 						name: "KEYWORD",
	 	 						value: "break",
	 	 						pos: -1,
	 	 						depth : -1
	 	 					});
	 	 					i++;

							//-- insert a newline
	 	 					tokenList.splice(i, 0, {
	 	 						name: "NEWLINE",
	 	 						value: "",
	 	 						pos: -1,
	 	 						depth : -1
	 	 					});
	 	 					i++;													
						}						
						casebreak.pop();
					}
				//-- check if a case statement needs closing
				}else if(token.value.toUpperCase() == "CASE" || token.value.toUpperCase() == "CASE ELSE"){
					casestatement = true;
					if(casebreak && casebreak.length > 0){
						if(casebreak[casebreak.length - 1]){
							//-- insert a break statement
	 	 					tokenList.splice(i, 0, {
	 	 						name: "KEYWORD",
	 	 						value: "break",
	 	 						pos: -1,
	 	 						depth : -1
	 	 					});
	 	 					i++;

							//-- insert a newline
	 	 					tokenList.splice(i, 0, {
	 	 						name: "NEWLINE",
	 	 						value: "",
	 	 						pos: -1,
	 	 						depth : -1
	 	 					});
	 	 					i++;													
						}
						casebreak[casebreak.length - 1] = true;
					}
				//-- check if a function is exiting early and add a return value
 				}else if(token.value.toUpperCase() == "EXIT FUNCTION" || token.value.toUpperCase() == "EXIT SUB" || token.value.toUpperCase() == "END"){
 					var functionreturnname = "";
 					if(functionname.toLowerCase() == "queryopen" || functionname.toLowerCase() == "queryclose" || functionname.toLowerCase() == "querysave" || functionname.toLowerCase() == "querymodechange"){
 						functionreturnname = "Continue";
 					}else if(functionname !== ""){
 						functionreturnname = "return_" + functionname.toLowerCase();						
 					}
 					if(functionreturnname !== ""){
 						//--return the function value					
 						tokenList.splice(i+1, 0, {
 							name: "IDENTIFIER",
 							value: functionreturnname,
 							pos: -1,
 							depth : -1
 						});
 					}
				//-- check if a function is exiting and insert a return value in case it isn't already present
				}else if(token.value.toUpperCase() == "END FUNCTION" || token.value.toUpperCase() == "END SUB"){
					var functionreturnname = "";
 					if(functionname.toLowerCase() == "queryopen" || functionname.toLowerCase() == "queryclose" || functionname.toLowerCase() == "querysave" || functionname.toLowerCase() == "querymodechange"){
 						functionreturnname = "Continue";
 					}else if(functionname !== ""){
 						functionreturnname = "return_" + functionname.toLowerCase();						
 					}
 					
					//--return the function value					
					tokenList.splice(i, 0, {
						name: "KEYWORD",
						value: "return",
						pos: -1,
						depth : -1
					});	
 					i++;		
 					
 					if(functionreturnname !== ""){		
 						tokenList.splice(i, 0, {
 							name: "IDENTIFIER",
 							value: functionreturnname,
 							pos: -1,
 							depth : -1
 						});	
 						i++;
 					}
 					
					//-- insert a newline
 					tokenList.splice(i, 0, {
 						name: "NEWLINE",
 						value: "",
 						pos: -1,
 						depth : -1
 					});
 					i++;
 					
 					functionvars = [];
 					functionvars.length = 0;
					functionvarsorig = [];
 					functionvarsorig.length = 0;					
 					functionname = "";
				//-- check if a class is exiting 
				}else if(token.value.toUpperCase() == "END CLASS"){
					classstatement = false;
				//-- check if a property is exiting 
				}else if(token.value.toUpperCase() == "END PROPERTY"){
					propertyname = "";
 				//-- check if this is a use statement
				}else if(token.value.toUpperCase() == "USE"){
					usestatement = true;
				//-- check if this is a getitemvalue call
				}else if(token.value.toUpperCase() == "GETITEMVALUE"){
	 					var lpcount = 0;
	 					var rpcount = 0;
	 					var convert = false;
	 					var c=i+1;
	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
	 							lpcount ++;
	 							if(convert){
	 								tokenList[c].value = "[";
	 							}
	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
	 							rpcount ++;
	 							if(rpcount == lpcount){
	 								if(convert){
	 									tokenList[c].value = "]";
	 									break;
	 								}else{
	 									if(c+1<tokenList.length && tokenList[c+1].name == "OPERATOR" && tokenList[c+1].value == "("){
	 										convert = true;
	 										lpcount = 0;
	 										rpcount = 0;
	 									}else{
	 										break;
	 									}
	 								}
	 							}
	 						}
	 						c++;
	 					}
				}				
 			
				var kw = this.kwtable[token.value.toUpperCase()];
				if(typeof kw !== 'undefined'){
					var ignoreterm = false;
					if(kw.followedby && kw.followedby !== ""){
						if(i+1 < tokenList.length){
							//-- check if this token is followed by a particular token
							if(tokenList[i+1].name != "OPERATOR" || tokenList[i+1].value !== kw.followedby){
								//-- insert token after this one since token requires something following it
								tokenList.splice(i+1, 0, {
									name: "OPERATOR",
									value: kw.followedby,
									pos: -1,
									depth : -1
								});
							}else{
								//-- ignore the terminator 
								ignoreterm = true;
							}
						}
					}
					//-- check if this token has a terminator required					
					if(kw.terminator && kw.terminator !== "" && !ignoreterm){
						termneeded = termneeded + kw.terminator;
					}	
				}	

				//--check if this reference needs to be converted from () array notation to [] array notation
				if((token.value.toUpperCase() == "COLUMNVALUES")){
	 				if(i+1 < tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
	 					tokenList[i+1].value = "[";
	 					var lpcount = 1;
	 					var rpcount = 0;
	 					var c=i+1;
	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
	 							lpcount ++;
	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
	 							rpcount ++;
	 							if(rpcount == lpcount){
	 								tokenList[c].value = "]";
	 							}
	 							break;
	 						}
	 						c++;
	 					}
	 				}
	 			}				
			//--3 IDENTIFIER	
 			}else if(token.name == "IDENTIFIER"){
 				//-- check if this identifier is being declared if so lets track it for later use
 				if((i>0 && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "DIM") || functiondef || classdef || propertydef){
 					if(functionname === ""){
 						globalvars.push(token.value.toUpperCase());
 						globalvarsorig.push(token.value);
 					}else{
 						functionvars.push(token.value.toUpperCase());
 						functionvarsorig.push(token.value);
 					}
 				}else{
 					//--check if this identifier has been previously declared if so make sure the case is consistent
 					var findx = functionvars.indexOf(token.value.toUpperCase());
 					if(findx > -1){
 						//-- change case of identifer to match declaration
 						token.value = functionvarsorig[findx];
 					}else{
 						findx = globalvars.indexOf(token.value.toUpperCase());
 	 					if(findx > -1){
 	 						//-- change case of identifer to match global declaration
 	 						token.value = globalvarsorig[findx];
 	 					}	
 					}					
 				}
 				
 				//-- check if we are in a for statement and need to store a variable
 				if(forstatement && forvar === ""){
 					forvar = token.value;
 				}
 				//-- check if we are in a forall statement and need to store a variable
 				if(forallstatement && forvar === ""){
 					forvar = token.value;
 					
					tokenList.splice(i, 0, {
						name: "KEYWORD",
						value: "var",
						pos: -1,
						depth : -1
					});			
 					i++;
 				}
 				
 				//-- check if we are in a with statement and no identifier stored on the stack yet
 				if(inwith && withname.length > 0 && withname[withname.length - 1] === ""){
 					withname[withname.length - 1] = token.value;
 				}
 				
 				//-- check if this identifier matches a function name if so we are going to rename it so that it does not conflict
 				if(token.value.toLowerCase() == functionname.toLowerCase()){
 					//-- make sure this is not a recursive call to the function itself
 					if(i+1<tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
 						//-- update function name to ensure it matches the case of the function definition
 						token.value = functionname;
 					}else{
 						//-- change the name of the function return variable
 						token.value = "return_" + functionname.toLowerCase();
 					}
 				//-- check if this identifier is preceded by an identifier/keyword and a period which may indicate a field reference
 				}else if(i>1 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "." &&	(tokenList[i-2].name == "IDENTIFIER" || tokenList[i-2].name == "KEYWORD")){
 					//-- check if this identifier is followed by brackets and a number which may indicate a field retrieval
 					if((i+1 < tokenList.length) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "(" &&
 					(i+2 < tokenList.length) && tokenList[i+2].name == "NUMBER" &&
					(i+3 < tokenList.length) && tokenList[i+3].name == "OPERATOR" && tokenList[i+3].value == ")"){
 					
						//-- check if this is a known property name or method if so we won't change it
 						var id = this.kwtable[token.value.toUpperCase()];
 		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
 						
 		 		 			//-- change the array notation brackets
 		 		 			//tokenList.splice(i+1, 3);
 		 		 			tokenList[i+1].value = "[";
 		 		 			tokenList[i+3].value = "]";
 						
 		 		 			//-- insert function call
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "KEYWORD",
 		 		 				value: "getField",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;
 		 		 			//-- insert opening parenthesis
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "OPERATOR",
 		 		 				value: "(",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;					
 		 		 			//-- convert the token to a quoted string
 		 		 			token.name = "QUOTEDSTRING";
 		 		 			//-- insert closing parenthesis
 		 		 			tokenList.splice(i+1, 0, {
 		 		 				name: "OPERATOR",
 		 		 				value: ")",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;
 		 		 		} 		  		 		 		
 					//-- check if this identifier is followed by an assignment/equality operator which may indicate field assignment
 					}else if(!ifcondition && (i+2 < tokenList.length) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "="){
						//-- check if this is a known property name or method if so we won't change it
 						var id = this.kwtable[token.value.toUpperCase()];
 		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
 		 		 			//-- insert function call
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "KEYWORD",
 		 		 				value: "setField",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;
 		 		 			//-- insert opening parenthesis
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "OPERATOR",
 		 		 				value: "(",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;					
 		 		 			//-- convert the token to a quoted string
 		 		 			token.name = "QUOTEDSTRING";
 		 		 			//-- change equals to a comma
 		 		 			tokenList[i+1].value = ",";
 						
 		 		 			//TODO - this may need more work to account for more than one term (ie. contents in parenthesis, or end of line)
 		 		 			//-- assign termination needed as a closing parenthesis
 		 		 			termneeded = ")" + termneeded;
 		 		 		}
 					//-- check if this identifier might be a field retrieval
					}else {
						//-- check if this is a known property name if so we won't change it
						//-- check if this is a known property name or method if so we won't change it
 						var id = this.kwtable[token.value.toUpperCase()];
 		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
 		 		 			//-- insert function call
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "KEYWORD",
 		 		 				value: "getField",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;
 		 		 			//-- insert opening parenthesis
 		 		 			tokenList.splice(i, 0, {
 		 		 				name: "OPERATOR",
 		 		 				value: "(",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;					
 		 		 			//-- convert the token to a quoted string
 		 		 			token.name = "QUOTEDSTRING";
 		 		 			//-- insert closing parenthesis
 		 		 			tokenList.splice(i+1, 0, {
 		 		 				name: "OPERATOR",
 		 		 				value: ")",
 		 		 				pos: -1,
 		 		 				depth: -1
 		 		 			});
 		 		 			i++;
 		 			
		 		 			//-- getField returns an array so need to convert any follow on parenthesis
	 						var tempindex = i+1;
 		 		 			if(tempindex < tokenList.length && tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
 		 		 				tokenList[tempindex].value = "[";
 		 		 				
 		 		 				//-- adjust closing parenthesis
 		 		 				var tempcount = 1;
 		 		 				while(tempindex < (tokenList.length - 1)){
 		 		 					tempindex = tempindex + 1;
 		 		 					if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
 		 		 						tempcount = tempcount + 1;
 		 		 					}else if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == ")"){
 		 		 						tempcount = tempcount - 1;
 		 		 						if(tempcount === 0){
 		 		 							//-- found closing parenthesis so convert it 
 		 		 							tokenList[tempindex].value = "]";
 		 		 							break;
 		 		 						}
 		 		 					}
 		 		 				}
	 						} 		 		 			
 		 		 		} 		 		 		
 					}			
 				}
 
 				//-- check if this identifier is a declared variable and if it is followed by parenthesis 
 				if(globalvars.indexOf(token.value.toUpperCase()) > -1 || functionvars.indexOf(token.value.toUpperCase()) > -1){
 	 				if(i+1 < tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
 	 					tokenList[i+1].value = "[";
 	 					var lpcount = 1;
 	 					var rpcount = 0;
 	 					var c=i+1;
 	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
 	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
 	 							lpcount ++;
 	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
 	 							rpcount ++;
 	 							if(rpcount == lpcount){
 	 								tokenList[c].value = "]";
 	 	 							break;
 	 							}
 	 						}
 	 						c++;
 	 					}
 	 				}
 	 			} 				
 			//--4 FUNCTION	
 	 		}else if(token.name == "FUNCTION"){
 				functiondef = true;  	 		
 				functionname = token.value;
 				if(classstatement){
 					token.flags = "INCLASS";
 				}
 			//--5 GET PROPERTY
 	 		}else if(token.name == "PROPERTY GET"){
 	 			propertydef = true;
 	 			propertyname = token.value;
 			//--6 SET PROPERTY 
 	 		}else if(token.name == "PROPERTY SET"){
 	 			propertydef = true;
 	 			propertyname = token.value; 	 			
 			//--7 CLASS
 	 		}else if(token.name == "CLASS"){
 	 			classdef = true;
 	 			classstatement = true;
 			//--8 NEWLINE
 	 		}else if(token.name == "NEWLINE"){
 	 			//--check if a case statement is on this line
 	 			if(casestatement){
 	 				var colonfound = false;
 					var c = i;
 	 				//-- check to see if a colon exists on this line or not
 	 				while(c>0 && tokenList[c-1].name !== "NEWLINE"){
	 					c--;
	 					if(tokenList[c].name === "OPERATOR" && tokenList[c].value == ":"){
	 						colonfound = true;
	 					}
 	 				} 	
 	 				//-- if no colon found insert one to close off the case statement
 	 				if(!colonfound){
 						tokenList.splice(i, 0, {
 							name: "OPERATOR",
 							value: ":",
 							pos: -1,
 							depth: -1	 							
 						});
 	 				}
 	 			}
 	 			
 	 			//--check to see if a function or class definition is underway
 	 			if(functiondef || classdef){
 					//--check if the function or class definition header is missing parenthesis
 	 				if(lparencount == 0 && rparencount == 0){
 	 					//-- insert an opening parenthesis after the function declaration before the newline
 	 					tokenList.splice(i, 0, {
 	 						name: "OPERATOR",
 	 						value: "(",
 	 						pos: -1,
 	 						depth : -1
 	 					});
 	 					i++;

 	 					//-- insert a closing parenthesis after the function declaration before the newline
 	 					tokenList.splice(i, 0, {
 	 						name: "OPERATOR",
 	 						value: ")",
 	 						pos: -1,
 	 						depth : -1
 	 					});
 	 					
 	 					lparencount = 1;
 	 					rparencount = 1;
 	 				} 	 				
 	 			}
 	 			
 	 			if(termneeded != ""){
  					//-- insert termination characters before the end of the line
  					tokenList.splice(i, 0, {
  						name: "OPERATOR",
  						value: termneeded,
  						pos: -1,
  						depth : -1
  					});
  					i++;
  					termneeded = "";
 	 			}
 	 			
 	 			if(hascomment){
 					var c = i;
 	 				//-- check to see if a comment on this line needs to be shifted
 	 				while(c>0 && tokenList[c-1].name !== "NEWLINE"){
	 					c--;
	 					if(tokenList[c].name === "COMMENT"){
	 						//-- check to see if this comment is preceeded by a newline (ie. all on its own)
		 					if(c>0 && tokenList[c-1].name !== "NEWLINE"){
		 						//-- shift comment to next line
		 						tokenList.splice(i+1, 0, {
		 							name: "COMMENT",
		 							value: tokenList[c].value,
		 							pos: tokenList[c].pos,
		 							depth: tokenList[c].depth	 							
		 						});
		 						//-- put the comment on its own row
		 						tokenList.splice(i+2, 0, {
		 							name: "NEWLINE",
		 							value: "",
		 							pos: -1,
		 							depth: -1	 							
		 						});		 				
		 						//-- remove the earlier comment
		 						tokenList.splice(c, 1);
		 						i--;
		 					}
	 					}
 	 				}
 	 			} 	 			
 	 			
	 			//--check to see if we may have a single line/short form if statement
 	 			if(ifstatement){
 	 				var thenfollowed = false;
 	 				var f = i;
 	 				while(f>0 && tokenList[f-1].name !== "NEWLINE"){
 	 					f--;
 	 					if(tokenList[f].name === "KEYWORD" && tokenList[f].value.toUpperCase() === "THEN"){
 	 						break;
 	 					}else if(tokenList[f].name !== "COMMENT"){
 	 						thenfollowed = true;
 	 						break;
 	 					}
 	 				}
 	 				if(thenfollowed){
 	 					//-- insert an additional new line
 	 					tokenList.splice(i, 0, {
 	 						name: "NEWLINE",
 	 						value: "",
 	 						pos: -1,
 	 						depth : -1
 	 					}); 	 					
 	 					i++;
 	 					
 	 					//-- close off the if statement
 	 					tokenList.splice(i, 0, {
 	 						name: "KEYWORD",
 	 						value: "END IF",
 	 						pos: -1,
 	 						depth : -1
 	 					}); 	 					
 	 					i++;
 	 				}
 	 			} 	 			
 	 			
 	 			if(forallstatement){
 	 				//-- insert a new row
					tokenList.splice(i+1, 0, {
						name: "NEWLINE",
						value: "",
						pos: -1,
						depth: -1	 							
					});
					//-- insert variable assignment from array
 	 				tokenList.splice(i+1, 0, {
 	 					name: "OPERATOR",
 	 					value: "]",
 	 					pos: -1,
 	 					depth: -1
 	 				});					
 	 				tokenList.splice(i+1, 0, {
 	 					name: "IDENTIFIER",
 	 					value: forvar,
 	 					pos: -1,
 	 					depth: -1
 	 				});					
 	 				tokenList.splice(i+1, 0, {
 	 					name: "OPERATOR",
 	 					value: "[",
 	 					pos: -1,
 	 					depth: -1
 	 				});					
 	 				for(var x=forallvals.length -1; x>-1; x--){
 	 	 				//-- insert a new row
 						tokenList.splice(i+1, 0, forallvals[x]);	 					
 	 				}
	 				tokenList.splice(i+1, 0, {
 	 					name: "ASSIGNMENT",
 	 					value: "=",
 	 					pos: -1,
 	 					depth: -1
 	 				}); 	 				
 	 				tokenList.splice(i+1, 0, {
 	 					name: "IDENTIFIER",
 	 					value: forvar,
 	 					pos: -1,
 	 					depth: -1
 	 				});
 	 				//-- store array element key in case it is needed later
 					tokenList.splice(i+1, 0, {
						name: "NEWLINE",
						value: "",
						pos: -1,
						depth: -1	 							
					});
 	 				tokenList.splice(i+1, 0, {
 	 					name: "IDENTIFIER",
 	 					value: forvar,
 	 					pos: -1,
 	 					depth: -1
 	 				});					
	 				tokenList.splice(i+1, 0, {
 	 					name: "ASSIGNMENT",
 	 					value: "=",
 	 					pos: -1,
 	 					depth: -1
 	 				}); 	 						
 	 				tokenList.splice(i+1, 0, {
 	 					name: "IDENTIFIER",
 	 					value: "ListTag",
 	 					pos: -1,
 	 					depth: -1
 	 				});
 	 				tokenList.splice(i+1, 0, {
 	 					name: "KEYWORD",
 	 					value: "var",
 	 					pos: -1,
 	 					depth: -1
 	 				}); 	 				
 	 			}
 	 			ifstatement = false;
 	 			ifcondition = false;
				forstatement = false;
				forallstatement = false;
				hascomment = false;
				forvar = "";
				forallvals = [];
 	 			assignmentfound = false;
 				assignlparencount = 0;
 				assignrparencount = 0;
 				termneeded = "";
 				usestatement = false;
 				casestatement = false;
 				
 			//--9 OPERATOR
 			}else if(token.name == "OPERATOR"){ 	
 				//-- check if a period for some special cases
 				if(token.value == "."){
 					//-- check if in a with statement and prior token is not an identifier
 					if(inwith && (i-1 >= 0) && tokenList[i-1].name != "IDENTIFIER"){
 						//-- also check that prior token is not a property
 						var kw = this.kwtable[tokenList[i-1].value.toUpperCase()];
 						if(typeof kw === 'undefined' || ! kw.isproperty){
 	 						if(withname[withname.length - 1] !== ""){
 	 							//-- insert object name before period
 	 							tokenList.splice(i, 0, {
 	 								name: "IDENTIFIER",
 	 								value: withname[withname.length -1],
 	 								pos: -1,
 	 								depth : -1
 	 							});
 	 							i++; 						
 	 						} 							
 						}
 					}
 				}else if(token.value == "("){
	 				if(functiondef || classdef){
 						lparencount ++;
	 				}else{
	 					lparencount = 0;
	 				}
	 				assignlparencount ++;
	 				
	 				//--check to see if the previous reference might be a declared variable
	 				if(i>1 && tokenList[i-1].name == "IDENTIFIER"){
	 					if(functionvars.indexOf(tokenList[i-1].value.toUpperCase()) > -1 || globalvars.indexOf(tokenList[i-1].value.toUpperCase()) > -1){
	 						//-- found a variable to the left so assume this is an array and convert the parenthesis
	 						token.value = "[";
	 						assignlparencount --;
	 						
	 						//-- adjust closing parenthesis
	 						var tempcount = 1;
	 						var tempindex = i;
	 						while(tempindex < tokenList.length){
	 							tempindex = tempindex + 1;
	 							if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
	 								tempcount = tempcount + 1;
	 							}else if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == ")"){
	 								tempcount = tempcount - 1;
		 							if(tempcount === 0){
		 								//-- found closing parenthesis so convert it 
		 								tokenList[tempindex].value = "]";
		 								break;
		 							}
	 							}
	 						}
	 					}	 					
	 				}
 				}else if(token.value == ")"){
	 				if(functiondef || classdef){
	 					rparencount ++;
	 				}else{
	 					rparencount = 0;
	 				}
	 				assignrparencount ++;	 				
 				}
 				
				if(token.value === "_"){
 					//-- if an underscore character found followed by newline remove the undercore and the newline
 					if((i+1 < tokenList.length) && (tokenList[i+1].name == "NEWLINE")){
 						tokenList.splice(i, 2);
 					}else{
 						tokenList.splice(i, 1);
 					} 	
 					i--;
 				}else if(token.value === "="){
 					if(!ifcondition && !assignmentfound && assignlparencount === assignrparencount){
 						token.name = "ASSIGNMENT";
 					}else{
 						token.name = "EQUALITY";
 						//-- check if the prior token was an integer value, true, or false if so use more specific format
 						if(i>0 && (tokenList[i-1].name == "NUMBER" || (tokenList[i-1].name == "QUOTEDSTRING" && jQuery.trim(tokenList[i-1].value) === "") || (tokenList[i-1].name == "KEYWORD" && (tokenList[i-1].value.toUpperCase() == "TRUE" || tokenList[i-1].value.toUpperCase() == "FALSE" || tokenList[i-1].value.toUpperCase() == "NULL")))){
 							token.value = "===";
 						}else{
 							token.value = "==";
 						}
 					}
 				}else if(token.value === "<>"){
					//-- check if the prior token was an integer value, true, or false if so use more specific not equals format
					if(i>0 && (tokenList[i-1].name == "NUMBER" || (tokenList[i-1].name == "QUOTEDSTRING" && jQuery.trim(tokenList[i-1].value) === "") || (tokenList[i-1].name == "KEYWORD" && (tokenList[i-1].value.toUpperCase() == "TRUE" || tokenList[i-1].value.toUpperCase() == "FALSE" || tokenList[i-1].value.toUpperCase() == "NULL")))){
						token.value = "!==";
					}
 				}	
				
			//--10 NUMBER
 			}else if(token.name == "NUMBER"){
 				//-- check if the prior token is equality, if so make it more specific due to number comparison
				if(i>0 && tokenList[i-1].name == "EQUALITY"){
					tokenList[i-1].value = "===";
 	 			//-- check if the prior token is not equals, if so make it more specific due to number comparison 						
				}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
						tokenList[i-1].value = "!==";
				}		
			//--11 QUOTEDSTRING
 			}else if(token.name == "QUOTEDSTRING"){
 				if(jQuery.trim(token.value) === ""){
 					//-- check if the prior token is equality, if so make it more specific due to empty string
 					if(i>0 && tokenList[i-1].name == "EQUALITY"){
 						tokenList[i-1].value = "===";
 	 				//-- check if the prior token is not equals, if so make it more specific due to empty string 						
 					}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
 						tokenList[i-1].value = "!==";
 					}
 				//-- if this is a use statement, then lets rename the library reference
 				}else if(usestatement){
 					token.value = token.value + "JS.js";
 				}
 			}
 						
 			//--check if function definition is in progress
 			if(functiondef){
 				//-- check if a return type is being declared, if so we want to clear it
 				if((tokenList.length > i+1) && tokenList[i+1].name === "KEYWORD" && tokenList[i+1].value.toUpperCase() === "AS"){
 					tokenList.splice(i+1, 2); //--remove the AS keyword plus the following type keyword
 				}
 				
 				//--check if the function definition header is complete
 				if((lparencount != 0 || rparencount != 0) && lparencount === rparencount ){ 				
				
 					//-- insert an an opening brace after the function declaration
 					tokenList.splice(i+1, 0, {
 						name: "OPERATOR",
 						value: "{",
 						pos: -1,
 						depth : -1
 					});
				
 					//-- insert a newline
	 				tokenList.splice(i+2, 0, {
	 					name: "NEWLINE",
	 					value: "",
	 					pos: -1,
	 					depth : -1
	 				});
	 				
 					var functionreturnname = "return_" + functionname.toLowerCase();
 					if(functionname.toLowerCase() == "queryopen" || functionname.toLowerCase() == "queryclose" || functionname.toLowerCase() == "querysave" || functionname.toLowerCase() == "querymodechange"){
 						functionreturnname = "Continue";
 					}else{ 					
 						//--declare a return value for the function
 						tokenList.splice(i+3, 0, {
 							name: "KEYWORD",
 							value: "dim",
 							pos: -1,
 							depth : -1
 						});
 					}
 					
 					tokenList.splice(i+4, 0, {
 						name: "IDENTIFIER",
 						value: functionreturnname,
 						pos: -1,
 						depth : -1
 					});
 					tokenList.splice(i+5, 0, {
 						name: "ASSIGNMENT",
 						value: "=",
 						pos: -1,
 						depth : -1
 					}); 
 					var functionreturndefault = "null";
 					if(functionname.toLowerCase() == "queryopen" || functionname.toLowerCase() == "queryclose" || functionname.toLowerCase() == "querysave"){
 						functionreturndefault = "(typeof Continue != 'undefined' ? Continue : true)";
 					}
 					tokenList.splice(i+6, 0, {
 						name: "PASSTHRU",
 						value: functionreturndefault,
 						pos: -1,
 						depth : -1
 					});  					
 					//-- insert a newline
	 				tokenList.splice(i+7, 0, {
	 					name: "NEWLINE",
	 					value: "",
	 					pos: -1,
	 					depth : -1
	 				});
	 				
 					functiondef = false;
 					lparencount = 0;
 					rparencount = 0; 					
 				}
 			}
 			
 			//--check if class definition is in progress
 			if(classdef){
 				//-- check if a return type is being declared, if so we want to clear it
 				if((tokenList.length > i+1) && tokenList[i+1].name === "KEYWORD" && tokenList[i+1].value.toUpperCase() === "AS"){
 					tokenList.splice(i+1, 2); //--remove the AS keyword plus the following type keyword
 				}
 				
 				//--check if the function definition header is complete
 				if((lparencount != 0 || rparencount != 0) && lparencount === rparencount ){ 				
				
 					//-- insert an an opening brace after the function declaration
 					tokenList.splice(i+1, 0, {
 						name: "OPERATOR",
 						value: "{",
 						pos: -1,
 						depth : -1
 					});
				
 					//-- insert a newline
	 				tokenList.splice(i+2, 0, {
	 					name: "NEWLINE",
	 					value: "",
	 					pos: -1,
	 					depth : -1
	 				});
	 				
 					classdef = false;
 					lparencount = 0;
 					rparencount = 0; 					
 				}
 			}
 			
 			//--check if property definition is in progress
 			if(propertydef){
 				//-- check if a return type is being declared, if so we want to clear it
 				if((tokenList.length > i+1) && tokenList[i+1].name === "KEYWORD" && tokenList[i+1].value.toUpperCase() === "AS"){
 					tokenList.splice(i+1, 2); //--remove the AS keyword plus the following type keyword
 				}
 				
 				propertydef = false;
 			}
 							
 		}//--end of main loop
 		
 		//-- secondary pass through the list
 		for(var i=0; i<tokenList.length; i++){
 			var token = tokenList[i];
 			
 			//-- correct !x===y to be x!==y
 			if(token.name === "EQUALITY" && (token.value === "===" || token.value === "==")){
 				if(i-2 > -1){
 					if(tokenList[i-2].name == "KEYWORD" && (tokenList[i-2].value.toUpperCase() == "NOT")){
 						token.value = "!" + token.value.slice(1);
 						tokenList.splice(i-2, 1);
 						i--;
 					}
 				}
 			//-- adjust ComposeDocument to be ComposeResponseDocument if response form referenced
 			}else if(token.name === "KEYWORD" && token.value.toUpperCase() === "COMPOSEDOCUMENT"){
				//--responseforms is a global variable that may or may not be set to the names of response forms
				if(typeof responseforms !== "undefined" && Array.isArray(responseforms) && responseforms.length > 0){
					var a=i+1;
					var cont = true;					
					while(a<tokenList.length && tokenList[a].name !== "NEWLINE" && cont){
						if(tokenList[a].name == "ASSIGNMENT"){
							cont = false;
						}else if(tokenList[a].name == "OPERATOR" && tokenList[a].value == ")"){
							cont = false;
						}
													
						if(tokenList[a].name == "QUOTEDSTRING"){
							var formname = tokenList[a].value;
							if(formname.slice(0,1) == '"' && formname.slice(-1) == '"'){
								formname = formname.slice(1, formname.length-1);
							}						
							if(responseforms.indexOf(formname) >-1){						
								token.value = 'ComposeResponseDocument';
								cont = false;
							}
						}
						a++;
					}
				}	
 			//-- modify format of case statements
 			}else if(token.name === "KEYWORD" && token.value.toUpperCase() === "CASE"){
 				var c=i+1;
 				while(c<tokenList.length && tokenList[c].name !== "NEWLINE"){
 					if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ","){
 						//-- found a comma in a case statement that we need to split
 						tokenList.splice(c, 0, {
 							name: "OPERATOR",
	 	 					value: ":",
	 	 					pos: -1,
	 	 					depth: -1
	 	 				});
 						c++;
		 				tokenList.splice(c+1, 0, {
	 	 					name: "KEYWORD",
	 	 					value: "case",
	 	 					pos: -1,
	 	 					depth: -1
	 	 				});
		 				tokenList[c].name = "NEWLINE";
		 				tokenList[c].value = "";
					}
 					c++;
 				}
 			//-- insert additional parameters for picklist and dialogbox and generate an anonymous function call
			}else if(token.name === "KEYWORD" && (token.value.toUpperCase() === "PICKLISTCOLLECTION" || token.value.toUpperCase() === "PICKLISTSTRINGS" || token.value.toUpperCase() === "DIALOGBOX")){
				var pvarname = "";
				
				var a=i-1;
				var cont = true;
				while(a>0 && tokenList[a].name !== "NEWLINE" && cont){
					if(tokenList[a].name == "ASSIGNMENT" ){
						if(a > 0 && tokenList[a-1].name == "IDENTIFIER"){
							pvarname = tokenList[a-1].value;
							cont = false;
						}
					}
					a--;
				}
				
				var pclp = 0;
				var pcrp = 0;
				var p=i+1;
				var cont = true;
 				while(p<tokenList.length && cont){
 					if(tokenList[p].name == "OPERATOR"){
 						if(tokenList[p].value == "("){
 							pclp ++;
 						}else if(tokenList[p].value == ")"){
 							pcrp ++;
 						}
 					}	
 					if(pclp > 0 && pclp == pcrp){
 						//-- at closure of picklist

 						//-- remove closure as we are going to move it
 						tokenList.splice(p, 1);
 						
 						//-- add some extra parameters
 		 				tokenList.splice(p, 0, {
	 	 					name: "PASSTHRU",
	 	 					value: "function(" + pvarname + "){",
	 	 					pos: -1,
	 	 					depth: -1
	 	 				}); 
 		 				anonclosureneeded ++; //--increment counter 

 		 				tokenList.splice(p, 0, {
	 	 					name: "OPERATOR",
	 	 					value: ",",
	 	 					pos: -1,
	 	 					depth: -1
	 	 				});
  		 				 		 				
 		 				cont = false;
 					}
 					p++;
 				}
 			//-- add additional params to save method
			}else if(token.name === "KEYWORD" && token.value.toUpperCase() === "SAVE"){
				var a=i+1;
				var cont = true;
				while(a<tokenList.length && tokenList[a].name !== "NEWLINE" && cont){
					if(tokenList[a].name == "OPERATOR" && tokenList[a].value == "(" ){
						if(!(tokenList[a+1].name == "OPERATOR" && tokenList[a+1].value == ")")){
							tokenList.splice(a+1, 0, {
		 	 					name: "OPERATOR",
		 	 					value: ",",
		 	 					pos: -1,
		 	 					depth: -1
		 	 				}); 							
						}
						tokenList.splice(a+1, 0, {
	 	 					name: "PASSTHRU",
	 	 					value: "{async: false, andclose: false, Navigate: false}",
	 	 					pos: -1,
	 	 					depth: -1
	 	 				}); 
						break;
					}
					a++;
				}	
 			//-- close off anonymous function call
			}else if(anonclosureneeded > 0 && token.name === "KEYWORD" && (token.value.toUpperCase() == "END FUNCTION" || token.value.toUpperCase() == "END CLASS" || token.value.toUpperCase() == "END SUB")){
				while(anonclosureneeded > 0){
					tokenList.splice(i, 0, {
						name: "OPERATOR",
						value: ")",
						pos: -1,
						depth: -1
					});
					tokenList.splice(i, 0, {
						name: "PASSTHRU",
						value: "}",
						pos: -1,
						depth: -1
					});				
					i++;
					i++;
					anonclosureneeded --;
				}
 			}
 			
 		}//--end of secondary loop

		while(anonclosureneeded > 0){
			tokenList.push({
 				name: "PASSTHRU",
 				value: "}",
 				pos: -1,
 				depth: -1
 			});
			tokenList.push({
 				name: "OPERATOR",
 				value: ")",
 				pos: -1,
 				depth: -1
 			});			
			anonclosureneeded --;
 		}
		
		if(termneeded != ""){
			//-- add termination characters that may not have been added yet
			tokenList.push({
				name: "OPERATOR",
				value: termneeded,
				pos: -1,
				depth : -1
			});
			if(termneeded.slice(-1) === ")"){
				//-- add newline character to ensure that the line is delimited
				tokenList.push({
					name: "NEWLINE",
					value: "",
					pos: -1,
					depth : -1
				});				
			}
			termneeded = "";
 		}
 		
 		return tokenList;
 	}//--end _adjustTokenList

 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: translateToJS
 	 * Performs conversion on a token node and its immediate children to generate JavaScript equivalent
 	 * Inputs: tokenNode - token - current token in the tree to proces
 	 *         lastNode - token - copy of last token processed in case we need to examine it in relation to this one
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
 	this.translateToJS = function(tokenNode, lastNode) {	
 			var lparen = 0;
 			var rparen = 0;
 			var paramcount = 0;
 			var priortoken = null;
 			var addwhitespace = true;

 			if(tokenNode.name == "OPERATOR"){
 				if(tokenNode.value == "&"){
 					tokenNode.value = "+";
 				}else if(tokenNode.value == "."){
 					addwhitespace = false;
 				}else if(tokenNode.value == "~"){
 					tokenNode.value = "";
 					addwhitespace = false;
 				}else if(tokenNode.value == "#"){
 					tokenNode.value = "";
 					addwhitespace = false;
 				}else if(tokenNode.value == "<>"){
 					tokenNode.value = "!=";
 				}
 			}else if(tokenNode.name == "EQUALITY" && tokenNode.value == "="){
 				tokenNode.value = "==";
 			}else if(tokenNode.name == "ASSIGNMENT"){
 				tokenNode.value = "=";
 			}else if(tokenNode.name == "KEYWORD"){
 		 		var kw = this.kwtable[tokenNode.value.toUpperCase()];
 		 		if(typeof kw !== 'undefined'){
 		 			tokenNode.value = kw.replacewith;
 		 		}
 		 		addwhitespace = true;
 			}else if(tokenNode.name == "IDENTIFIER"){
				var tempval = tokenNode.value;
				tempval = tempval.replace(/&/g, '').replace(/%/g, '');
				
				var id = this.otherconvtable[tempval.toUpperCase()];
 		 		if(typeof id !== 'undefined'){
 		 			tempval = id.replacewith;
 		 		}
 		 		
 				tokenNode.value = tempval; 	
 				addwhitespace = true;
 			}else if(tokenNode.name == "QUOTEDSTRING"){
 				var tempval = tokenNode.value;
				tempval = tempval.replace(/"/g, '\\"');
 				//tempval = safe_quotes_js(tempval, true);
 				tempval = '"' + tempval + '"';
 				tokenNode.value = tempval;
 			}else if(tokenNode.name == "COMMENT"){
 				var tempval = tokenNode.value;
 				tempval = "/*" + tempval + "*/";
 				tokenNode.value = tempval;
 			}else if(tokenNode.name == "FUNCTION"){
 				if(typeof tokenNode.flags !== "undefined" && tokenNode.flags == "INCLASS"){
 					tokenNode.value = "this." + tokenNode.value + "=function";
 				}else{
 					tokenNode.value = "function " + tokenNode.value;
 				}
// 				functiondef = true;
 			}else if(tokenNode.name == "CLASS"){
 				tokenNode.value = "function " + tokenNode.value; 
// 				classdef = true;
 			}else if(tokenNode.name == "PROPERTY GET"){
 				tokenNode.value = "Object.defineProperty(this,'" + tokenNode.value + "', { get: function(){";
// 				propertydef = true;
 			}else if(tokenNode.name == "PROPERTY SET"){
 				tokenNode.value = "Object.defineProperty(this,'" + tokenNode.value + "', { set: function(" + tokenNode.value + "){";
// 				propertydef = true;				
 			}else if(tokenNode.name == "NEWLINE"){
 				if(lastNode && lastNode.name != "ROOT" && lastNode.name != "NEWLINE" && (lastNode.value.trim().indexOf("{", lastNode.value.trim().length - 1) === -1) && (lastNode.value.trim().indexOf("}", lastNode.value.trim().length - 1) === -1) && (lastNode.value.trim().indexOf("*/", lastNode.value.trim().length - 2) === -1) && (lastNode.value.trim().indexOf(":", lastNode.value.trim().length - 1) === -1) && lastNode.value.trim() !== ""){
 					tokenNode.value = ";" + tokenNode.value;
 				}
 				addwhitespace = false;
 			}
 			if(addwhitespace){
 				tokenNode.value = tokenNode.value + " ";
 			}
 	}//--end translateToJS
 	
 	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 	 * Method: convertCode
 	 * Converts source code to a new language format.
 	 * Inputs: fcode - string - source code to be converted
 	 *              convertto - string  - output format.  JS - for JavaScript (default)
 	 * Returns: string - converted code in string format, or unaltered code if no conversion specified
 	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
 	this.convertCode = function(fcode, convertto){
 		var outputTxt = "";
 		var tokenTreeRoot = null;
 		if(typeof fcode == 'undefined' || fcode === ""){return outputTxt;}
 		
 		var convtype = (typeof convertto == 'undefined' || convertto == "" ? "JS" : convertto.toUpperCase());
 		
 		this.input(fcode);	
 		var tokenList = this.tokenize();	
 	
 		if(convtype == "JS"){
 			tokenList = this._adjustTokenList(tokenList);
 			tokenTreeRoot = this.tokenListToTree(tokenList);				
 			outputTxt = this._walkTokenTree(tokenTreeRoot, this.translateToJS);
 		}else{
 			tokenTreeRoot = this.tokenListToTree(tokenList);		
 			outputTxt = this._walkTokenTree(tokenTreeRoot);		
 		}
 		return outputTxt;
 	}//--end convertCode
 	
 }//--end LexerLS



  /*-------------------------------------------------------------------------------------------------------------------------------------------- 
   * Class: LexerPHP 
   * Analyzer and formatter for LotusScript code to convert it to other formats such as JavaScript
   * If the beautify.js file is loaded, the code can be formatted by calling beautify() on the result
   * Inputs:     usepath - string (optional) - path to other files
   * Example: 	var LexerObj = new LexerPHP(); 
   *              var outputTxt = LexerObj.convertCode(formulacode, "PHP");
   *              outputTxt = beautify(outputTxt); 	
   *------------------------------------------------------------------------------------------------------------------------------------------- */
   function LexerPHP(usepath) {
  	this.pos = 0;
  	this.buf = null;
  	this.buflen = 0;
  	this.depth = 0;
  	this.usepath = usepath && usepath != ""? usepath : "";
  	// Operator table, mapping operator -> token name
  	this.optable = {
  			'+' : {name: 'PLUS'},
  			'-' : {name: 'MINUS'},
  			'*' : {name: 'MULTIPLY'},
  			'=' : {name: 'EQUALS'},		
  			'/' : {name: 'DIVIDE'},
  			'.' : {name: 'PERIOD'},
  			'\\' : {name: 'BACKSLASH'},
  			':' : {name: 'COLON'}, 
//  			'%' : {name: 'PERCENT'},
//  			'|' : {name: 'PIPE'},
  			'#' : {name: 'HASH'},
  			'!' : {name: 'EXCLAMATION'},
  			'&' : {name: 'AMPERSAND'},
  			';' : {name: 'SEMI'},
  			',' : {name: 'COMMA'},
  			'(' : {name: 'L_PAREN'},

  			')' : {name: 'R_PAREN'},
  			'<' : {name: 'LESS_THAN', 
  				followedby : {
  					'=' : {name: 'LESS_THAN_EQUALS'},
  					'>' : {name: 'NOT_EQUAL'}
  				}
  			},
  			'>' : {name: 'GREATER_THAN',
  				followedby : {'=' : {name: 'GREATER_THAN_EQUALS'}}					
  			},
  	//		'{' : {name: 'L_BRACE'},
  	//		'}' : {name: 'R_BRACE'},
  			'[' : {name: 'L_BRACKET'},
  			']' : {name: 'R_BRACKET'},
  			'_' : {name: 'UNDERSCORE'},
  			'~' : {name: 'TILDE'}
  	};
  		
  	this.kwtable = {
  		    'ABBREVIATED' : {isproperty: true, replacewith: 'abbreviated'},			
  			'ABS' : {replacewith: 'abs'},
  			'ACOS' : {replacewith: 'acos'},
 			'ADDDOCUMENT' : {ismethod: true, replacewith: 'addEntry'},
  			'ADDNEWLINE' : {ismethod: true, replacewith: 'addNewLine'},
  			'ADDRESSBOOKS' : {ismethod : true, replacewith: 'addressBooks'},
  			'ADDTAB' : {ismethod: true, replacewith: 'addTab', followedby: "(", terminator: ")"},
  			'ADJUSTYEAR' : {ismethod : true, replacewith: 'adjustYear', followedby: "(", terminator: ')'}, 	
  			'ADJUSTMONTH' : {ismethod : true, replacewith: 'adjustMonth', followedby: "(", terminator: ')'},
  			'ADJUSTDAY' : {ismethod : true, replacewith: 'adjustDay', followedby: "(", terminator: ')'},
  			'ADJUSTHOUR' : {ismethod : true, replacewith: 'adjustHour', followedby: "(", terminator: ')'},
  			'ADJUSTMINUTE' : {ismethod : true, replacewith: 'adjustMinute', followedby: "(", terminator: ')'},
  			'ADJUSTSECOND' : {ismethod : true, replacewith: 'adjustSecond', followedby: "(", terminator: ')'},			
  			'AND' : {replacewith: '&&'},		
  			'APPENDDOCLINK' : {ismethod : true, replacewith : 'appendDocLink'},
  			'APPENDPARAGRAPHSTYLE' : {ismethod: true, replacewith: 'appendParagraphStyle'}, 			
  			'APPENDRTITEM' : {ismethod : true, replacewith : 'appendRTItem'},
  			'APPENDSTYLE' : {ismethod: true, replacewith: 'appendStyle'},
  			'APPENDTABLE' : {ismethod: true, replacewith: 'appendTable'},
  			'APPENDTEXT' : {ismethod: true, replacewith: 'appendText', followedby: "(", terminator: ")"},
  			'APPENDTOTEXTLIST' : {ismethod: true, replacewith: 'appendToTextList'},
  			'ARRAYAPPEND' : {replacewith: 'array_push'},
  			'ARRAYGETINDEX' : {ismethod : true, replacewith: '_ArrayGetIndex'},
  			'AUTOUPDATE' : {isproperty: true, replacewith: 'autoUpdate'},
  			'BOLD' : {isproperty: true, replacewith: 'bold'},
  			'ARRAYUNIQUE' : {replacewith: '_ArrayUnique', followedby: "(", terminator: ')'},
  			'AS' : {replacewith: '='},
  			'ASIN' : {replacewith: '_ASin'},
  			'ATN' : {replacewith: '_ATan'},
  			'ATN2' : {replacewith: '_ATan2'}, 
  			'BEEP' : {replacewith: '/* beep */'},
  			'BEGININSERT' : {ismethod: true, replacewith: 'beginInsert'},
  			'BINARY' : {},
  			'BOLD' : {isproperty: true, replacewith: 'bold'},
  			'BOOLEAN' : {replacewith: 'null'},
  			'BYVAL' : {replacewith: ''},
  			'CALL' : {replacewith: ''},
  		    'CANONICAL' : {isproperty: true, replacewith: 'canonical'},			
  			'CASE' : {replacewith: 'case'},
  			'CASE ELSE' : {replacewith: 'default'},
  			'CBOOL' : {replacewith: 'Boolean'},
  			'CDAT' : {replacewith: '_CDat'},
  			'CDBL' : {replacewith: 'parseFloat'}, 	
  			'CHR' : {replacewith: '_Char'},
  			'CHR$' : {replacewith: '_Char'},
  			'CHR' : {replacewith: '_Char'},
  			'CINT' : {replacewith: 'intval'},
  		    'CLASS' : {replacewith: 'class'},
  		    'CLOSE' : {ismethod : true, replacewith: 'fclose', followedby: "(", terminator: ')'},
  			'COLOR' : {isproperty: true, replacewith: 'color'},
  		    'COLUMNS' : {isproperty: true, replacewith: 'columns'},
  		    'COMMON' : {isproperty: true, replacewith: 'common'},
  		    'COMMONUSERNAME' : {isproperty: true, replacewith: 'commonUserName'},
  		    'COMPARE' : {replacewith: 'compare'},
  		    'COMPOSEDOCUMENT' : {ismethod : true, replacewith: 'composeDocument', followedby: "(", terminator: ')'},
  		    'COMPOSERESPONSEDOCUMENT' : {ismethod : true, replacewith: 'composeResponseDocument', followedby: "(", terminator: ')'}, 		    
  		    'COMPUTEWITHFORM' : {ismethod : true, replacewith: 'computeWithForm', followedby: "(", terminator: ')'},
  		    'CONST' : {replacewith: 'define', followedby: "(", terminator: ')'},
  		    'COPYALLITEMS' : {ismethod : true, replacewith: 'copyAllItems', followedby: "(", terminator: ')'},
  		    'COPYITEMTODOCUMENT' : {ismethod: true, replacewith: 'copyItemToDocument'},
  		    'COS' : {replacewith: '_Cos'},
  		    'COUNT' : {isproperty: true, replacewith: 'count'},
  			'COLUMNVALUES' : {isproperty: true, replacewith: 'columnValues'},
  			'CONTAINS' : {ismethod: true, replacewith: 'contains'},
  			'COPYITEM' : {ismethod: true, replacewith: 'copyItem'},
  		    'COUNTRY' : {isproperty: true, replacewith: 'country'},						
  			'CREATED' : {isproperty: true, replacewith: 'created'}, 					    
  		    'CREATECOLOROBJECT' : {ismethod : true, replacewith: 'createColorObject', followedby: "(", terminator: ")", flag: true},
  		    'CREATEDATERANGE' : {ismethod: true, replacewith: 'createDateRange', followedby: "(", terminator: ")"},
  		    'CREATEDOCUMENT' : {ismethod : true, replacewith: 'createDocument', followedby: "(", terminator: ')'},
  		    'CREATENAME' : {ismethod : true, replacewith: 'createName'},
  		    'CREATENAVIGATOR' : {ismethod : true, replacewith: 'createNavigator', followedby: "(", terminator: ')'},
 			'CREATERANGE' : {ismethod: true, replacewith: 'createRange', followedby: "(", terminator: ')'}, 				 
  		    'CREATERICHTEXTITEM' : {ismethod : true, replacewith: 'createRichTextItem'},
  		    'CREATERICHTEXTSTYLE' : {ismethod : true, replacewith: 'createRichTextStyle', followedby: "(", terminator: ')'},
  			'CREATERICHTEXTPARAGRAPHSTYLE' : {ismethod : true, replacewith: 'createRichTextParagraphStyle', followedby: "(", terminator: ')'}, 		    
  			'CREATEVIEWNAV' : {ismethod : true, replacewith: 'createViewNav'},
  			'CREATEVIEWNAVFROMCATEGORY' : {ismethod : true, replacewith: 'createViewNavFromCategory'},
  		    'CSTR' : {replacewith: '_Text'},
 		    'CURRENCY' : {replacewith: 'null'},
  		    'CURRENTAGENT' : {isproperty: true, replacewith: 'currentAgent'},
  			'CURRENTACCESSLEVEL' : {isproperty: true, replacewith: 'currentAccessLevel'},	
  			'CURRENTCALENDARDATETIME' : {isproperty: true, replacewith: 'currentCalendarDateTime'},
  		    'CURRENTDATABASE' : {isproperty: true, replacewith: 'currentDatabase'}, 	
  		    'CURRENTDOCUMENT' : {isproperty: true, replacewith: 'currentDocument'},
  		    'CURRENTVIEW' : {isproperty: true, replacewith: 'currentView'},
  		    'CVAR' : {replacewith: ''},
  		    'CVDAT' : {replacewith: '_CDat'},
  		    'DATATYPE' : {replacewith: 'typeof'},
  		    'DATE' : {replacewith: '_Date'},
  		    'DATENUMBER' : {replacewith: '_DateNumber'},
  		    'DATETIMEVALUE' : {isproperty: true, replacewith: 'dateTimeValue'}, 
  		    'DATEONLY' : {isproperty: true, replacewith: 'dateOnly'},
  		    'DAY' : {replacewith: '_Day'},
  		    'DECLARE' : {replacewith: ''},
  		    'DELETE' : {replacewith: '// delete'},
  		    'DELETEDOCUMENT' : {ismethod: true, replacewith: 'deleteDocument'},
  		    'DESELECTALL' : {ismethod: true, replacewith: 'deselectAllEntries', followedby: '(', terminator: ')'},
  		    'DIM' : {replacewith: ''},
 		    'DIALOGBOX' : {ismethod: true, replacewith: 'dialogBox', flag: true}, 			
  		    'DOCUMENT' : {isproperty: true, replacewith: 'document'}, 		
  		    'DOCUMENTS' : {isproperty: true, replacewith: 'documents'}, 		    
  		    'DO' : {replacewith: 'do{'},
  		    'DO WHILE' : {replacewith: 'while(', terminator: '){'},
  		    'DO UNTIL' : {replacewith: 'while(!(', terminator: '){'},
  		    'DOUBLE' : {replacewith: 'null'},
  		    'EDITDOCUMENT' : {ismethod: true, replacewith: 'editDocument', followedby: '(', terminator: ')'},
  		    'EDITPROFILE' : {ismethod: true, replacewith: 'editProfile', followedby: '(', terminator: ')'}, 		    
 			'EDITMODE' : {isproperty: true, replacewith: 'editMode', flag: true},		    
  		    'EFFECTIVEUSERNAME' : {isproperty: true, replacewith: 'effectiveUserName'},
  		    'ELSE' : {replacewith: '}else{'},
  		    'EOF' : {replacewith: 'feof'},
  		    'ELSEIF' : {replacewith: '}else if('},
  		    'ENDDATETIME' : {isproperty : true, replacewith: 'endDateTime'},
  			'ENDINSERT' : {ismethod: true, replacewith: 'endInsert', followedby: "(", terminator: ")"},		    
  		    'END' : {replacewith: 'return'},
  		    'END CLASS' : {replacewith: '}'},
  		    'END FORALL' : {replacewith: '}'},
  		    'END FUNCTION' : {replacewith: '}'},
  		    'END IF' : {replacewith: '}'}, 		     		    
  		    'END PROPERTY' : {replacewith: '}'},
  		    'END SELECT' : {replacewith: '}'},
  		    'END SUB' : {replacewith: '}'}, 		
  		    'END TYPE' : {replacewith: '}'}, 		
  		    'END WITH' : {replacewith: ''},
  		    'ENDREM' : {replacewith: '*/'},
  		    'ERROR' : {replacewith: 'error'},
  		    'EVALUATE' : {replacewith: 'evaluateTWIG'},
  		    'EXECUTE' : {replacewith: 'eval'},
  		    'EXIT' : {replacewith: 'return'},
  		    'EXIT DO' : {replacewith: 'break'},
  		    'EXIT FOR' : {replacewith: 'break'},
  		    'EXIT FORALL' : {replacewith: 'break'},
  		    'EXIT FUNCTION' : {replacewith: 'return'},
  		    'EXIT PROPERTY' : {replacewith: 'break'},		    
  		    'EXIT SUB' : {replacewith: 'return'},
  		    'EXPLICIT' : {replacewith: ''}, 		    
  		    'FALSE' : {replacewith: 'false'},
  		    'FIELDGETTEXT' : {ismethod: true, replacewith: 'getField'},
 		    'FIELDSETTEXT' : {ismethod: true, replacewith: 'setField'},		    
 			'FILEPATH' : {isproperty: true, replacewith: 'filePath'},
  			'FINDFIRSTELEMENT' : {ismethod: true, replacewith: 'findFirstElement'}, 	
 			'FINDLASTELEMENT' : {ismethod: true, replacewith: 'findLastElement'}, 				 			
  			'FINDNEXTELEMENT' : {ismethod: true, ismethod: true, replacewith: 'findNextElement'},
 			'FINDNTHELEMENT' : {ismethod: true, replacewith: 'findNthElement'}, 				  			
  			'FIRSTLINELEFTMARGIN' : {ismethod: true, replacewith: 'firstLineLeftMargin'},
  			'FONT' : {isproperty: true, replacewith: 'font'},
  			'FONTSIZE' : {isproperty: true, replacewith: 'fontSize'},		    
  		    'FOR' : {replacewith: 'for(', terminator: '){'},
  		    'FORALL' : {replacewith : 'foreach(', terminator: '){'},
  		    'FORMAT' : {replacewith: '_Format'},
  		    'FORMAT$' : {replacewith: '_Format'},
  		    'FREEFILE' : {replacewith: 'null'},
  		    'FTSEARCH' : {ismethod : true, replacewith: 'FTSearch'},
  		    'FULLTRIM' : {replacewith: '_Trim'},
  		    'FUNCTION' : {replacewith: 'function'},
  		    'GET' : {replacewith: ''},
  		    'GETAGENT' : {ismethod: true, replacewith: 'getAgent'},
  		    'GETALLDOCUMENTS' : {ismethod : true, replacewith: 'getAllDocuments'}, 		    
  		    'GETALLDOCUMENTSBYKEY' : {ismethod : true, replacewith: 'getAllDocumentsByKey'},
  		    'GETDATABASE' : {ismethod : true, replacewith: 'getDatabase'},
  		    'GETDOCUMENT' : {ismethod : true, replacewith: 'getDocument'},
  		    'GETDOCUMENTBYKEY' : {ismethod : true, replacewith: 'getDocumentByKey'}, 
  		    'GETDOCUMENTBYUNID' : {ismethod : true, replacewith: 'getDocument'},
  		    'GETDOCUMENTBYID' : {ismethod : true, replacewith: 'getDocument'},
  		    'GETENTRYBYKEY' : {ismethod : true, replacewith: 'getDocumentByKey'},
  		    'GETELEMENT' : {ismethod : true, replacewith: 'getElement', followedby: "(", terminator : ")"},
  		    'GETENVIRONMENTVAR' : {ismethod : true, replacewith: 'getEnvironmentVar'},	
  			'GETENVIRONMENTVALUE' : {ismethod : true, replacewith: 'getEnvironmentVar'}, 	
 			'GETENVIRONMENTSTRING' : {ismethod : true, replacewith: 'getEnvironmentVar'},  
 			'GETFIRST' : {ismethod : true, replacewith: 'getFirstDocument', followedby: "(", terminator: ")"},
  		    'GETFIRSTDOCUMENT' : {ismethod : true, replacewith: 'getFirstDocument', followedby: "(", terminator : ")"}, 
  		    'GETFIRSTITEM' : {ismethod : true, replacewith: 'getFirstItem'},
  		    'GETITEMVALUE' : {ismethod : true, replacewith: 'getField'},
  		    'GETNEXT' : {ismethod : true, replacewith: 'getNext'},
  		    'GETNEXTDOCUMENT' : {ismethod : true, replacewith: 'getNextDocument'},  	
  		    'GETNEXTSIBLING' : {ismethod : true, replacewith: 'getNextSibling'},
  		    'GETNTHDOCUMENT' : {ismethod : true, replacewith: 'getNthDocument'},  
  		    'GETLASTDOCUMENT' : {ismethod : true, replacewith: 'getLastDocument', followedby: "(", terminator : ")"},  	 		    
  		    'GETPREV' : {ismethod : true, replacewith: 'getPrev'},
  		    'GETPREVDOCUMENT' : {ismethod : true, replacewith: 'getPrevDocument'},  	
  		    'GETPROFILEDOCUMENT' : {ismethod : true, replacewith: 'getProfileDocument'},
  			'GETPROFILEDOCCOLLECTION' : {ismethod : true, replacewith: 'getProfileDocCollection'},
  			'GETUNFORMATTEDTEXT' : {ismethod : true, replacewith: 'getUnformattedText', followedby: "(", terminator : ")"},
  		    'GETVIEW' : {ismethod : true, replacewith: 'getView'},
  		    'GIVEN' : {isproperty: true, replacewith: 'given'},			
  		    'GMTTIME' : {isproperty: true, replacewith: 'GMTTime'},
  		    'GOSUB' : {replacewith: '/* TODO - rework this code as it contains a gosub */', terminator: '*/', flag: true}, 		    
  		    'GOTO' : {replacewith: '/* TODO - rework this code as it contains a goto ', terminator: '*/', flag: true},
  		    'GOTOFIELD' : {ismethod: true, replacewith: 'goToField'},
  		    'HASITEM' : {ismethod : true, replacewith: 'hasItem'},
  		    'HTTPURL' : {ismethod : true, replacewith: 'httpURL'},
  		    'HOUR' : {replacewith : "_Hour"},
  		    'IF' : {replacewith: 'if('},
  		    'IMPLODE' : {replacewith: '_Implode'},
  		    'IN' : {replacewith: 'in'},
  		    'INCLUDE' : {replacewith: '/* TODO - rework this code as it contains an INCLUDE statement ', terminator: '*/', flag: true}, 		    
  		    'INITIALS' : {isproperty: true, replacewith: 'initials'},			 		    
  		    'INPREVIEWPANE' : {isproperty: true, replacewith: 'inPreviewPane'},
  		    'INSTR' : {replacewith: '_instr'},
  		    'IS' : {replacewith: '==='},
  			'ISAUTHORS' : {isproperty: true, replacewith: 'isAuthors'},
  			'ISDATE' : {replacewith: '_IsDate'},
  			'ISDELETED' : {isproperty: true, replacewith: 'isDeleted'},
  		    'ISHIERARCHICAL' : {isproperty: true, replacewith: 'isHierarchical'},			 			
  			'ISNAMES' : {isproperty: true, replacewith: 'isNames'},
  			'ISNEWDOC' : {isproperty: true, replacewith: 'isNewDoc'},
  			'ISNEWNOTE' : {isproperty: true, replacewith: 'isNewDocument'},
  			'ISPROFILE' : {isproperty: true, replacewith: 'isProfile'},
  			'ISREADERS' : {isproperty: true, replacewith: 'isReaders'},
  			'ISRESPONSE' : {isproperty: true, replacewith: 'isResponse'},
  			'ISUIDOCOPEN' : {isproperty: true, replacewith: 'isUIDocOpen'},
  			'ISVALID' : {isproperty: true, replacewith: 'isValid'},		  
  			'ITALIC' : {isproperty: true, replacewith: 'italic'},
  			'ITEMS' : {isproperty: true, replacewith: 'items'},
  		    'INTEGER' : {replacewith: 'null'},
  		    'INT' : {replacewith: '_Int'},
  		    'ISARRAY' : {replacewith: 'is_array'},
  		    'ISELEMENT' : {replacewith: '_IsElement'},
  		    'ISEMPTY' : {replacewith: '_IsEmpty'},
  		    'ISNULL' : {replacewith: '_IsNull'},
  		    'ISNUMERIC' : {replacewith: '_IsNumeric'},
  		    'ISONSERVER' : {isproperty: true, replacewith: 'isOnServer'},
  		    'JOIN' : {replacewith: '_Implode'},
  		    'LBOUND' : {replacewith: '_LBound'}, 
  		    'LCASE' : {replacewith: '_LCase'},
  		    'LEFT' : {replacewith: '_Left'},
  			'LEFTMARGIN' : {isproperty: true, replacewith: 'leftMargin'},
  			'LEN' : {replacewith: '_Length'},
  		    'LIST' : {replacewith: '', flag: true},
  		    'LINE' : {replacewith: 'fgets',followedby: '(', terminator: ')'},
  		    'LISTTAG' : {replacewith: '$key'},
  		    'LOCALTIME' : {isproperty: true, replacewith: 'localTime'},
  		    'LONG' : {replacewith: 'null'},
  		    'LOOP' : {replacewith: '}'},
  		    'LSGMTTIME' : {isproperty: true, replacewith: 'GMTTime'}, 		    
  		    'LSLOCALTIME' : {isproperty: true, replacewith: 'localTime'}, 		    
  		    'MAKERESPONSE' : {ismethod : true, replacewith: 'makeResponse'},
  		    'MID' : {replacewith: '_Mid'},
  		    'MID$' : {replacewith: '_Mid'},
  		    'MINUTE' : {replacewith: '_Minute'},
  		    'MOD' : {replacewith: '%'},
  		    'MONTH' : {replacewith: '_Month'},
  		    'MESSAGEBOX' : {replacewith: 'echo', followedby: '(', terminator: ')', flag: true}, 	
  		    'ME' : {replacewith: '$this'},
  		    'MSGBOX' : {replacewith: 'echo', followedby: '(', terminator: ')'},
  		    'NEW' : {replacewith: 'new'},
  		    'NEXT' : {replacewith: '}'},
  		    'NOCASE' : {},
  		    'NOT' : {replacewith: '!'},
 			'NOTEID' : {isproperty: true, replacewith: 'universalID'},
 			'NOTESAGENT' : {replacewith: 'null'},
  			'NOTESCOLOR' : {isproperty: true, replacewith: 'color'},	
  			'NOTESCOLOROBJECT' : {replacewith: 'null'},
  
  		    'NOTESDATABASE' : {replacewith: 'DocovaApplication'},
  		    'NOTESDATETIME' : {replacewith: 'DocovaDateTime', followedby: '(', terminator: ')'},
  		    'NOTESDATERANGE' : {replacewith: 'DocovaDateRange', followedby: '(', terminator: ')'},
  		    'NOTESDOCUMENT' : {replacewith: 'DocovaDocument'},
  		    'NOTESDOCUMENTCOLLECTION' : {replacewith: 'DocovaCollection'},
  		    'NOTESINTERNATIONAL' : {replacewith: '/* TODO - rework code to handle NotesInternational ', terminator: '*/', flag: true},
  		    'NOTESITEM' : {replacewith: 'DocovaField', followedby: '(', terminator: ')'},
  		    'NOTESNAME' : {replacewith: 'DocovaName', followedby: '(', terminator: ')'},
  		    'NOTESRICHTEXTITEM' : {replacewith: 'DocovaRichTextItem', followedby: '(', terminator: ')'},
  		    'NOTESRICHTEXTNAVIGATOR' : {replacewith: 'null', flag: true}, 		    
  		    'NOTESRICHTEXTPARAGRAPHSTYLE' : {replacewith: 'null'},
  		    'NOTESRICHTEXTRANGE' : {replacewith: 'null'},
  		    'NOTESRICHTEXTSTYLE' : {replacewith: 'DocovaRichTextStyle', followedby: '(', terminator: ')'}, 	 		    
  		    'NOTESRICHTEXTTABLE' : {replacewith: 'null'},
  		    'NOTESSESSION' : {replacewith: 'DocovaSession()'},
  		    'NOTESUIDOCUMENT' : {replacewith: 'DocovaUIDocument()'},
  		    'NOTESUIWORKSPACE' : {replacewith: 'DocovaUIWorkspace()'},
  		    'NOTESVIEW' : {replacewith: 'DocovaView()'},
  		    'NOTESVIEWCOLUMN' : {replacewith: 'null'},
  		    'NOTESVIEWENTRY' : {replacewith: 'null'}, 		     		    
  		    'NOTESVIEWNAVIGATOR' : {replacewith: 'null', flag: true}, 		    
  		    'NOTHING' : {replacewith: 'null'},
  		    'NOW' : {replacewith: "date('Y-m-d H:i:s')"},
  		    'NULL' : {replacewith: 'null'},
  		    'ON' : {replacewith: 'on'}, 	
  		    'ON ERROR' : {replacewith: '/* TODO - rework code to handle on error ', terminator: '*/', flag: true},
  		    'ON ERROR GOTO' : {replacewith: '/* TODO - rework code to handle on error goto ', terminator: '*/', flag: true},
  		    'OPENDATABASE' : {ismethod: true, replacewith: 'openDatabase'},
  		    'OPEN' : {ismethod : true, replacewith: 'fopen', followedby: '(', terminator: ')'},
  		    'OPTION' : {replacewith: ''},
  		    'OPTION COMPARE' : {replacewith: ''},
  		    'OPTION COMPARE BINARY' : {replacewith: ''}, 
  		    'OPTION COMPARE NOCASE' : {replacewith: ''},  		    
  		    'OPTION DECLARE' : {replacewith: ''}, 		    
  		    'OPTION EXPLICIT' : {replacewith: ''}, 
  		    'OR' : {replacewith: '||'},
  		    'ORGANIZATION' : {isproperty: true, replacewith: 'organization'},			 		   
  		    'ORGUNIT1' : {isproperty: true, replacewith: 'orgUnit1'},			 		    
  		    'ORGUNIT2' : {isproperty: true, replacewith: 'orgUnit2'},			 		    
  		    'ORGUNIT3' : {isproperty: true, replacewith: 'orgUnit3'},			 		    
  		    'ORGUNIT4' : {isproperty: true, replacewith: 'orgUnit4'},			 		    
  		    'PARENTDATABASE' : {isproperty: true, replacewith: 'parentApp'},
  			'PARENTDOCUMENTUNID' : {isproperty: true, replacewith: 'parentDocumentUNID'},
  			'PARAMETERDOCID' : {isproperty: true, replacewith: 'parameterDocID'},
  			'PARENTVIEW' : {isproperty: true, replacewith: 'parentView'},
  			'PICKLISTCOLLECTION' : {ismethod: true, replacewith: 'picklistCollection', flag: true},	
  			'PICKLISTSTRINGS' : {ismethod: true, replacewith: 'picklistStrings', flag: true},	 			
  			'PUTINFOLDER' : {ismethod: true, replacewith: 'putInFolder'},
  			'PRESERVE' : {replacewith: ''},
  		    'PRINT' : {replacewith: 'echo(', terminator: ')'},
  		    'PROMPT' : {ismethod : true, replacewith: 'prompt', followedby: '(', terminator: ')', flag: true},
  		    'PROPERTY' : {replacewith: ''},
  		    'PUBLIC' : {replacewith: 'public'},
 		    'PRIVATE' : {replacewith: 'protected'},    		    
  		    'REDIM' : {replacewith: '/* TODO - check this value redefinition ', terminator: '*/'},
 		    'REFRESH' : {ismethod : true, replacewith: 'refresh', followedby: '(', terminator: ')', flag: true},
 		    'REFRESHHIDEFORMULAS' : {ismethod : true, replacewith: 'refresh', followedby: '(', terminator: ')', flag: true},		    
 		    'RELOAD' : {ismethod : true, replacewith: 'reload', followedby: '(', terminator: ')'},
  		    'REM' : {replacewith: '//'},
  		    'REMOVE' : {ismethod : true, replacewith: 'remove', followedby: '(', terminator: ')'},
  			'REMOVEFROMFOLDER' : {ismethod: true, replacewith: 'removeFromFolder'}, 		    
  		    'REMOVEITEM' : {ismethod : true, replacewith: 'deleteField', followedby: '(', terminator: ')'},
  		    'RENDERTORTITEM' : {ismethod: true, replacewith: 'renderToRTItem'},
  		    'REPLACEITEMVALUE' : {ismethod : true, replacewith: 'setField', followedby: '(', terminator: ')'},
 			'RESPONSES' : {isproperty: true, replacewith: 'responses'},
  		    'RESUME' : {replacewith: ''},
  		    'RESUME NEXT' : {replacewith: ''},
  		    'RETURN' : {replacewith: 'return'},
  		    'RIGHT' : {replacewith: '_Right'},
  			'RIGHTMARGIN' : {isproperty: true, replacewith: 'rightMargin'},
  			'RUN' : {ismethod: true, replacewith: 'run'},
  			'RUNONSERVER' : {ismethod: true, replacewith: 'runOnServer'},
  			'SAVE' : {ismethod : true, replacewith: 'save', followedby: '(', terminator: ')', flag: true},
  			'SEARCH' : {ismethod : true, replacewith: 'search'},
 			'SECOND' : {replacewith: '_Second'},
 		    'SELECT' : {},
  		    'SELECT CASE' : {replacewith: 'switch(', terminator: '){'},
  		    'SEND' : {ismethod: true, replacewith: 'send', followedby: '(', terminator: ')'},
  			'SERVER' : {isproperty: true, replacewith: 'server'},
  			'SERVERNAME' : {isproperty: true, replacewith: 'serverName'},
  			'SETENVIRONMENTVALUE' : {ismethod: true, replacewith: 'setEnvironmentValue'},
  		    'SET' : {replacewith: ''},
  		    'SETALTERNATECOLOR' : {ismethod : true, replacewith: 'setAlternateColor'},
  		    'SETBEGIN' : {ismethod : true, replacewith: 'setBegin'}, 		    
  		    'SETCOLOR' : {ismethod : true, replacewith: 'setColor'},
  		    'SETEND' : {ismethod : true, replacewith: 'setEnd'}, 		    
  		    'SETENVIRONMENTVAR' : {ismethod : true, replacewith: 'setEnvironmentVar'},
  			'SETNOW' : {ismethod : true, replacewith: 'setNow'},
  		    'SETPOSITIONATEND' : {ismethod : true, replacewith: 'setPositionAtEnd'}, 	
  		    'SINGLE' : {replacewith: 'null'},
  		    'SPLIT' : {replacewith: '_Split'},
  		    'STAMPALL' : {ismethod : true, replacewith: 'stampAll'},
  		    'STARTDATETIME' : {isproperty : true, replacewith: 'startDateTime'},
  		    'STATIC' : {replacewith: 'static'},
  		    'STEP' : {replacewith: ''},
  		    'STRING' : {replacewith: null},
  		    'STRLEFT' : {replacewith: '_StrLeft'},
  		    'STRRIGHT' : {replacewith: '_StrRight'},
 			'STYLE' : {isproperty: true, replacewith: 'style'},			 
  		    'SUB' : {replacewith: 'function'},
  		    'SURNAME' : {isproperty: true, replacewith: 'surname'},			 		    		    
  		    'TEXT' : {isproperty: true, replacewith: 'text'},
  		    'THEN' : {replacewith: '){'}, 		    
  			'TIMEDIFFERENCE' : {ismethod : true, replacewith: 'timeDifference'}, 		    
  		    'TIMEONLY' : {isproperty: true, replacewith: 'timeOnly'},
  		    'TIMEZONE' : {isproperty: true, replacewith: 'timeZone'}, 	
  		    'TRIM' : {replacewith: '_Trim'},
  		    'TYPE' : {replacewith: '', terminator: '= {'},
  		    'TO' : {replacewith: ';'},
  		    'TRUE' : {replacewith: 'true'},
  		    'UCASE' : {replacewith: '_UpperCase'},
  		    'UBOUND' : {replacewith: '_UBound'},
  			'UNDERLINE' : {isproperty: true, replacewith: 'underline'},
  			'UNIVERSALID' : {isproperty: true, replacewith: 'universalID'},
  			'UNPROCESSEDDOCUMENTS' : {isproperty: true, replacewith: 'unprocessedDocuments'},
  		    'UNTIL' : {replacewith: 'while(!', terminator: ')'},
  		    'USE' : {replacewith: 'require(', terminator: ')'},
  		    'USELSX' : {replacewith: '//Uselsx', flag: true },
  			'USERNAME' : {isproperty: true, replacewith: 'userName'},
  			'USERNAMELIST' : {isproperty: true, replacewith: 'userNameList'},
  			'USERNAMEOBJECT' : {isproperty: true, replacewith: 'userNameObject'},
  			'USERGROUPNAMELIST' : {isproperty: true, replacewith: 'userGroupNameList'},
  			'USTRING' : {replacewith: '_UString'},
  			'VALUES' : {isproperty: true, replacewith: 'values'},
  		    'VARIANT' : {replacewith: 'null'},
  		    'VIEWS' : {isproperty: true, replacewith: 'Views'},
  		    'VIEWREFRESH' : {ismethod : true, replacewith: 'viewRefresh', followedby: '(', terminator: ')'},
  		    'WEND' : {replacewith: '}'},
  		    'WHILE' : {replacewith: 'while', followedby: '(', terminator: '){'},
  		    'WITH' : {replacewith: '//with', flag: true},
  		    'YEAR' : {replacewith: '_Year'},
  		    '%END' : {},
  		    '%INCLUDE' : {replacewith: '/* TODO - rework this code as it contains an INCLUDE statement ', terminator: '*/', flag: true},
  		    '%REM' : {replacewith: '/*' },
  		    '%END REM' : {replacewith: '*/'},
  		    '%ENDREM' : {replacewith: '*/'}
  	};
  	
  	this.otherconvtable = {
  	
  	};
  	
  	this.constanttable = [
     ];				

  	

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: input 
  	 * Initialize the Lexer's buffer. This resets the lexer's internal state and subsequent tokens will be returned 
  	 * starting with the beginning of the new buffer.
  	 * Inputs: buf - string - input string containing source code
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
  	this.input = function (buf) {
  		this.pos = 0;
  		this.buf = (buf ? buf : "");
  		this.buflen = (buf ? buf.length : 0);
  		this.depth = 0;
  	}

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: tokenize
  	 * Converts the input buffer to a token stream
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
  	this.tokenize = function() {
  		var tokenList = [];	

  		var tokenObj = null;
  		do{
  			tokenObj = this._token();
  			if(tokenObj){
  				tokenList.push(tokenObj);
  			}
  		}while (tokenObj); 	
  		
  		return tokenList;
  	}

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: _token
  	 * Get the next token from the current buffer. A token is an object with the following properties:
  	 *    - name: name of the pattern that this token matched (taken from rules).
  	 *    - value: actual string value of the token.
  	 *    - pos: offset in the current buffer where the token starts.
  	 * 	If there are no more tokens in the buffer, returns null. In case of an error throws Error.
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
  	this._token = function () {
  		var curpos = this.pos;
  		
  		this._skipnontokens();
  		if (this.pos >= this.buflen) {
  			return null;
  		}

  		// The char at this.pos is part of a real token. Figure out which.
  		var c = this.buf.charAt(this.pos);

  		// Look it up in the table of operators
  		var op = this.optable[c];
  		if (typeof op !== 'undefined') {
  			if(op.followedby){
  				for (var childop in op.followedby) {
     						if (op.followedby.hasOwnProperty(childop)) {
     							if(this.buf.charAt(this.pos + 1) === childop){
      								c += childop;
      								break;
      							}
      						}
  					}					
  			}
  			
  			if(c === "("){
  				this.depth ++;
  			}else if(c === ")"){
  				if(this.depth > 0){
  					this.depth --;
  				}
  			}
  				
  			this.pos = this.pos + c.length;
  				
  			return {
  				name : "OPERATOR",
  				value : c,
  				pos : curpos,
  				depth : this.depth
  			};
  		} else {
  				// Not an operator - so it's the beginning of another token.
  				if(this._iscomment(c)){
  					return this._process_comment(c);
  				}else if(this._isnewline(c)){
  					return this._process_newline();
  				}else if(this._isfunction(c)){
  					return this._process_function();
  				}else if(this._isalpha(c)) {
  					return this._process_identifier();
  				} else if (this._isdigit(c)) {
  					return this._process_number();
  				} else if (c === '|') {
  					return this._process_quote('|');
  				} else if (c === '"') {
  					return this._process_quote('"');
  				} else if (c === '{') {
  					return this._process_quote('}');					
  				} else {
  					if(console){
  						console.log("Error in LexerLS>tokenize>_token: unidentified token at buffer position [" + this.pos + "] character [" + c + "] character code [" + c.charCodeAt(0) + "].")
  					}
  				}
  		}
  	}

  	
  	this._isnewline = function (c) {
  		return c === '\r' || c === '\n';
  	}

  	this._isdigit = function (c) {
  		return c >= '0' && c <= '9';
  	}

  	this._isalpha = function (c) {
  		return (c >= 'a' && c <= 'z') ||
  		(c >= 'A' && c <= 'Z') ||
  		c === '_' || c === '$' || c === '%';
  	}

  	this._isalphanum = function (c) {
  		return (c >= 'a' && c <= 'z') ||
  		(c >= 'A' && c <= 'Z') ||
  		(c >= '0' && c <= '9') ||
  		c === '_' || c === '$' || c === '%' || c === '&';
  	}	
  	
  	this._isconstant = function () {
  		var result = false;
  		if(this.buf.charAt(this.pos) != "["){ return result;}
  		
  		var endpos = this.pos;	
  		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
  			endpos++;
  		}
  		if(endpos === this.pos){return result;}	
  		
  		var searchconst = this.buf.substring(this.pos, endpos + 1);
  		if(this.constanttable.indexOf(searchconst) > -1){
  			result = true;
  		}

  		return result;		
  	}
  	
  	this._iscomment = function (c) {
  		var result = false;
  		
  		if(c === "'"){
  			result = true;
  		}else if(c === "R" && this.buflen > this.pos + 3 && this.buf.substring(this.pos, this.pos + 3) === "REM"){
  			result = true;
  		}else if(c === "%" && this.buflen > this.pos + 4 && this.buf.substring(this.pos, this.pos + 4) === "%REM" ){
  			result = true;
  		}
  		
  		return result;
  	}
  	
  	this._isfunction = function () {
  		var result = false;
  		
  		var firstchar = this.buf.charAt(this.pos).toUpperCase();
  		if(firstchar === "F" && this.buflen < this.pos + 9 ){ return result;}
  		if(firstchar === "S" && this.buflen < this.pos + 4 ){ return result;}

 		var tempstring = this.buf.substring(this.pos, this.pos + (firstchar === "F" ? 9 : 4));
  		if(tempstring.toUpperCase() === "FUNCTION " || tempstring.toUpperCase() === "SUB "){
  			result = true;
  		}
  		
  		return result;		
  	}	
  	
  	this._process_constant = function () {
  		var tok = null;
  		if(this.buf.charAt(this.pos) != "["){ return tok;}
  		
  		var endpos = this.pos;	
  		while (endpos < this.buflen && this.buf.charAt(endpos) != "]") {
  			endpos++;
  		}
  		if(endpos === this.pos){return tok;}
  		
  		var searchconst = this.buf.substring(this.pos, endpos + 1);
  		if(this.constanttable.indexOf(searchconst) > -1){
  			tok = {
  				name : 'CONSTANT',
  				value : '"' + searchconst + '"',
  				pos : this.pos
  			};
  			this.pos = endpos + 1;
  		}

  		return tok;		
  	}	

  	this._process_number = function () {
  		var endpos = this.pos + 1;
  		while (endpos < this.buflen &&
  			this._isdigit(this.buf.charAt(endpos))) {
  			endpos++;
  		}

  		var tok = {
  			name : 'NUMBER',
  			value : this.buf.substring(this.pos, endpos),
  			pos : this.pos
  		};
  		this.pos = endpos;
  		return tok;
  	}
  	
 	this._process_newline = function () {
  		var tok = {
  			name : 'NEWLINE',
  			value : this.buf.charAt(this.pos),
  			pos : this.pos
  		};
  		this.pos = this.pos + 1;
  		return tok;
  	}	

 	this._process_comment = function (c) {
  		var endpos = this.pos;
  		var tok = {
  				name: '',
  				value: '',
  				pos: -1
  		};
  		
  		if(c === "'"){
  	 		endpos = this.pos + 1;
  	 		// Skip until the end of the line
  	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
  	 			endpos++;
  	 		}	 		
  			tok = {
  		 			name : 'COMMENT',
  		 			value : this.buf.substring(this.pos + 1, endpos),
  		 			pos : this.pos
  		 	};
  		 	this.pos = endpos; 	 		
  		}else if(c === "R" && this.buflen > this.pos + 3 && this.buf.substring(this.pos, this.pos + 3) === "REM"){
  	 		endpos = this.pos + 4;
  	 		// Skip until the end of the line
  	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
  	 			endpos++;
  	 		}
 			tok = {
  		 			name : 'COMMENT',
  		 			value : this.buf.substring(this.pos + 4, endpos),
  		 			pos : this.pos
  		 	};
  		 	this.pos = endpos; 	 		 	 		
  		}else if(c === "%" && this.buflen > this.pos + 4 && this.buf.substring(this.pos, this.pos + 4) === "%REM" ){
  	 		endpos = this.pos + 5;
  	 		// Skip until the end remark identifier found. search for the first of two forms
  	 		var endpos1 = this.buf.indexOf("%END REM", endpos);
  	 		var endpos2 = this.buf.indexOf("%ENDREM", endpos);
  	 		var offset = 0;
  	 		if(endpos1 > -1 || endpos2 > -1){
  	 			if(endpos1 == -1){
  	 				endpos = endpos2;
  	 				offset = 7;
  	 			}else if(endpos2 == -1){
  	 				endpos = endpos1;
  	 				offset = 8;
  	 			}else if(endpos1 < endpos2){
  	 				endpos = endpos1;
  	 				offset = 8;
  	 			}else{
  	 				endpos = endpos2;
  	 				offset = 7;
  	 			}
 	 			
  	 		}else{
  	 	 		// Just skip until the end of the line since no end of remark statement found
  	 	 		while (endpos < this.buflen && !this._isnewline(this.buf.charAt(endpos))) {
  	 	 			endpos++;
  	 	 		}	 			
  	 		}

 			tok = {
  		 			name : 'COMMENT',
  		 			value : this.buf.substring(this.pos + 5, endpos),
  		 			pos : this.pos
  		 	};
  		 	this.pos = endpos + offset; 	 		
  	 		
  		}		
  		
  		return tok;
  	}
 	
  	this._process_identifier = function () {
  		var endpos = this.pos + 1;
  		while (endpos < this.buflen &&
  			this._isalphanum(this.buf.charAt(endpos))) {
  			endpos++;
  		}
  		
  		var tempval = this.buf.substring(this.pos, endpos);
  		var kw = this.kwtable[tempval.toUpperCase()];
  		var tempname = "IDENTIFIER";
  		if(typeof kw !== 'undefined'){
  			if((kw.ismethod || kw.isproperty) && (this.pos > 0) && (this.buf.charAt(this.pos -1) === ".")){
  				tempname = "KEYWORD";
  			}else if(!kw.ismethod && !kw.isproperty && (this.pos > 0) && (this.buf.charAt(this.pos -1) === ".")){
 				tempname = "IDENTIFIER";
  			}else{
  				tempname = "KEYWORD"; 	
  			}
  		} 

  		var tok = {
  			name : tempname,
  			value : tempval,
  			pos : this.pos
  		};
  		this.pos = endpos;
  		return tok;
  	}
  	
  	this._process_function = function () {
  
  		var firstchar = this.buf.charAt(this.pos).toUpperCase();
  		var charcount = (firstchar === "F" ? 9 : 4 );
  		
  		var startpos = this.pos + charcount;
  		var endpos = this.pos + charcount;
  		while (endpos < this.buflen &&
  			this._isalphanum(this.buf.charAt(endpos))) {
  			endpos++;
  		}

  		var tok = {
  			name : 'FUNCTION',
  			value : this.buf.substring(startpos, endpos),
  			pos : startpos
  		};
  		this.pos = endpos;
  		return tok;
  	}	

  	this._process_quote = function (quotechar) {
  		var tok = false;
  		var curpos = this.pos;
  		var end_index = -1;
  		var keeplooking = true;

  		do {
  			//Find the next quote.
  			curpos = this.buf.indexOf(quotechar, curpos + 1);
  			if (curpos === -1) {
  				keeplooking = false;
  				throw Error('Unterminated quote at ' + this.pos);
  			} else {
  				//check for double quote indicating a literal
  				if((curpos + 1) < this.buflen && this.buf.charAt(curpos + 1) === quotechar){
  					curpos ++;
  				}else{
  					var tempstring = "";
  					if(this.pos + 1 <= curpos){
  						tempstring = this.buf.substring(this.pos+1, curpos);
  					}
  	
  					//--replace single slash with double slash
  			 		tempstring = tempstring.replace(/\\/g, "\\\\");
  			 											
  					keeplooking = false;
  					tok = {
  						name : 'QUOTEDSTRING',
  						value : tempstring,
  						pos : this.pos
  					};								
  					this.pos = curpos + 1; //increment buffer position					
  				}
  			}		
  		} while(keeplooking);	
  		
  		return tok;
  	}

  	this._skipnontokens = function () {
  		while (this.pos < this.buflen) {
  			var c = this.buf.charAt(this.pos);
  			if (c == ' ' || c == '\t') {
  				this.pos++;
  			} else {
  				break;
  			}
  		}
  	}

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: tokenListToTree
  	 * Converts a token list to a tree such that child operations are nested beneath the parent operation
  	 * Enables easier parsing of content within the same level (eg. function parameters, vs. child functions).
  	 * Inputs: tokenList - array - array of token objects
  	 * Returns: rootToken - token object at top of tree
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
  	this.tokenListToTree = function(tokenList) {
  		var depth = 0;
  		
  		var rootToken = {
  			name: "ROOT",
  			value: "",
  			children: [],
  			parent : null
  		};
  		var parentToken = rootToken;
  	
  		for(var i=0; i<tokenList.length; i++){
  			var token = tokenList[i];
  			var newtoken = {
  				name: token.name, 
  				value: token.value,
  				children : null,
  				parent : null
  			}
  			
  			if(newtoken.name == "OPERATOR"){			
  				 if(newtoken.value == ")"){
  					var temptoken = (parentToken.parent ? parentToken.parent : parentToken);		
  					depth --;									 	
  					var parentToken = temptoken; //-- pop up one level so that this parenthesis is at the same level as its match		
  				}
  			}

  			newtoken.parent = parentToken;
  			if(parentToken.children === null){
  				parentToken.children = new Array();
  			}					
  			parentToken.children.push(newtoken);

  			//console.log(("     ").repeat(depth) + " -> " + newtoken.value);

  			//-- reset parent token for functions and brackets  
  			if(newtoken.name == "FUNCTION" || (newtoken.name == "OPERATOR" && newtoken.value == "(")){
  				depth ++;
  				parentToken = newtoken;
  			}else if(newtoken.name == "OPERATOR" && newtoken.value == ")" && parentToken.name == "FUNCTION"){
  				depth --;
  				parentToken = (parentToken.parent ? parentToken.parent : parentToken);
  			}							
  		}	
  		
  		return rootToken;
  	}//--end tokenListToTree

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: _walkTokenTree
  	 * Walks the token tree from top to bottom, left to right, returning either the original content, or 
  	 * if a convertFunction parameter is passed, the converted code.  A recursive function.
  	 * Inputs: tokenNode - current token node to be traversed
  	 *              convertFunction - function (optional) - function to call to perform processing on the token node
  	 *                                              and possibly its children.  Function must accept the token node as its input
  	 *              depth - integer (optional) - counter used to guage how many recursive calls have been made
  	 *              lastNode - last token node traversed (optional)
  	 * Returns: string - original code or converted code in text format
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
  	this._walkTokenTree = function (tokenNode, convertFunction, depth, lastNode){	
  		var tempdepth = (typeof depth == 'undefined' ? 0 : depth);
  		var tempresult = "";
  		var returnresult = "";

  		if(typeof tokenNode != 'undefined' && tokenNode != null){
  			if(typeof convertFunction === 'function'){
  				convertFunction.call(this, tokenNode, lastNode);
  			} 

  			//console.log(("     ").repeat(depth) + " -> " + tokenNode.value);
  			lastNode = tokenNode;
  			if(tokenNode.children != null){
  				tempdepth ++;				
  				for(var c=0; c<tokenNode.children.length; c++){
  					tempresult  += this._walkTokenTree(tokenNode.children[c], convertFunction, tempdepth, lastNode);
  					lastNode = tokenNode.children[c];
  				} 
  			}
  			returnresult = tokenNode.value + tempresult;			
  		}
  		return returnresult;
  	}
  	
  
  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: _adjustTokenList
  	 * Walks the token list from left to right, and makes adjustments that are needed before the tree
  	 * can be generated.  eg. converts field references to function calls, inserts missing function brackets
  	 * Inputs: tokenList - array of tokens
  	 * Returns: token object array - array of token objects
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */	
  	this._adjustTokenList = function(tokenList){
  		var lparencount = 0;
  		var rparencount = 0;
  		var hascomment = false;
  		var functiondef = false;
  		var functionname = "";
  		var usestatement = false;
  		var ifcondition = false;
  		var conststatement = false;
  		var ifstatement = false;
  		var forstatement = false;
  		var forallstatement = false;
  		var forvar = "";
  		var forallvals = [];
  		var inwith = false;
  		var withname = [];
  		var casestatement = false;
  		var casebreak = [];
  		var assignmentfound = false;
  		var assignlparencount = 0;
  		var assignrparencount = 0;
  		var termneeded = "";
  		var globalvars = [];
  		var globalvarsorig = [];
  		var functionvars = [];
  		var classmembervariables = [];
  		var functionvarsorig = [];
  		var functionbody = false;
  		var classblock = false;
  		var allfunctions = [];
  		var vartypearr = [];
  		//-- main pass through the list
  		for(var i=0; i<tokenList.length; i++){
  			var token = tokenList[i];
  			
  			//-- check if we are in a forall declaration and need to keep track of array source tokens
  			if(forallstatement  )
  			{
  				/*forallvals.push({
  					name: token.name,
  					value: token.value,
  					pos: -1,
  					depth: -1
  				});*/
  				
  				if (forvar === ""){
  					forvar = token.value;
  					tokenList.splice(i, 1);
  					i--;
  				}else if ( token.value.toUpperCase() == "IN"){
  					tokenList.splice(i, 1);
  					i--;
  				}else if ( token.name == "NEWLINE"){
  				
 	 				
 	 				tokenList.splice(i, 0, {
 	 		 			name: "KEYWORD",
 	 		 			value: " as ",
 	 		 			pos: -1,
 	 		 			depth: -1
 	 		 		}); 
 	 				i++;
 	 				tokenList.splice(i, 0, {
 	 		 			name: "KEYWORD",
 	 		 			value: "$key => ",
 	 		 			pos: -1,
 	 		 			depth: -1
 	 		 		}); 
 	 				i++;
 	 				tokenList.splice(i, 0, {
 	 		 			name: "IDENTIFIER",
 	 		 			value:  forvar,
 	 		 			pos: -1,
 	 		 			depth: -1
 	 		 		}); 
 	 				i++;
  				}
  			}
  			
  			//--1 COMMENT
  			if(token.name == "COMMENT"){
  				hascomment = true;
  			//--2 KEYWORD
  			}else if(token.name == "KEYWORD"){		
  				//-- check if token is ListTag call if so we need to adjust it
  				if(token.value.toUpperCase() == "LISTTAG"){
  					//-- remove the parenthesis and variable name following ListTag 
  					tokenList.splice(i+1, 3);				
  				//-- check if token is equality
  				}else if(token.value.toUpperCase() == "IS"){
  					token.name = "EQUALITY";
  					token.value = "===";
  				//-- check if we are in a for statement and have a variable that needs to be applied
  				}else if(token.value.toUpperCase() == "TO" && forstatement && forvar !== ""){
 	 		 		//-- insert operator
 	 		 		tokenList.splice(i+1, 0, {
 	 		 			name: "OPERATOR",
 	 		 			value: "<=",
 	 		 			pos: -1,
 	 		 			depth: -1
 	 		 		}); 					
  					//-- insert variable
  	 		 		tokenList.splice(i+1, 0, {
 	 		 			name: "IDENTIFIER",
 	 		 			value: forvar,
 	 		 			pos: -1,
 	 		 			depth: -1
 	 		 		});
 	 		 		termneeded = "; $" + forvar + "++" + termneeded;
 	 		 		forvar = "";	
 	 		 	//-- check if this is a true/false/null statement
  				}else if(token.value.toUpperCase() == "TRUE" || token.value.toUpperCase() == "FALSE" || token.value.toUpperCase() == "NULL"){
  					//-- if the prior token is equality operator then make more specific
  					if(i>0 && tokenList[i-1].name === "EQUALITY"){
  						tokenList[i-1].value = "===";					
  					//-- check if the prior token is not equals, if so make it more specific						
  					}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
  						tokenList[i-1].value = "!==";
  					}
   				//-- check if this is a variable declaration
  				}else if(token.value.toUpperCase() == "AS"){
  					//-- if this is a list declaration then just convert to an array
  					if((i-1 > -1) && (i+1 < tokenList.length) && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "LIST"){
  						tokenList[i+1].value = "[]";
  					}else if(i+1 < tokenList.length && tokenList[i+1].name == "KEYWORD" && tokenList[i+1].value.toUpperCase() !== "NEW"){
  						//-- since this is a declaration without a new keyword we can clear it out
  						vartypearr[tokenList[i-1].value.toUpperCase()]=   tokenList[i+1].value.toUpperCase();
  						 tokenList[i-1].vartype = tokenList[i+1].value;
  						tokenList[i+1].value = "null";
  					}
  				//-- check if two keywords follow each other and may combine into a single keyword
  				}else if((i+1 < tokenList.length) && (tokenList[i+1].name == "KEYWORD" || tokenList[i+1].name == "FUNCTION")){
  					var testkw = tokenList[i].value + " " + tokenList[i+1].value;
  					testkw = testkw.trim();
  					var kw = this.kwtable[testkw.toUpperCase()];
  					if(typeof kw !== 'undefined'){
  						tokenList[i].value = testkw;
  						tokenList.splice(i+1, 1);
  					}
  				}else if (token.value.toUpperCase() == "PUBLIC" && i-1 > -1 && tokenList[i-1].value.toUpperCase() == "OPTION")
  				{
  					token.value = "";
  				}else if ( token.value.toUpperCase() == "OPEN")
  				{
  					//look for for and convert it into a , for the fopen function
  					if ( i + 2 < tokenList.length && tokenList[i+2].value.toUpperCase() == "FOR"){
  						tokenList[i+2].name = "OPERATOR"
  						tokenList[i+2].value = ","
  							
  						if ( i + 3 < tokenList.length){
  							if ( tokenList[i+3].value.toUpperCase() == "INPUT" ){
  								tokenList[i+3].value = "r";
  								tokenList[i+3].name = "QUOTEDSTRING"
  							}else if ( tokenList[i+3].value.toUpperCase() == "APPEND" ){
  								tokenList[i+3].name = "QUOTEDSTRING"
  							}else{
  								tokenList[i+3].name = "QUOTEDSTRING"
  							}
  						}
  					}
  					
  					//look for variable name of file
  					if ( i + 4 < tokenList.length && tokenList[i +4].value.toUpperCase() == "AS")
  						tokenList[i+4].value = "";
  					
  					if ( i + 5 < tokenList.length && tokenList[i+5].name == "IDENTIFIER")
  					{
  						tokenList.splice(i, 0, {
  		 		 			name: "OPERATOR",
  		 		 			value: "/*TODO - check path of file operations*/\n",
  		 		 			pos: -1,
  		 		 			depth: -1
  						});
  						i++;
  						tokenList.splice(i, 0, {
  		 		 			name: "IDENTIFIER",
  		 		 			value: tokenList[i+5].value,
  		 		 			pos: -1,
  		 		 			depth: -1
  						});
 	 		 		 	i++;
 	 		 		 	//tokenList[i+5].value = "";
 	 		 		 	tokenList.splice(i, 0, {
 	 		 		 			name: "OPERATOR",
 	 		 		 			value: "=",
 	 		 		 			pos: -1,
 	 		 		 			depth: -1
 	 		 		 	});
 	 		 		 	i++;
 	 		 		 	tokenList.splice(i+5, 1);
  					}	
  				}else if ( token.value.toUpperCase() == "LINE"){
  					if ( i + 1 < tokenList.length && tokenList[i+1].value.toUpperCase() == "INPUT"){
  						//remove the "INPUT
  						tokenList.splice(i+1, 1);
  					}
  					if ( i + 1 < tokenList.length && tokenList[i+1].value.toUpperCase() == "#"){
  						//remove the "#
  						tokenList.splice(i+1, 1);
  					}
  					if ( i + 2 < tokenList.length && tokenList[i+2].value.toUpperCase() == ","){
  						//remove the "comma
  						tokenList.splice(i+2, 1);
  					}
  					tokenList.splice(i, 0, {
 		 		 			name: "IDENTIFIER",
 		 		 			value: tokenList[i+2].value,
 		 		 			pos: -1,
 		 		 			depth: -1
 						});
  		 		 	i++;
  		 		 	//tokenList[i+5].value = "";
  		 		 	tokenList.splice(i, 0, {
  		 		 			name: "OPERATOR",
  		 		 			value: "=",
  		 		 			pos: -1,
  		 		 			depth: -1
  		 		 	});
  		 		 	i++;
  		 		 	tokenList.splice(i+2, 1);
  					
  				}
  				
  				
 				//-- check if this is an if or elseif statement
 				if(token.value.toUpperCase() == "IF"){
 					ifstatement = true;
 					ifcondition = true;
 					assignlparencount = 0;
 	 				assignrparencount = 0;
 	 				assignmentfound = false;
 				}else if ( token.value.toUpperCase() == "CONST"){
 	 					conststatement = true
 				}else if ( token.value.toUpperCase() == "CLASS"){
  					classblock = true;
  					if ( i + 1 < tokenList.length && tokenList[i+1].name == "IDENTIFIER"){
  						tokenList[i+1].name = "OPERATOR";
  						
  						if (i+2 < tokenList.length && tokenList[i+2].value.toUpperCase() == "AS"){
  							tokenList[i+2].value = "extends"
  							tokenList[i+3].name = "OPERATOR";
  							tokenList.splice(i+4, 0, {
  		 		 		 			name: "OPERATOR",
  		 		 		 			value: "{",
  		 		 		 			pos: -1,
  		 		 		 			depth: -1
  		 		 		 	});
  						}else{
 	 						tokenList.splice(i+2, 0, {
 	 		 		 			name: "OPERATOR",
 	 		 		 			value: "{",
 	 		 		 			pos: -1,
 	 		 		 			depth: -1
 	 		 		 		});
  						}
  					}
  					
 				}else if(token.value.toUpperCase() == "THEN"){
 					ifcondition = false;
 					assignlparencount = 0;
 	 				assignrparencount = 0;
 	 				assignmentfound = false;
 	 			}else if ( token.value.toUpperCase() == "CURRENTAGENT"){
 					if ( i-1 > -1 && tokenList[i-1].value == "." && i-2 > -1 && tokenList[i-2].name == "IDENTIFIER"){
 						token.value = "$this";
 						tokenList.splice(i-2, 2);
 					}
 				}else if(token.value.toUpperCase() == "ELSE"){
 					ifcondition = false;
 					assignlparencount = 0;
 	 				assignrparencount = 0;
 	 				assignmentfound = false;
 				}else if ( token.value.toUpperCase() == "END CLASS"){
 					classblock = false;
 					classmembervariables = [];
 				}else if(token.value.toUpperCase() == "ELSEIF"){
 					ifstatement = false;
 					ifcondition = true;
 					assignlparencount = 0;
 	 				assignrparencount = 0;
 	 				assignmentfound = false;					
 				}else if(token.value.toUpperCase() == "END IF"){
 					ifstatement = false;
 					ifcondition = false;
 					assignlparencount = 0;
 	 				assignrparencount = 0;
 	 				assignmentfound = false;	
 				//-- check if this is the start of a for loop
 				}else if(token.value.toUpperCase() == "FOR"){
 					forstatement = true;
 					forvar = "";
 				//-- check if this is the start of a forall loop
 				}else if(token.value.toUpperCase() == "FORALL"){
 					forallstatement = true;
 					forvar = "";
 					forallvals = [];
 				//-- check if this is the start of a with statement
 				}else if(token.value.toUpperCase() == "WITH"){
 					inwith = true;
 					withname.push("");
 				//-- check if this is the end of a with statement
 				}else if(token.value.toUpperCase() == "END WITH"){
 					inwith = false;
 					withname.pop();
 				//-- keep track of select case statements
 				}else if(token.value.toUpperCase() == "SELECT CASE"){
 					casebreak.push(false);
 				//-- keep track of end select statements
 				}else if(token.value.toUpperCase() == "END SELECT"){
 					if(casebreak && casebreak.length > 0){
 						if(casebreak[casebreak.length - 1]){
 							//-- insert a break statement
 	 	 					tokenList.splice(i, 0, {
 	 	 						name: "KEYWORD",
 	 	 						value: "break",
 	 	 						pos: -1,
 	 	 						depth : -1
 	 	 					});
 	 	 					i++;

 							//-- insert a newline
 	 	 					tokenList.splice(i, 0, {
 	 	 						name: "NEWLINE",
 	 	 						value: "",
 	 	 						pos: -1,
 	 	 						depth : -1
 	 	 					});
 	 	 					i++;													
 						}						
 						casebreak.pop();
 					}
 				//-- check if a case statement needs closing
 				}else if(token.value.toUpperCase() == "CASE" || token.value.toUpperCase() == "CASE ELSE"){
 					casestatement = true;
 					if(casebreak && casebreak.length > 0){
 						if(casebreak[casebreak.length - 1]){
 							//-- insert a break statement
 	 	 					tokenList.splice(i, 0, {
 	 	 						name: "KEYWORD",
 	 	 						value: "break",
 	 	 						pos: -1,
 	 	 						depth : -1
 	 	 					});
 	 	 					i++;

 							//-- insert a newline
 	 	 					tokenList.splice(i, 0, {
 	 	 						name: "NEWLINE",
 	 	 						value: "",
 	 	 						pos: -1,
 	 	 						depth : -1
 	 	 					});
 	 	 					i++;													
 						}
 						casebreak[casebreak.length - 1] = true;
 					}
 				//-- check if a function is exiting early and add a return value
  				}else if(token.value.toUpperCase() == "EXIT FUNCTION" || token.value.toUpperCase() == "EXIT SUB" || token.value.toUpperCase() == "END"){	 				
 	 				//--return the function value					
  					tokenList.splice(i+1, 0, {
  						name: "IDENTIFIER",
  						value: "return_" + functionname.toLowerCase(),
  						pos: -1,
  						depth : -1
  					});	 					
 				//-- check if a function is exiting and insert a return value in case it isn't already present
 				}else if(token.value.toUpperCase() == "END FUNCTION" || token.value.toUpperCase() == "END SUB"){	
 					functionbody = false;
 					//--return the function value					
 					tokenList.splice(i, 0, {
 						name: "KEYWORD",
 						value: "return",
 						pos: -1,
 						depth : -1
 					});	
  					i++;					
 					tokenList.splice(i, 0, {
 						name: "IDENTIFIER",
 						value: "return_" + functionname.toLowerCase(),
 						pos: -1,
 						depth : -1
 					});	
  					i++;					
 					//-- insert a newline
  					tokenList.splice(i, 0, {
  						name: "NEWLINE",
  						value: "",
  						pos: -1,
  						depth : -1
  					});
  					i++;
  					
  					functionvars = [];
  					functionvars.length = 0;
 					functionvarsorig = [];
  					functionvarsorig.length = 0;					
  					functionname = "";
  				//-- check if this is a use statement
 				}else if(token.value.toUpperCase() == "USE"){
 					usestatement = true;
 					if ( i +1 < tokenList.length && this.usepath != ""){
 						tokenList[i+1].value = this.usepath  + tokenList[i+1].value;
 					}
 				//-- check if this is a getitemvalue call
 				}else if(token.value.toUpperCase() == "GETITEMVALUE"){
 	 					var lpcount = 0;
 	 					var rpcount = 0;
 	 					var convert = false;
 	 					var c=i+1;
 	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
 	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
 	 							lpcount ++;
 	 							if(convert){
 	 								tokenList[c].value = "[";
 	 							}
 	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
 	 							rpcount ++;
 	 							if(rpcount == lpcount){
 	 								if(convert){
 	 									tokenList[c].value = "]";
 	 									break;
 	 								}else{
 	 									if(c+1<tokenList.length && tokenList[c+1].name == "OPERATOR" && tokenList[c+1].value == "("){
 	 										convert = true;
 	 										lpcount = 0;
 	 										rpcount = 0;
 	 									}else{
 	 										break;
 	 									}
 	 								}
 	 							}
 	 						}
 	 						c++;
 	 					}
 				}				
  			
 				var kw = this.kwtable[token.value.toUpperCase()];
 				if(typeof kw !== 'undefined'){
 					var ignoreterm = false;
 					if(kw.followedby && kw.followedby !== ""){
 						if(i+1 < tokenList.length){
 							//-- check if this token is followed by a particular token
 							if(tokenList[i+1].name != "OPERATOR" || tokenList[i+1].value !== kw.followedby){
 								//-- insert token after this one since token requires something following it
 								tokenList.splice(i+1, 0, {
 									name: "OPERATOR",
 									value: kw.followedby,
 									pos: -1,
 									depth : -1
 								});
 							}else{
 								//-- ignore the terminator 
 								ignoreterm = true;
 							}
 						}
 					}
 					//-- check if this token has a terminator required					
 					if(kw.terminator && kw.terminator !== "" && !ignoreterm){
 						termneeded = termneeded + kw.terminator;
 					}	
 				}	

 				//--check if this reference needs to be converted from () array notation to [] array notation
 				if((token.value.toUpperCase() == "COLUMNVALUES")){
 	 				if(i+1 < tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
 	 					tokenList[i+1].value = "[";
 	 					var lpcount = 1;
 	 					var rpcount = 0;
 	 					var c=i+1;
 	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
 	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
 	 							lpcount ++;
 	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
 	 							rpcount ++;
 	 							if(rpcount == lpcount){
 	 								tokenList[c].value = "]";
 	 							}
 	 							break;
 	 						}
 	 						c++;
 	 					}
 	 				}
 	 			}				
 			//--3 IDENTIFIER	
  			}else if(token.name == "IDENTIFIER"){
  				
  				
  				//-- check if this identifier is being declared if so lets track it for later use
  				if((i>0 && tokenList[i-1].name == "KEYWORD" && tokenList[i-1].value.toUpperCase() == "DIM") || functiondef)
  				{
  					if ( !functiondef ){
 	 					//first check if this is a multi line assignment as in dim j as integer, p as integer
 	 					var ind = i;
 	 					while ( ind < tokenList.length && tokenList[ind].name != "NEWLINE"){
 	 						if ( tokenList[ind].name == "OPERATOR" && tokenList[ind].value == ","){
 	 							tokenList[ind].name = "NEWLINE";
 	 							tokenList[ind].value = "";
 	 						}
 	 						ind++;
 	 					}
  					}
  					if(functionname === ""){
  						globalvars.push(token.value.toUpperCase());
  						globalvarsorig.push(token.value);
  					}else{
  						functionvars.push(token.value.toUpperCase());
  						functionvarsorig.push(token.value);
  					}
  				}else if ( conststatement ){
  					token.name = "QUOTEDSTRING";
  					//token.value = "$" + token.value;
  				}else{
  					//-see if the identifier is a member variable of a class
  					if ( classblock){
  						var findx = classmembervariables.indexOf(token.value.toUpperCase());
  						if(findx > -1){
  							//check if this is preceeded with a me.
  							if ( i-2 > 0 && tokenList[i-2].value.toUpperCase() != "ME")
  								//found a class member variable reference
 	 							token.value = "this->" + token.value;
  						}
  					}
  					
  					var findx = functionvars.indexOf(token.value.toUpperCase());
  					if(findx > -1){
  						//-- change case of identifer to match declaration
  						token.value = functionvarsorig[findx];
  					}else{
  						findx = globalvars.indexOf(token.value.toUpperCase());
  	 					if(findx > -1){
  	 						//-- change case of identifer to match global declaration
  	 						token.value = globalvarsorig[findx];
  	 					}	
  					}					
  				}
  				
  				//-- check if we are in a for statement and need to store a variable
  				if(forstatement && forvar === ""){
  					forvar = token.value;
  				}
  				//-- check if we are in a forall statement and need to store a variable
  				if(forallstatement && forvar === ""){
  					forvar = token.value;
  				}
  				
  				//-- check if we are in a with statement and no identifier stored on the stack yet
  				if(inwith && withname.length > 0 && withname[withname.length - 1] === ""){
  					withname[withname.length - 1] = token.value;
  				}
  				
  				//-- check if this identifier matches a function name if so we are going to rename it so that it does not conflict
  				if(token.value.toLowerCase() == functionname.toLowerCase()){
  					//-- make sure this is not a recursive call to the function itself
  					if(i+1<tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
  						//-- update function name to ensure it matches the case of the function definition
  						token.value = functionname;
  					}else{
  						//-- change the name of the function return variable
  						token.value = "return_" + functionname.toLowerCase();
  					}
  				}else if ( i-2>0 && tokenList[i-2].value.toUpperCase() == "ME" )
  				{
  				    //change the me. in a class reference to $this->
  					tokenList[i-2].name = "IDENTIFIER";
  					tokenList[i-2].value = "this"
  					tokenList[i].name = "OERATOR"
  					//this is a class member variable..so leave it alone
  				//-- check if this identifier is preceded by an identifier/keyword and a period which may indicate a field reference
  				}else if(i>1 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "." &&	(tokenList[i-2].name == "IDENTIFIER" || tokenList[i-2].name == "KEYWORD")){
  					//-- check if this identifier is followed by brackets and a number which may indicate a field retrieval
  					if((i+1 < tokenList.length) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "(" &&
  					(i+2 < tokenList.length) && tokenList[i+2].name == "NUMBER" &&
 					(i+3 < tokenList.length) && tokenList[i+3].name == "OPERATOR" && tokenList[i+3].value == ")"){
  					
 						//-- check if this is a known property name or method if so we won't change it
  						var id = this.kwtable[token.value.toUpperCase()];
  		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
  						
  		 		 			//-- change the array notation brackets
  		 		 			//tokenList.splice(i+1, 3);
  		 		 			tokenList[i+1].value = "[";
  		 		 			tokenList[i+3].value = "]";
  						
  		 		 			//-- insert function call
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "KEYWORD",
  		 		 				value: "getField",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;
  		 		 			//-- insert opening parenthesis
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "OPERATOR",
  		 		 				value: "(",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;					
  		 		 			//-- convert the token to a quoted string
  		 		 			token.name = "QUOTEDSTRING";
  		 		 			//-- insert closing parenthesis
  		 		 			tokenList.splice(i+1, 0, {
  		 		 				name: "OPERATOR",
  		 		 				value: ")",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;
  		 		 		} 		  		 		 		
  					//-- check if this identifier is followed by an assignment/equality operator which may indicate field assignment
  					}else if(!ifcondition && (i+2 < tokenList.length) && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "="){
 						//-- check if this is a known property name or method if so we won't change it
  						var id = this.kwtable[token.value.toUpperCase()];
  		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
  		 		 			//-- insert function call
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "KEYWORD",
  		 		 				value: "setField",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;
  		 		 			//-- insert opening parenthesis
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "OPERATOR",
  		 		 				value: "(",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;					
  		 		 			//-- convert the token to a quoted string
  		 		 			token.name = "QUOTEDSTRING";
  		 		 			//-- change equals to a comma
  		 		 			tokenList[i+1].value = ",";
  						
  		 		 			//TODO - this may need more work to account for more than one term (ie. contents in parenthesis, or end of line)
  		 		 			//-- assign termination needed as a closing parenthesis
  		 		 			termneeded = ")" + termneeded;
  		 		 		}
  					//-- check if this identifier might be a field retrieval
 					}else {
 						//-- check if this is a known property name if so we won't change it
 						//-- check if this is a known property name or method if so we won't change it
  						var id = this.kwtable[token.value.toUpperCase()];
  		 		 		if(typeof id == 'undefined' || (!id.isproperty && !id.ismethod)){
  		 		 			//-- insert function call
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "KEYWORD",
  		 		 				value: "getField",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;
  		 		 			//-- insert opening parenthesis
  		 		 			tokenList.splice(i, 0, {
  		 		 				name: "OPERATOR",
  		 		 				value: "(",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;					
  		 		 			//-- convert the token to a quoted string
  		 		 			token.name = "QUOTEDSTRING";
  		 		 			//-- insert closing parenthesis
  		 		 			tokenList.splice(i+1, 0, {
  		 		 				name: "OPERATOR",
  		 		 				value: ")",
  		 		 				pos: -1,
  		 		 				depth: -1
  		 		 			});
  		 		 			i++;
  		 			
 		 		 			//-- getField returns an array so need to convert any follow on parenthesis
 	 						var tempindex = i+1;
  		 		 			if(tempindex < tokenList.length && tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
  		 		 				tokenList[tempindex].value = "[";
  		 		 				
  		 		 				//-- adjust closing parenthesis
  		 		 				var tempcount = 1;
  		 		 				while(tempindex < (tokenList.length - 1)){
  		 		 					tempindex = tempindex + 1;
  		 		 					if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
  		 		 						tempcount = tempcount + 1;
  		 		 					}else if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == ")"){
  		 		 						tempcount = tempcount - 1;
  		 		 						if(tempcount === 0){
  		 		 							//-- found closing parenthesis so convert it 
  		 		 							tokenList[tempindex].value = "]";
  		 		 							break;
  		 		 						}
  		 		 					}
  		 		 				}
 	 						} 		 		 			
  		 		 		} 		 		 		
  					}			
  				}
  				
  				//check if this is an identifier in the class statement
  				if ( classblock && ! functionbody ){
  					classmembervariables.push(token.value.toUpperCase());
  					if ( i>0 && tokenList[i-1].value.toUpperCase() == "PRIVATE" ){
  						tokenList[i-1].value = "protected";
  						
  						//token.name = "OPERATOR";
  						//token.value = "protected $" + token.value;
  						
  					}
  				}
  				//-- check if this identifier is a declared variable and if it is followed by parenthesis 
  				if(globalvars.indexOf(token.value.toUpperCase()) > -1 || functionvars.indexOf(token.value.toUpperCase()) > -1){
  	 				if(i+1 < tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "("){
  	 					tokenList[i+1].value = "[";
  	 					var lpcount = 1;
  	 					var rpcount = 0;
  	 					var c=i+1;
  	 					while (c<tokenList.length && tokenList[c].name !== "NEWLINE"){
  	 						if(tokenList[c].name == "OPERATOR" && tokenList[c].value == "("){
  	 							lpcount ++;
  	 						}else if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ")"){
  	 							rpcount ++;
  	 							if(rpcount == lpcount){
  	 								tokenList[c].value = "]";
  	 	 							break;
  	 							}
  	 						}
  	 						c++;
  	 					}
  	 				}
  	 			} 
  				
  				//check if this a function defined elsewehere..eg scriptlibrary
  				//if it doesn't have . before function name and has a open parenthesis..then its a 
  				//function defined elsewhere
  				if (i-1>0 && tokenList[i-1].value != "." && i+1 < tokenList.length && tokenList[i+1].name == "OPERATOR" && tokenList[i+1].value == "(")
  					token.name = "OPERATROR"
  			//--4 FUNCTION	
  	 		}else if(token.name == "FUNCTION"){
  				functiondef = true;  	 	
  				functionbody = true;
  				functionname = token.value;
  				
  				if ( i-1> -1 && (tokenList[i-1].value.toUpperCase() != "PUBLIC" && tokenList[i-1].value.toUpperCase() != "PRIVATE"))
  				{
  					/*tokenList.splice(i, 0, {
  						name: "OPERATOR",
  						value: "public",
  						pos: -1,
  						depth : -1
  					});
  					i++;*/
  				}
  				if ( classblock ){
  					
  					//check if the sub name is NEW which is a constructor 
  					if ( token.value.toUpperCase() == "NEW")
  						token.value = "__construct"
  					else if (token.value.toUpperCase() == "DELETE" )
  						token.value = "__destruct"
  							
  				    
  				    
  				}else{
  					allfunctions.push ( token.value.toUpperCase());
  				}
  				
  			//--5 NEWLINE
  	 		}else if(token.name == "NEWLINE"){
  	 			//--check if a case statement is on this line
  	 			if(casestatement){
  	 				var colonfound = false;
  					var c = i;
  	 				//-- check to see if a colon exists on this line or not
  	 				while(c>0 && tokenList[c-1].name !== "NEWLINE"){
 	 					c--;
 	 					if(tokenList[c].name === "OPERATOR" && tokenList[c].value == ":"){
 	 						colonfound = true;
 	 					}
  	 				} 	
  	 				//-- if no colon found insert one to close off the case statement
  	 				if(!colonfound){
  						tokenList.splice(i, 0, {
  							name: "OPERATOR",
  							value: ":",
  							pos: -1,
  							depth: -1	 							
  						});
  	 				}
  	 			}
  	 			
  	 			//--check to see if a function definition is underway
  	 			if(functiondef){
  					//--check if the function definition header is missing parenthesis
  	 				if(lparencount == 0 && rparencount == 0){
  	 					//-- insert an opening parenthesis after the function declaration before the newline
  	 					tokenList.splice(i, 0, {
  	 						name: "OPERATOR",
  	 						value: "(",
  	 						pos: -1,
  	 						depth : -1
  	 					});
  	 					i++;

  	 					//-- insert a closing parenthesis after the function declaration before the newline
  	 					tokenList.splice(i, 0, {
  	 						name: "OPERATOR",
  	 						value: ")",
  	 						pos: -1,
  	 						depth : -1
  	 					});
  	 					
  	 					lparencount = 1;
  	 					rparencount = 1;
  	 				} 	 				
  	 			}
  	 			
  	 			if(termneeded != ""){
   					//-- insert termination characters before the end of the line
   					tokenList.splice(i, 0, {
   						name: "OPERATOR",
   						value: termneeded,
   						pos: -1,
   						depth : -1
   					});
   					i++;
   					termneeded = "";
  	 			}
  	 			
  	 			if(hascomment){
  					var c = i;
  	 				//-- check to see if a comment on this line needs to be shifted
  	 				while(c>0 && tokenList[c-1].name !== "NEWLINE"){
 	 					c--;
 	 					if(tokenList[c].name === "COMMENT"){
 	 						//-- check to see if this comment is preceeded by a newline (ie. all on its own)
 		 					if(c>0 && tokenList[c-1].name !== "NEWLINE"){
 		 						//-- shift comment to next line
 		 						tokenList.splice(i+1, 0, {
 		 							name: "COMMENT",
 		 							value: tokenList[c].value,
 		 							pos: tokenList[c].pos,
 		 							depth: tokenList[c].depth	 							
 		 						});
 		 						//-- put the comment on its own row
 		 						tokenList.splice(i+2, 0, {
 		 							name: "NEWLINE",
 		 							value: "",
 		 							pos: -1,
 		 							depth: -1	 							
 		 						});		 				
 		 						//-- remove the earlier comment
 		 						tokenList.splice(c, 1);
 		 						i--;
 		 					}
 	 					}
  	 				}
  	 			} 	 			
  	 			
 	 			//--check to see if we may have a single line/short form if statement
  	 			if(ifstatement){
  	 				var thenfollowed = false;
  	 				var f = i;
  	 				while(f>0 && tokenList[f-1].name !== "NEWLINE"){
  	 					f--;
  	 					if(tokenList[f].name === "KEYWORD" && tokenList[f].value.toUpperCase() === "THEN"){
  	 						break;
  	 					}else if(tokenList[f].name !== "COMMENT"){
  	 						thenfollowed = true;
  	 						break;
  	 					}
  	 				}
  	 				if(thenfollowed){
  	 					//-- insert an additional new line
  	 					tokenList.splice(i, 0, {
  	 						name: "NEWLINE",
  	 						value: "",
  	 						pos: -1,
  	 						depth : -1
  	 					}); 	 					
  	 					i++;
  	 					
  	 					//-- close off the if statement
  	 					tokenList.splice(i, 0, {
  	 						name: "KEYWORD",
  	 						value: "END IF",
  	 						pos: -1,
  	 						depth : -1
  	 					}); 	 					
  	 					i++;
  	 				}
  	 			} 	 			
  	 			
  	 			
  	 			ifstatement = false;
  	 			ifcondition = false;
 				forstatement = false;
 				forallstatement = false;
 				hascomment = false;
 				conststatement = false;
 				forvar = "";
 				forallvals = [];
  	 			assignmentfound = false;
  				assignlparencount = 0;
  				assignrparencount = 0;
  				termneeded = "";
  				usestatement = false;
  				casestatement = false;
  				
  			//--6 OPERATOR
  			}else if(token.name == "OPERATOR"){ 	
  				//-- check if a period for some special cases
  				if(token.value == "."){
  					//-- check if in a with statement and prior token is not an identifier
  					if(inwith && (i-1 >= 0) && tokenList[i-1].name != "IDENTIFIER"){
  						//-- also check that prior token is not a property
  						var kw = this.kwtable[tokenList[i-1].value.toUpperCase()];
  						if(typeof kw === 'undefined' || ! kw.isproperty){
  	 						if(withname[withname.length - 1] !== ""){
  	 							//-- insert object name before period
  	 							tokenList.splice(i, 0, {
  	 								name: "IDENTIFIER",
  	 								value: withname[withname.length -1],
  	 								pos: -1,
  	 								depth : -1
  	 							});
  	 							i++; 						
  	 						} 							
  						}
  					}
  				}else if(token.value == "("){
 	 				if(functiondef){
  						lparencount ++;
 	 				}else{
 	 					lparencount = 0;
 	 				}
 	 				assignlparencount ++;
 	 				
 	 				//--check to see if the previous reference might be a declared variable
 	 				if(i>1 && tokenList[i-1].name == "IDENTIFIER"){
 	 					if(functionvars.indexOf(tokenList[i-1].value.toUpperCase()) > -1 || globalvars.indexOf(tokenList[i-1].value.toUpperCase()) > -1){
 	 						//-- found a variable to the left so assume this is an array and convert the parenthesis
 	 						token.value = "[";
 	 						assignlparencount --;
 	 						
 	 						//-- adjust closing parenthesis
 	 						var tempcount = 1;
 	 						var tempindex = i;
 	 						while(tempindex < tokenList.length){
 	 							tempindex = tempindex + 1;
 	 							if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == "("){
 	 								tempcount = tempcount + 1;
 	 							}else if(tokenList[tempindex].name == "OPERATOR" && tokenList[tempindex].value == ")"){
 	 								tempcount = tempcount - 1;
 		 							if(tempcount === 0){
 		 								//-- found closing parenthesis so convert it 
 		 								tokenList[tempindex].value = "]";
 		 								break;
 		 							}
 	 							}
 	 						}
 	 					}	 					
 	 				}
  				}else if(token.value == ")"){
 	 				if(functiondef){
 	 					rparencount ++;
 	 				}else{
 	 					rparencount = 0;
 	 				}
 	 				assignrparencount ++;	 				
  				}
  				
  				if ( token.value === "+"){
  					var left = tokenList[i-1];
  					var right = tokenList[i+1];
  					//check the type of variable so we can do the appropriate concat operation
  					var stype = "";
  					if (left.name == "IDENTIFIER")
  						stype =vartypearr[left.value.toUpperCase()] ?  vartypearr[left.value.toUpperCase()]: "" ;
  					if ( stype != "STRING" && right.name == "IDENTIFIER")
  						stype =vartypearr[right.value.toUpperCase()] ?  vartypearr[right.value.toUpperCase()]: "" ;
  					if ( stype != "STRING" && (left.name == "QUOTEDSTRING" || right.name == "QUOTEDSTRING"))
  						stype = "STRING"
  							
  					if ( stype == "STRING"  ){
  						token.value = " . ";
  					}
  				
  				}
  				
 				if(token.value === "_"){
  					//-- if an underscore character found followed by newline remove the undercore and the newline
  					if((i+1 < tokenList.length) && (tokenList[i+1].name == "NEWLINE")){
  						tokenList.splice(i, 2);
  					}else{
  						tokenList.splice(i, 1);
  					} 	
  					i--;
  				}else if(token.value === "="){
  					if(!ifcondition && !assignmentfound && assignlparencount === assignrparencount){
  						token.name = "ASSIGNMENT";
  					}else if ( conststatement ){
  						token.value = ",";
  					
  					}else{
  						token.name = "EQUALITY";
  						//-- check if the prior token was an integer value, true, or false if so use more specific format
  						if(i>0 && (tokenList[i-1].name == "NUMBER" || (tokenList[i-1].name == "QUOTEDSTRING" && jQuery.trim(tokenList[i-1].value) === "") || (tokenList[i-1].name == "KEYWORD" && (tokenList[i-1].value.toUpperCase() == "TRUE" || tokenList[i-1].value.toUpperCase() == "FALSE" || tokenList[i-1].value.toUpperCase() == "NULL")))){
  							token.value = "===";
  						}else{
  							token.value = "==";
  						}
  					}
  				}else if(token.value === "<>"){
 					//-- check if the prior token was an integer value, true, or false if so use more specific not equals format
 					if(i>0 && (tokenList[i-1].name == "NUMBER" || (tokenList[i-1].name == "QUOTEDSTRING" && jQuery.trim(tokenList[i-1].value) === "") || (tokenList[i-1].name == "KEYWORD" && (tokenList[i-1].value.toUpperCase() == "TRUE" || tokenList[i-1].value.toUpperCase() == "FALSE" || tokenList[i-1].value.toUpperCase() == "NULL")))){
 						token.value = "!==";
 					}
  				}	
 				
 			//--7 NUMBER
  			}else if(token.name == "NUMBER"){
  				//-- check if the prior token is equality, if so make it more specific due to number comparison
 				if(i>0 && tokenList[i-1].name == "EQUALITY"){
 					tokenList[i-1].value = "===";
  	 			//-- check if the prior token is not equals, if so make it more specific due to number comparison 						
 				}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
 						tokenList[i-1].value = "!==";
 				}		
 			//--8 QUOTEDSTRING
  			}else if(token.name == "QUOTEDSTRING"){
  				if(jQuery.trim(token.value) === ""){
  					//-- check if the prior token is equality, if so make it more specific due to empty string
  					if(i>0 && tokenList[i-1].name == "EQUALITY"){
  						tokenList[i-1].value = "===";
  	 				//-- check if the prior token is not equals, if so make it more specific due to empty string 						
  					}else if(i>0 && tokenList[i-1].name == "OPERATOR" && tokenList[i-1].value == "<>"){
  						tokenList[i-1].value = "!==";
  					}
  				//-- if this is a use statement, then lets rename the library reference
  				}else if(usestatement){
  					token.value = token.value + ".php";
  				}
  			}
  						
  			//--check if function definition is in progress
  			if(functiondef){
  				//-- check if a return type is being declared, if so we want to clear it
  				if((tokenList.length > i+1) && tokenList[i+1].name === "KEYWORD" && tokenList[i+1].value.toUpperCase() === "AS"){
  					tokenList.splice(i+1, 2); //--remove the AS keyword plus the following type keyword
  				}
  				
  				//--check if the function definition header is complete
  				if((lparencount != 0 || rparencount != 0) && lparencount === rparencount ){ 				
 				
  					//-- insert an an opening brace after the function declaration
  					tokenList.splice(i+1, 0, {
  						name: "OPERATOR",
  						value: "{",
  						pos: -1,
  						depth : -1
  					});
 				
  					//-- insert a newline
 	 				tokenList.splice(i+2, 0, {
 	 					name: "NEWLINE",
 	 					value: "",
 	 					pos: -1,
 	 					depth : -1
 	 				});
 	 					
  					//--declare a return value for the function
  					tokenList.splice(i+3, 0, {
  						name: "KEYWORD",
  						value: "dim",
  						pos: -1,
  						depth : -1
  					}); 
  					tokenList.splice(i+4, 0, {
  						name: "IDENTIFIER",
  						value: "return_" + functionname.toLowerCase(),
  						pos: -1,
  						depth : -1
  					});
  					tokenList.splice(i+5, 0, {
  						name: "ASSIGNMENT",
  						value: "=",
  						pos: -1,
  						depth : -1
  					}); 
  					tokenList.splice(i+6, 0, {
  						name: "KEYWORD",
  						value: "null",
  						pos: -1,
  						depth : -1
  					});  					
  					//-- insert a newline
 	 				tokenList.splice(i+7, 0, {
 	 					name: "NEWLINE",
 	 					value: "",
 	 					pos: -1,
 	 					depth : -1
 	 				});
 	 				
  					functiondef = false;
  					lparencount = 0;
  					rparencount = 0; 					
  				}
  			}
  		}//--end of main loop
  		
  		//-- secondary pass through the list
  		for(var i=0; i<tokenList.length; i++){
  			var token = tokenList[i];
  			
  			//-- correct !x===y to be x!==y
  			if(token.name === "EQUALITY" && (token.value === "===" || token.value === "==")){
  				if(i-2 > -1){
  					if(tokenList[i-2].name == "KEYWORD" && (tokenList[i-2].value.toUpperCase() == "NOT")){
  						token.value = "!" + token.value.slice(1);
  						tokenList.splice(i-2, 1);
  						i--;
  					}
  				}
  			}else if(token.name === "KEYWORD" && token.value.toUpperCase() === "CASE"){
  				var c=i+1;
  				while(c<tokenList.length && tokenList[c].name !== "NEWLINE"){
  					if(tokenList[c].name == "OPERATOR" && tokenList[c].value == ","){
  						//-- found a comma in a case statement that we need to split
  						tokenList.splice(c, 0, {
  							name: "OPERATOR",
 	 	 					value: ":",
 	 	 					pos: -1,
 	 	 					depth: -1
 	 	 				});
  						c++;
 		 				tokenList.splice(c+1, 0, {
 	 	 					name: "KEYWORD",
 	 	 					value: "case",
 	 	 					pos: -1,
 	 	 					depth: -1
 	 	 				});
 		 				tokenList[c].name = "NEWLINE";
 		 				tokenList[c].value = "";
 					}
  					c++;
  				}
 			}else if ( token.name == "IDENTIFIER"){
 				//check if this is a refrence to an existing function and doesnt have the () after function name
 				var findx = allfunctions.indexOf(token.value.toUpperCase());
 				
 				if ( findx != -1 ){
 					token.name = "OPERATOR";
 					if ( i+1 < tokenList.length &&  tokenList[i+1].value != "(")
 						token.value = token.value + "()"
 				}

 			}
  			
  			
  		}//--end of secondary loop

  		return tokenList;
  	}//--end _adjustTokenList
  	
  	
  	this.parseExpr = function ( tokenNode)
  	{
  		var retval = "";
  		var tmparr = [];
  		retval= tokenNode.value;
  		tokenNode.value = "";
  		if ( tokenNode.children != null){
  			for(var c=0; c<tokenNode.children.length; c++)
 	 		{		
 	 			if(tokenNode.children[c].name == "OPERATOR" && tokenNode.children[c].value == "+"){
 	 				//tmparr.pop()
 	 				var leftside = tokenNode.children[c-1];
 					var rightside =  tokenNode.children[c+1];
 					if ( leftside.value == ")")
 						leftside = tokenNode.children[c-2];
 					tmparr.push( "_concat (" + this.parseExpr(leftside) + "," + this.parseExpr ( rightside ) + ")")
 					//c++;
 	 			}else{
 	 				tmparr.push(this.parseExpr(tokenNode.children[c]))
 	 			}
 	 		
 	 		}
  			for ( p = 0; p < tmparr.length; p ++ ){
  				retval += tmparr[p];
  			}
  		}
  		return retval;
  	}

  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: translateToJS
  	 * Performs conversion on a token node and its immediate children to generate JavaScript equivalent
  	 * Inputs: tokenNode - token - current token in the tree to proces
  	 *         lastNode - token - copy of last token processed in case we need to examine it in relation to this one
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
  	this.translateToJS = function(tokenNode, lastNode) {	
  			var lparen = 0;
  			var rparen = 0;
  			var paramcount = 0;
  			var priortoken = null;
  			var addwhitespace = true;
  			var arrayopen = false;
  			
  			/*
  			//-- loop through the immediate children of the current node to do things like checking for lists				
 			if(tokenNode.children !== null){		
 				for(var c=0; c<tokenNode.children.length; c++){				
 					if(tokenNode.children[c].name == "OPERATOR" && tokenNode.children[c].value == "+"){
 						tokenNode.children[c].value = "";
 						var leftside = tokenNode.children[c-1];
 						var rightside =  tokenNode.children[c+1];
 						if ( leftside.value == ")")
 							leftside = tokenNode.children[c-2];
 						
 						if(!arrayopen){
 							//-- need to insert an array identifier to handle this as an array
 							tokenNode.children.splice(c-1, 0, {
 								name: "OPERATOR", 
 								value: "_concat (" + this.parseExpr(leftside) + "," + this.parseExpr(rightside) + ")", 
 								parent: tokenNode, 
 								children: null
 							});
 							c++;
 						//	tokenNode.children.splice(c, 3);
 						//	c=c-3
 							
 						}
 					}
 					
 					
 				}//--end of loop

 						
 				
 			}//--end of check for childre
  			
  			*/

  			if(tokenNode.name == "OPERATOR"){
  				if(tokenNode.value == "&"){
  					tokenNode.value = ".";
  				}else if(tokenNode.value == "."){
  					
  					tokenNode.value = "->"
  					addwhitespace = false;
  				}else if(tokenNode.value == "~"){
  					tokenNode.value = "";
  					addwhitespace = false;
  				}else if(tokenNode.value == "<>"){
  					tokenNode.value = "!=";
  				}
  			}else if(tokenNode.name == "EQUALITY" && tokenNode.value == "="){
  				tokenNode.value = "==";
  			}else if(tokenNode.name == "ASSIGNMENT"){
  				tokenNode.value = "=";
  			}else if(tokenNode.name == "KEYWORD"){
  		 		var kw = this.kwtable[tokenNode.value.toUpperCase()];
  		 		if(typeof kw !== 'undefined'){
  		 			tokenNode.value = kw.replacewith;
  		 		}
  		 		addwhitespace = true;
  			}else if(tokenNode.name == "IDENTIFIER"){
 				var tempval = tokenNode.value;
 				tempval = tempval.replace(/&/g, '').replace(/%/g, '');
 				
 				var id = this.otherconvtable[tempval.toUpperCase()];
  		 		if(typeof id !== 'undefined'){
  		 			tempval = id.replacewith;
  		 		}
  		 		
  				tokenNode.value = "$" + tempval; 	
  				addwhitespace = false;
  			}else if(tokenNode.name == "QUOTEDSTRING"){
  				var tempval = tokenNode.value;
 				tempval = tempval.replace(/"/g, '\\"');
  				//tempval = safe_quotes_js(tempval, true);
  				tempval = '"' + tempval + '"';
  				tokenNode.value = tempval;
  			}else if(tokenNode.name == "COMMENT"){
  				var tempval = tokenNode.value;
  				tempval = "/*" + tempval + "*/";
  				tokenNode.value = tempval;
  			}else if(tokenNode.name == "FUNCTION"){
  				tokenNode.value = "function " + tokenNode.value;
  				functiondef = true;
  			}else if(tokenNode.name == "NEWLINE"){
  				if(lastNode && lastNode.name != "ROOT" && lastNode.name != "NEWLINE" && (lastNode.value.trim().indexOf("{", lastNode.value.trim().length - 1) === -1) && (lastNode.value.trim().indexOf("}", lastNode.value.trim().length - 1) === -1) && (lastNode.value.trim().indexOf("*/", lastNode.value.trim().length - 2) === -1) && (lastNode.value.trim().indexOf(":", lastNode.value.trim().length - 1) === -1) && lastNode.value.trim() !== ""){
  					tokenNode.value = ";" + tokenNode.value;
  				}
  				addwhitespace = false;
  			}
  			if(addwhitespace){
  				tokenNode.value = tokenNode.value + " ";
  			}
  	}//--end translateToJS
  	
  	/*-------------------------------------------------------------------------------------------------------------------------------------------- 
  	 * Method: convertCode
  	 * Converts source code to a new language format.
  	 * Inputs: fcode - string - source code to be converted
  	 *              convertto - string  - output format.  JS - for JavaScript (default)
  	 * Returns: string - converted code in string format, or unaltered code if no conversion specified
  	 *------------------------------------------------------------------------------------------------------------------------------------------- */		
  	this.convertCode = function(fcode, convertto){
  		var outputTxt = "";
  		var tokenTreeRoot = null;
  		if(typeof fcode == 'undefined' || fcode === ""){return outputTxt;}
  		
  		var convtype = (typeof convertto == 'undefined' || convertto == "" ? "PHP" : convertto.toUpperCase());
  		
  		this.input(fcode);	
  		var tokenList = this.tokenize();	
  	
  		if(convtype == "PHP"){
  			tokenList = this._adjustTokenList(tokenList);
  			tokenTreeRoot = this.tokenListToTree(tokenList);				
  			outputTxt = this._walkTokenTree(tokenTreeRoot, this.translateToJS);
  			var phpCleaner = new nowClean.php(outputTxt);
 			var cleanedCode = phpCleaner.clean();

 			outputTxt = "<?php \n" + cleanedCode;
  		}else{
  			tokenTreeRoot = this.tokenListToTree(tokenList);		
  			outputTxt = this._walkTokenTree(tokenTreeRoot);		
  		}
  		return outputTxt;
  	}//--end convertCode
  	
  }//--end LexerPHP
  




  

function beautify(sourcetext) {
	var opts = {};
    opts.indent_size = "4";
    opts.indent_char = opts.indent_size == 1 ? '\t' : ' ';
    opts.max_preserve_newlines = "5";
    opts.preserve_newlines = opts.max_preserve_newlines !== "-1";
    opts.keep_array_indentation = false;
    opts.break_chained_methods = false;
    opts.indent_scripts = true;
    opts.brace_style = "collapse";
    opts.space_before_conditional = false;
    opts.unescape_strings = false;
    opts.jslint_happy = false;
    opts.end_with_newline = false;
    opts.wrap_line_length = "0";
    opts.indent_inner_html = false;
    opts.comma_first = false;
    opts.e4x = false;
    var output = js_beautify(sourcetext, opts);
    return output;
}

nowClean = {};

nowClean.create = function (constructor){
	var nclass = function (){
		nowClean.core.apply(this, arguments);
		constructor.apply(this, arguments);
	};
	nclass.prototype = new nowClean.core();
	
	return nclass;
};

nowClean.load = function (lang){
	if(typeof require === 'undefined'){
		throw "nowClean.load may only be used with node.js";
	}
	var path = require('path');
	require(__dirname + path.sep + "nowClean." + lang + ".js")(this);
};

String.prototype.willEndWith = function(l, suffix) {
	var s = this.toString() + l;
    return s.endsWith(suffix);
};

Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time 
    if (this.length != array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i]))
                return false;       
        }           
        else if (this[i] != array[i]) { 
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;   
        }           
    }       
    return true;
};

Array.prototype.last = function(){
	return this[this.length - 1];
};

//For all browsers not yet implemented ECMAScript 6

if(typeof String.prototype.startsWith !== 'function'){
	String.prototype.startsWith = function(prefix, position){
		return this.slice(position).lastIndexOf(prefix, 0) === 0;
	};
}

if(typeof String.prototype.endsWith !== 'function'){	
	String.prototype.endsWith = function(suffix, position) {
		var p = this.slice(0, position);
		return p.indexOf(suffix, p.length - suffix.length) !== -1;
	};
}  

nowClean.core = function (code){
	this.indentationSequence = "\t";
	this.linePrefix = "";
	
	this.keepSpace = /^[a-zA-Z\.\d#]{2}$/;
	
	this.depth = 0;
	this.output = "";
	this.position = 0;
	this.code = code;
	this.inComment = false;
	this.oneLineComment = false;
	this.inString = false;
	this.inStringOpeningChar = "";
};

nowClean.core.prototype.clean = function (){
	this.preprocess();
	while(this.position < this.code.length){
		var line = this.createLine();
		if(!line) {
			break;
		}
		this.output += this.linePrefix + this.indent(line[0]) + line[1] + "\n";
	}
	
	return this.output;
};

nowClean.core.prototype.preprocess = function (){
	this.preprocessLoop();
};

nowClean.core.prototype.nextChar = function (){
	if(this.position +1 >= this.code.length) return "";
	return this.code.charAt(this.position+1);
};

nowClean.core.prototype.prevChar = function (){
	if(this.position === 0) return "";
	return this.code.charAt(this.position-1);
};

nowClean.core.prototype.isFollowing = function (str){
	return this.code.endsWith(str, this.position + str.length + 1);
};

nowClean.core.prototype.isFollowingNotCaseSensitive = function (str){
	return this.code.toLowerCase().endsWith(str.toLowerCase(), this.position + str.length + 1);
};

nowClean.core.prototype.isBefore = function (search, end){
	for(var i = this.position; i < this.code.length; i++){
		if(search.indexOf(this.code.charAt(i)) > -1){
			return true;
		}
		else if(this.code.charAt(i) == end){
			return false;
		}
	}
	return false;
};

nowClean.core.prototype.indent = function (thisLineDepth){
	var indention = "";
	for ( var i = 0; i < thisLineDepth; i++ ) {
		indention += this.indentationSequence;
	}	
	return indention;
};

nowClean.core.prototype.isCommentBegin = function (cf, p){
	return (cf[p] == "/" && cf.startsWith("/*", p)) || (cf[p] == "/" && cf.startsWith("//", p));
};

nowClean.core.prototype.isOneLineCommentBegin = function (cf, p){
	return cf[p] == "/" && cf.startsWith("//", p);
};

nowClean.core.prototype.isCommentEnding = function (cf, p){
	return cf[p] == "/" && cf.endsWith("*/", p+1);
};

nowClean.core.prototype.updateIsInComment = function (){
	if(!this.inString){
		if(!this.inComment && this.isCommentBegin(this.code, this.position)){
			this.inComment = true;
			this.oneLineComment = this.isOneLineCommentBegin(this.code, this.position);
		} 
		else if(this.oneLineComment && this.code[this.position] == "\n"){
			this.inComment = false;
			this.oneLineComment = false;
			return true;
		} 
		else if(this.inComment && this.isCommentEnding(this.code, this.position)){
			this.inComment = false;
			return true;
		}
	}
	return false;
};

nowClean.core.prototype.isString = function (cf, p, detectEnding){
	var theChar = cf[p];
	
	if(detectEnding){
		if ( theChar == this.inStringOpeningChar ){
			if ( p-1 > 0 && cf[p-1] != "\\"  )
				return true;
			else if ( p-2 > 0 && cf[p-1] == "\\" && cf[p-2] == "\\" )
				return true;
			else
				return false;
		}else{
			return false;
		}
		
	}
	
	if(theChar == "'" || theChar == '"' ){
		this.inStringOpeningChar = theChar;
		
		return true;
	}

	return false;
};

nowClean.core.prototype.updateIsInString = function (){
	if(!this.inComment){
		if(!this.inString && this.isString(this.code, this.position, false)){
			this.inString = true;
		}
		else if(this.inString && this.isString(this.code, this.position, true)){
			this.inString = false;
			return true;
		}
	}
	return false;
};

nowClean.core.prototype.preprocessLoop = function (){
	var code = "";
	
	for (; this.position < this.code.length; this.position++) {
		var theChar = this.code.charAt(this.position);
		
		var cmend = this.updateIsInComment();
		this.updateIsInString();
		
		if(this.inComment || this.inString || cmend){
			code += theChar;
		}
		else if(theChar == " "){
			var nextchar =  this.nextChar();
			var prevchar = this.prevChar();
			var totest = nextchar + prevchar;
			if(code[code.length-1] != " " && this.keepSpace.test(totest )){
				code += " ";
			}
		}
		else if(theChar != "\t" && theChar != "\n") {
			code += theChar;
		}
	}
	
	this.code = code;
	this.position = 0;
	this.inComment = false;
	this.oneLineComment = false;
};

nowClean.core.prototype.createLine = function(){
	var line = {
		string: "",
		depth: this.depth
	};
	
	for (; this.position < this.code.length; this.position++) {
		var theChar = this.code.charAt(this.position);
		
		var commentEnds = this.updateIsInComment();
		var stringEnds = this.updateIsInString();
		
		if(this.proccessLineChar(theChar, line, commentEnds, stringEnds) === true){
			break;
		}
		
	}
	this.position++;
	
	return [line.depth, line.string];
};



var nowCleanExt = function (nowClean){		
	nowClean.php = nowClean.create(function (code){
		this.keepSpace = /^[a-zA-Z\$_]{2}$/;
	});
	
	nowClean.php.prototype.preprocess = function (){
		this.code = this.code.replace(/(<\?(php)?) /g, "$1");
		this.preprocessLoop();
	};
	
	nowClean.php.prototype.isOneLineCommentBegin = function (cf, p){
		return (cf[0] == "/" && cf.startsWith("//", p)) || cf[0] == "#";
	};
	
	
	nowClean.php.prototype.proccessLineChar = function(theChar, line, commentEnds, stringEnds){
		if(commentEnds){
			line.string += theChar;
			line.string += "\n";
			return true;	
		}
		else if(this.inComment || this.inString){
			line.string += theChar;
		}
		else if(theChar == "p" && line.string.willEndWith(theChar, "<?php")){
			line.string += "p";
			return true;
		}
		else if(theChar == '='){
			if(this.prevChar() != "=" && this.prevChar() != "!"){
				line.string += " ";
			}
			line.string += '=';
			if(this.nextChar() != "=" && this.nextChar() != ">"){
				line.string += " ";
			}
		} 
		else if(theChar == ';'){
			line.string += ';';
			return true;
		}
		else if(theChar == '{'){
			line.string += ' {';
			this.depth++;
			return true;
		} 
		else if(theChar == '}'){
			line.string += '}';
			
			if(this.nextChar() != "}"){
				line.string += '\n';
			}
			
			this.depth--;
			line.depth--;
			return true;
		} 
		else if(theChar == '(' && this.nextChar() != ")"){
			line.string += '( ';
		} 
		else if(theChar == ')' && this.prevChar() != "("){
			line.string += ' )';
		}
		else if(theChar == ','){
			line.string += ', ';
		} 
		else if(theChar == '|'){
			if(this.prevChar() != "|"){
				line.string += " ";
			}
			line.string += '|';
			if(this.nextChar() != "|"){
				line.string += " ";
			}
		} 
		else if(theChar == '&'){
			if(this.prevChar() != "&" && this.nextChar() != "$"){
				line.string += " ";
			}
			line.string += '&';
			if(this.nextChar() != "&" && this.nextChar() != "$"){
				line.string += " ";
			}
		} 
		else if(theChar == '>'){
			if(this.prevChar() != ">" && this.prevChar() != "-" && this.prevChar() != "=" && this.prevChar() != "?"){
				line.string += " ";
			}
			line.string += '>';
			if(this.nextChar() == "$" || /^\d$/.test(this.nextChar())){
				line.string += " ";
			}
		}
		else if(theChar == '!' && this.nextChar() == "="){
			line.string += ' !';
		} 
		else if(theChar == '+'){
			line.string += ' + ';
		} 
		else if(theChar == '+'){
			line.string += ' + ';
		}
		else if(theChar == '*'){
			line.string += ' * ';
		} 
		else if(theChar == '^'){
			line.string += ' ^ ';
		}
		else if(theChar == '/'){
			line.string += ' / ';
		} 
		else if(theChar == '-'){
			if(this.nextChar() != ">"){
				line.string += " ";
			}
			line.string += '-';
			if(this.nextChar() != ">"){
				line.string += " ";
			}
		}
		//else if(line.string.willEndWith(theChar, "if") || line.string.willEndWith(theChar, "foreach") || line.string.willEndWith(theChar, "switch") || line.string.willEndWith(theChar, "else")){
			//line.string += theChar + " ";
		//}
		else {
			line.string += theChar;
		}
	};
};

if(typeof nowClean !== 'undefined'){
	nowCleanExt(nowClean);
}
if(typeof module !== 'undefined'){
	module.exports = nowCleanExt;
}
