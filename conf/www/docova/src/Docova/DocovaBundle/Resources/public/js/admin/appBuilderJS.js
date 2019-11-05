//-- global variables
var designversion = 2;  //design version number used to track version of form design	
var formissaved = false;
var openpar = false;
var openparwithrun = false;
var pardefcount = 0;
var lastpardefused = 0;
var arrUndo = []; 
var computedFields = [];
var fieldaction = ""; //cutelement, cutselection, copyelement, copyselection
var fieldactionelem = "";
var clipBoard = null; //obj used as a clip board
var elements = [];
var datatables = [];
var computedcode = [];
var defaultcode = [];
var fieldsarr = [];
var jsnCode = null;
var dropIntervalId = null;
var dropActionIntervalId = null;
var eventintervalcounter = 0;
var iconhtml = "";
var alliconhtml = "";	
var manualClose = false;

var defaultJsonOutline = {
		'Perspective' : 'B',
		'Alignment' : 'L',
		'Style' : 'Smokey',
		'SubmenuDetector' : false,
		'SubmenuPerspective' : null,
		'borderColor' : '#808080',
		'borderRadius' : 0,
		'borderWidth' : 0,
		'bpaddingTop' : 0,
		'bpaddingRight' : 0,
		'bpaddingBottom' : 0,
		'bpaddingLeft' : 0,
		'Items' : [],
		'designversion' : designversion.toString()
	};

function initDesignElementForm(){
	var temptype = docInfo.DesignElementType;
	var lctemptype = temptype.toLowerCase();

	if(lctemptype == "scriptlibrary" || lctemptype == "agent"){
		//Initialize ACE editor
		editor = ace.edit(temptype + "Code");
		editor.setTheme("ace/theme/chrome");
		editor.getSession().setMode("ace/mode/vbscript");
		editor.setOption("showPrintMargin", false);
	}else if(lctemptype == "css"){
		//Initialize ACE editor
		editor = ace.edit(temptype + "Code");
		editor.setTheme("ace/theme/chrome");
		editor.getSession().setMode("ace/mode/css");		
		editor.setOption("showPrintMargin", false);		
	}
	
    $(".ui-layout-center").resizable({
		handles: 's',
		ghost: true,
		resize:function ( e,ui ){
			$(".ui-resizable-helper ").css("border-bottom", " 8px solid  rgb(221,221,221)");
		},
		stop: function(e, ui){
			resizePanelVertical(ui);
			editor.resize(true);			
		}
	});
   
   
	$(window ).resize(function(e) {
		e = e || event;
		if (e.target == window) {
			$("#inner-center").height ( ($("#divContentSection").height() - $(".ui-layout-south").outerHeight())-10  );
  			$('#divViewContent').height($("#inner-center").height() - 40);
		}
		editor.resize(true);
	});
	
	$(".ui-layout-south").height("200px");	
	$("#inner-center").height ( ($("#divContentSection").height() - $(".ui-layout-south").outerHeight())-10  );
	$('#divViewContent').height($("#inner-center").height() - 40);

    $( "#tabs" + temptype).tabs();
    
    bindElmOnFocus();
    
	//---Loads the design content either default or existing---
	LoadContent();	
}


function resizePanelVertical ( ui ){
	var minWidth = 20;
	var parentHeight = ui.element.parent().height();
	
	var divTwo = ui.element.next();
     	  
    var remainingSpace = parentHeight - ( ui.element.outerHeight() + 18);
    	
    ui.element.css('width','auto');

	divTwoHeight = (remainingSpace - (divTwo.outerHeight() - divTwo.height()))/parentHeight*100+"%";
	divTwo.height(remainingSpace);
	divTwo.css('width','auto');
	
	if (typeof setSelectors === 'function') { 
		  setSelectors(); 
	}
	
	var bcWidth = jQuery(".ui-layout-south").width() - 50;
	jQuery("#eBreadCrumbs").css("width",  bcWidth + "px");
	
}

function resizePanelHorizontal ( ui ){
	
	var minWidth = 20;
	var elem = ui.element.parent();
	var parentWidth = ui.element.parent().width();
     var divTwo = ui.element.prev();
     var id = ui.element.attr("id");
         	  
     var remainingSpace = parentWidth - ( ui.element.outerWidth()+ 20);
    
     ui.element.css('height','auto');

    divTwo.width(remainingSpace);
    divTwo.css('height','auto');
    
	if (typeof setSelectors === 'function') { 
		  setSelectors(); 
	}
	
	var bcWidth = jQuery(".ui-layout-south").width() - 50;
	jQuery("#eBreadCrumbs").css("width",  bcWidth + "px");
 
}

function closeDocumentPrompt(msgTitle, msgPrompt){
	if ( typeof formissaved != 'undefined' && formissaved === true ) {
		closeDocument(); 
		return false;
	}	
	window.top.Docova.Utils.messageBox({
		width: 400,
		title: msgTitle,
		prompt: msgPrompt,
		msgIcon: "ui-icon-close",
		msgboxtype: 4,
		onYes: function(){ SaveForm(false, true)},
		onNo: function(){ closeDocument() }
	})
}

function closeDocument(){
	window.top.Docova.Utils.showProgressMessage("Saving....done!")
	window.top.Docova.Utils.hideProgressMessage();
	if(window.parent.fraTabbedTable && window.parent.fraTabbedTable.objTabBar){
		window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList( "appBuilderMainView", docInfo.FormUNID);
		window.parent.fraTabbedTable.objTabBar.CloseTab(docInfo.FormUNID, true, "appBuilderMainView");
	}
}

function SaveForm(confirmSaveFlag, andClose){
	//-----Prompt to ensure the user wants to save the form-----	
	if(confirmSaveFlag){
		var msg = "You are about to save this " + docInfo.DesignElementLabel + " design element.<br>Are you sure?";
		window.top.Docova.Utils.messageBox({
			prompt: msg,
			title: "Warning",
			width: 400,
			icontype : 2,
			msgboxtype : 4,
			onYes: function() {
				docInfo.isNewDoc = "0"
				DoSaveForm(andClose);
			},
			onNo: function() { 
				return false;
			}
		});
	}else{
		docInfo.isNewDoc = "0"
		DoSaveForm(andClose);
	}	
}

function DoSaveForm(andClose){
	var appID = docInfo.AppID;
	var uname = docInfo.UserNameAB
	var isFormNameChanged = false;
	var oldformname = "";
	var dsnName = jQuery("#DEName").val();	
	var dsnAlias = jQuery("#DEAlias").val();
	var dsnType = docInfo.DesignElementType;
	var lctemptype = dsnType.toLowerCase();		
	var dsnSubType = "";
	var dsnCode = "";
	var dsnInit = "";
	var dsnHead = "";
	var dsnEnd = "";
	var dsnHTML = "";
	var dsnHTML_m = "";
	var dsnCSS = "";
	var dsnAddCSS = "";
	var dsnAddJS = "";
	var dsnDoMobile = "";
	var dsnProperties = "";
	var otherXML = "";
	var objDsnProperties = {};
	var uname = docInfo.UserNameAB;
	var createdoctype = false;
	var createdesignelement = true;
	var ProhibitDesignUpdate = Docova.Utils.getField({ field: "ProhibitDesignUpdate" });
	
	if(dsnName == ""){
		var msg = "Please provide a " + docInfo.DesignElementLabel + " name";
		alert(msg);
		return false;
	}
	
	dsnName = dsnName.replace(/\/|\\/g, '').replace(/\s\s+/g, ' ');
	//----- If the form name has changed compared to what it was upon opening then we need to track that in order to delete the "old" design element---
	if(initFormName == ""){
		initFormName = dsnName
	}
	if(dsnName != initFormName){
		isFormNameChanged = true
		
	}
	
	if ( isFormNameChanged   && !formissaved && formExists(dsnName, dsnAlias, lctemptype)) {
		var msg = "A " + docInfo.DesignElementLabel + " with that name already exists. Please use a different name.";
		alert(msg);
		return false;		
	}
	if(strFormNameValidation(dsnName) == false || strFormNameValidation(dsnAlias) == false){
		return false;
	}
	
	if(dsnName != initFormName){
		oldformname = initFormName
		initFormName = dsnName
	}
	
/*	
 * NOT APPLICABLE IN SE
 * 	
	var elementOK = checkDesignElement(lctemptype, dsnName)
	if(elementOK == "NOTOK"){
		var msg = "This " + docInfo.DesignElementLabel + " name is already in use, please use a different name.";
		alert(msg);
		return;
	}
*/	
	//Show saving progress message
	window.top.Docova.Utils.showProgressMessage("Saving....one moment.");

	//create temp object from current HTML to clean it up
	var cleanedHtml = $("<div></div>").append ( $("#layout").html()) ;
	var cleanedHtml_m = $("<div></div>").append ( $("#layout_m").html()) ;
	//do some cleanup
	removeTableResizers( cleanedHtml);
	//cleanedHtml.find("#layout table tr th:not(:last-child)").html("")
	cleanedHtml.find("[bc]").removeAttr("bc");
	removeSelected(cleanedHtml);
	
	if(lctemptype == "agent"){
		dsnSubType = jQuery("#DESubType").val();
		
		objDsnProperties.agentschedule = jQuery("#AgentSchedule").val();		
		dsnProperties += "<agentschedule>" + objDsnProperties.agentschedule + "</agentschedule>";
		objDsnProperties.startdayofmonth = jQuery("#StartDayOfMonth").val()
		dsnProperties += "<startdayofmonth>" + objDsnProperties.startdayofmonth + "</startdayofmonth>";
		objDsnProperties.startweekday = jQuery("#StartWeekDay").val();
		dsnProperties += "<startweekday>" + objDsnProperties.startweekday + "</startweekday>";
		objDsnProperties.starthour = jQuery("#StartHour").val();
		dsnProperties += "<starthour>" + objDsnProperties.starthour + "</starthour>";
		objDsnProperties.startminutes = jQuery("#StartMinutes").val();
		dsnProperties += "<startminutes>" + objDsnProperties.startminutes + "</startminutes>";		
		objDsnProperties.starthourampm = jQuery("#StartHourAMPM").val();
		dsnProperties += "<starthourampm>" + objDsnProperties.starthourampm + "</starthourampm>";
		objDsnProperties.intervalhours = jQuery("#IntervalHours").val();
		dsnProperties += "<intervalhours>" + objDsnProperties.intervalhours + "</intervalhours>";
		objDsnProperties.intervalminutes = jQuery("#IntervalMinutes").val();
		dsnProperties += "<intervalminutes>" + objDsnProperties.intervalminutes + "</intervalminutes>";
		objDsnProperties.runas = jQuery("#RunAs").val();
		dsnProperties += "<runas>" + objDsnProperties.runas + "</runas>";
		objDsnProperties.runtimesecuritylevel = jQuery("#RuntimeSecurityLevel").val();
		dsnProperties += "<runtimesecuritylevel>" + objDsnProperties.runtimesecuritylevel + "</runtimesecuritylevel>";
		dsnProperties += "<usercode><![CDATA["+ btoa(encodeURIComponent(editor.getValue())) +"]]></usercode>";
		var editorCode = fetchCodeSections(editor.getValue());
		dsnHead = btoa(encodeURIComponent(editorCode.header));
		dsnInit = btoa(encodeURIComponent(editorCode.init));
		dsnCode = btoa(encodeURIComponent(editorCode.body));
		dsnEnd = btoa(encodeURIComponent(editorCode.end));
		createdesignelement = false;
	}else if(lctemptype == "scriptlibrary"){
		dsnSubType = jQuery("#DESubType").val();
		dsnCode = btoa(encodeURIComponent(editor.getValue()));
		createdesignelement = false;
	}else if(lctemptype == "css"){
		dsnCSS = btoa(encodeURIComponent(editor.getValue()));
		createdesignelement = false;
	}else if(lctemptype == "js" || lctemptype == "jslibrary" || lctemptype == "javascript"){
		dsnCode = btoa(encodeURIComponent(editor.getValue()));
		createdesignelement = false;
	}else if(lctemptype == "form"){
		//-- set the design version attribute
		if(jQuery("#designversion").length == 0){
			jQuery("#layout").append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
			cleanedHtml.append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
		}else{
			jQuery("#designversion").text(designversion.toString());
			cleanedHtml.find("#designversion").text(designversion.toString());
		}
		dsnAddCSS = Docova.Utils.getField({ field: "AdditionalCSS[]", separator: ';' });
		dsnAddJS = Docova.Utils.getField({ field: "AdditionalJS[]", separator: ';' });

		var jscode = editor.getValue();
		//fornow I assign the original js header code to dsnHead, in future we may want to create a separate xml node name and variable for it.
		dsnHead = btoa(encodeURIComponent(jscode));
		var eventcode = '';
		var eventlist = ["Initialize","Queryopen","Postopen","Querymodechange","Queryrecalc","Postrecalc","Querysave","Postsave","Queryclose","Terminate"];
		for(var x=0; x<eventlist.length; x++){
			var searchfor = "function " + eventlist[x] + "(";
			var funcName = dsnName.replace(/\-/g, '').replace(/\s+/g, '');
			var replacewith = "function " + eventlist[x] + "_" + funcName + "(";
			if(jscode.indexOf(searchfor)>-1){
				var re = new RegExp(searchfor.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"),"g");
				jscode = jscode.replace(re, replacewith);		
				eventcode += '\r\n' + 'FormEventList.' + eventlist[x].toLowerCase() + '.push("' + eventlist[x] + '_' + funcName + '");' 		
			}		
		}
		eventcode += '\r\n';

		dsnCode = btoa(encodeURIComponent(eventcode + jscode));
		//jQuery("table tr th").html("");
		removeTableResizers(cleanedHtml);
	//	cleanedHtml.find("table tr th").html("");
		adjustSourcePaths("relative", cleanedHtml);
		cleanedHtml.find('#layout iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				var olname = $(this).attr('outlinename');
				var newsrc = "{{ path('docova_homepage') }}wViewOutline/"+olname+"?AppID="+docInfo.AppID;
				$(this).attr("src", newsrc);
				$(this).attr("osrc", "wViewOutline/"+olname);
			}
		});
		

		cleanedHtml.find(".grid-stack").removeClass().addClass("grid-stack");
		cleanedHtml.find(".grid-stack").find(".ui-resizable-handle").remove();
    //--encode inline html before saving
		cleanedHtml.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});		
		dsnHTML = cleanedHtml.html();
	
    	cleanedHtml_m.find('iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				var olname = $(this).attr('outlinename');
				var newsrc = "{{ path('docova_homepage') }}wViewOutline/"+olname+"?AppID="+docInfo.AppID;
				$(this).attr("src", newsrc);
				$(this).attr("osrc", "wViewOutline/"+olname);
			}
		});
		//--encode inline html before saving
		cleanedHtml_m.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});				
		dsnHTML_m = cleanedHtml_m.html();
		//Add the CSS styles for this form

		//getFormCSS is in the appFormbuilder.html.twig file
		dsnCSS = getFormCSS();
		createdoctype = true;
	}else if(lctemptype == "subform"){
		//-- set the design version attribute
		if(jQuery("#designversion").length == 0){
			jQuery("#layout").append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
			cleanedHtml.append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
		}else{
			jQuery("#designversion").text(designversion.toString());
			cleanedHtml.find("#designversion").text(designversion.toString());
		}

		var jscode = editor.getValue();
		//fornow I assign the original js header code to dsnHead, in future we may want to create a separate xml node name and variable for it.
		dsnHead = btoa(encodeURIComponent(jscode));
		var eventcode = '';
		var eventlist = ["Initialize","Queryopen","Postopen","Querymodechange","Queryrecalc","Postrecalc","Querysave","Postsave","Queryclose","Terminate"];
		for(var x=0; x<eventlist.length; x++){
			var searchfor = "function " + eventlist[x] + "(";
			var funcName = dsnName.replace(/\-/g, '').replace(/\s+/g, '');
			var replacewith = "function " + eventlist[x] + "_" + funcName + "(";
			if(jscode.indexOf(searchfor)>-1){
				var re = new RegExp(searchfor.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"),"g");
				jscode = jscode.replace(re, replacewith);		
				eventcode += '\r\n' + 'FormEventList.' + eventlist[x].toLowerCase() + '.push("' + eventlist[x] + '_' + funcName + '");' 		
			}		
		}
		eventcode += '\r\n';

		dsnCode = btoa(encodeURIComponent(eventcode + jscode));
		adjustSourcePaths("relative", cleanedHtml);
		
		//cleanedHtml.find("table tr th").html("");
		removeTableResizers(cleanedHtml);
		cleanedHtml.find('#layout iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				jQuery(this).attr('src', jQuery(this).attr('dsrc'));
			}
		});
    //--encode inline html before saving
		cleanedHtml.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});		
		dsnHTML = cleanedHtml.html();
		
		cleanedHtml_m.find('iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				jQuery(this).attr('src', jQuery(this).attr('dsrc'));
			}
		});
		//--encode inline html before saving
		cleanedHtml_m.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});				
		dsnHTML_m = cleanedHtml_m.html();

	}else if(lctemptype == "page"){
		//-- set the design version attribute
		if(jQuery("#designversion").length == 0){
			jQuery("#layout").append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
			cleanedHtml.append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
		}else{
			jQuery("#designversion").text(designversion.toString());
			cleanedHtml.find("#designversion").text(designversion.toString());
		}		
		dsnProperties = "<PageBackgroundColor>" + jQuery("#PageBackgroundColor").val() + "</PageBackgroundColor>";

		var jscode = editor.getValue();
		//fornow I assign the original js header code to dsnHead, in future we may want to create a separate xml node name and variable for it.
		dsnHead = btoa(encodeURIComponent(jscode));
		var eventcode = '';
		var eventlist = ["Initialize","Queryopen","Postopen","Querymodechange","Queryrecalc","Postrecalc","Querysave","Postsave","Queryclose","Terminate"];
		for(var x=0; x<eventlist.length; x++){
			var searchfor = "function " + eventlist[x] + "(";
			var funcName = dsnName.replace(/\-/g, '').replace(/\s+/g, '');
			var replacewith = "function " + eventlist[x] + "_" + funcName + "(";
			if(jscode.indexOf(searchfor)>-1){
				var re = new RegExp(searchfor.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&"),"g");
				jscode = jscode.replace(re, replacewith);		
				eventcode += '\r\n' + 'FormEventList.' + eventlist[x].toLowerCase() + '.push("' + eventlist[x] + '_' + funcName + '");' 		
			}		
		}
		eventcode += '\r\n';

		dsnCode = btoa(encodeURIComponent(eventcode + jscode));

		adjustSourcePaths("relative", cleanedHtml);
		//cleanedHtml.find("table tr th").html("");
		removeTableResizers(cleanedHtml);
		cleanedHtml.find('#layout iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				jQuery(this).attr('src', jQuery(this).attr('dsrc'));
			}
		});
		cleanedHtml.find(".grid-stack").removeClass().addClass("grid-stack");
		cleanedHtml.find(".grid-stack").find(".ui-resizable-handle").remove();
    //--encode inline html before saving
		cleanedHtml.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});
		dsnHTML = cleanedHtml.html();
    
    cleanedHtml_m.find('iframe').each(function() {
			if (jQuery(this).attr('elem') == 'outline') {
				jQuery(this).attr('src', jQuery(this).attr('dsrc'));
			}
		});		
		//--encode inline html before saving
		cleanedHtml_m.find("div[elem=htmlcode]").each(function(){
			var htmleditor = ace.edit(this.id);
			var encodedhtml = btoa(encodeURIComponent(htmleditor.getValue()))
			//htmleditor.destroy();
			jQuery(this).html(encodedhtml);
		});				
		dsnHTML_m = cleanedHtml_m.html();


		//getPageCSS is in the appPagebuilder.html.twig file
		dsnCSS = getPageCSS();

		createdesignelement = true;
	}else if(lctemptype == "outline"){
		var tmphtml = jQuery(".OutlineItems").prop("outerHTML");
		//---Before getting html, remove isSelected class from all li elements so no element have focus class
		$("li").removeClass("isSelected");
		var TargetApplication = $("#TargetApplication").val();
		var otype = jQuery("#OutlinePerspective").val();
		dsnProperties = "<OutlineType>" + otype + "</OutlineType>";
		otherXML = "<OutlineType>" + otype + "</OutlineType>";

		//this function is in the outlinebuilder.html.twig file
		UpdateCSSTxt();
		var jsonvar = generateJsonOutline({'jsonobject': outlineJson});	
		
		dsnCSS =  cssstylestring + custcssdelm + editorcss.getValue();

		jsnCode = jsonvar;
		if(jsonvar){
			jsonvar.designversion = designversion.toString();
			dsnCode = JSON.stringify(jsonvar);
		}
		if(! andClose){
			jQuery("#divOutlineBuilder").html(tmphtml);
			initOutlineProperties();	
			reAddLIClick();
		}
		createdesignelement = true;
	} else if (lctemptype == "widget") {
		dsnCode = '';
		dsnName = jQuery("#DEName").val();
		dsnHTML = jQuery("#layout").html();
		var wdgDesc = jQuery('#DEDescription').val();
		dsnProperties = '<WidgetDesc><![CDATA['+ wdgDesc +']]></WidgetDesc>';
		createdesignelement = true;
	} else {
		window.top.Docova.Utils.hideProgressMessage();
		var msg = "Error: DoSaveForm() does not currently support elements of type " + docInfo.DesignElementType + "";
		alert(msg);
		return false;		
	}

	var request = "<Request>";
	request += "<Action>SAVEDESIGNELEMENTHTML</Action>";
	request += "<UserName><![CDATA[" + uname + "]]></UserName>";
	request += "<Document>";
	request += "<AppID><![CDATA[" + appID + "]]></AppID>";
	request += "<Unid>" + docInfo.FormUNID + "</Unid>";
	request += "<Type><![CDATA[" + dsnType + "]]></Type>";		
	request += "<SubType><![CDATA[" + dsnSubType + "]]></SubType>";	
	request += "<Name><![CDATA[" + dsnName + "]]></Name>";
	request += "<Alias><![CDATA[" + dsnAlias + "]]></Alias>";	
	request += "<ProhibitDesignUpdate>" + ProhibitDesignUpdate + "</ProhibitDesignUpdate>";	
	request += "<DoMobile>" + dsnDoMobile + "</DoMobile>";
	request += "<HeadCode><![CDATA[" + dsnHead + "]]></HeadCode>";
	request += "<InitCode><![CDATA[" + dsnInit + "]]></InitCode>";
	request += "<Code><![CDATA[" + dsnCode + "]]></Code>";
	request += "<EndCode><![CDATA[" + dsnEnd + "]]></EndCode>";
	request += "<HTML><![CDATA[" + dsnHTML + "]]></HTML>";	
	request += "<HTML_m><![CDATA[" + dsnHTML_m + "]]></HTML_m>";		
	request += "<CSS><![CDATA[" + dsnCSS + "]]></CSS>";	
	request += "<AddCSS><![CDATA[" + (dsnAddCSS ? dsnAddCSS : '') + "]]></AddCSS>";
	request += "<AddJS><![CDATA[" + (dsnAddJS ? dsnAddJS : '') + "]]></AddJS>";
	request += "<Properties>" + dsnProperties + "</Properties>";
	request += "</Document>";
	request += "</Request>";
	
	
	var formunid = SubmitRequest(encodeURIComponent(request), "DesignServices");			
	if ( ! formunid || formunid == "" || formunid == "FAILED" ) {
		window.top.Docova.Utils.hideProgressMessage();
		var msg = "An error occurred while saving the changes to this " + docInfo.DesignElementLabel + " design element."; 
		msg += "Please try again. If the problem persists, copy your code changes to a temporary location before closing the form.";

		window.top.Docova.Utils.messageBox({
			width: 400,
			title: "Error Saving",
			prompt: msg,
			icontype: 1,
			msgboxtype: 0
		});
		return;
	}
	docInfo.FormUNID = formunid;
	formissaved = true;
	
	//-- create a document type record
	if(createdoctype){
		var doctypeunid = doCreateDocType();
		if(doctypeunid){
			docInfo.DocTypeID = doctypeunid;
		}
	}
	
	//-- go ahead and create the back end design element
	if(createdesignelement){		
		var result = doCreateDesignElement({
			"elementtype" : dsnType,
			"elementname" : dsnName,
			"elementalias" : dsnAlias,
			"oldelementname" : oldformname,
			"otherproperties" : objDsnProperties,
			"silent" : false
		});		
		if(result == "FAILED"){
			window.top.Docova.Utils.hideProgressMessage();
			var msg = "An error occurred while generating the back end design element for this entry."; 
			msg += "Your changes to the design element have been saved, but the application may not work as expected until the issues creating the back end design element are resolved.";

			window.top.Docova.Utils.messageBox({
				width: 400,
				title: "Error Generating Design Element",
				prompt: msg,
				icontype: 1,
				msgboxtype: 0
			});
			return;
		}
		if(andClose == true){closeDocument();	}
	}else{
		
		if(andClose == true){closeDocument();}
	}
	jsnCode = null;

	if ( window.top && window.top.Docova )
	{
		window.top.Docova.Utils.showProgressMessage("Saving....done!")
		window.top.Docova.Utils.hideProgressMessage();	
	}
}

function doCreateDocType(options){
	
	var uname = docInfo.UserNameAB;
	var appID = docInfo.AppID;
	var datatable_array = [];
	var formname = jQuery("#DEName").val();		
	var hasRTEditor = jQuery("textarea[editortype='R']").length > 0 ? "2" : (jQuery("textarea[editortype='D']").length > 0 ? "1" : "0");
	var qoval = $("#selectQueryOpenAgent").val();
	if (qoval === null || qoval == "-Select-") {
	   qoval = "";
	}
	var qsval = $("#selectQuerySaveAgent").val();
	if (qsval === null || qsval == "-Select-") {
	   qsval = "";
	}	
	
	var request = "<Request><Action>SAVEDOCTYPE</Action>";
	request += "<UserName><![CDATA[" + uname + "]]></UserName>";
	request += "<Document>";
	request += "<DEName><![CDATA[" + formname + "]]></DEName>";
	request += "<DocTypeID>" + docInfo.DocTypeID + "</DocTypeID>";
	request += "<HasRTEditor>" + hasRTEditor + "</HasRTEditor>";
	request += "<FormUnid>" + docInfo.FormUNID + "</FormUnid>";
	request += "<AppID><![CDATA[" + appID + "]]></AppID>";
	request += "<WqoAgent><![CDATA[" + qoval + "]]></WqoAgent>";
	request += "<WqsAgent><![CDATA[" + qsval + "]]></WqsAgent>";

	$(".datatable").each ( function () 
	{
		var dttype = $(this).attr("dttype");
		if ( dttype && dttype == "local"){
			var dtobj = {"id" : $(this).attr("id"), "dttype" : "local"};
			dtobj.fields= [];
		}else{

			var dtfrm = $(this).attr("dtform");
			var dtobj = {"id" : $(this).attr("id"), "dtform" : dtfrm, "dttype" : "form"};
			var dtobjfields = [];
			$(".disland_templ_row").find("[elem]").each ( function () 
			{	
				var otype = $(this).attr("elem");
				if ( otype == "text"  || otype == "chbox" || otype == "rdbutton" || otype == "select" || otype == "toggle" || otype == "tarea" || otype== "date")
				{
					var stype = "text";
					
					if ( otype == "text")
					{
						stype = $(this).attr("textrole") && $(this).attr("textrole" ) == "n" ? "number" : stype;
					}else if (otype == "date" )
					{
						var displaytime = $(this).attr("displaytime");
						var displayonlytime = $(this).attr("displayonlytime");
						var ftype =  ((typeof displayonlytime !== "undefined" && displayonlytime == "true") ? "time" :(typeof displaytime !== "undefined" && displaytime == "true") ? "datetime" : "date");
						stype = ftype;	
					}

					dtobjfields.push({"id" : $(this).attr("id"), "type" : stype});	
				}
				
			});
			dtobj.fields = dtobjfields;
		}
		datatable_array.push(dtobj);
	});
	request += '<DataTables><![CDATA[' + (datatable_array && datatable_array.length > 0 ? encodeURIComponent(JSON.stringify(datatable_array)) : "")+ ']]></DataTables>'; 
	
	//attachments section properties
	jQuery("div[elem='attachments']").each( function() {
		var me = $(this);
		request += "<Section>";
			jQuery.each( $(me.get(0).attributes), function() {
				var name = this.name;
				if ( name && name != "" ) {
					if ( name.substr(-5) == "_prop") {
						var nodename = name.split("_prop")[0];						
						request += "<" +nodename + "><![CDATA[" + this.value + "]]></" + nodename + ">";
					}
					else if (name == 'hidewhen') {
						request += "<" +name+ "><![CDATA[" + this.value + "]]></" + name + '>';
					}
				}				
			});
			request += '<hasattachment>1</hasattachment>';
			request += "</Section>";
	});
	
	//section properties
	jQuery("div[elemtype='section']").each( function() 
	{
		var me = jQuery(this);
		if ( me.attr("elem") != "fields" ) {
			request += "<Section>";
			jQuery.each( $(me.get(0).attributes), function() {
				var name = this.name;
				if ( name && name != "" ) {
					if ( name.substr(-5) == "_prop") {
						var nodename = name.split("_prop")[0];						
						request += "<" +nodename + "><![CDATA[" + this.value + "]]></" + nodename + ">";
					}
					else if (name == 'elem') {
						request += '<sectiontype><![CDATA['+ this.value +']]></sectiontype>';
					}
				}
				
			});
			request += "</Section>";
		}
	});
	request += "</Document>";
	request += "</Request>";
	var doctypeunid = SubmitRequest(encodeURIComponent(request), "DesignServices");	
}

function doCreateDesignElement(options){
	var defaultOptns = {
		elementtype : "",
		elementname : "",
		elementalias : "",
		oldelementname : "",
		otherproperties : {},
		silent : true,
		disablesigning : false,
		disablecompile : false
	};
	var opts = $.extend({}, defaultOptns, options);	
	
	if(opts.elementtype == ""){
		opts.elementtype = docInfo.DesignElementType;
	}
	if(opts.elementname == ""){
		opts.elementname = jQuery("#DEName").val();
	}
	if(opts.elementalias == ""){
		opts.elementalias = jQuery("#DEAlias").val();		
	}	
	
	var isnamechanged = (opts.oldelementname != "" && opts.oldelementname != opts.elementname);
	var uname = docInfo.UserNameAB;
	var appID = docInfo.AppID;
	
	//for all images - make src attr equal to imagename attr to get ready for the save for the design element
	$("[elem=image]").each(function(){
		$(this).prop("src", $(this).attr("imagename"));
	});
	removeSelected();
	//enclose the checkboxes/radio buttons in a div if they are in a p as you can't have tables inside p tags
	$("[elem='chbox'],[elem='rdbutton']").each ( function () 
	{
		if ( $(this).hasClass("newElement")) return;
		var parentp = $(this).parents("[elem='par']");
		if ( parentp.length > 0 )
		{
			var origstyle = parentp.attr("style");
			origstyle += ";display:inline-flex"
			var cln = parentp.html();
			var newdiv = $("<div>" + cln + "</div>").attr("style", origstyle);
			parentp.replaceWith(newdiv);
		}
	})
	
	//change paths to relative
	adjustSourcePaths("relative");
	
	var compile = false;
	var sign = false;
	
	var dxlCode = false;
	//$("#layout table tr th:not(:last-child)").html("");
	
	if(opts.elementtype.toLowerCase() === "scriptlibrary"){
		dxlCode = parseLotusScriptCode({
			returndxl: true, 
			elementtype: opts.elementtype, 
			elementname: opts.elementname,		
			elementalias: opts.elementalias,
			silent : opts.silent
		});
		dxlCode = dxlCode != false ? ('<![CDATA['+dxlCode+']]>') : dxlCode;
		compile = (opts.disablecompile ? false : true);
		sign = (opts.disablesigning ? false : true);
	}else if(opts.elementtype.toLowerCase() === "agent"){
		dxlCode = parseLotusScriptCode({
			returndxl: true, 
			elementtype: opts.elementtype, 
			elementname: opts.elementname,		
			elementalias: opts.elementalias,
			otherproperties: opts.otherproperties,
			silent : opts.silent
		});
		dxlCode = dxlCode != false ? ('<![CDATA['+dxlCode+']]>') : dxlCode;
		compile = (opts.disablecompile ? false : true);
		sign = (opts.disablesigning ? false : true);		
	}else if(opts.elementtype.toLowerCase() === "page"){
		dxlCode = getFormDXL();
		//jQuery("#layout").html(tmphtml);
		//Init();

	}else if(opts.elementtype.toLowerCase() === "subform"){
		dxlCode = getSubformDXL();
	}else if(opts.elementtype.toLowerCase() === "form"){
		dxlCode = getFormDXL();
		//jQuery("#layout").html(tmphtml);
		//Init();
	}else if ( opts.elementtype.toLowerCase() === "outline"){

		if ( !jsnCode ){
			var jsonvar = generateJsonOutline({'jsonobject': outlineJson});		
			jsnCode = jsonvar;
		}

		dxlCode = '<twigoutline><![CDATA['+getOutlineDXL(jsnCode) +']]></twigoutline>';
	}
	else if (opts.elementtype.toLowerCase() == 'widget') {
		dxlCode = getWidgetDXL();
		//jQuery("#layout").html(tmphtml);
		//Init();		
	}
	//-- if no design details, or if any field elements have been re-defined with different properties quit early
	if(dxlCode === false || cleanFieldElements(false) === false){
		//reset the elements
		elements = [];
		defaultcode = [];
		computedcode = [];
		fieldsarr = [];
		return false;	
	}	

	var request = "<Request>";
	request += "<Action>CREATEDESIGNELEMENT</Action>";
	request += "<UserName><![CDATA[" + uname + "]]></UserName>";
	request += "<Document>";
	request += '<FormUNID><![CDATA['+ docInfo.FormUNID +']]></FormUNID>';
	request += "<AppID><![CDATA[" + appID + "]]></AppID>";
	request += '<Elements><![CDATA[' + JSON.stringify(elements) + ']]></Elements>'; 
	request += "<DesignElementType><![CDATA[" + opts.elementtype + "]]></DesignElementType>";
	request += "<DesignElementName><![CDATA[" + opts.elementname + "]]></DesignElementName>";
	request += "<DesignElementAlias><![CDATA[" + opts.elementname + "]]></DesignElementAlias>";	
	request += "<isNameChanged>" + (isnamechanged ? "true" : "false") + "</isNameChanged>";
	request += "<oldDesignElementName><![CDATA[" + opts.oldelementname + "]]></oldDesignElementName>";
	request += "<sign>" + (sign ? "1" : "") + "</sign>";
	request += "<compile>" + (compile ? "1" : "") + "</compile>";
	request += "<htmlDXL>" + dxlCode + "</htmlDXL>";
	request += "</Document>";
	request += "</Request>";
	
	var formunid = SubmitRequest(encodeURIComponent(request), "DesignServices");
	
	//after the save of the design element, the image urls need to be readded for the element builder
	$("[elem=image]").each(function(){
		if ($(this).attr('imagename') == 'DOCOVA-Logo.png') {
			$(this).prop("src", docInfo.ImgPath + '../' + $(this).attr("imagename"));
		}
		else {
			$(this).prop("src", docInfo.ImgPath + $(this).attr("imagename"));
		}
	});
	adjustSourcePaths("full");

	//after the save of the design element...change th div that we wrapped the checkboxes and radios earlier back to a P
	//enclose the checkboxes/radio buttons in a div if they are in a p as you can't have tables inside p tags
	$("[elem='chbox'],[elem='rdbutton']").each ( function () 
	{
		if ( $(this).hasClass("newElement")) return;
		var parentdiv= $(this).parent();
		if ( parentdiv.prop("nodeName") == "DIV")
		{
			var origstyle = parentdiv.attr('style');
			origstyle = origstyle.replace("display:inline-flex", "");
			var cln = parentdiv.html();
			var newp = $('<p class="selectable" elem="par" elemtype="par">' + cln + '</p>').attr("style", origstyle);
			parentdiv.replaceWith(newp);
		}
	});
	setSelectable();

	//reset the elements
	elements = [];
	defaultcode = [];
	computedcode = [];
	fieldsarr = [];
	
    return formunid;
}

function cleanFieldElements(silent){
	var result = true;
	
	var outputarray = [];
	
	var elementarray = elements; //--elements is a global variable

	var fieldnamelist = [];
	var fieldattrlist = [];
	
	for(var i=0; i<elementarray.length; i++){
		var key = elementarray[i].name.toLowerCase();
		var att = elementarray[i].type + "*" + elementarray[i].separator;
		
		var pos = fieldnamelist.indexOf(key);
		
		if(pos == -1){
			//-- not already present in the array so keep it
			outputarray.push(elementarray[i]);
			fieldnamelist.push(key);
			fieldattrlist.push(att);
		}else{
			//-- already present so don't add it

			//-- but check the attributes to see if we have a problem
			var tempatt = fieldattrlist[pos];
			if(tempatt !== att){
				if(result && !silent){
					alert("The field [" + elementarray[i].name + "] has been defined multiple times with different data type or multi value properties.\nPlease check the field definitions.");
				}
				result = false;
			}
		}
	}
		
	elements = outputarray.slice(); //--elements is a global variable

	return result;
}


function safe_tags(str, undo) {
	if(str === null | str === undefined | str === '' | str == 'f_'){
		return "";
	}else{
		if(!undo){
			return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');			
		}else{
			return str.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
		}
    }
}

//check to unsure this is a design element we can update
function checkDesignElement(type, elementname){
	var appPath = docInfo.AppPath + "/" + docInfo.AppFileName
	appPath = appPath.substring(1) //remove the first forward slash for the agent
		
	var request = "<Request><Action>CHECKDESIGNELEMENT</Action>"
	request += "<Document>"
	request += "<ElementType><![CDATA[" + type + "]]></ElementType>"	
	request += "<ElementName><![CDATA[" + elementname + "]]></ElementName>"
	request += "<AppPath>" + appPath + "</AppPath>"
	request += "</Document>"
	request += "</Request>"
	
	var result = SubmitRequest(encodeURIComponent(request), "DesignServices")
	
	return result
	
}

function formExists(elmName, elmAlias, elmType)
{
	var request = "<Request><Action>CHECKEXISTENCE</Action>";
	request += "<Document>";
	request += "<ElementType><![CDATA[" + elmType + "]]></ElementType>";	
	request += "<ElementName><![CDATA[" + elmName + "]]></ElementName>";
	request += "<ElementAlias><![CDATA[" + elmAlias + "]]></ElementAlias>";
	request += '<AppID>'+ docInfo.AppID +'</AppID>';
	request += "</Document>";
	request += "</Request>";
	
	var result = SubmitRequest(encodeURIComponent(request), "DesignServices");
	if (result == 'NODUPLICATE') { return false; }
	return true;
}

function strFormNameValidation(strValue){
	//-----Check the name to ensure there are no unallowed chars used-----
	strNotAllowed = "<>&*%$";
	var currChar;
	var fixedString = "";
	if ( !strValue ) strValue = "";
	for(var k=0; k<strValue.length; k++){
		currChar = strValue.substring(k, k+1)
		if(strNotAllowed.indexOf(currChar) != -1){
			Docova.Utils.messageBox({
				title: "String Error",
				prompt: "The " + docInfo.DesignElementLabel + " name cannot have the following characters in it.  <>&*%$.",
				icontype: 3
			})
			return false;
		}
	}
	return true;
}

function SubmitRequest(request, agentName, detailedResult){
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + agentName  + "?OpenAgent"
	var result = false;
	var resulttext = "";
	var returndata = null;
	
	$.ajax({
		type: "POST",
		url: processUrl,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			resulttext = xmlobj.find("Result[ID=Ret1]").text();
			if(statustext == "OK"){
				result = true;
			}
		},
		error: function(){
		}
	})
	
	if(!detailedResult){
		returndata = (result === true ? resulttext : "FAILED");
	}else{
		returndata = {
				'status' : result,
				'data' : resulttext
		}					
	}
	
	return returndata;
}


function parseLotusScriptCode(options) {
	var result = false;

	var defaultOptns = {
		silent : false,
		returndxl : false,
		elementtype : "",
		elementname : "",
		elementalias : ""
	};

	var opts = $.extend({}, defaultOptns, options);

	var nl = "\r\n";
	var dxldata = "";
	
	var optiondata = "";
	var declarationdata = "";
	var maincodedata = "";
	
	var maincodebuffer = "";
	var commentbuffer = "";

	var errormsg = "";

	var editdoc = editor.getSession().getDocument();
	var codearray = editdoc.getAllLines();

	var linetype = "";
	var linetxt = "";
	var subfuncname = "";
	var infunc = false;
	var inclass = false;
	var insub = false;
	var intype = false;
	var incomment = false;

	var i = 0;
	var limit = codearray.length;
	for (i = 0; i < limit; i++) {
		linetxt = codearray[i];

		var templine = linetxt.trim().substr(0, 17).toLowerCase();
		if (templine.trim() === "%end rem" || templine.trim() === "%endrem") {
			linetype = "comment_close";
		} else if (incomment){
			linetype = "comment";			
		} else if (templine.trim() === "%rem" || templine.substr(0, 5) === "%rem ") {
			linetype = "comment_open";
		} else if (templine.substr(0, 1) === "'" || templine.substr(0, 4) === "rem " || templine === "rem") {
			linetype = "comment";
		} else if (templine.substr(0, 9) === "function " || templine.substr(0, 17) === "private function " || templine.substr(0, 16) === "public function ") {
			linetype = "function_open";
		} else if (templine.substr(0, 4) === "sub " || templine.substr(0, 12) === "private sub " || templine.substr(0, 11) === "public sub ") {
			linetype = "sub_open";
		} else if (templine === "end function") {
			linetype = "function_close";
		} else if (templine === "end sub") {
			linetype = "sub_close";
		} else if (templine.substr(0, 6) === "class " || templine.substr(0, 14) === "private class " || templine.substr(0, 13) === "public class ") {
			linetype = "class_open";
		} else if (templine === "end class") {
			linetype = "class_close";
		} else if (templine.substr(0, 5) === "type " || templine.substr(0, 13) === "private type " || templine.substr(0, 12) === "public type ") {
			linetype = "type_open";
		} else if (templine === "end type") {
			linetype = "type_close";
		} else if (templine.substr(0, 4) === "use " || templine === "option public" || templine === "option declare") {
			linetype = "option";
		} else {
			linetype = "";
		}

		//-- now check the results of the new line type
		if (linetype === "comment_open") {
			incomment = true;
			if (opts.returndxl) {
				if (inclass || !(insub || infunc || intype )) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
				}else{
					maincodebuffer += linetxt + nl;
				}
			}
		} else if (linetype === "comment_close") {
			if (incomment) {
				incomment = false;
				if (opts.returndxl) {
					if (inclass || !(insub || infunc || intype )) {
						declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					}else{
						
						maincodebuffer += linetxt + nl;
					}
				}
			} else {
				errormsg = "Comment end marker with no starting comment marker.";
				break;
			}
		} else if (linetype === "comment") {
			if (opts.returndxl) {
				if (inclass || !(insub || infunc || intype )) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
				}else{
				
					maincodebuffer += linetxt + nl;
				}
			}
		} else if (linetype === "function_open") {
			if (infunc) {
				errormsg = "New function declared without closing preceeding function.";
				break;
			} else if (insub) {
				errormsg = "Function declared without closing preceeding sub.";
				break;
			} else if (intype) {
				errormsg = "Function declared without closing preceeding type.";
				break;
			} else if (inclass) {
				infunc = true;
				if (opts.returndxl) {					
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			} else {
				var pos = linetxt.indexOf("function ");
				subfuncname = linetxt.substr(pos + 9);
				var pos = subfuncname.indexOf("(");
				if (pos > 0) {
					subfuncname = subfuncname.substr(0, pos);
				}
				subfuncname = subfuncname.trim();
				if (subfuncname == "") {
					errormsg = "Missing sub routine name in declaration.";
					break;
				} else {
					infunc = true;
					if (opts.returndxl) {
						maincodebuffer = (commentbuffer != "" ? commentbuffer + nl: "") + linetxt + nl;
						commentbuffer = "";
					}
				}
			}
		} else if (linetype === "function_close") {
			if (!infunc) {
				errormsg = "Function closed with no preceding function declaration. ";
				break;
			} else if(inclass){
				infunc = false;
				if (opts.returndxl) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			} else {
				infunc = false;
				if (opts.returndxl) {
					maincodebuffer += linetxt + nl;
					
					maincodedata += "<code event='" + subfuncname + "'><lotusscript>";
					maincodedata += safe_tags(maincodebuffer);
					maincodedata += "</lotusscript></code>";
					
					maincodebuffer = "";
				}
			}
		} else if (linetype === "sub_open") {
			if (infunc) {
				errormsg = "Sub routine declared without closing preceeding function.";
				break;
			} else if (insub) {
				errormsg = "New sub routine declared without closing preceeding sub routine.";
				break;
			} else if (intype) {
				errormsg = "New sub routine declared without closing preceeding type.";
				break;
			} else if (inclass) {
				insub = true;
				if (opts.returndxl) {					
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			} else {
				var pos = linetxt.indexOf("sub ");
				subfuncname = linetxt.substr(pos + 4);
				var pos = subfuncname.indexOf("(");
				if (pos > 0) {
					subfuncname = subfuncname.substr(0, pos);
				}
				subfuncname = subfuncname.trim();
				if (subfuncname == "") {
					errormsg = "Missing sub routine name in declaration.";
				} else {
					insub = true;
					if (opts.returndxl) {		
						maincodebuffer = (commentbuffer != "" ? commentbuffer + nl: "") + linetxt + nl;
						commentbuffer = "";						
					}
				}
			}
		} else if (linetype === "sub_close") {
			if (!insub) {
				errormsg = "Sub routine closed with no preceding sub declaration.";
				break;
			} else if(inclass){
				insub = false;
				if (opts.returndxl) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			} else {
				insub = false;
				if (opts.returndxl) {
					maincodebuffer += linetxt + nl;
					
					maincodedata += "<code event='" + (subfuncname.toLowerCase() == "initialize" ? "initialize" : (subfuncname.toLowerCase() == "terminate" ? "terminate" : subfuncname)) + "'><lotusscript>";
					maincodedata += safe_tags(maincodebuffer);
					maincodedata += "</lotusscript></code>";
					
					maincodebuffer = "";
				}
			}
		} else if (linetype === "type_open") {
			if (infunc) {
				errormsg = "Type declared without closing preceeding function.";
				break;
			} else if (insub) {
				errormsg = "Type declared without closing preceeding sub routine.";
				break;
			} else if (intype) {
				errormsg = "New type declared without closing preceeding type.";
				break;
			} else if (inclass) {
				errormsg = "Type declared without closing preceeding class.";
				break;
			} else {
				intype = true;
				if (opts.returndxl) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			}
		} else if (linetype === "type_close") {
			if (!intype) {
				errormsg = "Type closed with no preceding type declaration. ";
				break;
			} else {
				intype = false;
				if (opts.returndxl) {
					declarationdata += linetxt + nl;
				}
			}
		} else if (linetype === "class_open") {
			if (infunc) {
				errormsg = "Class declared without closing preceeding function.";
				break;
			} else if (insub) {
				errormsg = "Class declared without closing preceeding sub routine.";
				break;
			} else if (intype) {
				errormsg = "Class declared without closing preceeding type.";
				break;
			} else if (inclass) {
				errormsg = "New class declared without closing preceeding class.";
				break;
			} else {
				inclass = true;
				if (opts.returndxl) {
					declarationdata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
					commentbuffer = "";
				}
			}
		} else if (linetype === "class_close") {
			if (!inclass) {
				errormsg = "Class closed with no preceding class declaration. ";
				break;
			} else {
				inclass = false;
				if (opts.returndxl) {
					declarationdata += linetxt + nl;
				}
			}
		} else if (linetype === "option") {
			if (opts.returndxl) {
				optiondata += (commentbuffer != "" ? commentbuffer + nl : "") + linetxt + nl;
				commentbuffer = "";
			}
		} else {
			if (opts.returndxl) {
				if (!(inclass) && (insub || infunc)) {
					maincodebuffer += linetxt + nl;
				} else {
					declarationdata += linetxt + nl;
				}
			}
		}
	} //--end of loop through code lines


	//-- if no errors so far check to see that everything is closed off
	if (errormsg === "") {
		if (insub) {
			errormsg = "Missing sub routine closure.";
		} else if (infunc) {
			errormsg = "Missing function closure.";
		} else if (incomment) {
			errormsg = "Missing comment closure.";
		} else if (inclass) {
			errormsg = "Missing class closure.";
		} else if (intype) {
			errormsg = "Missing type closure.";
		}
	}

	if (errormsg == "") {
		if (opts.returndxl) {
			dxldata =  "<?xml version='1.0' encoding='UTF-8'?>";
			dxldata += "<database>";
				if(opts.elementtype.toLowerCase() == "agent"){
					dxldata += "<agent name='" + opts.elementname + "' alias='" + opts.elementalias + "' ";
					if(opts.otherproperties && opts.otherproperties.runas && opts.otherproperties.runas == "A"){
						dxldata += " runaswebuser='false' ";						
					}else{
						dxldata += " runaswebuser='true' ";
					}
					if(opts.otherproperties && opts.otherproperties.runtimesecuritylevel){
						if(opts.otherproperties.runtimesecuritylevel == "2"){
							dxldata += " restrictions='unrestricted' ";							
						}else if(opts.otherproperties.runtimesecuritylevel == "3"){
							dxldata += " restrictions='fulladminunrestricted' ";
						}
					}					
					dxldata += " publicaccess='false' noreplace='true' comment='APPBUILDER'>";
	
					if(opts.otherproperties && opts.otherproperties.agentschedule && opts.otherproperties.agentschedule !== "" & opts.otherproperties.agentschedule !== "0"){
						dxldata += "<trigger type='scheduled'>";	
						var includestarttime = true;
						
						switch(opts.otherproperties.agentschedule){
						case "1":
							dxldata += "<schedule type='byminutes'";
							dxldata += " hours='" + (opts.otherproperties.intervalhours ? opts.otherproperties.intervalhours : "0") + "' ";
							dxldata += " minutes='" + (opts.otherproperties.intervalminutes ? opts.otherproperties.intervalminutes : "0") + "'";
							dxldata += " runlocation='any'>";
							includestarttime = false;
							break;
						case "D":
							dxldata += "<schedule type='daily'";
							dxldata += " runlocation='any'>";
							break;
						case "W":
							dxldata += "<schedule type='weekly'";
							dxldata += " dayofweek='" + (opts.otherproperties.startweekday ? opts.otherproperties.startweekday.toLowerCase() : "") + "' ";
							dxldata += " runlocation='any'>";						
							break;
						case "M":
							dxldata += "<schedule type='monthly'";
							dxldata += " dateinmonth='" + (opts.otherproperties.startdayofmonth ? opts.otherproperties.startdayofmonth.toLowerCase() : "") + "' ";
							dxldata += " runlocation='any'>";													
							break;							
						}
						
						if(includestarttime){
							dxldata += "<starttime>";
							dxldata += "<datetime>";
								dxldata += "T"
								var hour = (opts.otherproperties.starthour ? parseInt(opts.otherproperties.starthour, 10) : 0);
								if(opts.otherproperties.starthourampm.toUpperCase() == "PM"){
									if (hour <= 12){
										hour = hour + 12;
									}
								}
								dxldata += (hour < 10 ? "0" : "") + hour.toString();
								var minutes = (opts.otherproperties.startminutes ? parseInt(opts.otherproperties.startminutes, 10) : 0);
								dxldata += (minutes < 10 ? "0" : "") + (minutes > 59 ? "59" : minutes.toString());
								dxldata += "00,00";										
							dxldata += "</datetime>";
							dxldata += "</starttime>";		
						}
						dxldata += "</schedule>";
						dxldata += "</trigger>";
					}else{					
						dxldata += "<trigger type='agentlist'/>";
					}
					dxldata += "<documentset type='runonce'/>";					
				}else if(opts.elementtype.toLowerCase() == "scriptlibrary"){
					dxldata += "<scriptlibrary name='" + opts.elementname + "' noreplace='true' comment='APPBUILDER'>";					
				}
						dxldata += "<code event='options'><lotusscript>";
						dxldata += safe_tags(optiondata);
						dxldata += "</lotusscript></code>";

						dxldata += "<code event='declarations'><lotusscript>";
						dxldata += safe_tags(declarationdata);
						dxldata += "</lotusscript></code>";
					
						dxldata += maincodedata;

				if(opts.elementtype.toLowerCase() == "agent"){						
					dxldata += "</agent>";
				}else if(opts.elementtype.toLowerCase() == "scriptlibrary"){
					dxldata += "</scriptlibrary>";
				}
			dxldata += "</database>";
		
			result = dxldata;
		} else {
			result = true;
		}
	} else {
		if (!opts.silent) {
			editor.focus();
			editor.resize(true);
			editor.scrollToLine(i+1, true, true, function () {});
			editor.gotoLine(i+1, 0, true);
			alert("Error at line # " + (i + 1).toString() + ":  " + errormsg);
		}
	}

	return result;
}//--end parseLotusScriptCode


function getOutlineDXL(jsonobj)
{
	var otwig =  JSON.stringify(jsonobj, function(key, value){
		if ( key.toLowerCase() == "items"){
			return undefined;
		}
		else if ( key.toLowerCase() == "context")
		{
			return undefined;
			
		} else{
			return value;
		}
	});

	otwig = otwig.slice(0, -1);

	if ( jsonobj.hasOwnProperty('context'))
	{
		//add context 
		otwig += ', "context":'
		if ( jsonobj.labelformula && jsonobj.labelformula != ""){
			var labeltwig = "\r\n";
			labeltwig +=  updateTWIG (jsonobj.labelformula, "set", "string");
			labeltwig += '\r\n"{{ __dexpreres | unescape | raw }}"\r\n';
			otwig += labeltwig;
		}else
			otwig += '"' + jsonobj.context + '"';
	}
	otwig += ', "Items":['

	var items = jsonobj.Items ? jsonobj.Items : jsonobj.items ;
	if ( items != null ){ 
		for ( var p = 0 ; p < items.length ; p ++){



			if ( items[p].hidewhen && items[p].hidewhen != ""){
					var hidewhenpardef = updateTWIG (items[p].hidewhen, "set", "bool");
					hidewhenpardef += '{% set hwen = __dexpreres %}\r\n'
					var formula =  'hwen';
					hidewhenpardef += '{% if  not ( ' + formula + ') %}\r\n';
					otwig += '\r\n' + hidewhenpardef + '\r\n'
			}

			otwig +=  getOutlineDXL(items[p]) + ", " ;
			

			if ( items[p].hidewhen && items[p].hidewhen != ""){
				otwig += '\r\n{% endif %}\r\n'
			}
		}
		otwig += '{}';
	}
	otwig += "]}";
	return otwig;

}

function getWidgetDXL()
{
	var dsnName = jQuery("#DEName").val();
	var dsnDesc = jQuery("#DEDescription").val();

	var myxml = '<widgethtml><![CDATA[';
	$("div[elemtype='section']").each( function() {
		if($(this).parents("div[elemtype='section']:first").length == 0){
			myxml += htmlTree(this);
		}		
	});
	myxml += ']]></widgethtml>';
	return myxml;
}

function getFormDXL()
{
	var retval = false;
	computedFields = [];
	computedFields.length = 0;
	
	var dsnName = jQuery("#DEName").val();
	var dsnAlias = jQuery("#DEAlias").val();
	
	var myxml =  "<form name='" + dsnName + "' alias='" + dsnAlias + "'>";

	//-- get the standard browser form data
	var targetarea = jQuery("#layout"); 	
	$("#pageProperties", targetarea).removeClass("ui-sortable-handle"); //clean up first	
	myxml += getFormDXLSub(targetarea, "edit", false);
	myxml += getFormDXLSub(targetarea, "read", false);

	var titleformula = $("#pageProperties", targetarea).attr("titleformula") || "";
	if(titleformula.trim() !== "" ){
		saveComputedVal("computed", titleformula, "__title");
	}	
	
	//-- get the mobile interface form data
	targetarea = jQuery("#layout_m"); 	
	$("#pageProperties", targetarea).removeClass("ui-sortable-handle"); //clean up first		
	myxml += getFormDXLSub(targetarea, "edit", true);
	myxml += getFormDXLSub(targetarea, "read", true);

	var computedcodestr = "";
	for ( var kp =0; kp < computedcode.length; kp ++){
		if(computedcodestr.trim() != "" & computedcodestr.slice(-1) !== ","){
			computedcodestr += ",";
		}
		computedcodestr += computedcode[kp];
	}
	if(computedcodestr.trim() == ""){
		computedcodestr = "{}"; //always include something in the subform so that any json generated doesnt have mis matched delimiters
	}
	if(computedcodestr.slice(-1) === ","){
		computedcodestr = computedcodestr.slice(0, -1);
	}

	var defaultcodestr = "";
	for ( var kp =0; kp < defaultcode.length; kp ++){
		if(defaultcodestr.trim() != "" & defaultcodestr.slice(-1) !== ","){
			defaultcodestr += ",";
		}
		defaultcodestr += defaultcode[kp];
	}
	if(defaultcodestr.trim() == ""){
		defaultcodestr = "{}"; //always include something in the subform so that any json generated doesnt have mis matched delimiters
	}
	if(defaultcodestr.slice(-1) === ","){
		defaultcodestr = defaultcodestr.slice(0, -1);
	}
	
	myxml += "<computedvalues>{{ f_SetUser(user) }}\r\n{{ f_SetApplication(app.request.query.get('AppID')) }}\r\n{% if object %}\r\n{{f_SetDocument(object)}}\r\n{% endif %}\r\n[" + computedcodestr  + "]</computedvalues>";
	myxml += "<defaultvalues>{{ f_SetUser(user) }}\r\n{{ f_SetApplication(app.request.query.get('AppID')) }}\r\n{{ f_SetNewDocUNID(newunid) }}\r\n{% if object %}\r\n{{f_SetDocument(object)}}\r\n{% endif %}\r\n[" + defaultcodestr  + "]</defaultvalues>";
	
	myxml += "</form>";
	retval = myxml;
	
	return retval;
}

function getFormDXLSub(targetarea, mode, ismobile){
	var subxml = "";
	
	subxml += getActionDXL('form', mode, targetarea, ismobile);
		
	subxml += '<'+mode+'body' + (ismobile ? "_m" : "") + '>';
	subxml += safe_tags($("#pageProperties", targetarea).prop("outerHTML"));
	subxml += safe_tags($("#workflowsection", targetarea).prop("outerHTML"));	

	if(!ismobile){
		var tmpSpacing = jQuery(".grid-stack", targetarea).attr("horizontalSpacing");
		if ( tmpSpacing){
			jQuery(".grid-stack", targetarea).attr("horizontalSpacing", tmpSpacing);
		}	
		subxml += safe_tags("<div class='grid-stack' " + (tmpSpacing ? "horizontalSpacing='" + tmpSpacing + "'" : "" ) + " >")
		$(".grid-stack > .grid-stack-item", targetarea).each ( function () 
		{
			var gsitem = $(this);
			if ( gsitem.hasClass("grid-stack-item")){
				var gshtml = "<div class='grid-stack-item' data-gs-x='" + gsitem.attr("data-gs-x") + "' ";
				gshtml += "data-gs-y='" + gsitem.attr("data-gs-y") + "' ";
				gshtml += "data-gs-width='" + gsitem.attr("data-gs-width") + "' ";
				gshtml += "data-gs-height='" + gsitem.attr("data-gs-height") + "' >";
				var contentDiv =  gsitem.children(".grid-stack-item-content");
				gshtml += "<div class='grid-stack-item-content' style='" + ( contentDiv.attr("style") ? contentDiv.attr("style") : '' ) + "'>";
				subxml += safe_tags(gshtml);
			}

			$(contentDiv).children("[elemtype='section']").each ( function (){
				var hidecode = handleHideWhenClientSide( $(this), mode, "span");
				if (hidecode != ""){
					subxml += hidecode;
					subxml += safe_tags('<span style="display:{{ hwen ? \'none\' : \'\' }};">\r\n');
				}
				subxml += htmlTree(this, mode);
				if ( hidecode != "" ){
					subxml += safe_tags("</span>");
				}
			});
			if ( gsitem.hasClass("grid-stack-item")){
				subxml += safe_tags("</div></div>");  //end the grid-stack-item and grid-stack-item-conent
			}
		});
		subxml += safe_tags("</div>");
	}else{
		$("div[elemtype='section']", targetarea).each( function() {
			//ignore if this section is contained within another section since we don't want to double up
			if($(this).parents("div[elemtype='section']:first").length == 0){
				var hidecode = handleHideWhenClientSide( $(this), mode, "div");
				if (hidecode != ""){
					subxml += hidecode;
					subxml += safe_tags('<div style="display:{{ hwen ? \'none\' : \'\' }};">\r\n');
				}
				subxml += htmlTree(this, mode, ismobile);
				if ( hidecode != "" ){
					subxml += safe_tags("</div>");
				}
			}
		});
	}

	//-- add attachments subform if attachment section added or if subforms added (since we don't know what might be on a subform)
	if ( $("div[elem='attachments'],div[elem='subform']", targetarea).length > 0 ){	
		subxml += "{% include 'DocovaBundle:Subform:sfDocSection-FormAttachments" + (ismobile ? "_m" : "") + (mode == "read" ? "-read" : "") +".html.twig' ignore missing with { 'doc' : object, 'mode': '" + mode + "' } %}\r";
	}

	if(mode == "edit"){
		subxml += safe_tags('\r\n<script type="text/javascript">\r\n');
		subxml += safe_tags('FieldInfo = typeof FieldInfo === typeof undefined ? [] : FieldInfo;\r\n');
		subxml += safe_tags (fieldsarr.join(";\r\n"));
		subxml += safe_tags( '\r\nfunction getFieldInfoVar(){\r\n return FieldInfo;\r\n }\r\n</script>\r\n');
	}
	
	subxml += '</' + mode + 'body' + (ismobile ? "_m" : "") + '>';

	return subxml;
}

function getSubformDXL()
{
	var retval = false;
	computedFields = [];
	computedFields.length = 0;
	
	var dsnName = jQuery("#DEName").val();
	var dsnAlias = jQuery("#DEAlias").val();

	var myxml =  "<subform name='" + dsnName + "' alias='" + dsnAlias + "' >";

	//-- get the standard browser subform data
	var targetarea = jQuery("#layout"); 		
	myxml += getSubformDXLSub(targetarea, "edit", false);
	myxml += getSubformDXLSub(targetarea, "read", false);

	//-- get the mobile interface subform data
	targetarea = jQuery("#layout_m"); 			
	myxml += getSubformDXLSub(targetarea, "edit", true);
	myxml += getSubformDXLSub(targetarea, "read", true);
	

	var computedcodestr = "";
	for ( var kp =0; kp < computedcode.length; kp ++){
		if(computedcodestr.trim() != "" & computedcodestr.slice(-1) !== ","){
			computedcodestr += ",";
		}
		computedcodestr += computedcode[kp];
	}
	if(computedcodestr.trim() == ""){
		computedcodestr = "{}"; //always include something in the subform so that any json generated doesnt have mis matched delimiters
	}
	if(computedcodestr.slice(-1) === ","){
		computedcodestr = computedcodestr.slice(0, -1);
	}	

	var defaultcodestr = "";
	for ( var kp =0; kp < defaultcode.length; kp ++){
		if(defaultcodestr.trim() != "" & defaultcodestr.slice(-1) !== ","){
			defaultcodestr += ",";
		}
		defaultcodestr += defaultcode[kp];
	}
	if(defaultcodestr.trim() == ""){
		defaultcodestr = "{}"; //always include something in the subform so that any json generated doesnt have mis matched delimiters
	}
	if(defaultcodestr.slice(-1) === ","){
		defaultcodestr = defaultcodestr.slice(0, -1);
	}

	myxml += "<computedvalues>" + computedcodestr + "</computedvalues>";
	myxml += "<defaultvalues>" + defaultcodestr + "</defaultvalues>";
	
	myxml += "</subform>";

	retval = myxml;
	
	return retval;
}


function getSubformDXLSub(targetarea, mode, ismobile){
	var subxml = "";
	
	subxml += getActionDXL('subform', mode, targetarea, ismobile);
		
	subxml += '<'+mode+'body' + (ismobile ? "_m" : "") + '>';
	
	$(targetarea).children("div[elemtype='section']").each( function() {
		var hidecode = handleHideWhenClientSide( $(this), mode, "div");
		if (hidecode != ""){
			subxml += hidecode;
			subxml += safe_tags('<div style="display:{{ hwen ? \'none\' : \'\' }};">\r\n');
		}
		subxml += htmlTree(this, mode, ismobile);
		if ( hidecode != "" ){
			subxml += safe_tags("</div>");
		}
	});


	if(mode == "edit"){
		subxml += safe_tags('\r\n<script type="text/javascript">\r\n');
		subxml += safe_tags('FieldInfo = typeof FieldInfo === typeof undefined ? [] : FieldInfo;\r\n');
		subxml += safe_tags (fieldsarr.join(";\r\n"));
		subxml += safe_tags( '\r\n</script>\r\n');
	}
	
	subxml += '</' + mode + 'body' + (ismobile ? "_m" : "") + '>';

	return subxml;
}


function getActionDXL(type, mode, targetarea, ismobile)
{
	var isMobile = (typeof ismobile !== "undefined" && ismobile !== null && (ismobile === true || ismobile === 1) ? true : false);
	var mobileext = (isMobile ? "_m" : "");
	
	var dxl = "<"+ type +"actionbar" + mode + mobileext + "><![CDATA[";
	
	var btns = '<div>' + $('#divToolBar' + mobileext, targetarea).html() + '</div>';
	$(btns).find('button').each(function() {
		var hide = handleHideWhenDXL(this, mode);
		if ( hide != "")
			dxl += hide + "\r";
		
		dxl += '<a ' + (isMobile ? 'class="pageBtns" data-role="none" ' : '');
		dxl += 'onclick="'+this.getAttribute('fonclick')+'" ';
		var iconprim = ($(this).attr("btnprimaryicon") ? $(this).attr("btnprimaryicon") : "");
		var iconsec = ($(this).attr("btnsecondaryicon") ? $(this).attr("btnsecondaryicon") : "");
		var showtext = ($(this).attr('showlabel') === "0" || $(this).attr('btntext') === "0" ? "0" : "1");
		var ganttview = $(this).attr('embeddedganttview') ? $(this).attr('embeddedganttview') : null;
		var ganttcatg = ganttview && $(this).attr('btnganttcatfield') ? $(this).attr('btnganttcatfield') : null;
		dxl += 'primary="' + iconprim + '" ';
		dxl += 'secondary="' + iconsec + '" ';
		dxl += 'btntext="' + showtext + '" ';
		dxl += ganttview ? ('embeddedganttview="'+ ganttview + '" ') : '';
		dxl += ganttcatg ? ('btnganttcatfield="'+ ganttcatg + '" ') : '';
		dxl += 'id="'+(this.getAttribute('id') ? this.getAttribute('id') : '')+ '">';
		dxl += this.getAttribute('btnlabel') ? this.getAttribute('btnlabel') : '';
		dxl += '</a>\n\r\t';

		if ( hide != "")
			dxl += "{% endif %}\r"
	});

	dxl += "]]></"+ type +"actionbar" + mode + mobileext + ">";

	return dxl;
}


/* function creates a div and sets it to display:none if hide when formula is true */
/*  this function return <div style='display:none'> for example
	the div needs to be closed after the section dxl code */	
function handleHideWhenClientSide(obj, mode,  wrappertype)
{
	var hidewhenpardef = "";
	var formula  = "";
//	var tagname = (wrappertype && wrappertype == "div" ? "div" : "span");
	
	var hideWhen = obj.attr("hidewhen")
	if ( hideWhen && hideWhen != "" ){
//		hidewhenpardef = safe_tags('<' + tagname + ' style="display:');
		var customhidewhen = obj.attr("customhidewhen")
		if ( jQuery.trim(customhidewhen) != "" )
		{
				hidewhenpardef += updateTWIG (customhidewhen, "set", "bool");
				hidewhenpardef += '{% set hwen = __dexpreres %}\r\n'
		}
		if ( mode == "read"){
			if ( hideWhen.indexOf("R") != -1 )
				formula = "true";
			else
				formula = "false";
		}else{
			if ( hideWhen.indexOf("E") != -1 )
				formula = "true";
			else
				formula = "false";
		}

		/*if ( hideWhen.indexOf("R") != -1 )
			  formula = (mode == "read")? "true" : "false";
		if ( hideWhen.indexOf("E") != -1 ){
			formula = (mode == 'edit' ? "true" : "false");
		}	*/	
		var customhidewhen = obj.attr("customhidewhen");
		var retformula = '';
		if ( jQuery.trim(customhidewhen) != "" )
		{
			retformula = hidewhenpardef;
			if ( formula != "" ) {
				retformula += "{% set hwen = "+ formula +' or hwen ? true : false %}\r\n';
			}
		}
		else if (formula != '') {
			retformula = '{% set hwen = '+ formula +'%}\r\n';
		}
		return retformula;
/*
		if ( jQuery.trim(formula) != "" ){
			hidewhenpardef += safe_tags('{{ ' + formula + '? "none": "" }}');
			hidewhenpardef += safe_tags('">')
		}	
*/
	}
	
	return hidewhenpardef;
}

function handleHideWhenDXL(obj, mode, fordisplaynone){

	if(fordisplaynone == "undefined" || fordisplaynone == null ){
		fordisplaynone = false;
	}
	if(typeof obj.attr != "function"){
		obj = jQuery(obj);
	}
	
	var hidewhenpardef = "";
	
	var formula = "";
	
	var hideWhen = obj.attr("hidewhen")
	if ( hideWhen && hideWhen != "" ){
		var customhidewhen = obj.attr("customhidewhen")
		if ( jQuery.trim(customhidewhen) != "" )
		{
			//if ( ! customhidewhen.startsWith("{")  )
			//		customhidewhen = '{{ ' + customhidewhen + '}}';

			hidewhenpardef = updateTWIG (customhidewhen, "set", "bool");
			hidewhenpardef += '{% set hwen = __dexpreres %}\r'
		}
		

		if ( mode == "read"){
			if ( hideWhen.indexOf("R") != -1 )
				formula = "true";
			else
				formula = "false";
		}else{
			if ( hideWhen.indexOf("E") != -1 )
				formula = "true";
			else
				formula = "false";
		}

		
		var customhidewhen = obj.attr("customhidewhen")
		if ( jQuery.trim(customhidewhen) != "" ){
			if ( formula != "" )
				formula += " or " + 'hwen'
			else
				formula =  'hwen';
	}

		if ( jQuery.trim(formula) != "" ){
			if ( fordisplaynone )
				hidewhenpardef += safe_tags('{% if  ( ' + formula + ') %}');
			else
				hidewhenpardef += safe_tags('{% if  not ( ' + formula + ') %}');
				
				
		}
	}
	return hidewhenpardef;
}

function handleHideWhenPassThrough(obj){
	if(typeof obj.attr != "function"){
		obj = jQuery(obj);
	}

	var prevpardef = lastpardefused;
	
	var dxl = "";
	var hidewhenpardef = "";
	
	var pardefid = 1;
	
	var hideWhen = obj[0].hasAttribute("hidewhen") ? obj.attr("hidewhen") : "";
	var customHideWhen = obj[0].hasAttribute("customhidewhen") ? obj.attr("customhidewhen") : "";	
	if ((hideWhen && hideWhen != "") || (customHideWhen && customHideWhen != "")){
		pardefcount++;
		pardefid = pardefcount;
		
		hidewhenpardef = '<pardef id="' + pardefid.toString() + '">';	
		var hformula = "";
		if((hideWhen.indexOf("R") != -1) && (hideWhen.indexOf("E") != -1)){
			hformula = "@True";
		}else{
			if (hideWhen.indexOf("R") != -1){		
				hformula = "!@IsDocBeingEdited";
			}else if(hideWhen.indexOf("E") != -1){
				hformula = "@IsDocBeingEdited";
			}
			
			if ( jQuery.trim(customHideWhen) != "" ){
				hformula += (hformula != "" ? " | " : "") + "@Do(" + customHideWhen + ")";
			}			
		}		

		if(jQuery.trim(hformula) != ""){
			//-- need to reverse the hide when since we want to show the display:none; 						
			hformula = "!(" + hformula + ")";
		
			hidewhenpardef += "<code event='hidewhen'><formula>" + safe_tags(hformula) + "</formula></code>";		
			hidewhenpardef += "</pardef>";
		
			dxl += closePar();		
			dxl += hidewhenpardef;
			dxl += openPar(pardefid);		
			dxl += "display:none;";
			dxl += closePar();
			dxl += openPar(prevpardef);
		}
	}

	return dxl
}

function getHideWhenProperties(){
	var showCustomHideWhen = false;
	var hidewhenvals = $(currElem).attr("hidewhen");
	if(hidewhenvals == null || hidewhenvals == "undefined"){
		hidewhenvals = "";
		$("#spanCustomHideWhen").css("display", "none");
	}
	showCustomHideWhen = (hidewhenvals.indexOf("C") != -1) ? true : false;
	Docova.Utils.setField({
		field: "HideWhen",
		value: $(currElem).attr("hidewhen"),
		separator: ";"
	})
	$("#CustomHideWhen").val($(currElem).attr("customhidewhen"));
	if(showCustomHideWhen){
		$("#spanCustomHideWhen").css("display", "");
		$('span[target=CustomHideWhen]').html($(currElem).attr("customhidewhen"));
	}else{
		$("#spanCustomHideWhen").css("display", "none");
		$('span[target=CustomHideWhen]').html('');
	}
}

function setHideWhenProperties(){
	$(currElem).attr("hidewhen", Docova.Utils.getField("HideWhen"))
	$(currElem).attr("customhidewhen", $("#CustomHideWhen").val());
}

function selectIcon(fieldid, skippropertyupdate, cb){
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgJQIconPicker?OpenForm"
	var dlgSelectIcon = window.top.Docova.Utils.createDialog({
		id: "divDlgSelectIcon",
		url: dlgUrl,
		title: "Select an Icon",
		height: 570,
		width: 500,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Select": function (){
				var dlgDoc = window.top.$("#divDlgSelectIconIFrame")[0].contentWindow.document
				
				var selectedIcon = $("#SelectedIcon", dlgDoc).val();
				
				if(selectedIcon == ""){
					window.top.Docova.Utils.messageBox({
						title: "Nothing Selected",
						prompt: "You have not selected an Icons from the list. <br><br> Please select an Icon or Cancel.",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}
				
				$("#" + fieldid).val(selectedIcon)
				$("#" + fieldid).click();
				if ( ! skippropertyupdate )
				{
					setProperties();
				}

				if ( cb && typeof cb == "function"){
					cb.call();
				}
				dlgSelectIcon.closeDialog();
			},
			"Cancel": function(){
				dlgSelectIcon.closeDialog();
			}
		}
	})
}


function getRandomID(){
	var rID = parseInt((Math.random() * 1000), 10)
	return rID
}


function deleteTab(){
	var ans = confirm("You are about to delete the current Tab.  Are you sure?")
	if(ans == true){
		var currTab = $(currElem).find(".ui-tabs-active")
		var tabid = $(currElem).find(".ui-tabs-active a").attr("href")
		var currTabDiv = $(tabid)	
		//remove the elements
		currTab.remove();
		currTabDiv.remove();
		$(currElem).tabs("refresh");
	}
}


function addTab(){
	//Adds a new tab to the currently selected tabset
	var tabs = $(currElem).tabs();
	var ul = tabs.find("ul")
	var rID = getRandomID();

	$( "<li><a href='#" + rID + "'>New Tab</a></li>" ).appendTo( ul )
	$( "<div id='" + rID + "' contenteditable=false></div>" ).appendTo( tabs );
	$("#"+rID).html(getSubTableHTML());
	tabs.tabs("refresh")
	//Reset the rulers and droppable of newly generated subtable
	resetRulers();
	setSelectable();
}

function rgb2hex(rgb) {
	try{
	//if undefined null or transparent, return white
	if(rgb == "undefined" || rgb == null || rgb == "transparent"){
		return "#ffffff"
	}
	//if color is already returned as hex val, just return it	
	if(rgb.indexOf("#") >= 0) return rgb;
	//if color is rgb, this converts and returns hex value
	rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
	function hex(x) {
		return ("0" + parseInt(x).toString(16)).slice(-2);
	}
	return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
	}catch(e){}
}

function getAgentList(targetfield)
{
	//Gets the list of agents from the application into a select field 
	var colmn = 1;

	var luAppPath = "";
	
	//handle chart
	if ( $(currElem).attr("elem") == "chart" ){
		luAppPath = $("#chartLUApplication").val();
		targetfield = "selectChartSource"
	}
	
	if(luAppPath == ""){
		luAppPath = docInfo.AppID;
	}
	
	var optionlist = Docova.Utils.dbColumn({
		servername : '',
		nsfname : docInfo.NsfName,
		viewname : "luApplication",
		urlsuffix : ['Agents'],
		viewiscategorized : true,
		key : luAppPath,
		column : colmn,
		secure: docInfo.SSLState
	});
	
	if(optionlist  == "404"){
		$("#"+targetfield).empty();
		return;
	}
	
	var elementList = $("#" + targetfield);
	elementList.empty();
	var optionlistarray = optionlist.split(";");
	elementList.append($("<option></option>").attr("value", "").text("- Select -"));
	for(x=0;x<optionlistarray.length;x++){
		elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
	}	
	
	return;
}

function getViewList(targetfield){
	//Gets the list of views from the application into a select field (used by select, checkbox and radio elems)
	var colmn = 1;
	var suffix = 'Views';
	var delim = "";
	var luAppPath = "";
	
	//handle view list for checkboxes
	if($(currElem).attr("elem") == "chbox"){	
		luAppPath = $("#cbLUApplication").val();
		targetfield = "cbLUView";
	}
	//handle view list for radio buttons
	if($(currElem).attr("elem") == "rdbutton"){	
		luAppPath = $("#rbLUApplication").val();
		targetfield = "rbLUView";
	}
	//handle view list for selection/dropdown fields
	if($(currElem).attr("elem") == "select"){	
		luAppPath = $("#selectLUApplication").val();
		targetfield = "selectLUView";
	}	
	
	//handle embeddedview
	if ( $(currElem).attr("elem") == "embeddedview" ){
		luAppPath = $("#embLUApplication").val();
		targetfield = "selectEmbView"
		colmn = 8;
		delim = "|";
	}
	
	//handle picklist button
	if ( $(currElem).attr("elem") == "picklist" ){
		luAppPath = $("#picklistLUApplication").val();
		targetfield = "selectPicklistView"
		colmn = 8;
		delim = "|";
	}
	
	//handle chart
	if ( $(currElem).attr("elem") == "chart" ){
		luAppPath = $("#chartLUApplication").val();
		targetfield = "selectChartSource"
		colmn = 8;
		delim = "|";
	}
	
	//handle counter box
	if ($(currElem).attr('elem') == 'counterbox'){
		luAppPath = $('#ctrbLUApplication').val();
		targetfield = 'selectCtrbView'
		colmn = 8;
		delim = "|";		
	}
	
	//handle appelement
	if ($(currElem).attr('elem') == 'appelement'){
		switch($('#appelementSource').val())
		{
			case 'F':
				suffix = 'Forms';
			break;
			case 'P':
				suffix = 'Pages';
			break;
			case 'V':
				suffix = 'Views';
			break;
		}

		luAppPath = $("#appelementLUApplication").val();
		targetfield = "selectAppElementSource"
	}
	
	if(luAppPath == ""){
		luAppPath = docInfo.AppID;
	}
	
	var optionlist = Docova.Utils.dbColumn({
		servername : '',
		nsfname : docInfo.NsfName,
		viewname : "luApplication",
		urlsuffix : [suffix],
		viewiscategorized : true,
		key : luAppPath,
		column : colmn,
		secure: docInfo.SSLState
	});
	
	if(optionlist  == "404"){
		$("#"+targetfield).empty()
		return;
	}
	
	var elementList = $("#" + targetfield);
	elementList.empty();
	var optionlistarray = optionlist.split(";");
	elementList.append($("<option></option>").attr("value", "").text("- Select -"));
	for(x=0;x<optionlistarray.length;x++){
		if ( delim != "")
		{
			var txtarry = optionlistarray[x].split(delim);
			var opttxt = "";
			var optval = ""; 
			if ( txtarry.length > 1 )
				optval = txtarry[1];
			opttxt = txtarry[0];
			if ( optval == "" ) 
				optval = opttxt;
				
			elementList.append($("<option></option>").attr("value", optval).text(opttxt));
		}else{

			elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
		}
	}	
	
	return;
}

function getViewColumnList(targetfield, appID, viewname)
{
	var request = "<Request>";
	request += "<Action>GETVIEWCOLUMNS</Action>";
	request += "<AppID>" + appID + "</AppID>";
	request += "<ViewName><![CDATA[" + viewname + "]]></ViewName>";
	request += "</Request>";

	var serviceurl = docInfo.PortalWebPath;
	serviceurl += (serviceurl.substr(-1) == "/" ? "" : "/") + 'ApplicationServices?OpenAgent';

	$.ajax({
		type: 'POST',
		url: serviceurl,
		data: encodeURI(request),
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			if (xml) {
				var xmlobj = $(xml);
				var statustext = xmlobj.find("Result").first().text();
				if (statustext == "OK") {
					var elementList = $("#" + targetfield);
					elementList.empty();
					elementList.append($('<option></option>').attr('value', '').text('- Select -'));
					var resultxmlobj = xmlobj.find('Result[ID=Ret1]');
					$(resultxmlobj).find('Column').each(function () {
						elementList.append($('<option></option>').attr('value', $(this).find('Title').text()).text($(this).find('Title').text()));						
					});
				}
			}
		},
		error: function () {}
	});
}

function getDataTableForms(targetfield, excludeform)
{
	
	$.ajax({
		type: 'GET',
		url: 'luApplication/Forms?AppID=' + docInfo.AppID ,
		async: false,
		dataType: "xml",		
		success: function(response) {
			if (response) {
				var optionlistarray = [];
				$(response).find("viewentry").each ( function ()
				{
					var unid = $(this).attr("unid");
					var fname = $(this).find("entrydata[columnnumber='5']").text();
					optionlistarray.push( fname + "~" + unid );
				});

				var elementList = $("#" + targetfield);
				elementList.empty();
				
				elementList.append($("<option></option>").attr("value", "").text("- Select -"));
				for(x=0;x<optionlistarray.length;x++)
				{
					var txtarry = optionlistarray[x].split("~");
					var opttxt = "";
					var optval = ""; 
					if ( txtarry.length > 1 )
						optval = txtarry[1];
					opttxt = txtarry[0];
					if ( optval == "" ) 
						optval = opttxt;
					if ( excludeform && opttxt != excludeform)
					{	
						elementList.append($("<option></option>").attr("value", optval).text(opttxt));
					}
				}	
				
				return;
			}
		},
		error: function() {
			console.log('Failed to load datatable forms');
			
		}
	});
}

function getAppForms(targetfield) {
	//Gets the list of views from the application into a select field (used by select, checkbox and radio elems)
	var colmn = 7;
	var suffix = 'Forms';
	var delim = "";
	var luAppPath = "";
	
	//handle picklist button
	if ( $(currElem).attr("elem") == "picklist" ){
		luAppPath = $("#picklistLUApplication").val();
		delim = "|";
	}

	if(luAppPath == ''){
		luAppPath = docInfo.AppID;
	}
	
	var optionlist = Docova.Utils.dbColumn({
		servername : '',
		nsfname : docInfo.NsfName,
		viewname : 'luApplication',
		urlsuffix : [suffix],
		viewiscategorized : true,
		key : luAppPath,
		column : colmn,
		secure: docInfo.SSLState
	});
	
	if(optionlist  == "404"){
		$("#"+targetfield).empty();
		return;
	}
	
	var elementList = $("#" + targetfield);
	elementList.empty();
	var optionlistarray = optionlist.split(";");
	elementList.append($("<option></option>").attr("value", "").text("- Select -"));
	for(x=0;x<optionlistarray.length;x++)
	{
		if ( delim != "")
		{
			var txtarry = optionlistarray[x].split(delim);
			var opttxt = "";
			var optval = ""; 
			if ( txtarry.length > 1 )
				optval = txtarry[1];
			opttxt = txtarry[0];
			if ( optval == "" ) 
				optval = opttxt;
				
			elementList.append($("<option></option>").attr("value", optval).text(opttxt));
		}else{

			elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
		}
	}	
	
	return;
}

function getViewColumns(targetfield){
	//Gets the list of view columns in the selected view
	var luAppPath = "";
	var sourceView = ""; 
	
	//handle chart
	if ( $(currElem).attr("elem") == "chart" ){
		luAppPath = $("#chartLUApplication").val();
		sourceView = $("#selectChartSource").val();
		targetfield = "chart_sourceitems";
	}	
	
	if(luAppPath == ""){
		luAppPath = docInfo.AppID;
	}	
	
	if(sourceView == ""){
		return;
	}
	
	var appobj = Docova.getApplication({
		"appid": luAppPath
	});
	
	var viewobj = appobj.getView(sourceView);
	var viewcolumns = viewobj.columns;
	
	if(viewcolumns === null || !Array.isArray(viewcolumns)){
		return;
	}
	
	//handle chart
	if ( $(currElem).attr("elem") == "chart" ){
		var fieldlisthtml = "";
		for(var i=0; i<viewcolumns.length; i++){
			fieldlisthtml += '<span id="ci_' + viewcolumns[i].ItemName + '" class="chart_drag_item chart_source_item" sourcefield="' + viewcolumns[i].ItemName + '" sourcefieldtitle="' + viewcolumns[i].Title + '" draggable="true" ondragstart="chartitemdragged(event)">' + viewcolumns[i].Title + '</span>';			
		}
		jQuery("#"+targetfield).html(fieldlisthtml);
	}
}	
	
function getViewType(selectedOptionText) {	//Calendar/Plug-in support
	var nsfPath = docInfo.AppPath + "/" + docInfo.AppFileName
	if($("#embLUApplication").val()==""){
		var luAppPath = nsfPath;
	}else{
		var luAppPath = docInfo.AppPath + "/" + $("#embLUApplication").val();
	}

	$.ajax({
		type: 'GET',
		url: 'luApplication/Views?AppID=' + ($("#embLUApplication").val() ? $("#embLUApplication").val() : docInfo.AppID) + '&StartKey=' + encodeURIComponent(selectedOptionText),
		async: false,
		dataType: "xml",		
		success: function(response) {
			if (response) {
				$("#ViewType").val($(response).find('entrydata[name=ViewType]').first().text());
			}
			else {
				$("#ViewType").val('Standard');
			}
		},
		error: function() {
			console.log('Failed to load view type');
			$("#ViewType").val('Standard');
		}
	});
}

function getElementTypeList(targetField, element) {
	var optionlist = '';
	var elemName = '';
	var query = '#layout [elem='+ element +']';
	var elarray = element.split(":");
	if ( elarray.length > 1 )
	{
		query = "";
		for ( var l = 0; l < elarray.length; l ++){
			query += query == "" ? "#layout [elem='" + elarray[l] + "'] " : ", #layout [elem='" + elarray[l] + "'] "
		}
	}

	if ($(query).length)
	{
		$(query).each(function(){
			optionlist += (optionlist=="" ? $(this).prop("id") : ";" + $(this).prop("id"));			
		});
		
		var elementList = targetField instanceof jQuery ? targetField : $("#" + targetField);
		elementList.empty();
		var optionlistarray = optionlist.split(";");
		elementList.append($("<option></option>").attr("value", "- Select -").text("- Select -"));
		for(x=0;x<optionlistarray.length;x++){
			elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
		}
	}
	return;
}

function getFieldList(targetfield){
		var nsfPath = docInfo.AppPath + "/" + docInfo.AppFileName
		var optionlist = "";
		var elemName = ""
		$("[elemtype=field]").each(function(){
			switch ($(this).attr("elem")) {
				case 'label':
					//do nothing
				break;				
				case 'text':
					optionlist += (optionlist=="" ? $(this).prop("id") : ";" + $(this).prop("id")) 
				break;				
				case 'chbox':
				case 'toggle':
					elemName = $(this).prop("name");
					optionlist += (optionlist=="" ? elemName : ";" + elemName) 
				break;
				case 'rdbutton':
					elemName = $(this).prop("name");
					optionlist += (optionlist=="" ? elemName : ";" + elemName) 
				break;
				case 'select':
					optionlist += (optionlist=="" ? $(this).prop("id") : ";" + $(this).prop("id")) 
				break;
				case 'tarea':
					//do nothing
				break;
				case 'date':
					optionlist += (optionlist=="" ? $(this).prop("id") : ";" + $(this).prop("id")) 
				break;
				case 'names':
					//do nothing
				break;
				case 'table':
					//do nothing
				break;
				case 'chart':
					//do nothing
				break;				
				case 'htmlcode':
					//do nothing
				break;
				case 'button':
					//do nothing
				break;
				case 'image':
					//do nothing
				break;
			}		
		})

		var elementList = targetfield instanceof jQuery ? targetfield : $("#" + targetfield);
		elementList.empty();
		var optionlistarray = optionlist.split(";");
		elementList.append($("<option></option>").attr("value", "- Select -").text("- Select -"));
		for(x=0;x<optionlistarray.length;x++){
			elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
		}	
	return;
}

function getSubformList(targetfield){
	//Gets the list of subforms from the application into a select field (used by insert subform)
	var colmn = 1;
	var delim = "";
	var luAppPath = "";	
	if(luAppPath == ""){
		luAppPath = docInfo.AppID;
	}
	
	var optionlist = Docova.Utils.dbColumn({
		servername : '',
		nsfname : docInfo.NsfName,
		viewname : "luApplication",
		urlsuffix : ['Subforms'],
		viewiscategorized : true,
		key : luAppPath,
		column : colmn,
		secure: docInfo.SSLState
	});
	
	if(optionlist  == "404"){
		$("#"+targetfield).empty()
		return;
	}
	
	var elementList = $("#" + targetfield);
	elementList.empty();
	var optionlistarray = optionlist.split(";");
	elementList.append($("<option></option>").attr("value", "").text("- Select -"));
	for(x=0;x<optionlistarray.length;x++){
		if ( delim != "")
		{
			var txtarry = optionlistarray[x].split(delim);
			var opttxt = "";
			var optval = ""; 
			if ( txtarry.length > 1 )
				optval = txtarry[1];
			opttxt = txtarry[0];
			if ( optval == "" ) 
				optval = opttxt;
				
			elementList.append($("<option></option>").attr("value", optval).text(opttxt));
		}else{
			elementList.append($("<option></option>").attr("value", optionlistarray[x]).text(optionlistarray[x]));
		}
	}	
	
	return;
}
	
function resetRulers(){
	//Sets the th resizable functionality on tables
	var resizehandlehtml = '<i class="fas fa-caret-down easthandle" style="margin-right:-4px;float:right;"></i>'
	$("th .easthandle").remove();  //remove all resize handles from th's
	$("table tr th:not(:last-child)").append(resizehandlehtml) //add back resize handles to all th's
	$(".datatable table thead tr th").each ( function (){
		$(this).attr("contenteditable", "true");
	})

	$("table tr th:not(:last-child)").resizable(
		{ 
			handles: "e", 
			start: function () { hideSelectors() }, 
			stop:  function () { setSelectors() }
		}
	) //make all th's resizable
	return;
}	

function findValidElemIndex(type, name_prefix){
	var x=1;
	var elem_exists = true;
	while(elem_exists == true){
		if(type == "chbox" || type == "rdbutton"){
			if($("[name=" + name_prefix + x +"]").length){
				elem_exists = true;
				x++;
			}else{
				elem_exists = false;
			}		
		}else{
			if($("#" + name_prefix + x).length){
				elem_exists = true;
				x++;
			}else{
				elem_exists = false;
			}
		}
	}
	return x;
}

function getNewElemId(currId, targetarea){
	
	var targetcontext = (typeof targetarea !== "undefined" && targetarea !== null ? targetarea : document);
	
	var x=1;
	var newElemId = "";
	var elem_exists = true;
	while(elem_exists == true){
		if($("#" + currId + "_" + x, targetcontext).length){
			elem_exists = true;
			x++;
		}else{
			elem_exists = false;
			newElemId = currId + "_" + x;
		}
	}
	return newElemId;
}

function getMaxColumns(cell)
{
	var colCount = 0;
	cell.parent().children().each(function () {
        if ($(this).prop('colspan') > 1) {
            colCount += $(this).prop('colspan');
        } else {
            colCount++;
        }
    });
    return colCount;
}

function delete_section(element){
	if($(element).attr("elem") == "fields"){
		var sectionnum = $("[elem='fields'][elemtype='section']").length;
		if(sectionnum == 1){
			window.top.Docova.Utils.messageBox({
				width: 400,
				title: "Error deleting a section",
				prompt: "Sorry, your form must have at least one fields section.",
				icontype: 1,
				msgboxtype: 0
			});
			return false;
		}	
	}

	var res = confirm('Do you really want to delete this section?');
	if (res) {
		pushUndo();
		$(element).remove();
		$(".selector").hide(); //hide selectors
		//---reset sortable on sections
		$("#layout").sortable();
	}
}

function delete_field(element, cutaction){
	//if is from a cut field action, then no Delete element prompt 
	if(cutaction){
		var res = true;
	}else{
		var res = confirm('Delete element?');
	}
	if (res) {
		pushUndo();
		var cElem = $(currElem).attr("elem")
		var prevElem = $(currElem).prev();
		var nextElem = $(currElem).next();
		if(cElem == "outline" || cElem == "tabset"  || cElem == "attachments" || cElem == "embeddedview" || cElem == "subform" || cElem == "htmlcode" || cElem == "singleuploader"){
			//if an outline/tabs/attachs/embview/htmlcode or subform is being delete and there is nothing in
			//front or behind it, then add a p
			if(prevElem.length == 0 && nextElem.length == 0){
				var parHTML = getParHTML();
				$(parHTML).insertBefore( currElem );
				$(currElem).remove(); //then remove element
				var newPar = $("p[elem=newpar]") ;
				$(newPar).attr("elem", "par");
				setParFocus(newPar);
				currElem = newPar;
				setSelectable();
				setSelected();
			}else if(prevElem.length==1){
				var pElem = $(prevElem).prop("tagName");
				if(pElem == "P"){ //if prevElem is a P par then get focus to it
					$(currElem).remove(); //delete element
					setParFocus(prevElem); //get focus on the P
					currElem = prevElem;
					setSelectable();
					setSelected();
				}else{
					$(currElem).remove();
					currElem = prevElem;
					setSelectable();
					setSelected();
				}
			}else{ //if(nextElem.length==1){
				var nElem = $(nextElem).prop("tagName");
				if(nElem == "P"){ //if nextElem is a P par then get focus to it
					$(currElem).remove(); //delete element
					setParFocus(nextElem); //get focus on the P
					currElem = nextElem;
					setSelectable();
					setSelected();
				}else{
					$(currElem).remove();
					currElem = nextElem;
					setSelectable();
					setSelected();
				}			
			}			
		}else{ //element being deleted is not outline,attachments,tabset,embview,subform
			var pElem = $(currElem).parent();
			if($(pElem).prop("tagName") == "P"){//if element's parent is a P par then set focus on it when element is deleted
				$(currElem).remove();
				setParFocus(pElem);
				currElem = pElem;
				setSelectable();
				setSelected();
			}else{
				$(currElem).remove();
				hideSelectors();
			}
		}
	}
	return;
}

function copy_field(element){
	//if an element was passed, this was a menu copy else it was a ctrl+c
	if(element){
		clipBoard = null; //in case previous copy was a selection to clipBoard
		objClip = element;
		fieldaction = "copyelement";
	}else{
		//if ctrl+c was used, an element still may be selected
		//get elem that has selected class
		if($(".selected").attr("elemtype") == "field"){
			fieldactionelem = $(currElem).attr("elem"); //if an element, track what it is for paste
			objClip = currElem;
			fieldaction = "copyelement";
		}else{
			objClip = null; //in case previous copy was of element
			fieldaction = "copyselection"
			if(clipBoard){ //remove any existing clipBoard first
				$(clipBoard).remove();
			}
			clipBoard = document.createElement("div");
			$(clipBoard).prop("id", "clipBoard")
			var sel = window.getSelection();
			clipBoard.appendChild(sel.getRangeAt(0).cloneContents());			
		}
	}
}

function cut_field(element){
	if(element){
		clipBoard = null //in case previous copy was a selection to clipBoard
		objClip = element;
		fieldaction = "cutelement";
		hideSelectors();//hide before deleting
		delete_field(element, true);
	}else{
		//if ctrl+x was used, an element still may be selected
		//get elem that has selected class
		if($(".selected").attr("elemtype") == "field"){
			fieldactionelem = $(currElem).attr("elem"); //if an element, track what it is for paste
			objClip = currElem;
			fieldaction = "cutelement";
			hideSelectors();//hide before deleting
			delete_field(currElem, true);
		}else{
			objClip = null; //in case previous copy was of element
			fieldaction = "cutselection"
			if(clipBoard){ //remove any existing clipBoard first
				$(clipBoard).remove();
			}
			clipBoard = document.createElement("div");
			$(clipBoard).prop("id", "clipBoard")
			var sel = window.getSelection();
			clipBoard.appendChild(sel.getRangeAt(0).cloneContents());
			sel.getRangeAt(0).deleteContents();		
		}		
	}

}

function paste_field(position)
{
	var parentlayout = $(currElem).closest("#layout,#layout_m");

	//--check if we are pasting to the tool bar	
	var istoolbar = ($(currElem).parent().prop("id") == "divToolBar" || $(currElem).prop("id") == "divToolBar_m");
	
	if ( objClip ) { //if pasting an element	

		var clone = $(objClip).clone();
		var type = $(clone).attr("elem");
		//-- if pasting to the toolbar we can only paste buttons
		if(istoolbar && type !== "button"){
			return false;
		}

		pushUndo();	
		var currId = $(clone).prop("id")
		var nextId = currId;
		while($("#" + nextId, parentlayout).length != 0){ //if field id exists
			nextId = getNewElemId(nextId, parentlayout);
			$(clone).prop("id", nextId );
			if((clone).attr("elem") != "ctext"){ //change placeholder text if not computed text
				$(clone).prop("placeholder", nextId );
			}
		}
		if(fieldactionelem == "attachments" || fieldactionelem == "subform" || fieldactionelem == "embeddedview" || fieldactionelem == "outline"){ //if element is a tabset, then some extra actions are needed
			$(clone[0].outerHTML).insertAfter(currElem);
		}else if(type == "tabset"){//special case for tabset because it could contain many field elements
			//handle it like a cut selection by moving clone html into clipBoard
			if(clipBoard){ //remove any existing clipBoard first
				$(clipBoard).remove();
			}
			clipBoard = document.createElement("div");
			$(clipBoard).prop("id", "clipBoard")
			$(clipBoard).html(clone[0].outerHTML)
			$(clipBoard).find("[elemtype='field']").each(function(){
				var cId = $(this).prop("id");
				var nId = cId;
				while($("#" + nId, parentlayout).length != 0){ //if field id exists
					nId = getNewElemId(nId, parentlayout);
					$(this).prop("id", nId );
					$(this).prop("placeholder", nId );
				}
			})
			$(clipBoard.innerHTML).insertAfter(currElem);
			$("#" + nextId).tabs();//
			resetRulers();
		}else if(typeof position !== "undefined" && (position == "before" || position == "after")){
			if(position == "after"){
				$(clone[0].outerHTML).insertAfter(currElem);				
			}else{
				$(clone[0].outerHTML).insertBefore(currElem);
			}
		}else{
				insertElement(clone[0].outerHTML);
		}
		setSelectable();
	}else{ //else pasting a selection
		//-- if pasting to the toolbar we can only paste buttons
		if(istoolbar){
			return false;
		}
		pushUndo();
		$(clipBoard).children().each(function(){
			var currId = $(this).prop("id");
			var nextId = currId;
			while($("#" + nextId, parentlayout).length != 0){ //if field id exists
				nextId = getNewElemId(nextId, parentlayout);
				$(this).prop("id", nextId );
				$(this).prop("placeholder", nextId );
			}
		})
		insertElement(clipBoard.innerHTML);
		setSelectable();
	}
}


function insert_row(element){
	pushUndo();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();
    var columns = '';
    var colCount = getMaxColumns(element);

	hideSelectors();

    for (var x = 0; x < colCount; x++) {
	    columns += '<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>';
	}

	element.parent().before($('<tr></tr>').append(columns));
	
	setSelectable();

}

function append_row(element)
{
	pushUndo();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();
    var columns = '';
    var colCount = getMaxColumns(element);
    for (var x = 0; x < colCount; x++) {
	    columns += '<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>';
	}

	element.parentsUntil('table').children('tr').last().after($('<tr></tr>').append($(columns)));
	resetRulers();
	setSelectable();
}

function delete_row(element)
{
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();
	if(element.parent().siblings().length == 0){ //if only one row, delete table
		var res = confirm('Delete table?');
		if (res) 
		{
			pushUndo();
			hideSelectors();
			//if table is in tabset, prevent deleting
			if(element.closest("[elem='table']").parent().attr("role") == "tabpanel"){
				window.top.Docova.Utils.messageBox({
					width: 400,
					title: "Error deleting table.",
					prompt: "Tabsets must have a table on each tab.",
					icontype: 1,
					msgboxtype: 0
				});
				return;
			}else{
				element.closest("[elem='table']").remove();
			}
		}
	}else{ //more than one row
		var res = confirm('Delete this row?');
		if (res) 
		{
			pushUndo();
			hideSelectors();
			element.parent().remove();
		}
	}
}

function insert_column(element)
{
	//element should always be a P initially, want to set to Ps parent TD
	pushUndo();
	element = element.parent();
	var position = element.parent().children().index(element);
	position = (element.prop('colspan') > 1) ? position + element.prop('colspan') : position;

	var isdataislandtable= element.parents(".datatabletbody").length > 0 ? true : false;
	var sub = 1;
	if ( isdataislandtable ){
		sub = 2;
	}

	element.parent().parent().find('tr').each(function() {
		var index = 0;
		$(this).find('td').each(function() {
			if ($(this).prop('colspan') > 1) {
				index += $(this).prop('colspan');
			}
			else {
				index++;
			}
			if ((index - sub) == position)
			{
				$(this).before($('<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>'));
			}
		});
	});

	setSelectable();
	setSelectors();
}

function append_column(element)
{
	pushUndo();
	hideSelectors();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();	
	var isdataislandtable= element.parents(".datatabletbody").length > 0 ? true : false;
	$(element).parentsUntil("table").parent().children("tr").each(function()
	{
		if ( isdataislandtable ){
			$('<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>').insertBefore($(this).find("td:last"));
		}else{
			$(this).append($('<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>'));
		}
	});

	//append corresponding th
	
	if (isdataislandtable){
		$('<th class="selectable" contenteditable="true"	><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></th>').insertBefore($(element).closest("table").children("thead").find("th:last"));
		$(element).closest("table").children("tfoot").find("td:last-child").after($('<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>'))
		
	}else{
		$(element).closest("table").children("thead").find("th:last-child").after($("<th></th>"))
	}

	resetRulers();
	setSelectable();
	setSelectors();
}

function delete_column(element)
{
	hideSelectors();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();
	//if the column cell is colspanned, then you can't delete it, must be split
	var colspanval = $(element).attr("colspan");
	if(colspanval > 1){
		alert("Sorry, you can't delete a cell that spans multiple columns.  Please split it first.");
		return;
	}
	
	//if the column is not colspanned but is the only column in the row, then no delete
	if (element.siblings().length < 1)
	{
		alert("Sorry, you can't delete the only column in a row.");
		return;	
	}

	//if any columns are colspanned they must be split before deleting a column
	var colspancnt = 0
	$(element).closest("table").children("tbody").children("tr").each(function(event, ui){
   		$(this).children("td").each(function(event, ui){
   			if($(this).attr("colspan") > 1){
   				colspancnt++
			}
   		})
	})
	if(colspancnt > 0){
		alert("Sorry, you can't delete columns if you have merged columns.<br>Please split columns and try again.");
		return;		
	}

	var res = confirm('Do you really want to delete this column with all its content?');
	if (res) {
		pushUndo();
		var td_indx = $(element).index();
		//first remove the th, then the corresponding td
		$(element).closest("table").children("thead").children("tr").each(function(event, ui){
			$(this).children("th").eq(td_indx).remove();
		})
		$(element).closest("table").children("tfoot").children("tr").each(function(event, ui){
				$(this).children("td").eq(td_indx).remove();
		})
		$(element).closest("table").children("tbody").children("tr").each(function(event, ui){
			$(this).children("td").eq(td_indx).remove();
		})
		resetRulers();
	}
	setSelectors();
}

function merge_right(element)
{
	pushUndo();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();	
	if (element.next() && element.next().prop('tagName').toLowerCase() == 'td') {
		if (element.next().children().length > 0 && element.children().length == 0) {
			element.next().prop('colspan', (element.prop('colspan') + 1));
			element.remove();
		}
		else {
			element.next().remove();
			element.prop('colspan', (element.prop('colspan') + 1));
		}
	}
	setSelectors();
}

function merge_cells(element)
{
	pushUndo();
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();	
	var colspan = 0;
	element.parent().children('td').each(function() {
		colspan += $(this).prop('colspan');
		if ($(this)[0] != element[0]) {
			$(this).remove();
		} 
	});
	
	element.prop('colspan', colspan);
	setSelectors();
}

function split_cell(element){
	//element should always be a P initially, want to set to Ps parent TD
	element = element.parent();	
	var colspanval = $(element).attr("colspan");
	if(colspanval == "undefined" || colspanval == null){
		return;
	}
	pushUndo();
	var td_num = $(element).attr("colspan") - 1;
	$(element).attr("colspan", "");
	for(x=1; x<=td_num; x++){
	   $(element).after('<td class="selectable" contenteditable="true"><p class="selectable" elem="par" elemtype="par" style="padding:5px;"></p></td>')
	}

	setSelectable();
	setSelectors();
}

function cell_properties(element)
{
	activeObj = element;
	$('#static_id').val($(activeObj).attr('id'));
	var stClasses = $(activeObj).attr('class') ? $(activeObj).attr('class').split(/\s+/) : [];
	for (var c = 0; c < stClasses.length; c++) {
		if (stClasses[c] == 'ui-resizable' || stClasses[c] == 'ui-droppable' || stClasses[c] == 'context-menu-active' || stClasses[c] == 'ui-droppable-disabled' || stClasses[c] == 'ui-state-disabled') {
			stClasses[c] = null;
		}
	}
	$('#static_class').val($.trim(stClasses.join(' ')));
	$('#static_style').val($(activeObj).attr('style'));
	$( "#static_properties" ).dialog("option", "title", "Cell Properties").dialog("open");
}

function table_properties(element)
{
	activeObj = element.parentsUntil('table').parent();
	$('#static_id').val($(activeObj).attr('id'));
	var stClasses = ($(activeObj).attr('class')) ? $(activeObj).attr('class').split(/\s+/) : [];
	for (var c = 0; c < stClasses.length; c++) {
		if (stClasses[c] == 'ui-resizable' || stClasses[c] == 'ui-droppable' || stClasses[c] == 'context-menu-active' || stClasses[c] == 'ui-droppable-disabled' || stClasses[c] == 'ui-state-disabled') {
			stClasses[c] = null;
		}
	}
	$('#static_class').val($.trim(stClasses.join(' ')));
	$('#static_style').val($(activeObj).attr('style'));
	$( "#static_properties" ).dialog("option", "title", "Cell Properties").dialog("open");

}

function addline_before(element){
	var parHTML = getParHTML();
	if($(element).attr("elemtype") == "field"){
		var elem = $(element).attr("elem");
		if(elem == "attachments" || elem=="subform" || elem=="embeddedview" || elem=="outline" || elem=="tabset" || elem=="htmlcode" || elem == "singleuploader" || elem == "icon" || elem == "counterbox" || elem == "weather" || elem == "barcode" || elem == "googlemap" || elem == "appelement" || elem == 'slider'){
			$(parHTML).insertBefore( element );
		}
	}else{
		var targetElem = $(element).closest("[elemtype='field']");
		var elem = $(targetElem).attr("elem");
		if(elem == "table"){
			//check to see if this is a table in a tabset
			if($(targetElem).parent().attr('role') == "tabpanel"){
				//re-get the target element to be the tabset
				targetElem = $(targetElem).parent().closest("[elem='tabset']")
				$(parHTML).insertBefore( targetElem );
			}else{
				$(parHTML).insertBefore( targetElem );
			}
		}
	}
	//newly inserted par has elem=newpar which needs to be changed to par
	var newPar = $("p[elem=newpar]") ;
	$(newPar).attr("elem", "par");
	
	setSelectable();
	setSelected();
}

function addline_after(element){
	var parHTML = getParHTML();
	if($(element).attr("elemtype") == "field"){
		var elem = $(element).attr("elem");
		if(elem == "attachments" || elem=="subform" || elem=="embeddedview" || elem=="outline" || elem=="tabset" || elem=="htmlcode" || elem == "singleuploader" || elem == "icon" || elem == "counterbox" || elem == "weather" || elem == "barcode" || elem == "googlemap" || elem == "appelement" || elem == 'slider'){
			$(parHTML).insertAfter( element );
		}
	}else{
		var targetElem = $(element).closest("[elemtype='field']");
		var elem = $(targetElem).attr("elem");
		if(elem == "table"){
			//check to see if this is a table in a tabset
			if($(targetElem).parent().attr('role') == "tabpanel"){
				//re-get the target element to be the tabset
				targetElem = $(targetElem).parent().closest("[elem='tabset']")
				$(parHTML).insertAfter( targetElem );
			}else{
				$(parHTML).insertAfter( targetElem );
			}
		}
	}
	//newly inserted par has elem=newpar which needs to be changed to par
	var newPar = $("p[elem=newpar]") ;
	$(newPar).attr("elem", "par");	
	setSelectable();
	setSelected();
}

function delete_element(element)
{
	var res = confirm('Do you really want to delete this element?');
	if (res) {
		pushUndo();
		if (element.find('input:radio').length || element.find('input:checkbox').length) {
			var parentTbl = element.parentsUntil('table').parent().parentsUntil('table').parent();
			element.parentsUntil('table').parent().remove();
		}
		else {
			element.children(':not(div)').each(function() {
				$(this).remove();
			});
		}
	}
}

function getObjectPassthrough(obj)
{
	var str = "";
	if ( !obj) return;
	 if ( obj.nodeType != 3 ) {
		str = "&lt;" + obj.tagName
		str += " " + handleAttributes(obj);
		str +=  "&gt;";
	}else{
		if ( obj.nodeValue !=  "")
			str += safe_tags(obj.nodeValue);
	}
	if (obj.hasChildNodes()) {
     	var child = obj.firstChild;
      	while (child) {
          	retval = getObjectPassthrough(child)
          	if ( retval != " " )
       	 		str +=retval;
       		child = child.nextSibling;
      	}
   }
   if ( obj.nodeType != 3  && obj.tagName.toUpperCase() != "BR" )
  		str += "&lt;/" + obj.tagName+ "&gt;";
   else{
  		//if ( obj.nodeValue != "")	
  			//str += "&quot;"
  	}
   
   return str;
}

function getObjectFontStyle(obj){
	obj = $(obj);
	
	var str = "";
	
	var styleitems = new Array("font", 'font-size', "font-weight", "font-style", "color");
	
	for(var i=0; i<styleitems.length; i++){
		var styleval = obj.css(styleitems[i]);
		if(typeof styleval != "undefined" && styleval !== ""){
			str += styleitems[i] + ": " + styleval + "; ";
		}
	}	
	
	return str;
}

function escapeJSEventCode(jscode){
	
	jscode = safe_quotes_js(jscode, false, true);
	jscode = safe_quotes_js(jscode, true);
	
	if(jscode != ""){
		jscode = jscode.split("\n");
		jscode = jscode.join("&amp;#10;");
	}
	return jscode;
}

function handleDateText(obj, mode)
{
	obj = $(obj);
	var dxl = '';
	var defaultvalue = obj.attr("fdefault");
	var datetype = obj.attr("datetype");
	var onclick = obj.attr("fonclick");
	var onchange = obj.attr("fonchange");
	var onfocus = obj.attr("fonfocus");
	var onblur = obj.attr("fonblur");
	var fieldtype = obj.attr("datetype");
	var htmlother = obj.attr("htmlother");
	var htmlclass = obj.attr("htmlclass");
	var displaytime = obj.attr("displaytime");
	var displayonlytime = obj.attr("displayonlytime");
	var required = obj.attr('isrequired');
	var placeholder = obj.attr('fplaceholder');
	
	var hidecode = handleHideWhenClientSide( obj, mode, "span");
	if (hidecode != "")
		dxl = hidecode;
	var mv = obj.attr("textmv");
	mv = ( typeof mv !== "undefined") ? mv : "";
	var mvsep = "";
	var dmsep = '';

	var ftype =  ((typeof displayonlytime !== "undefined" && displayonlytime == "true") ? "time" :(typeof displaytime !== "undefined" && displaytime == "true") ? "datetime" : "date");
	if(mv === "true"){
		mvsep = obj.attr("textmvsep");
		mvsep = (typeof mvsep !== "undefined") ? mvsep : "comma semicolon";
		dmsep = obj.attr('datemvdisep');
		dmsep = (typeof dmsep !== "undefined") ? dmsep : mvsep.split(" ")[0];
		var fldjson = "FieldInfo['" + obj.attr('id').toLowerCase() + "'] = { mvsep: '" + mvsep + "' }";
		fieldsarr.push(fldjson);
	}
	var objstyle = getObjectFontStyle(obj)
	if (mode == 'read')
	{
		
		//-- add text formatting 
		if(objstyle != ""){
			dxl += safe_tags("<span docovafield='1' style='"+ (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + objstyle + "' id='"+ obj.attr('id') +"'>");
		}else{
			dxl += safe_tags("<span docovafield='1' style='"+ (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') +"' id='"+ obj.attr('id') +"'>");
		}

		if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1)
			dxl += '&lt;span style="display:inline-block;"&gt;';

		if(fieldtype == "cfd"){
			//special case for cfd fields calculate again inline so that it renders	
			dxl += updateTWIG(defaultvalue, "raw", "string", obj.attr('id').toLowerCase() );			
			dxl += '{{ f_AddComputedToBuffer ("' + obj.attr('id').toLowerCase() + '", __dexpreresraw )}}\n'
		}		
		dxl += "{{ f_GetFieldString('" + obj.attr('id').toLowerCase() + "', '" + dmsep + "', '" + ftype + "')| raw}}";
/*
		if ( defaultvalue != "" && (fieldtype == "c" || fieldtype == "cfd"))
		{		
			defaultvalue = updateTWIG( defaultvalue, "set", "string", obj.attr("id"));
			dxl += defaultvalue + "\r";
			dxl += '{{ __dexpreres is empty ? "" : __dexpreres|dateformat("m/d/Y") }}\r';	
		}else
			dxl += "{{ document[0]['" + obj.attr('id').toLowerCase() + "'] is defined and document[0]['" + obj.attr('id').toLowerCase() + "'] ? document[0]['" + obj.attr('id').toLowerCase() + "']|nl2br : '' }}";
*/

		if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1) 
			dxl += '&lt;/span&gt;';

		dxl += '&lt;/span&gt;';
	}
	else if (mode == 'edit')
	{
		if (fieldtype == "c" || fieldtype == "cfd" || fieldtype == 'cwc') {
			dxl += '&lt;span ';
			dxl += 'id="SPAN' + obj.attr("id") + '" ';
			if(fieldtype == "cfd"){
				dxl += " cfdfieldname='" + obj.attr("id") + "' ";
			}			
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " mvdisep='" + dmsep + "' ";
			dxl += " docovafield = '1' ";
			dxl += ( typeof onclick !== typeof undefined ) ? 'onclick="' + escapeJSEventCode(onclick) + '" ' : '';
			dxl += ( typeof onchange !== typeof undefined ) ? 'onchange="' + escapeJSEventCode(onchange) + '" ' : '';
			dxl += ( typeof onfocus !== typeof undefined ) ? 'onfocus="' + escapeJSEventCode(onfocus) + '" ' : '';
			dxl += ( typeof onblur !== typeof undefined ) ? 'onblur="' + escapeJSEventCode(onblur) + '" ' : '';
			dxl += " elem=date ";
			dxl += (objstyle != "") ? 'style="'+ (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + objstyle + '" &gt;' : 'style="'+(hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '')+'"&gt;';
			if ( defaultvalue && jQuery.trim(defaultvalue) != "" )
			{
				if (fieldtype == 'cwc')
					saveComputedVal("default", defaultvalue, obj.attr('id').toLowerCase() );
				else
					saveComputedVal("computed", defaultvalue, obj.attr('id').toLowerCase() );
			}
			if (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1)
				dxl += '&lt;span style="display:inline-block;"&gt;';

			if(fieldtype == "cfd"){
				//special case for cfd fields calculate again inline so that it renders	
				dxl += updateTWIG(defaultvalue, "raw", "string", obj.attr('id').toLowerCase() );			
				dxl += '{{ f_AddComputedToBuffer ("' + obj.attr('id').toLowerCase() + '", __dexpreresraw )}}\n'
			}					
			dxl += "{{ f_GetFieldString('" + obj.attr('id').toLowerCase() + "', '" + dmsep + "', '" + ftype + "')| raw}}";
			
			if (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1)
				dxl += '&lt;/span&gt;';
			if (fieldtype == 'c' || fieldtype == 'cwc') {
				dxl += '&lt;input type ="hidden" ';
				dxl += 'name="' + obj.attr("id") + '" ';
				dxl += required ? 'isrequired="' + required + '" ' : '';
				dxl += " mvsep='" + mvsep + "' ";
				dxl += " elem=date ";
				dxl += " mvdisep='" + dmsep + "' ";
				dxl += 'value="';
				dxl += '{{ f_GetFieldString("' + obj.attr('id').toLowerCase() + '",  "' + dmsep + '", "' + ftype + '", true) }}';
				
				dxl += '" /&gt;';
			}
			dxl += '&lt;/span&gt;';
		}
		else {
			if (placeholder && jQuery.trim(placeholder) != '') {
				placeholder = updateTWIG(placeholder, 'output', 'string');
			}
			else {
				placeholder = '';
			}
		
			if ( defaultvalue && jQuery.trim(defaultvalue) != "" )
			{
				saveComputedVal("default", defaultvalue, obj.attr('id').toLowerCase() );
			}
			if(mv === "true" && (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1 )){
				dxl += '\n&lt;textarea ';
				dxl += ( typeof htmlclass !== typeof undefined ) ? 'class="'+ htmlclass + '" ' : ' ';
			}else{ 
				dxl += '\n&lt;input type="text" class="docovaDatepicker ';
				dxl += (( typeof htmlclass !== typeof undefined ) ?  htmlclass : '') + '" ';
			}
			dxl += 'id="' + obj.attr("id") + '" ';
			dxl += 'name="' + obj.attr("id") + '" ';
			dxl += 'placeholder="'+ placeholder + '" ';
			dxl += required ? 'isrequired="'+ required + '" ' : '';
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " docovafield = '1' ";
			dxl += " mvdisep='" + dmsep + "' ";
			dxl += " textrole='t' ";
			dxl += " elem=date ";
			dxl += ( typeof htmlother !== typeof undefined ) ? htmlother + ' ' : '';		
			dxl += ( typeof onclick !== typeof undefined ) ? 'onclick="' + escapeJSEventCode(onclick) + '" ' : '';
			dxl += ( typeof onchange !== typeof undefined ) ? 'onchange="' + escapeJSEventCode(onchange) + '" ' : '';
			dxl += ( typeof onfocus !== typeof undefined ) ? 'onfocus="' + escapeJSEventCode(onfocus) + '" ' : '';
			dxl += ( typeof onblur !== typeof undefined ) ? 'onblur="' + escapeJSEventCode(onblur) + '" ' : '';
			var inputtranslation = obj.attr('ftranslate');
			dxl += ( typeof inputtranslation !== typeof undefined ) ? ' ftranslate="' + escapeJSEventCode(inputtranslation) + '" ' : '';
			dxl += 'style="'+(hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '');
			dxl += (obj.attr('style')) ? obj.attr('style') + '" ' : '" ';
			
			if(ftype == "datetime"){
				dxl += 'displaytime="true" ';
			}else if(ftype == "time"){
				dxl += 'displayonlytime="true" ';
			}
			
			if (mv !== "true" ||  ( (mvsep.indexOf('newline') == -1 && mvsep.indexOf('blankline') == -1 )))
			{				
				dxl += 'value="{{ f_GetFieldString("' + obj.attr('id').toLowerCase() + '",  "' + dmsep + '", "' + ftype + '", true) }}"';
			}
			
			dxl += '&gt;';
			if(mv === "true" && (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1 )){
				dxl += '{{ f_GetFieldString("' +  obj.attr('id').toLowerCase() + '", "' + dmsep + '", "' + ftype + '", true) }}';
				dxl += ' &lt;/textarea&gt;';
			}
		}
	}
	if (mode == 'edit') {
		//if not data tables field
		var isdatatablefield = obj.parents(".datatabletbody").length > 0 ? true : false;
		if ( ! isdatatablefield)
		{

			elements.push({'name': obj.attr('id'), 'type': 'date', 'separator': mvsep });
		}
	}
	
//	if ( hidecode != "" )
//		dxl += safe_tags("</span>");
	return dxl;
}

function handleManualHtmlText(obj, mode)
{
	obj = $(obj);
	var dxl = '';
	var htmleditor = ace.edit(obj.attr("id"));	
	var temphtml = htmleditor.getValue();
	temphtml = safe_tags(temphtml);
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		dxl = hide;
	
	dxl += temphtml;

	if ( hide != "")
		dxl += "{% endif %}\r"
	
	return dxl;
}

function handleComputedText(obj, mode)
{
	obj = $(obj);
	var dxl = '';
	var formula = obj.attr("fformula");
	var onclick = obj.attr("fonclick");
	var onchange = obj.attr("fonchange");
	var onfocus = obj.attr("fonfocus");
	var onblur = obj.attr("fonblur");
	var htmlother = obj.attr("htmlother");
	var htmlclass = obj.attr("htmlclass") 
	var hidewhen = obj.attr('hidewhen');
	var style = obj.attr('style');
	var objstyle = getObjectFontStyle(obj)
	var etype = obj.attr('etype') ? obj.attr('etype') : 'D';
	var luapp = etype != 'D' && obj.attr('lunsfname') ? $.trim(obj.attr('lunsfname')) : null;
	var luview = etype != 'D' && obj.attr('luview') ? $.trim(obj.attr('luview')) : null;
	var lucolumn = etype != 'D' && obj.attr('lucolumn') ? obj.attr('lucolumn') : null;
	var fncalculation = null;
	var tiedupembview = null;
	var refreshscript = null;
	var restrictions = null;
	var action = null;
	var hidecode = handleHideWhenDXL(obj, mode);
	if ( hidecode != "" )
		dxl = hidecode;
	
	if (etype == 'V') {
		action = obj.attr('lufunction') ? $.trim(obj.attr('lufunction')) : null;
		fncalculation = obj.attr('fnCalculationType');
		tiedupembview = fncalculation == 'E' ? obj.attr('embViewRef') : null;
		refreshscript = fncalculation == 'E' ? obj.attr('linkedEmbRefreshScript') : null;
		restrictions = obj.attr('restrictions') ? obj.attr('restrictions').split(';') : null;
	}

	if (etype == 'V' && fncalculation != 'E' && luview) {
		var criteria = '';
		var formula = '';
		if (restrictions && restrictions.length) {
			criteria  = '{';
			for (var x = 0; x < restrictions.length; x++) {
				var attr = restrictions[x].split(',');
				criteria += "'" + attr[0] + "': VARX_"+ x +',';
				formula += 'VARX_'+ x +' := '+ attr[1]+";\n";
			}
			criteria = criteria.slice(0, -1) + '}';
		}
		
		formula += "$$DbCalculate('"+ (luapp ? luapp : docInfo.AppID) +"', '"+ luview +"', '"+ action +"', '"+ lucolumn +"'";
		if (restrictions && restrictions.length) {
			formula += ",'[DOCOVADBCALC_FUNCTION]'";
		}
		formula += ')';
		
		formula = updateTWIG(formula, "output", "string");
		if (restrictions && restrictions.length) {
			formula = formula.replace("'[DOCOVADBCALC_FUNCTION]'", criteria);
		}
	}

	if (formula && $.trim(formula) != "") {
		if ($.trim(style) != '') {
			objstyle += ' '+ style;
		}
		dxl += safe_tags("<span style='" + objstyle + "' id='"+ obj.prop('id') +"'>");
	
		if (etype != 'V') {
			formula = updateTWIG(formula, "output", "string");
		}
		dxl += formula;
		dxl += '&lt;/span&gt;';
	}
	else if (etype == 'V' && fncalculation == 'E') {
		dxl += safe_tags("<span style='"+ objstyle +"' id='"+ obj.prop('id') +"'");
		var tieduptype = $("#" + tiedupembview);
		if ( tieduptype.length > 0  && tieduptype.attr("elem") == "datatable"){
			dxl += ' fndatatable="'+ tiedupembview +'"';	
		}else{
			dxl += ' fnembview="'+ tiedupembview +'"';	
		}
		dxl += ' fncolumn="'+ lucolumn +'"';
		dxl += ' fnrefreshscript="'+ refreshscript +'" ';
		dxl += safe_tags("></span>");
	}
	if ( hidecode != "" )
		dxl += "{% endif %}\r"
	
	return dxl;
}

function handleObjText(obj, mode)
{
	obj = $(obj).clone();
	var dxl = '';
    	var initdxl = "";

	var hidewhenstr = handleHideWhenDXL(obj, mode);
	if ( hidewhenstr != "")
		dxl = hidewhenstr;
	dxl +=getObjectPassthrough(obj.get(0))
	
	
	
	if ( hidewhenstr != "")
		dxl += "{% endif %}\r"
	return dxl;
}

function handleCheckBox(obj, mode)
{
	obj = $(obj).clone(true);
	var dxl = '';
	var hidecode = handleHideWhenClientSide(obj, mode, "span");
	if ( hidecode != "" ) 
		dxl = hidecode;
	dxl += (mode == 'edit' ? '&lt;table docovafield="1"' : '&lt;span docovafield="1"');
	var onClick = obj.attr('fonclick');
	var onChange = obj.attr('fonchange');
	var onFocus = obj.attr('fonfocus');
	var onBlur = obj.attr('fonblur');
	var htmlclass = obj.attr("htmlclass");
	var htmlother = obj.attr("htmlother");	
	var required = obj.attr('isrequired');
	
	var optionmethod = obj.attr("optionmethod");
	var fdefault = $.trim(obj.attr("fdefault"));
	var name = obj.attr('name');
	var style = obj.attr("style");
	var cbcolumns = parseInt(obj.attr('colno'));
	cbcolumns = (cbcolumns == 0 || isNaN(cbcolumns) ? 1 : cbcolumns);
	
	var mv = "";
	var dmsep = "";
	
	var fieldtype = (obj.attr("elem") ? obj.attr("elem") : "");

	if(fieldtype == "rdbutton"){
    	fieldtype = "radio";
    	mv = "false";
	}else{
    	fieldtype = "checkbox";
		mv = "true";	    	
	}
    
	if(mv === "true"){
		dmsep = obj.attr('chmvdisep');
		dmsep = (typeof dmsep !== "undefined") ? dmsep : 'semicolon';
    }
	if (mode == 'edit') {
		//if not data tables field
		var isdatatablefield = obj.parents(".datatabletbody").length > 0 ? true : false;
		if ( ! isdatatablefield)
			elements.push({'name': name, 'type': fieldtype, 'separator': '' });
	}
	
	htmlclass = (typeof htmlclass !== typeof undefined) ? htmlclass : "";	
	style = (typeof style !== typeof undefined) ? style : "";
	htmlother = (typeof htmlother !== typeof undefined) ? htmlother : "";
	
	
	optionmethod = (typeof optionmethod !== typeof undefined) ? optionmethod : "";
	fdefault = (typeof fdefault !== typeof undefined) ? fdefault : "";

	var classname = "";
	classname += htmlclass ? htmlclass : "";
	
	var inputtranslation = obj.attr('ftranslate');
	dxl += ( typeof inputtranslation !== typeof undefined ) ? ' ftranslate="' + escapeJSEventCode(inputtranslation) + '" ' : '';

	
	if (mode == 'read') {
		dxl += ' id="' + name + '" ';
		dxl += 'class= "' + classname + '" ';
		dxl += (hidecode != '' ? 'style="display:{{ hwen ? \'none\' : \'\' }};"' : '');
		dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
		dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
		dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
		dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';	
		dxl += htmlother ? htmlother + ' ' : '';
		dxl += '&gt;';
		dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '")|raw}}';
		dxl += '&lt;/span&gt;';	
	}
	else {
		dxl += ' class= "noresize checkradio ' + classname + '" ';
		dxl += ' style="'+(hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + style + '"';

		if (optionmethod == 'manual' || optionmethod == 'formula') {	
			dxl += '&gt;';

			if ( fdefault != ""){
				saveComputedVal("default", fdefault, name.toLowerCase() );
			}
			
			dxl += '{% set fdefval = f_GetField("' + name.toLowerCase() + '") %}\r';
			dxl += '{% set fdefvalarray = fdefval is iterable ? fdefval : [fdefval] %}\r';
			
			if(optionmethod == 'manual'){
				dxl += '{% set itemsval = "' + obj.attr('optionlist') + '"|split(";") %}\r';
			}else{
				var fformula = obj.attr("fformula");
				fformula = (typeof fformula !== typeof undefined) ? fformula.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
				fformula = updateTWIG( fformula, "set", "array", name);
				
				dxl += fformula + "\r";
				dxl += '{% set itemsval = __dexpreresraw %}\r';				
			}			
						
			dxl += '{% set items = itemsval is iterable? itemsval : [itemsval] %}';
			
			dxl += '{% for elem in items %}\r';
				dxl += '{% set itemval = elem|split("|") %}\r';
				dxl += '{% set alias = itemval|length &gt; 1 ? itemval[1] | trim : itemval[0] | trim %}\r';
				dxl += '{% if loop.index0 % ' + cbcolumns + ' == 0 %}&lt;tr&gt;{% endif %}';
				dxl += '&lt;td style="border-width:0px"'+ '&gt;';
				dxl += '&lt;label&gt; &lt;input type="' +fieldtype+ '" name="' + name + (fieldtype == "checkbox" ? '[]" ' : '" ');
				dxl += required ? 'isrequired="' + required + '" ' : '';
				dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
				dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
				dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
				dxl += onBlur ?  'onblur="' + escapeJSEventCode(onblur) + '" ' : '';
				dxl += htmlother ? htmlother + ' ' : '';				
				dxl += 'value="{{ alias|raw }}" ';
				if (fieldtype == 'checkbox') {
					dxl += '{% set vpos = f_ArrayGetIndex(fdefvalarray, itemval[0], 0) %}\r';
					dxl += '{% if vpos is null and itemval[0] != alias %}\r';
						dxl += '{% set vpos = f_ArrayGetIndex(fdefvalarray, alias, 0) %}\r';					
					dxl += '{% endif %}\r';
					dxl += '{% if not vpos is null %}\r';					
						dxl += 'checked="checked"\r';
					dxl += '{% endif %}\r';
				}else {
					dxl += "{% if fdefvalarray[0] == itemval[0] or fdefvalarray[0] == alias %}\r";
						dxl += 'checked="checked"\r';
					dxl += "{% endif %}";
				}
				dxl += '/&gt; {{ itemval[0]|raw }}&lt;/label&gt;&lt;/td&gt;';
				dxl += '{% if loop.index % '+ cbcolumns +' == 0 or loop.index == items|length %}&lt;/tr&gt;{% endif %}';
			dxl += '{% endfor %}\r';
		}else if (optionmethod == 'select') {
			var luservername = obj.attr("luservername");
			var luview = obj.attr("luview");
			var lucolumn = obj.attr("lucolumn");
			var lukeytype = obj.attr("lukeytype");
			var lukey = obj.attr("lukey");
			var lukeyfield = obj.attr("lukeyfield");
			var lunsfname = obj.attr("lunsfname");

			dxl += ' id="' + name  + '" ';

		//	if ( lukeytype != "Field")
				dxl += ' require="initiateInputs" elem="' + fieldtype + '" ';
	//		else
	//			dxl += '  elem="' + fieldtype + '" ';
			
			dxl += luservername ? 'luservername="' + luservername + '" ' : '';
			dxl += luview ? 'luview="' + luview + '" ' : '';
			dxl += 'fvalue = "{{ f_GetFieldString("' + name + '", "comma") }}" ';
			dxl += required ? 'isrequired="' + required + '" ' : '';
			dxl += lunsfname ? 'lunsfname="' + lunsfname + '" ' : '';
			dxl += lucolumn ? 'lucolumn="' + lucolumn + '" ' : '';
			dxl += lukeytype ? 'lukeytype="' + lukeytype + '" ' : '';
			dxl += lukey ? 'lukey="' + lukey + '" ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ?  'onblur="' + escapeJSEventCode(onblur) + '" ' : '';
			dxl += lukeyfield ? 'lukeyfield="' + lukeyfield + '" ' : '';
			dxl += cbcolumns > 1 ? 'cbcolumns="' + cbcolumns + '" ' : '';
			dxl += '&gt;&lt;tr/&gt;';			
		}
		dxl += '&lt;/table&gt;';	
	}
	obj = null;
//    if ( hidecode != "")
//		dxl += safe_tags("</span>");
	return dxl;
}//--end handleCheckBox

function handleToggleSwitchDXL(obj, mode)
{
	obj = $(obj);
	var hidecode = handleHideWhenClientSide(obj, mode, "span");
	if ( hidecode != "" ) 
		dxl = hidecode;
	var dxl = '&lt;span ';
	var onClick = obj.attr('fonclick');
	var onChange = obj.attr('fonchange');
	var onFocus = obj.attr('fonfocus');
	var onBlur = obj.attr('fonblur');
	var htmlclass = obj.attr("htmlclass");
	var fdefault = $.trim(obj.attr("fdefault"));
	var name = obj.attr('name');
	var style = obj.attr("style");
	var oncolor = obj.attr('toggleoncolor');
	var offcolor = obj.attr('toggleoffcolor');
	
	if (mode == 'read') {
		if (onClick) {
			dxl += 'onclick="' + escapeJSEventCode(onClick) + '" ';
		}
		if (onFocus) {
			dxl += 'onfocus="' + escapeJSEventCode(onFocus) + '" ';
		}

		dxl += 'id="'+ name +'" ';
		dxl += 'class="'+ (htmlclass ? (htmlclass+' ') : '') + 'docova-toggle disabled far ';
		dxl += '{{ f_GetFieldString("'+ name.toLowerCase() +'") == "true" ? "fa-toggle-on" : "fa-toggle-off" }}" ';
		dxl += 'style="color:{{ f_GetFieldString("'+ name.toLowerCase() +'") == "true" ? "'+ oncolor +';" : "'+ offcolor +';" }} ';
		dxl += (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '');
		dxl += (style ? style : '') + '" ';
		dxl += '&gt;&lt;/span&gt;';
	}
	else {
		if (onClick) {
			dxl += 'onclick="' + escapeJSEventCode(onClick) + '" ';
		}
		if (onFocus) {
			dxl += 'onfocus="' + escapeJSEventCode(onFocus) + '" ';
		}
		if (onBlur) {
			dxl += 'onblur="' + escapeJSEventCode(onBlur) + '" ';
		}
		if ( fdefault != ""){
			saveComputedVal("default", fdefault, name.toLowerCase() );
		}
		
		dxl += 'class="'+ (htmlclass ? (htmlclass+' ') : '') + 'docova-toggle far ';
		dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '" ) == "true" ? "fa-toggle-on" : "fa-toggle-off" }}" ';
		dxl += 'id="tg_'+ name +'" oncolor="'+ oncolor +'" offcolor="'+ offcolor +'" ';
		dxl += 'style="color:{{ f_GetFieldString("'+ name.toLowerCase() +'") == "true" ? "'+ oncolor +';" : "'+ offcolor +';" }} ';
		dxl += (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '');
		dxl += (style ? style : '') + '" ';
		dxl += '&gt;&lt;/span&gt;';
		dxl += '&lt;input type="hidden" name="'+ name + '" id="'+ name + '" ';
		dxl += 'value="{{ f_GetFieldString("' + name.toLowerCase() + '" ) == "true" ? "true" : "" }}" /&gt;';

		elements.push({'name': name, 'type': 'checkbox', 'separator': '' });
	}

//	if ( hidecode != "")
//		dxl += safe_tags("</span>");

	return dxl ;
}//--end handleToggleSwitchDXL

function handleSliderElement(obj, mode)
{
	var dxl = '';
	obj = $(obj);
	if (obj.attr('style') && obj.attr('style').indexOf('width:' > -1)) {
		obj.css('width', '');
	}
	if (obj.attr('style') && obj.attr('style').indexOf('height:' > -1)) {
		obj.css('height', '');
	}
	var hidecode = handleHideWhenClientSide(obj, mode, "span");
	if ( hidecode != "" ) 
		dxl = hidecode;
	
	var boundtoelem = $(obj).attr('boundelem') ? $(obj).attr('boundelem') : '';
	var min = $(obj).attr('minvalue') ? $(obj).attr('minvalue') : 1;
	var max = $(obj).attr('maxvalue') ? $(obj).attr('maxvalue') : 100;
	var slidelength = $(obj).attr('sliderlength') ? $(obj).attr('sliderlength') : null;
	var orientation = $(obj).attr('orientation') ? $(obj).attr('orientation') : 'horizontal';
	var activecolor = $(obj).attr('activecolor') ? $(obj).attr('activecolor') : null;
	var onClick = obj.attr('fonclick');
	var onChange = obj.attr('fonchange');
	var htmlclass = obj.attr("htmlclass");
	var name = obj.attr('name');
	var style = obj.attr("style");
	
	dxl += '&lt;div class="docova_sliderelem';
	dxl += htmlclass ? (' '+htmlclass) : '';
	dxl += '" id="'+ name +'" ';
	if (style || slidelength || hidecode != '') {
		dxl += 'style="';
		dxl += (hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }}; ' : '');
		if (slidelength) {
			if (orientation == 'Horizontal') {
				dxl += 'width:'+ (slidelength.indexOf('%') > -1 ? slidelength : parseInt(slidelength)+'px')+'; ';
			}
			else {
				if ($.trim(slidelength)) {
					dxl += 'height:'+ (slidelength.indexOf('%') > -1 ? slidelength : parseInt(slidelength)+'px')+'; ';
				}
			}
		}
		if (style) {
			dxl += style;
		}
		dxl += '" ';
	}
	
	dxl += 'boundto="'+ boundtoelem +'" ';
	dxl += 'minval="'+ min + '" ';
	dxl += 'maxval="'+ max + '" ';
	dxl += 'orientation="'+ orientation +'" ';
	
	if (mode == 'read') {
		dxl += 'disabled="true" ';
	}
	else {
		if (activecolor) {
			dxl += 'activecolor="'+ activecolor +'" ';
		}
		if (onClick) {
			dxl += 'onclick="' + escapeJSEventCode(onClick) + '" ';
		}
		if (onChange) {
			dxl += 'onchange="' + escapeJSEventCode(onChange) + '" ';
		}
	}
	
	dxl += '&gt;&lt;/div&gt;';
	return dxl;
}

function saveComputedVal(type, code, elemid, issubform, ignoreTwig){

	if (!issubform || typeof issubform == 'undefined')
	{
		if (!ignoreTwig || typeof ignoreTwig == 'undefined') {
			var codevalue = updateTWIG(code, "raw", "string", elemid );
		}
		else {
			var codevalue = code;
		}
		
		codevalue += '{"'  + elemid.toLowerCase() + '" : \r\n';
		codevalue += '{{ __dexpreresraw|serialize|json_encode() }}\r\n';
		codevalue += '{{ f_AddComputedToBuffer ("' + elemid + '", __dexpreresraw )}}\n'
		codevalue += "}";
	
		if ( type == "default"){
			defaultcode.push ( codevalue);
		}
		else{
			defaultcode.push ( codevalue);
			computedcode.push( codevalue);
		}
	}
	else {
		if ( type == "default"){
			defaultcode.push(code);
		}
		else{
			//defaultcode.push(code);
			computedcode.push(code);
		}
	}
}

function InputNamesDXL(obj, mode)
{
	var obj = $(obj);
	var dxl = "";
	var defaultvalue = obj.attr("fdefault");
	var fieldtype = obj.attr("texttype");
	var hidecode= handleHideWhenClientSide(obj, mode, "span");
	if ( hidecode != "" ) 
		dxl = hidecode;
	var name = obj.prop("id");
	var htmlclass = obj.attr('htmlclass') ? obj.attr('htmlclass') : '';
	var htmlother = obj.attr('htmlother') ? obj.attr('htmlother') : '';
	var objstyle = getObjectFontStyle(obj);
	var style = obj.attr('style') ? (objstyle + ' ' + obj.attr('style')) : objstyle;
	var required = obj.attr('isrequired') ? obj.attr('isrequired') : '';
	var namesformat = obj.attr('namesformat');
	var mv = obj.attr("textmv");
	var dmsep = '';
	mv = ( typeof mv !== "undefined") ? mv : "";
	var mvsep = "";
	if(mv === "true"){
		dmsep = obj.attr('textmvdisep');
		dmsep = (typeof dmsep !== "undefined" && dmsep !== null && dmsep !== "") ? dmsep : "semicolon";
		mvsep = dmsep;
		var fldjson = "FieldInfo['" + name.toLowerCase() + "'] = { mvsep: '" + mvsep + "' }";
		fieldsarr.push(fldjson);
	}
	if (mode == 'read') {
		dxl += '&lt;span docovafield="1" id="' + name + '" ';
		dxl += htmlclass ? 'class="' + htmlclass + '" ' : '';
		dxl += htmlother ? htmlother + ' ' : '';
		dxl += 'style="'+(hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '');
		dxl += objstyle ? objstyle + '" ' : '" ';
		dxl += '&gt;';


		if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1)
			dxl += '&lt;span style="display:inline-block;"&gt;';

		if(fieldtype == "cfd"){
			//special case for cfd fields calculate again inline so that it renders	
			dxl += updateTWIG(defaultvalue, "raw", "string", name.toLowerCase() );			
			dxl += '{{ f_AddComputedToBuffer ("' + name.toLowerCase() + '", __dexpreresraw )}}\n'
		}		
		dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '", "names", false, "'+ namesformat +'" ) | raw }}';

		if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1) 
			dxl += '&lt;/span&gt;';

		dxl += '&lt;/span&gt;';
	}
	else {
		var placeholder = obj.attr('fplaceholder') ? obj.attr('fplaceholder') : '';
		var onClick = obj.attr('fonclick') ? obj.attr('fonclick') : '';
		var onChange = obj.attr('fonchange') ? obj.attr('fonchange') : '';
		var onFocus = obj.attr('fonfocus') ? obj.attr('fonfocus') : '';
		var onBlur = obj.attr('fonblur') ? obj.attr('fonblur') : '';
		var selectype = obj.attr('textmv') == 'true' ? 'group' : 'single';
		var showbutton = obj.attr('textnp') && obj.attr('textnp') === '1' ? true : false;
		var type = obj.attr("textrole");
		if ( type && type == "a" )
			type = "authors";
		else if ( type && type == "r")
			type= "readers";
		else
			type = "names";

		if (fieldtype == "c" || fieldtype == "cfd" || fieldtype == 'cwc') {
			dxl += '&lt;span ';
			dxl += 'id="SPAN' + name + '" ';
			if(fieldtype == "cfd"){
				dxl += " cfdfieldname='" + name + "' ";
			}			
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " mvdisep='" + dmsep + "' ";
      		dxl += " docovafield = '1' ";
			dxl += ' style="display:'+(hidecode != '' ? '{{ hwen ? \'none\' : \'inline-block\' }};' : 'inline-block;');
			dxl += (style != "") ? style + '"&gt;' : '"&gt;';
			if ( defaultvalue && jQuery.trim(defaultvalue) != "" )
			{
				if ( fieldtype == "cwc")
					saveComputedVal("default", defaultvalue, name.toLowerCase() );
				else
					saveComputedVal("computed", defaultvalue, name.toLowerCase() );
				
			}
			if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1)
				dxl += '&lt;span style="display:inline-block;"&gt;';

			
			if(fieldtype == "cfd"){
				//special case for cfd fields calculate again inline so that it renders	
				dxl += updateTWIG(defaultvalue, "raw", "string", name.toLowerCase() );			
				dxl += '{{ f_AddComputedToBuffer ("' + name.toLowerCase() + '", __dexpreresraw )}}\n'
			}					
			dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '", "names", false, "'+ namesformat +'") | raw }}';
			
			if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1)
				dxl += '&lt;/span&gt;';
			if (fieldtype == 'c' || fieldtype == 'cwc') {
				dxl += '&lt;input type ="hidden" ';
				dxl += 'name="' + name + '" ';
				dxl += required ? 'isrequired="' + required + '" ' : '';
				dxl += 'selectiontype="' + selectype + '" ';
				dxl += 'namesrole="' + type + '" ';
				dxl += " mvdisep='" + dmsep + "' ";
				dxl += 'value="';
				dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '", "names", false, "'+ namesformat +'" ) }}';
				dxl += '" /&gt;';
			}
			dxl += '&lt;/span&gt;';
		}
		else {
			if (placeholder && jQuery.trim(placeholder) != '') {
				placeholder = updateTWIG(placeholder, 'output', 'string');
			}
		
			dxl += '&lt;input type="text" id="'+ (selectype != 'single' ? 'txt' : '') + name +'" ';
			dxl += required ? 'isrequired="' + required + '" ' : '';
			dxl += 'placeholder="'+ (placeholder ? placeholder : 'Type to lookup names') +'" ';
			dxl += htmlclass ? 'class="namepicker ' + htmlclass +'" ' : 'class="namepicker" ';
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			var inputtranslation = obj.attr('ftranslate');
			dxl += ( typeof inputtranslation !== typeof undefined ) ? ' ftranslate="' + escapeJSEventCode(inputtranslation) + '" ' : '';

			if ( defaultvalue && jQuery.trim(defaultvalue) != "" )
			{
				saveComputedVal("default", defaultvalue, name.toLowerCase() );
			}
	
			dxl += 'style="'+(hidecode != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '');
			dxl += style ? style + '" ' : '" ';
			dxl += 'selectiontype="' + selectype + '" ';
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " mvdisep='" + dmsep + "' ";
			dxl += " docovafield = '1' ";
			dxl += 'namesrole="' + type + '" ';
			dxl += 'target="'+ name +'" ';
			if (selectype == 'single') {
				dxl += 'name="' + name + '" ';
				dxl += 'value="{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '", "names", true, "'+ namesformat +'" )}}" ';
			}
			dxl += '&gt;';
			if (showbutton === true) {
				dxl += '&lt;button type="button" id="btn'+ name +'" tiedto="'+ (selectype == 'group' ? ('txt'+name) : name) +'" class="ui-icon-'+ selectype +'"&gt;&lt;/button&gt;';
			}
			if (selectype == 'group')
			{
				var delimiter = mvsep;
				if (delimiter && delimiter.indexOf('semicolon') == 0) {
					delimiter = ';';
				}
				else if (delimiter && delimiter.indexOf('comma') == 0) {
					delimiter = ',';
				}
				else if (delimiter && (delimiter.indexOf('newline') == 0 || delimiter.indexOf('blankline') == 0)) {
					delimiter = '\n';
				}
				else if (delimiter && delimiter.indexOf('space') == 0) {
					dsp_delimiter = ' ';
				}else{
					delimiter = ';';
				}
				dxl += '&lt;input type=\'hidden\' mvdisep="' + dmsep + '" mvsep="' + mvsep + '" name="'+ name +'" id="'+ name +'" value="{{  f_GetFieldString("' + name.toLowerCase() + '", "' + delimiter + '", "names" ) }}" /&gt;';
				dxl += '&lt;em class="slContainer" id="slContainer' + name.toLowerCase() + '"&gt; {{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '", "names", true, "'+ namesformat +'" ) | raw }}&lt;/em&gt;';
			}			
		}
		//if not data tables field
		var isdatatablefield = obj.parents(".datatabletbody").length > 0 ? true : false;
		if ( ! isdatatablefield)
			elements.push({'name': name, 'type': type, 'separator': mvsep });
	}
	
//	if ( hide != "")
//		dxl += safe_tags("</span>");
	return dxl;
}


function handleInputText( obj, mode )
{
	var dxl = '';
	var obj = $(obj);
	var defaultvalue = obj.attr("fdefault");
	var fieldtype = obj.attr("texttype");
	var onclick = obj.attr("fonclick");
	var onchange = obj.attr("fonchange");
	var onfocus = obj.attr("fonfocus");
	var onblur = obj.attr("fonblur");
	var htmlother = obj.attr("htmlother");
	var htmlclass = obj.attr("htmlclass") 
	var maxlength = obj.attr("maxlength");
	var id =obj.attr('id');
	var fieldrole = obj.attr('textrole');
	var required = obj.attr('isrequired');
	var placeholder = obj.attr('fplaceholder');
	var etype = obj.attr('etype') ? obj.attr('etype') : 'D';
	var luapp = etype != 'D' && obj.attr('lunsfname') ? $.trim(obj.attr('lunsfname')) : null;
	var luview = etype != 'D' && obj.attr('luview') ? $.trim(obj.attr('luview')) : null;
	var lucolumn = etype != 'D' && obj.attr('lucolumn') ? obj.attr('lucolumn') : null;
	var selectiontype = etype != 'D' && obj.attr('textSelectionType') ? obj.attr('textSelectionType') : null;
	var fncalculation = null;
	var tiedupembview = null;
	var refreshscript = null;
	var action = null;
	var textlukey = null;
	var restrictions = null;

	if (selectiontype == 'dblookup') {
		textlukey = obj.attr('text_LUKey');
	}
	else if (selectiontype == 'dbcalculate') {
		action = obj.attr('lufunction') ? $.trim(obj.attr('lufunction')) : null;
		fncalculation = obj.attr('fnCalculationType');
		tiedupembview = fncalculation == 'E' ? obj.attr('embViewRef') : null;
		refreshscript = fncalculation == 'E' ? obj.attr('linkedEmbRefreshScript') : null;
		restrictions = obj.attr('restrictions') ? obj.attr('restrictions').split(';') : null;
	}
	
	var maxlength = obj.attr("maxlength");
	maxlength = ( typeof maxlength !== typeof undefined ) ? maxlength : "";
	var numdec = obj.attr("textnumdecimals");
	numdec = ( typeof numdec !== "undefined" && numdec != "undefined") ? numdec : "";
	var numformat = (obj.attr("numformat") || "none");

	
	var hidecode = handleHideWhenClientSide( obj, mode, "span");
	if (hidecode != "")
		dxl = hidecode;
	var mv = obj.attr("textmv");
	mv = ( typeof mv !== "undefined") ? mv : "";
	var mvsep = "";
	var dmsep = '';
	if(mv === "true"){
		mvsep = obj.attr("textmvsep");
		mvsep = (typeof mvsep !== "undefined") ? mvsep : "comma semicolon";
		dmsep = obj.attr('textmvdisep');
		dmsep = (typeof dmsep !== "undefined") ? dmsep : mvsep.split(" ")[0];
		var fldjson = "FieldInfo['" + id.toLowerCase() + "'] = { mvsep: '" + mvsep + "' }";
		fieldsarr.push(fldjson);
	}
	var objstyle = getObjectFontStyle(obj)		
	if (mode == 'read')
	{
		var display_val = dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1 ? 'inline-block;' : '';
		if(objstyle != ""){
			dxl += safe_tags('<span docovafield="1" id="'+ id + '" style="' + (hidecode != '' ? ('display:{{ hwen ? "none;": "'+ display_val +'" }}') : display_val) + objstyle);
		}else{
			dxl += safe_tags('<span  docovafield="1" id="'+ id +'" style="'+ (hidecode != '' ? ('display:{{ hwen ? "none;": "'+ display_val +'" }}') : display_val));
		}
		
		dxl += safe_tags('" ');

		if (fncalculation == 'E') {
			var tieduptype = $("#" + tiedupembview);
			if ( tieduptype.length > 0  && tieduptype.attr("elem") == "datatable"){
				dxl += ' fndatatable="'+ tiedupembview +'"';	
			}else{
				dxl += ' fnembview="'+ tiedupembview +'"';	
			}
			dxl += ' fncolumn="'+ lucolumn +'"';
			dxl += ' fnrefreshscript="'+ refreshscript +'" ';
		}
		
		if (selectiontype == 'dbcalculate' || fieldrole == 'n') {
			dxl += "class='dspNumber'" + " numformat='" + numformat + "' textnumdecimals='" + numdec + "'"
		}
		
		dxl += '&gt;';

		if(fieldtype == "cfd"){
			//special case for cfd fields calculate again inline so that it renders	
			if (etype == 'V' && luview && lucolumn) {
				if (fncalculation != 'E') {
					if (selectiontype == 'dbcolumn') {
						dxl += '{% docovascript "raw:string" %}';
						dxl += '{{ f_DbColumn("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", '+ lucolumn +') }}';
						dxl += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dblookup' && textlukey) {
						textlukey = textlukey.indexOf('"') > -1 || textlukey.indexOf("'") > -1 ? textlukey.replace(/['"]/g, '') : textlukey;
						dxl += '{% docovascript "raw:string" %}';
						dxl += '{{ f_DbLookup("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", "'+ textlukey +'", '+ lucolumn +') }}';
						dxl += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dbcalculate') {
						var criteria = '';
						var formula = '';
						if (restrictions && restrictions.length) {
							criteria  = '{';
							for (var x = 0; x < restrictions.length; x++) {
								var attr = restrictions[x].split(',');
								criteria += "'" + attr[0] + "': VARX_"+ x +',';
								formula += 'VARX_'+ x +' := '+ attr[1]+";\n";
							}
							criteria = criteria.slice(0, -1) + '}';
						}
						
						formula += "$$DbCalculate('"+ (luapp ? luapp : docInfo.AppID) +"', '"+ luview +"', '"+ action +"', '"+ lucolumn +"'";
						if (restrictions && restrictions.length) {
							formula += ",'[DOCOVADBCALC_FUNCTION]'";
						}
						formula += ')';
						
						formula = updateTWIG(formula, "raw", "string");
						if (restrictions && restrictions.length) {
							formula = formula.replace("'[DOCOVADBCALC_FUNCTION]'", criteria);
						}
						
						dxl += formula;
					}
				}
			}
			else {
				dxl += updateTWIG(defaultvalue, "raw", "string", id.toLowerCase() );			
			}
			if (fncalculation != 'E') {
				dxl += '{{ f_AddComputedToBuffer("' + id.toLowerCase() + '", __dexpreresraw ) }}';
			}
		}
		//if (fncalculation != 'E') {
			dxl += "{{ f_GetFieldString('" + id.toLowerCase() + "', '" + dmsep + "')| raw }}";
		//}
/*
		if (dmsep.indexOf('newline') != -1 || dmsep.indexOf('blankline') != -1) 
			dxl += '&lt;/span&gt;';
*/
		dxl += '&lt;/span&gt;';
	}
	else if (mode == 'edit') //&& hidewhen.indexOf('E') == -1
	{
		if (fieldtype == "c" || fieldtype == "cfd" || fieldtype == 'cwc') {
			dxl += '&lt;span ';
			dxl += 'id="SPAN' + id + '" ';
			if(fieldtype == "cfd"){
				dxl += " cfdfieldname='" + id + "' ";
			}			
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " docovafield = '1' ";
			dxl += " mvdisep='" + dmsep + "' ";
			if (fncalculation == 'E') {
				var tieduptype = $("#" + tiedupembview);
				if ( tieduptype.length > 0  && tieduptype.attr("elem") == "datatable"){
					dxl += ' fndatatable="'+ tiedupembview +'"';	
				}else{
					dxl += ' fnembview="'+ tiedupembview +'"';	
				}
				dxl += ' fncolumn="'+ lucolumn +'"';
				dxl += ' fnrefreshscript="'+ refreshscript +'" ';
			}

			if (selectiontype == 'dbcalculate' || fieldrole == 'n') {
				dxl += "class='dspNumber'" + " numformat='" + numformat + "' textnumdecimals='" + numdec + "' ";
			}

			var display_val = mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1 ? 'inline-block;' : '';
			dxl += 'style="display:';
			dxl += (objstyle != "") ? (hidecode != '' ? ('{{ hwen ? "none;" : "'+ display_val +'" }}') : display_val) + objstyle + '"&gt;' : (hidecode != '' ? ('{{ hwen ? "none;" : "'+ display_val +'" }};" ') : display_val) + '"&gt;';
			if (etype == 'V' && luview && lucolumn) {
				if (fncalculation != 'E')
				{
					//if computed when composed, then put this formula in the default field
					if (selectiontype == 'dbcolumn') {
						var code = '{% docovascript "raw:string" %}';
						code += '{{ f_DbColumn("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", '+ lucolumn +') }}';
						code += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dblookup' && textlukey) {
						textlukey = textlukey.indexOf('"') > -1 || textlukey.indexOf("'") > -1 ? textlukey.replace(/['"]/g, '') : textlukey;
						var code = '{% docovascript "raw:string" %}';
						code += '{{ f_DbLookup("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", "'+ textlukey +'", '+ lucolumn +') }}';
						code += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dbcalculate') {
						var criteria = '';
						var formula = '';
						if (restrictions && restrictions.length) {
							criteria  = '{';
							for (var x = 0; x < restrictions.length; x++) {
								var attr = restrictions[x].split(',');
								criteria += "'" + attr[0] + "': VARX_"+ x +',';
								formula += 'VARX_'+ x +' := '+ attr[1]+";\n";
							}
							criteria = criteria.slice(0, -1) + '}';
						}
						
						formula += "$$DbCalculate('"+ (luapp ? luapp : docInfo.AppID) +"', '"+ luview +"', '"+ action +"', '"+ lucolumn +"'";
						if (restrictions && restrictions.length) {
							formula += ",'[DOCOVADBCALC_FUNCTION]'";
						}
						formula += ')';
						
						formula = updateTWIG(formula, "raw", "string");
						if (restrictions && restrictions.length) {
							formula = formula.replace("'[DOCOVADBCALC_FUNCTION]'", criteria);
						}
						
						code = formula;
					}
					if (fieldtype == 'cwc')
						saveComputedVal("default", code, id.toLowerCase(), false, true);
					else
						saveComputedVal("computed", code, id.toLowerCase(), false, true);
				}
			}
			else if (defaultvalue && jQuery.trim(defaultvalue) != "" )
			{
				//if computed when composed, then put this formula in the default field
				if (fieldtype == 'cwc')
					saveComputedVal("default", defaultvalue, id.toLowerCase() );
				else
					saveComputedVal("computed", defaultvalue, id.toLowerCase() );
			}
			
			if(fieldtype == "cfd" && fncalculation != 'E'){
				//special case for cfd fields calculate again inline so that it renders	
				if (etype == 'V' && luview && lucolumn) {
					if (selectiontype == 'dbcolumn') {
						dxl += '{% docovascript "raw:string" %}';
						dxl += '{{ f_DbColumn("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", '+ lucolumn +') }}';
						dxl += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dblookup' && textlukey) {
						textlukey = textlukey.indexOf('"') > -1 || textlukey.indexOf("'") > -1 ? textlukey.replace(/['"]/g, '') : textlukey;
						dxl += '{% docovascript "raw:string" %}';
						dxl += '{{ f_DbLookup("", "'+ (luapp ? luapp : docInfo.AppID) +'", "'+ luview +'", "'+ textlukey +'", '+ lucolumn +') }}';
						dxl += "{% enddocovascript %}";
					}
					else if (selectiontype == 'dbcalculate') {
						var criteria = '';
						var formula = '';
						if (restrictions && restrictions.length) {
							criteria  = '{';
							for (var x = 0; x < restrictions.length; x++) {
								var attr = restrictions[x].split(',');
								criteria += "'" + attr[0] + "': VARX_"+ x +',';
								formula += 'VARX_'+ x +' := '+ attr[1]+";\n";
							}
							criteria = criteria.slice(0, -1) + '}';
						}
						
						formula += "$$DbCalculate('"+ (luapp ? luapp : docInfo.AppID) +"', '"+ luview +"', '"+ action +"', '"+ lucolumn +"'";
						if (restrictions && restrictions.length) {
							formula += ",'[DOCOVADBCALC_FUNCTION]'";
						}
						formula += ')';
						
						formula = updateTWIG(formula, "raw", "string");
						if (restrictions && restrictions.length) {
							formula = formula.replace("'[DOCOVADBCALC_FUNCTION]'", criteria);
						}
						
						dxl += formula;
					}
				}
				else {
					dxl += updateTWIG(defaultvalue, "raw", "string", id.toLowerCase());			
				}
				dxl += '{{ f_AddComputedToBuffer ("' + id.toLowerCase() + '", __dexpreresraw )}}\n'
			}
			
			if (fncalculation != 'E') {
				dxl += '{{ f_GetFieldString("' + id.toLowerCase() + '", "' + dmsep + '") | raw}}';
			}

/*			
			if (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1)
				dxl += '&lt;/span&gt;';
*/
			if (fieldtype == 'c' || fieldtype == 'cwc') {
				dxl += '&lt;input type="hidden" ';
				dxl += 'id="' + id + '" ';
				dxl += " mvsep='" + mvsep + "' ";
				dxl += 'name="' + id + '" ';
				dxl += " mvdisep='" + dmsep + "' ";
				dxl += 'value="';
				dxl += '{{ f_GetFieldString("' + id.toLowerCase() + '",  "' + dmsep + '", "", true) }}';
				
				dxl += '" /&gt;';
			}
			dxl += '&lt;/span&gt;';
		}
		else {
			if (placeholder && jQuery.trim(placeholder) != '') {
				placeholder = updateTWIG(placeholder, 'output', 'string');
			}
			else {
				placeholder = '';
			}
			
			//if number type that wrap with span
			if ( fieldrole == 'n'){
				dxl += safe_tags("<span id='dsp" +  id + "' class='dspNumber'" + " numformat='" + numformat + "' textnumdecimals='" + numdec + "'>")
			}
			if(mv === "true" && (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1 ))
				dxl += '&lt;textarea ';
			else 
				dxl += '&lt;input type="text" ';
			dxl += 'id="' + id + '" ';
			dxl += 'name="' + id + '" ';
			dxl += 'placeholder="' + placeholder + '" ';
			dxl += " mvsep='" + mvsep + "' ";
			dxl += " docovafield = '1' ";
			dxl += " mvdisep='" + dmsep + "' ";
			dxl += required ? 'isrequired="' + required + '" ' : '';
			if ( maxlength != ""){
				dxl += " maxlength='" + maxlength + "'";
			}
			if (fieldrole == 'n'){
				dxl +=  " textrole='n'";
				dxl +=  " textnumdecimals='" + numdec + "'";
				dxl +=  " numformat='" + numformat + "'";
			}
			dxl += ( typeof htmlclass !== typeof undefined ) ? 'class="'+ htmlclass + '" ' : '';
			dxl += ( typeof htmlother !== typeof undefined ) ? htmlother + ' ' : '';		
			dxl += ( typeof onclick !== typeof undefined ) ? 'onclick="' + escapeJSEventCode(onclick) + '" ' : '';
			dxl += ( typeof onchange !== typeof undefined ) ? 'onchange="' + escapeJSEventCode(onchange) + '" ' : '';
			dxl += ( typeof onfocus !== typeof undefined ) ? 'onfocus="' + escapeJSEventCode(onfocus) + '" ' : '';
			dxl += ( typeof onblur !== typeof undefined ) ? 'onblur="' + escapeJSEventCode(onblur) + '" ' : '';
			var inputtranslation = obj.attr('ftranslate');
			dxl += ( typeof inputtranslation !== typeof undefined ) ? ' ftranslate="' + escapeJSEventCode(inputtranslation) + '" ' : '';
	
			dxl += 'style="'+ (obj.attr('style') ? (hidecode != '' ? ('display:{{ hwen ? "none;" : "" }}') : '') + obj.attr('style') : (hidecode != '' ? ('display:{{ hwen ? "none;" : "" }} ') : '')) + '" ';
			
			if (mv !== "true" ||  ( (mvsep.indexOf('newline') == -1 && mvsep.indexOf('blankline') == -1 )))
			{
				if (defaultvalue && $.trim(defaultvalue) !== '') {
					saveComputedVal("default", defaultvalue, id.toLowerCase() );
				}
				dxl += 'value="{{ f_GetFieldString("' + id.toLowerCase() + '",  "' + dmsep + '", "", true) }}"';

			}
			dxl += '&gt;';
			if (mv === "true" && (mvsep.indexOf('newline') != -1 || mvsep.indexOf('blankline') != -1 )) {
				if (defaultvalue && $.trim(defaultvalue) !== '') {
					saveComputedVal("default", defaultvalue, id.toLowerCase() );
					
				}
				dxl += '{{ f_GetFieldString("' + id.toLowerCase() + '", "' + dmsep + '", "", true) }}';
				dxl += '&lt;/textarea&gt;';
			}
			//close the span around number fields
			if ( fieldrole == "n")
				dxl += safe_tags("</span>");
		}
	}
	
	if (mode == 'edit') {
		var role = 'text';
		if (fieldrole == 'n')
			role = 'number';
		else if (fieldrole == 'a')
			role = 'authors';
		else if (fieldrole == 'r')
			role = 'readers';
		//if not data tables field
		var isdatatablefield = obj.parents(".datatabletbody").length > 0 ? true : false;
		if ( ! isdatatablefield)
			elements.push({'name': id, 'type': role, 'separator': mvsep });
	}

//	if ( hidecode != "" )
	//	dxl += safe_tags("</span>");

	return dxl; 
}

function handleButtonText( obj, mode )
{
	var obj = $(obj)
	var dxl = "";
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		dxl = hide;
	
	obj.removeAttr("customhidewhen");

	dxl += getObjectPassthrough(obj.get(0));
	if ( hide != "")
		dxl += "{% endif %}\r"
	return dxl;
}

function handlePicklistText(obj, mode)
{
	var obj = $(obj);
	var dxl = '';
	var hide = handleHideWhenDXL(obj, mode);
	if (hide != '') {
		dxl = hide;
	}
	
	obj.removeAttr('customhidewhen');
	var dlgtitle = obj.attr('pldialogtitle') ? obj.attr('pldialogtitle') : '';
	var promptmsg = obj.attr('plprompt') ? obj.attr('plprompt') : '';
	var appid = obj.attr('lunsfname') ? obj.attr('lunsfname') : null;
	var srcview = obj.attr('luview') ? obj.attr('luview') : null;
	var dstform = obj.attr('luform') ? obj.attr('luform') : null;
	var tiedupembview = obj.attr('plRefreshEmbView') ? obj.attr('plRefreshEmbView') : null;
	var restrictTo = obj.attr('plrestrictTo') ? obj.attr('plrestrictTo') : null;
	var plaction = obj.attr('plAction') ? obj.attr('plAction') : 'D';
	var frmelems = [];
	var srcelems = [];

	$.each(obj.get(0).attributes, function(){
		if (this.name.indexOf('pltf_') == 0) {
			frmelems[this.name] = this.value;
		}
		else if (this.name.indexOf('plsf_') == 0) {
			srcelems[this.name] = this.value;
		}
	});
	
	if (restrictTo && jQuery.trim(restrictTo) != '') {
		restrictTo = updateTWIG(restrictTo, 'output', 'string');
		dxl += '&lt;input type="hidden" id="pklist_'+ obj.prop('id') +'" value="'+ restrictTo +'" /&gt;';
	}
	
	var code = "if (docInfo.isNewDoc || docInfo.isNewDoc != '') {\n";
	code += "	var restrictTo = $('#pklist_"+ obj.prop('id') +"').val();\n";
	code += '	var sourcewindow = window;\n';
	code += "	restrictTo = restrictTo && $.trim(restrictTo) != '' ? restrictTo : '';\n";
	code += "	window.top.Docova.Utils.messageBox({icontype: 4, msgboxtype : 1, prompt: 'The document must be saved prior to adding any item through pick-list. Click \"OK\" to save the document and continue or \"Cancel\" to close this dialog.', title: 'Pick-list warning', onOk: function(){\n";
	code += "		var uidoc = Docova.getUIDocument();\n";
	code += "		uidoc.save();\n";
	code += '		var ws = new DocovaUIWorkspace();\n'
	code += "		ws.picklistCollection(null, true, '', "+ (appid ? ("'"+appid+"'") : 'docInfo.AppID') +", '"+ srcview +"', '"+ dlgtitle +"', '"+ promptmsg +"', restrictTo, function(ei) {\n";
	code += '			if (!ei.count) {\n';
	code += "				alert('No record has been selected.');\n";
	code += '				return false;\n';
	code += '			}\n';
	code += '			var doc = ei.getFirstDocument();\n';
	code += '			var app = Docova.getApplication();\n';
	code += '			var parentDoc = new DocovaDocument(app, docInfo.DocKey, docInfo.DocKey);\n';
	code += '			var saved = false;\n';
	code += '			while(doc !== null) {\n';
	if (plaction == 'D') {
		code += '				var expDoc = app.createDocument();\n';
		code += "				expDoc.setField('Form', '"+ dstform +"');\n";
		for (var key in frmelems) {
			if ($.trim(frmelems[key]) != '' && key.indexOf('pltf_') == 0) {
				var tmp = key.replace('pltf_', '');
				var unescapedcode = safe_quotes_js(frmelems[key], false, true);
				unescapedcode = safe_tags(unescapedcode, true);
				frmelems[key] = unescapedcode;
				if (frmelems[key].indexOf("'") > -1 || frmelems[key].indexOf('"') > -1) {
					code += "				expDoc.setField('"+ tmp +"', '"+ (frmelems[key].replace(/["']/g, '')) +"');\n";
				}
				else {
					code += "				expDoc.setField('"+ tmp +"', doc.getField('"+ frmelems[key] +"'));\n";
				}
			}
			else {
				var tmp = key.replace('pltf_', '');
				code += "				expDoc.setField('"+ tmp +"', doc.getField('"+ key +"'));\n";
			}
		}
	}
	else {
		code += '				var expDoc = doc;\n';
		code += '				expDoc.isNewDocument = false;\n';
		code += '				expDoc.isModified = true;\n';
		if (Object.keys(srcelems).length) {
			for (var key in srcelems) {
				if ($.trim(srcelems[key]) != '' && key.indexOf('plsf_') == 0) {
					var tmp = key.replace('plsf_', '');
					var unescapedcode = safe_quotes_js(srcelems[key], false, true);
					unescapedcode = safe_tags(unescapedcode, true);
					srcelems[key] = unescapedcode;
					if (srcelems[key].indexOf("'") > -1 || srcelems[key].indexOf('"') > -1) {
						code += "				expDoc.setField('"+ tmp +"', '"+ srcelems[key].replace(/["']/g, '') +"');\n";
					}
					else {
						code += "				var parentValue = parentDoc.getField('"+ srcelems[key] +"');\n";
						code += "				expDoc.setField('"+ tmp +"', parentValue);\n";
					}
				}
			}
		}
	}
	code += '				expDoc.makeResponse(parentDoc);\n';
	code += '				if (expDoc.save()) {\n';
	code += '					saved = true;\n';
	if (tiedupembview) {
		code += "					var embView = sourcewindow.Docova.getUIDocument();\n";
		code += "					embView = embView.getUIEmbeddedView('"+ tiedupembview +"');\n";
		code += "					embView.refresh({loadxml: true, loadxsl: true, restorestate: false});\n";
	}
	code += '				} else {\n';
	code += '					saved = false;\n';
	code += "					alert('Failed to create child document. Contact Admin for details or retry.');\n";
	code += '					break;\n';
	code += '				}\n';
	code += '				doc = ei.getNextDocument(doc);\n';
	code += '			}\n';
	code += '			if (saved === true) { return true; }\n';
	code += '		});\n';
	code +=	'		}, onCancel: function(){\n';
	code += '			return false;\n';
	code += '		}\n';
	code += '	});\n';
	code += '} else {\n';
	code += '	var ws = new DocovaUIWorkspace();\n'
	code += '	var sourcewindow = window;\n';
	code += "	var restrictTo = $('#pklist_"+ obj.prop('id') +"').val();\n";
	code += "	restrictTo = restrictTo && $.trim(restrictTo) != '' ? restrictTo : '';\n";
	code += "	ws.picklistCollection(null, true, '', "+ (appid ? ("'"+appid+"'") : 'docInfo.AppID') +", '"+ srcview +"', '"+ dlgtitle +"', '"+ promptmsg +"', restrictTo, function(ei) {\n";
	code += '		if (!ei.count) {\n';
	code += "			alert('No record has been selected.');\n";
	code += '			return false;\n';
	code += '		}\n';
	code += '		var doc = ei.getFirstDocument();\n';
	code += '		var app = Docova.getApplication();\n';
	code += '		var parentDoc = new DocovaDocument(app, docInfo.DocKey, docInfo.DocKey);\n';
	code += '		var saved = false;\n';
	code += '		while(doc !== null) {\n';
	if (plaction == 'D') {
		code += '			var expDoc = app.createDocument();\n';
		code += "			expDoc.setField('Form', '"+ dstform +"');\n";
//		code += "			expDoc.setField('txt_parentdockey', docInfo.DocKey);\n";
		for (var key in frmelems) {
			if ($.trim(frmelems[key]) != '' && key.indexOf('pltf_') == 0) {
				var tmp = key.replace('pltf_', '');
				var unescapedcode = safe_quotes_js(frmelems[key], false, true);
				unescapedcode = safe_tags(unescapedcode, true);
				frmelems[key] = unescapedcode;
				if (frmelems[key].indexOf("'") > -1 || frmelems[key].indexOf('"') > -1) {
					code += "			expDoc.setField('"+ tmp +"', '"+ (frmelems[key].replace(/["']/g, '')) +"');\n";
				}
				else {
					code += "			expDoc.setField('"+ tmp +"', doc.getField('"+ frmelems[key] +"'));\n";
				}
			}
			else {
				var tmp = key.replace('pltf_', '');
				code += "			expDoc.setField('"+ tmp +"', doc.getField('"+ key +"'));\n";
			}
		}
	}
	else {
		code += '			var expDoc = doc;\n';
		if (Object.keys(srcelems).length) {
			for (var key in srcelems) {
				if ($.trim(srcelems[key]) != '' && key.indexOf('plsf_') == 0) {
					var tmp = key.replace('plsf_', '');
					var unescapedcode = safe_quotes_js(srcelems[key], false, true);
					unescapedcode = safe_tags(unescapedcode, true);
					srcelems[key] = unescapedcode;
					if (srcelems[key].indexOf("'") > -1 || srcelems[key].indexOf('"') > -1) {
						code += "			expDoc.setField('"+ tmp +"', '"+ srcelems[key].replace(/["']/g, '') +"');\n";
					}
					else {
						code += "			var parentValue = parentDoc.getField('"+ srcelems[key] +"');\n";
						code += "			expDoc.setField('"+ tmp +"', parentValue);\n";
					}
				}
			}
		}
	}
	code += '			expDoc.makeResponse(parentDoc);\n';
	code += '			if (expDoc.save()) {\n';
	code += '				saved = true;\n';
	if (tiedupembview) {
		code += "				var embView = sourcewindow.Docova.getUIDocument();\n";
		code += "				embView = embView.getUIEmbeddedView('"+ tiedupembview +"');\n";
		code += "				embView.refresh({loadxml: true, loadxsl: true, restorestate: false});\n";
	}
	code += '			} else {\n';
	code += '				saved = false;\n';
	code += "				alert('Failed to create child document. Contact Admin for details or retry.');\n";
	code += '				break;\n';
	code += '			}\n';
	code += '			doc = ei.getNextDocument(doc);\n';
	code += '		}\n';
	code += '		if (saved === true) { return true; }\n';
	code += '	});\n';
	code += '}\n';
	
	obj.attr('fonclick', safe_quotes_js(safe_tags(code)));
	dxl += getObjectPassthrough(obj.get(0));
	if ( hide != "")
		dxl += "{% endif %}\r";
	return dxl;
}

function InputKeywordsDXL(obj, mode) 
{
	var origobj  = $(obj);
	obj = $(obj).clone(true);
	var dxl = '';
	var hidecode = handleHideWhenClientSide( obj, mode, "span");
	if (hidecode != "")
		dxl = hidecode;
	dxl += (mode == 'edit' ? '&lt;select elem="select"' : '&lt;span ');
	var onClick = obj.attr('fonclick');
	var onChange = obj.attr('fonchange');
	var onFocus = obj.attr('fonfocus');
	var onBlur = obj.attr('fonblur');
	var htmlclass = obj.attr("htmlclass");
	var htmlother = obj.attr("htmlother");	
	var required = obj.attr('isrequired');
	
	var optionmethod = obj.attr("optionmethod");
	var fdefault = $.trim(obj.attr("fdefault"));
	var placeholder = $.trim(obj.attr('fplaceholder'));
	var name = obj.prop('id');
	var style = obj.attr("style");
	var allowNewVals = (obj.attr("allownewvals") == "1" ? true : false);

	var mv = obj.attr("selectmv");
	mv = ( typeof mv !== "undefined") ? mv : "";
	var mvsep = "";
	var dmsep = '';
	if(mv === "true"){
		mvsep = obj.attr("selectmvsep");
		mvsep = (typeof mvsep !== "undefined") ? mvsep : "comma semicolon";
		dmsep = obj.attr('selectmvdisep');
		dmsep = (typeof dmsep !== "undefined") ? dmsep : mvsep.split(" ")[0];
	}

	if (mode == 'edit') {
		//if not data tables field
		
		var isdatatablefield = origobj.parents(".datatabletbody").length > 0 ? true : false;
		if ( ! isdatatablefield)
			elements.push({'name': name, 'type': 'select', 'separator': mvsep });
	}

	htmlclass = (typeof htmlclass !== typeof undefined) ? htmlclass : "";	
	style = (typeof style !== typeof undefined && style != '') ? style : '';
	htmlother = (typeof htmlother !== typeof undefined) ? htmlother : "";
	
	optionmethod = (typeof optionmethod !== typeof undefined) ? optionmethod : "";
	fdefault = (typeof fdefault !== typeof undefined) ? fdefault : "";
	
	if (mode == 'read' && style) {
		style = getObjectFontStyle(obj);
	}

	dxl += mv === "true" ? " multiple " : '';
	dxl += ' id="'+ name + '" name="' + name + (mv==="true" ? '[]"' :  '" ');
	dxl += htmlclass ? ' class="' + htmlclass + '" ' : '';
	dxl += mode == 'edit' && required ? 'isrequired="' + required + '" ' : '';
	dxl += ' style="';
	dxl += hidecode != '' ? 'display:{{ hwen ? "none" : "" }};' : '';
	dxl += style ? style +'" ' : '" ';
	dxl += htmlother ? htmlother + ' ' : '';
	dxl +=  ' textmv = "' + mv + '" textmvsep="' + mvsep + '" ';
	dxl += ' mvdisep="' + dmsep + '" ';
	dxl += ' docovafield="1" ';
	dxl += (allowNewVals ? ' allownewvals="1" ' : '');
	dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
	dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
	dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
	dxl += ' fvalue="{{ f_GetFieldString("' + name.toLowerCase() + '",  "' + mvsep + '", "", true) }}" ';
	dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
	var inputtranslation = obj.attr('ftranslate');
	dxl += ( typeof inputtranslation !== typeof undefined ) ? ' ftranslate="' + escapeJSEventCode(inputtranslation) + '" ' : '';
	
	
	if (mode == 'read') {
		dxl += '&gt;';
		dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '", "' + dmsep + '") | raw }}';
		dxl += '&lt;/span&gt;';
	}else if (mode == 'edit'){
		dxl += 'elem="select" ';
		if (placeholder && jQuery.trim(placeholder) != '') {
			placeholder = updateTWIG(placeholder, 'output', 'string');
		}
		else {
			placeholder = '';
		}
		
		dxl += 'fplaceholder="' + placeholder + '" ';
		
		if (optionmethod == 'manual' || optionmethod == 'formula') {
			dxl += '&gt;\r';
			dxl += '&lt;option value="" &gt;- Select -&lt;/option&gt;\r';			

			if ( fdefault != ""){
				saveComputedVal("default", fdefault, name.toLowerCase() );
			}
			
			dxl += '{% set fdefval = f_GetField("' + name.toLowerCase() + '") %}\r';
			dxl += '{% set fdefvalarray = fdefval is iterable ? fdefval : [fdefval] %}\r';
			
			if(optionmethod == 'manual'){
				var optionlist = (obj.attr('optionlist')||"").replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;");
				dxl += '{% set itemsval = "' + optionlist + '"|split(";") %}\r';
			}else{
				var fformula = obj.attr("fformula");
				//fformula = (typeof fformula !== typeof undefined) ? fformula.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;") : "";
				fformula = updateTWIG( fformula, "set", "array", name);
				
				dxl += fformula + "\r";
				dxl += '{% set itemsval = __dexpreresraw %}\r';				
			}	
				
			dxl += '{% set items = itemsval is iterable ? itemsval : [itemsval] %}';
			
			dxl += '\r{% for elem in items %}\r';
   				dxl += '{% set itemval = elem|trim|split("|") %}\r';
   				dxl += '{% set alias = itemval|length &gt; 1 ? itemval[1]|trim : itemval[0]|trim %}\r';
   				dxl += '&lt;option value="{{ alias|raw }}" ';
   				dxl += '{% set vpos = f_ArrayGetIndex(fdefvalarray, itemval[0], 0) %}\r';
				dxl += '{% if vpos is null and itemval[0] != alias %}\r';
					dxl += '{% set vpos = f_ArrayGetIndex(fdefvalarray, alias, 0) %}\r';					
				dxl += '{% endif %}\r';
				dxl += '{% if not vpos is null %}\r';
					if(allowNewVals){
						dxl += '{% set fdefvalarray = f_ArrayRemoveItem(fdefvalarray, vpos) %}\r';
					}
					dxl += 'selected\r';
				dxl += '{% endif %}\r';			
				dxl += '&gt;{{ itemval[0]|raw }}'
   				dxl += '&lt;/option&gt;\r';
			dxl += '{% endfor %}\r';
			
			//if(allowNewVals){
				dxl += '{% for elem in fdefvalarray %}\r';
				dxl += '{% set vpos = f_ArrayGetIndex(items, elem, 0) %}\r';
					dxl += '{% if elem|trim != "" and vpos is null %}\r';
						dxl += '&lt;option value="{{ elem|raw }}" selected&gt;{{ elem|raw }}&lt;/option&gt;\r';
					dxl += '{% endif %}\r';	
				dxl += '{% endfor %}\r';
			//}
			
			dxl += '&lt;/select&gt;';
		}else if (optionmethod == 'select') {
			var luservername = obj.attr("luservername");
			var luview = obj.attr("luview");
			var lucolumn = obj.attr("lucolumn");
			var lukeytype = obj.attr("lukeytype");
			var lukey = obj.attr("lukey");
			var lukeyfield = obj.attr("lukeyfield");
			var lunsfname = obj.attr("lunsfname");

			if ( fdefault != ""){
				saveComputedVal("default", fdefault, name.toLowerCase() );
			}

			dxl += 'require="initiateInputs" elem="select" ';
			dxl += luservername ? 'luservername="' + luservername + '" ' : '';
			dxl += luview ? 'luview="' + luview + '" ' : '';
			dxl += lucolumn ? 'lucolumn="' + lucolumn + '" ' : '';
			dxl += lukeytype ? 'lukeytype="' + lukeytype + '" ' : '';
			dxl += lukey ? 'lukey="' + lukey + '" ' : '';
			dxl += lunsfname ? 'lunsfname="' + lunsfname + '" ' : '';
			dxl += lukeyfield ? 'lukeyfield="' + lukeyfield + '" ' : '';
			dxl += '&gt;&lt;/select&gt;';
			dxl += '&lt;input type="hidden" disabled="disabled" name="' + name + '[]" value="{{ f_GetFieldString("' + name.toLowerCase() + '",  "' + mvsep + '", "", true) }}" /&gt;';
		}
	}
//	if ( hidecode != "" )
//		dxl += safe_tags("</span>");
	return dxl;
}//--end InputKeywordsDXL

function updateTWIG(dsstring, mode, expect, currentfieldname)
{
	if (typeof mode == typeof undefined || !mode)
		mode = 'output';
	if (typeof expect == typeof undefined || !expect)
		expect = 'string';
	if (typeof currentfieldname == typeof undefined || !currentfieldname)
		currentfieldname = '';
	
	
	dsstring = $.trim(dsstring);
	
	//dsstring = dsstring.replace(/\$\$|\@/g, 'f_');
	var LexerObj = new Lexer();	
	if ( currentfieldname && currentfieldname != "" )
		LexerObj.setCurrentFieldName(currentfieldname);

	var outputTxt = LexerObj.convertCode(dsstring, "TWIG");	

	dsstring = '{% docovascript "' + mode + ":" + expect + '" %}' + safe_tags(outputTxt)  + "{% enddocovascript %}"
	
	return dsstring;
	
}

function embeddedViewDXL(oDiv, mode)
{
	obj = $( oDiv);
    var initdxl = "";
    var height = "";
	height = obj.attr("embviewheight");
	if (height && height != '') {
		if (height.indexOf('%') > -1) {
			height = height.replace('%', 'vh');
		}
		else {
			height = height + 'px';
		}
	}
	else {
		height = '300px';
	}
	var viewname =obj.attr("embviewname");
	var viewtype = obj.attr("eViewType");
	var viewformula = obj.attr("fdefault");
	var embviewsearch = obj.attr("embviewsearch");
	var luservername = obj.attr("luservername");
	luservername = (typeof luservername !== typeof undefined) ? luservername : "";
	var lunsfname = obj.attr("lunsfname");
	lunsfname = (typeof lunsfname !== typeof undefined) ? lunsfname : "";
	var embviewstyle = obj.attr("style");
	if(embviewstyle == ""){
	   	embviewstyle =  "height: " + height + " border: 1px solid black";	
	}
	var restrictions = null;
	var queryVariables = '';
	var queryString = '';
	var id= obj.attr("id");
	var filter = obj.attr("emb_view_filter");
	var restrictStr = "";
	if ( filter == "rc"){
		var restrict = obj.attr("fformula");
		if ( restrict && restrict != "" ){
			restrict = updateTWIG( restrict, "output", "string");
			restrictStr = "&amp;restrictToCategory="
			restrictStr += restrict;
		}
	}else if (filter == 'c'){
		restrictStr = "&amp;restrictToChildren=1"
	}
	else if (filter == 'f') {
		restrictions = obj.attr('restrictions') ? obj.attr('restrictions').split(';') : null;
		if (restrictions && restrictions.length) {
			for (var x = 0; x < restrictions.length; x++) {
				var attr = restrictions[x].split(',');
				queryString += '&amp;embflt_' + attr[0] + "={{ VARX_"+ x +' }}';
				queryVariables += 'VARX_'+ x +' := '+ attr[1]+";\n";
			}
		}		
	}
	
	var embviewsearch = embviewsearch == "1" ? "&amp;showSearch=1": "";
	var allViewsPath = "{{ path('docova_homepage') }}AppViewsAll/";

	var vname = "";
	if(viewname && viewname !== "" && viewname !== "- Select -"){
   		vname += viewname;
   	//-- check if we have a view formula defined if so use it
   	}else if(viewformula && viewformula !== ""){
   		//-- declare a formula to compute the view name
   		vname +=  updateTWIG( viewformula, "output", "string");
   		
   	//-- otherwise set it to an invalid value
   	}else{
   		vname += "view_not_defined";
   	}

	viewsrc ="{{ path('docova_homepage') }}AppViewsAll/" + vname  + "?OpenDocument&amp;AppID="+ docInfo.AppID  + restrictStr + "&amp;isEmbedded=true" + embviewsearch;
	if (viewtype == 'Gantt' || viewtype == 'Calendar') {
		viewsrc += '&amp;viewType=' + viewtype;
	}

	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		initdxl += hide;
	if (queryVariables) {
		var LexerObj = new Lexer();
		initdxl += LexerObj.convertCode(queryVariables, "TWIG");
		viewsrc += queryString;
	}
   	initdxl += safe_tags('\r<iframe id="' + id + '" style="height:' + height +  '; width: 100%; box-sizing: border-box; border: 1px solid lightgray" src="') + viewsrc + safe_tags('"></iframe>');
	
	if ( hide != "")
		initdxl += "{% endif %}\r"
	return initdxl;
}

function chartDXL(obj, mode){
	obj = $(obj);	
	
	var chartid = obj.attr("id").replace(/\s+/g, '');
    var wdth = (obj.attr("chartwidth") || "400");
    var hght = (obj.attr("chartheight") || "200");
    var charttype = (obj.attr("charttype") || "");
    var sourcetype = (obj.attr("chartsourcetype") || "");
    var source = (obj.attr("chartsource") || "");
    var fformula = (obj.attr("fformula") || "");
    var chartlegenditems = (obj.attr("chartlegenditems") || "");
    var chartaxisitems = (obj.attr("chartaxisitems") || "");
    var chartvalueitems = (obj.attr("chartvalueitems") || "");
    var charttitle = (obj.attr("charttitle") || "");
    var charthorzlabel = (obj.attr("charthorzlabel") || "");
    var chartvertlabel = (obj.attr("chartvertlabel") || "");    
    var charthidevalues = (obj.attr("charthidevalues") || "");
    var charthidelegend = (obj.attr("charthidelegend") || "");
    var app = (obj.attr("lunsfname") || "");
    var style = obj.attr('style');
        
	var initdxl = "";
	var hide= handleHideWhenDXL(obj, mode);
    if ( hide != "" ){ 
      initdxl += hide + "\r";
    }
	initdxl += safe_tags('<canvas id="' + chartid +'" name="' + chartid + '" width="' + wdth +'" height="' + hght + '"');
	initdxl += safe_tags(' elemtype="chart" charttype="' + charttype + '" chartsourcetype="' + sourcetype + '"');
	initdxl += safe_tags(' sourceapp="' + app + '" chartsource="' + source + '"');
	initdxl += safe_tags(' chartlegenditems="' + chartlegenditems + '" chartaxisitems="' + chartaxisitems + '" chartvalueitems="' + chartvalueitems + '"');	
	initdxl += safe_tags(' charttitle="' + charttitle + '" charthorzlabel="' + charthorzlabel + '" chartvertlabel="' + chartvertlabel + '" charthidevalues="' + charthidevalues + '" charthidelegend="' + charthidelegend + '" ');		
	if(fformula != ""){
		initdxl += updateTWIG (fformula, "set", "string");
		initdxl += safe_tags(' singlecat="{{ __dexpreres | unescape | raw }}"');
	}	
	initdxl += safe_tags(' style="display:inline;' + style + '"');
	initdxl += safe_tags('></canvas>');
    if ( hide != "" ){
    	initdxl += "{% endif %}\r"
	}
    
	return initdxl;
}

function AttachmentsDXL(obj, mode)
{
	obj = $(obj);
	var uploaderid = obj.attr("id").replace(/\s+/g, '');
    var wdth = obj.attr("attachmentswidthvalue_prop") || "100%";
    var hght = obj.attr("attachmentsheightvalue_prop") || "200px";
    var rdonly = obj.attr("attachmentsreadonly_prop") || "0";
    var maxfiles = obj.attr("maxfiles_prop") || "0";
    var hidebuttons = obj.attr("hideattachbuttons_prop") || "0";
    var genthumbs = obj.attr("generatethumbnails_prop") || "";
    var thumbwdth = obj.attr("thumbnailheight_prop") || "";
    var thumbhght = obj.attr("thumbnailwidth_prop") || "";
    var fileext = obj.attr("allowedfileextensions_prop") || "";
    var listtype = obj.attr("listtype_prop") || "";
    var allowscan = obj.attr("enablelocalscan_prop") || "";
    var customscanjs = obj.attr("localscanjs_prop") || "";
    var hideattachbuttons = obj.attr("hideattachbuttons_prop") || "";
    var required = obj.attr('att_isrequired_prop') || '';
    var singleattach = "";
    if ( obj.hasClass("singleattachment")){
    	singleattach = " onefilemode='true' "
    }
   
    
    var initdxl = "";
	var hide= handleHideWhenClientSide(obj, mode, "div");
    if ( hide != "" ){ 
    	initdxl = hide;
    	initdxl += safe_tags('<div id="'+ uploaderid +'" style="display:{{ hwen ? \'none\' : \'\' }};" >');
    }
	
   	initdxl += safe_tags('<input type="hidden" kind="text" name="' + uploaderid + '_FileNames" id="' + uploaderid + '_FileNames" htmlstyle="display:none;" value="');
   	initdxl += "{{ f_GetFieldString('" + uploaderid.toLowerCase() + "_filenames', '')| raw }}\"";
   	initdxl += required ? 'isrequired="'+ required + '" ' : '';
	initdxl += safe_tags('/>\r');
   
   	initdxl += safe_tags('<div ' + singleattach + ' id="uploaderSTD_' + uploaderid  + '" refId="' + uploaderid + '" style="display:none; width:' + wdth +  ';">\r');
   	initdxl += safe_tags('    <script type="text/javascript">\r');
   	initdxl += '    if(typeof DLIUploaderConfigs == "undefined" || DLIUploaderConfigs == null){\r';	
   
	initdxl += '            var DLIUploaderConfigs = [];\r';  		
	initdxl += '    }\r';  		

    initdxl += "{% set CData = '' %}\r";
    initdxl += "{% set Dates = '' %}\r";
    initdxl += "{% set FilesSize = '' %}\r";
    initdxl += "{% if object and object.getAttachmentsByFieldName('" + uploaderid + "_FileNames')|length %}\r";
    initdxl += "{% for row in object.getAttachmentsByFieldName('" + uploaderid + "_FileNames') %}\r";
    initdxl += "{% set CData = CData ~ row.getFileName ~ '*' %}\r";
    initdxl += "{% set Dates = Dates ~ row.getFileDate|dateformat('m/d/Y') ~ ', ' %}\r";
    initdxl += "{% set FilesSize = FilesSize ~ row.getFileSize ~ ', ' %}\r";
    initdxl += "{% endfor %}\r";
    initdxl += "{% endif %}\r";

   	initdxl += '    DLIUploaderConfigs.push({\r'; 
   		initdxl += '        divId: "attachDisplay_' + uploaderid + '",\r';
   		initdxl += '        refId: "' + uploaderid + '",\r';
   		initdxl += '        FieldName: "' + uploaderid + '_FileNames",\r';
   		initdxl += '        AllowedFileExtensions: "' + fileext + '",\r';
   		initdxl += '        MaxFiles: "' + maxfiles + '",\r';
   		initdxl += '        AttachmentNames : "{% if CData %}{{ CData[:(CData|length-1)]|raw }}{% endif %}",\r';
   		initdxl += '        AttachmentLengths: "{% if FilesSize %}{{ FilesSize[:(FilesSize|length-2)] }}{% endif %}",\r';
   		initdxl += '        AttachmentDates: "{% if Dates %}{{ Dates[:(Dates|length-2)] }}{% else %}Incorrect data type for operator or @Function: Time/Date expected{% endif %}",\r';
   		initdxl += '        ListType: "' +  listtype + '",\r';
   		initdxl += '        CheckoutXmlUrl: "",\r';
   		initdxl += '        LaunchLocally: "{{ settings.getLaunchLocally }}",\r';
   		initdxl += '        LocalDelete: "{{ settings.getLocalDelete }}",\r';
   		initdxl += '        Height: "' + hght + '",\r';
   		initdxl += '        LocalDeleteExcludes: "{{ settings.getLocalDeleteExclude }}",\r';
   		initdxl += '        GenerateThumbnails: "' + genthumbs + '",\r';
   		initdxl += '        ThumbnailWidth: "' + thumbwdth + '",\r';
   		initdxl += '        ThumbnailHeight: "' + thumbhght + '",\r';
    	
   		if(rdonly == "1"){
   			initdxl += '        AllowAdd: "0",\r';
   			initdxl += '        AllowDelete: "0",\r';
   			initdxl += '        AllowFileEdit: "0",\r';
   			initdxl += '        AllowScan: "0",\r';
   		}else{
   			initdxl += '        AllowScan: "' + allowscan + '",\r';		
   		}
   		if(allowscan == "1"){
   			initdxl += '        onScan: function() { ' + ( customscanjs !== "" ? customscanjs : 'return HandleScanClick(this);' ) + ' },\r';
   		}   	
   		initdxl += '        HideButtons: "' + hideattachbuttons + '",\r';
   		
   		initdxl += '        onBeforeAdd: function(filename) {return UploaderOnBeforeAdd(filename, this);},\r';
   		initdxl += '        onBeforeFileEdit: function(filename) { return UploaderOnBeforeFileEdit(filename, this); },\r';
   		initdxl += '        onFileEdit:  function(filename) { return UploaderOnFileEdit(filename, this); },\r';
   		initdxl += '        onBeforeDelete: function(filename) {return UploaderOnDelete(filename, this); },\r';
   		initdxl += '        onDownload: function(filename) { return LogFileDownloaded(filename, this); },\r';
   		initdxl += '        onBeforeLaunch: function(filename) { return UploaderOnBeforeLaunch(filename, this); },\r';
   		initdxl += '        onLaunch: function(filename) { return LogFileViewed(filename, this); } \r';
	initdxl += '        });\r';	//--end of push of new uploader object onto DLIUploaderConfigs
	initdxl += safe_tags('    </script>\r');
	initdxl += safe_tags('</div>\r');
    	
    if ( hide != "" ){
    	initdxl += safe_tags("</div>\r");
	}
	if (mode == 'edit') 
		elements.push({'name': uploaderid + "_FileNames", 'type': 'attachment' ,'separator': '' });
	
	return initdxl;
}

function InputTextAreaDXL(obj, mode )
{
	obj = $(obj);
	var dxl = '';
	var onClick = obj.attr('fonclick') ? safe_tags(obj.attr('fonclick')) : '';
	var onChange = obj.attr('fonchange') ? safe_tags(obj.attr('fonchange')) : '';
	var onFocus = obj.attr('fonfocus') ? safe_tags(obj.attr('fonfocus')) : '';
	var onBlur = obj.attr('fonblur') ? safe_tags(obj.attr('fonblur')) : '';
	var oType = obj.attr('editortype');
	var name = obj.prop('name') != "" ? obj.prop('name') : obj.prop('id');
	var editorHeight = obj.attr('editorheight');
	var htmlclass = obj.attr('htmlclass') ? obj.attr('htmlclass') : '';
	var htmlother = obj.attr('htmlother') ? obj.attr('htmlother') : '';
	var style = obj.attr('style') ? obj.attr('style') : '';
	var hidewhen = obj.attr('hidewhen') ? obj.attr('hidewhen') : '';
	var fdefault = obj.attr('fdefault') ? safe_tags(obj.attr('fdefault')) : '';
	oType = ( typeof oType !== typeof undefined ) ? oType : "";
	var editorWidth = obj.attr('editorwidth');
	var editorSettings = obj.attr('editorsettings')||"";		
	var required = obj.attr('isrequired') ? obj.attr('isrequired') : '';
	var wrappertype = (oType == "P" || oType == "" ? "span" : "div");
	var hide = handleHideWhenClientSide(obj, mode, wrappertype);
	var placeholder = obj.attr('fplaceholder') ? obj.attr('fplaceholder') : '';
	if (hide != "" ) {
		dxl += hide;
	}

	if (mode == 'read')
	{
		if (oType == 'R' || oType == 'D') {
			if (oType == 'R')
			{
				dxl += '&lt;div docovafield="1" id="' + name + '" ';
				//dxl += editorHeight ? 'editorHeight="' + editorHeight + '" ' : 'editorHeight="100" ';
				dxl += htmlclass ? 'class="rdOnlyBody ' + htmlclass + '" ' : 'class="rdOnlyBody" ';
			}
			else {
				dxl += '&lt;span docovafield = "1" id="' + name + '" ';
				dxl += htmlclass ? 'class="rdOnlyBody ' + htmlclass + '" ' : 'rdOnlyBody ';
			}
			dxl += hide != '' ? 'style="display:{{ hwen ? \'none\' : \'\' }};" ' : ''; 
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick)+ '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			//dxl += style ? 'style="' + style + '" ' : '';
			dxl += '&gt;{{ f_GetFieldString("' + name.toLowerCase() + '") | raw }}\n';
			
			dxl += oType == 'R' ? '&lt;/div&gt;' : '&lt;/span&gt;';
		}
		else {
			if (mode == 'read' && style) {
				style = getObjectFontStyle(obj);
			}

			dxl += '&lt;span docovafield = "1" id="' + name + '" ';
			dxl += htmlclass ? 'class="' + htmlclass + '" ' : '';
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			dxl += style ? 'style="white-space:pre-wrap;'+ (hide != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + style + '" ' : '';
			dxl += "&gt;";
			dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '") | raw }}\n';
			dxl += "&lt;/span&gt;";
		}
	}
	else {
		if ( fdefault != "" )
		{
			saveComputedVal("default", fdefault, name.toLowerCase() );
		}

		if (oType == 'R') {
			dxl += '&lt;div processMceEditor="1" docovafield="1" name="'+ name  +'" id="dEdit' + name + '" ';
			dxl += 'editorHeight="'+ editorHeight  +'" ';
			dxl += 'editorWidth="'+  editorWidth  +'" ';
			dxl += 'editorSettings="'+ editorSettings +'" ';
			dxl += htmlclass ? 'class="editArea ' + htmlclass + '" ' : 'class="editArea" ';
			dxl += hide != '' ? 'style="display:{{ hwen ? \'none\' : \'\' }};" ' : '';
			dxl += 'contentEditable="true"&gt;&lt;/div&gt;&lt;br&gt;';
			dxl += '&lt;span elemtype="richtext" style="display:none"&gt;';
			dxl += '&lt;textarea name="'+ name +'" id="'+ name +'" ';
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += required ? 'isrequired="' + required + '" ' : '';
			dxl += style ? 'style="'+ (hide != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + style + '" ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			dxl += "&gt;";
			dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '") | raw }}\n';
			dxl += "&lt;/textarea&gt;";
			dxl += '&lt;/span&gt;';
		}
		else if (oType == 'D') {
			dxl += '&lt;div id="dToolbar'+ name +'" class="ToolBar" '+(hide != '' ? 'style="display:{{ hwen ? \'none\': \'\' }};"' : '')+'&gt;&lt;/div&gt;';
			dxl += '&lt;div processDocovaEditor="1"  docovafield = "1" name="'+ name +'" id="dEdit'+ name +'" class="editArea" '+(hide != '' ? 'style="display:{{ hwen ? \'none\' : \'\' }}"' : '')+' contentEditable="true"&gt;&lt;/div&gt;&lt;br&gt;';
			dxl += '&lt;span elemtype="richtext" style="display:none"&gt;';
			dxl += '&lt;textarea name="'+ name +'" id="'+ name +'" ';
			dxl += htmlclass ? 'class="' + htmlclass + '" ' : '';
			dxl += required ? 'isrequired="' + required + '" ' : '';
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += style ? 'style="' + style + '" ' : '';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			dxl += "&gt;";
			dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '") | raw }}\n';
			dxl += "&lt;/textarea&gt;";
			dxl += '&lt;/span&gt;';
		}
		else {
			if (placeholder && jQuery.trim(placeholder) != '') {
				placeholder = updateTWIG(placeholder, 'output', 'string');
			}
		
			dxl += '&lt;textarea docovafield = "1" id="'+ name +'" name="'+ name + '" ';
			dxl += htmlclass ? 'class="' + htmlclass + '" ' : '';
			dxl += required ? 'isrequired="' + required + '" ' : '';
			dxl += htmlother ? htmlother + ' ' : '';
			dxl += style ? 'style="'+ (hide != '' ? 'display:{{ hwen ? \'none\' : \'\' }};' : '') + style + '" ' : 'style="width: 99%; height:'+ editorHeight +'px" ';
			dxl += 'placeholder="'+ placeholder +'" ';
			dxl += onClick ? 'onclick="' + escapeJSEventCode(onClick) + '" ' : '';
			dxl += onChange ? 'onchange="' + escapeJSEventCode(onChange) + '" ' : '';
			dxl += onFocus ? 'onfocus="' + escapeJSEventCode(onFocus) + '" ' : '';
			dxl += onBlur ? 'onblur="' + escapeJSEventCode(onBlur) + '" ' : '';
			dxl += "&gt;";
			dxl += '{{ f_GetFieldString("' + name.toLowerCase() + '") | raw }}\n';
			dxl += "&lt;/textarea&gt;";
		}
		
		if (oType == 'R') {
			dxl += '&lt;style&gt; .mce-container, .mce-container *, .mce-widget, .mce-widget *, .mce-reset  { font-size: 11px }&lt;/style&gt;';
		}
		//if not data tables field
		var isdatatablefield = obj.parents(".datatabletbody").length > 0 && ! obj.hasClass("dtvaluefield") ? true : false;
		if ( ! isdatatablefield)
			elements.push({'name': name, 'type': 'text', 'separator': '' });
	}
//	if ( hide != "" )
//		dxl += safe_tags("</" +  wrappertype + ">");
	return dxl;
}

function safe_quotes(str, undo){
	if(str === null || str === undefined){
		return "";
	}else{	
		if(!undo){
			return str.replace(/'/g, '\\&amp;apos;').replace(/"/g, '&apos;');
		}else{
			return str.replace(/\\&amp;apos;/g, '\'').replace(/&apos;/g, '"');			
		}
	}
}	

function safe_quotes_js(str, escape, undo){
	if(str === null || str === undefined){
		return "";
	}else{	
		if(!undo){
			return str.replace(/'/g, (escape ? '&amp;apos;' : '&apos;')).replace(/"/g, (escape ? '&amp;quot;' : '&quot;'));
		}else{
			if(escape){
				return str.replace(/&amp;apos;/g, '\'').replace(/&amp;quot;/g, '"');							
			}else{
				return str.replace(/&apos;/g, '\'').replace(/&quot;/g, '"');			
			}
		}
	}
}

function handleAttributes(obj, mode, extraAttribData){
	var arrSkip = ["contenteditable", "selectable", "droppable",  "ui-resizable",  "ui-droppable", "draggable",  "ui-draggable",  "ui-draggable-handle", "ui-sortable", "ui-sortable-handle", "placeholder", "sectionformula", "hidewhen", "customhidewhen", "additional_style", "htmlother", "htmlclass"];
	var arrEventNames = ["fonclick", "fonblur", "fonchange", "fonfocus"];
	var str = "";
	$.each(obj.attributes, function(i, attrib){
				
		if ( attrib.name == "class") {
			var attrbStr = "";
			if ( attrib.value != "" ) {
				var attribArry = attrib.value.split(" ");
				for (var p =0; p < attribArry.length; p ++ ){
					if (  $.inArray(attribArry[p], arrSkip) == -1 ){
						if(attrbStr == ""){
							attrbStr += attribArry[p];
						}else{
							attrbStr += " " + attribArry[p];
						}
					}
				}
				if(str == ""){
					str += attrib.name + "='" + attrbStr + "'";
				}else{
					str += " " + attrib.name + "='" + attrbStr + "'";
				}
			}
		//-- javascript events that need to be handled differently to account for newlines
		}else if ($.inArray(attrib.name.toLowerCase(), arrEventNames) > -1 ){
			var jscode = attrib.value;
			jscode = safe_quotes_js(jscode, false, true);
			jscode = safe_quotes_js(jscode, true);
			
			str += (str != "" ? " " : "") + attrib.name.slice(1) + "='";
			if(jscode != ""){
				jscode = jscode.split("\n");
				str  += jscode.join("&amp;#10;");
			}
			str += "'";
		} else if (attrib.name == 'htmlother') {
			if (attrib.value != '') {
				str += attrib.value + ' ';
			}
		} else if (attrib.name == 'src' && attrib.value.indexOf("{{ path('docova_homepage') }}") > -1) {
			str += attrib.value;
		}else{
			if ($.inArray(attrib.name, arrSkip) == -1 ){
				str += (str == "" ? "" : " ");
				str += attrib.name;
				str += "='";
				str += safe_tags(attrib.value);
				//--append any extra attribute values that might have been passed
				if(extraAttribData && extraAttribData.hasOwnProperty(attrib.name.toUpperCase())){
					str += (attrib.value != "" ? ";" : "") + extraAttribData[attrib.name.toUpperCase()];
					delete extraAttribData[attrib.name.toUpperCase()];
				}
				str += "'";
			}
		 }
	});
	//--if any additional data attributes have not been used yet append them
	if(extraAttribData){
		for (var property in extraAttribData) {
			if (extraAttribData.hasOwnProperty(property)) {
			    str += (str == "" ? "" : " ");
				str += property;
				str += "='";
				str += extraAttribData[property];		
				str += "'";							
			}
		}
	}
	return str;
}


function auditlogDXL(obj, mode )
{
	obj = $(obj);
	var str = "";
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		str = hide;
	var ht = obj.attr("alogheight");
	ht = ht && ht!= "" ? ht : '150px';
	var filter = obj.attr("filter") ? obj.attr("filter") : '0';
	var collapsible = obj.attr("collapsible") ? obj.attr("collapsible") : '0';

	var optstr = " 'height' : '" + ht + "', ";
	optstr += " 'filter' : '" + filter + "', ";
	optstr += " 'collapsible' : '" + collapsible + "', ";
	
	if (mode == 'edit') {
		str += "{% include 'DocovaBundle:Subform:sfDocSection-AppFormAuditLog.html.twig' ignore missing with {'object' : object, 'document' : document, " + optstr + " 'mode' : (mode is defined ? mode : '') } %}";
	}
	else {
		str += "{% include 'DocovaBundle:Subform:sfDocSection-AppFormAuditLog.html.twig' ignore missing with {'object' : object, 'document' : docvalues, " + optstr + " 'mode' : (mode is defined ? mode : '') } %}";
	}
	if (mode == 'edit' && (obj.attr('elem') == 'deditor' || obj.attr('elem') == 'reditor')) 
	{
		elements.push({'name': obj.attr('id'), 'type': 'text', 'separator': '' });
	}
	if ( hide != "")
		str += "{% endif %}\r"
	return str;
}

function sectionDXL(obj, name, mode, ismobile)
{
	if(typeof ismobile == "undefined"){var ismobile = false;}
	obj = $(obj);
	var str = "";
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		str = hide;
	
	if (mode == 'edit') {
		str += "{% include 'DocovaBundle:Subform:"+ name + (ismobile ? "_m" : "") + ".html.twig' ignore missing with {'object' : object, 'document' : document, 'mode' : (mode is defined ? mode : '') } %}";
	}
	else {
		str += "{% include 'DocovaBundle:Subform:"+ name + (ismobile ? "_m" : "") +  ".html.twig' ignore missing with {'object' : object, 'document' : docvalues, 'mode' : (mode is defined ? mode : '') } %}";
	}
	if (mode == 'edit' && (obj.attr('elem') == 'deditor' || obj.attr('elem') == 'reditor')) 
	{
		elements.push({'name': obj.attr('id'), 'type': 'text', 'separator': '' });
	}
	if ( hide != ""){
		str += "{% endif %}\r";
	}
	
	return str;
}

function outlineDXL(obj){
	
	obj = $(obj)
	var olID = obj.prop("id");
	var olName = obj.attr("outlinename");
	var olExpand = obj.attr("expand");
	var olWidth = (obj.attr("width") || "");
	olWidth = (olWidth == "" ? "200" : olWidth );
	olWidth += (olWidth.indexOf("%")==-1 && olWidth.indexOf("px")==-1 ? "px" : "");
	var olHeight = (obj.attr("height")|| "");
	olHeight = (olHeight == "" ? "100%" : olHeight );
	olHeight += (olHeight.indexOf("%")==-1 && olHeight.indexOf("px")==-1 ? "px" : "");
	//htmlstr += " osrc='wViewOutline/'"+olName;
	var htmlstr = "";
	var str = "";
	htmlstr += "<IFRAME src='{{ path('docova_homepage') }}wViewOutline/"+ olName +"?AppID="+ docInfo.AppID +"&expand=" + olExpand + "'";
	htmlstr += " osrc=''";
	htmlstr += " class=''";
	htmlstr += " id='" + olID + "'";
	htmlstr += " name=''";
	htmlstr += " style='width: " + olWidth + "; height: " + olHeight + "; border: 0px none;'";
	htmlstr += " elem='outline' elemtype='field'";
	htmlstr += " outlinename='" + olName + "'";
	htmlstr += " expand='" + olExpand + "'";
	htmlstr += "></IFRAME>";
	str += safe_tags(htmlstr);
	return str;
}

function subformDXL(obj, mode, ismobile)
{
	if(typeof ismobile == "undefined"){var ismobile = false;}
	
	obj = $(obj);
	var str ="";
	var insertMethod = obj.attr("insertmethod");
	
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != ""){
		str += hide;
	}

	var computedvalue = "";
	var defaultvalue = "";
	if ( insertMethod && insertMethod == "Computed" ){
		var formula = obj.attr("subformformula")
		formula = formula && formula != "" ? formula : "";
	

		formula = updateTWIG(formula, "set", "string");
		str += formula;
		str += '{% set subform_name =  f_GetSubformFileName(__dexpreres) %}\r';
		str += "{% include 'DocovaBundle:DesignElements:" + docInfo.AppID +"/subforms/' ~ subform_name ~ '"+ (ismobile ? '_m' : '' ) + (mode == 'read' ? '_read' : '' ) +".html.twig' ignore missing %}";
		if ( mode == "edit"){
			computedvalue = formula + '{% set subform_name =  f_GetSubformFileName(__dexpreres)  %}\r';
		}		
	}else{
		var name = obj.attr("subform");
		str += '{% set subform_name =  f_GetSubformFileName("' + name + '") %}\r';
		computedvalue = str;
		str += "{% include 'DocovaBundle:DesignElements:" + docInfo.AppID +"/subforms/' ~ subform_name ~ '" +  (ismobile ? '_m' : '' ) + (mode == 'read' ? '_read' : '' ) +".html.twig' ignore missing %}";
		
	}

	if ( mode == "edit"){
		defaultvalue = computedvalue + "{% include ['DocovaBundle:DesignElements:" + docInfo.AppID +"/subforms/' ~ subform_name ~ '_default.html.twig', 'DocovaBundle:Applications:missingsubform_computeddefault.html.twig'] ignore missing %}";		
		computedvalue +="{% include ['DocovaBundle:DesignElements:" + docInfo.AppID +"/subforms/' ~ subform_name ~ '_computed.html.twig', 'DocovaBundle:Applications:missingsubform_computeddefault.html.twig'] ignore missing %}";
		saveComputedVal("computed", computedvalue, 'embedded_subform', true);
		saveComputedVal("default", defaultvalue, 'embedded_subform', true);
	}

	if ( hide != ""){
		str += "{% endif %}\r";
	}
	return str;

}

function handleLabelDXL( obj, mode )
{
	var obj = $(obj)
	var dxl = "";	
	var hide = handleHideWhenDXL(obj, mode);
	if ( hide != "")
		dxl = hide;
	obj.removeAttr("customhidewhen");
	dxl += getObjectPassthrough(obj.get(0));
	if ( hide != "")
		dxl += "{% endif %}\r"
	return dxl;
}

function GoogleMapDxl(obj)
{
	var obj = $(obj);
	var dxl = '';
	var name = obj.prop('id');
	var apikey = obj.attr('googlemapapikey') ? $.trim(obj.attr('googlemapapikey')) : '';
	var latitude = obj.attr('googlelatitude') ? $.trim(obj.attr('googlelatitude')) : '';
	var longitude = obj.attr('googlelongitude') ? $.trim(obj.attr('googlelongitude')) : '';
	var postalcode = obj.attr('googlepostalcode') ? $.trim(obj.attr('googlepostalcode')) : '';
	var htmlclass = obj.attr('htmlclass') ? obj.attr('htmlclass') : '';
	var htmlother = obj.attr('htmlother') ? obj.attr('htmlother') : '';
	if (apikey && latitude && longitude)
	{
		if (docInfo.DesignElementType != 'Widgets')
		{
			latitude = updateTWIG(latitude, "output", "string");
			longitude = updateTWIG(longitude, "output", "string");
			postalcode = postalcode ? updateTWIG(postalcode, "output", "string") : '';
		}
		dxl += '<style type="text/css">\n';
		dxl += '#'+ name +' { width:100%; height:100%; }\n';
		dxl += '</style>\n';
		dxl += '<div id="'+ name +'"></div>\n';
		dxl += '<script type="text/javascript">\n';
		dxl += 'function initMap() {\n';
		dxl += '	var myloc = {lat: '+ latitude +', lng: '+ longitude +'}\n';
		dxl += "	var map = new google.maps.Map(document.getElementById('"+ name +"'), {zoom: 15, center: myloc});\n";
		dxl += '	var marker = new google.maps.Marker({position: myloc, map: map});\n';
		dxl += "	if ('"+ postalcode +"') {\n";
		dxl += '		var geocoder = new google.maps.Geocoder();\n';
		dxl += "		google.maps.event.addListenerOnce(map, 'tilesloaded', function() {\n";
		dxl += "			geocoder.geocode({ address: '"+ postalcode +"' }, function(result, status) {\n";
		dxl += "				if (status == 'OK' && result.length > 0) {\n";
		dxl += "					new google.maps.Marker({ position: result[0].geometry.location, map: map });\n";
		dxl += "				}\n";
		dxl += "			});\n";
		dxl += "		});\n";
		dxl += '	}\n';
		dxl += '}\n';
		dxl += '</script>\n';
		dxl += '<script async defer src="https://maps.googleapis.com/maps/api/js?key='+ apikey +'&callback=initMap"></script>';
	}
	dxl = safe_tags(dxl);
	return dxl;
}

function getAppElementDXL(obj)
{
	var obj = $(obj);
	var dxl = '';
	var name = obj.prop('id');
	var appid = obj.attr('appelementappid') ? obj.attr('appelementappid') : '';
	var source_type = obj.attr('appelementsourcetype') ? obj.attr('appelementsourcetype') : '';
	var source_name = obj.attr('appelementsource') ? obj.attr('appelementsource') : '';
	if (appid && source_type && source_name)
	{
		var eUrl = '';
		if (source_type == 'V') {
			eUrl = '/' + docInfo.PortalNsfName + '/AppViewsAll/' + source_name.replace(/\/|\\/g, '-') + "?openDocument&AppID=" + appid + '&viewType=Standard&isEmbedded=true';
		}
		else if (source_type == 'P') {
			eUrl = '/' + docInfo.PortalNsfName + '/wViewPage/' + source_name + '?openPage&AppID=' + appid;
		}
		else if (source_type == 'F') {
			eUrl = '/' + docInfo.PortalNsfName + '/wViewForm/' + source_name + '?openform&AppID=' + appid;
		}
		
		dxl = safe_tags('<iframe id="' + name + '" style="height:100%;width:100%;box-sizing:border-box;border:0" src="') + eUrl + safe_tags('"></iframe>');
	}

	return dxl;
}

function getTableTHDXL(obj)
{
	
	var tmpobj = $(obj).clone();
	tmpobj.find("i").remove();
	tmpobj.find(".ui-resizable-handle").remove();
	return safe_tags($("<div></div>").append(tmpobj).html());
}

function getBarcodeDXL(obj)
{
	var obj = $(obj);
	var dxl = '';
	var name = obj.prop('id');
	var barcode_type = obj.attr('barcodetype') ? obj.attr('barcodetype') : '';
	var barcode_data = obj.attr('barcodedata') ? obj.attr('barcodedata') : '';
	var human_readable = obj.attr('humanreadable') ? obj.attr('humanreadable') : 'yes';
	var readable_vallocation = obj.attr('readablevallocation') ? obj.attr('readablevallocation') : 'bottom';
	var barcode_txtalignment = obj.attr('barcodetxtalignment') ? obj.attr('barcodetxtalignment') : 'center';
	var measure_unit = obj.attr('measureunit') ? obj.attr('measureunit') : 'cm';
	var qrcode_render = obj.attr('qrcoderender') ? obj.attr('qrcoderender') : (barcode_type == 'qrcode' ? 'canvas' : '');
	var barcode_width = obj.attr('barcodewidth') ? obj.attr('barcodewidth') : '';
	var barcode_height = obj.attr('barcodeheight') ? obj.attr('barcodeheight') : '';
	if (barcode_type == 'codabar' ||  barcode_type == 'code39' || barcode_type == 'code39ascii' || barcode_type == 'i2of5') {
		var width_ratio = obj.attr('barcodewidthratio') ? obj.attr('barcodewidthratio') : '0';
	}
	else {
		var width_ratio = '0';
	}
	var fore_color = obj.attr('barcodeforecolor') ? obj.attr('barcodeforecolor') : 'black';
	var back_color = obj.attr('barcodebackcolor') ? obj.attr('barcodebackcolor') : 'white';
	
	if (barcode_type)
	{
		if (barcode_type != 'qrcode')
		{
			dxl = '<script type="text/javascript" src="{{ asset(\'bundles/docova/js/barcode/'+ barcode_type +'/connectcode-javascript-'+ barcode_type +'.js\') }}"></script>\n';
			dxl += '<script type="text/javascript">\n';
			dxl += 'jQuery(function() {\n';
			dxl += 'var data = jQuery("#barcode_value").val();\n';
			switch(barcode_type)
			{
				case 'codabar':
					dxl += "var barcode_output = DrawHTMLBarcode_Codabar(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", "+ width_ratio +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code128a':
					dxl += "var barcode_output = DrawHTMLBarcode_Code128A(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code128auto':
					dxl += "var barcode_output = DrawHTMLBarcode_Code128Auto(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code128b':
					dxl += "var barcode_output = DrawHTMLBarcode_Code128B(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code128c':
					dxl += "var barcode_output = DrawHTMLBarcode_Code128C(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code39':
					dxl += "var barcode_output = DrawHTMLBarcode_Code39(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", "+ width_ratio +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code39ascii':
					dxl += "var barcode_output = DrawHTMLBarcode_Code39ASCII(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", "+ width_ratio +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'code93':
					dxl += "var barcode_output = DrawHTMLBarcode_Code93(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'ean13':
					dxl += "var barcode_output = DrawHTMLBarcode_EAN13(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'ean8':
					dxl += "var barcode_output = DrawHTMLBarcode_EAN8(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'ext2':
					dxl += "var barcode_output = DrawHTMLBarcode_EXT2(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'ext5':
					dxl += "var barcode_output = DrawHTMLBarcode_EXT5(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'gs1databar14':
					dxl += "var barcode_output = DrawHTMLBarcode_GS1Databar14(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'i2of5':
					dxl += "var barcode_output = DrawHTMLBarcode_I2OF5(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", "+ width_ratio +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'industrial2of5':
					dxl += "var barcode_output = DrawHTMLBarcode_Industrial2OF5(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'modifiedplessy':
					dxl += "var barcode_output = DrawHTMLBarcode_ModifiedPlessy(data, 0,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'uccean':
					dxl += "var barcode_output = DrawHTMLBarcode_UCCEAN(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'upca':
					dxl += "var barcode_output = DrawHTMLBarcode_UPCA(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
				case 'upce':
					dxl += "var barcode_output = DrawHTMLBarcode_UPCE(data,'"+ human_readable +"','"+ measure_unit +"', 0, "+ barcode_width +", "+ barcode_height +", '"+ readable_vallocation +"', '"+ barcode_txtalignment +"', '', '"+ fore_color +"', '"+ back_color +"');\n";
					break;
			}
			dxl += 'jQuery("#my_barcode").html(barcode_output);\n';
			dxl += '});\n</script>\n';
			dxl += '<input type="hidden" value="'+ updateTWIG(barcode_data, "output", "string") +'" id="barcode_value" />\n';
			dxl += '<div id="my_barcode"></div>';
		}
		else {
			dxl = '<script type="text/javascript" src="{{ asset(\'bundles/docova/js/barcode/qrcode/jquery.qrcode.js\') }}" ></script>\n';
			dxl += '<script type="text/javascript" src="{{ asset(\'bundles/docova/js/barcode/qrcode/qrcode.js\') }}" ></script>\n';
			dxl += '<script type="text/javascript">\n';
			dxl += 'jQuery(function(){\n';
			dxl += '	var data = jQuery.trim(jQuery("#barcode_value").val());\n';
			dxl += '	jQuery("#myQRCode").qrcode({\n';
			dxl += '		render: "'+ qrcode_render +'",\n';
			dxl += '		width: '+ barcode_width + ',\n';
			dxl += '		height: '+ barcode_height +',\n';
			dxl += '		background: "'+ back_color + '",\n';
			dxl += '		foreground: "'+ fore_color +'",\n';
			dxl += '		text: data\n';
			dxl += '	});\n';
			dxl += '});\n'
			dxl += '</script>\n';
			dxl += '<input type="hidden" value="'+ updateTWIG(barcode_data, "output", "string") +'" id="barcode_value" />\n';
			dxl += '<div id="myQRCode"></div>';
		}
	}
	
	dxl = safe_tags(dxl);
	return dxl;
}

function getWeatherDXL(obj)
{
	var obj = $(obj);
	var dxl = '';
	var name = obj.prop('id');
	var apikey = obj.attr('weatherapikey') ? $.trim(obj.attr('weatherapikey')) : '';
	var city = obj.attr('city') ? $.trim(obj.attr('city')) : '';
	var enable_forecast = obj.attr('forecastfivedays') == 'enabled' ? true : false;
	var temp_unit = obj.attr('tempratureunit') ? obj.attr('tempratureunit') : 'celsius';
	
	if (apikey && $.trim(city) != '')
	{
		var url = (docInfo.SSLState == 'ON'? 'https' : 'http') +'://api.openweathermap.org/data/2.5/weather?q="+ jQuery("#weather_city").val() +"&appid='+ apikey;
		var innerurl = (docInfo.SSLState == 'ON'? 'https' : 'http') +'://api.openweathermap.org/data/2.5/weather?q="+ jQuery("#weather_city").val() +"&appid='+ apikey;
		var symbol = '&#8490;';
		var speed = 'm/s';
		if (enable_forecast === true) {
			url = (docInfo.SSLState == 'ON'? 'https' : 'http') +'://api.openweathermap.org/data/2.5/forecast?q="+ jQuery("#weather_city").val() +"&appid='+ apikey;
		}
		if (temp_unit == 'celsius') {
			url += '&units=metric';
			innerurl += '&units=metric';
			symbol = '&#8451;';
		}
		else if (temp_unit == 'fahrenheit') {
			url += '&units=imperial';
			innerurl += '&units=imperial';
			symbol = '&#8457;';
			speed = 'mph';
		}
		dxl = '<script type="text/javascript">\n';
		dxl += 'jQuery(function(){\n';
		dxl += '	jQuery.ajax({\n';
		dxl += '		url: "'+ url +'",\n';
		dxl += '		type: "GET",\n';
		dxl += '		success: function(response){ buildWeatherContent(response); },\n';
		dxl += '		error: function() { buildWeatherContent(false); }\n';
		dxl += '	})\n';
		dxl += '	jQuery("li.navbul").on("click", function(){\n';
		dxl += '		jQuery("#weather_panels li").hide();\n';
		dxl += '		jQuery("#"+ jQuery(this).attr("ref")).css("display", "block");\n';
		dxl += '	});\n';
		dxl += '});\n';
		dxl += 'function buildWeatherContent(data) {\n';
		dxl += '	if (!data) { jQuery("#myWeatherContainer").html("<h4>Weather API not responding!</h4>"); return false; }\n';
		if (enable_forecast === true) {
			dxl += '	var fc_html = \'<h4 style="font-family:Times New Roman;font-size:1.2em;">\'+ data.city.name +\'</h4>\';\n';
			dxl += '	fc_html += \'<div style="display:flex;white-space:nowrap;margin-top:5px;">\';\n';
			dxl += '	var days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];\n';
			dxl += '	var today = new Date();\n';
			dxl += '	var today_data = null;\n';
			dxl += '	var active_item = null;\n';
			dxl += '	for (var x = 0; x < data.list.length; x++) {\n';
			dxl += '		var item = data.list[x];\n';
			dxl += '		var dt_obj = new Date(item.dt_txt);\n';
			dxl += '		if (dt_obj.getHours() < 10 || dt_obj.getHours() > 20 || (active_item && active_item.getDate() == dt_obj.getDate() && active_item.getMonth() == dt_obj.getMonth() && active_item.getYear() == dt_obj.getYear())) {\n';
			dxl += '			continue;\n';
			dxl += '		}\n';
			dxl += '		active_item = dt_obj;\n';
			dxl += '		if (dt_obj.getDate() == today.getDate() && dt_obj.getMonth() == today.getMonth() && dt_obj.getYear() == today.getYear()) {\n';
			dxl += '			today_data = item;\n';
			dxl += '		}\n';
			dxl += '		fc_html += \'<div style="float:left;margin-left:7px;padding:7px;width:100px;height:195px;overflow:hidden;box-shadow:0px 0px 7px 2px rgba(255,255,255,0.5);border-radius:5px;">\';\n';
			dxl += '		fc_html += \'<h4 style="font-size:1.2em;">\'+ days[dt_obj.getDay()] +\'</h4>\';\n';
			dxl += '		fc_html += \'<h4 style="font-size:1em;">\'+ (dt_obj.getMonth()+1) + \'/\' + dt_obj.getDate() +\'</h4>\';\n';
			dxl += '		fc_html += \'<img src="http://openweathermap.org/img/w/\'+ item.weather[0].icon +\'.png" style="height:45px;width:50px;float:left;" />\';\n';
			dxl += '		fc_html += \'<h4 style="float:left;width:50%;padding-top:5px;">\'+ Math.round(item.main.temp) +\''+ symbol +'</h4>\';\n';
			dxl += '		fc_html += \'<p style="clear:both;">\';\n';
			dxl += '		fc_html += \'<strong>\'+ item.weather[0].description +\'</strong>\';\n';
			dxl += '		fc_html += \'<span style="text-align:left;color:#EEE;display:block;margin-top:3px;padding:2px;">{% trans %}Clouds{% endtrans %}: \'+ item.clouds.all +\'%</span>\';\n';
			dxl += '		fc_html += \'<span style="text-align:left;color:#EEE;display:block;margin-top:2px;padding:2px;">{% trans %}Humidity{% endtrans %}: \'+ item.main.humidity +\'%</span>\';\n';
			dxl += '		fc_html += \'<span style="text-align:left;color:#EEE;display:block;margin-top:2px;padding:2px;">{% trans %}Wind{% endtrans %}: \'+ item.wind.speed +\''+ speed +'</span>\';\n';
			dxl += '		fc_html += \'<span style="text-align:left;color:#EEE;display:block;margin-top:2px;padding:2px;line-height:12px;">{% trans %}Min{% endtrans %}: \'+ Math.round(item.main.temp_min) +\''+ symbol +'</span>\';\n';
			dxl += '		fc_html += \'<span style="text-align:left;color:#EEE;display:block;margin-top:2px;padding:2px;line-height:12px;">{% trans %}Max{% endtrans %}: \'+ Math.round(item.main.temp_max) +\''+ symbol +'</span>\';\n';
			dxl += '		fc_html += \'</p>\';\n';
			dxl += '		fc_html += \'</div>\';\n';
			dxl += '	}\n';
			dxl += '	fc_html += \'</div>\';\n';
			dxl += '	jQuery("#forecast").html(fc_html);\n';
			dxl += '	jQuery.ajax({\n';
			dxl += '		url: "'+ innerurl +'",\n';
			dxl += '		type: "GET",\n';
			dxl += '		success: function(response){\n';
			dxl += '			if (response) {\n';
			dxl += '				var dt_obj = new Date();';
			dxl += '				var today_html = \'<h4 style="font-family:Times New Roman;font-size:1.3em;">\'+ response.name +\'</h4>\';\n';
			dxl += '				today_html += \'<div style="display:inline-block;width:140px;margin:0 auto;font-size:1em;">\';\n';
			dxl += '				today_html += \'<img src="http://openweathermap.org/img/w/\'+ response.weather[0].icon +\'.png" style="height:60px;width:60px;float:left;" />\';\n';
			dxl += '				today_html += \'<h4 style="float:left;width:50%;margin-top:20px;font-size:1.2em;">\'+ Math.round(response.main.temp) +\''+ symbol +'</h4>\';\n';
			dxl += '				today_html += \'</div><table style="width:100%;table-layout:fixed;"><tr>\';\n';
			dxl += '				today_html += \'<th>\'+ response.weather[0].description +\'</th><th>\'+ (dt_obj.getMonth()+1) + \'/\' + dt_obj.getDate() +\' \'+ days[dt_obj.getDay()] +\'</th></tr>\';\n';
			dxl += '				today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Min{% endtrans %}: \'+ response.main.temp_min +\''+ symbol +'</td>\';\n';
			dxl += '				today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Max{% endtrans %}: \'+ response.main.temp_max +\''+ symbol +'</td></tr>\';\n';
			dxl += '				today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Clouds{% endtrans %}: \'+ response.clouds.all +\'%</td>\';\n';
			dxl += '				today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Humidity{% endtrans %}: \'+ response.main.humidity +\'%</td></tr>\';\n';
			dxl += '				today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Wind{% endtrans %}: \'+ response.wind.speed +\''+ speed +'</td>\';\n';
			dxl += '				today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Pressure{% endtrans %}: \'+ response.main.pressure +\'hpa</td></tr>\';\n';
			dxl += '				today_html += \'</table>\';\n';
			dxl += '			} else {\n';
			dxl += '				var today_html = \'<h4>Weather API could not load today info!</h4>\';\n';
			dxl += '			}\n';
			dxl += '			jQuery("#today").html(today_html);\n';
			dxl += '		},\n';
			dxl += '		error: function() { jQuery("#today").html(\'<h4>Weather API could not load today info!</h4>\'); }\n';
			dxl += '	});\n';
		}
		else {
			dxl += '	var days = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];\n';
			dxl += '	var dt_obj = new Date();';
			dxl += '	var today_html = \'<h4 style="font-family:Times New Roman;font-size:1.3em;">\'+ data.name +\'</h4>\';\n';
			dxl += '	today_html += \'<div style="display:inline-block;width:140px;margin:0 auto;font-size:1em;">\';\n';
			dxl += '	today_html += \'<img src="http://openweathermap.org/img/w/\'+ data.weather[0].icon +\'.png" style="height:60px;width:60px;float:left;" />\';\n';
			dxl += '	today_html += \'<h4 style="float:left;width:50%;margin-top:20px;font-size:1.2em;">\'+ Math.round(data.main.temp) +\''+ symbol +'</h4>\';\n';
			dxl += '	today_html += \'</div><table style="width:100%;table-layout:fixed;"><tr>\';\n';
			dxl += '	today_html += \'<th>\'+ data.weather[0].description +\'</th><th>\'+ (dt_obj.getMonth()+1) + \'/\' + dt_obj.getDate() +\' \'+ days[dt_obj.getDay()] +\'</th></tr>\';\n';
			dxl += '	today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Min{% endtrans %}: \'+ data.main.temp_min +\''+ symbol +'</td>\';\n';
			dxl += '	today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Max{% endtrans %}: \'+ data.main.temp_max +\''+ symbol +'</td></tr>\';\n';
			dxl += '	today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Clouds{% endtrans %}: \'+ data.clouds.all +\'%</td>\';\n';
			dxl += '	today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Humidity{% endtrans %}: \'+ data.main.humidity +\'%</td></tr>\';\n';
			dxl += '	today_html += \'<tr><td style="color:#EEE;font-size:1.1em;">{% trans %}Wind{% endtrans %}: \'+ data.wind.speed +\''+ speed +'</td>\';\n';
			dxl += '	today_html += \'<td style="color:#EEE;font-size:1.1em;">{% trans %}Pressure{% endtrans %}: \'+ data.main.pressure +\'hpa</td></tr>\';\n';
			dxl += '	today_html += \'</table>\';\n';
			dxl += '	jQuery("#today").html(today_html);\n';
		}
		dxl += '}\n';
		dxl += '</script>\n<div id="myWeatherContainer" style="display:block;width:100%;height:100%;overflow:hidden;background:#6d8093;color:#FFF;text-align:center;">\n';
		dxl += '<input id="weather_city" value="'+ updateTWIG(city, "output", "string") +'" type="hidden" />\n';
		dxl += '<ul id="weather_panels" style="list-style:none;margin:0;padding:0;height:95%;"><li id="today" style="display:block;margin:0;height:100%;padding:5px;overflow:hidden;min-height:195px;">\n';
		if (enable_forecast === true) {
			dxl += '</li><li id="forecast" style="display:none;margin:0;height:100%;padding:5px 5px 5px 0;">\n';
		}
		dxl += '</li></ul>\n';
		if (enable_forecast === true) {
			dxl += '<ul style="width:100%;height:5%;padding-bottom:7px;list-style:none;">\n';
			dxl += '<li class="navbul" style="display:inline-block;width:12px;height:12px;background-color:#EEE;border-radius:50%;cursor:pointer;" ref="today" onMouseOver="this.style.backgroundColor =\'#CCC\';" onMouseOut="this.style.backgroundColor =\'#EEE\';">&nbsp;</li>\n';
			dxl += '<li class="navbul" style="display:inline-block;width:12px;height:12px;background-color:#EEE;border-radius:50%;cursor:pointer;margin-left:3px;" ref="forecast" onMouseOver="this.style.backgroundColor =\'#CCC\';" onMouseOut="this.style.backgroundColor =\'#EEE\';">&nbsp;</li></ul>\n';
		}
		dxl += '</div>';
		dxl = safe_tags(dxl);
	}
	
	return dxl;
}

function getCounterBoxDXL(obj)
{
	var obj = $(obj);
	var dxl = '';
	var name = obj.prop('id');
	var style = obj.attr('style') ? obj.attr('style') : '';
	var etype = obj.attr('etype') ? obj.attr('etype') : 'D';
	var value = obj.attr('countervalue') ? $.trim(obj.attr('countervalue')) : '';
	var luapp = etype != 'D' && obj.attr('lunsfname') ? $.trim(obj.attr('lunsfname')) : null;
	var luview = etype != 'D' && obj.attr('luview') ? $.trim(obj.attr('luview')) : null;
	var lucolumn = etype != 'D' && obj.attr('lucolumn') ? obj.attr('lucolumn') : null;
	var action = etype != 'D' && obj.attr('lufunction') ? $.trim(obj.attr('lufunction')) : null;
	var restrictions = etype != 'D' && obj.attr('restrictions') ? obj.attr('restrictions').split(';') : null;
	var title = obj.attr('countertitle') ? $.trim(obj.attr('countertitle')) : '';
	var subtitlecolor = obj.attr('countersubtitlecolor') ? $.trim(obj.attr('countersubtitlecolor')) : null;
	var subtitlesize = obj.attr('countersubtitlesize') ? obj.attr('countersubtitlesize') : null;
	var direction = obj.attr('counterdir') && obj.attr('counterdir') == 'down' ? 'down' : 'up';
	var starton = obj.attr('counterstarton') ? obj.attr('counterstarton') : 'topview';
	var icon = obj.attr('countericon') ? $.trim(obj.attr('countericon')) : '';
	var iconpos = icon && obj.attr('countericonpos') ? obj.attr('countericonpos') : 'l';
	var iconsize = icon && obj.attr('countericonsize') ? obj.attr('countericonsize') : '';
	var unit = obj.attr('counterunit') ? $.trim(obj.attr('counterunit')) : '';
	var unitpos = unit && obj.attr('counterunitpos') ? $.trim(obj.attr('counterunitpos')) : 'prefix';
	var enablebox = obj.attr('counterframe') == 'yes' ? true : false;
	var duration = parseInt(obj.attr('counterspeed')) ? parseInt(obj.attr('counterspeed')) : null;
	var textcolor = obj.attr('countertextcolor') ? $.trim(obj.attr('countertextcolor')) : null;
	var fontsize = obj.attr('counterfontsize') ? obj.attr('counterfontsize') : null;
	
	if (etype == 'D' && value) {
		value = updateTWIG(value, "output", "string");
	}
	else if (etype == 'V' && luview) {
		var criteria = '';
		var formula = '';
		if (restrictions && restrictions.length) {
			criteria  = '{';
			for (var x = 0; x < restrictions.length; x++) {
				var attr = restrictions[x].split(',');
				criteria += "'" + attr[0] + "': VARX_"+ x +',';
				formula += 'VARX_'+ x +' := '+ attr[1]+";\n";
			}
			criteria = criteria.slice(0, -1) + '}';
		}
		
		formula += "$$DbCalculate('"+ (luapp ? luapp : docInfo.AppID) +"', '"+ luview +"', '"+ action +"', '"+ lucolumn +"'";
		if (restrictions && restrictions.length) {
			formula += ",'[DOCOVADBCALC_FUNCTION]'";
		}
		formula += ')';
		
		var LexerObj = new Lexer();
		value = LexerObj.convertCode(formula, "TWIG");
		if (restrictions && restrictions.length) {
			value = value.replace("'[DOCOVADBCALC_FUNCTION]'", criteria);
		}
		LexerObj = null;
	}
	else {
		value = null;
	}
	
	if (value) {
		title = $.trim(title) ? updateTWIG(title, "output", "string") : '';
		dxl = '<div class="docova-counterbox';
		if (enablebox === true) {
			dxl += ' counterbox-borders-on" ';
		}
		else {
			dxl += '" ';
		}
		if (style) {
			dxl += 'style="'+ style +'" ';
		}
		dxl += 'name="'+ name +'" id="'+ name +'" ';
		dxl += 'cval="'+ value +'" ctitle="'+ title +'" ';
		dxl += 'cdir="'+ direction +'" ';
		dxl += 'cstarton="'+ starton +'" ';
		if (icon) {
			dxl += 'cicon="'+ icon +'" ciconpos="'+ iconpos +'" ciconsize="'+ iconsize +'" ';
		}
		if (unit) {
			dxl += 'cunit="'+ unit +'" cunitpos="'+ unitpos +'" ';
		}
		if (duration) {
			dxl += 'cspeed="'+ duration +'" ';
		}
		if (textcolor) {
			dxl += 'ctextcolor="'+ textcolor +'" ';
		}
		if (fontsize) {
			dxl += 'cfontsize="'+ fontsize +'" ';
		}
		if (subtitlecolor) {
			dxl += 'csubtitlecolor="' + subtitlecolor + '" ';
		}
		if (subtitlesize) {
			dxl += 'csubtitlesize="' + subtitlesize + '" ';
		}
		dxl += '></div>';
		
		dxl = safe_tags(dxl);
	}
	return dxl;
}


function htmlTree(obj, mode, ismobile){
	if(typeof ismobile == "undefined"){var ismobile = false;}
	
    var obj = obj || document.getElementsByTagName('body')[0];
  	var str="" ;

  	if ( obj.nodeType != 3 ) {
  		$(obj).removeClass('selected');
   	 	 if ( obj.tagName.toUpperCase() == "INPUT" && $(obj).attr("elem") == "text"){
   	 		 if ($(obj).attr('textrole') == 'names' || $(obj).attr('textrole') == 'a' || $(obj).attr('textrole') == 'r')
   	 			 str = InputNamesDXL(obj, mode);
   	 		 else
   	 			 str = handleInputText( obj, mode );
   		}else if ( obj.tagName.toUpperCase() == "INPUT" && $(obj).attr("elem") == "date" ){
   			str = handleDateText( obj, mode );
   		}else if ( obj.tagName.toUpperCase() == "BUTTON" && $(obj).attr("elem") == "button" ){
   			str = handleButtonText( obj, mode );
   		}else if ( obj.tagName.toUpperCase() == 'BUTTON' && $(obj).attr('elem') == 'picklist'){
   			str = handlePicklistText(obj, mode);
   		}else if ( obj.tagName.toUpperCase() == "IMG" && $(obj).attr("elem") == "image" ){
   			str = handleObjText( obj, mode );
   		}else if ( obj.tagName.toUpperCase() == "INPUT" &&   $(obj).attr("elem") == "ctext" ){   		
   			str = handleComputedText( obj, mode );
   		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "htmlcode" ){
   			str = handleManualHtmlText( obj, mode );
			return str;
   		}else if (  obj.tagName.toUpperCase() == "LABEL"  ) {
   			str = handleLabelDXL( obj, mode );
		}else if ( obj.tagName.toUpperCase() == "INPUT" &&(  $(obj).attr("elem")=="chbox" ||  $(obj).attr("elem")=="rdbutton" )){
  			str =  handleCheckBox( obj, mode );
  		}else if (obj.tagName.toUpperCase()== "INPUT" && $(obj).attr("elem") == "select") {
  			str = InputKeywordsDXL(obj, mode );
  		}else if (obj.tagName.toUpperCase() == 'INPUT' && $(obj).attr('elem') == 'toggle') {
  			str = handleToggleSwitchDXL(obj, mode);
  		}else if (obj.tagName.toUpperCase() == 'DIV' && $(obj).attr('elem') == 'slider') {
  			str = handleSliderElement(obj, mode);
  		}else if ( obj.tagName.toUpperCase() == "TEXTAREA"  && $(obj).attr("elem") == "tarea" ){
  			str = InputTextAreaDXL(obj, mode );
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "attachments" ){
  			str = AttachmentsDXL(obj, mode);
  		}else if (obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == 'googlemap') {
  			str = GoogleMapDxl(obj);
  		}else if (obj.tagName.toUpperCase() == 'DIV' && $(obj).attr('elem') == 'appelement') {
  			str = getAppElementDXL(obj);
  		}else if (obj.tagName.toUpperCase() == 'DIV' && $(obj).attr('elem') == 'barcode') {
  			str = getBarcodeDXL(obj);
  		}else if (obj.tagName.toUpperCase() == 'DIV' && $(obj).attr('elem') == 'weather') {
  			str = getWeatherDXL(obj);
  		}else if (obj.tagName.toUpperCase() == 'DIV' && $(obj).attr('elem') == 'counterbox') {
  			str = getCounterBoxDXL(obj);
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "relateddocuments" ){
  			str = sectionDXL(obj, "sfDocSection-RelatedDocuments", mode)
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "relatedemails" ){
  			str = sectionDXL(obj, "sfDocSection-FormRelatedEmails", mode)
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "auditlog" ){
  			//str = sectionDXL(obj, "sfDocSection-AppFormAuditLog", mode);
  			str = auditlogDXL ( obj, mode );
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "deditor" ){
  			str = sectionDXL(obj, "sfDocSection-DocovaEditor", mode)
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "embeddedview" ){
  			str = embeddedViewDXL(obj, mode);
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "outline" ){
  			str = outlineDXL(obj);  
 		}else if ( obj.tagName.toUpperCase() == "SPAN" && $(obj).attr("elem") == "chart" ){
  			str = chartDXL(obj, mode);  			  			
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "subform" ){
  			str = subformDXL(obj, mode, ismobile);
  		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "reditor" ){
  			str = sectionDXL(obj, "sfDocSection-RTEditor", mode)
// 		}else if ( obj.tagName.toUpperCase() == "DIV" && $(obj).attr("elem") == "teditor" ){
// 			str = sectionDXL(obj, "sfDocSection-TextEditor")
  		}else if (obj.tagName.toUpperCase() == "TH" && $(obj).hasClass("ui-resizable")){
  			//this is to ignore the resize handles that designer creates for td's

  			str = getTableTHDXL (obj);
  		}else if (obj.tagName.toUpperCase() == "BR"){
  			var tempobj = jQuery(obj);
  			var hide = handleHideWhenDXL(tempobj, mode);
			if ( hide != "")
				str += hide;
			str += "&lt;" + obj.tagName 			
			str += " " + handleAttributes(obj, mode);  		
			str +=  "&gt;";	
			if ( hide != "")
				str += "{% endif %}\r"
  		}else{
			//-- need to add a wrapper div for controlled access section
  			if (obj.tagName.toUpperCase() == "DIV" && ($(obj).attr("enablecasection") == "1" || $(obj).attr("expandcollapse") == "1")){
  				str += "&lt;div class='cacontainer' id='cacontainer" + getRandomID() + "'&gt;";
  				if ($.trim($(obj).attr('sectionformula')) != '') {
  					str += updateTWIG($(obj).attr('sectionformula'), "set", "string");
  					str += '{% set casectionoutput = __dexpreres %}\r\n';
  					str += '{% set sectionaccess = 0 %}\r\n';
  					str += "{% if user.getUserName() in casectionoutput or user.getUserNameDnAbbreviated() in casectionoutput %}\r\n";
  					str += '	{% set sectionaccess = 1 %}\r\n';
  					str += '{% else %}\r\n';
  					str += '	{% for urole in user.getRoles if urole in casectionoutput %}\r\n';
  					str += '		{% set sectionaccess = 1 %}';
  					str += '	{% endfor %}';
  					str += '{% endif %}';
  				}
  			}

			str += "&lt;" + obj.tagName
			var extraAttr = null;
			var temphw = handleHideWhenDXL(obj, mode, true);
			if(temphw && temphw != ""){
				temphw += 'display:none';
				temphw += '{% endif %}';
				extraAttr = {'STYLE' : temphw}
			}
			if ($.trim($(obj).attr('sectionformula')) != '') {
				str += " sectionaccess='{{ sectionaccess }}' ";
			}
			str += " " + handleAttributes(obj, mode, extraAttr); 
			str +=  "&gt;";	
			
  			var retval = "";	  			 			
  		    if (obj.hasChildNodes()) {
    		      var child = obj.firstChild;
    		      while (child) {
    		          retval = htmlTree(child, mode, ismobile);
    		          if ( jQuery.trim(retval) != "" ){
    		         	 str +=retval + "\r\n";
    		          }
    		        child = child.nextSibling;
    		      }
    		    }  			  			  			

  		    if(obj.tagName.toUpperCase() !== "BR"){
  		    	str += "&lt;/" + obj.tagName+ "&gt;";
  		    }

  		    //-- need to add a wrapper div for controlled access section
  			if (obj.tagName.toUpperCase() == "DIV" && ($(obj).attr("enablecasection") == "1" || $(obj).attr("expandcollapse") == "1")){
  				str += "&lt;/div&gt;";
  			} 		    
		}
	}else{
		if( obj.nodeType == 3 ){
			str += safe_tags(obj.nodeValue.replace(/\s/g, '&nbsp;'));
		}
		//** Should be removed if the else section is irelevant
		else if (obj.parentNode.innerHTML == obj.nodeValue ){
			str = obj.nodeValue;
		}
	}
		
    return str;
}

function setSectionProperties()
{
	var type = $(currElem).attr("elem");
	
	//read all propertes for attachments, mius the radio buttons..we will deal with them separately
	$("#attr_" + type).find("input,select").each( function() 
	{
		try{ 
			$(currElem).get(0).setAttribute($(this).attr("name") + "_prop", Docova.Utils.getField($(this).attr("name")))
			if ( $(this).attr("name") == "AttachmentsWidthValue" )
			{
				var attachDiv = $("div[elem='attachments']");
				attachDiv.width ( Docova.Utils.getField($(this).attr("name")) );
			}
			
			if ( $(this).attr("name") == "AttachmentsHeightValue" )
			{
				var attachDiv = $("div[elem='attachments']");
				var listtype = $(currElem).attr("listtype_prop");
			
				if ( listtype == "I" )
					attachDiv.height ( Docova.Utils.getField($(this).attr("name")) );
				else
					attachDiv.find("tbody").height(Docova.Utils.getField($(this).attr("name")) );
			}
			
		}catch(e){ alert(e) }
	
	});
}

function setProperties() {
	pushUndo();
	//---Set placeholder text for some element types text,chbox,rdbutton,select,date
	var cElem = $(currElem).attr("elem");
	if(cElem == "text" || cElem == "chbox" || cElem == "rdbutton" || cElem == "select" || cElem == "date" || cElem == "tarea"){
		$(currElem).prop("placeholder", $("#element_id").val());
	}
	
	//--- catch case of overall form properties selected
	if(!currElem && $("#divContentSection").hasClass("app-form")){
		$("#pageProperties").attr("titleformula", Docova.Utils.getField({ field: "TitleFormula" }));		
	}
	
	//---General properties
	$(currElem).prop("id", $("#element_id").val());
	$(currElem).prop("name", $("#element_id").val());
	$(currElem).attr("elem_index", $("#elem_index").val());
	//handle the current elements' class attributes
	if($(currElem).attr("htmlClass") == "undefined" || $(currElem).attr("htmlClass") == null){
		var currHTMLClass	 = "";
	}else{
		var currHTMLClass = $(currElem).attr("htmlClass")	
	}
	var currHTMLClassArray = currHTMLClass.split(" ")
	//remove any extra spaces before/after and in between the new class(es)
	var newHTMLClass = $("#htmlClass").val()
	newHTMLClass = newHTMLClass.replace( /\s\s+/g, ' ' ) //get rid of multi spaces in between
	newHTMLClass = $.trim(newHTMLClass) //get rid of leading trailing spaces
	var newHTMLClassArray = newHTMLClass.split(" ")
	//remove all the older classes from the element
	for(var x=0; x<currHTMLClassArray.length;x++){
		$(currElem).removeClass(currHTMLClassArray[x]);
	}
	//add all the new classes to the elements
	for(var y=0;y<newHTMLClassArray.length;y++){
		$(currElem).addClass(newHTMLClassArray[y]);
	}
	//set the new class attribute for the element
	$(currElem).attr("htmlClass", newHTMLClass);
	$(currElem).attr("htmlOther", $("#htmlOther").val());
	
	//---if elem is null, this is a td---
	if($(currElem).attr("elem") == null || $(currElem).attr("elem") == "undefined"){
		//Can set manual entry attributes for tds here
	}
	
	//---check to see if there are any tiedto elements that need to be changed
	//var oldID = $("#element_id").prop("defaultValue")
	//if($("#element_id").val() != oldID){
	//	$("[tiedto=" + oldID + "]").each(function(){
	//		$(this).attr("tiedto", $("#element_id").val());
	//	})
	//}

	if($(currElem).attr("elem") == "datatable")
	{
		var fld = $(currElem).find(".dtvaluefield")
		fld.attr("id", $("#element_id").val() + "_values");
		fld.attr("name", $("#element_id").val() + "_values");

		$(currElem).attr("showfooter", $("[name='datatableShowFooter']").is(":checked") ? "1" : "");
		$(currElem).attr("dttype", Docova.Utils.getField("datatableType"));
		$(currElem).attr("dtform", $("#datatableForm").val());
	}
	
	//---Label related attributes---
	if($(currElem).attr("elem") == "label"){
		$(currElem).text($("#label_text").val())
		$(currElem).prop("title", $("#label_helpertext").val())
	}

	if ( $(currElem).attr("elem") == "singleuploader"){
		$(currElem).css("height", $("#uploader_singleup_height").val())
	}
	
	//embedd view attributes
	if($(currElem).attr("elem") == "embeddedview"){
		$(currElem).height($("#embHeight").val() +"px" );
		$(currElem).attr("embviewheight", $("#embHeight").val() );
		$(currElem).attr("embviewname", $("#selectEmbView").val());
		$(currElem).attr("eViewType", $("#ViewType").val());
		$(currElem).attr("fformula", $("#restrictCategory_formula").val());
		$(currElem).attr("emb_view_filter", $("[name=embviewfilter]:checked").val() );
		$(currElem).attr("luservername", $("#embLUServerName").val());
		$(currElem).attr("dspEmbViewAppTitle", $("#dspEmbViewAppTitle").text());
		$(currElem).attr("lunsfname", $("#embLUApplication").val());
		$(currElem).attr("fdefault", $("#selectEmbView_formula").val());
		if ( $("#EmbViewSearch").is(":checked") ){
			$(currElem).attr("embviewsearch", "1")
		}else{
			$(currElem).attr("embviewsearch", "0")
		}

		if ($("[name=embviewfilter]:checked").val() == 'f')
		{
			var restrictions = "";
			$('#embrestriction_container input.restrictcolumn').each(function(i) {
				if ($(this).val() && $.trim($(this).val()) != '') {
					var value = '';
					if ($.trim($('#embrestriction_container input.restrictvalue:eq('+ i +')').val()) != '') {
						value = $.trim($('#embrestriction_container input.restrictvalue:eq('+ i +')').val());
					}
					restrictions += $.trim($(this).val()) + ',' + value + ';';
				}
			});
			if (restrictions != '') {
				restrictions = restrictions.slice(0, -1);
				$(currElem).attr('restrictions', restrictions);
			}
		}
		else {
			$(currElem).removeAttr('restrictions');
		}
	}
	
	//---Input text field attributes
	if($(currElem).attr("elem") == "text"){
		var widthvar = $("#text_width").val() || "";
		$(currElem).css("width", (widthvar.indexOf("%")>0 ? widthvar :  widthvar + "px"));
		$(currElem).attr("maxlength", $("#text_maxchars").val());
		$(currElem).attr("ftranslate", safe_quotes_js(safe_tags($("#text_translate").val()), false));
		$(currElem).attr("fplaceholder", safe_tags($("#text_placeholder").val()));
		$(currElem).attr('namesformat', $('#namesDisplayFormat').val());
		var isrequired = $('#text_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		var tr = $("#text_role").val() || "";
		$(currElem).attr("textrole", tr);
		var tt = $("#text_type").val() || "";
		$(currElem).attr("texttype", tt);
		$(currElem).attr("textalign", $("#text_align").val());
		var np = "";
		if((tr == "names" || tr == "a" || tr == "r" ) && tt == "e"){
			np = ($("[name=text_namepicker]:checked").length > 0 ? "1" : "");
		}
		$(currElem).attr("textnp", np);
		$(currElem).attr('etype', $('#textEntryType').val());
		$(currElem).attr('restrictions', '');
		
		var mv = $("[name=text_mv]:checked");
		$(currElem).attr("textmv", (mv === undefined || mv.length === 0 ? "" : "true"));
		var sep = Docova.Utils.getField({field: "text_sep", separator: " "});
		$(currElem).attr("textmvsep", (mv === undefined ? "" : (sep === "" ? "" : sep)));
		var sep = Docova.Utils.getField({field: "text_disep"});
		$(currElem).attr("textmvdisep", (mv === undefined ? "" : (sep === "" ? "" : sep)));
		
		if (mv !== undefined && mv.length) {
			var heightvar = $('#text_height').val() || '';		
			$(currElem).css('height', (heightvar.indexOf("%") > 0 ? heightvar : (heightvar != "" ? heightvar + "px" : 'auto')));		
		}
		else {
			$(currElem).css('height', 'auto');
		}

		if ($('#textEntryType').val() == 'V') {
			$(currElem).removeAttr("fdefault");
			$(currElem).attr('textSelectionType', $('#textSelectionType').val());
			if ($('#textSelectionType').val() == 'dblookup') {
				$(currElem).attr('dspTextAppTitle', $('#dspTextAppTitle').text());
				$(currElem).attr('lunsfname', $('#textLUApplication').val());
				$(currElem).attr('luview', $('#textLUView').val());
				$(currElem).attr('lucolumn', $('#textLUColumn').val());

				if (mv !== undefined || mv.length !== 0) {
					$(currElem).attr('text_LUKey', $('#textLUKey').val());
				}
			}
			else if ($('#textSelectionType').val() == 'dbcalculate') {
				$(currElem).attr('lufunction', $('#textFunction').val());
				$(currElem).attr('fnCalculationType', $('#textCalculationType').val());
				if ($('#textCalculationType').val() == 'V') {
					$(currElem).attr('dspTextAppTitle', $('#dspTextAppTitle').text());
					$(currElem).attr('lunsfname', $('#textLUApplication').val());
					$(currElem).attr('luview', $('#textLUView').val());
					$(currElem).attr('lucolumn', $('#textLUColumn').val());
					$(currElem).removeAttr('embViewRef');
					$(currElem).removeAttr('linkedEmbRefreshScript');

					var restrictions = "";
					$('#numrestriction_container input.restrictcolumn').each(function(i) {
						if ($(this).val() && $.trim($(this).val()) != '') {
							var value = '';
							if ($.trim($('#numrestriction_container input.restrictvalue:eq('+ i +')').val()) != '') {
								value = $.trim($('#numrestriction_container input.restrictvalue:eq('+ i +')').val());
							}
							restrictions += $.trim($(this).val()) + ',' + value + ';';
						}
					});
					if (restrictions != '') {
						restrictions = restrictions.slice(0, -1);
						$(currElem).attr('restrictions', restrictions);
					}
				}
				else if ($('#textCalculationType').val() == 'E') {
					$(currElem).attr('embViewRef', $('#textLinkedEmbView').val());
					$(currElem).attr('lucolumn', $('#textLUColumn').val());
					$(currElem).attr('linkedEmbRefreshScript', safe_quotes_js(safe_tags($('#textEmbViewRefreshCode').val()), true));
					$(currElem).removeAttr('lunsfname');
					$(currElem).removeAttr('luview');
					$(currElem).removeAttr('text_LUKey');
					$(currElem).removeAttr('restrictions');
				}
				else {
					$(currElem).removeAttr('lunsfname');
					$(currElem).removeAttr('luview');
					$(currElem).removeAttr('text_LUKey');
					$(currElem).removeAttr('restrictions');
					$(currElem).removeAttr('embViewRef');
					$(currElem).removeAttr('lucolumn');
					$(currElem).removeAttr('linkedEmbRefreshScript');
				}
			}
			else {
				$(currElem).attr('dspTextAppTitle', $('#dspTextAppTitle').text());
				$(currElem).removeAttr('text_LUKey');
				$(currElem).removeAttr('lufunction');
				$(currElem).removeAttr('restrictions');
				$(currElem).removeAttr('embViewRef');
				$(currElem).removeAttr('linkedEmbRefreshScript');
				if (mv == undefined || mv.length == 0) {
					$(currElem).removeAttr('lunsfname');
					$(currElem).removeAttr('luview');
					$(currElem).removeAttr('lucolumn');
				}
				else {
					$(currElem).attr('lunsfname', $('#textLUApplication').val());
					$(currElem).attr('luview', $('#textLUView').val());
					$(currElem).attr('lucolumn', $('#textLUColumn').val());
				}
			}
		}
		else {
			$(currElem).attr("fdefault", $("#text_value").val());
			$(currElem).removeAttr('dspNumAppTitle');
			$(currElem).removeAttr('lunsfname');
			$(currElem).removeAttr('luview');
			$(currElem).removeAttr('lucolumn');
			$(currElem).removeAttr('lufunction');
			$(currElem).removeAttr('text_LUKey', '');
			$(currElem).removeAttr('restrictions', '');
		}
	}
	
	//section properties
	if ( $(currElem).attr("elemtype") == "section" && $(currElem).attr("elem") != "fields"  && $(currElem).attr("elem") != "auditlog" )  {
		setSectionProperties();
	}
	
	if ( $(currElem).attr("elemtype") == "section" && $(currElem).attr("elem") == "fields" ) {
		if ( $("#EnableControlledSectionAccess").is(":checked") ){
			$(currElem).attr("enablecasection", "1" );
		}else{
			$(currElem).attr("enablecasection", "0" );
		}
		if ( $("#sectionexpandcollapse").is(":checked") )
		{
			$(currElem).attr("expandcollapse", "1" );
			//$(currElem).addClass('sectioncontent');
			$(currElem).attr("caheaderwidth", Docova.Utils.getField("caheading_width"));
		}else{
			$(currElem).attr("expandcollapse", "0" );
			$(currElem).attr("caheaderwidth", "");
			//$(currElem).removeClass('sectioncontent');
		}
		$(currElem).attr("sectionformula", $("#caccess_formula").val());
		$(currElem).attr("sectiontitle", $("#caTitle").val());
		
		$(currElem).attr("cacop", Docova.Utils.getField("cacop"));
	}
	
	
	//section properties for attachments
	if ( $(currElem).attr("elemtype") == "field" && $(currElem).attr("elem") == "attachments" ) {
		setSectionProperties();
	}
	
	//---Computed text field attributes
	if($(currElem).attr("elem") == "ctext"){
		$(currElem).attr('etype', $('#ctEntryType').val());
		$(currElem).attr('restrictions', '');

		if ($('#ctEntryType').val() == 'V') {
			$(currElem).removeAttr("fformula");
			$(currElem).attr('fnCalculationType', $('#ctCalculationType').val());
			if ($('#ctCalculationType').val() == 'V') {
				$(currElem).attr('dspCtAppTitle', $('#dspCTextAppTitle').text());
				$(currElem).attr('lunsfname', $('#ctLUApplication').val());
				$(currElem).attr('luview', $('#selectCtView').val());
				$(currElem).attr('lucolumn', $('#selectCtViewColumn').val());
				$(currElem).attr('lufunction', $('#ctFunction').val());
				$(currElem).removeAttr('linkedEmbView');
				$(currElem).removeAttr('linkedEmbRefreshScript');
				var restrictions = "";
				$('#ctextrestriction_container input.restrictcolumn').each(function(i) {
					if ($(this).val() && $.trim($(this).val()) != '') {
						var value = '';
						if ($.trim($('#ctextrestriction_container input.restrictvalue:eq('+ i +')').val()) != '') {
							value = $.trim($('#ctextrestriction_container input.restrictvalue:eq('+ i +')').val());
						}
						restrictions += $.trim($(this).val()) + ',' + value + ';';
					}
				});
				if (restrictions != '') {
					restrictions = restrictions.slice(0, -1);
					$(currElem).attr('restrictions', restrictions);
				}
			}
			else if ($('#ctCalculationType').val() == 'E') {
				$(currElem).attr('embViewRef', $('#ctLinkedEmbView').val());
				$(currElem).attr('linkedEmbRefreshScript', safe_quotes_js(safe_tags($('#ctEmbViewRefreshCode').val()), false));
				$(currElem).attr('lucolumn', $('#selectCtViewColumn').val());
				$(currElem).attr('lufunction', $('#ctFunction').val());
				$(currElem).removeAttr('dspCtAppTitle');
				$(currElem).removeAttr('lunsfname');
				$(currElem).removeAttr('luview');
				$(currElem).removeAttr('restrictions');
			}
			else {
				$(currElem).removeAttr('linkedEmbView');
				$(currElem).removeAttr('linkedEmbRefreshScript');
				$(currElem).removeAttr('lufunction');
				$(currElem).removeAttr('dspCtAppTitle');
				$(currElem).removeAttr('lunsfname');
				$(currElem).removeAttr('luview');
				$(currElem).removeAttr('restrictions');
			}
		}
		else {
			$(currElem).attr("fformula", $("#ctext_formula").val());
			$(currElem).removeAttr('dspCtAppTitle');
			$(currElem).removeAttr('lunsfname');
			$(currElem).removeAttr('luview');
			$(currElem).removeAttr('lucolumn');
			$(currElem).removeAttr('lufunction');
			$(currElem).removeAttr('restrictions');
		}
	}
	
	//---Tabset attributes	
	if($(currElem).attr("elem") == "tabset"){
		$(currElem).find(".ui-tabs-active a").text($("#TabLabelName").val())
	}	
	
	// JAVAD - I think this code should be removed since names and text inputs are merged in one element (we don't have elem="names" any more)
	//---Names field attributes
	if($(currElem).attr("elem") == "names"){
		$(currElem).prop("name", $(currElem).prop("id"));
		$(currElem).next("button").attr("tiedto", $("#element_id").val()) //if names field id changes, button tied to it needs to have tiedto attr changed.
		$(currElem).css("width", $("#namesWidth").val() + "px");
		$(currElem).attr("namesrole", $("#names_role").val());
		if($("#namesSelectionType").val() == "single"){
			$(currElem).next("button").removeClass("ui-icon-group").addClass("ui-icon-single")
			$(currElem).attr("namesSelectionType", "single");
		}else{
			$(currElem).next("button").removeClass("ui-icon-single").addClass("ui-icon-group")
			$(currElem).attr("namesSelectionType", "multi");			
		}		
	}
	
	//---Date field attributes
	if($(currElem).attr("elem") == "date"){
		var isrequired = $('#date_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		$(currElem).css("width", $("#dateWidth").val() + "px");
		$(currElem).attr("fdefault", $("#date_value").val());
		$(currElem).attr("ftranslate", safe_quotes_js(safe_tags($("#date_translate").val()), false));	
		$(currElem).attr("fplaceholder", safe_tags($("#date_placeholder").val()));
		$(currElem).attr("datetype", $("#date_type").val());
		
		var mv = $("[name=date_mv]:checked");
		$(currElem).attr("textmv", (mv === undefined || mv.length === 0 ? "" : "true"));
		var sep = Docova.Utils.getField({field: "date_sep", separator: " "});
		$(currElem).attr("textmvsep", (mv === undefined ? "" : (sep === "" ? "" : sep)));		
		var sep = Docova.Utils.getField({field: "date_disep"});
		$(currElem).attr("datemvdisep", (mv === undefined ? "" : (sep === "" ? "" : sep)));		
		
		if (mv !== undefined && mv.length) {
			var heightvar = $('#date_height').val() || '';		
			$(currElem).css('height', (heightvar.indexOf("%") > 0 ? heightvar : (heightvar != "" ? parseInt(heightvar) + "px" : 'auto')));		
		}
		else {
			$(currElem).css('height', 'auto');
		}
	}
	
	//---Textarea field attributes
	if($(currElem).attr("elem") == "tarea"){
		var widthvar = $("#tarea_width").val();
		$(currElem).css("width", (widthvar.indexOf("%")>0 ? widthvar :  widthvar + "px"));

		var heightvar = $("#tarea_height").val();
		$(currElem).css("height", (heightvar.indexOf("%")>0 ? heightvar :  heightvar + "px"))

		var isrequired = $('#tarea_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		$(currElem).attr("fdefault", $("#tareaDefault").val())
		$(currElem).attr("fplaceholder", ($("#tarea_placeholder").val() ? safe_tags($("#tarea_placeholder").val()) : ''));
		$(currElem).attr("editortype", Docova.Utils.getField("EditorType"));
		$(currElem).attr("editorheight", Docova.Utils.getField("tarea_height"));
		$(currElem).attr("editorwidth", Docova.Utils.getField("tarea_width"));		
		$(currElem).attr("limittextlength", (Docova.Utils.getField("limittextlength") == "1" ? "1" : "0"));
		$(currElem).attr("editorsettings", Docova.Utils.getField("rteditorsettings"));
	}
	
	//---Button attributes
	if($(currElem).attr("elem") == "button"){
		$(currElem).attr("btnLabel", $("#btn_Label").val());
		$(currElem).attr("btnText", Docova.Utils.getField("btn_ShowLabel"));
		$(currElem).attr("btnPrimaryIcon", $("#btn_PrimaryIcon").val());
		$(currElem).attr("btnSecondaryIcon", $("#btn_SecondaryIcon").val());
		$(currElem).attr("btnType", Docova.Utils.getField({ field: "btn_Type" }));
		if (Docova.Utils.getField({ field: 'btn_Type'}) == 'CMP') {
			$(currElem).attr('btnComposeForm', $('#cmpFormsList').val());
			$(currElem).attr('btnIsChild', Docova.Utils.getField('cmpIsChild') == 'yes' ? 'yes' : 'no');
			$(currElem).attr('btnInheritValues', Docova.Utils.getField('cmpInheritValues') == 'yes' ? 'yes' : 'no');
			$(currElem).removeAttr('btnGanttCatField');
			$(currElem).removeAttr('embeddedganttview');
		}
		else if (Docova.Utils.getField({ field: 'btn_Type' }) == 'GSC') {
			$(currElem).attr('btnGanttCatField', $('#ganttCatField').val());
			$(currElem).attr('embeddedganttview', $('#embeddedGanttView').val());
			$(currElem).removeAttr('btnComposeForm');
			$(currElem).removeAttr('btnIsChild');
			$(currElem).removeAttr('btnInheritValues');
		}
		else {
			$(currElem).removeAttr('btnComposeForm');
			$(currElem).removeAttr('btnIsChild');
			$(currElem).removeAttr('btnInheritValues');
			$(currElem).removeAttr('btnGanttCatField');
			$(currElem).removeAttr('embeddedganttview');
		}
		var showlabel = Docova.Utils.getField("btn_ShowLabel") == "0" ? false : true;
		$(currElem).button({
			icons: {
        		primary: $("#btn_PrimaryIcon").val(),
        		secondary: $("#btn_SecondaryIcon").val()
      		},
      		label: $("#btn_Label").val(),
      		text: showlabel 
		})
		$(currElem).addClass("selected")
		currElem = $(".selected");  //reset the element
	}
	
	//---Picklist button attributes
	if ($(currElem).attr('elem') == 'picklist') {
		$(currElem).attr('plLabel' , $("#picklist_Label").val());
		$(currElem).attr('plPrimaryIcon', $("#picklist_PrimaryIcon").val());
		$(currElem).attr("plSecondaryIcon", $("#picklist_SecondaryIcon").val());
		$(currElem).attr("plShowLabel", Docova.Utils.getField({ field: "picklist_ShowLabel" }));
		$(currElem).attr('pldialogtitle', $('#pcklt_DialogTitle').val());
		$(currElem).attr('plPrompt', $('#pcklt_Prompt').val());
		$(currElem).attr("dspPlViewAppTitle", $("#dspPicklistAppTitle").val());
		$(currElem).attr("lunsfname", $("#picklistLUApplication").val());
		$(currElem).attr("luview", $("#selectPicklistView").val());
		$(currElem).attr('plrestrictTo', $('#pl_restrictto').val() ? safe_tags($("#pl_restrictto").val()) : '');
		$(currElem).attr('plAction', $('#picklistAction').val());
		$(currElem).attr('plRefreshEmbView', $('#plTiedupEmbView').val() ? $('#plTiedupEmbView').val() : '');
		var showlabel = Docova.Utils.getField("picklist_ShowLabel") == "0" ? false : true;

		//remove all pltf_* and plsf_* attributes first
		$.each($(currElem).get(0).attributes, function(){
			if (this.name.indexOf('pltf_') == 0 || this.name.indexOf('plsf_') == 0) {
				$(currElem).removeAttr(this.name);
			}
		});
		
		//if picklist action is Duplicate (Copy), add the pltf_* attributes
		if ($('#picklistAction').val() == 'D') {
			$(currElem).attr('luform', $('#selectPicklistForm').val());
			$('input.allelements').each(function(){
				if ($(this).prop('id')) {
					var value = '';
					if ($.trim($(this).val()) && $.trim($(this).val()).toUpperCase() != '') {
						value = safe_quotes_js(safe_tags($(this).val()), false);
					}
					else {
						value = $(this).prop('id');
					}
					
					$(currElem).attr('pltf_' + $(this).prop('id'), value);
				}
			});
		}
		else {
			$(currElem).attr('luform', '');
			$('input.sourceelemnts').each(function(i){
				if ($.trim($(this).val()) != '') {
					var value = '';
					if ($.trim($('input.targetvalue:eq('+ i +')').val()) != '') {
						value = safe_quotes_js(safe_tags($.trim($('input.targetvalue:eq('+ i +')').val())), false);
					}
					$(currElem).attr('plsf_' + $.trim($(this).val()), value);
				}
			});
		}
		
		$(currElem).button({
			icons: {
        		primary: $("#picklist_PrimaryIcon").val(),
        		secondary: $("#picklist_SecondaryIcon").val()
      		},
      		label: $("#picklist_Label").val(),
      		text: showlabel 
		})
		$(currElem).addClass("selected");
		currElem = $(".selected");  //reset the element
	}
	
	//---Image attributes
	if($(currElem).attr("elem") == "image"){
		$(currElem).css("width", $("#imageWidth").val() + "px")
	}
	
	//---Outline attributes
	if($(currElem).attr("elem") == "outline"){
		$(currElem).attr("width", $("#outlineWidth").val());
		$(currElem).attr("height", $("#outlineHeight").val());
		$(currElem).attr("expand", $("#outlineExpand").val() );
	}
	
	//Chart attributes
	if($(currElem).attr("elem") == "chart"){
		$(currElem).attr("charttitle", $("#chartTitle").val());
		$(currElem).attr("charthorzlabel", $("#chartHorzLabel").val());
		$(currElem).attr("chartvertlabel", $("#chartVertLabel").val());
		$(currElem).attr("charthidevalues", ($("#chartHideValues").prop("checked") ? "1" : ""));
		$(currElem).attr("charthidelegend", ($("#chartHideLegend").prop("checked") ? "1" : ""));
		
		$('span[chartlabel="chartlabel"]', $(currElem)).html('Chart - ' + $("#element_id").val());
		
		var cw = ($("#chartWidth").val() || "400");
		var ch = ($("#chartHeight").val() || "200");

		$(currElem).attr("chartwidth", cw);
		$(currElem).attr("chartheight", ch);
		
		if(cw.indexOf("px")==-1 && cw.indexOf("%")==-1){
			cw += "px";
		}		
		if(ch.indexOf("px")==-1 && ch.indexOf("%")==-1){
			ch += "px";
		}		
		$(currElem).css("width", cw);
		$(currElem).css("height", ch);
		var iconpadding = Math.max(($(currElem).height()/2).toFixed(0)-20, 0);
		$(currElem).find("span.item").css("padding-top", iconpadding.toString() + "px");
		
		$(currElem).attr("charttype", $("#chartType").val());
		
		var sourcetype = $("#chartSourceType").val();
		$(currElem).attr("chartsourcetype", sourcetype);
			
		var source = "";
		if(sourcetype  == "function"){
			source = $("#chartDataFunc").val();
			
		}else{
			source = $("#selectChartSource").val();
		}
		$(currElem).attr("chartsource", source);
		$(currElem).attr("dspChartAppTitle", $("#dspChartAppTitle").text());
		$(currElem).attr("lunsfname", $("#chartLUApplication").val());	
		
		$(currElem).attr("fformula", $("#chart_rcat_formula").val()||"");
		
		var chartlegenditems = "";
		$("#chart_legenditems").find("span.chart_drag_item").each(function(){
			chartlegenditems += (chartlegenditems == "" ? "" : ",");
			chartlegenditems += $(this).attr("sourcefield") + "|" + $(this).attr("sourcefieldtitle");
		});
		$(currElem).attr("chartlegenditems", chartlegenditems);
		
		var chartaxisitems = "";
		$("#chart_axisitems").find("span.chart_drag_item").each(function(){
			chartaxisitems += (chartaxisitems == "" ? "" : ",");
			chartaxisitems += $(this).attr("sourcefield") + "|" + $(this).attr("sourcefieldtitle");
		});
		$(currElem).attr("chartaxisitems", chartaxisitems);
		
		var chartvalueitems = "";
		$("#chart_valueitems").find("span.chart_drag_item").each(function(){
			chartvalueitems += (chartvalueitems == "" ? "" : ",");
			chartvalueitems += $(this).attr("sourcefield") + "|" + $(this).attr("sourcefieldtitle") + "|" + $(this).attr("valop");
		});
		$(currElem).attr("chartvalueitems", chartvalueitems);		
	}	
	
	//---Select/Dropdown field
	if($(currElem).attr("elem") == "select"){
		var isrequired = $('#select_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		$(currElem).css("width", $("#selectWidth").val() + "px");
		$(currElem).attr("allownewvals", (Docova.Utils.getField("selectAllowNewValues") == "1" ? "1" : ""));
		var optionmethod = $("#selectOptionMethod").val();
		optionmethod = !optionmethod ? 'manual' : optionmethod;
		$(currElem).attr("optionmethod", optionmethod); 
		$(currElem).attr("fdefault", $("#selectDefault").val()); 
		$('span[target="selectDefault"]').text($(currElem).attr("fdefault"));
		$(currElem).attr("ftranslate", safe_quotes_js(safe_tags($("#select_translate").val()), false));
		$(currElem).attr("fplaceholder", safe_tags($("#select_placeholder").val()));

		var mv = $("[name=select_mv]:checked");
		$(currElem).attr("selectmv", (mv === undefined || mv.length === 0 ? "" : "true"));
		var sep = Docova.Utils.getField({field: "select_sep", separator: " "});
		$(currElem).attr("selectmvsep", (mv === undefined ? "" : (sep === "" ? "" : sep)));		
		var sep = Docova.Utils.getField({field: "combo_disep", separator: " "});
		$(currElem).attr("selectmvdisep", (mv === undefined ? "" : (sep === "" ? "" : sep)));		

		if(optionmethod == "manual"){
			$(currElem).empty();
			var optionlist = $("#selectOptions").val().replace( /\n/g, ";")
			$(currElem).attr("optionlist", optionlist);
		}else if(optionmethod == "formula"){
			$(currElem).empty();
			$(currElem).append($("<option></option>").attr("value", "[computed]").text("[computed]"));
			$(currElem).attr("fformula", $("#selectOptions").val().replace( /\n/g, " "));			
		}else if(optionmethod == "select"){
			$(currElem).empty() //clear the selection field
			$(currElem).append($("<option></option>").attr("value", "[computed]").text("[computed]"));
			$(currElem).attr("luservername", $("#selectLUServerName").val());
			$(currElem).attr("dspSelectAppTitle", $("#dspSelectAppTitle").text())			
			$(currElem).attr("lunsfname", $("#selectLUApplication").val())			
			$(currElem).attr("luview", $("#selectLUView").val());
			$(currElem).attr('luselectiontype', $('#cmbSelectionType').val());
			$(currElem).attr("lucolumn", $("#selectLUColumn").val());
			if ($("#cmbSelectionType").val() == 'dblookup') {
				$(currElem).attr("lukeytype", $("#selectLUKeyType").val());
				$(currElem).attr("lukey", $("#selectLUKey").val());
				$(currElem).attr("lukeyfield", $("#selectLUKeyField").val());
			}
		}
	}
	
	//---Checkbox field attributes
	if($(currElem).attr("elem") == "chbox"){
		var isrequired = $('#cb_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		var optionmethod = $("#cbOptionMethod").val();
		$(currElem).attr("optionmethod", optionmethod); //set the optionmethod attribute
		$(currElem).attr('colno',  $("#cbColumns").val());
		$(currElem).prop("id", $("#element_id").val());
		$(currElem).prop("name", $("#element_id").val());

		//display separator
		var sep = Docova.Utils.getField({field: "chbox_disep"});
		$(currElem).attr("chmvdisep", (sep === "" ? "" : sep));		

		$(currElem).attr("fdefault", $("#cbDefault").val())			

		if(optionmethod == "manual"){
			$(currElem).attr("luview", ""); 
			$(currElem).attr("lucolumn","");
			$(currElem).attr("lukeytype","none");
			$(currElem).attr("lukey", "");
			$(currElem).attr("lukeyfield", "");
			$(currElem).attr("optionlist", $("#cbOptions").val().replace( /\n/g, ";"));						

			//set default val in app builder interface
			var defaultvallist = "";
			var defaultvalarray = $("#cbDefault").val().split(",");
			for(var x=0; x<defaultvalarray.length; x++){
				if(defaultvallist == ""){
					defaultvallist = $.trim(defaultvalarray[x])
				}else{
					defaultvallist += "," + $.trim(defaultvalarray[x])
				}
			}
			defaultvallist = defaultvallist.replace(/"/g, "") //removes double quotes
		}else if(optionmethod == "select"){ //using select, not manual option so set attributes for it
			$(currElem).attr("luservername", $("#cbLUServerName").val());
			$(currElem).attr("dspCBAppTitle", $("#dspCBAppTitle").val());
			$(currElem).attr("lunsfname", $("#cbLUApplication").val())		
			$(currElem).attr("luview", $("#cbLUView").val());
			$(currElem).attr("lucolumn", $("#cbLUColumn").val());
			$(currElem).attr('luselectiontype', $('#cbSelectionType').val());
			if ($('#cbSelectionType').val() == 'dblookup') {
				$(currElem).attr("lukeytype", $("#cbLUKeyType").val());
				$(currElem).attr("lukey", $("#cbLUKey").val());
				$(currElem).attr("lukeyfield", $("#cbLUKeyField").val());
			}
		}
		else if(optionmethod == "formula"){
			$(currElem).attr("fformula", $("#cbOptions").val().replace( /\n/g, " "));			
		}
		//set width after the new table
		$(currElem).css("width", $("#cbWidth").val() + "px")
		$(currElem).attr("tblWidth", $("#cbWidth").val());
		$(currElem).attr("ftranslate", safe_quotes_js(safe_tags($("#cb_translate").val()), false));
	}
	
	//---Radio field attributes
	if($(currElem).attr("elem") == "rdbutton")
	{
		var isrequired = $('#rdb_isrequired').prop('checked') ? 'Y' : '';
		$(currElem).attr('isrequired', isrequired);
		var optionmethod = $("#rbOptionMethod").val();
		$(currElem).attr("optionmethod", optionmethod); //set the optionmethod attribute
		$(currElem).attr('colno',  $("#rbColumns").val());		
		$(currElem).attr("fdefault", $("#rbDefault").val()); 
		var elemName = $("#element_id").val();
		if(optionmethod == "manual"){
			var stylestr = $(currElem).attr("style");
			$(currElem).attr("luview", ""); 
			$(currElem).attr("lucolumn","");
			$(currElem).attr("lukeytype","none");
			$(currElem).attr("lukey", "");
			$(currElem).attr("lukeyfield", "");
			$(currElem).attr("optionlist", $("#rbOptions").val().replace( /\n/g, ";"));		
		}else if(optionmethod == "select"){ //using select, not manual option so set attributes for it
			$(currElem).attr("luservername", $("#rbLUServerName").val());
			$(currElem).attr("dspRBAppTitle", $("#dspRBAppTitle").val());
			$(currElem).attr("lunsfname", $("#rbLUApplication").val())		
			$(currElem).attr("luview", $("#rbLUView").val());
			$(currElem).attr("lucolumn", $("#rbLUColumn").val());
			$(currElem).attr('luselectiontype', $('#rbSelectionType'));
			if ($('#rbSelectionType').val() == 'dblookup') {
				$(currElem).attr("lukeytype", $("#rbLUKeyType").val());
				$(currElem).attr("lukey", $("#rbLUKey").val());
				$(currElem).attr("lukeyfield", $("#rbLUKeyField").val());
			}
		}else if (optionmethod == "formula"){		
			$(currElem).attr("fformula", $("#rbOptions").val().replace( /\n/g, " "));			
		}
		//set width of new radio button table
		$(currElem).css("width", $("#rbWidth").val() + "px")
		$(currElem).attr("tblWidth", $("#rbWidth").val());

		$(currElem).attr("ftranslate", safe_quotes_js(safe_tags($("#rb_translate").val()), false));
	}
	
	if ($(currElem).attr('elem') == 'toggle')
	{
		$(currElem).attr("fdefault", $("#tgDefault").val());
		$(currElem).attr('toggleoncolor', $('#toggleOnColor').val());
		$(currElem).attr('toggleoffcolor', $('#toggleOffColor').val());
	}
	
	if ($(currElem).attr('elem') == 'slider')
	{
		$(currElem).attr('boundelem', $('#sld_bindtoelement').val());
		$(currElem).attr('minvalue', parseInt($('#sld_minvalue').val()));
		$(currElem).attr('maxvalue', parseInt($('#sld_maxvalue').val()));
		$(currElem).attr('sliderlength', $('#sld_length').val());
		$(currElem).attr('orientation', $('#sld_orientation').val());
		$(currElem).attr('activecolor', $('#sldActivationColor').val());
	}
	
	//---Subform section---
	if($(currElem).attr("elem") == "subform"){
		$(currElem).attr("subformformula", $("#subform_formula").val())
	}

	if ($(currElem).attr("elem") == "appelement") {
		$(currElem).attr('appelementsourcetype', $("#appelementSource").val());
		$(currElem).attr('appelementappid', $('#appelementLUApplication').val());
		$(currElem).attr('appelementapptitle', $('#dspAppElementTitle').val());
		$(currElem).attr('appelementsource', $('#selectAppElementSource').val());
	}
	
	if ($(currElem).attr('elem') == 'googlemap') {
		$(currElem).attr('googlemapapikey', $('#googlemapApiKey').attr('key'));
		$(currElem).attr('googlelatitude', $('#googleLatitude').val());
		$(currElem).attr('googlelongitude', $('#googleLongitude').val());
		$(currElem).attr('googlepostalcode', $('#googlePostalCode').val());
	}
	
	if ($(currElem).attr('elem') == 'barcode') {
		var barcode_type = $('#barcodeType').val();
		$(currElem).attr('barcodetype', barcode_type);
		$(currElem).attr('barcodedata', $('#barcodeData').val());
		$(currElem).attr('humanreadable', barcode_type != 'qrcode' ? $('input[name="readableValue"]:checked').val() : '');
		$(currElem).attr('readablevallocation', barcode_type != 'qrcode' ? $('input[name="readableValLocation"]:checked').val() : '');
		$(currElem).attr('barcodetxtalignment', barcode_type != 'qrcode' ? $('input[name="readableValAlignment"]:checked').val() : '');
		$(currElem).attr('measureunit', barcode_type != 'qrcode' ? $('input[name="measureUnit"]:checked').val() : '');
		$(currElem).attr('barcodewidth', $('#barcodeWidth').val());
		$(currElem).attr('barcodeheight', $('#barcodeHeight').val());
		if (barcode_type == 'codabar' ||  barcode_type == 'code39' || barcode_type == 'code39ascii' || barcode_type == 'i2of5') {
			$(currElem).attr('barcodewidthratio', $('#thicknessRatio').val());
		}
		else {
			$(currElem).attr('barcodewidthratio', '');
		}
		$(currElem).attr('barcodeforecolor', $('#barcodeForeColor').val());
		$(currElem).attr('barcodebackcolor', $('#barcodeBackColor').val());
	}
	
	if ($(currElem).attr('elem') == 'weather') {
		$(currElem).attr('weatherapikey', $('#weatherApiKey').attr('key'));
		$(currElem).attr('city', $('#cityname').val());
		$(currElem).attr('forecastfivedays', $('#forecastfivedays').prop('checked') ? 'enabled' : 'disabled');
		$(currElem).attr('tempratureunit', $('input[name="tempratureunit"]:checked').val());
	}
	
	if ($(currElem).attr('elem') == 'counterbox') {
		$(currElem).attr('etype', $('#cbEntryType').val());
		$(currElem).attr('restrictions', '');

		if ($('#cbEntryType').val() == 'V') {
			$(currElem).attr('dspCounterBoxAppTitle', $('#dspCounterBoxAppTitle').text());
			$(currElem).attr('lunsfname', $('#ctrbLUApplication').val());
			$(currElem).attr('luview', $('#selectCtrbView').val());
			$(currElem).attr('lucolumn', $('#selectCtrbViewColumn').val());
			$(currElem).attr('lufunction', $('#ctbFunction').val());

			var restrictions = "";
			$('#cbrestriction_container input.restrictcolumn').each(function(i) {
				if ($(this).val() && $.trim($(this).val()) != '') {
					var value = '';
					if ($.trim($('#cbrestriction_container input.restrictvalue:eq('+ i +')').val()) != '') {
						value = $.trim($('#cbrestriction_container input.restrictvalue:eq('+ i +')').val());
					}
					restrictions += $.trim($(this).val()) + ',' + value + ';';
				}
			});
			if (restrictions != '') {
				restrictions = restrictions.slice(0, -1);
				$(currElem).attr('restrictions', restrictions);
			}
		}
		else {
			$(currElem).attr('countervalue', $('#counterboxValue').val());			
			$(currElem).removeAttr('dspCounterBoxAppTitle');
			$(currElem).removeAttr('lunsfname');
			$(currElem).removeAttr('luview');
			$(currElem).removeAttr('lucolumn');
			$(currElem).removeAttr('lufunction');
		}
		$(currElem).attr('countertitle', $('#counterTitle').val());
		$(currElem).attr('counterdir', $('input[name="counterDir"]:checked').val());
		$(currElem).attr('counterstarton', $('#counterStartOn').val());
		$(currElem).attr('countericon', $('#counterIcon').val());
		$(currElem).attr('countericonpos', $('#counterIconPos').val());
		$(currElem).attr('countericonsize', $('#counterIconSize').val());
		$(currElem).attr('counterunit', $('#counterUnit').val());
		$(currElem).attr('counterunitpos', $('#counterUnitPos').val());
		$(currElem).attr('counterspeed', $('#counterSpeed').val());
		$(currElem).attr('counterframe', $('#counterFrame').prop('checked') ? 'yes' : '');
		$(currElem).attr('countertextcolor', $('#counterTextColor').val());
		$(currElem).attr('counterfontsize', $('#counterFontSize').val());
		$(currElem).attr('countersubtitlecolor', $('#counterSubtitleColor').val());
		$(currElem).attr('countersubtitlesize', $('#counterSubtitleSize').val());
	}

	if ($(currElem).attr('elem') == 'auditlog') 
	{
		$(currElem).attr('alogheight', $('#docova_auditlog_height').val());
		$(currElem).attr('filter', $('input[name="docova_auditlog_filter"]:checked').val() );
		$(currElem).attr('collapsible', $('#docova_auditlog_collapse').is(":checked") ? "1" : "0" );
	}
	
	//---Additional style---
	//First need to format the additional style because jQuery, if it fires, adds spaces after all colons and semicolons in the style string
	//Hence we need to follow that format to ensure we can manage the additional style properly.	
//	$("#AdditionalStyle").val(formatAdditionalStyle($("#AdditionalStyle").val())); //format additional style
	var currElemStyle = ($(currElem).attr("style") == undefined || $(currElem).attr("style") == null) ? "" : $(currElem).attr("style")
	var currElemAdditionalStyle = $(currElem).attr("additional_style");
	var tmpStyle = currElemStyle.replace(currElemAdditionalStyle, ""); //remove old additional style from style
	var newElemStyle = tmpStyle + $("#AdditionalStyle").val(); //append additional style to existing style
	$(currElem).attr("additional_style", $("#AdditionalStyle").val()); //set additional_style attrib
	$(currElem).attr("style", newElemStyle); //set style for current element
	if ($(currElem).is(':hidden')) {
		$(currElem).show();
		$(currElem).addClass('forcehide');
		$(currElem).attr('forcehide', 'yes');
	}
	else {
		$(currElem).removeClass('forcehide');
		$(currElem).removeAttr('forcehide');
	}
	
	if (typeof setWorkflowProperties === 'function') { 
		setWorkflowProperties();
	}
	setEvents();
	setHideWhenProperties();
	generateBreadCrumbs(currElem);
	$('#right-panel-tabs').tabs({ active: 0 });
	setSelectable(); 
	setSelectors();

	return;
}

//-----RIGHT CLICK CONTEXT MENUS-----
function menuHandler(event){
	var elemSource = (jQuery(event.target).closest("[elem!=''][elem]") || null);
	var elem = ($(elemSource).attr("elem") || "");
	var elemType = ($(elemSource).attr("elemtype") || "");
	
	if(elem == ""){
		//-- special case of pasting button into toolbar
		if(jQuery(event.target).prop("id") == "divToolBar" || jQuery(event.target).prop("id") == "divToolBar_m"){
			elemSource = event.target;			
			elemType = "toolbar";
		//-- special case of chart field elements
		}else if($(event.target).hasClass("chart_drag_item")){
			elemSource = event.target;			
			elemType = "chartfield";			
		}
	}	
	
	if(elemType == ""){		
		//checks for the special case of a tabset element
		if($(currElem).attr("elem") == "tabset"){ 
			fieldMenu(event, currElem);
		}

		//checks for the special case of a singleuploader
		if($(currElem).attr("elem") == "singleuploader"){ 
			fieldMenu(event, currElem);
		}

		//handle a button if the right click occurs on the inner SPAN
		if($(elemSource).parent().prop("tagName") == "BUTTON"){
			currElem = $(elemSource).parent();
			fieldMenu(event, currElem);
		}		
		//if right clicking on text in the layout in a <p> or inside b, i, or font tags around text in a <p>
		if( $(elemSource).prop("tagName") == "B" || $(elemSource).prop("tagName") == "STRONG" || $(elemSource).prop("tagName") == "U" || $(elemSource).prop("tagName") == "FONT" ){
			if($(elemSource).parent().prop("tagName") == "P"){
				elemSource = $(elemSource).parent();
				elemType = "par";
			}else{
				elemSource = $(elemSource).parentsUntil("p").parent();
				elemType = "par";
			}
		}
		//special case of I element as it can represent italics or icons
		if( $(elemSource).prop("tagName") == "I" ){
			//if this I element has font-awesome class of fa then we know its an attachments,subform type of element
			//otherwise the element is for italics
			var classStr = $(elemSource).prop("class");
			if( classStr.indexOf("fa") >= 0 ){
				currElem = $(elemSource).closest("[elemtype='field']");
				//if a field elemtype is not found, then it is a section elemtype, find the section element
				if($(currElem).attr("elemtype") == "undefined" || $(currElem).attr("elemtype") == null){
					currElem = $(elemSource).closest("[elemtype='section']");
					elemSource = currElem;
					elemType = "section";
				}else{
					elemSource = currElem;
					elemType = "field";
				}
			}else{
				if($(elemSource).parent().prop("tagName") == "P"){
					elemSource = $(elemSource).parent();
					elemType = "par";
				}else{
					elemSource = $(elemSource).parentsUntil("p").parent();
					elemType = "par";
				}
			}
		}
		//to catch a right click on the div inside attachments,embview,subform,related email, audit log
		if( $(elemSource).prop("tagName") == "DIV" && $(elemSource).hasClass("item")){
				currElem = $(elemSource).closest("[elemtype='field']");
				//if a field elemtype is not found, then it is a section elemtype, find the section element
				if($(currElem).attr("elemtype") == "undefined" || $(currElem).attr("elemtype") == null){
					currElem = $(elemSource).closest("[elemtype='section']");
					elemSource = currElem;
					elemType = "section";
				}else{
					elemSource = currElem;
					elemType = "field";
				}
		}
	}
	if(elemType == "field"){
		fieldMenu(event, elemSource);	
	}else if(elemType == "par"){
		//if elemType is par, check if par is in a td, if it is then present the table menu
		if( $(elemSource).parent().prop("tagName") == "TD"){
			tableMenu(event, elemSource);
		}
		if( $(elemSource).parent().prop("tagName") == "DIV"){
			pasteMenu(event, elemSource);
		}		
	}else if(elemType == "section"){
		sectionMenu(event, elemSource);
	}else if(elemType == "toolbar"){
		pasteMenu(event, elemSource);
	}else if(elemType == "chartfield"){
		//check for chart property field elements
		if($(elemSource).hasClass("chart_drag_item")){
			chartMenu(event, elemSource);
		}		
	}
}

function pasteMenu(event, elemSource){
	currElem = elemSource
	
	Docova.Utils.menu({
		delegate: event,
		width: 170,		
		position: "XandY",
		menus: [
				{ title: "Paste element", itemicon: "ui-icon-clipboard", action: "paste_field();" },
		]
	})
}

function fieldMenu(event, elemSource){
	currElem = elemSource;
	var elem = $(currElem).attr("elem");
	
	if(elem == "tabset"){ //tabset currently no copy
		Docova.Utils.menu({
			delegate: event,
			width: 170,		
			position: "XandY",
			menus: [
					{ title: "Delete element", itemicon: "ui-icon-close", action: "delete_field(currElem);" },
					{ title: "Cut element", itemicon: "ui-icon-scissors", action: "cut_field(currElem);" }
			]
		})
	}else if(elem == "attachments" || elem == "outline" || elem == "subform" || elem == "embeddedview" || elem == "singleuploader" || elem=="icon" || elem == "counterbox" || elem == "weather" || elem == "barcode" || elem == "googlemap" || elem == "appelement" || elem=='slider'){
		Docova.Utils.menu({
			delegate: event,
			width: 170,		
			position: "XandY",
			menus: [
					{ title: "Delete element", itemicon: "ui-icon-close", action: "delete_field(currElem);" },
					{ title: "Copy element", itemicon: "ui-icon-copy", action: "copy_field(currElem);" },
					{ title: "Cut element", itemicon: "ui-icon-scissors", action: "cut_field(currElem);" },
					{ title: "Add Line",  itemicon: "ui-icon-grip-solid-vertical",
			   			menus: [
			     			{ title: "Before", itemicon: "ui-icon-arrowstop-1-n", action: "addline_before($(currElem));" },
			     			{ title: "After", itemicon: "ui-icon-arrowstop-1-s", action: "addline_after($(currElem));" },
			  			]
			 		},							
			]
		})
	}else{
		Docova.Utils.menu({
			delegate: event,
			width: 170,		
			position: "XandY",
			menus: [
					{ title: "Delete element", itemicon: "ui-icon-close", action: "delete_field(currElem);" },
					{ title: "Copy element", itemicon: "ui-icon-copy", action: "copy_field(currElem);" },
					{ title: "Cut element", itemicon: "ui-icon-scissors", action: "cut_field(currElem);" }
			]
		})
	}
}

function sectionMenu(event, elemSource){
	currElem = elemSource;
	Docova.Utils.menu({
		delegate: event,
		width: 170,
		menus: [
			{ title: "Delete section", itemicon: "ui-icon-close", action: "delete_section(currElem);" },
		]
	})
}

function tableMenu(event, elemSource){
	currElem = elemSource;
	var sh_rows = true;
	var parenttable = $(elemSource).closest("table");
	if ( parenttable.parent().attr("elem") == "datatable")
	{
		if ( $(elemSource).parent().parent().parent().prop("tagName") != "TFOOT")
		{
			sh_rows = false;				
		}
	}

	var menurow = [];
	if ( sh_rows )
	{
		menurow.push ({ title: "Rows", itemicon: "ui-icon-grip-solid-horizontal",
			  menus: [
			     { title: "Insert", itemicon: "ui-icon-arrowstop-1-n", action: "insert_row($(currElem));" },
			     { title: "Delete", itemicon: "ui-icon-close", action: "delete_row($(currElem));" },
			     { title: "Append", itemicon: "ui-icon-arrowstop-1-s", action: "append_row($(currElem));" },
			  ]
			} );
	}

	menurow.push ({ title: "Columns",  itemicon: "ui-icon-grip-solid-vertical",
			   menus: [
			     { title: "Delete", itemicon: "ui-icon-close", action: "delete_column($(currElem));" },
			     { title: "Append", itemicon: "ui-icon-arrowstop-1-e", action: "append_column($(currElem));" },
			  ]
			 });

	menurow.push ({ title: "Cells", itemicon: "ui-icon-circlesmall-plus",
			   menus: [
			     { title: "Merge right", itemicon: "ui-icon-arrow-1-e", action: "merge_right($(currElem));" },
			     { title: "Merge cells", itemicon: "ui-icon-arrow-2-e-w", action: "merge_cells($(currElem));" },
			     { title: "Split cell", itemicon: "ui-icon-arrow-2-e-w", action: "split_cell($(currElem));" },			     
			  ]
			 });

	menurow.push ({ title: "Add Line",  itemicon: "ui-icon-grip-solid-vertical",
			   menus: [
			     { title: "Before table/tabs", itemicon: "ui-icon-arrowstop-1-n", action: "addline_before($(currElem));" },
			     { title: "After table/tabs", itemicon: "ui-icon-arrowstop-1-s", action: "addline_after($(currElem));" },
			  ]
			 });

	menurow.push ({ title: "Paste element", itemicon: "ui-icon-clipboard", action: "paste_field();" });

	Docova.Utils.menu({
		delegate: event,
		position: "XandY",
		menus: menurow
	})
}

function chartMenu(event, elemSource){
	if(!jQuery(elemSource).hasClass("chart_drag_item")){
		return false;
	}
	
	var itemid = jQuery(elemSource).prop("id");
	
	var menuitems = [];
	menuitems.push({ title: "Remove field", itemicon: "ui-icon-close", action: 'chartMenuAction("#' + itemid + '", "remove");' });
	
	var valop = jQuery(elemSource).attr("valop");
	if(valop !== null && valop != ""){
		menuitems.push({ title: "Sum", itemicon: "", disabled : (valop == "sum"), action: 'chartMenuAction("#' + itemid + '", "sum");' });
		menuitems.push({ title: "Count", itemicon: "", disabled : (valop == "count"), action: 'chartMenuAction("#' + itemid + '", "count");' });
		menuitems.push({ title: "Average", itemicon: "", disabled : (valop == "average"), action: 'chartMenuAction("#' + itemid + '", "average");' });
		menuitems.push({ title: "Min", itemicon: "", disabled : (valop == "min"), action: 'chartMenuAction("#' + itemid + '", "min");' });		
		menuitems.push({ title: "Max", itemicon: "", disabled : (valop == "max"), action: 'chartMenuAction("#' + itemid + '", "max");' });
	}
	
	Docova.Utils.menu({
		delegate: event,
		width: 170,
		menus: menuitems
	});
}

function chartMenuAction(itemid, action){
	if(typeof itemid !== "string" || itemid == "" || typeof action !== "string" || action == ""){
		return false;
	}
	if(itemid.substr(0, 1) !== "#"){
		itemid = "#" + itemid;
	}
	
	var elemobj = jQuery(itemid);
	if(elemobj.length == 0){
		return false;		
	}
	if(!jQuery(elemobj).hasClass("chart_drag_item") || jQuery(elemobj).hasClass("chart_source_item")){
		return false;
	}
	
	if(action == "remove"){
		jQuery(elemobj).remove();
	}else{
		var valop = action.toLowerCase();
		var newname = jQuery(elemobj).attr("sourcefieldtitle");
		newname = valop.substr(0,1).toUpperCase() + valop.substr(1).toLowerCase()  + " of " + newname;
		jQuery(elemobj).text(newname).attr("valop", valop);		
	}
	 setProperties();
	
}

function generateBreadCrumbs(elemObj){
	var bcPath = "";
	var cnt = 0;
	$("[bc]").removeAttr("bc") //remove any existing bc attributes on elements before setting new ones.
	$(elemObj).attr("bc", cnt);
	
	//Set width of breadcrumb area
	if ($(".ui-layout-south").length) {
		var bcWidth = $(".ui-layout-south").width() - 50
	}
	else {
		var bcWidth = $(".ui-layout-center").width() - 40
	}
	$("#eBreadCrumbs").css("width",  bcWidth + "px")

	if($(elemObj).attr("elem") == "chbox"){
		var useName = $(elemObj).prop("name")
		bcPath = "<span class='breadcrumb' bcIndex='" + cnt + "'>" + "CHECKBOX" + "#" + useName + "</span>"
	}else if($(elemObj).attr("elem") == "rdbutton"){
		var useName = $(elemObj).find("[type=radio]").first().prop("name")
		bcPath = "<span class='breadcrumb' bcIndex='" + cnt + "'>" + "RADIOBUTTON" + "#" + useName + "</span>"
	}else if($(elemObj).attr("elem") == "ctext"){
		bcPath = "<span class='breadcrumb' bcIndex='" + cnt + "'>" + "COMPUTEDTEXT" + "#" + $(elemObj).prop("id") + "</span>"
	}else{
		bcPath = "<span class='breadcrumb' bcIndex='" + cnt + "'>" + $(elemObj).prop("tagName") + "#" + $(elemObj).prop("id") + "</span>"
	}
	
	$(elemObj).parentsUntil( "#layout,#layout_m" ).each(function(){
		cnt ++;
		$(this).attr("bc", cnt);
		bcPath = "<span class='breadcrumb' bcIndex='" + cnt + "'>" + $(this).prop("tagName") + "#" + $(this).prop("id") + "</span>" + bcPath;
	});
	$("#eBreadCrumbs").children().remove();
	$("#eBreadCrumbs").append(bcPath);
	$(".breadcrumb").mouseenter( function(){ $(this).addClass("breadcrumbhighlight") } )
	$(".breadcrumb").mouseout( function(){ $(this).removeClass("breadcrumbhighlight") } )
	$(".breadcrumb").click(function(event){
		event.stopPropagation();
		$("#eBreadCrumbs").children().removeClass("breadcrumbselected");
		$(this).addClass("breadcrumbselected")
		$(".selected").removeClass("selected");
		var bcIndex = $(this).attr("bcIndex");
		$("[bc=" + bcIndex +"]").addClass("selected");
		currElem = $("[bc=" + bcIndex +"]") //sets the current element based on the bc that was selected
		getProperties();
		setSelectors();
	});
	
	return;
}

function getIconHTML()
{
	var x = findValidElemIndex(':input', 'icon_');
	var IDName = "icon_" + x;
	var strHTML = "<span class='selectable fas fa-star' id='" + IDName + "' ondrop='return false;' value='' style='font-size:2em' elem='icon' elemtype='field' fdefault='' ></span><span>&nbsp;</span>" 
	return strHTML;
}

function getInputFieldHTML(type){
	var x = findValidElemIndex(':input', 'txt_input_');
	var IDName = "txt_input_" + x;
	var strHTML = "<input class='selectable' id='" + IDName + "' textrole='"+ type +"' name='' placeholder='" + IDName + "' etype='D' ondrop='return false;' value='' style='width:200px; font-size:12px; border-style:solid; border-width:1px; border-color:#AAA; padding:5px;' elem='text' elemtype='field' fdefault='' textrole='t' texttype='e' textmv='' textnumdecimals='0' textalign='left' numformat='auto'></input>" 
	return strHTML;
}

function getCheckboxHTML(){
	var x = findValidElemIndex('chbox', 'chbox_input_');
	var eName = "chbox_input_" + x;
	var strHTML = "<input class='selectable' id='" + eName + "' name='" + eName +"' placeholder='" + eName + "' ondrop='return false;' value='' elem='chbox' elemtype='field' style='font-size:12px;' fdefault='' optionmethod='manual' optionlist='' tblwidth='200' luservername='' colno='1' lunsfname='' luview='' lucolumn='' lukeytype='none' lukey='' lukeyfield=''></input>"
	return strHTML;
}

function getRadioButtonHTML(){
	var x = findValidElemIndex('rdbutton','radio_btn_');
	var eName = "radio_btn_" + x;
	var strHTML = "<input class='selectable' id='" + eName + "' name='" + eName +"' placeholder='" + eName + "' ondrop='return false;' value='' elem='rdbutton' elemtype='field' style='font-size:12px;' fdefault='' optionmethod='manual' optionlist='' tblwidth='200' luservername='' lunsfname='' luview='' lucolumn='' lukeytype='none' colno='1' lukey='' lukeyfield=''></input>"	
	return strHTML;
}

function getSelectHTML(){
	var x = findValidElemIndex(':input','select_box_');
	var eIDName = "select_box_" + x;
	var strHTML = "<input class='selectable' id='" + eIDName + "' name='" + eIDName + "' placeholder='" + eIDName + "' ondrop='return false;' value='' elem='select' elemtype='field' optionmethod='manual' style='border-style:solid; border-width:1px; border-color:#AAA; font-size:12px; padding:5px;' optionlist='' luservername='' lunsfname='' luview='' lucolumn='' lukeytype='none' lukey='' lukeyfield=''></input>"
	return strHTML;
}

function getToggleSwitchHTML(){
	var x = findValidElemIndex(':input','toggle_');
	var eIDName = "toggle_" + x;
	var strHTML = "<input class='selectable' id='" + eIDName + "' name='" + eIDName + "' placeholder='" + eIDName + "' ondrop='return false;' value='' elem='toggle' elemtype='field' style='font-size:12px;' ></input>";
	return strHTML;
}

function getSliderHTML(){
	var x = findValidElemIndex('slider', 'slider_');
	var eIDName = 'slider_' + x;
	var strHTML = "<div class='selectable noselect selectableSlider' id='"+ eIDName +"' ondrop='return false;' elem='slider' elemtype='field' minvalue='1' maxvalue='100' orientation='horizontal' sliderlength='250' style='font-size:12px; width:250px;'><span class='caption noselect' contenteditable=false>"+ eIDName +"</span><div class='slideritem'><i class='fal fa-sliders-h-square' style='color:gray'></i></div></div>";
	return strHTML;
}

function getTextAreaHTML(type){
	var x = findValidElemIndex(':input','text_area_');
	var eIDName = "text_area_" + x;
	var strHTML = "<textarea class='selectable' id='" + eIDName + "' name='' editortype='"+ (type ? type : 'P') +"' placeholder='" + eIDName + "' style='width:200px; height:100px; font-size:12px; border-style:solid; border-width:1px; border-color:#AAA; padding:5px;'  editorHeight='100' elem='tarea' elemtype='field' readonly></textarea>"
	return strHTML;
}

function getDateHTML(type){
	var x = findValidElemIndex(':input','date_');
	var eIDName = "date_" + x;
	var typeAttribute = 'displayonlytime="false" displaytime="true"';// when it's date and time
	if (type == 't') {
		typeAttribute = 'displayonlytime="true" displaytime="false"';// when it's time only
	}
	else if (type == 'd') {
		typeAttribute = 'displayonlytime="false" displaytime="false"';// when it's date only
	}
	var strHTML = "<input class='selectable' id='" + eIDName + "' "+ typeAttribute +" name='' placeholder='" + eIDName + "' style='width:80px; font-size:12px; border-style:solid; border-width:1px; border-color:#AAA; padding:5px;' elem='date' ondrop='return false;' elemtype='field' datetype='e'></input>";
	return strHTML;
}

function getLabelHTML(){
	var x = findValidElemIndex('label', 'label_');
	var IDName = "label_" + x;
	var strHTML = '<label id="' + IDName + '" name="' + IDName + '" class="selectable" style="text-align:left; font-size:12px;" elem="label" elemtype="field" contenteditable=false>Label:</label>'
	return strHTML;
}

function getOutlineHTML(){
	var x = findValidElemIndex(':input','outline_');
	var eIDName = "outline_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='outline' elemtype='field' outlinename='- Select -' expand='all' contenteditable=false style='font-size:12px;'><div class='item'><i class='far fa-bars fa-3x' style='color:gray'></i><span class='caption noselect' contenteditable=false>Outline: -Select-</span></div></div>"	
	return strHTML
}

function getParHTML(){
	var strHTML = "<p class='selectable' elemtype='par' elem='newpar' style='font-size:12px; padding:5px;'></p>"
	return strHTML;
}

function getSubTableHTML(){
	var x = findValidElemIndex(':input','sub_table_');
	var eIDName = "sub_table_" + x;
	var strHTML = "<table id='" + eIDName + "' name='' elem='table' elemtype='field' style='width:100%;border-spacing:0px;table-layout:fixed;font-size:12px;' contenteditable='false'>"
	strHTML += "<thead><tr><th style='width:200px;'><i class='fas fa-caret-down easthandle' style='float:right;margin-right:-4px;display:;'></i></th><th>&nbsp;</th></tr></thead>"
	strHTML += "<tr><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td></tr></table>"
	return strHTML;
}

function getDataIslandHTML(){
	var x = findValidElemIndex(':input','datatable_');
	var eIDName = "datatable_" + x;
	var strHTML = "<div id='" + eIDName + "' elem='datatable' elemtype='field' class='datatable selectable' dttype='local' >";
	strHTML += '<div style="display:none">';
	strHTML += 		'<textarea class="dtvaluefield selectable" id="' + eIDName + '_values"  editortype="P"  elem="tarea" elemtype="field" readonly=""></textarea>';
	strHTML += '</div>';
	strHTML += "<table name='' style='width:100%;border-spacing:0px;table-layout:fixed;font-size:12px;' contenteditable='false'>"
	strHTML += "<thead><tr><th style='width:200px;' class='selectable' contenteditable='true'><i class='fas fa-caret-down easthandle' style='float:right;margin-right:-4px;display:;'></i><p class='selectable' elemtype='par' elem='par' style='padding:5px;'>Header 1</p></th><th class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'>Header 2</p></th><th class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'>Actions</p></th></tr></thead><tbody class='datatabletbody'>"
	strHTML += "<tr class='disland_templ_row'><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td>";
	strHTML += "<td class='selectable' contenteditable='true'>";
	strHTML += '<p class="dtaction_buttons" elem="par" elemtype="par" style="padding: 5px; text-align: right;" bc="1">';
	var x = findValidElemIndex(':input', 'icon_');
	strHTML += 		'<span title="Edit Row"  class="disland_edit selectable fas fa-edit" id="icon_' + x + '" ondrop="return false;" value="" style="font-size: 16px; background: rgb(226, 226, 228); color: rgb(92, 92, 234); padding: 7px;" elem="icon" elemtype="field" fdefault="" onclick="" onchange="" onfocus="" onblur=""></span>';
	strHTML += 		'<span title="Save Row"  class="disland_save selectable fas fa-check-square" id="icon_' + (++x) + '" ondrop="return false;" value="" style="font-size: 16px; background: rgb(226, 226, 228) ;color: rgb(104, 179, 104); padding: 7px;" elem="icon" elemtype="field" fdefault="" onclick="" onchange="" onfocus="" onblur="" bc="0"></span>';
	strHTML += 		'<span title= "Delete Row" class="disland_delete selectable fas fa-times-square" id="icon_' + (++x) + '" ondrop="return false;" value="" style="font-size: 16px; background: rgb(226, 226, 228); padding: 7px; color: rgb(179, 23, 23)" elem="icon" elemtype="field" fdefault="" onclick="" onchange="" onfocus="" onblur=""></span>';
	strHTML += 		'<span title="Undo Edit"  class="disland_canceledit selectable fas fa-share-square" id="icon_' + (++x) + '" ondrop="return false;" value="" style="font-size: 16px; background: rgb(226, 226, 228); padding: 7px; color: rgb(114, 118, 179)" elem="icon" elemtype="field" fdefault="" onclick="" onchange="" onfocus="" onblur=""></span>';
	strHTML += '</p>';
	strHTML += "</td></tr></tbody><tfoot><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td><td class='selectable' contenteditable='true'><p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p></td></tfoot></table></div>"
	return strHTML;
}

function getHTMLHTML(){
	var x = findValidElemIndex(':input','html_code_');
	var eIDName = "html_code_" + x;	
	var strHTML = "<div class='selectable' id='" + eIDName + "' elem='htmlcode' ondrop='return false;' value='' elemtype='field' contenteditable='false' >&lt;!--Add your HTML code here--&gt;</div>&nbsp;"
	return strHTML;
}

function getChartHTML(){
	var width = '99%';
	var height = 200;
	var iconpadding = Math.max((height/2).toFixed(0)-20, 0);
	var x = findValidElemIndex(':input','chart_');
	var eIDName = "chart_" + x;
	var strHTML = "<span id='" + eIDName + "' class='selectable noselect selectableSections' elem='chart' elemtype='field' contenteditable=false";
	strHTML += " style='width:" + width.toString() + "; height:" + height.toString() + "px; vertical-align:top; font-size:12px;' ";
	strHTML += " chartwidth='" + width.toString() +"' chartheight='" + height.toString() + "' ";
	strHTML += " htmlclass='' htmlother='' charttitle='' charthorzlabel='' chartvertlabel='' charthidevalues='' charthidelegend='' charttype='' chartsourcetype='' chartsource='' dspchartapptitle='' lunsfname='' fformula='' "; 
	strHTML += " chartlegenditems='' chartaxisitems='' chartvalueitems='' additional_style='' fonclick='' fonchange='' fonfocus='' fonblur='' hidewhen='' customhidewhen='' >";
	strHTML += "<span class='item' style='display:inline-block; padding-top:" + iconpadding.toString() + "px;'>";
	strHTML += "<i class='fa fa-chart-pie fa-3x' style='color:gray;'></i>";
	strHTML += "</br>";
	strHTML += "<span class='caption noselect' chartlabel='chartlabel'>Chart - "+ eIDName +"</span>";
	strHTML += "</span>";
	strHTML += "</span>";
	return strHTML
}


function getButtonHTML(){
	var x = findValidElemIndex(':input','button_');
	var eIDName = "button_" + x;
	var strHTML = "<button class='btn dtoolbar selectable' id='" + eIDName + "' elem='button' elemtype='field' type='button' btnLabel='Button' btnText='1' btnPrimaryIcon='' style='font-size:12px;' btnSecondaryIcon='' btntype='CST' contenteditable='false'></button>"
	return strHTML;
}

function getPicklistButtonHTML(fetchelements){
	var x = findValidElemIndex(':input', 'picklist_');
	var eIDName = 'picklist_' + x;
	var strHTML = "<button class='btn dtoolbar selectable' id='" + eIDName + "' elem='picklist' elemtype='field' type='button' plLabel='Picklist Button' style='font-size:12px;' plShowLabel='1' plAction='D' plPrimaryIcon='' plSecondaryIcon='' contenteditable='false'></button>";
	return strHTML;
}

function getImageHTML(){
	var x = findValidElemIndex(':input','image_');
	var eIDName = "image_" + x;
	var strHTML = "<img class='selectable' id='" + eIDName + "' src='" +docInfo.ImgPath + "../DOCOVA-Logo.png' imagename='DOCOVA-Logo.png' elem='image' style='font-size:12px;' elemtype='field' contenteditable='false'></img>"
	return strHTML;	
}

function getCTextHTML(){
	var x = findValidElemIndex(':input','ctext_');
	var eIDName = "ctext_" + x;
	var strHTML = "<input class='selectable' id='" + eIDName + "' elem='ctext' elemtype='field' etype='D' fformula='' style='border-style:solid; font-size:12px; border-width:1px; border-color:#AAA; padding:5px;' readonly placeholder='$$ComputedText'></input>"
	return strHTML;
}

function getTabSetHTML(){
	var x = findValidElemIndex(':input','tabset_');
	var eIDName = "tabset_" + x;
	var strHTML = "<div class='selectable' id='" + eIDName + "' elem='tabset' elemtype='field' style='font-size:12px;' contenteditable=false>"
	var rID1 = getRandomID();
	var rID2 = getRandomID();
  	strHTML += "<ul>"
	strHTML += "<li><a href='#" + rID1 + "'>Tab 1</a></li>"
	strHTML += "<li><a href='#" + rID2 + "'>Tab 2</a></li>"
	strHTML += "</ul>"
	strHTML += "<div id='" + rID1 + "' contenteditable=false>"
	strHTML += "</div>"
	strHTML += "<div id='" + rID2 + "' contenteditable=false>"
	strHTML += "</div>"
	strHTML += "</div>"
	return strHTML
}

function getPanelHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "panel_section_" + x;
	strHTML = "<div class='grid-stack-item-content' id='" + eIDName + "'> " + getFieldsHTML() ;
	strHTML += "<i title='Move Panel' class='dhgrip fas fa-grip-horizontal'></i><i title='Delete Panel' class='rmpanel far fa-times'></i></div>"
	return strHTML;
}

function getFieldsHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable'  style=' overflow:hidden;' elem='fields' elemtype='section' contenteditable=true>"
	strHTML += "<p class='selectable' elemtype='par' elem='par' style='padding:5px;'></p>";
	strHTML += "</div>";
	
	return strHTML;
}

function getSectionHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;	
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable fieldssection'  elem='fields' elemtype='section'>Section</div>";
	
	return strHTML
}

function getSubformHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;	
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='subform' elemtype='field' subform='- Select -' insertmethod='Selected' subformformula='' style='font-size:12px;' contenteditable=false><div class='item'><i class='far fa-indent fa-3x' style='color:gray'></i><span class='caption noselect' contenteditable=false>Custom Subform: -Select-</span></div></div>"	
	return strHTML
}

function getAttachmentsHTML(){
	var x = findValidElemIndex('section','att_');
	var eIDName = "att_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='attachments' elemtype='field' style='font-size:12px;' contenteditable=false><div class='item'><i class='far fa-paperclip fa-3x' style='color:gray'></i><span class='caption noselect'>Attachments</span></div></div>";
	return strHTML
}


function getSingleAttachmentHTML()
{
	var x = findValidElemIndex('section','att_');
	var eIDName = "att_single_" + x;
	
	var html = '<div id="' + eIDName + '" upcontrolno="' + x + '" class="singleuploadercontainer selectable" elem="singleuploader" elemtype="field" style="height:300px; font-size:12px;"><div class="singleuploaderedit">';
	var ix = findValidElemIndex(':input', 'icon_');
	var iconIDName = "icon_" + ix;
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center;"><span class="selectable fas fa-cloud-upload" id="' + iconIDName + '" ondrop="return false;" value="" style="font-size: 70px;color: rgba(104, 120, 185, 1);" elem="icon" elemtype="field" fdefault="" onclick="" onchange="" onfocus="" onblur=""></span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center;"><span><br></span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center; font-size: 16px; color: rgb(134, 134, 134); font-weight: 700;" id="" onclick="" onchange="" onfocus="" onblur=""><span>Drag and Drop your files here</span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center; font-size: 16px; color: rgb(134, 134, 134); font-weight: 700;" id="" onclick="" onchange="" onfocus="" onblur=""><span><br></span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center; font-size: 16px; color: rgb(134, 134, 134); font-weight: 700;" id="" onclick="" onchange="" onfocus="" onblur=""><span><br></span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center; font-size: 16px; color: rgb(134, 134, 134); font-weight: 700;" id="" onclick="" onchange="" onfocus="" onblur="">';
	var bx = findValidElemIndex(':input','button_');
	var btneIDName = "button_" + bx;

	html += '<button class="btnsingleattachmentadd selectable btn dtoolbar ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary" id="' + btneIDName + '" elem="button" elemtype="field" type="button" ';
	html += 'btnlabel="Browse for files" btntext="1" btnprimaryicon="fas fa-folder-open" btnsecondaryicon="" btntype="CST" role="button" name="button_1" style="font-size: 12px; color: rgb(104, 120, 185);" onclick="" onchange="" onfocus="" onblur="">';
	html += '<span class="ui-button-icon-primary ui-icon fas fa-folder-open"></span><span class="ui-button-text">Browse for files</span></button><span id="singleupcurrfile"></span><span><br></span></p>';
	html += '<p class="selectable" elemtype="par" elem="par" style="text-align: center;"><span><br></span></p></div>';
	html += '<div class="singleuploaderdisplay" style="display:none">';
	html += '<div class="singleuptoolbar">';
	html += '<i id="btn_singleupedit" class="far fa-pencil" title="Edit Attachment" ></i>';
	html += '<i id="btn_singleupdialog" class="far fa-window-restore" title="Launch in dialog"></i>';
	html += '</div>';
	html += '<div style="position:relative;width:100%;height:100%"><iframe style="position:absolute; width: 100%;height: 100%;border: 0px;border: 1px solid lightgray;" src="about:blank"></iframe><img id="suimagepreview"></img></div></div>';

	//html += getAttachmentsHTML(true);
	//var x = findValidElemIndex('section','att_');
	var eIDName = "att_" + x;
	
	html += "<div id='" + eIDName + "' class='singleattachment selectable noselect selectableSections' style='display:none' elem='attachments' elemtype='field' maxfiles_prop='1' contenteditable=false><div class='item'><i class='far fa-paperclip fa-3x' style='color:gray'></i><span class='caption noselect'>Attachments</span></div></div></div>";
	
	return html;
}

function getAppElementHtml(){
	var x = findValidElemIndex('section', 'appelem_');
	var eIDName = 'appelem_' + x;
	var strHTML = '';
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='appelement' elemtype='field' style='font-size:12px;' contenteditable=false><div class='item' style='width:100%;'><i class='fa fa-indent fa-3x' style='color:gray;width:100%;'></i><span class='caption noselect' style='width:100%;'>Application Element</span></div></div>";
	return strHTML;
}

function getMapHtml(){
	var x = findValidElemIndex('section', 'googlemap_');
	var eIDName = 'googlemap_' + x;
	var strHTML = '';
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='googlemap' elemtype='field' style='font-size:12px;' contenteditable=false><div class='item' style='width:100%;'><i class='far fa-map fa-3x' style='color:gray;width:100%;'></i><span class='caption noselect' style='width:100%;'>Google Map</span></div></div>";
	return strHTML;
}

function getBarcodeHtml(){
	var x = findValidElemIndex('section', 'barcode_');
	var eIDName = 'barcode_' + x;
	var strHTML = '';
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='barcode' elemtype='field' style='font-size:12px;' contenteditable=false><div class='item' style='width:100%;'><i class='far fa-barcode fa-3x' style='color:gray;width:100%;'></i><span class='caption noselect' style='width:100%;'>Barcode</span></div></div>";
	return strHTML;	
}

function getWeatherHtml(){
	var x = findValidElemIndex('section', 'weather_');
	var eIDName = 'weather_' + x;
	var strHTML = '';
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='weather' elemtype='field' style='font-size:12px;' contenteditable=false><div class='item' style='width:100%;'><i class='far fa-cloud fa-3x' style='color:gray;width:100%;'></i><span class='caption noselect' style='width:100%;'>Weather Forecast</span></div></div>";
	return strHTML;	
}

function getCounterBoxHtml(){
	var x = findValidElemIndex('section', 'counterbox_');
	var eIDName = 'counterbox_' + x;
	var strHTML = '';
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='counterbox' elemtype='field' style='font-size:12px;' contenteditable=false etype='D'><div class='item' style='width:100%;'><i class='fab fa-digital-ocean fa-3x' style='color:gray;width:100%;'></i><span class='caption noselect' style='width:100%;'>Counter Box</span></div></div>";
	return strHTML;		
}

function getRelatedDocumentsHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable' elem='relateddocuments' style='font-size:12px;' elemtype='section'>Related Documents</div>"

	return strHTML
}


function getEmbeddedViewHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable noselect selectableSections' elem='embeddedview' elemtype='field' embviewheight='250' style='font-size:12px;' contenteditable=false><div class='item'><i class='far fa-list-ul fa-3x' style='color:gray'></i><span class='caption noselect'>Embedded View</span></div></div>";
	getViewList("selectEmbView");
	return strHTML
}

function getRelatedEmailsHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;
	var strHTML = "";
	strHTML += "<div id='" + eIDName + "' class='selectable selectableSections' elem='relatedemails' elemtype='section' style='font-size:12px;' contenteditable=false><div class='item'><i class='far fa-envelope-square fa-3x' style='color:gray'></i>&nbsp;<span class='caption'>Related Emails</span></div></div>"
	return strHTML
}

function getAuditLogHTML(){
	var x = findValidElemIndex('section','section_');
	var eIDName = "section_" + x;
	var strHTML = "";	
	strHTML += "<div id='" + eIDName + "' class='selectable selectableSections' elem='auditlog' alogheight='150px' collapsible='' filter='0' style='font-size:12px;' elemtype='section' contenteditable=false><div class='item'><i class='far fa-check-circle-o fa-3x' style='color:gray'></i>&nbsp;<span class='caption'>Audit Log</span></div></div>"
	return strHTML
}

function setPreCannedButton(){
	var btnType = Docova.Utils.getField({ field: "btn_Type" });
	if(btnType == "CST"){
		$("#btn_Label").val("Button")
		$("#btn_PrimaryIcon").val("");
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" })
		Docova.Utils.setField({ field: "HideWhen", value: "" })		
		$("#onClickEvent").val("");
		setProperties();
	}
	if(btnType == "E"){
		$("#btn_Label").val("Edit")
		$("#btn_PrimaryIcon").val("far fa-edit");
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" })
		Docova.Utils.setField({ field: "HideWhen", value: "E" })
		$("#onClickEvent").val("var uidoc = Docova.getUIDocument(); uidoc.edit();");
		$('[target=onClickEvent]').text('var uidoc = Docova.getUIDocument(); uidoc.edit();');
		setProperties();		
	}
	if(btnType == "SC"){
		$("#btn_Label").val("Save and Close")
		$("#btn_PrimaryIcon").val("far fa-save");		
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" })
		Docova.Utils.setField({ field: "HideWhen", value: "R" })
		$("#onClickEvent").val("var uidoc = Docova.getUIDocument(); uidoc.save({ andclose: true });");
		$('[target=onClickEvent]').text('var uidoc = Docova.getUIDocument(); uidoc.save({ andclose: true });');
		setProperties();
	}
	if(btnType == "S"){
		$("#btn_Label").val("Save")
		$("#btn_PrimaryIcon").val("far fa-save");		
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" })
		Docova.Utils.setField({ field: "HideWhen", value: "R" })
		$("#onClickEvent").val("var uidoc = Docova.getUIDocument(); uidoc.save();");
		$('[target=onClickEvent]').text('var uidoc = Docova.getUIDocument(); uidoc.save();');
		setProperties();
	}	
	if(btnType == "C"){
		$("#btn_Label").val("Close")
		$("#btn_PrimaryIcon").val("ui-icon-close");
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" })
		Docova.Utils.setField({ field: "HideWhen", value: "" })
		$("#onClickEvent").val("var uidoc = Docova.getUIDocument(); uidoc.close();");
		$('[target=onClickEvent]').text('var uidoc = Docova.getUIDocument(); uidoc.close();');
		setProperties();
	}
	if (btnType == 'CMP'){
		$('#btn_Label').val('Create New Document');
		$('#btn_PrimaryIcon').val('far fa-folder-plus');
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" });
		Docova.Utils.setField({ field: "HideWhen", value: '' });
		$('#onClickEvent').val('var uidoc = Docova.compose({ formname: "FORM_NAME" });');
		$('[target=onClickEvent]').text('var uidoc = Docova.compose({ formname: "FORM_NAME" });');
		setProperties();
	}
	if (btnType == 'GSC'){
		var tmpCode = "var newCategory = $('#'+ $(this).attr('btnGanttCatField')).val();\n"+
"			var ganttframe = $(this).attr('embeddedganttview');\n" +
"			var uidoc = Docova.getUIDocument();\n"+
"			uidoc.save({\n"+
"				andclose: false,\n"+
"				async: true,\n"+
"				editMode: true,\n"+
"				Navigate: false,\n"+
"				onOk: function(){\n" +
"					$('#'+ ganttframe)[0].contentWindow.docInfo.RestrictToCategory = newCategory;\n"+
"					$('#'+ ganttframe)[0].contentWindow.saveGanttOnServer(uidoc);\n"+
"					uidoc.close({ savePrompt: false });\n"+
"				}\n"+
"			});";
		$('#btn_Label').val('Gantt Save and Close');
		$('#btn_PrimaryIcon').val('far fa-save');
		Docova.Utils.setField({ field: "btn_ShowLabel", value: "1" });
		Docova.Utils.setField({ field: "HideWhen", value: 'R' });
		$('#onClickEvent').val(tmpCode);
		$('[target=onClickEvent]').text(safe_tags(tmpCode));
		setProperties();
	}
}

function hookPanelDelete(){

	$(".rmpanel").click ( function(e) {

		var ans = confirm("Are you sure you want to remove this panel?")
		if (! ans) return;

		e.preventDefault();
    	var grid = $('.grid-stack').data('gridstack'),
        el = $(this).closest('.grid-stack-item')

    	grid.remove_widget(el);

	})
}


function initGridStack(){


	if ( $('.grid-stack').length == 0 ) return;

	var options = {
        verticalMargin:12,
        alwaysShowResizeHandle: true,
        acceptWidgets: '.panel',
        cellHeight:10,
        draggable: {
        	 handle: '.dhgrip'
        }
    };

	$('.grid-stack').gridstack(options);
	$('.grid-stack').on('dragstart', function(event, ui) {
		
	    hideSelectors();
	});

	$('.grid-stack').on('resizestart', function(event, ui) {
	    hideSelectors();
	});

	$('.grid-stack').on('gsresizestop', function(event, elem) {
    	setSelectors();
	});

	$('.grid-stack').on('change', function(event, items) {
    	setSelectors();
	});

	var pjson = getPanelsJSON();
	var pcss = getPanelsCSS(pjson, true);
	addcss(pcss);

	hookPanelDelete();

	$('.panel').draggable({
                revert: true,
                helper :  function ()
                {
                	removeSelected();
                	return $("<div style='width:90px;height:70px;border:1px solid darkgray; background:white; text-align:center; padding : 8px 5px 2px 5px'><i style='font-size:2.5em; margin-bottom:5px; display:block' class='far fa-images'></i><span class='elemLabel'>Panel</div>");

                },
                scroll: false,
                appendTo: 'body'
     });
	
}


function dropElement(event, ismobile)
{	
	eventintervalcounter ++;
	if(eventintervalcounter > 5){
		clearInterval(dropIntervalId);
		eventintervalcounter = 0;
	}
	
	var targetdivname = "layout" + (typeof ismobile !== "undefined" && ismobile !== null && (ismobile == true || ismobile == 1) ? "_m" : "");
	var targetdiv = $("#"+targetdivname);
	var targetElem = $(event.target);

	if ( targetElem.hasClass('grid-stack')){
		droppedElem = targetElem.find(".newPanel");
		if (  droppedElem.length == 0){
			
			return;
		}
	}else{
		var droppedElem = $("div[elem='fields']", targetdiv).find(".newElement");
		while( ($(targetElem).attr("elemtype") != "section") & ($(targetElem).prop("id") != targetdivname) ){
			targetElem = $(targetElem).parent();
		}
		var droppedElem = targetElem.find(".newElement");
		if (  droppedElem.length == 0)
		{
			//look for dropped element as a datatable field
			droppedElem = targetElem.find(".dtfieldph")
			if (  droppedElem.length == 0)
			{
				return;
			}
		}
		//Elements can only be dropped into a p, if not dropped into a P..then remove
		getCurrRange();
		clearInterval(dropIntervalId);

		if (  currRange ){
			var droptargetElem = currRange.startContainer
			//all dropped elements must be dropped into a P element/node(initially)
			//if the droptargetElem is not a P element/node, then move up the DOM tree to find the container P element and set that to the droptargetElem.
			if($(droptargetElem).prop("tagName") != "P"){
				while( ($(droptargetElem).prop("tagName") != "P") & ($(droptargetElem).prop("id") != targetdivname) ){
					droptargetElem = $(droptargetElem).parent();
				}
			}
			//if a P element/node isn't found then remove the dropped element. This prevents dropping an element into
			//sections of the form where it shouldn't be dropped, for example, in an attachments element or the action button bar.
			if($(droptargetElem).prop("tagName") != "P"){
				$(droppedElem).remove();
				clearInterval(dropIntervalId);
				return false;
			};
		}
	}

	
	if($(droppedElem).length){
		pushUndo();
		var elem = $(droppedElem).attr("elem");
		var parentPar = getCurrentParObj(droppedElem) //the current P that the html is being dropping into

		if ( elem != "panel"){
			$(droppedElem).remove();
		}
		clearInterval(dropIntervalId);
		
		switch(elem){
			case "text":
				insertElement(getInputFieldHTML('t'));
			break;
			case "number":
				insertElement(getInputFieldHTML('n'));
			break;
			case "author":
				insertElement(getInputFieldHTML('a'));
			break;
			case "reader":
				insertElement(getInputFieldHTML('r'));
			break;
			case "names":
				insertElement(getInputFieldHTML('names'));
			break;
			case "icon":
				insertElement(getIconHTML('r'));
				break;
			case "chbox":
				insertElement(getCheckboxHTML());
			break;
			case "rdbutton":
				insertElement(getRadioButtonHTML());
			break;
			case "select":
				insertElement(getSelectHTML());
			break;
			case "toggle":
				insertElement(getToggleSwitchHTML());
			break;
			case 'slider':
				var sliderHTML = getSliderHTML();
				$(sliderHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
				break;
			case "date":
				insertElement(getDateHTML('d'));
			break;
			case "time":
				insertElement(getDateHTML('t'));
			break;
			case "datetime":
				insertElement(getDateHTML());
			break;
			case "label":
				insertElement(getLabelHTML());
			break;
			case "tarea":
				insertElement(getTextAreaHTML());
			break;
			case "docovaeditor":
				insertElement(getTextAreaHTML('D'));
			break;
			case "richtext":
				insertElement(getTextAreaHTML('P'));
			break;
			case "button":
				var tmpButtonHTML = $(getButtonHTML())
				var newButtonId = tmpButtonHTML.prop("id") //get the id from the newly generated button html
				insertElement(getButtonHTML());
				$("#" + newButtonId).button({ label: "Button", text: true });				
			break;
			case 'picklist':
				var tmpButtonHTML = $(getPicklistButtonHTML());
				var newButtonId = tmpButtonHTML.prop("id"); //get the id from the newly generated button html
				insertElement(getPicklistButtonHTML(true));
				$("#" + newButtonId).button({ label: "Picklist Button", text: true });				
			break;
			case "image":
				insertElement(getImageHTML());
			break;
			case "table":
				var tableHTML = getSubTableHTML();
				$(tableHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
				resetRulers();
			break;
			case "datatable":
				var tableHTML = getDataIslandHTML();
				var newobj = $(tableHTML).insertAfter(parentPar);
				setCurrElem(newobj);
				setSelectable();
				setSelected();
				resetRulers();
			break;
			case "htmlcode":
				var htmlHTML = getHTMLHTML();
				var newobj = $(htmlHTML).insertAfter(parentPar);			
				initializeHtmlElements(newobj, true);
				setCurrElem(newobj);
				setSelectable();
				setSelected();	
			break;
			case "chart":
				var chartHTML = getChartHTML();
				$(chartHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;			
			case "ctext":
				insertElement(getCTextHTML());
			break;
			case "outline":
				var outlineHTML = getOutlineHTML();
				$(outlineHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case "tabset":
				var tmpTabSetHTML = $(getTabSetHTML())
				var newTabSetId = tmpTabSetHTML.prop("id") //get the id from the newly generated tabset html
				$(tmpTabSetHTML).insertAfter(parentPar);

				$("#" + newTabSetId).tabs({ activate: function(event, ui){ $("#TabLabelName").val($(ui.newTab).text()) } });
				$("#" + newTabSetId + " div").each(function(){
					$(this).html(getSubTableHTML())  //insert subtables into each tab div
				});
				$("#" + newTabSetId).find( ".ui-tabs-nav" ).sortable({ //make tabs sortable on x-axis
      				axis: "x",
      				stop: function() {
        				$("#" + newTabSetId).tabs( "refresh" );
      				}
    			});
    			// Tab initialization
    			setSelectable();
    			setSelected();
				resetRulers();
			break;
			case "attachments":
				var attachmentHTML = getAttachmentsHTML();
				$(attachmentHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case "singleattachment":
				var attachmentHTML = getSingleAttachmentHTML();
				$(attachmentHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case "embeddedview":
				var embviewHTML = getEmbeddedViewHTML();
				$(embviewHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case "subform":
				var subformHTML = getSubformHTML();
				$(subformHTML).insertAfter(parentPar);
				setSelectable();
				setSelected();				
			break;
			case "fields":
				var currSection = $(parentPar).closest("[elem=fields]")
				var sectionHTML = getFieldsHTML();
				$(sectionHTML).insertAfter(currSection);
				initFieldsSections();
				setSelectable();
				setSelected();			
			break;
			case "relatedemails":
				var lastSection = targetElem.children().last();
				var sectionHTML = getRelatedEmailsHTML();
				$(sectionHTML).insertAfter(lastSection);
				setSelectable();
				setSelected();			
			break;
			case "auditlog":
				var lastSection = targetElem.children().last();
				var sectionHTML = getAuditLogHTML();
				$(sectionHTML).insertAfter(lastSection);
				setSelectable();
				setSelected();
			break;
			case 'appelement':
				var appelementHtml = getAppElementHtml();
				$(appelementHtml).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case 'panel':
				$(droppedElem).parent().removeClass("panel").addClass("selectable").attr("elem", "panel");
				var newelem = $(droppedElem).replaceWith( getPanelHTML());
				initFieldsSections();
				setSelectable();
				setSelected();
				hookPanelDelete();
				break;
			case 'googlemap':
				var googlemapHtml = getMapHtml();
				$(googlemapHtml).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case 'barcode':
				var barcodeHtml = getBarcodeHtml();
				$(barcodeHtml).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case 'weather':
				var weatherHtml = getWeatherHtml();
				$(weatherHtml).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case 'counterbox':
				var counterHtml = getCounterBoxHtml();
				$(counterHtml).insertAfter(parentPar);
				setSelectable();
				setSelected();
			break;
			case 'datatablefield':
				
				var id = $(droppedElem).attr("id");
				var orignode = $("#"+id);
				if ( orignode.length > 0 ){
					var newnode = orignode.attr("templ");
					newnode = $(decodeURIComponent(newnode));
					var stype = newnode.attr("elem");
					if ( $("#" + newnode.attr("id")).length > 0 )
					{
						alert ("There is already a field with that name on this form!");
						orignode.remove();
						clearInterval(dropIntervalId);
						return false;
					}

					
					//parentPar.append()
					insertElement($("<div></div>").append(newnode).html() );
					orignode.remove();
				}
				
			break;
		}

		if ( elem != "panel"){
			//this code expands the panel to fit the content
			var grid = $('.grid-stack').data('gridstack');
	        var gsi = targetElem.parents(".grid-stack-item");
	        var gsic = gsi.children(".grid-stack-item-content");
	        var sheight = 0;
	        gsic.children("[elemtype='section']").each(function (){
  			     sheight += $(this).get(0).scrollHeight ;      	
	        });

	        var curheight = gsi.height();
	        if ( curheight < sheight){
	        	var newHeight = Math.round(( sheight + grid.opts.verticalMargin) / (grid.cellHeight() + grid.opts.verticalMargin));
	        	grid.resize(gsi,$(gsi).attr('data-gs-height'),newHeight+5);
	        }
		}

	}else{
		clearInterval(dropIntervalId);
		$(parentPar).find("a").remove();
	}
	
	return false;
}

function setCurrElem(newElem){
	if(typeof newElem == "undefined" || newElem === null){return;}
	
	if(typeof currElem !== "undefined" && currElem !== null){
		//if currElem is a label elem then make it contenteditable and remove contenteditable from its parent p
		if($(currElem).attr("elem") == "label"){
			$(currElem).parent().prop("contenteditable", "true");
			$(currElem).prop("contenteditable", "false");	
		//if currElem is an html elem then disable row numbers and row highlighting			
		}else if($(currElem).attr("elem") == "htmlcode"){
			var htmleditor = ace.edit($(currElem).attr("id"));
			htmleditor.renderer.setShowGutter(false);
			htmleditor.clearSelection();
			htmleditor.setHighlightActiveLine(false);
		}	
	}
	
	currElem = newElem;
	
	//if the current element being selected is a TD then move the selection to the first P in the TD
	if($(currElem).prop("tagName") == "TD"){
		currElem = $(currElem).children().first();
	//if currElem is a label elem then make it contenteditable and remove contenteditable from its parent p
	}else if($(currElem).attr("elem") == "label"){
		$(currElem).parent().prop("contenteditable", "false");
		$(currElem).prop("contenteditable", "true");
	//if currElem is an html elem then enable row numbers and row highlighting			
	}else if($(currElem).attr("elem") == "htmlcode"){
		var htmleditor = ace.edit($(currElem).attr("id"));
		htmleditor.renderer.setShowGutter(true);
		htmleditor.clearSelection();
		htmleditor.setHighlightActiveLine(true);
	}
	
	//if currElem is a section then enable sortable property
	if($(currElem).attr("elemtype") == "section"){
		$("#layout").sortable( "enable");
		$("#layout").sortable({ sort: function(e){hideSelectors()} });		
	}else{
		$("#layout").sortable( "disable" );
	}	
}

function setSelectable(){
	//unbind click and mouse events so they don't keep getting added
	$('.selectable').off('mouseenter');
	$('.selectable').off('mouseout');
	$('.selectable').off('click');
	
	//for all element with the selectable class, set their onclick functionality
	$(".selectable").click( function(event){ 
		event.preventDefault();
		event.stopPropagation();

		var stype = $(this).attr("elem");
		if ( stype && stype == "icon")
		{
			try {
				var el = this;
		    	var range = document.createRange();
		    	var sel = window.getSelection();
		    	var targ = el.nextSibling;
		    	if ( targ ){
		    		range.setStart(targ, 1);
		    		range.collapse(true);
		    		sel.removeAllRanges();
		    		sel.addRange(range);
		    		gg.focus();
		    
		   	 	}

		    	
		    }catch ( e){}
		}
		if ( $(this).hasClass("ui-draggable")) return;
		$(".selected").removeClass("selected");
	
		setCurrElem(this);
		
		setSelected();
	});	
	return;
}

function setSelected(){
	$(currElem).addClass("selected");
	$(currElem).off('keyup.setSelected');
	$(currElem).off('keydown.setSelected');
	$(currElem).off('blur.setSelected');
	//When setting selected, for text, checkbox, radio, select and date handle the editing effect
	var cElem = $(currElem).attr("elem");
	if(cElem == "text" || cElem == "chbox" || cElem == "rdbutton" || cElem == "select" || cElem == "date" || cElem == "toggle"){
		$(currElem).val($(currElem).attr("placeholder"));
		$(currElem).on('keydown.setSelected', function(e){
			if(e.keyCode == 18){ //detect that alt was pressed
				$(currElem).blur();
				return;
			}		
		})
		$(currElem).on('keyup.setSelected', function(e) {
			e.stopPropagation();
			var newstring = $(this).val().replace(/[^a-z0-9$_.\s]/gi, '').replace(/\s/g, '')
			if(newstring == ""){
				$(currElem).attr("placeholder", "Untitled");
				$(currElem).prop("name", "Untitled");
				$(currElem).prop("id", "Untitled");
				$("#element_id").val("Untitled");	
			}else{
				$(currElem).val(newstring);
				$("#element_id").val($(this).val());
				$(currElem).attr("placeholder", $(currElem).val());
				$(currElem).prop("name", $(currElem).val());
				$(currElem).prop("id", $(currElem).val());
			}
		}).on('blur.setSelected', function(e){
			$(currElem).val("");
		});
	}
	
	generateBreadCrumbs(currElem); //generate breadcrumbs for current selected element
	if ( !manualClose){
		$('#right-panel-tabs').tabs({ active: 0 });
		if (!$('#element_properties').is(':visible') && !$('#prStyle').is(':visible') && !$('#prHideShow').is(':visible') && !$('#prEvents').is(':visible')) {
			$('#element_properties').show();
		}
		
		
		$('#vertical-tabs li[title="Properties"]').click();
	}

	getProperties(); //get properties for current selected element
	setSelectors();
	return;
}

function resetSelected(){
	//Whatever element was selected prior to a save with no close
	//The currElem object has the info, but it needs to be re-tied to the dom element
	if(typeof currElem == "undefined" || currElem == null){
		return;
	}	
	var selectedElemBeforeSave = $(currElem).prop("id");
	currElem = $("#"+selectedElemBeforeSave);
	setSelected();
}
function removeSelected(inobj){
	//Removes the .selected class from the currently selected element (typically when saving)

	if ( inobj ){
		var tmp = inobj.find(".selected");
		tmp.removeClass("selected")
	}
	else{
		$(".selected").removeClass("selected")
	}
		
	hideSelectors(inobj);
	return;
}

function hideSelectors(inobj)
{
	if ( inobj && ! inobj.target )
		inobj.find(".selector").hide();
	else
		$(".selector").hide();

	return;
}

function setSelectors(){
	if(currElem == "undefinded" || currElem == null){
		return;
	}
	if($(currElem).attr("elem") == "par"){//if currElem is a par then get the current cursor point
		getCurrRange();
	}
	
	var $curtab = jQuery(currElem).closest("#ltab,#mtab");
	
	//---Positions selectors around current selected element
	$(".selector", $curtab).show(); //ensure selectors are visible
	$(".h-selector", $curtab).hide(); //hide highlight selectors
	var posLeft = parseInt($(currElem).offset().left, 10) - 3
	var posTop = parseInt($(currElem).offset().top, 10) - 3
	var posRight = posLeft + parseInt($(currElem).outerWidth(),10) - 1
	var posBottom = posTop + parseInt($(currElem).outerHeight(),10) - 1	
	$("#selector-topleft", $curtab).offset({
		left: posLeft,
		top: posTop
	})
	$("#selector-topright", $curtab).offset({
		left: posRight,
		top: posTop
	})
	$("#selector-bottomleft", $curtab).offset({
		left: posLeft,
		top: posBottom
	})
	$("#selector-bottomright", $curtab).offset({
		left: posRight,
		top: posBottom
	})
	
	return;
}

function outercenterResize(){
	//---When the outer-center panel is resized---
	//---reset/reposition the selectors
	setSelectors(); 
	//---reset the width of the breadcrumb scroller
	var bcWidth = $(".ui-layout-south").width() - 50
	$("#eBreadCrumbs").css("width",  bcWidth + "px")
}

function getStyleProperties(){
	if(!currElem){return;}
	
	//---Font---
	var fontname = $(currElem).css("font-family") ? $(currElem).css("font-family").toString().split(',') : ['Verdana'];
	$("#fontname").val($.trim(fontname[0]).toLowerCase());
	$("#fontstyle").val( $(currElem).css("font-style") )
	$("#fontweight").val( $(currElem).css("font-weight") )
	$("#fontsize").val( parseInt($(currElem).css("font-size"), 10) ) //removes the px from return val
	if($(currElem).attr("elemtype") == "field"){
		$("#textalign").val( $(currElem).parent().css("text-align") ) //--- Get from parent TD if element is a field
	}else if ($(currElem).attr("elemtype") == 'par') {
		$('#textalign').val($(currElem).css('text-align'));
	}else{
		$("#textalign").val( $(currElem).parent().css("text-align") )
	}
	$("#fontcolor").val( rgb2hex($(currElem).css("color")) )  //---need to convert rgb to hex, if already hex, just returns

	//---Borders---
	//---find first border that does not have a "hidden" class and use that for the style
	if(!$(currElem).hasClass("borderleft0")){
		$("#border_style").val( $(currElem).css("border-left-style") )
	}else if(!$(currElem).hasClass("bordertop0")){
		$("#border_style").val( $(currElem).css("border-top-style") )
	}else if(!$(currElem).hasClass("borderright0")){
		$("#border_style").val( $(currElem).css("border-right-style") )
	}else if(!$(currElem).hasClass("borderbottom0")){
		$("#border_style").val( $(currElem).css("border-bottom-style") )
	}else{
		$("#border_style").val("solid")  //if all hidden, use solid
	}
	$("#bordercolor").val( rgb2hex($(currElem).css("border-left-color")) ) //no value such as border-color, must get one of the sides
	
	//---border widths, if have the "hidden" class then border is 0
	if($(currElem).hasClass("borderleft0")){
		var borderleft = 0;
	}else{
		var borderleft = parseInt($(currElem).css("border-left-width"), 10)
	}
	if($(currElem).hasClass("borderright0")){
		var borderright = 0;
	}else{
		var borderright = parseInt($(currElem).css("border-right-width"),10)
	}
	if($(currElem).hasClass("bordertop0")){
		var bordertop = 0
	}else{
		var bordertop = parseInt($(currElem).css("border-top-width"),10)
	}
	if($(currElem).hasClass("borderbottom0")){
		var borderbottom = 0
	}else{	
		var borderbottom = parseInt($(currElem).css("border-bottom-width"),10)
	}
	$("#borderleft").val( borderleft )
	$("#borderright").val( borderright )
	$("#bordertop").val( bordertop )
	$("#borderbottom").val( borderbottom )
	if(borderleft == borderright && borderleft == bordertop && borderleft == borderbottom){ // if all borders are same width, then set borderall to same
		$("#borderall").val(borderleft);
	}else{
		$("#borderall").val("0")
	}

	//--Padding---
	var paddingleft = parseInt($(currElem).css("padding-left"), 10)
	var paddingright =  parseInt($(currElem).css("padding-right"), 10)
	var paddingtop =  parseInt($(currElem).css("padding-top"), 10)
	var paddingbottom =  parseInt($(currElem).css("padding-bottom"), 10)
	$("#paddingleft").val(paddingleft)
	$("#paddingright").val(paddingright)
	$("#paddingtop").val(paddingtop)
	$("#paddingbottom").val(paddingbottom)	
	if(paddingleft == paddingright && paddingleft == paddingtop && paddingleft == paddingbottom){
		$("#paddingall").val(paddingleft);
	}else{
		$("#paddingall").val("0");
	}

	//---Margin---
	var marginleft = parseInt($(currElem).css("margin-left"), 10)
	var marginright = parseInt($(currElem).css("margin-right"), 10)	
	var margintop = parseInt($(currElem).css("margin-top"), 10)	
	var marginbottom = parseInt($(currElem).css("margin-bottom"), 10)	
	$("#marginleft").val(marginleft)	
	$("#marginright").val(marginright)	
	$("#margintop").val(margintop)	
	$("#marginbottom").val(marginbottom)		
	if(marginleft == marginright && marginleft == margintop && marginleft == marginbottom){
		$("#marginall").val(marginleft);
	}else{
		$("#marginall").val("0");
	}

	//---Background color---
	$("#backgroundcolor").val(rgb2hex($(currElem).css("backgroundColor")))
	
	//--Additional style---
	
return;
}


function CellBorderReset(){
	$(currElem).removeClass("bordertop0 borderright0 borderbottom0 borderleft0");
	$(currElem).css("border-width", "");
	$(currElem).css("border-color", "");
	$(currElem).css("border-style", "");
	setSelectors()
}

function CellBorderApplyAll(){ //special case style...set border on all cells of current table

	$(currElem).parentsUntil("TABLE").parent().children("tbody").children("tr").children("td").each(function(){
		//---border-style---
		//for border class, cant just copy, need to determine
		if($(currElem).hasClass("bordertop0")){
			$(this).addClass("bordertop0")
		}else{
			$(this).removeClass("bordertop0")
		}
		if($(currElem).hasClass("borderright0")){
			$(this).addClass("borderright0")
		}else{
			$(this).removeClass("borderright0")
		}
		if($(currElem).hasClass("borderbottom0")){
			$(this).addClass("borderbottom0")
		}else{
			$(this).removeClass("borderbottom0")
		}
		if($(currElem).hasClass("borderleft0")){
			$(this).addClass("borderleft0")
		}else{
			$(this).removeClass("borderleft0")
		}		
		$(this).css("border-style", $(currElem).css("border_style"));
		$(this).css("border-top-width", $(currElem).css("border-top-width"));
		$(this).css("border-right-width", $(currElem).css("border-right-width"));
		$(this).css("border-bottom-width", $(currElem).css("border-bottom-width"));
		$(this).css("border-left-width", $(currElem).css("border-left-width"));
		$(this).css("border-top-color", $(currElem).css("border-top-color"));
		$(this).css("border-right-color", $(currElem).css("border-right-color"));
		$(this).css("border-bottom-color", $(currElem).css("border-bottom-color"));
		$(this).css("border-left-color", $(currElem).css("border-left-color"));
	})
	
	setSelectors()
}

function CellPaddingApplyAll(){ //special case style...set border on all cells of current table

	$(currElem).parentsUntil("TABLE").parent().children("tbody").children("tr").children("td").each(function(){
		$(this).css("padding-left", $(currElem).css("padding-left"));
		$(this).css("padding-right", $(currElem).css("padding-right"));
		$(this).css("padding-bottom", $(currElem).css("padding-bottom"));
		$(this).css("padding-top", $(currElem).css("padding-top"));
	})
	
	setSelectors()
}

function CellColorApplyRow(){
	$(currElem).parent().children("td").each(function(){  //each td in current tr. currElem is td
		$(this).css("background", $("#backgroundcolor").val());
	})
}

function getEvents(){
	if($(currElem).attr("fonclick") == "undefined" || $(currElem).attr("fonclick") == null){
		$("#onClickEvent").val("");
		$("[target='onClickEvent']").text("");	
	}else{
		var escapedcode = $(currElem).attr("fonclick");
		var unescapedcode = safe_quotes_js(escapedcode, false, true);
		unescapedcode = safe_tags(unescapedcode, true);
		$("#onClickEvent").val(unescapedcode);
		$("[target='onClickEvent']").text(unescapedcode);	
	}
	if($(currElem).attr("fonchange") == "undefined" || $(currElem).attr("fonchange") == null){
		$("#onChangeEvent").val("")
		$("[target='onChangeEvent']").text("");	
	}else{
		var escapedcode = $(currElem).attr("fonchange");
		var unescapedcode = safe_quotes_js(escapedcode, false, true);
		unescapedcode = safe_tags(unescapedcode, true);		
		$("#onChangeEvent").val(unescapedcode);		
		$("[target='onChangeEvent']").text(unescapedcode);	
	}	
	if($(currElem).attr("fonfocus") == "undefined" || $(currElem).attr("fonfocus") == null){
		$("#onFocusEvent").val("")
		$("[target='onFocusEvent']").text("");	
	}else{
		var escapedcode = $(currElem).attr("fonfocus");
		var unescapedcode = safe_quotes_js(escapedcode, false, true);
		unescapedcode = safe_tags(unescapedcode, true);		
		$("#onFocusEvent").val(unescapedcode);	
		$("[target='onFocusEvent']").text(unescapedcode);
	}	
	if($(currElem).attr("fonblur") == "undefined" || $(currElem).attr("fonblur") == null){
		$("#onBlurEvent").val("")
		$("[target='onBlurEvent']").text("");	
	}else{
		var escapedcode = $(currElem).attr("fonblur");
		var unescapedcode = safe_quotes_js(escapedcode, false, true);
		unescapedcode = safe_tags(unescapedcode, true);		
		$("#onBlurEvent").val(unescapedcode);	
		$("[target='onBlurEvent']").text(unescapedcode);	
	}
}

function setEvents(){
	$(currElem).attr("fonclick", safe_quotes_js(safe_tags($("#onClickEvent").val()), false));
	$(currElem).attr("fonchange", safe_quotes_js(safe_tags($("#onChangeEvent").val()), false));
	$(currElem).attr("fonfocus", safe_quotes_js(safe_tags($("#onFocusEvent").val()), false));
	$(currElem).attr("fonblur", safe_quotes_js(safe_tags($("#onBlurEvent").val()), false));

}

//hide/ show for custom generic section
function hideShowGenericCustomFormula(chkObj,spanObjNm)
{
	var spanObj=document.getElementById(spanObjNm);
	if (chkObj.value=="C" && chkObj.checked)
	{
		spanObj.style.display="block";
	}
	else
	{
		spanObj.style.display="none";
	}
}

//gets attributes for the different sections.
//relevant attributes to the section are stored with a _prop postfix.  Then we assign the attributes value to a 
//field that had a matching "id" 
function getSectionAttributes(){
	var me = $(currElem);
	$.each( $(me.get(0).attributes), function() {
		var name = this.name;
		if ( name && name != "" ) {
			if ( name.substr(-5) == "_prop") {
				var nodename = name.split("_prop")[0]
				if ( this.value && this.value != "" ){
					try{ 
					
						if ( nodename == "templatelist" || nodename == "hideattachments" || nodename=="hidereldocuments" ){
							var tmparr = this.value.split(";");
							for ( var p =0; p  < tmparr.length; p ++ ){
								Docova.Utils.setField({
									field: nodename,
									value: tmparr[p]
								});
							}
						}else{
							Docova.Utils.setField({
								field: nodename,
								value: this.value
							});
						}
					}catch(e){}
				}
			}
		}
	});
	return;
}

function getProperties(){
	//---Ensure element properties are being show and hide form properties
	$("#tabsForm").css("display", "none");
	$("#tabsSubForm").css("display", "none");	
	$("#tabsPage").css("display", "none");	
	
	//$("#element_properties").css("display", "");
	$('#frmStyle').hide();
	var header = $(currElem).attr('elem');
	if (header) {
		header += ' Properties';
		$('#element-type').html(header.charAt(0).toUpperCase() + header.slice(1));
		$('#frmStyle').hide();
		$("#tabsForm").hide();
		$('#element_properties').show();
		$('#elmStyle').show();
		$('#frmEvents').hide();
		$('#frmHideShow').hide();
		if ($('#prHideShow').is(':visible')) {
			$('#elmHideShow').show();
		}
		if ($('#prEvents').is(':visible')) {
			$('#elmEvents').show();
		}
	}

	//--- Cell border apply all button set to display none, below it is displayed if currelem is td
	$("#btn-CellBorderApplyAll").css("display", "none");
	$("#btn-CellBorderReset").css("display", "none");
	$("#btn-CellPaddingApplyAll").css("display", "none");
	$("#btn-CellColorApplyRow").css("display", "none");	
	
	//---show/hide related attributes div sections - div section ids are named "attr_" + elem attribute of element that is selected
	$(".attrSection").css("display", "none") //first hide all sections
	$("#attr_" + $(currElem).attr("elem")).css("display", "")//display attr section for curr elem type
	if($(currElem).attr("elem") != "par"){ //if elem is not a par then show the element id(field) section
		$("#attr_element_id").css("display","");
	}
	
	//---Get general properties
	if($(currElem).hasClass('grid-stack-item')){
		currElem = $(currElem).find(".grid-stack-item-content");
	}
	
	$("#element_id").val($(currElem).prop("id"));
	$("#elem_index").val($(currElem).attr("elem_index"));
	$("#htmlClass").val($(currElem).attr("htmlClass"));
	$("#htmlOther").val($(currElem).attr("htmlOther"));

	if($(currElem).attr("elem") == "text"){
		var widthvar = $(currElem)[0].style.width || "";		
		$("#text_width").val((widthvar.indexOf("%")>0 ? widthvar :  parseInt(widthvar, 10)))
		$("#text_type").val($(currElem).attr("texttype"));
		$("#text_maxchars").val($(currElem).attr("maxlength"))
		$("#text_align").val($(currElem).attr("textalign"))
		$('span[target="text_value"]').text($(currElem).attr("fdefault"));
		$('#numrestriction_container').empty();

		$("#dspTextAppTitle").text("<Current Application>"); //clear app title
		$('#textEntryType').val($(currElem).attr('etype') ? $(currElem).attr('etype') : 'D');
		if ($(currElem).attr('etype') == 'V') {
			if ($(currElem).attr('textSelectionType') != 'dbcalulate' || ($(currElem).attr('textSelectionType') == 'dbcalulate' && $(currElem).attr('fnCalculationType') == 'V')) {
				var appID = $(currElem).attr('lunsfname') ? $(currElem).attr('lunsfname') : docInfo.AppID;
				getViewList("textLUView");
				$("#dspTextAppTitle").text($(currElem).attr('dspTextmAppTitle'));
				$('#textLUApplication').val($(currElem).attr('lunsfname'));
				$('#textLUView').val($(currElem).attr('luview'));
				$('#textLUColumn').val($(currElem).attr('lucolumn'));
			}
			else {
				$('#textLUApplication').val('');
				$('#textLUView').val('- Select -');
				$('#textLUColumn').val('');
			}
			$('TR.textEntryTypeV').css('display', '');
			$('TR.textEntryTypeD').css('display', 'none');
			$('#textSelectionType').val($(currElem).attr('textSelectionType'));
			if ($(currElem).attr('textSelectionType') == 'dbcalculate') {
				if ($(currElem).attr('fnCalculationType') == 'V') {
					$('#textLinkedEmbView').val('');
					$('#textEmbViewRefreshCode').val('');
					$('span[target=textEmbViewRefreshCode]').html('');

					var appended = false;
					if ($.trim($(currElem).attr('restrictions')) != '') {
						var restrictions = $(currElem).attr('restrictions').split(';');
						for (var i = 0; i < restrictions.length; i++) {
							var tmp_arr = restrictions[i].split(',');
							$('#numrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="'+ tmp_arr[0] +'" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="'+ tmp_arr[1] +'" class="restrictvalue inputEntry"/></li>');
							appended = true;
						}
					}
					if (appended === false) {
						$('#numrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="restrictvalue inputEntry"/></li>');
					}
					
					$('TR.textCalculationTypeV, TR.textCalculationType').css('display', '');
					$('TR.textCalculationTypeE').css('display', 'none');
				}
				else {
					//initiate tied up embedded views list
					getElementTypeList('textLinkedEmbView', 'embeddedview:datatable');
					$('#textLinkedEmbView').val($(currElem).attr('embViewRef'));
					var escapedval = $(currElem).attr('linkedEmbRefreshScript');
					var unescapedval = safe_quotes_js(escapedval, false, true);
					unescapedval = safe_tags(unescapedval, true);
					$('#textEmbViewRefreshCode').val(unescapedval);
					$('span[target=textEmbViewRefreshCode]').html(unescapedval);
					$('#ctLUApplication').val('');
					$('#selectCtView').val('');
					$('TR.textCalculationTypeV, TR.textdblookup').css('display', 'none');
					$('TR.textCalculationTypeE, TR.textCalculationType').css('display', '');
				}
				$('TR.textclfunction').css('display', '');
				$('TR.textdblookup').css('display', 'none');
				//getViewColumnList('selectNumViewColumn', appID, $(currElem).attr('luview'));
				$('#textFunction').val($(currElem).attr('lufunction'));
				$('#textCalculationType').val($(currElem).attr('fnCalculationType'));
			}
			else if ($(currElem).attr('textSelectionType') == 'dblookup') {
				$('#textLUKey').val($(currElem).attr('text_LUKey'));
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
				$('TR.textdblookup').css('display', '');
			}
			else {
				$('TR.textclfunction, TR.textdblookup').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
		}
		else {
			$('TR.textclfunction, TR.textdblookup, TR.textEntryTypeV').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
			$('TR.textEntryTypeD').css('display', '');
			$("#text_value").val($(currElem).attr("fdefault"));
		}
		
		if ($(currElem).attr('fplaceholder') == 'undefined' || $(currElem).attr('fplaceholder') == null) {
			$('#text_placeholder').val('');
			$('span[target="text_placeholder"]').text('');
		}
		else {
			var escapedval = $(currElem).attr('fplaceholder');
			var unescapedval = safe_quotes_js(escapedval, false, true);
			unescapedval = safe_tags(unescapedval, true);
			$('#text_placeholder').val(unescapedval);
			$('span[target="text_placeholder"]').text(unescapedval);
		}

		if($(currElem).attr("ftranslate") == "undefined" || $(currElem).attr("ftranslate") == null){
			$("#text_translate").val("");
			$('a[target="text_translate"]').prev().text("");
		}else{
			var escapedcode = $(currElem).attr("ftranslate");
			var unescapedcode = safe_quotes_js(escapedcode, false, true);
			unescapedcode = safe_tags(unescapedcode, true);		
			$("#text_translate").val(unescapedcode);			
			$('span[target="text_translate"]').text(unescapedcode);			
		}				
		$("#text_role").val($(currElem).attr("textrole"))
		if ($(currElem).attr("isrequired")) {
			$('#text_isrequired').prop('checked', true);
		}
		else {
			$('#text_isrequired').prop('checked', false);
		}
		if($(currElem).attr("textrole") == "n"){ //if this input field role is a number
			$("#text_num_decimals").val($(currElem).attr("textnumdecimals"));
			$("#text_numformat").val($(currElem).attr("numformat"));
			$("#attr_number_format").css("display", "")
		}else{
			$("#attr_number_format").css("display", "none") //if textrole is not a number, ensure number attribs are hidden
		}
		var tr = $(currElem).attr("textrole");
		if((tr == "names" || tr == "a" || tr == "r" )){ //if this input field role is a names authors or readers field
			var np = $(currElem).attr("textnp") || ""; 
			$("#text_namepicker").prop("checked", (np == "1"));
			$("#text_namepicker").val(np);
			$('#namesDisplayFormat').val($(currElem).attr('namesformat'));
			$("#namepicker").css("display", "");
			$("#namepickerlabel").css("display", "");
			$('#attr_names_format').css('display', '');
		}else{
			$("#text_namepicker").prop("checked", false);
			$("#text_namepicker").val("");
			$('#namesDisplayFormat').val('');
			$("#namepicker").css("display", "none");
			$("#namepickerlabel").css("display", "none");
			$('#attr_names_format').css('display', 'none');
		}
		var txttype = $(currElem).attr("texttype");
		if( typeof txttype == 'undefined' || txttype == "" || txttype === "e"){
			$("#deflabel").css("display", "");
			$("#formulalabel").css("display", "none");
		}else{			
			$("#deflabel").css("display", "none");
			$("#formulalabel").css("display", "");
		}
		var mv = $(currElem).attr("textmv");
		if(mv != "true"){
			$("[name=text_mv]").prop("checked", false);
			$("#seplabel").css("display", "none");
			$("#seplist").css("display", "none");
			$("#diseplist").css("display", "none");
			$("[name=text_sep]:checked").prop("checked", false);
			$('[name=text_disep]').prop({'checked': false, 'disabled' : true});
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
		}else{
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
			$("[name=text_mv]").prop("checked", true);			
			$("#seplabel").css("display", "");
			if (tr != "names" && tr != "a" && tr != "r") {
				$("#seplist").css("display", "");
				$("input[name=text_disep][value=space]").closest("label").show();
			}else{
				$("#seplist").css("display", "none");
				$("input[name=text_disep][value=space]").closest("label").hide();
			}
			$("#diseplist").css("display", "");
			$("[name=text_sep]:checked").prop("checked", false);
			if (tr != "names" && tr != "a" && tr != "r") {
				var seplist = $(currElem).attr("textmvsep");
				if(seplist === ""){
					seplist = "comma semicolon";
				}
				$('[name=text_disep]').each(function() {
					if (seplist.indexOf($(this).val()) > -1) {
						$(this).prop('disabled', false);
					}
					else {
						$(this).prop('disabled', true);
					}
				});
			}
			else {
				seplist = 'comma';
				$('[name=text_disep]').each(function() {
					$(this).prop('disabled', false);
				});
			}
			Docova.Utils.setField({field: "text_sep", value: seplist, separator: " "});
			Docova.Utils.setField({field: "text_disep", value: $(currElem).attr('textmvdisep')});
		}
	}

	if($(currElem).attr('elem') == "singleuploader")
	{
		var ht = $(currElem).css("height");
		$("#uploader_singleup_height").val ( ht);
	}
	
	if($(currElem).attr('elem') == "ctext"){
		$("#dspCTextAppTitle").text("<Current Application>"); //clear app title
		$('#ctEntryType').val($(currElem).attr('etype'));
		$('#ctextrestriction_container').empty();
		if ($(currElem).attr('etype') == 'V') {
			$('#ctCalculationType').val($(currElem).attr('fnCalculationType'));
			$("#ctext_formula").val('');
			$("span[target='ctext_formula']").text('');
			$('#selectCtViewColumn').val($(currElem).attr('lucolumn'));
			$('#ctFunction').val($(currElem).attr('lufunction'));
			if ($(currElem).attr('fnCalculationType') == 'V') {
				var appID = $(currElem).attr('lunsfname') ? $(currElem).attr('lunsfname') : docInfo.AppID;
				getViewList("selectCtView");
				$("#dspCTextAppTitle").text($(currElem).attr('dspCTextAppTitle'));
				$('#ctLUApplication').val($(currElem).attr('lunsfname'));
				$('#selectCtView').val($(currElem).attr('luview'));
				$('#ctLinkedEmbView').val('');
				$('#ctEmbViewRefreshCode').val('');
				$('span[target=ctEmbViewRefreshCode]').html('');

				var appended = false;
				if ($.trim($(currElem).attr('restrictions')) != '') {
					var restrictions = $(currElem).attr('restrictions').split(';');
					for (var i = 0; i < restrictions.length; i++) {
						var tmp_arr = restrictions[i].split(',');
						$('#ctextrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="'+ tmp_arr[0] +'" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="'+ tmp_arr[1] +'" class="restrictvalue inputEntry"/></li>');
						appended = true;
					}
				}
				if (appended === false) {
					$('#ctextrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="restrictvalue inputEntry"/></li>');
				}
				
				$('TR.ctCalculationTypeV, TR.ctCalculationType').css('display', '');
				$('TR.ctCalculationTypeE').css('display', 'none');
			}
			else {
				//initiate tied up embedded views list
				getElementTypeList('ctLinkedEmbView', 'embeddedview:datatable');
				$('#ctLinkedEmbView').val($(currElem).attr('embViewRef'));
				var escapedval = $(currElem).attr('linkedEmbRefreshScript');
				var unescapedval = safe_quotes_js(escapedval, false, true);
				unescapedval = safe_tags(unescapedval, true);
				$('#ctEmbViewRefreshCode').val(unescapedval);
				$('span[target=ctEmbViewRefreshCode]').html(unescapedval);
				$('#ctLUApplication').val('');
				$('#selectCtView').val('');
				$('TR.ctCalculationTypeV').css('display', 'none');
				$('TR.ctCalculationTypeE, TR.ctCalculationType').css('display', '');
			}
			$('TR.ctEntryTypeV').css('display', '');
			$('TR.ctEntryTypeD').css('display', 'none');
		}
		else {
			$("#ctext_formula").val($(currElem).attr("fformula"));
			$("span[target='ctext_formula']").text($(currElem).attr("fformula"));
			$('TR.ctEntryTypeV').css('display', 'none');
			$('TR[class^="ctCalculationType"]').css('display', 'none');
			$('TR.ctEntryTypeD').css('display', '');
		}
	}
	
	if($(currElem).attr("elem") == "tabset"){
		if( $("#TabLabelName").val() == ""){
			$("#TabLabelName").val($(currElem).find(".ui-tabs-active a").text())
		}
	}	

	//get embedded view properties
	if($(currElem).attr("elem") == "embeddedview"){
		//first clear a couple of options
		$("#dspEmbViewAppTitle").text("<Current Application>") //clear app title
		$("#embLUApplication").val("")//clear app
			
		$("#embHeight").val($(currElem).attr("embviewheight"));
		$("#ViewType").val($(currElem).attr("eViewType"));
		$("#restrictCategory_formula").val($(currElem).attr("fformula"));
		$("span[target='restrictCategory_formula']").text($(currElem).attr("fformula"));
		$('#embrestriction_container').empty();
		var eviewfilter = $(currElem).attr("emb_view_filter") || ""; 
		if (eviewfilter == "rc")
		{
			$('input[name="embviewfilter"]').filter("[value='rc']").attr('checked', true);
			$("#restrict_cat_formula").show();
			$('#extra_restriction').hide();
		}else if ( eviewfilter == "c"){
			$('input[name="embviewfilter"]').filter("[value='c']").attr('checked', true);
			$("#restrict_cat_formula").hide();
			$('#extra_restriction').hide();
		}
		else if (eviewfilter == 'f') {
			$('input[name="embviewfilter"]').filter("[value='f']").attr('checked', true);
			$("#restrict_cat_formula").hide();
			$('#extra_restriction').show();
			var appended = false;
			if ($.trim($(currElem).attr('restrictions')) != '') {
				var restrictions = $(currElem).attr('restrictions').split(';');
				for (var i = 0; i < restrictions.length; i++) {
					var tmp_arr = restrictions[i].split(',');
					$('#embrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="'+ tmp_arr[0] +'" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="'+ tmp_arr[1] +'" class="restrictvalue inputEntry"/></li>');
					appended = true;
				}
			}
			if (appended === false) {
				$('#embrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="restrictvalue inputEntry"/></li>');
			}
		} else {
			$('input[name="embviewfilter"]').filter("[value='n']").attr('checked', true);
			$("#restrict_cat_formula").hide();
			$('#extra_restriction').hide();
		}

		$("#embLUServerName").val($(currElem).attr("luservername"));
		$("#dspEmbViewAppTitle").text($(currElem).attr("dspEmbViewAppTitle"));
		$("#embLUApplication").val($(currElem).attr("lunsfname"));
		$("#selectEmbView_formula").val($(currElem).attr("fdefault"));
		$("span[target='selectEmbView_formula']").text($(currElem).attr("fdefault"));
		
		getViewList("selectEmbView");
		var viewname = $(currElem).attr("embviewname");
		var elemlist = $("#selectEmbView");
		var optsarray = elemlist.find("option").each( function() {
			if ( $(this).val() == viewname || $(this).text() == viewname  )
				$(this).prop('selected', true)
			
		});
		
		if ( $(currElem).attr("embviewsearch") == "1" ){
			$("#EmbViewSearch").prop("checked", true);
		}else{
			$("#EmbViewSearch").prop("checked", false);
		}
	}	
	
	//---Get Style properties - general to all
	getStyleProperties();
	
	//---Get additional style---
	$("#AdditionalStyle").val($(currElem).attr("additional_style"));
	$('span[target=AdditionalStyle]').html($(currElem).attr("additional_style"));
	
	//---Get label properties
	if($(currElem).attr("elem") == "label"){
		if ($(currElem).parentsUntil('table').parent().attr('elem') == 'dataisland') {
			$('#labelDataField').show();
			$('#labeldatafld').val($(currElem).attr('datafld'));
		}
		else {
			$('#labelDataField').hide();
		}
		$("#label_text").val($(currElem).text())
		$("#label_helpertext").val($(currElem).attr("title"))	
	}
	
	//---Get date properties
	if($(currElem).attr("elem") == "date"){
		$("#dateWidth").val( parseInt($(currElem).css("width"), 10) );
		$("#date_value").val( $(currElem).attr("fdefault") );
		$("span[target='date_value']").text($(currElem).attr("fdefault"));
		
		if ($(currElem).attr('fplaceholder') == 'undefined' || $(currElem).attr('fplaceholder') == null) {
			$('#date_placeholder').val('');
			$('span[target="date_placeholder"]').text('');
		}
		else {
			var escapedval = $(currElem).attr('fplaceholder');
			var unescapedval = safe_quotes_js(escapedval, false, true);
			unescapedval = safe_tags(unescapedval, true);
			$('#date_placeholder').val(unescapedval);
			$('span[target="date_placeholder"]').text(unescapedval);
		}

		if($(currElem).attr("ftranslate") == "undefined" || $(currElem).attr("ftranslate") == null){
			$("#date_translate").val("");
			$("span[target='date_translate']").text("");
		}else{
			var escapedcode = $(currElem).attr("ftranslate");
			var unescapedcode = safe_quotes_js(escapedcode, false, true);
			unescapedcode = safe_tags(unescapedcode, true);		
			$("#date_translate").val(unescapedcode);	
			$("span[target='date_translate']").text(unescapedcode);				
		}
		$("#date_type").val($(currElem).attr("datetype"));
		if ($(currElem).attr("isrequired")) {
			$('#date_isrequired').prop('checked', true);
		}
		else {
			$('#date_isrequired').prop('checked', false);
		}
		var optionlist = "";
		if($(currElem).attr("displaytime") == "true"){
			optionlist += (optionlist == "" ? "displaytime" : ";" + "displaytime");
		}
		if($(currElem).attr("displayonlytime") == "true"){
			optionlist += (optionlist == "" ? "displayonlytime" : ";" + "displayonlytime");
		}		
		Docova.Utils.setField({ field: "dateOptions", value: optionlist, separator: ";" } )
		
		var mv = $(currElem).attr("textmv");
		if(mv != "true"){
			$("[name=date_mv]").prop("checked", false);
			$("#date_seplabel").css("display", "none");
			$("#date_seplist").css("display", "none");
			$("#date_diseplist").css("display", "none");
			$("[name=date_sep]:checked").prop("checked", false);
			$('[name=date_disep]').prop({'checked': false, 'disabled' : true});
			$('#date_height').val('');
			$('TR.dateMultiHeight').hide();
		}else{
			var heightvar = $(currElem)[0].style.height || '';		
			$('#date_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.dateMultiHeight').show();
			$("[name=date_mv]").prop("checked", true);			
			$("#date_seplabel").css("display", "");
			$("#date_seplist").css("display", "");
			$("#date_diseplist").css("display", "");
			$("[name=date_sep]:checked").prop("checked", false);			
			var seplist = $(currElem).attr("textmvsep");
			if(seplist === ""){
				seplist = "comma semicolon";
			}
			$('[name=date_disep]').each(function() {
				if (seplist.indexOf($(this).val()) > -1) {
					$(this).prop('disabled', false);
				}
				else {
					$(this).prop('disabled', true);
				}
			});
			Docova.Utils.setField({field: "date_sep", value: seplist, separator: " "});
			Docova.Utils.setField({field: "date_disep", value: $(currElem).attr('datemvdisep')});
		}		
	}	

	//---Get textarea properties
	if ( $(currElem).attr("elem") == "tarea"){
		var tmpEditorType = $(currElem).attr("editortype");
		Docova.Utils.setField({
				field: "EditorType",
				value: tmpEditorType
		});
		Docova.Utils.setField({
			field: "limittextlength",
			value: ($(currElem).attr("limittextlength") == "1" ? "1" : "0")
		});

		Docova.Utils.setField({
			field: "rteditorsettings",
			value: ($(currElem).attr("editorsettings") || "")
		});

		if ($(currElem).attr("isrequired")) {
			$('#tarea_isrequired').prop('checked', true);
		}
		else {
			$('#tarea_isrequired').prop('checked', false);
		}

		if(tmpEditorType == "P"){
			$("#attr_tarea").find("[name=limitlength]").show();
			$('#attr_tarea').find('[name=ta_placeholder]').show();
			if ($(currElem).attr('fplaceholder') == 'undefined' || $(currElem).attr('fplaceholder') == null) {
				$('#tarea_placeholder').val('');
				$('span[target="tarea_placeholder"]').text('');
			}
			else {
				var escapedval = $(currElem).attr('fplaceholder');
				var unescapedval = safe_quotes_js(escapedval, false, true);
				unescapedval = safe_tags(unescapedval, true);
				$('#tarea_placeholder').val(unescapedval);
				$('span[target="tarea_placeholder"]').text(unescapedval);
			}
		}else{
			$('#tarea_placeholder').val('');
			$('span[target="tarea_placeholder"]').text('');
			$("#attr_tarea").find("[name=limitlength]").hide();
			$('#attr_tarea').find('[name=ta_placeholder]').hide();
		}

		if(tmpEditorType == "R"){
			$("#attr_tarea").find("[id=editorsettings]").show();
		}else{
			$("#attr_tarea").find("[id=editorsettings]").hide();
		}


		$("#tarea_width").val($(currElem).attr("editorwidth"));
		$("#tarea_height").val($(currElem).attr("editorheight"));
		$("#tareaDefault").val( $(currElem).attr("fdefault") );
		$("span[target='tareaDefault']").text($(currElem).attr("fdefault"));
	}
	
	///---get section properties
	if ( $(currElem).attr("elemtype") == "section" && $(currElem).attr("elem") != "fields") 
	{
		getSectionAttributes();
	}
	
	///---get section controlled access properties
	if ( $(currElem).attr("elemtype") == "section" && $(currElem).attr("elem") == "fields") 
	{
		if ( $(currElem).attr("enablecasection") == "1"){
			$("#EnableControlledSectionAccess").prop("checked", true); 
		}else{
			$("#EnableControlledSectionAccess").prop("checked", false); 
		}
		
		if ( $(currElem).attr("expandcollapse") == "1"){
			$("#sectionexpandcollapse").prop("checked", true);
			Docova.Utils.setField({field: "caheading_width", value: $(currElem).attr("caheaderwidth")});
			$("#attr_collapsesection").show();
		}else{
			$("#sectionexpandcollapse").prop("checked", false); 
			Docova.Utils.setField({field: "caheading_width", value: ""});			
			$("#attr_collapsesection").hide();
		}
		$("#caccess_formula").val($(currElem).attr("sectionformula"));
		$("span[target='caccess_formula']").text($(currElem).attr("sectionformula"));
		$("#caTitle").val($(currElem).attr("sectiontitle"));
		Docova.Utils.setField({
			field: "cacop",
			value: $(currElem).attr("cacop")
		});
	}
	
	///get attributes for attachments section
	if ( $(currElem).attr("elemtype") == "field" && $(currElem).attr("elem") == "attachments") 
	{
		getSectionAttributes();
	}
	if($(currElem).attr("elem") == "datatable")
	{
		var dttype = $(currElem).attr("dttype");
		Docova.Utils.setField({
			field: "datatableType",
			value: $(currElem).attr("dttype")
		});
		var shfoot = $(currElem).attr("showfooter");
		Docova.Utils.setField({
			field: "datatableShowFooter",
			value: shfoot
		});

		if ( shfoot == "1"){
			$(currElem).find(".datatabletbody").parent().find("tfoot").show();
		}else{
			$(currElem).find(".datatabletbody").parent().find("tfoot").hide();
		}


		if ($.trim($('#datatableForm').find('option').first().text().toLowerCase()) == 'get forms') 
		{
			var curform = $("#DEName").val() + "|" + $("#DEAlias").val();
			getDataTableForms('datatableForm', curform);
		}
		
		if ( dttype == "local"){
			$(".dataTableFormProp").hide();
		}else{
			$(".dataTableFormProp").show();
			var dtfrm = $(currElem).attr("dtform");
			if ( dtfrm && dtfrm != "" ){
				Docova.Utils.setField({
					field: "datatableForm",
					value: dtfrm
				});
				loadDataTableFormFields ( dtfrm);
			}
			
		}
	}
	//---Get button properties
	if($(currElem).attr("elem") == "button"){
		$("#btn_Label").val($(currElem).attr("btnLabel"));
		$("#btn_PrimaryIcon").val($(currElem).attr("btnPrimaryIcon"));
		$("#btn_SecondaryIcon").val($(currElem).attr("btnSecondaryIcon"));
		Docova.Utils.setField({ field: "btn_ShowLabel", value: $(currElem).attr("btnText") });
		Docova.Utils.setField({ field: "btn_Type", value: $(currElem).attr("btntype") });
		if ($(currElem).attr('btnType') == 'CMP') {
			Docova.Utils.setField({ field: 'cmpFormsList', value: $(currElem).attr('btnComposeForm') });
			Docova.Utils.setField({ field: 'cmpIsChild', value: $(currElem).attr('btnIsChild') });
			Docova.Utils.setField({ field: 'cmpInheritValues', value: $(currElem).attr('btnInheritValues') });
			$('#ganttCatField').val('');
			$('TR.btnGsc').hide();
			$('TR.btnCmp').show();
		}
		else if ($(currElem).attr('btnType') == 'GSC') {
			//initiate tied up embedded views list
			getElementTypeList('embeddedGanttView', 'embeddedview');
			$('#ganttCatField').val($(currElem).attr('btnGanttCatField'));
			$('#embeddedGanttView').val($(currElem).attr('embeddedganttview'));
			Docova.Utils.setField({ field: 'cmpFormsList', value: '' });
			Docova.Utils.setField({ field: 'cmpIsChild', value: '' });
			Docova.Utils.setField({ field: 'cmpInheritValues', value: '' });
			$('TR.btnCmp').hide();
			$('TR.btnGsc').show();
		}
		else {
			Docova.Utils.setField({ field: 'cmpFormsList', value: '' });
			Docova.Utils.setField({ field: 'cmpIsChild', value: '' });
			Docova.Utils.setField({ field: 'cmpInheritValues', value: '' });
			$('#ganttCatField').val('');
			$('TR.btnCmp, TR.btnGsc').hide();
		}
	}
	
	//---Get picklist properties
	if ($(currElem).attr('elem') == 'picklist'){
		//first clear a couple of options
		if ($.trim($(currElem).attr("lunsfname")) == '') {
			$("#dspPicklistAppTitle").val('<Current Application>'); //clear app title
			$("#picklistLUApplication").val('');//clear app
		}
		else {
			$("#dspPicklistAppTitle").val($(currElem).attr("dspPlViewAppTitle"));
			$("#picklistLUApplication").val($(currElem).attr("lunsfname"));
		}
		$('#fieldelement_container').empty();
		$('#sourceelement_container').empty();
		
		//initiate tied up embedded views list
		getElementTypeList('plTiedupEmbView', 'embeddedview');

		//initiate views list and app forms list
		if ($.trim($('#selectPicklistView').find('option').first().text().toLowerCase()) == 'get views') {
			getViewList('selectPicklistView');
		}
		if ($.trim($('#selectPicklistForm').find('option').first().text().toLowerCase()) == 'get forms') {
			getAppForms('selectPicklistForm');
		}

		$("#picklist_Label").val($(currElem).attr("plLabel"));
		$("#picklist_PrimaryIcon").val($(currElem).attr("plPrimaryIcon"));
		$("#picklist_SecondaryIcon").val($(currElem).attr("plSecondaryIcon"));
		Docova.Utils.setField({ field: "picklist_ShowLabel", value: $(currElem).attr("plShowLabel") });
		$('#pcklt_DialogTitle').val($(currElem).attr('pldialogtitle'));
		$('#pcklt_Prompt').val($(currElem).attr('plPrompt'));
		$('#plTiedupEmbView').val($(currElem).attr('plRefreshEmbView'));
		$("#selectPicklistView").val($(currElem).attr("luview"));
		$('#picklistAction').val($(currElem).attr('plAction'));
		if ($(currElem).attr('plrestrictTo') == 'undefined' || $(currElem).attr('plrestrictTo') == null) {
			$('#pl_restrictto').val('');
			$('span[target="pl_restrictto"]').text('');
		}
		else {
			var escapedval = $(currElem).attr('plrestrictTo');
			var unescapedval = safe_quotes_js(escapedval, false, true);
			unescapedval = safe_tags(unescapedval, true);
			$('#pl_restrictto').val(unescapedval);
			$('span[target="pl_restrictto"]').text(unescapedval);
		}
		resetPicklistForm($('#picklistAction'));
		if ($(currElem).attr('plAction') == 'D') {
			$('#selectPicklistForm').val($(currElem).attr('luform'));
			resetFormElementsList($('#selectPicklistForm'));
		}
		else {
			var added = false;
			$.each($(currElem).get(0).attributes, function(){
				if (this.name.indexOf('plsf_') == 0) {
					var fname = this.name.replace('plsf_', '');
					$('#sourceelement_container').prepend('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="'+ fname +'" class="sourceelemnts inputEntry" style="width:86%;" /></li><li> = <input type="text" value="'+ this.value +'" class="targetvalue inputEntry"/></li>');
					added = true;
				}
			});
			if (added === false && !$('#sourceelement_container input.sourceelemnts').length) {
				$('#sourceelement_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="sourceelemnts inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="targetvalue inputEntry"/></li>');
			}
		}
		
		$('.allelements').each(function() {
			$(this).val($(currElem).attr('pltf_'+$(this).prop('id')));
		});
	}
	
	//--Get image properties
	if($(currElem).attr("elem") == "image"){
		$("#imageWidth").val(parseInt($(currElem).css("width"),10))
		$("#imageList").val($(currElem).attr("imageName"))
	}
	
	//---Get select/dropdown properties
	if($(currElem).attr("elem") == "select"){
		$("#selectWidth").val(parseInt($(currElem).css("width"), 10))
		Docova.Utils.setField({'field': 'selectAllowNewValues', 'value' : ($(currElem).attr("allownewvals") == "1" ? "1" : "")});
		var optionmethod = $(currElem).attr("optionmethod"); //get the select method
		optionmethod = !optionmethod ? 'manual' : optionmethod;
		$("#selectOptionMethod").val(optionmethod);  
		$("#selectDefault").val($(currElem).attr("fdefault"))  //get the default value
		
		if($(currElem).attr("ftranslate") == "undefined" || $(currElem).attr("ftranslate") == null){
			$("#select_translate").val("");
			$("span[target='select_translate']").text("");
		}else{
			var escapedcode = $(currElem).attr("ftranslate");
			var unescapedcode = safe_quotes_js(escapedcode, false, true);
			unescapedcode = safe_tags(unescapedcode, true);		
			$("#select_translate").val(unescapedcode);	
			$("span[target='select_translate']").text(unescapedcode);	
		}

		if ($(currElem).attr('fplaceholder') == 'undefined' || !$(currElem).attr('fplaceholder')) {
			$('#select_placeholder').val('');
			$('span[target="select_placeholder"]').text('');
		}
		else {
			var escapedval = $(currElem).attr('fplaceholder');
			var unescapedval = safe_quotes_js(escapedval, false, true);
			unescapedval = safe_tags(unescapedval, true);
			$('#select_placeholder').val(unescapedval);
			$('span[target="select_placeholder"]').text(unescapedval);
		}

		if ($(currElem).attr("isrequired")) {
			$('#select_isrequired').prop('checked', true);
		}
		else {
			$('#select_isrequired').prop('checked', false);
		}
				
		//first clear select options
		$("#dspSelectAppTitle").text("<Current Application>") //clear app title
		$("#selectLUApplication").val("")//clear app
		
		//multi value display separator option
		if ($(currElem).attr('selectmv') == 'true'){
			$('#select_mv').prop('checked', true);
			$('#select_seplist').css('display', '');
			$('#select_diseplist').css('display', '');
			var seplist = ($(currElem).attr("selectmvsep") || "");
			if(seplist === ""){
				seplist = "semicolon";
			}
			$('[name=combo_disep]').each(function() {
				if (seplist.indexOf($(this).val()) > -1) {
					$(this).prop('disabled', false);
				}
				else {
					$(this).prop('disabled', true);
				}
			});
			Docova.Utils.setField({field: "select_sep", value: seplist, separator: " "});
			Docova.Utils.setField({field: "combo_disep", value: $(currElem).attr("selectmvdisep"), separator: " "});
		} else {
			$('#select_mv').prop('checked', false);
			$('#select_seplist').css('display', 'none');
			$('#select_diseplist').css('display', 'none');
			$('[name=combo_disep]').prop({'checked': false, 'disabled' : true});
		}
		
		if ( optionmethod == "select"){
			$("#selectLUServerName").val($(currElem).attr("luservername"));
			$("#selectLUApplication").val($(currElem).attr("lunsfname"));
			$("#dspSelectAppTitle").text($(currElem).attr("dspSelectAppTitle"));			
			getViewList("selectLUView");
			$("#selectLUView").val($(currElem).attr("luview"));
			$('#selectLUColumn').val($(currElem).attr('lucolumn'));
			$('#cmbSelectionType').val($(currElem).attr('luselectiontype'));
			
			if ($(currElem).attr('luselectiontype') == 'dblookup')
			{
				//get key type options
				if ( $(currElem).attr("lukeytype")  == "Manual" ){
					$("#select_LUKey").css("display", "block");
					$("#selectLUKey").val($(currElem).attr("lukey"));
					$("#select_LUKeyField").css("display", "none");
					$("#selectLUKeyField").val("- Select -");
				}
				else {
					$("#selectLUKeyField").val($(currElem).attr("lukey"))
					$("#selectLUKey").val('');
					$("#select_LUKey").css("display", "none");
					$("#select_LUKeyField").css("display", "block");
				}
				$("#selectLUKeyType").val($(currElem).attr("lukeytype"))
				$('#selectdblookup').css('display', '');
			}
			else {
				$('#selectdblookup').css('display', 'none');
			}
			$("#selectManualOptions").css("display", "none") //hide the manual options
			$("#selectSelectOptions").css("display", "") //show the select field options
		}
		else if(optionmethod == "formula"){
			$("#selectManualOptions").css("display", "") //show the manual options
			$("#selectSelectOptions").css("display", "none") //hide the select field options
			$("#selectOptions").val($(currElem).attr("fformula"));
			$("span[target='selectOptions']").text($(currElem).attr("fformula"));
		}
		else if(optionmethod == "manual"){
			$("#selectManualOptions").css("display", "") //show the manual options
			$("#selectSelectOptions").css("display", "none") //hide the select field options
			var elemID = $(currElem).prop("id")
			var optionlist = $(currElem).attr("optionlist").replace(/;/g, "\n")
			$("#selectOptions").val(optionlist);
			$("span[target='selectOptions']").text(optionlist);
		}
	}
	
	//---Get checkbox properties
	if($(currElem).attr("elem") == "chbox"){
		var optionmethod = $(currElem).attr("optionmethod"); //get the select method
		optionmethod = !optionmethod ? 'manual' : optionmethod;
		$("#element_id").val($(currElem).prop("name"));
		$("#cbWidth").val($(currElem).attr("tblWidth"));
		$("#cbDefault").val($(currElem).attr("fdefault"));	
		
		if($(currElem).attr("ftranslate") == "undefined" || $(currElem).attr("ftranslate") == null){
			$("#cb_translate").val("");
			$("span[target='cb_translate']").text("");			
		}else{
			var escapedcode = $(currElem).attr("ftranslate");
			var unescapedcode = safe_quotes_js(escapedcode, false, true);
			unescapedcode = safe_tags(unescapedcode, true);		
			$("#cb_translate").val(unescapedcode);		
			$("span[target='cb_translate']").text(unescapedcode);			
		}
		
		$("#cbOptionMethod").val(optionmethod);
		$("#cbColumns").val($(currElem).attr("colno"));

		if ($(currElem).attr("isrequired")) {
			$('#cb_isrequired').prop('checked', true);
		}
		else {
			$('#cb_isrequired').prop('checked', false);
		}
		
		//first clear cb options
		if ($(currElem).attr("dspCBAppTitle") != "" )
			$("#dspCBAppTitle").val($(currElem).attr("dspCBAppTitle")) //clear app title
		else
			$("#dspCBAppTitle").text("<Current Application>") //clear app title
		$("#cbLUApplication").val("")//clear app		

		//display separator
		Docova.Utils.setField({field: "chbox_disep", value: $(currElem).attr('chmvdisep')});
		
		// get optionmethod options
		if(optionmethod == "manual"){ //get the options
			$("#cbManualOptions").css("display", "") //show the manual options
			$("#cbSelectOptions").css("display", "none") //hide the select field options
			var optionlist = $(currElem).attr("optionlist").replace(/;/g, "\n")
			$("#cbOptions").val(optionlist);
			$("span[target='cbOptions']").text(optionlist);
		}
		else if(optionmethod == "select"){ //else select method is select
			$("#cbLUServerName").val($(currElem).attr("luservername"))
			$("#dspCBAppTitle").text($(currElem).attr("dspCBAppTitle"))			
			$("#cbLUApplication").val($(currElem).attr("lunsfname"));
			getViewList("cbLUView");
			$("#cbLUView").val($(currElem).attr("luview"))
			$('#cbLUColumn').val($(currElem).attr('lucolumn'));
			$('#cbSelectionType').val($(currElem).attr('luselectiontype'));
			
			if ($(currElem).attr('luselectiontype') == 'dblookup')
			{
				// get key type options
				if ( $(currElem).attr("lukeytype")  == "Manual" ){
					$("#cb_LUKey").css("display", "block");
					$("#cbLUKey").val($(currElem).attr("lukey"));
					$("#cb_LUKeyField").css("display", "none");
					$("#cbLUKeyField").val("- Select -");
				}
				else {
					getFieldList("cbLUKeyField");
					$("#cbLUKeyField").val($(currElem).attr("lukey"))
					$("#cbLUKey").val('');
					$("#cb_LUKey").css("display", "none");
					$("#cb_LUKeyField").css("display", "block");
				}
				$("#cbLUKeyType").val($(currElem).attr("lukeytype"))
				$('#cbdblookup').css('display', '');
			}
			else {
				$('#cbdblookup').css('display', 'none');
			}
			$("#cbManualOptions").css("display", "none") //hide the manual options
			$("#cbSelectOptions").css("display", "") //show the select field options
		}
		else if(optionmethod == "formula"){
			$("#cbManualOptions").css("display", "") //show the manual options
			$("#cbSelectOptions").css("display", "none") //hide the select field options
			$("#cbOptions").val($(currElem).attr("fformula"));
			$("span[target='cbOptions']").text($(currElem).attr("fformula"));
		}
	}

	//---Get radio properties
	if($(currElem).attr("elem") == "rdbutton"){
		var optionmethod = $(currElem).attr("optionmethod"); //get the select method
		optionmethod = !optionmethod ? 'manual' : optionmethod;
		$("#element_id").val($(currElem).prop("name"));
		$("#rbWidth").val($(currElem).attr("tblWidth"))
		$("#rbDefault").val($(currElem).attr("fdefault"));	
		
		if($(currElem).attr("ftranslate") == "undefined" || $(currElem).attr("ftranslate") == null){
			$("#rb_translate").val("");
			$("span[target='rb_translate']").text("");			
		}else{
			var escapedcode = $(currElem).attr("ftranslate");
			var unescapedcode = safe_quotes_js(escapedcode, false, true);
			unescapedcode = safe_tags(unescapedcode, true);		
			$("#rb_translate").val(unescapedcode);	
			$("span[target='rb_translate']").text(unescapedcode);
		}			
		$("#rbColumns").val($(currElem).attr("colno"));
		$("#rbOptionMethod").val(optionmethod)  //set the select method

		if ($(currElem).attr("isrequired")) {
			$('#rdb_isrequired').prop('checked', true);
		}
		else {
			$('#rdb_isrequired').prop('checked', false);
		}
		
		//first clear rb options
		$("#dspRBAppTitle").text("<Current Application>") //clear app title
		$("#rbLUApplication").val("")//clear app
				
		// get option method options
		if(optionmethod == "manual"){ //get the options
			$("#rbManualOptions").css("display", "") //show the manual options
			$("#rbSelectOptions").css("display", "none") //hide the select field options
			var optionlist = $(currElem).attr("optionlist").replace(/;/g, "\n")
			$("#rbOptions").val(optionlist) 
			$("span[target='rbOptions']").text(optionlist);
		}
		else if(optionmethod == "select"){ //else select method is select
			$("#rbLUServerName").val($(currElem).attr("luservername"))
			$("#dspRBAppTitle").val($(currElem).attr("dspRBAppTitle"))			
			$("#rbLUApplication").val($(currElem).attr("lunsfname"))
			getViewList("rbLUView");		
			$("#rbLUView").val($(currElem).attr("luview"))
			$('#rbLUColumn').val($(currElem).attr('lucolumn'));
			$('#rbSelectionType').val($(currElem).attr('luselectiontype'));

			if ($(currElem).attr('luselectiontype') == 'dblookup')
			{
				// get key type options
				if ($(currElem).attr("lukeytype")  == "Manual"){
					$("#rb_LUKey").css("display", "block");
					$("#rbLUKey").val($(currElem).attr("lukey"));
					$("#rb_LUKeyField").css("display", "none");
					$("#rbLUKeyField").val("- Select -");
				}
				else {
					getFieldList("rbLUKeyField");
					$("#rbLUKeyField").val($(currElem).attr("lukey"))
					$("#rb_LUKey").css("display", "none");
					$("#rb_LUKeyField").css("display", "block");
					$("#rbLUKey").val('');
				}
				$("#rbLUKeyType").val($(currElem).attr("lukeytype"))
				$('#rbdblookup').css('display', '');
			}

			$("#rbManualOptions").css("display", "none") //hide the manual options
			$("#rbSelectOptions").css("display", "") //show the select field options
		}
		else if(optionmethod == "formula"){
			$("#rbManualOptions").css("display", "") //show the manual options
			$("#rbSelectOptions").css("display", "none") //hide the select field options
			$("#rbOptions").val($(currElem).attr("fformula")); 				
			$("span[target='rbOptions']").text($(currElem).attr("fformula"));
		}
	}
	
	if($(currElem).attr('elem') == "toggle"){
		$("#element_id").val($(currElem).prop("name"));
		$("#tgDefault").val($(currElem).attr("fdefault"));
		$('#toggleOnColor').val($(currElem).attr('toggleoncolor') ? rgb2hex($(currElem).attr('toggleoncolor')) : '#3CB371');
		$('#toggleOffColor').val($(currElem).attr('toggleoffcolor') ? rgb2hex($(currElem).attr('toggleoffcolor')) : '#FA8072');
	}

	if ($(currElem).attr('elem') == 'slider')
	{
		getFieldList("sld_bindtoelement");
		$("#element_id").val($(currElem).prop("id"));
		$('#sld_bindtoelement').val($(currElem).attr('boundelem'));
		$('#sld_minvalue').val($(currElem).attr('minvalue'));
		$('#sld_maxvalue').val($(currElem).attr('maxvalue'));
		$('#sld_length').val($(currElem).attr('sliderlength'));
		Docova.Utils.setField({ field: 'sld_orientation', value: $(currElem).attr('orientation') });
		$('#sldActivationColor').val($(currElem).attr('activecolor') ? rgb2hex($(currElem).attr('activecolor')) : '');
		$('#sldActivationColor').prev('input').val($(currElem).attr('activecolor') ? rgb2hex($(currElem).attr('activecolor')) : '');
	}
	
	//---Get subform properties (different than getting attribs in getSectionAttributes as that handles fields for sections on doctype
	if($(currElem).attr("elem") == "subform"){
		//$("#subformList").val($(currElem).attr("subform"))
		var subselected = $(currElem).attr("subform");
		var elemlist = $("#subformList");
		var optsarray = elemlist.find("option").each( function() {
			if ( $(this).val() == subselected || $(this).text() == subselected  )
				$(this).prop('selected', true)
			
		});
		$("#subformInsertMethod").val($(currElem).attr("insertmethod"))
		$("#subform_formula").val($(currElem).attr("subformformula"));
		$("span[target='subform_formula']").text($(currElem).attr("subformformula"));

		if($(currElem).attr("insertmethod") == "Selected"){
			$("#subform_formula").val(""); //clear subform_formula field
			$("span[target='subform_formula']").text(""); //clear subform_formula field
			$(currElem).attr("subformformula", ""); //clear subformformula attribute on element
			$(".subformselected").css("display", ""); //show Subform list field
			$(".subformcomputed").css("display", "none"); //hide computed formula
		}else{
			$("#subformList").val("- Select -"); //set subform list to -Select-
			$(".subformselected").css("display", "none"); //hide Subform list field
			$(".subformcomputed").css("display", ""); //show computed formula
		}
	}
	
	//---Get outline/iframe properties
	if($(currElem).attr("elem") == "outline"){
		var ow = $(currElem).attr("width");
		if(ow.indexOf("px")>-1){
			ow = parseInt(ow, 10);
		}
		$("#outlineWidth").val(ow);
		var oh = $(currElem).attr("height");
		if(oh.indexOf("px")>-1){
			oh = parseInt(oh, 10);
		}		
		$("#outlineHeight").val(oh);
		$("#outlineList").val($(currElem).attr("outlinename"));
		$("#outlineExpand").val($(currElem).attr("expand"));
	}
	
	//---Get chart properties
	if($(currElem).attr("elem") == "chart"){
		//reset all chart legend items, source items and axis items
		$('#chart_legenditems, #chart_sourceitems, #chart_axisitems, #chart_valueitems').html('');
		$("#chartTitle").val($(currElem).attr("charttitle")||"");
		$("#chartHorzLabel").val($(currElem).attr("charthorzlabel")||"");
		$("#chartVertLabel").val($(currElem).attr("chartvertlabel")||"");
		var hv = ($(currElem).attr("charthidevalues")||"");
		$("#chartHideValues").prop("checked", (hv == "1" || hv=="true"));
		var hl = ($(currElem).attr("charthidelegend")||"");
		$("#chartHideLegend").prop("checked", (hl == "1" || hl=="true"));
		
		var cw = ($(currElem).attr("chartwidth")||"");
		if(cw.indexOf("px")>-1){
			cw = parseInt(cw, 10);
		}
		$("#chartWidth").val(cw);
		
		var ch = ($(currElem).attr("chartheight")||"");
		if(ch.indexOf("px")>-1){
			ch = parseInt(ch, 10);
		}		
		$("#chartHeight").val(ch);
		
		var ct = $(currElem).attr("charttype");
		$("#chartType").val(ct);
		
		if(ct == "pie" || ct == "doughnut"){
			$("#disp_chart_hidevalues").css("display", "");
			$("#chart_legenditems").closest("tr").css("display", "none");
			$("#chart_legenditems").html("");			
		}else{
			$("#disp_chart_hidevalues").css("display", "none");	
			$("#chartHideValues").prop("checked", false);
			$("#chart_legenditems").closest("tr").css("display", "");
		}
		
		var sourcetype = $(currElem).attr("chartsourcetype");
		var source = $(currElem).attr("chartsource");
		$("#chartSourceType").val(sourcetype);
		$("#chartDataFunc").val((sourcetype == "function" ? source : ""));		
		
		$("#dspChartAppTitle").val($(currElem).attr("dspChartAppTitle"));
		$("#chartLUApplication").val($(currElem).attr("lunsfname"));
		
		if(sourcetype == "" || sourcetype == "- Select -"){
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "none");	
			$("#disp_chart_fields").css("display", "none");	
			$("#disp_chart_rcat").css("display", "none");
		}else if(sourcetype == "function"){
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "");
			$("#disp_chart_fields").css("display", "none");
			$("#disp_chart_rcat").css("display", "none");			
		}else{
			$("#disp_chart_app").css("display", "");
			$("#disp_chart_source").css("display", "");
			$("#disp_chart_func").css("display", "none");
			if(sourcetype == "view"){
				$("#chart_rcat_formula").val($(currElem).attr("fformula")||"");
				$("a[target='chart_rcat_formula']").prev().text($(currElem).attr("fformula")||"");
				$("#disp_chart_fields").css("display", "");	
				$("#disp_chart_rcat").css("display", "");
				getViewList("selectChartSource");
			}else if(sourcetype == "agent"){
				$("#disp_chart_fields").css("display", "none");	
				$("#disp_chart_rcat").css("display", "none");
				getAgentList("selectChartSource");				
			}
			$("#selectChartSource").val((sourcetype != "function" ? source : ""));		

			var elemlist = $("#selectChartSource");
			var optsarray = elemlist.find("option").each( function() {
				if ( $(this).val() == source || $(this).text() == source  )
					$(this).prop('selected', true)				
			});		
			
			if(sourcetype == "view"){
				var fieldlisthtml = "";
				var chartlegenditems = ($(currElem).attr("chartlegenditems") || "");
				if(chartlegenditems != ""){
					chartlegenditems = chartlegenditems.split(",");
					for(var i=0; i<chartlegenditems.length; i++){
						var chartitem = chartlegenditems[i].split("|");
						fieldlisthtml += '<span id="cli_' + chartitem[0].toLowerCase() + '" class="chart_drag_item" sourcefield="' + chartitem[0] + '" sourcefieldtitle="' + chartitem[1] + '" draggable="true" ondragstart="chartitemdragged(event)">' + chartitem[1] + '</span>';			
					}
				}
				$("#chart_legenditems").html(fieldlisthtml);
												
				var fieldlisthtml = "";
				var chartaxisitems = ($(currElem).attr("chartaxisitems") || "");
				if(chartaxisitems != ""){
					chartaxisitems = chartaxisitems.split(",");
					for(var i=0; i<chartaxisitems.length; i++){
						var chartitem = chartaxisitems[i].split("|");
						fieldlisthtml += '<span id="cai_' + chartitem[0].toLowerCase() + '" class="chart_drag_item" sourcefield="' + chartitem[0] + '" sourcefieldtitle="' + chartitem[1] + '" draggable="true" ondragstart="chartitemdragged(event)">' + chartitem[1] + '</span>';			
					}
				}
				$("#chart_axisitems").html(fieldlisthtml);

				var fieldlisthtml = "";
				var chartvalueitems = ($(currElem).attr("chartvalueitems") || "");
				var idcount = {};
				if(chartvalueitems != ""){
					chartvalueitems = chartvalueitems.split(",");
					for(var i=0; i<chartvalueitems.length; i++){
						var chartitem = chartvalueitems[i].split("|");
						var newname = chartitem[1];
						var valop = chartitem[2];
						if(valop != ""){
							newname = valop.substr(0,1).toUpperCase() + valop.substr(1).toLowerCase()  + " of " + newname;
						}
						var newitemid = chartitem[0].toLowerCase();
						if(idcount.hasOwnProperty(newitemid)){
							idcount[newitemid] = idcount[newitemid] + 1;
							newitemid = newitemid + "_" + (idcount[newitemid] - 1).toString(); 
						}else{
							idcount[newitemid] = 1;
						}
						fieldlisthtml += '<span id="cvi_' + newitemid + '" class="chart_drag_item" sourcefield="' + chartitem[0] + '" sourcefieldtitle="' + chartitem[1] + '" valop="' + valop + '" draggable="true" ondragstart="chartitemdragged(event)">' + newname + '</span>';			
					}
				}
				$("#chart_valueitems").html(fieldlisthtml);	
				
				$("span.chart_drag_item:not(.chart_source_item)").off("contextmenu").on("contextmenu", function(e){ menuHandler(e); });
				
				setTimeout(function(){getViewColumns("chart_sourceitems");}, 500);
			}
		}
	}	

	if ($(currElem).attr("elem") == "appelement")
	{
		if ($.trim($('#appelementLUApplication').val()) !== '')
		{
			getViewList('selectAppElementSource');
		}
		$('#appelementSource').val($(currElem).attr('appelementsourcetype'));
		$('#appelementLUApplication').val($(currElem).attr('appelementappid'));
		$('#dspAppElementTitle').val($(currElem).attr('appelementapptitle'));
		$('#selectAppElementSource').val($(currElem).attr('appelementsource'));
	}

	if ($(currElem).attr("elem") == "googlemap")
	{
		$('#googleLatitude').val($(currElem).attr('googlelatitude'));
		$('#googleLongitude').val($(currElem).attr('googlelongitude'));
		$('#googlePostalCode').val($(currElem).attr('googlepostalcode'));
	}
	
	if ($(currElem).attr('elem') == 'barcode')
	{
		var barcode_type = $(currElem).attr('barcodetype');
		$('#barcodeType').val(barcode_type);
		$('#barcodeData').val($(currElem).attr('barcodedata'));
		if (barcode_type != 'qrcode')
		{
			$('input[name="readableValue"][value="'+ $(currElem).attr('humanreadable') +'"]').prop('checked', true);
			$('input[name="readableValLocation"][value="'+ $(currElem).attr('readablevallocation') +'"]').prop('checked', true);
			$('input[name="readableValAlignment"][value="'+ $(currElem).attr('barcodetxtalignment') +'"]').prop('checked', true);
			$('input[name="measureUnit"][value="'+ $(currElem).attr('measureunit') +'"]').prop('checked', true);
			$('TR.qrcode_hide').show();
			$('TR.qrcode_show').hide();
			$('span.munits').text($(currElem).attr('measureunit'));
		}
		else {
			$('TR.qrcode_hide').hide();
			$('TR.qrcode_show').show();
			$('span.munits').text('px');
			$('input[name="qrcoderendering"][value="'+ $(currElem).attr('qrcoderender') +'"]').prop('checked', true);
		}
		$('#barcodeWidth').val($(currElem).attr('barcodewidth'));
		$('#barcodeHeight').val($(currElem).attr('barcodeheight'));
		if (barcode_type == 'codabar' ||  barcode_type == 'code39' || barcode_type == 'code39ascii' || barcode_type == 'i2of5') {
			$('#thicknessRatio').val($(currElem).attr('barcodewidthratio'));
			$('#td_thickness_ratio').show();
		}
		else {
			$('#thicknessRatio').val('');
			$('#td_thickness_ratio').hide();
		}
		$('#barcodeForeColor').val($(currElem).attr('barcodeforecolor') ? rgb2hex($(currElem).attr('barcodeforecolor')) : '#000000');
		$('#barcodeBackColor').val($(currElem).attr('barcodebackcolor') ? rgb2hex($(currElem).attr('barcodebackcolor')) : '#FFFFFF');
	}

	if ($(currElem).attr("elem") == "weather")
	{
		$('#cityname').val($(currElem).attr('city'));
		$('#forecastfivedays').prop('checked', $(currElem).attr('forecastfivedays') == 'enabled' ? true : false);
		$('input[name="tempratureunit"][value="'+ ($(currElem).attr('tempratureunit') ? $(currElem).attr('tempratureunit') : 'celsius') +'"]').prop('checked', true);
	}
	
	if ($(currElem).attr('elem') == 'counterbox') {
		$("#dspCounterBoxAppTitle").text("<Current Application>"); //clear app title
		$('#cbEntryType').val($(currElem).attr('etype') ? $(currElem).attr('etype') : 'D');
		$('#cbrestriction_container').empty();
		if ($(currElem).attr('etype') == 'V') {
			var appID = $(currElem).attr('lunsfname') ? $(currElem).attr('lunsfname') : docInfo.AppID;
			getViewList("selectCtrbView");
			getViewColumnList('selectCtrbViewColumn', appID, $(currElem).attr('luview'));
			$('TR.cbEntryTypeV').show();
			$('TR.cbEntryTypeD').hide();
			$("#dspCounterBoxAppTitle").text($(currElem).attr('dspCounterBoxAppTitle'));
			$('#ctrbLUApplication').val($(currElem).attr('lunsfname'));
			$('#selectCtrbView').val($(currElem).attr('luview'));
			$('#selectCtrbViewColumn').val($(currElem).attr('lucolumn'));
			$('#ctbFunction').val($(currElem).attr('lufunction'));
			var appended = false;
			if ($.trim($(currElem).attr('restrictions')) != '') {
				var restrictions = $(currElem).attr('restrictions').split(';');
				for (var i = 0; i < restrictions.length; i++) {
					var tmp_arr = restrictions[i].split(',');
					$('#cbrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="'+ tmp_arr[0] +'" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="'+ tmp_arr[1] +'" class="restrictvalue inputEntry"/></li>');
					appended = true;
				}
			}
			if (appended === false) {
				$('#cbrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="restrictvalue inputEntry"/></li>');
			}
		}
		else {
			$('TR.cbEntryTypeV').hide();
			$('TR.cbEntryTypeD').show();
			$('#counterboxValue').val($(currElem).attr('countervalue'));			
		}
		$('#counterTitle').val($(currElem).attr('countertitle'));
		$('input[name="counterDir"][value="'+ ($(currElem).attr('counterdir') ? $(currElem).attr('counterdir') : 'up') +'"]').prop('checked', true);
		$('#counterStartOn').val($(currElem).attr('counterstarton') ? $(currElem).attr('counterstarton') : 'topview');
		$('#counterIcon').val($(currElem).attr('countericon'));
		$('#counterIconPos').val($(currElem).attr('countericonpos'));
		$('#counterIconSize').val($(currElem).attr('countericonsize'));
		$('#counterUnit').val($(currElem).attr('counterunit'));
		$('#counterUnitPos').val($(currElem).attr('counterunitpos'));
		$('#counterSpeed').val($(currElem).attr('counterspeed') ? $(currElem).attr('counterspeed') : 2000);
		$('#counterFrame').prop('checked', $(currElem).attr('counterframe') == 'yes' ? true : false);
		$('#counterTextColor').val($(currElem).attr('countertextcolor'));
		$('#counterFontSize').val($(currElem).attr('counterfontsize') ? $(currElem).attr('counterfontsize') : 20);
		$('#counterSubtitleColor').val($(currElem).attr('countersubtitlecolor'));
		$('#counterSubtitleSize').val($(currElem).attr('countersubtitlesize') ? $(currElem).attr('countersubtitlesize') : 20);
	}
	
	//---Get other properties (TD)
	if($(currElem).attr("elem") == null || $(currElem).attr("elem") == "undefined"){
		$("#textvalign").val($(currElem).css("vertical-align"));
		$("#attr_td").css("display", "");
		if($(currElem).prop('tagName') && $(currElem).prop("tagName").toUpperCase() == "TD"){
			$("#btn-CellBorderApplyAll").css("display", "");
			$("#btn-CellBorderReset").css("display", "");
			$("#btn-CellPaddingApplyAll").css("display", "");
			$("#btn-CellColorApplyRow").css("display", "");			
		}
	}

	if ($(currElem).attr('elem') == 'auditlog') 
	{
		$("#docova_auditlog_height").val ( $(currElem).attr ( "alogheight") );
		$('input[name="docova_auditlog_filter"][value="'+ ($(currElem).attr('filter') ? $(currElem).attr('filter') : '0') +'"]').prop('checked', true);
		$('#docova_auditlog_collapse').prop('checked', $(currElem).attr('collapsible') == '1' ? true : false);
		
	}
	
	getHideWhenProperties();
	getEvents();
	
}

function closePar(){
	var dxl = "";
	
	if(openpar === true){
		if(openparwithrun === true){
			dxl += "</run>"
		}
		dxl += "</par>";
		openpar = false;
	}
	openparwithrun = false;
	
	return dxl;
}

function openPar(pardef, norun){
	var dxl = "";
	
	if(openpar === true){
		if(openparwithrun === true){
			dxl += "</run>"
		}
		dxl += "</par>";
		openpar = false;
	}
	openparwithrun = false;
	
	dxl += "<par def='";
	if(typeof pardef !== 'undefined'){
		dxl += pardef.toString();
		lastpardefused = pardef;
	}else{
		dxl += "1";
		lastpardefused = 1;
	}
	dxl += "'>";	
//	if(!norun){
//		dxl += "<run>";
//		//dxl += "<run html='true'>";
//		openparwithrun = true;
//	}
	openpar = true;
	
	return dxl;	
}

function removeTableResizers(inobj)
{
	
	var coll;
	if ( inobj )
	{
		coll = inobj.find("table tr th:not(:last-child)");
	}else{
		coll = $("table tr th:not(:last-child)");
	}
	coll.each ( function (){
		
		var parentdiv = $(this).closest(".datatable");
		if ( parentdiv && parentdiv.attr("elem") && parentdiv.attr("elem") == "datatable")
		{
			$(this).removeClass("selectable ui-resizable");
			$(this).removeAttr("contenteditable");
		}else{
			$(this).html("");
		}
	})
}

function popUndo(){
	hideSelectors()
	$("#layout").html(arrUndo.pop());
	setSelectable();
	//$("table tr th:not(:last-child)").html("")
	removeTableResizers();
	resetRulers();
	return;
}
function pushUndo(){
	arrUndo.push($("#layout").html())
	if(arrUndo.length > 20){
		arrUndo.shift();
	}
	return;
}

function runMacro(){
		var html = $('#divMultiLinePopup').html();
		var dlgTextInput = window.top.Docova.Utils.createDialog({
			id: "divDlgMultiLineInput",
			title: "Enter JavaScript Macro",
			height: 313,
			width: 800,
			dlghtml: html,
			resizable: true,
			sourcewindow: window,
			sourcedocument: document,
			onOpen : function () 
			{				
				$("#divDlgMultiLineInput #docova_formula_helper", window.top.document).hide();
				$("#divDlgMultiLineInput #btnInsertFormula", window.top.document).hide();				
				var dlg = $("#divDlgMultiLineInput", window.top.document);
				var tbox = $("#divDlgMultiLineInput #multilineinput", window.top.document);
			},

			buttons: {
				"Run Macro": function (){
					var tbox = $("#divDlgMultiLineInput #multilineinput", window.top.document);
					var macrocode = tbox.val();
					if(jQuery.trim(macrocode) !== ""){
						eval(macrocode);
					}
					dlgTextInput.closeDialog();
				},
				"Cancel": function(){
					dlgTextInput.closeDialog();
				}
			}
		});
	
	return;
}

function isElementVisible (el, holder) {
  holder = holder || document.body
  var elrect= el.getBoundingClientRect();
  var top = elrect.top;
  var bottom = elrect.bottom;
  var height = elrect.height;

  var holderRect = holder.getBoundingClientRect();

  return top <= holderRect.top
    ? holderRect.top - top <= height
    : bottom - holderRect.bottom <= height
}

function loadDataTableFormFields(formid)
{
	var elementURL = docInfo.PortalWebPath + "ReadContent/" + formid + "?OpenDocument&DesignElement=Form&AppID=" + docInfo.AppID + "&" + (new Date()).valueOf();	
	var fldhtml = "";
	var ind=1;
	jQuery.ajax({
    	url: elementURL,
    	async: false, 
		success: function (results) 
		{
			$(results).find("[elem='text'], [elem='select'], [elem='date'], [elem='chbox'], [elem='rdbutton']").each ( function ()
			{
				var objid = $(this).attr("id");
				if ( $("[id='" + objid + "']").length == 0  )
				{
					var inhtml =  $("<div></div>").append($(this)).html() ;
					fldhtml += "<span ondragstart='dragdtfield(event)' class='dtfieldtempl' draggable='true' id='dtfieldtempl_" + ind + "' templ='" + encodeURIComponent(inhtml) + "'>" + objid + "</span>";
					ind ++;
				}	
			})
			$("#datatablefields").html(fldhtml);

		}
	});
}

function setPropertiesTriggers()
{

	$("[name='datatableType']").on("change", function (){
		var dttype = Docova.Utils.getField("datatableType");
		if ( dttype == "local"){
			$(".dataTableFormProp").hide();
		}else{
			$(".dataTableFormProp").show();
		}
	})

	$("[name='datatableShowFooter']").on("change", function (){
		var dttype = Docova.Utils.getField("datatableShowFooter");
		if ( dttype == "1"){
			$(currElem).find(".datatabletbody").parent().find("tfoot").show();
		}else{
			$(currElem).find(".datatabletbody").parent().find("tfoot").hide();
		}
	})

	$("#datatableForm").on("change", function (){
		var formid = $(this).val();
		loadDataTableFormFields(formid);
		
	})
	//icon text alignment
	$("#docova_icon_valign").on("change", function ()
	{
		$(currElem).css("vertical-align", $(this).val());
	})
	
	$("#mtab, .grid-stack-item-content").scroll(function() {
		hideSelectors();
		var that = this;
	    clearTimeout($.data(this, 'scrollTimer'));
	    $.data(this, 'scrollTimer', setTimeout(function() 
	    {
	     	var p = currElem;
	     	var k = that;
	     	if ( currElem && isElementVisible ( p, k)){
	     		setSelectors();
	     	}

	     	if ( !currElem) setSelectors();
	        
	    }, 250));
	});

	$(".docova_quick_width").on("click", function ()
	{
		var rows = $(this).text();
		var grid = $('.grid-stack').data('gridstack');
	    var gsi = $(currElem).parent();
		if ( rows == "1/2"){
			
	        grid.resize(gsi,6,null);
	        
		}else if ( rows == "1/3"){
			 grid.resize(gsi,4,null);

		}else if ( rows == "1/4"){
			grid.resize(gsi,3,null);
					
		}else if ( rows == "2/3"){
			 grid.resize(gsi,8,null);
			
		}else if ( rows == "3/4"){
			 grid.resize(gsi,9,null);
			
		}
	})


	//---border-style---
	$("#border_style").on("change", function(){
		$(currElem).css("border-style", $(this).val());
		setSelectors();
	});
	$("#borderall").on("change", function(){
		if($("#borderall").val() == 0){
			$(currElem).css("border-top-width", "0px")
			$(currElem).css("border-right-width", "0px")
			$(currElem).css("border-bottom-width", "0px")
			$(currElem).css("border-left-width", "0px")
			$(currElem).addClass("bordertop0 borderright0 borderbottom0 borderleft0");
			$("#bordertop").val("0");
			$("#borderright").val("0");
			$("#borderbottom").val("0");
			$("#borderleft").val("0");
		}else{
			$(currElem).removeClass("bordertop0 borderright0 borderbottom0 borderleft0");
			$(currElem).css("border-width", $(this).val()+"px");	
			$("#borderleft").val($(this).val());
			$("#borderright").val($(this).val());
			$("#bordertop").val($(this).val());
			$("#borderbottom").val($(this).val());
		}
		$(currElem).css("border-style", $("#border_style").val());
		setSelectors();
	});
	$("#borderleft").on("change", function(){
		if($("#borderleft").val() == 0){
			$(currElem).css("border-left-width", "0px")
			$(currElem).addClass("borderleft0");
			if($("#bordertop").val() == 0 & $("#borderright").val() == 0 & $("#borderright").val() == 0){
				$("#borderall").val("0")
			}
		}else{
			$(currElem).removeClass("borderleft0");
			$(currElem).css("border-left-width", $(this).val()+"px");
		}
		$(currElem).css("border-style", $("#border_style").val());
		setSelectors();
	});
	$("#borderright").on("change", function(){
		if($("#borderright").val() == 0){
			$(currElem).css("border-right-width", "0px")
			$(currElem).addClass("borderright0");
			if($("#borderbottom").val() == 0 & $("#borderleft").val() == 0 & $("#bordertop").val() == 0){
				$("#borderall").val("0")
			}
		}else{
			$(currElem).removeClass("borderright0");
			$(currElem).css("border-right-width", $(this).val()+"px");
		}
		$(currElem).css("border-style", $("#border_style").val());
		setSelectors();
	});
	$("#bordertop").on("change", function(){
		if($("#bordertop").val() == 0){
			$(currElem).css("border-top-width", "0px")
			$(currElem).addClass("bordertop0");
			if($("#borderright").val() == 0 & $("#borderbottom").val() == 0 & $("#borderleft").val() == 0){
				$("#borderall").val("0")
			}			
		}else{
			$(currElem).removeClass("bordertop0");
			$(currElem).css("border-top-width", $(this).val()+"px");
		}
		$(currElem).css("border-style", $("#border_style").val());
		setSelectors();
	});
	$("#borderbottom").on("change", function(){
		if($("#borderbottom").val() == 0){
			$(currElem).css("border-bottom-width", "0px")
			$(currElem).addClass("borderbottom0");
			if($("#borderleft").val() == 0 & $("#bordertop").val() == 0 & $("#borderright").val() == 0){
				$("#borderall").val("0")
			}			
		}else{
			$(currElem).removeClass("borderbottom0");
			$(currElem).css("border-bottom-width", $(this).val()+"px");
		}
		$(currElem).css("border-style", $("#border_style").val());
		setSelectors();
	});
	
	$("#bordercolor").on("change", function(){
		$(currElem).css("border-color", $(this).val());
		$(currElem).css("border-style", $("#border_style").val());
	});
	//---font-style---
	$("#fontname").on("change", function(){
		$(currElem).css("font-family", $(this).val());
		setSelectors();
	});
	$("#fontsize").on("change", function(){
		$(currElem).css("font-size", $(this).val()+"px");
		setSelectors();
	});
	$("#fontweight").on("change", function(){
		$(currElem).css("font-weight", $(this).val());
		setSelectors();
	});
	$("#fontstyle").on("change", function(){
		$(currElem).css("font-style", $(this).val());
		setSelectors();
	});
	$("#textalign").on("change", function(){
		if($(currElem).attr("elemtype") == "field"){
			$(currElem).parent().css("text-align", $(this).val());
		}else{
			$(currElem).css("text-align", $(this).val());
		}
		setSelectors();
	});
	$("#fontcolor").on("change", function(){
		$(currElem).css("color", $(this).val());
	});
	//---padding-style---
	$("#paddingall").on("change", function(){
		var padval = $(this).val();
		$(currElem).css("padding", padval +"px " + padval +"px " + padval +"px " + padval +"px");	
		$("#paddingleft").val($(this).val());
		$("#paddingright").val($(this).val());
		$("#paddingtop").val($(this).val());
		$("#paddingbottom").val($(this).val());
		setSelectors();
	});
	$("#paddingleft").on("change", function(){
		$(currElem).css("padding-left", $(this).val()+"px");
		setSelectors();
	});
	$("#paddingright").on("change", function(){
		$(currElem).css("padding-right", $(this).val()+"px");
		setSelectors();
	});	
	$("#paddingtop").on("change", function(){
		$(currElem).css("padding-top", $(this).val()+"px");
		setSelectors();
	});
	$("#paddingbottom").on("change", function(){
		$(currElem).css("padding-bottom", $(this).val()+"px");
		setSelectors();
	});
	//---margin-style---
	$("#marginall").on("change", function(){
		var margval = $(this).val();
		$(currElem).css("margin", margval +"px " + margval +"px " + margval +"px " + margval +"px");	
		$("#marginleft").val($(this).val());
		$("#marginright").val($(this).val());
		$("#margintop").val($(this).val());
		$("#marginbottom").val($(this).val());
		setSelectors();
	});
	$("#marginleft").on("change", function(){
		$(currElem).css("margin-left", $(this).val()+"px");
		setSelectors();
	});
	$("#marginright").on("change", function(){
		$(currElem).css("margin-right", $(this).val()+"px");
		setSelectors();
	});	
	$("#margintop").on("change", function(){
		$(currElem).css("margin-top", $(this).val()+"px");
		setSelectors();
	});	
	$("#marginbottom").on("change", function(){
		$(currElem).css("margin-bottom", $(this).val()+"px");
		setSelectors();
	});
	
		
	//---Background color---
	$("#backgroundcolor").on("change", function(){
		$(currElem).css("background", $(this).val());
		setSelectors();
	});
	$("[name=embviewfilter]").on("change", function()
	{
		var filter = Docova.Utils.getField("embviewfilter");
		if ( filter && filter == "rc"){
			$("#restrict_cat_formula").show();
			$('#extra_restriction').hide();
		} else if (filter && filter == 'f') {
			if (!$('#embrestriction_container').children('li').length) {
				$('#embrestriction_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="restrictcolumn inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="restrictvalue inputEntry"/></li>');
			}
			$('#restrict_cat_formula').hide();
			$('#extra_restriction').show();
		}else{
			$("#restrict_cat_formula").hide();
			$('#extra_restriction').hide();
		}
	});
	
	//---Hide/When---
	$("[name=HideWhen]").on("change", function(){
		var hw = Docova.Utils.getField("HideWhen");
		if(hw == null || hw == "undefined"){ //if nothing selected the clear all
			$("#spanCustomHideWhen").css("display", "none")
			$("#CustomHideWhen").val("");
			$(currElem).attr("hidewhen", "")
			$(currElem).attr("customhidewhen", "");
		}else{
			if(Docova.Utils.getField("HideWhen").indexOf("C") != -1){
				$("#spanCustomHideWhen").css("display", "");
				if (!$("#CustomHideWhen").val()) {
					$('span[target=CustomHideWhen]').html('');
				}
			}else{
				$("#spanCustomHideWhen").css("display", "none")
				$("#CustomHideWhen").val("");
				$('span[target=CustomHideWhen]').html('');
			}
			$(currElem).attr("hidewhen", Docova.Utils.getField("HideWhen"))
			$(currElem).attr("customhidewhen", $("#CustomHideWhen").val());
		}
	});
	
	//---input text attribute fields
	$("#text_role").on("change", function(){
		$(currElem).attr("textrole", $("#text_role").val())
		if ($('[name=text_mv]').is(':checked')) {
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
		}
		else {
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
		}
		if($("#text_role").val() == "n"){
			$("#attr_number_format").css("display", "");
			$("#text_num_decimals").val($(currElem).attr("textnumdecimals"));
			$("#text_numformat").val($(currElem).attr("numformat"))
			$(currElem).prop("placeholder", $("#element_id").val());
			$('#textSelectionType option[value=dbcalculate]').prop('disabled', false);
		}else{
			$("#attr_number_format").css("display", "none");
			$("#text_num_decimals").val("0");
			$("#text_numformat").val("auto");
			$(currElem).attr("textnumdecimals", "0");
			$(currElem).attr("numformat", "auto");
			$(currElem).prop("placeholder", $("#element_id").val());
			$('#textSelectionType option[value=dbcalculate]').prop('disabled', true);
		}

		var et = $('#textEntryType').val();
		if (!et || et == 'D') {
			$('TR.textEntryTypeV, TR.textdblookup, TR.textclfunction').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
			$('TR.textEntryTypeD').css('display', '');
		}
		else {
			$('TR.textEntryTypeD').css('display', 'none');
			$('TR.textEntryTypeV').css('display', '');
			if ($.trim($('#textLUView').find('option').first().text().toLowerCase()) == 'get views') {
				getViewList('textLUView');
			}
			var st = $('#textSelectionType').val();
			if (st == 'dblookup') {
				$('[name=text_mv]').prop('checked', true);
				var heightvar = $(currElem)[0].style.height || '';		
				$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
				$('TR.textMultiHeight').show();
				if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
					$("#seplabel").css("display", "");
					$("#seplist").css("display", "");
					$("input[name=text_disep][value=space]").closest("label").show();
				}
				else {
					$("#seplabel").css("display", "none");
					$("#seplist").css("display", "none");				
					$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
					$("input[name=text_disep][value=space]").closest("label").hide();
				}
				$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
				$("#diseplist").css("display", "");
				
				$('TR.textdblookup').css('display', '');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
			else if (st == 'dbcalculate') {
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', '');
			}
			else {
				$('[name=text_mv]').prop('checked', true);
				var heightvar = $(currElem)[0].style.height || '';		
				$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
				$('TR.textMultiHeight').show();
				if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
					$("#seplabel").css("display", "");
					$("#seplist").css("display", "");
					$("input[name=text_disep][value=space]").closest("label").show();
				}
				else {
					$("#seplabel").css("display", "none");
					$("#seplist").css("display", "none");				
					$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
					$("input[name=text_disep][value=space]").closest("label").hide();
				}
				$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
				$("#diseplist").css("display", "");
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
		}

		var tr = $("#text_role").val();
		if (tr == 'names' || tr == 'a' || tr == 'r') {
			$('#attr_names_format').css('display', '');
		}
		else {
			$('#attr_names_format').css('display', 'none');
		}
		if((tr == "names" || tr == "a" || tr == "r") && $("#text_type").val() == "e"){
			$("#namepickerlabel").css("display", "");
			$("#namepicker").css("display", "");
			$("#text_namepicker").val(($(currElem).attr("textnp")||""));
			$("#text_namepicker").prop("checked",($(currElem).attr("textnp") == "1"));
			$('#seplist').hide();
			$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
			$("input[name=text_disep][value=space]").closest("label").hide();			
		}else{
			$("#namepickerlabel").css("display", "none");
			$("#namepicker").css("display", "none");			
			$("#text_namepicker").val("");	
			$("#text_namepicker").prop("checked",false);
			$('[name=text_sep]').prop({'checked': false});
			$('[name=text_disep]').prop({'checked': false, 'disabled' : true});
			$("input[name=text_disep][value=space]").closest("label").show();
			if ($('[name=text_mv]').is(':checked')) {
				$('#seplist').show();
				$('TR.textMultiHeight').show();
			}
			else {
				$('#seplist').hide();
				$('#text_height').val('');
				$('TR.textMultiHeight').hide();
			}
		}
	});

	$("#text_type").on("change", function(){
		$(currElem).attr("texttype", $(this).val());
		if($(this).val() == "e"){
			$("#deflabel").css("display", "")
			$("#formulalabel").css("display", "none");
		}else{
			$("#deflabel").css("display", "none")
			$("#formulalabel").css("display", "");
		}
		if ($('[name=text_mv]').is(':checked')) {
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
		}
		else {
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
		}
		$(currElem).prop("readonly", true);
		var et = $('#textEntryType').val();
		if (!et || et == 'D') {
			$('TR.textEntryTypeV, TR.textdblookup, TR.textclfunction').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
			$('TR.textEntryTypeD').css('display', '');
		}
		else {
			$('TR.textEntryTypeD').css('display', 'none');
			$('TR.textEntryTypeV').css('display', '');
			if ($.trim($('#textLUView').find('option').first().text().toLowerCase()) == 'get views') {
				getViewList('textLUView');
			}
			var st = $('#textSelectionType').val();
			if (st == 'dblookup') {
				$('[name=text_mv]').prop('checked', true);
				var heightvar = $(currElem)[0].style.height || '';		
				$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
				$('TR.textMultiHeight').show();
				if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
					$("#seplabel").css("display", "");
					$("#seplist").css("display", "");
					$("input[name=text_disep][value=space]").closest("label").show();
				}
				else {
					$("#seplabel").css("display", "none");
					$("#seplist").css("display", "none");				
					$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
					$("input[name=text_disep][value=space]").closest("label").hide();
				}
				$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
				$("#diseplist").css("display", "");
				$('TR.textdblookup').css('display', '');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
			else if (st == 'dbcalculate') {
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', '');
			}
			else {
				$('[name=text_mv]').prop('checked', true);
				var heightvar = $(currElem)[0].style.height || '';		
				$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
				$('TR.textMultiHeight').show();
				if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
					$("#seplabel").css("display", "");
					$("#seplist").css("display", "");
					$("input[name=text_disep][value=space]").closest("label").show();
				}
				else {
					$("#seplabel").css("display", "none");
					$("#seplist").css("display", "none");				
					$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
					$("input[name=text_disep][value=space]").closest("label").hide();
				}
				$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
				$("#diseplist").css("display", "");
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
		}
		var tr = $("#text_role").val();
		if (tr == 'names' || tr == 'a' || tr == 'r') {
			$('#attr_names_format').css('display', '');
		}
		if( (tr == "names" || tr == "a" || tr == "r") && $("#text_type").val() == "e"){
			$("#namepickerlabel").css("display", "");
			$("#namepicker").css("display", "");
			$("#text_namepicker").val(($(currElem).attr("textnp")||""));
			$("#text_namepicker").prop("checked",($(currElem).attr("textnp") == "1"));
		}else{
			$("#namepickerlabel").css("display", "none");
			$("#namepicker").css("display", "none");			
			$("#text_namepicker").val("");	
			$("#text_namepicker").prop("checked",false);
		}		
	});
	
	$('#textEntryType').on('change', function(){
		var tm = $('[name=text_mv]').is(':checked');
		if (tm) {
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
		}
		else {
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
		}
		if (!$(this).val() || $(this).val() == 'D') {
			$('TR.textEntryTypeV, TR.textdblookup, TR.textclfunction').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
			$('TR.textEntryTypeD').css('display', '');
		}
		else {
			$('TR.textEntryTypeD').css('display', 'none');
			$('TR.textEntryTypeV').css('display', '');
			if ($.trim($('#textLUView').find('option').first().text().toLowerCase()) == 'get views') {
				getViewList('textLUView');
			}
			var st = $('#textSelectionType').val();
			if (st == 'dblookup') {
				if (!tm) {
					$('[name=text_mv]').prop('checked', true);
					var heightvar = $(currElem)[0].style.height || '';		
					$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
					$('TR.textMultiHeight').show();
					if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
						$("#seplabel").css("display", "");
						$("#seplist").css("display", "");
						$("input[name=text_disep][value=space]").closest("label").show();
					}
					else {
						$("#seplabel").css("display", "none");
						$("#seplist").css("display", "none");				
						$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
						$("input[name=text_disep][value=space]").closest("label").hide();
					}
					$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
					$("#diseplist").css("display", "");
				}
				$('TR.textdblookup').css('display', '');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
			else if (st == 'dbcalculate') {
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', '');
			}
			else {
				if (!tm) {
					$('[name=text_mv]').prop('checked', true);
					var heightvar = $(currElem)[0].style.height || '';		
					$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
					$('TR.textMultiHeight').show();
					if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
						$("#seplabel").css("display", "");
						$("#seplist").css("display", "");
						$("input[name=text_disep][value=space]").closest("label").show();
					}
					else {
						$("#seplabel").css("display", "none");
						$("#seplist").css("display", "none");				
						$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
						$("input[name=text_disep][value=space]").closest("label").hide();
					}
					$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
					$("#diseplist").css("display", "");
				}
				$('TR.textdblookup').css('display', 'none');
				$('TR.textclfunction').css('display', 'none');
				$('TR[class^="textCalculationType"]').css('display', 'none');
			}
		}
	});
	
	$('#textSelectionType').on('change', function(){
		var tm = $('[name=text_mv]').is(':checked');
		if (tm) {
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
		}
		else {
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
		}
		if ($(this).val() == 'dblookup') {
			$('[name=text_mv]').prop('checked', true);
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
			if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
				$("#seplabel").css("display", "");
				$("#seplist").css("display", "");
				$("input[name=text_disep][value=space]").closest("label").show();
			}
			else {
				$("#seplabel").css("display", "none");
				$("#seplist").css("display", "none");				
				$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
				$("input[name=text_disep][value=space]").closest("label").hide();
			}
			$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
			$("#diseplist").css("display", "");
			$('TR.textdblookup').css('display', '');
			$('TR.textclfunction').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
		}
		else if ($(this).val() == 'dbcalculate') {
			//initiate tied up embedded views list
			getElementTypeList('textLinkedEmbView', 'embeddedview:datatable');
			$('TR.textdblookup').css('display', 'none');
			$('TR.textclfunction').css('display', '');
			if ($('#textCalculationType').val() == 'V') {
				$('TR.textCalculationTypeV, TR.textCalculationType').css('display', '');
				$('TR.textCalculationTypeE').css('display', 'none');
			}
			else if ($('#textCalculationType').val() == 'E') {
				$('TR.textCalculationTypeV').css('display', 'none');
				$('TR.textCalculationTypeE, TR.textCalculationType').css('display', '');
			}
			else {
				$('TR.textCalculationTypeV, TR.textCalculationTypeE, TR.textCalculationType').css('display', 'none');
			}
		}
		else {
			$('[name=text_mv]').prop('checked', true);
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
			if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
				$("#seplabel").css("display", "");
				$("#seplist").css("display", "");
				$("input[name=text_disep][value=space]").closest("label").show();
			}
			else {
				$("#seplabel").css("display", "none");
				$("#seplist").css("display", "none");				
				$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
				$("input[name=text_disep][value=space]").closest("label").hide();
			}
			$("input[name=text_sep][value=semicolon]").prop('check', true).trigger('click');
			$("#diseplist").css("display", "");
			$('TR.textdblookup').css('display', 'none');
			$('TR.textclfunction').css('display', 'none');
			$('TR[class^="textCalculationType"]').css('display', 'none');
		}
	});
	
	$('#textFunction, #textLinkedEmbView, #textLUColumn').on('change', function(){
		if ($('#textCalculationType').val() == 'E' && $('#textLinkedEmbView').val()) {
			var script = "$$EmbCalculate('"+ $('#textLinkedEmbView').val() +"', '"+ $('#textFunction').val() +"', "+ $('#textLUColumn').val() + ')';
			$('#textEmbViewRefreshCode').val(script);
			$('span[target=textEmbViewRefreshCode]').html(safe_tags(script));
		}
	});
	
	$('#textCalculationType').on('change', function(){
		if ($(this).val())
		{
			$('TR[class^="textCalculationType"]').css('display', 'none');
			$('TR.textCalculationType' + $(this).val()).css('display', '');
			$('TR.textCalculationType').css('display', '');
		}
		else {
			$('TR.textCalculationTypeV, TR.textCalculationTypeE, TR.textCalculationType').css('display', 'none');
		}
		if ($(this).val() != 'E') {
			$('#textEmbViewRefreshCode').val('');
			$('span[target=textEmbViewRefreshCode]').html('');
		}
	});
	
	$("[name=text_mv]").on("change", function(){
		if($(this).prop("checked")){
			var heightvar = $(currElem)[0].style.height || '';		
			$('#text_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.textMultiHeight').show();
			if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
				$("#seplabel").css("display", "");
				$("#seplist").css("display", "");
				$("input[name=text_disep][value=space]").closest("label").show();
			}
			else {
				$("#seplabel").css("display", "none");
				$("#seplist").css("display", "none");				
				$('[name=text_disep]').prop({'checked': false, 'disabled' : false});
				$("input[name=text_disep][value=space]").closest("label").hide();
			}
			$("#diseplist").css("display", "");
		}else{
			if ($('#textEntryType').val() != 'D' && $('#textSelectionType').val() != 'dbcalculate') {
				alert('You cannot have dbColumn or dbLookup without multi values ON.');
				$(this).prop('checked', true);
				return false;
			}
			$('#text_height').val('');
			$('TR.textMultiHeight').hide();
			$("#seplabel").css("display", "none");
			$("#seplist").css("display", "none");
			$("#diseplist").css("display", "none");
			$("[name=text_sep]:checked").prop("checked", false);
			if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
				$('[name=text_disep]').prop({'checked': false, 'disabled' : true});
			}
			else {
				$('[name=text_disep]').prop('checked', false);				
			}
		}	
		setProperties();
	});

	$('#ctCalculationType').on('change', function(){
		if ($(this).val())
		{
			$('TR[class^="ctCalculationType"]').css('display', 'none');
			$('TR.ctCalculationType' + $(this).val()).css('display', '');
			$('TR.ctCalculationType').css('display', '');
		}
		else {
			$('TR.ctCalculationTypeV, TR.ctCalculationTypeE, TR.ctCalculationType').css('display', 'none');
		}
		if ($(this).val() != 'E') {
			$('#ctEmbViewRefreshCode').val('');
			$('span[target=ctEmbViewRefreshCode]').html('');
		}
	});

	$('#ctCalculationType').on('change', function(){
		if ($(this).val() == 'E') {
			//initiate tied up embedded views list
			getElementTypeList('ctLinkedEmbView', 'embeddedview:datatable');
		}
	});
	
	$('#ctFunction, #ctLinkedEmbView, #selectCtViewColumn').on('change', function(){
		if ($('#ctCalculationType').val() == 'E' && $('#ctLinkedEmbView').val()) {
			var script = "$$EmbCalculate('"+ $('#ctLinkedEmbView').val() +"', '"+ $('#ctFunction').val() +"', "+ $('#selectCtViewColumn').val() + ')';
			$('#ctEmbViewRefreshCode').val(script);
			$('span[target=ctEmbViewRefreshCode]').html(safe_tags(script));
		}
	});
	
	if ($(currElem).attr('textrole') != 'names' && $(currElem).attr('textrole') != 'a' && $(currElem).attr('textrole') != 'r') {
		$("[name=text_sep]").on("change", function(){
			if ($(this).prop('checked')){
				$('[name=text_disep][value='+$(this).val()+']').prop('disabled', false);
			}
			else {
				$('[name=text_disep][value='+$(this).val()+']').prop('disabled', true);
			}
			setProperties();
		});
	}
	$("#text_align").on("change", function(){
		$(currElem).css("text-align", $("#text_align").val());	
		$(currElem).attr("textalign", $("#text_align").val());	
	});
	$("#text_num_decimals").on("change", function(){
		$(currElem).attr("textnumdecimals", $("#text_num_decimals").val());
	});
	$("#text_numformat").on("change", function(){
		$(currElem).attr("numformat", $("#text_numformat").val());
	});
	
	
	//Text Area section
	$("#attr_tarea").find("[name=EditorType]").on("change", function(){
		if($("#attr_tarea").find("[name=EditorType]:checked").val() == "P"){
			$("#attr_tarea").find("[name=limitlength]").show();
		}else{
			$("#attr_tarea").find("[name=limitlength]").hide();
		}	

		if($("#attr_tarea").find("[name=EditorType]:checked").val() == "R"){
			$("#attr_tarea").find("[id=editorsettings]").show();
		}else{
			$("#attr_tarea").find("[id=editorsettings]").hide();
		}				
	});
	
	//Slider orientation section
	$('#sld_orientation').on('change', function(){
		var slen = $('#sld_length').val();
		if ($(this).val() == 'Vertical') {
			if ($.trim(slen) !== '') {
				$(currElem).css({
					width: '24px',
					height: slen.indexOf('%') ? slen : (slen+'px'),
				});
			}
			else {
				$(currElem).css({
					width: '24px',
					height: 'auto',
					'min-height': '120px'
				});
			}
			$(currElem).children('span').css('margin-top', '20px');
		}
		else {
			if ($.trim(slen) !== '') {
				$(currElem).css({
					height: '19px',
					width: slen.indexOf('%') ? slen : (slen+'px'),
					'min-height': ''
				});
			}
			else {
				$(currElem).css({
					height: '19px',
					width: '100%',
					'min-height': ''
				});
			}
			$(currElem).children('span').css('margin-top', '');
		}
	});
	
	$('#sld_length').on('change', function(){
		$('#sld_orientation').trigger('change');
	});
	
	//subform section
	$("#subformList").on("change", function(){
		$(currElem).attr("subform", $("#subformList").val());
		//$(currElem).text("Custom Subform: " + $("#subformList").val())
		$(currElem).find(".caption").text("Custom Subform: " + $("#subformList").val())
	});
	$("#subformInsertMethod").on("change", function(){
		$(currElem).attr("insertmethod", $("#subformInsertMethod").val());
		if($("#subformInsertMethod").val() == "Selected"){
			$("#subform_formula").val(""); //clear subform_formula field
			$("a[target='subform_formula']").val(""); //clear subform_formula field
			$(currElem).attr("subformformula", ""); //clear subformformula attribute on element
			$(".subformselected").css("display", ""); //show Subform list field
			$(".subformcomputed").css("display", "none"); //hide computed formula
			//$(currElem).text("Custom Subform: " + "- Select -")
			$(currElem).find(".caption").text("Custom Subform: " + "- Select -")
		}else{
			$("#subformList").val("- Select -"); //set subform list to -Select-
			$(".subformselected").css("display", "none"); //hide Subform list field
			$(".subformcomputed").css("display", ""); //show computed formula
			//$(currElem).text("Custom Subform: " + "COMPUTED")
			$(currElem).find(".caption").text("Custom Subform: " + "COMPUTED")
		}
	});
	
	$("#cbColumns").on("change", function(){
		$(currElem).attr("cbColumns", $("#cbColumns").val());
		setProperties();
	});
	
	$("#cbLUView, #rbLUView, #selectLUView, #selectEmbView, #selectCtrbView, #selectCtrbViewColumn").on("change", function(){
		setProperties();
	});
	$("#cbLUColumn, #rbLUColumn, #selectLUColumn").on("change", function(){
		setProperties();
	});
	
	$("#selectQueryOpenAgent").on("change", function() {
		var qoval = $(this).val();
		if ( qoval == null || qoval == "-Select-") {
			qoval = "";
		}
		$("#pageProperties").attr("wqoAgent", qoval)	
	});
	
	$("#selectQuerySaveAgent").on("change", function() {
		var qsval = $(this).val();
		if ( qsval == null || qsval == "-Select-") {
			qsval = "";	
		}
		$("#pageProperties").attr("wqsAgent", qsval);	
	});
		
	$("#cbLUKeyField, #rbLUKeyField, #selectLUKeyField").on("change", function(){
		
		setProperties();
	});
	
	$("[name=cacop]").on("change", function(){	
			setProperties();
	});

	$("[name=EditorType]").on("change", function(){
		$(currElem).attr("editortype", $(this).val());
	});
	
	$("#attachmentsreadonly").on("change", function(){
		setProperties();
	});
	
	$("#EmbViewSearch").on("change", function(){
		setProperties();
	});
	
	
	$("[name=HideAttachButtons]").on("change", function(){
			setProperties();
	});
	
	$("[name=GenerateThumbnails]").on("change", function(){
			setProperties();
	});
	
	
	$("#enablefileviewlogging").on("change", function(){
			setProperties();
	});
	
	$("#enablefiledownloadlogging").on("change", function(){
			setProperties();
	});
	
	$("#enablelocalscan").on("change", function(){
			setProperties();
	});
	
	$("#sectionexpandcollapse").on("change", function(){
			if ($(this).is(":checked")){
				$("#attr_collapsesection").show();
			}else{
				$("#attr_collapsesection").hide();
			}
			setProperties();
	});
	
	$("#EnableControlledSectionAccess").on("change", function(){
			setProperties();
	});
	
	$("#enablefileciao").on("change", function(){
			setProperties();
	});
	
	$("[name=ListType]").on("change", function(){
			setProperties();
/*
			var attach = $("div[elem='attachments']");
		
			if  ( $(this).val() == "I"){
				var htmlstr = "<div class='item'  style='background-color: rgb(232, 232, 232);' ><i class='fa fa-file-pdf-o fa-3x' style='color:red'></i>&nbsp;<span class='caption'>sample.pdf</span></div></div>"
				attach.html( htmlstr );
			}else{
				var htmlstr= '<table id="tblUploaderattachDisplay" class="upTable"><thead class="ui-state-hover"><tr><td style="width:40%; padding:8px">Name</td><td style="width:20%;">Date</td><td style="width:20%;">Size</td><td>Type</td></tr></thead><tbody id="tbodyUploaderattachDisplay" >'
				htmlstr +='<tr style="background-color: rgb(255, 255, 255);"><td width="40%"><i class="fa fa-file-pdf-o fa-3x" style="color:red"></i></span>&nbsp;Sample.pdf</td><td width="20%">12/11/2015</td><td width="20%">7.25 MB</td><td>pdf</td></tr>'
				htmlstr += '</tbody></table>'
				attach.html(htmlstr );
			}
*/			
	});
	
	$("#templatetype").on("change", function(){
		setProperties();
	});
	
	$("#templateautoattach").on("change", function(){
			setProperties();
	});
	
	$("#relateddocopenmode").on("change", function(){
			setProperties();
	});
	
	$("#enablexlink").on("change", function(){
			setProperties();
	});
	
	$("#enablelinkedfiles").on("change", function(){
			setProperties();
	});
	
	$("#launchlinkedfiles").on("change", function(){
			setProperties();
	});
	
	$("[name=IncludeEmailLinks]").on("change", function(){
			setProperties();
	});
		
	$("#cbSelectionType").on('change', function(){
		if ($(this).val() == 'dblookup') {
			$('#cbdblookup').css('display', '');
			if ($('#cbLUKeyType').val() == 'Manual') {
				$("#cb_LUKey").css("display", "block");
				$("#cb_LUKeyField").css("display", "none");
				$("#cbLUKeyField").val("- Select -");
			}
			else {
				$("#cb_LUKey").css("display", "none");
				$("#cbLUKey").val("");
				$("#cb_LUKeyField").css("display", "block");
				getFieldList("cbLUKeyField");
			}
		}
		else {
			$("#cbLUKey").val("");
			$("#cbLUKeyField").val("- Select -");
			$('#cbdblookup').css('display', 'none');
			$("#cb_LUKey").css("display", "none");
			$("#cb_LUKeyField").css("display", "none");
		}
	});
	
	$("#cbLUKeyType").on("change", function(){
		if($(this).val() == "Manual"){
			$("#cb_LUKey").css("display", "block");
			$("#cb_LUKeyField").css("display", "none");
			$("#cbLUKeyField").val("- Select -");
		}
		if($(this).val() == "Field"){
			$("#cb_LUKey").css("display", "none");
			$("#cbLUKey").val("");
			$("#cb_LUKeyField").css("display", "block");
			getFieldList("cbLUKeyField");
		}		
		setProperties();
	});
	
	$('input[name=cbluColumnField]').on('click', function(){
		$('TR[class^=cbluColumnField]').hide();
		$('TR.cbluColumnField' + $(this).val()).show();
	});
	
	//---checkbox attribute fields---
	$("#cbOptionMethod").on("change", function(){
		if($(this).val() == "manual" || $(this).val() == "formula"){
			eName =$(currElem).find("[type=checkbox]").first().prop("name");
			$(currElem).empty();
			strHTML = "<tr><td><input type='checkbox' value='Option 1' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 1</span><br>"
			strHTML += "<input type='radio' checkbox='Option 2' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 2</span><br>"
			strHTML += "<input type='radio' checkbox='Option 3' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 3</span><br></td></tr>"
			$(currElem).append($(strHTML ))
			$("#cbManualOptions").css("display", "")
			$("#cbSelectOptions").css("display", "none")
		}else{
			eName =$(currElem).find("[type=checkbox]").first().prop("name");
			$(currElem).empty();
			
			$(currElem).append($("<tr><td><input type='checkbox' name='" + eName + "' value='[computed]'><span style='padding:0 0 0 5;'>[computed]</span></td></tr>" ))
			
			$("#cbManualOptions").css("display", "none")
			$("#cbSelectOptions").css("display", "")	
			getViewList("cbLUView");
		}
		setProperties();
	});
	
	$('[name=chbox_disep]').on('change', function(){
		setProperties();
	});

	//---radio button attribute fields---
	$("#rbOptionMethod").on("change", function(){
			
		if($(this).val() == "manual" || $(this).val() == "formula"){
			
			eName =$(currElem).find("[type=radio]").first().prop("name");
			$(currElem).empty();
		
			strHTML = "<tr><td><input type='radio' value='Option 1' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 1</span><br>"
			strHTML += "<input type='radio' value='Option 2' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 2</span><br>"
			strHTML += "<input type='radio' value='Option 3' name='" + eName + "'><span style='padding:0 0 0 5;'>Option 3</span><br></td></tr>"
			$(currElem).append($(strHTML ))
			$("#rbManualOptions").css("display", "")
			$("#rbSelectOptions").css("display", "none")
		}else{
			eName =$(currElem).find("[type=radio]").first().prop("name");
			$(currElem).empty();
			
			$(currElem).append($("<tr><td><input type='radio' name='" + eName + "' value='[computed]'><span style='padding:0 0 0 5;'>[computed]</span></td></tr>" ))
			$("#rbManualOptions").css("display", "none")
			$("#rbSelectOptions").css("display", "")	
			getViewList("rbLUView");
		}
		setProperties();
	});
	$("#rbColumns").on("change", function(){
		$(currElem).attr("rbColumns", $("#rbColumns").val());
		setProperties();
	});
	$("#rbSelectionType").on('change', function(){
		if ($(this).val() == 'dblookup') {
			$('#rbdblookup').css('display', '');
			if ($('#rbLUKeyType').val() == 'Manual') {
				$("#rb_LUKey").css("display", "block");
				$("#rb_LUKeyField").css("display", "none");
				$("#rbLUKeyField").val("- Select -");
			}
			else {
				$("#rb_LUKey").css("display", "none");
				$("#rbLUKey").val("");
				$("#rb_LUKeyField").css("display", "block");
				getFieldList("rbLUKeyField");
			}
		}
		else {
			$("#rbLUKey").val("");
			$("#rbLUKeyField").val("- Select -");
			$('#rbdblookup').css('display', 'none');
			$("#rb_LUKey").css("display", "none");
			$("#rb_LUKeyField").css("display", "none");
		}
	});
	$("#rbLUKeyType").on("change", function(){
		if($(this).val() == "Manual"){
			$("#rb_LUKey").css("display", "block");
			$("#rbLUKey").css('display', '');
			$("#rb_LUKeyField").css("display", "none");
			$("#rbLUKeyField").val("- Select -");
		}
		if($(this).val() == "Field"){
			$("#rb_LUKey").css("display", "block");
			$("#rbLUKey").css('display', 'none');
			$("#rbLUKey").val("");
			$("#rb_LUKeyField").css("display", "block");
			getFieldList("rbLUKeyField");
		}		
		setProperties();
	});
	
	$('input[name=rbluColumnField]').on('click', function(){
		$('TR[class^=rbluColumnField]').hide();
		$('TR.rbluColumnField' + $(this).val()).show();
	});

	$('#selectPicklistForm').on('change', function(){
		resetFormElementsList($(this));
	});
	
	$('#picklistAction').on('change', function(){
		resetPicklistForm($(this));
	});
	//---select attribute fields---
	$("#selectOptionMethod").on("change", function(){
		if($(this).val() == "manual" || $(this).val() == "formula"){
			$("#selectManualOptions").css("display", "")
			$("#selectSelectOptions").css("display", "none")
		}else{
			$("#selectManualOptions").css("display", "none")
			$("#selectSelectOptions").css("display", "")
			getViewList("selectLUView");
		}
		setProperties();
	});
	$("#cmbSelectionType").on('change', function(){
		if ($(this).val() == 'dblookup') {
			$('#selectdblookup').css('display', '');
			if ($('#selectLUKeyType').val() == 'Manual') {
				$("#select_LUKey").css("display", "block");
				$("#select_LUKeyField").css("display", "none");
				$("#selectLUKeyField").val("- Select -");
			}
			else {
				$("#select_LUKey").css("display", "none");
				$("#selectLUKey").val("");
				$("#select_LUKeyField").css("display", "block");
				getFieldList("selectLUKeyField");
			}
		}
		else {
			$("#selectLUKey").val("");
			$("#selectLUKeyField").val("- Select -");
			$('#selectdblookup').css('display', 'none');
			$("#select_LUKey").css("display", "none");
			$("#select_LUKeyField").css("display", "none");
		}
	});

	$("#selectLUKeyType").on("change", function(){
		if($(this).val() == "Manual"){
			$("#select_LUKey").css("display", "block");
			$("#select_LUKeyField").css("display", "none");
			$("#selectLUKeyField").val("- Select -");
		}
		if($(this).val() == "Field"){
			$("#select_LUKey").css("display", "none");
			$("#selectLUKey").val("");
			$("#select_LUKeyField").css("display", "block");
			getFieldList("selectLUKeyField");
		}	
		setProperties();	
	});
	
	$('input[name=slluColumnField]').on('click', function(){
		$('TR[class^=slluColumnField]').hide();
		$('TR.slluColumnField' + $(this).val()).show();
	});

	$('#select_mv').on('change', function(){
		if ($(this).prop('checked')) {
			$('#select_seplist').show();
			$('#select_diseplist').show();
		}
		else {
			$('#select_seplist').hide();
			$('#select_diseplist').hide();
			$("[name=select_sep]:checked").prop("checked", false);
			$("[name=combo_disep]").prop({'checked': false, 'disabled' : true});
		}
		setProperties();
	});
	$("[name=select_sep]").on("change", function(){
		if ($(this).prop('checked')){
			$('[name=combo_disep][value='+$(this).val()+']').prop('disabled', false);
		}
		else {
			$('[name=combo_disep][value='+$(this).val()+']').prop('disabled', true);
		}
		setProperties();
	});	
	$('[name=combo_disep]').on('change', function(){
		setProperties();
	});
	
	//---Names attribute fields---
	$("#namesSelectionType").on("change", function(){
		if($(this).val() == "single"){
			$(currElem).next("button").removeClass("ui-icon-group").addClass("ui-icon-single")
			$(currElem).attr("namesSelectionType", "single");
		}else{
			$(currElem).next("button").removeClass("ui-icon-single").addClass("ui-icon-group")
			$(currElem).attr("namesSelectionType", "multi");			
		}
	});
	$("#names_role").on("change", function(){
		$(currElem).attr("namesrole", $("#names_role").val())
	});
	
	//---Date attributes fields---
	$("[name=dateOptions]").on("change", function(){
		var dateOpts = Docova.Utils.getField("dateOptions")
		var wastimeenabled = ("true" == $(currElem).attr("displaytime"));
		$(currElem).attr("displaytime", false);
		if(dateOpts.indexOf("displaytime") > -1){
			$(currElem).attr("displaytime", true);
			if(!wastimeenabled){
				var curwidth = parseInt($(currElem).css("width"), 10);
				if(curwidth <= 80){
					var newwidth = (curwidth + 70).toString();
					$(currElem).css("width", newwidth + "px;");
					$("#dateWidth").val(newwidth);
				}
			}
		}
		$(currElem).attr("displayonlytime", false);
		if(dateOpts.indexOf("displayonlytime") > -1){
			$(currElem).attr("displayonlytime", true);
		}
	});

	
	$("#date_type").on("change", function(){
		if($(this).val() == "e"){
			$("#datedeflabel").css("display", "")
			$("#dateformulalabel").css("display", "none")
			$(currElem).prop("readonly", false);
		}else{
			$("#datedeflabel").css("display", "none")
			$("#dateformulalabel").css("display", "")		
			$(currElem).prop("readonly", true);
		}
		$(currElem).attr("datetype", $("#date_type").val());
	});
	
	$("[name=date_mv]").on("change", function(){
		if($(this).prop("checked")){
			var heightvar = $(currElem)[0].style.height || '';		
			$('#date_height').val(!heightvar || heightvar == 'auto' ? '' : (heightvar.indexOf('%') > 0 ? heightvar : parseInt(heightvar, 10)));
			$('TR.dateMultiHeight').show();
			$("#date_seplabel").css("display", "");
			$("#date_seplist").css("display", "");
			$("#date_diseplist").css("display", "");
		}else{
			$('#date_height').val('');
			$('TR.dateMultiHeight').hide();
			$("#date_seplabel").css("display", "none");
			$("#date_seplist").css("display", "none");
			$("#date_diseplist").css("display", "none");
			$("[name=date_sep]:checked").prop("checked", false);
			$("[name=date_disep]").prop({'checked': false, 'disabled' : true});
		}
		setProperties();
	});	
	$("[name=date_sep]").on("change", function(){
		if ($(this).prop('checked')){
			$('[name=date_disep][value='+$(this).val()+']').prop('disabled', false);
		}
		else {
			$('[name=date_disep][value='+$(this).val()+']').prop('disabled', true);
		}
		setProperties();
	});	

	//---dbCalculate attribute fields on valid elements
	$('.entryTypeSwitch').on('change', function(){
		var target = $(this).prop('id');
		$('tr[class^="'+ target +'"]').css('display', 'none');
		$('tr.'+ target + $(this).val()).css('display', '');
		if ($(this).val() != 'D')
		{
			if ($(this).prop('id') == 'cbEntryType') {
				getViewList('selectCtrbView');
			}
			else if ($(this).prop('id') == 'numEntryType') {
				getViewList('textLUView');
			}
			else if ($(this).prop('id') == 'ctEntryType') {
				getViewList('selectCtView');
			}
		}
		setProperties();
	});
/*
	$('#textLUView').on('change', function(){
		var appID = $('#numLUApplication').val();
		appID = appID ? appID : docInfo.AppID;
		getViewColumnList('selectNumViewColumn', appID, $(this).val());
	});
*/
	$('#selectCtrbView').on('change', function(){
		var appID = $('#ctrbLUApplication').val();
		appID = appID ? appID : docInfo.AppID;
		getViewColumnList('selectCtrbViewColumn', appID, $(this).val());
	});
/*	
	$('#selectCtView').on('change', function(){
		var appID = $('#ctLUApplication').val();
		appID = appID ? appID : docInfo.AppID;
		getViewColumnList('selectCtViewColumn', appID, $(this).val());
	});
*/
	//---Button attribute fields
	$("[name=btn_ShowLabel]").on("change", function(){
		//if changing the show label radio button, update all button attribs with setProperties();
		setProperties();
	});
	$("[name=btn_Type]").on("change", function(){
		setPreCannedButton();
		if ($(this).val() == 'CMP') {
			$('TR.btnGsc').hide();
			$('TR.btnCmp').show();
		}
		else if ($(this).val() == 'GSC') {
			getElementTypeList('embeddedGanttView', 'embeddedview');
			$('TR.btnCmp').hide();
			$('TR.btnGsc').show();
		}
		else {
			$('TR.btnCmp, TR.btnGsc').hide();
		}
	});
	
	$('#cmpFormsList').on('change', function(){
		if (!Docova.Utils.getField('cmpIsChild') && !Docova.Utils.getField('cmpInheritValues')) {
			$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $(this).val() +"')");
			$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $(this).val() +"')");
		}
		else if (!Docova.Utils.getField('cmpIsChild')) {
			$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $(this).val() +"', true, $$DocumentUniqueID())");
			$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $(this).val() +"', true, $$DocumentUniqueID())");
		}
		else if (Docova.Utils.getField('cmpIsChild')) {
			$('#onClickEvent').val("$$Command('[COMPOSERESPONSE]', '"+ $(this).val() +"', "+ Docova.Utils.getField('cmpInheritValues') ? 'true' : 'false' +", $$DocumentUniqueID())");
			$('span[target=onClickEvent]').html("$$Command('[COMPOSERESPONSE]', '"+ $(this).val() +"', "+ Docova.Utils.getField('cmpInheritValues') ? 'true' : 'false' +", $$DocumentUniqueID())");
		}
	});
	
	$('#cmpIsChild').on('click', function(){
		if ($(this).prop('checked')) {
			$('#onClickEvent').val("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', "+ Docova.Utils.getField('cmpInheritValues') ? 'true' : 'false' +", $$DocumentUniqueID())");
			$('span[target=onClickEvent]').html("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', "+ Docova.Utils.getField('cmpInheritValues') ? 'true' : 'false' +", $$DocumentUniqueID())");
		}
		else {
			if(Docova.Utils.getField('cmpInheritValues')) {
				$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
			}
			else {
				$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"')");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"')");
			}
		}
	});
	
	$('#cmpInheritValues').on('click', function(){
		if ($(this).prop('checked')) {
			if (Docova.Utils.getField('cmpIsChild')) {
				$('#onClickEvent').val("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
			}
			else {
				$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"', true, $$DocumentUniqueID())");
			}
		}
		else {
			if(Docova.Utils.getField('cmpIsChild')) {
				$('#onClickEvent').val("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', false, $$DocumentUniqueID())");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSERESPONSE]', '"+ $('#cmpFormsList').val() +"', false, $$DocumentUniqueID())");
			}
			else {
				$('#onClickEvent').val("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"')");
				$('span[target=onClickEvent]').html("$$Command('[COMPOSE]', '"+ $('#cmpFormsList').val() +"')");
			}
		}
	});
	
	$(".inputEntry").on("change", function(){
		setProperties();
	});
	
	//---Initialize Image selection field
	var imagelist = Docova.Utils.dbColumn({
		servername : "",
		nsfname : docInfo.NsfName,
		viewname : "luApplication",
		viewiscategorized : true,
		urlsuffix : ['Files'],
		key : docInfo.AppID,
		column : "1",
		secure: docInfo.SSLState
	});	
	$("#imageList").empty();
	var imagelistarray = imagelist.split(";");
	$("#imageList").append($("<option></option>").attr("value", "- Select -").text("- Select -"));
	for(x=0;x<imagelistarray.length;x++){
		$("#imageList").append($("<option></option>").attr("value", imagelistarray[x]).text(imagelistarray[x]));
	}
	$("#imageList").on("change", function(){
		$(currElem).attr("imagename", $("#imageList").val())
		var filePath = docInfo.ImgPath + $("#imageList").val()
		$(currElem).load(function(){
			setSelectors();
		})
		$(currElem).prop("src", filePath);
	});
	
	//---Initialize Outline selection field
	var outlinelist = Docova.Utils.dbColumn({
		servername : "",
		nsfname : docInfo.NsfName,
		viewname : "luApplication",
		urlsuffix : ['Outlines'],
		viewiscategorized : true,
		key : docInfo.AppID,
		column : "7",
		secure: docInfo.SSLState
	});	

	$("#outlineList").empty();
	var outlinelistarray = outlinelist.split(";");
	$("#outlineList").append($("<option></option>").attr("value", "- Select -").text("- Select -"));
	for(x=0;x<outlinelistarray.length;x++){
		var txtarry = outlinelistarray[x].split("|");
		var opttxt = "";
		var optval = ""; 
		if ( txtarry.length > 1 )
			optval = txtarry[1];
		opttxt = txtarry[0];
		if ( optval == "" ) 
			optval = opttxt;
			
		$("#outlineList").append($("<option></option>").attr("value", optval).text(opttxt));

		
	}
	$("#outlineList, #outlineExpand").on("change", function(){
		$(currElem).attr("outlinename", $("#outlineList").val())
		$(currElem).find(".caption").text("Outline: " + $("#outlineList").val())
		var expand = $("#outlineExpand").val();
		var outlinePath = "/" + docInfo.PortalNsfName + "/wViewOutline/" + $("#outlineList").val() + "?AppID=" + docInfo.AppID + "&expand=" + expand;
		var outlineDpath = "{{ path('docova_homepage') }}wViewOutline/" + $("#outlineList").val() + "?AppID=" + docInfo.AppID + "&expand=" + expand;
		$(currElem).prop("src", outlinePath);
		$(currElem).attr('dsrc', outlineDpath);
		$(currElem).attr("expand", expand);   
		setSelectors();
	});
	
	
	//--Chart properties
	$("#chartSourceType").on("change", function(){
		var st = $(this).val();
		if(st == "function"){
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "");
		}else if(st == "view"){
			$("#disp_chart_app").css("display", "");
			$("#disp_chart_source").css("display", "");
			$("#disp_chart_func").css("display", "none");
			getViewList("selectChartSource");
		}else if(st == "agent"){
			$("#disp_chart_app").css("display", "");
			$("#disp_chart_source").css("display", "");
			$("#disp_chart_func").css("display", "none");
			getAgentList("selectChartSource");
		}else{	
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "none");		
		}
	});	
	$("#selectChartSource").on("change", function(){
		if($("#chartSourceType").val() == "view"){
			setTimeout(function(){getViewColumns("chart_sourceitems");}, 500);
		}
	});
	$("#chartType").on("change", function(){
		var ct = $(this).val();
		if(ct == "pie" || ct == "doughnut"){
			$("#disp_chart_hidevalues").css("display", "");
		}else{
			$("#disp_chart_hidevalues").css("display", "none");	
			$("#chartHideValues").prop("checked", false);
		}
	});
	$('#chartSourceType').on('change', function(){
		var sourcetype = $(this).val();
		if(sourcetype == "" || sourcetype == "- Select -"){
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "none");	
			$("#disp_chart_fields").css("display", "none");	
			$("#disp_chart_rcat").css("display", "none");
		}else if(sourcetype == "function"){
			$("#disp_chart_app").css("display", "none");
			$("#disp_chart_source").css("display", "none");
			$("#disp_chart_func").css("display", "");
			$("#disp_chart_fields").css("display", "none");
			$("#disp_chart_rcat").css("display", "none");			
		}else{
			$("#disp_chart_app").css("display", "");
			$("#disp_chart_source").css("display", "");
			$("#disp_chart_func").css("display", "none");
			if(sourcetype == "view"){
				$("#chart_rcat_formula").val('');
				$("a[target='chart_rcat_formula']").prev().text('');
				$("#disp_chart_fields").css("display", "");	
				$("#disp_chart_rcat").css("display", "");
				getViewList("selectChartSource");
			}else if(sourcetype == "agent"){
				$("#disp_chart_fields").css("display", "none");	
				$("#disp_chart_rcat").css("display", "none");
				getAgentList("selectChartSource");				
			}
			$("#selectChartSource").val('');		

			if(sourcetype == "view"){
				$("span.chart_drag_item:not(.chart_source_item)").off("contextmenu").on("contextmenu", function(e){ menuHandler(e); });
				setTimeout(function(){getViewColumns("chart_sourceitems");}, 500);
			}
		}
	});
	
	
	//---TD vertical align---
	$("#textvalign").on("change", function(){
		$(currElem).css("vertical-align", $("#textvalign").val());
	});
	
	//---Workflow related field triggers---
	//EnableLifecycle checkbox
	$("[name=EnableLifecycle]").on("change", function(){
		setWorkflowProperties();
	});
	//EnableVersions checkbox
	$("[name=EnableVersions]").on("change", function(){
		setWorkflowProperties();
	});	
	//RestrictLiveDrafts checkbox
	$("[name=RestrictLiveDrafts]").on("change", function(){
		setWorkflowProperties();
	});
	//StrictVersioning checkbox
	$("[name=StrictVersioning]").on("change", function(){
		setWorkflowProperties();
	});
	//AllowRetract checkbox
	$("[name=AllowRetract]").on("change", function(){
		setWorkflowProperties();
	});
	//RestrictDrafts checkbox
	$("[name=RestrictDrafts]").on("change", function(){
		setWorkflowProperties();
	});
	//UpdateBookmarks checkbox
	$("[name=UpdateBookmarks]").on("change", function(){
		setWorkflowProperties();
	});
	//AttachmentOptions radio
	$("[name=AttachmentOptions]").on("change", function(){
		setWorkflowProperties();
	});
	//ShowHeaders checkbox
	$("[name=ShowHeaders]").on("change", function(){
		setWorkflowProperties();
	});
	//WorkflowList checkbox
	$("[name=WorkflowList]").on("change", function(){
		setWorkflowProperties();
	});
	//HideWorkflow checkbox
	$("[name=HideWorkflow]").on("change", function(){
		setWorkflowProperties();
	});
	//DisableDeleteInWorkflow checkbox
	$("[name=DisableDeleteInWorkflow]").on("change", function(){
		setWorkflowProperties();
	});
	//wfHideButtons checkbox
	$("[name=wfHideButtons]").on("change", function(){
		setWorkflowProperties();
	});
	
	//Set activate event on tabs
	$("#layout [elem=tabset]").each(function(){
		$(this).tabs({ activate: function(event, ui){ $("#TabLabelName").val($(ui.newTab).text()) } });
	});
}

function createDefaultOutline(options){
	var result = false;
	
	var defaultOptns = {
			appid : "",
			apppath : "",
			silent : false
		};
	var opts = $.extend({}, defaultOptns, options);	
	
	var uname = docInfo.UserNameAB;
	if(opts.appid == ""){
		opts.appid = (docInfo && docInfo.AppID ? docInfo.AppID : "");
	}
	if(opts.apppath == ""){
		opts.apppath = (docInfo && docInfo.AppPath && docInfo.AppFileName ? (docInfo.AppPath + "/" + docInfo.AppFileName) : "");		
	}
	if(opts.appid == "" || opts.apppath == ""){
		return result;
	}
	
	
	var viewlist = Docova.Utils.dbColumn({
		'servername' : "",
		'nsfname' : opts.apppath,
		'viewname' : "AppViews",
		'viewiscategorized' : true,
		'column' : 1,
		'secure' : (docInfo && docInfo.SSLState ? docInfo.SSLState : "")
	});
	if(viewlist == "404"){
		return result;
	}
	viewlist = jQuery.unique(viewlist.split(";"));
	
	var outlinehtml = "";
	outlinehtml += '<ul class="OutlineItems" style="list-style-type: none; margin-left: 20px; padding: 0px; border: 0px solid rgb(0, 0, 0); border-radius: 0px;">';
	for(var i=0; i<viewlist.length; i++){
		if(viewlist[i].length != "" && viewlist[i].slice(0,1) != "("){
			outlinehtml += '<li class="ui-state-default" style="margin:0px 3px 3px 3px; padding:0.4em; padding-left:1.5em; text-align:left;" etype="view" eelement="' + safe_tags(viewlist[i]) +'" etarget="" einitiallyexpanded="0" emenuitemtype="M" einitiallyselected="0">';
	 			outlinehtml += '<span class="" style="position: relative; padding-right: 0.5em; padding-left: 0.3em; padding-top: 0px;" icontitle=""></span>';
	 			outlinehtml += '<div class="itemlabel" style="font-weight: normal; display: inline-block; font-style: normal; font-size: 12px;">' + safe_tags(viewlist[i]) + '</div>';
			outlinehtml += '</li>';			
		}
	}
	outlinehtml += '</ul>';
	
	
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
	request += "<Code><![CDATA[]]></Code>";
	request += "<HTML><![CDATA[" + outlinehtml + "]]></HTML>";	
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


function createDefaultLayout(options){
	var result = false;
	
	var defaultOptns = {
			appid : "",
			apppath : "",
			isdefault : false,
			silent : false
		};
	var opts = $.extend({}, defaultOptns, options);	
	
	var uname = docInfo.UserNameAB;
	if(opts.appid == ""){
		opts.appid = (docInfo && docInfo.AppID ? docInfo.AppID : "");
	}
	if(opts.apppath == ""){
		opts.apppath = (docInfo && docInfo.AppPath && docInfo.AppFileName ? (docInfo.AppPath + "/" + docInfo.AppFileName) : "");		
	}
	if(opts.appid == "" || opts.apppath == ""){
		return result;
	}
	
	

	
	var layouthtml = "";
	layouthtml += '<frameset id="" cols="328px,*" frameborder="1" framespacing="2" dbordertype="3D Border" dborderwidth="">';
		layouthtml += '<frame src="luOutline/ViewListing?openDocument" issel="1" id="fraLeft" target="fraRight" dnoresize="" dnoscroll="" tabtitle="" openintab="" contenttype="Outline" content="ViewListing" dsrc="luOutline/ViewListing?openDocument">';
		layouthtml += '<frame src="about:blank" issel="" id="fraRight" target="fraRight" dnoresize="" dnoscroll="" tabtitle="" openintab="1" contenttype="" content="" dsrc="about:blank">';
	layouthtml += '</frameset>';
	
	var request = "<Request>";
	request += "<Action>SAVELAYOUTHTML</Action>";
	request += "<UserName><![CDATA[" + uname +"]]></UserName>";
	request += "<Document>";
	request += "<AppID><![CDATA[" + opts.appid + "]]></AppID>";
	request += "<TargetApplication><![CDATA[" + opts.apppath + "]]></TargetApplication>";
	request += "<LayoutName><![CDATA[DefaultLayout]]></LayoutName>";
	request += "<ProhibitDesignUpdate></ProhibitDesignUpdate>";	
	request += "<LayoutHTML><![CDATA[" + layouthtml + "]]></LayoutHTML>";	
	request += "<isDefault>" + (opts.isdefault ? "1" : "") + "</isDefault>";
	request += "</Document>";
	request += "</Request>";
	
	
	var formunid = SubmitRequest(encodeURIComponent(request), "DesignServices");			
	if ( ! formunid || formunid == "" || formunid == "FAILED" ) {
		if(! opts.silent){
			window.top.Docova.Utils.messageBox({
				width: 400,
				title: "Error Generating Default Layout",
				prompt: "An error occurred while generating the default layout.",
				icontype: 1,
				msgboxtype: 0
			});
		}
	}else{
		result = true;
	}
	
	return result;	
}

function ABOpenApp(){
	//Used by checkbox/radio button/dropdown/emb view/chart elements to pick app for lookups
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgAddABApp?OpenForm"
	var dlgAddApp = window.top.Docova.Utils.createDialog({
		id: "divDlgAddApp",
		url: dlgUrl,
		title: "Add an Application",
		height: 450,
		width: 500,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Add": function (){
				var dlgDoc = window.top.$("#divDlgAddAppIFrame")[0].contentWindow.document;
				
				var appPath = $("#AppUnid", dlgDoc).text();
				var appTitle = $("#TitleText", dlgDoc).text();
				
				if(appPath == ""){
					window.top.Docova.Utils.messageBox({
						title: "Nothing Selected",
						prompt: "You have not selected an Application from the list. <br><br> Please select and Application.",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}
				ABAddApp(appPath, appTitle);
				dlgAddApp.closeDialog();
			},
			"Cancel": function(){
				dlgAddApp.closeDialog();
			}
		}
	})
}


function ClearSelApp()
{
	//Add app for checkbox attribute field
	if($(currElem).attr("elem") == "chbox"){
		$("#cbLUApplication").val("");
		$("#dspCBAppTitle").val("<Current Application>");
	}
	//Add app for radio button attribute field
	if($(currElem).attr("elem") == "rdbutton"){
		$("#rbLUApplication").val("");
		$("#dspRBAppTitle").val("<Current Application>");
	}
	//Add app for select attribute field
	if($(currElem).attr("elem") == "select"){
		$("#selectLUApplication").val("");
		$("#dspSelectAppTitle").val("<Current Application>");
	}	
	//Add app for emb view attribute field
	if ( $(currElem).attr("elem") == "embeddedview" ){
		$("#embLUApplication").val("");
		$("#dspEmbViewAppTitle").val("<Current Application>");
	}	
	//Add app for chart attribute field
	if ( $(currElem).attr("elem") == "chart" ){
		$("#chartLUApplication").val("");
		$("#dspChartAppTitle").val("<Current Application>");
	}		
	//Add app for counterbo attribute field
	if ( $(currElem).attr("elem") == 'counterbox' ){
		$('#ctrbLUApplication').val('');
		$('#dspCounterBoxAppTitle').val("<Current Application>");
	}		
	getViewList();
	setProperties();
	return;
}

function ABAddApp(appFullPath, appTitle){
//Used by checkbox/radio button/dropdown/emb view/chart elements to pick app for lookups
//The appFullPath includes the instance part of the path
//We want to strip off the instance part of the path to return only
//the app db name and any directory path info it might have like "myapp.nsf" or "hr/myapp.nsf"
	var AppPath = appFullPath;

	//Add app for checkbox attribute field
	if($(currElem).attr("elem") == "chbox"){
		$("#cbLUApplication").val(AppPath);
		$("#dspCBAppTitle").val(appTitle);
	}
	//Add app for radio button attribute field
	if($(currElem).attr("elem") == "rdbutton"){
		$("#rbLUApplication").val(AppPath);
		$("#dspRBAppTitle").val(appTitle);
	}
	//Add app for select attribute field
	if($(currElem).attr("elem") == "select"){
		$("#selectLUApplication").val(AppPath);
		$("#dspSelectAppTitle").val(appTitle);
	}	
	//Add app for emb view attribute field
	if ( $(currElem).attr("elem") == "embeddedview" ){
		$("#embLUApplication").val(AppPath);
		$("#dspEmbViewAppTitle").val(appTitle);
	}	
	//Add app for chart attribute field
	if ( $(currElem).attr("elem") == "chart" ){
		$("#chartLUApplication").val(AppPath);
		$("#dspChartAppTitle").val(appTitle);
	}
	//Add app for appelement attribute field in widget builder
	if ($(currElem).attr('elem') == 'appelement'){
		$('#appelementLUApplication').val(AppPath);
		$('#dspAppElementTitle').val(appTitle);
	}
	//Add app for picklist view attribute field
	if ( $(currElem).attr("elem") == "picklist" ){
		$("#picklistLUApplication").val(AppPath);
		$("#dspPicklistAppTitle").val(appTitle);
	}	
	//Add app for counterbox attribute field
	if ( $(currElem).attr("elem") == 'counterbox' ){
		$('#ctrbLUApplication').val(AppPath);
		$('#dspCounterBoxAppTitle').val(appTitle);
	}	
	
	getViewList();
	setProperties();
	return;
}

function formatAdditionalStyle(strStyle){
//Formats the string to be attr colon space value semicolon space (last space if more than 1 attribute)
//eg: min-height: 30px; min-width: 200px;
//The purpose is to format the additional style similar to the way jquery does so that the additional
//style string can be found in the style string to remove the old additional style, and then append the new sting
	strStyle = strStyle.replace(/ +(?= )/g,''); //remove any instances of multi-spaces together to be one space
	strStyle = strStyle.replace(/: /g, ":") //replace all colon-space with colon
	strStyle = strStyle.replace(/; /g, ";") //replace all semicolon-space with comma
	strStyle = strStyle.replace(/ :/g, ":") //replace all space-colon with colon
	strStyle = strStyle.replace(/ ;/g, ";") //replace all space-semicolon with semicolon
	strStyle = strStyle.replace(/:/g, ": ") //replace all colon with colon-space
	strStyle = strStyle.replace(/;/g, "; ") //replace all semicolon with semicolon-space
	strStyle = $.trim(strStyle); //remove leading/trailing spaces
	return strStyle;
}

function adjustSourcePaths(pathtype, inobj)
{

	if(typeof pathtype == 'undefined'){
		return;
	}
	if(pathtype.toLowerCase() == "relative"){
		//for all images on the page/form/subform make src relative

		var obj = inobj ? inobj.find("img[elem=image]") : $("img[elem=image]");

		obj.each(function(){
			if ($(this).attr('imagename') == 'DOCOVA-Logo.png') {
				$(this).prop("src", docInfo.ImgPath + '../' + $(this).attr("imagename"));
			}
			else {
				$(this).prop("src", docInfo.ImgPath + $(this).attr("imagename"));
			}
		});
		
	}else if(pathtype.toLowerCase() == "full"){
		//after the save of the design element, the image urls need to be re-added for the element builder
		var obj = inobj ? inobj.find("img[elem=image]") : $("img[elem=image]");

		obj.each(function(){
			if ($(this).attr('imagename') == 'DOCOVA-Logo.png') {
				$(this).prop("src", docInfo.ImgPath + '../' + $(this).attr("imagename"));
			}
			else {
				$(this).prop("src", docInfo.ImgPath + $(this).attr("imagename"));
			}
		});
	}
}

function getCurrRange(){
	currSel = window.getSelection();
	if(currSel.getRangeAt && currSel.rangeCount){
		currRange = currSel.getRangeAt(0);
	}
	return;
}

function insertElement(html) {
	// Range.createContextualFragment() would be useful here but is
	// non-standard and not supported in all browsers (IE9, for one)
	getCurrRange();
     var el = document.createElement("div");
     el.innerHTML = html;
     var frag = document.createDocumentFragment(), node, lastNode;
     while ( (node = el.firstChild) ) {
		lastNode = frag.appendChild(node);
	}
	
	currRange.insertNode(frag); 

	// Preserve the selection
	if (lastNode) {
		currRange = currRange.cloneRange();
		currRange.setStartAfter(lastNode);
		currRange.collapse(true);
		currSel.removeAllRanges();
		currSel.addRange(currRange);
	} 
	
    hideSelectors();
	setSelectable(); 
	setSelectors();
}

function applyTool(editObj, reset){
	pushUndo(); 
	var editAction = $(editObj).attr("editaction");
	
	var sel = window.getSelection();
	var selectedtext = sel.toString();
	if(selectedtext.length != 0){ //means some text is highlighted
		var textSelected = true;
	}else{
		var textSelected = false;
	}
	
	if(editAction == "FontName"){
		var editValue = $(editObj).val();
		if(editValue == "null"){
			editValue = "verdana";
		}
		if(textSelected){
			document.execCommand(editAction, "", editValue);
		}else{
			$(currElem).css("font-family", editValue);
		}
	}else if(editAction == "FontSize"){
		var editValue = $(editObj).val();
		if(editValue == "null"){
			editValue = "2";
		}
		if(textSelected){
			document.execCommand(editAction, "", editValue);
		}else{
			//convert 1-7 to pixel equivalent
			switch(editValue){
			case "1":
				editValue = "8px";
			break;
			case "2":
				editValue = "10px";
			break;
			case "3":
				editValue = "12px";
			break;
			case "4":
				editValue = "14px";
			break;
			case "5":
				editValue = "18px";
			break;
			case "6":
				editValue = "24px";
			break;
			case "7":
				editValue = "36px";
			break;
			}
			$(currElem).css("font-size", editValue);
		}
	}else if(editAction == "FormElement"){
		elemType = $(editObj).val();
		getElemHTML(elemType);
	}else if(editAction == "Cut"){
		cut_field();
	}else if(editAction == "Copy"){
		copy_field();
	}else if(editAction == "Paste"){
		paste_field();
	}else if(editAction == "Bold"){
		if(textSelected){
			document.execCommand(editAction);
		}else{
			if($(currElem).css("font-weight") == 700){
				$(currElem).css("font-weight", 400);
			}else{
				$(currElem).css("font-weight", 700);
			}
		}
	}else if(editAction == "Italic"){
		if(textSelected){
			document.execCommand(editAction);
		}else{
			if($(currElem).css("font-style") == "italic"){
				$(currElem).css("font-style", "normal");
			}else{
				$(currElem).css("font-style", "italic");
			}
		}
	}else if(editAction == "Underline"){
		if(textSelected){
			document.execCommand(editAction);
		}else{
			if($(currElem).css("text-decoration") == "underline"){
				$(currElem).css("text-decoration", "none");
			}else{
				$(currElem).css("text-decoration", "underline");
			}
		}
	}else if(editAction == "ForeColor"){
		if(textSelected){
			document.execCommand(editAction);
		}else{
			$(currElem).css("color", "none");
		}			
	}else{
		document.execCommand(editAction);
	}
}

function loadToolBarButtons()
{
	var spanrepeat = 0;
	var tools = ["sep","Bold","Italic","Underline","sep","JustifyLeft","JustifyCenter",
	"JustifyRight","sep","Cut","Copy","Paste","sep","ForeColor","sep","FontName","FontSize"];
	
	tbar = document.getElementById("ToolBar")
	tbarHTML = "";
	for(x=0; x<tools.length; x++){
		if(tools[x] == "sep"){ //uses sep to make buttonsets
			spanrepeat = spanrepeat + 1;
			if(spanrepeat == 1){
					tbarHTML += "<span class='btngroup'>"
			}
			if(spanrepeat > 1){
				tbarHTML += "</span><span class='btngroup'>"
			}
		}
		else if(tools[x] == "FontName") {
			tbarHTML += "<select unselectable='on' class='DropDown' id=" + tools[x] + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" +
			"<option value='null'>- Select Font -" + 
			"<option value='arial'>Arial" +
			"<option value='comic sans ms'>Comic Sans" +
			"<option value='courier new'>Courier New" + 
			"<option value='helvetica'>Helvetica" + 
			"<option value='times new roman'>Times New Roman" +
			"<option value='verdana'>Verdana</select>"
		} 
		else if(tools[x] == "FontSize") {
			tbarHTML += " <select unselectable='on' class='DropDown' id=" + tools[x] + " onchange='applyTool(this, true);' editaction='" + tools[x] + "'>" + 
			"<option value='null'>- Font Size -" +
			"<option value='1'>1 (8px)" +
			"<option value='2'>2 (10px)" +
			"<option value='3'>3 (12px)" +
			"<option value='4'>4 (14px)" + 
			"<option value='5'>5 (18px)" + 
			"<option value='6'>6 (24px)" +
			"<option value='7'>7 (36px)</select>"
		}
		else if(tools[x] == "ForeColor" ||tools[x] == "BackColor") { 
			tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='setColor(event)' id=" + tools[x] + " editaction='" + tools[x] + "' title=" + tools[x] + "></button>"
		}
		else if(tools[x] == "Help") {
			tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='showHelp(this);' id=" + tools[x] + " editaction='" + tools[x] + "'></button>"
		}
		else {
			tbarHTML += "<button unselectable='on' type='button' class='btnDEditor " + "edt" + tools[x] + "' onclick='applyTool(this)' id=" + tools[x] + " editaction='" + tools[x] + "' title='" + tools[x] + "'></button>"
		}
	}
	if(spanrepeat != 0){
		tbarHTML += "</span>" //close off last span tag for button groups if any
	}
	$(tbar).html(tbarHTML)

}


function setTextColor(colorval){
	var sel = window.getSelection();
	var selectedtext = sel.toString();
	if(selectedtext.length != 0){ //means some text is highlighted
		var textSelected = true;
	}else{
		var textSelected = false;
	}
	if(textSelected){
		document.execCommand(currEditAction, "", colorval)
	}else{
		$(currElem).css("color", colorval);
	}
}

function setColor(event){
	var source = event.target;
	var editAction = $(source).attr("editaction")
	currEditAction = editAction;

	window.top.Docova.Utils.colorPicker(event, "", setTextColor)
}

function setPPosition(currentNode){
	var parentElem = currentNode.parentNode;
	
	if($(parentElem).prop("tagName") == "P"){
		currElem = parentElem;
		setSelectable();
		setSelected();
	}else if($(parentElem).prop("tagName") == "DIV"){
		currElem = currRange.startContainer;
		setSelectable();
		setSelected();
	}else if($(parentElem).prop("tagName") == "TD"){
		currElem = currRange.startContainer;
		setSelectable();
		setSelected();		
	}else{
		currElem = $(currentNode).parentsUntil("p").parent();
		setSelectable();
		setSelected();
	}
}

function gelementDropped(event, ismobile)
{
	event.stopImmediatePropagation();
	eventintervalcounter = 0;
	if(typeof dropIntervalId !== "undefined" && dropIntervalId !== null){
		clearInterval(dropIntervalId);
		dropIntervalId = null;
	}	
	dropIntervalId = setInterval(function(){ dropElement(event, ismobile); }, 100);
}

function elementDropped(event, ismobile)
{
	event.stopImmediatePropagation();
	eventintervalcounter = 0;
	if(typeof dropIntervalId !== "undefined" && dropIntervalId !== null){
		clearInterval(dropIntervalId);
		dropIntervalId = null;
	}	
	dropIntervalId = setInterval(function(){ dropElement(event, ismobile); }, 100);
}

function actionbuttondropped(ismobile){
	eventintervalcounter = 0;
	if(typeof dropActionIntervalId !== "undefined" && dropActionIntervalId !== null){
		clearInterval(dropActionIntervalId);
		dropActionIntervalId = null;
	}
	dropActionIntervalId = setInterval(function(){dropActionButton(ismobile);}, 100);
}

function dropActionButton(ismobile){
	eventintervalcounter ++;
	if(eventintervalcounter > 5){
		clearInterval(dropActionIntervalId);
		eventintervalcounter = 0;
	}
	
	var targetdiv = "#divToolBar" + (typeof ismobile !== "undefined" && ismobile !== null && (ismobile == true || ismobile == 1) ? "_m" : "");
	var droppedElem = $(targetdiv).find(".newElement");
	if($(droppedElem).length){
		if(typeof dropActionIntervalId !== "undefined" && dropActionIntervalId !== null){
			clearInterval(dropActionIntervalId);
			dropActionIntervalId = null;
		}		
		
		var elem = $(droppedElem).attr("elem");
		$(droppedElem).remove();
		if(elem == "button"){
				var tmpButtonHTML = $(getButtonHTML())
				var newButtonId = tmpButtonHTML.prop("id") //get the id from the newly generated button html
				insertElement(getButtonHTML());
				$("#" + newButtonId).button({ label: "Button", text: true });
		}
	}
	return false;
}

function dragdtfield(ev){
	ev.dataTransfer.setData("text", ev.target.id);
	ev.dataTransfer.setData("text/html", "<a class='dtfieldph' elem='datatablefield' id='" + ev.target.id +  "' href=''></a>");
	ev.dataTransfer.dropEffect = "copy";
}



function chartitemallowdrop(ev){
	ev.preventDefault();
}

function chartitemdragged(ev){
	ev.dataTransfer.setData("text", ev.target.id);
	ev.dataTransfer.dropEffect = "copy";
}

function chartitemdropped(ev){ 
	 ev.preventDefault();

	 var data=ev.dataTransfer.getData("text");
	 var targetid = ev.target.id;
	 var sourceNode = document.getElementById(data);
	 var dropNode = null;

	 var valop = "";
	 var newid = "";
	 if(targetid == "chart_legenditems"){
		 newid = "cli_";			 
	 }else if(targetid == "chart_axisitems"){
		 newid = "cai_";	
	 }else if(targetid == "chart_valueitems"){
		 valop = "sum";
		 newid = "cvi_";	
	 }else{
		 return false;
	 }

	 newid += data.substring(data.indexOf("_")+1).replace(/ /g, "_").toLowerCase();

	 //check to see if we need to adjust the id for a duplicate summary value
	 if(targetid == "chart_valueitems"){
		 var dupcount = 0;
		 while(jQuery("#"+targetid).find("span[id="+newid+(dupcount > 0 ? "_"+dupcount.toString() : "")+"]").length > 0){
			 dupcount ++;
		 }
		 if(dupcount > 0){
			 newid += "_" + dupcount.toString();
		 }
	 }
	 
	 //check if item already exists with that id
	 if(jQuery("#"+targetid).find("span[id="+newid+"]").length > 0){		 
		 //check to see if id is the same if so then let it go as a move
		 if(data != newid){
				return false; 
		 }
	 }

	 if(jQuery(sourceNode).hasClass("chart_source_item")){
		 dropNode = sourceNode.cloneNode(true);		 
	 }else{
		dropNode = sourceNode; 
	 }	 
	 	 
	 dropNode.id = newid;	 
	 var newname = jQuery(dropNode).attr("sourcefieldtitle");
	 if(valop != ""){
		 newname = valop.substr(0,1).toUpperCase() + valop.substr(1).toLowerCase()  + " of " + newname;
	 }
	 jQuery(dropNode).text(newname).attr("valop", valop).removeClass("chart_source_item").off("contextmenu").on("contextmenu", function(e){ menuHandler(e); });
	 ev.target.appendChild(dropNode);	 
	 
	 setProperties();
}


function getCurrentParObj(obj){
	return $(obj).closest("p[elem='par']")
}

function setParFocus(obj){
	//obj is the jQuery p par element
	var temppar = $(obj).get(0); //get the native html element
    range = document.createRange();
	range.selectNodeContents(temppar);
	range.collapse(false);
	var sel = window.getSelection();
	sel.removeAllRanges();
	sel.addRange(range);
	return;	
}

function generateJsonOutline(options, parentUl)
{
	var defaultOptions = {
		jsonobject: null,
		iteration : 0
	}
	var opts = jQuery.extend({}, defaultOptions, options);
	opts.iteration++;

	if(opts.jsonobject === null){
		opts.jsonobject = jQuery.extend({}, defaultJsonOutline);
	}
	
	if(opts.iteration == 1){
		opts.jsonobject.Items = [];
		opts.jsonobject.Items.length = 0;

	}

	var innerList = new Array();
	var list = (typeof parentUl == "undefined" || parentUl === null) ? $('#divOutlineBuilder > ul > li') : parentUl.children('li');
	list.each(function() {
		var fw = $('div:first', this).attr('d-font-weight');
		var is_bold = typeof fw != typeof (undefined)  && ( fw.toLowerCase() == 'bold' || fw == '700') ? true : false;
		var fw = $('div:first', this).attr('d-font-style');
		var is_italic = typeof fw != typeof (undefined)  && ( fw.toLowerCase() == 'italic') ? true : false;
		var expicon = null;
		var colicon = null;
		if ($(this).attr('emenuitemtype') == 'H' && !$(this).attr('expicon')) {
			expicon = "fa-caret-down"
		}else{
			expicon = $(this).attr('expicon') ? $(this).attr('expicon') : null;
		}
		
		if ($(this).attr('emenuitemtype') == 'H' && !$(this).attr('colicon')) {
			colicon = "fa-caret-right"
		}else{
			colicon = $(this).attr('colicon') ? $(this).attr('colicon') : null;
		}

		var iconcl = "";
		if ( $('span:first', this).length > 0 )
		{
			iconcl = $('span:first', this).get(0).style.color;
		}		

		var menu_items = {
			context: $('div:first', this).text(),
			type: $(this).attr('emenuitemtype') == 'H' ? 'header' : 'menu',
			etype: typeof ($(this).attr('etype')) == typeof (undefined) ? null : $(this).attr('etype'),
			etarget : typeof ($(this).attr('etarget')) == typeof (undefined) ? null : $(this).attr('etarget'),
			eelement: typeof ($(this).attr('eelement')) == typeof (undefined) ? null : $(this).attr('eelement'),
			eviewtype : typeof ($(this).attr('eviewtype')) == typeof (undefined) ? null : $(this).attr('eviewtype'),
			enotab: $(this).attr('eNoTab') == '1' ? 1 : 0,
			isSpacer : $(this).attr("isSpacer") && $(this).attr("isSpacer") == '1' ?  1 : 0,
			helptext : $(this).attr("Title") && $(this).attr("Title") != '' ?  $(this).attr("Title") : null,
			initselected : $(this).attr('emenuitemtype') == 'H' ? false : ($(this).attr('einitiallyselected')=="1" || $(this).attr('einitiallyselected')==true ? true : false),
			initexpand : ($(this).attr('einitiallyexpanded') == "1" || $(this).attr('einitiallyexpanded') == true ? true : false),			
			icontitle : $('span:first', this).attr('icontitle') ? $('span:first', this).attr('icontitle') : null,
			iconcolor : iconcl != "" ? iconcl : null,

			iconplacement: $('span:first', this).css('float') && $('span:first', this).css('float') != "" ? $('span:first', this).css('float') : null,
			customicon : $(this).attr("docova_custom_icon") && $(this).attr("docova_custom_icon") == "1" ? "1" : "0" ,
			expandicon: expicon,
			collapseicon: colicon,
			hidewhen : typeof ($(this).attr('customhidewhen')) == typeof (undefined) ? "" : $(this).attr('customhidewhen'),
			labelformula:  typeof ($(this).attr('labelformula')) == typeof (undefined) ? "" : $(this).attr('labelformula'),
			iconfontsize : $('span:first', this).css('font-size'),

			size :    typeof ($('div:first', this).attr('d-font-size')) == typeof (undefined) ? null : $('div:first', this).attr('d-font-size'),
			isbold :  is_bold,
			isitalic : is_italic,
			fontcolor : typeof ($('div:first', this).attr('d-color') ) == typeof (undefined) ? null: $('div:first', this).attr('d-color'),
			Items : $(this).attr('emenuitemtype') == 'H' || $(this).next().is('ul') ? [] : null
		};
		if (menu_items['Items'] !== null && $(this).next('ul').length) {
			menu_items['Items'] = generateJsonOutline(opts, $(this).next('ul'));
		}

		if (opts.iteration === 1){
			opts.jsonobject.Items.push(menu_items);
		}else {
			innerList.push(menu_items);
		}
	});

	if (opts.iteration === 1){
		return opts.jsonobject;
	}else{
		return innerList;
	}
}


function UpgradeCheck(silent){
	if(info.DesignElementType != "Outline" && (info.IsNewDoc == "1" || !info.isDocBeingEdited || info.TriggerDesignCreation)){
		return false;
	}
	if(console){console.log("Performing upgrade check....")};
	var priorversion = 0;
	if(info && info.DesignElementType){
		switch (info.DesignElementType || ""){
		case "Form":
		case "Subform":
		case "Page":
		case "Outline":
			priorversion = (jQuery("#designversion").text() || 0);			
			break;
		default:
			return false;
		}
	}
	priorversion = Number(priorversion);
	if (designversion > priorversion){

		if ( silent ){
			upgradeDesign(priorversion);
		}else{

			if(confirm("This element's design is from an older version and should be upgraded.\n Select OK to perform an upgrade, or Cancel to leave the design unchanged.")){
				upgradeDesign(priorversion);
			}	
		}		
	}
}


function upgradeDesign(priorversion){
	
	if(info.DesignElementType != "Outline"){
		//forms,subforms,pages		
		var $layout = jQuery("#layout");
	
		if(priorversion < designversion){
			
			$('#layout div[elemtype=section][elem=fields]').wrapAll('<div class="grid-stack"><div class="grid-stack-item selectable ui-draggable ui-resizable" elem="panel" data-gs-x="0" data-gs-y="0" data-gs-width="12" data-gs-height="30"><div class="grid-stack-item-content"></div></div></div>');
			$layout.find('div.grid-stack-item-content').append('<i title="Move Panel" class="dhgrip fas fa-grip-horizontal ui-draggable-handle"></i><i title="Delete Panel" class="rmpanel far fa-times"></i>');
			initGridStack();

			//-- change action button bar attributes
			$layout.find("#divToolBar").attr({
				"ondrop" : "actionbuttondropped()",
				"contenteditable" : "true"
			}).css({
				"margin-bottom" : "5px",
				"min-height" : "25px",
				"border" : "1px solid #cfcfcf"
			}).removeClass();
		
			//-- change attributes and styling on field sections
			$layout.find("div[elemtype=section][elem=fields]").attr({
				"contenteditable" : "true",
				"elemtype" : "section",
				"elem" : "fields"
			}).css({
				"overflow" : "hidden"
			}).prepend('<p class="selectable" elemtype="par" elem="par" style="padding:5px;"></p>');
		
			//-- change attributes on button elements
			$layout.find("button").attr({
				"contenteditable" : "false"
			});
		
			//-- convert select elements to input elements
			$layout.find("select[elem=select][optionmethod=manual]").each(function(){
				var optionlist = "";
				jQuery(this).find("option").each(function(){
					var val = jQuery(this).val()||"";
					var lbl = jQuery(this).text();
					optionlist += (optionlist !== "" ? ";" : "") + lbl + "|" + val;
				});
				jQuery(this).attr("optionlist", optionlist);
			});
			//-- assign name attributes to select field elements missing them
			$layout.find("input[elem=select]").each(function(){
				if(jQuery(this).attr("name") !== jQuery(this).attr("id")){
					jQuery(this).attr("name", jQuery(this).attr("id"));
				}			
				if(jQuery(this).attr("name") !== jQuery(this).attr("placeholder")){
					jQuery(this).attr("placeholder", jQuery(this).attr("name"));
				}			
			});
			$layout.find("select[elem=select]").find("option").remove();
			$layout.find("select[elem=select]").replaceWith(function(){
				var newhtml = jQuery(this)[0].outerHTML;
				newhtml = newhtml.replace(/\<select /i, "<input ").replace(/\<\/select\>/i, "</input>");
				return newhtml;
			});
		
			//-- convert radio buttons to input fields
			$layout.find("table[elem=rdbutton][optionmethod=manual]").each(function(){
				var optionlist = "";
				var fieldname = "";
				jQuery(this).find("td").each(function(){
					fieldname = jQuery(this).find("input[type=radio]:first").attr("name");
					var val = jQuery(this).find("input[type=radio]:first").val()||"";
					var lbl = jQuery(this).find("span:first").text()||"";
					optionlist += (optionlist !== "" ? ";" : "") + lbl + "|" + val;
				});
				jQuery(this).html("");
				jQuery(this).attr("optionlist", optionlist).attr("id", fieldname).attr("name", fieldname);
			});		
			$layout.find("table[elem=rdbutton]").each(function(){
				var newhtml = jQuery(this)[0].outerHTML;
				newhtml = newhtml.replace(/^\s*\<table /i, "<input ").replace(/\<\/table\>\s*$/i, "</input>");
				jQuery(this).replaceWith(newhtml);			
			});
			//-- assign name attributes to radio button field elements missing them
			$layout.find("input[elem=rdbutton]").each(function(){
				if(jQuery(this).attr("name") !== jQuery(this).attr("id")){
					jQuery(this).attr("name", jQuery(this).attr("id"));
				}			
				if(jQuery(this).attr("name") !== jQuery(this).attr("placeholder")){
					jQuery(this).attr("placeholder", jQuery(this).attr("name"));
				}			
			});		
			
			//-- convert check boxes to input fields
			$layout.find("table[elem=chbox][optionmethod=manual]").each(function(){
				var optionlist = "";
				var fieldname = "";
				jQuery(this).find("td").each(function(){
					fieldname = jQuery(this).find("input[type=checkbox]:first").attr("name");
					var val = jQuery(this).find("input[type=checkbox]:first").val()||"";
					var lbl = jQuery(this).find("span:first").text()||"";
					optionlist += (optionlist !== "" ? ";" : "") + lbl + "|" + val;
				});
				jQuery(this).html("");
				jQuery(this).attr("optionlist", optionlist).attr("id", fieldname).attr("name", fieldname);
			});		
			$layout.find("table[elem=chbox]").each(function(){
				var newhtml = jQuery(this)[0].outerHTML;
				newhtml = newhtml.replace(/^\s*\<table /i, "<input ").replace(/\<\/table\>\s*$/i, "</input>");
				jQuery(this).replaceWith(newhtml);			
			});	
			//-- assign name attributes to checkbox field elements missing them
			$layout.find("input[elem=chbox]").each(function(){
				if(jQuery(this).attr("name") !== jQuery(this).attr("id")){
					jQuery(this).attr("name", jQuery(this).attr("id"));
				}			
				if(jQuery(this).attr("name") !== jQuery(this).attr("placeholder")){
					jQuery(this).attr("placeholder", jQuery(this).attr("name"));
				}			
			});		
		
			//-- convert span computed text elements to input elements
			$layout.find("span[elemtype=field][elem=ctext]").replaceWith(function(){
				var newhtml = jQuery(this)[0].outerHTML;
				newhtml = newhtml.replace(/^\s*\<span /i, "<input ").replace(/\<\/span\>\s*$/i, "</input>").replace("[ComputedText]", "");
				return newhtml;
			});					
			//-- assign placeholder attribute to computed text elements missing them
			$layout.find("input[elem=ctext]").each(function(){
				if(jQuery(this).attr("placeholder") != "$$ComputedText"){
					jQuery(this).attr("placeholder", "$$ComputedText");
				}			
			});
			
			//-- assign ondrop event to field elements
			$layout.find("input[elemtype=field]").attr({
				"ondrop" : "return false;"
			}).removeClass().addClass("selectable");
			
			//-- assign default editable attribute to field elements where it is not set
			$layout.find("input[elemtype=field]:not([texttype])").attr({
				"texttype" : "e"
			});

			//-- change attributes on tables
			$layout.find("table").attr({
				"elem" : "table",
				"elemtype" : "field",
				"contenteditable" : "false"
			});
			var subtablecount=0;
			$layout.find("table").each(function(){
				if(!jQuery(this).attr("id")){
					subtablecount++;
					jQuery(this).attr("id", "sub_table_" + subtablecount.toString());
				}
				if(jQuery(this).attr("style").indexOf("width:") == -1){
					jQuery(this).css("width", "100%");
				}
			});
		
			//-- change attributes on td elements
			$layout.find("td").attr({
				"contenteditable" : "true"
			}).removeClass("droppable ui-droppable draggable");
			
		
			//-- wrap td content in separate P elements for each BR
			$layout.find("td").each(function(){
				var startpos = 0;
				var $subelem = jQuery(this).children();
				var endpos = 0;
				for(var x=0; x<$subelem.length; x++){				
					var dowrap = false;
					if($subelem[x].tagName.toUpperCase() == "BR"||$subelem[x].tagName.toUpperCase() == "TABLE"){ 
						endpos = x;
						dowrap = true;
					}else if(x==($subelem.length - 1)){
						endpos = x + 1;
						dowrap = true;
					}
					if(dowrap){
						if(endpos > startpos){
							var tempelems = $subelem.slice(startpos, endpos);
							jQuery(tempelems).wrapAll('<p class="selectable" elemtype="par" elem="par" style="padding:5px;"></p>');
						}
						startpos = x+1;
					}
				}
			});
			//-- remove any remaining br elements within the p tags
			$layout.find("td>br").remove();
			
			//-- remove unnecessary &nbsp; chars from TDs
			$layout.find("td").contents().filter(function(){
				return (this.nodeType === 3);
			}).each(function(){
				jQuery(this).remove();
			});		
			
			//-- change attachment area format
			$layout.find("div[elemtype=field][elem=attachments]").each(function(){
				jQuery(this).attr("contenteditable", "false").removeClass().addClass("selectable noselect");
				jQuery(this).find("div.item").attr("style", null);
				jQuery(this).find("i.fa-file-pdf-o").removeClass("fa-file-pdf-o").addClass("fa-paperclip").attr("style", "color:gray;");
				jQuery(this).find("span.caption").text("Attachments");
			});
			
			$layout.find('div[elemtype=section][elem=field]').each(function(){
				jQuery(this).attr('elem', 'fields');
			});
			
			$layout.find('div.grid-stack-item-content').each(function(i){
				if (!jQuery(this).prop('id') || jQuery.trim(jQuery(this).prop('id')) == '') {
					jQuery(this).prop('id', 'panel_section_'+ (i+1));
				}
			});
		
			/*
			$layout.find("p>label").each(function(){
				var labelstyle = (jQuery(this).attr("style") || "");
				var labeltext = jQuery(this).text();
				var parentelem = jQuery(this).parent();
				jQuery(parentelem).text(labeltext);
				jQuery(parentelem).attr("style", labelstyle);
				jQuery(this).remove();
			});		
			 */
		}//--end priorversion < 1
	
		//-- set the design version attribute
		if($layout.find("#designversion").length == 0){
			$layout.append('<div id="designversion" style="display:none;">' + designversion.toString() + '</div>');
		}else{
			$layout.find("#designversion").text(designversion.toString());
		}
	}//--end forms,subforms,pages
}

function bindElmOnFocus(){
	$("div [elem=fields] :input, div [elem=fields] button, div [elem=fields] TD").on('focus', function(e){  //set on focus
		setCurrElem(this);						
		setSelected();
	});
}

function initFieldsSections(){
	$('#layout').on('click', 'div [elem=fields]', function(){  //set on click
		getCurrRange();
	});

	$('#layout').on('keyup', 'div[elem=fields]', function(e){  //set on keyup
		if ($(e.target).closest("[elem!=''][elem]").attr("elem").toUpperCase() == 'HTMLCODE') {
			return;
		}
		getCurrRange();
		var currentNode = currRange.startContainer;
		var evtobj = window.event? event : e;
		
		var elemtype = ($(currentNode).closest("[elem!=''][elem]").attr("elem") || "");		
		var containerType = ($(currentNode).closest("p").prop("tagName") || $(currentNode).prop("tagName") || "");
		
		switch (e.which){
			case 13: //enter key
				setPPosition(currentNode);
				setSelectable();
				setSelected();
				break;
			case 38: //up arrow
				setPPosition(currentNode);
				break;
			case 40: //down arrow
				setPPosition(currentNode);
				break;
			case 37: //left arrow
				setPPosition(currentNode);	
				break;
			case 39: //right arrow
				setPPosition(currentNode);
				break;
			case 46: //del key
				setPPosition(currentNode);
				break;
			case 9: //tab
				if(containerType == "TD"){
					currElem = $(currentNode).children().first();//gets the first P in the TD if tabbing into a td
					var range = document.createRange();
					range.selectNodeContents(currElem[0]);
					range.collapse(false); //false collapses to end, true collapses to start
					var sel = window.getSelection();
					sel.removeAllRanges();
					sel.addRange(range);
					setSelected();
				}
				break;
			default:
				setPPosition(currentNode);
		}	
		setSelected();
	});


	$('#layout').on('keydown', 'div[elem=fields]', function(e){   //set on keydown		
		getCurrRange();
		var currentNode = currRange.startContainer;
		var evtobj = window.event? event : e
		
		var elemtype = ($(currentNode).closest("[elem!=''][elem]").attr("elem") || "");		
		var containerType = ($(currentNode).closest("p").prop("tagName") || $(currentNode).prop("tagName") || "");
		
		if(containerType.toUpperCase() == "P"){ //if current node is, or is in a P
			if(evtobj.keyCode == 8){ //backspace
					var nType = currentNode.nodeType;
					if(nType == 1){ //returns P
						if($(currentNode).html() == "" || $(currentNode).html() == "<br>" || $(currentNode).html() == '<br type="_moz">'){
							var nextElem = $(currElem).next();
							var prevElem = $(currElem).prev();
							if(prevElem.length){
								cElem = currElem;
								$(cElem).removeClass("selected");
								currElem = prevElem;
								$(currElem).addClass("selected");
								$(cElem).remove();
  								var range = document.createRange();
								range.selectNodeContents(currElem[0]);
								range.collapse(false); //false collapses to end, true collapses to start
								var sel = window.getSelection();
								sel.removeAllRanges();
								sel.addRange(range);
 								setSelected();
							}
							return false;
						}
					}else if ( currRange.startOffset == 0 && currRange.endOffset == 0 ){
						return false;
					}
				}
			if(evtobj.keyCode == 46){ //delete
					if($(currentNode).html() == "" || $(currentNode).html() == "<br>" || $(currentNode).html() == '<br type="_moz">'){
						var nextElem = $(currElem).next();
						var prevElem = $(currElem).prev();
						if(nextElem.length){
							var cElem = currElem;
							setCurrElem(nextElem);
							$(cElem).remove()
  							var range = document.createRange();
							range.selectNodeContents(currElem[0]);
							range.collapse(true); //false collapses to end, true collapses to start
							var sel = window.getSelection();
							sel.removeAllRanges();
							sel.addRange(range);						
							setSelected();
						}
						return false;
					}else{		
						var nextElem = $(currentNode).closest("p").next();
						var nextElemTag = ($(nextElem).prop("tagName") || "");
						var nextElemType = ($(nextElem).closest("[elem!=''][elem]").attr("elem") || "");	
						
						if(nextElemTag.toUpperCase() == "DIV"){
							//need to check if this delete operation will delete the following non p element
							var tempcontent = ($(currentNode).html() || $(currentNode).text() || "");
							var pos = currRange.endOffset;
							var remaindercontent = tempcontent.slice(pos);
							if(remaindercontent == ""){
								e.preventDefault;
								return false;
							}
						}
					}
			}
			if(evtobj.ctrlKey && evtobj.keyCode == 67){  //ctrl+c was pressed for copy
				e.preventDefault();
				copy_field();
			}
			if(evtobj.ctrlKey && evtobj.keyCode== 86){  //ctrl+v was pressed for paste
				e.preventDefault();
				paste_field();
			}
			if(evtobj.ctrlKey && evtobj.keyCode == 88){  //ctrl+x was pressed for cut
				e.preventDefault();
				cut_field();
			}
			if(evtobj.ctrlKey && evtobj.keyCode == 66){  //ctrl+b was pressed for bold
				e.preventDefault();
				document.execCommand("Bold")
			}
			if(evtobj.ctrlKey && evtobj.keyCode == 73){  //ctrl+i was pressed for italic
				e.preventDefault();
				document.execCommand("Italic");
			}
			if(evtobj.ctrlKey && evtobj.keyCode == 85){  //ctrl+u was pressed for underline
				e.preventDefault();
				document.execCommand("Underline");
			}
		}else if(elemtype == "htmlcode"){  //if current node is not P and is an htmlcode element let the keystrokes through
			//-- do nothing
		}else{ //if current node is not P, and not an htmlcode element then allow only ctrl+c and ctrl+x			
			if(evtobj.ctrlKey && evtobj.keyCode == 67){  //ctrl+c was pressed for copy
				e.preventDefault();
				copy_field();
			}else if(evtobj.ctrlKey && evtobj.keyCode== 88){  //ctrl+x was pressed for paste
				e.preventDefault();
				cut_field();
			}else{
				e.preventDefault();
			}
		}			
	});
	return;
}


//functions used by the viewbuilder to convert the hide/whens for buttons to TWIG
function handleHideWhenTWIG(obj){

	
	if(typeof obj.attr != "function"){
		obj = jQuery(obj);
	}
	var hidewhenpardef = "";
	var formula = "";
	var hideWhen = obj.attr("hidewhen")
	if ( hideWhen && hideWhen != "" ){
		var customhidewhen = obj.attr("customhidewhen")
		if ( jQuery.trim(customhidewhen) != "" )
		{
			hidewhenpardef = updateTWIG (customhidewhen, "set", "bool");
			hidewhenpardef += '{% set hwen = __dexpreres %}\r\n'
			formula =  'hwen';
			hidewhenpardef += '{% if  not ( ' + formula + ') %}\r\n';
				
		}
		
		
	}
	return hidewhenpardef;
}

function GetViewToolbarTwig(container){
	var dxl = "";
	container.find('button').each(function() {
		var button = $(this);
		var hide = handleHideWhenTWIG(this);
		if ( hide != "")
			dxl += hide + "\r";
		
		var onclickcode = button.attr('onclick');		
		onclickcode = safe_quotes_js (onclickcode);
		
		dxl+= '<button onclick="' + onclickcode + '" ' ;
		var iconprim = (button.attr("btnprimaryicon") ? button.attr("btnprimaryicon") : (button.attr('iconleft') ? button.attr('iconleft') : ''));
		var iconsec = (button.attr("btnsecondaryicon") ? button.attr("btnsecondaryicon") : (button.attr('iconright') ? button.attr('iconright') : ''));
		var showtext = (button.attr('showlabel') === "0" || button.attr('btntext') === "0" ? false : true);
		var title = (button.attr('btnlabel') ? button.attr('btnlabel') : (button.attr('title') ? button.attr('title') : ''));
		dxl+= 'iconleft="' + iconprim + '" ';
		dxl+= 'iconright="' + iconsec + '" ';
		dxl+= 'title="' + (showtext ? title : '') + '" ';
		dxl+= 'id="'+(button.attr('id') ? button.attr('id') : '')+ '">';
		dxl += (showtext ? title : '');
		dxl += "</button>\n\r\t";

		if ( hide != "")
			dxl += '{% endif %}\r'
	});
	
	return dxl;

}

function GetViewToolbarMobileTwig(container){

	var dxl = "";
	container.find('button').each(function() {
		var button = $(this);
		var hide = handleHideWhenTWIG(this);
		if ( hide != "")
			dxl += hide + "\r";
		
		var onclickcode = button.attr('onclick');
		
		onclickcode = safe_quotes_js (onclickcode);

		var iconprim = (button.attr("btnprimaryicon") ? button.attr("btnprimaryicon") : (button.attr('iconleft') ? button.attr('iconleft') : ''));
		var iconsec = (button.attr("btnsecondaryicon") ? button.attr("btnsecondaryicon") : (button.attr('iconright') ? button.attr('iconright') : ''));
		var showtext = (button.attr('showlabel') && (button.attr('showlabel') == "1" || button.attr('showlabel') == "true") ? true  : false);
		var title = (button.attr('btnlabel') ? button.attr('btnlabel') : (button.attr('title') ? button.attr('title') : ''));

		dxl += '<a href="#" onclick="' + onclickcode + '" ';
		dxl += ' id="'+(button.attr('id') ? button.attr('id') : '')+ '" ';
		dxl += ' class="ui-btn ui-btn-b ui-btn-inline ui-shadow ui-corner-all ui-mini" ';
		dxl += ' title="' + title + '" ';
		dxl += '>';
		if(iconprim != ""){
			dxl += '<i class="' + iconprim + '"></i>';
			dxl += (showtext == "1" ? '&nbsp;' : '');			
		}
		if(showtext == "1"){
			dxl += '<span>'+ title +'</span>';			
		}
		if(iconsec != ""){
			dxl += (showtext == "1" ? '&nbsp;' : '');						
			dxl += '<i class="' + iconsec + '"></i>';
		}		
		dxl += "</a>\n\r\t";

		if ( hide != "")
			dxl += '{% endif %}\r'
	});
	
	if(dxl != ""){
		dxl = '{{ f_SetIsMobile(true) }}\r' + dxl;		
	}
	
	return dxl;

}


function initializeHtmlElements(targetelement, skipcontentloading){
	var targetelem = (typeof targetelement == "undefined" || targetelement === null ? document : targetelement)
	
	//--initialize any html editor elements after load
	jQuery(targetelem).find("div[elem=htmlcode]").addBack("div[elem=htmlcode]").each(function(){
			var encodedhtml = "";
			if(!(typeof skipcontentloading != "undefined" && skipcontentloading === true)){
				encodedhtml = $(this).text();
				$(this).html("");					
			}
			
			//Initialize ACE editor
			var htmleditor = ace.edit($(this).attr("id"));
			htmleditor.setTheme("ace/theme/chrome");
			htmleditor.getSession().setMode("ace/mode/html");
			htmleditor.setOption("maxLines", 66);
			htmleditor.setOption("showPrintMargin", false)
			htmleditor.renderer.setShowGutter(false);
			htmleditor.setHighlightActiveLine(false);			
			if(!$.trim(encodedhtml) == ""){
				var decodedhtml = encodedhtml;
				try{
					decodedhtml = decodeURIComponent(atob(encodedhtml));
				}catch(e){}
				htmleditor.setValue(decodedhtml, -1);
			}		
			htmleditor.clearSelection();

	});	
}

function LoadFormPresetStyle(stylestr){
	if ( stylestr == "System")
		return;

	var stylejson = {};

	
	 
   stylejson.FormUIStyle = stylestr;
   stylejson.ButtonHoverFontSize = "12" ;
   stylejson.ButtonHoverFontStyle ="normal" ;
   stylejson.ButtonFontSize = "12"  ;
   stylejson.ButtonFontStyle= "normal" ;
   stylejson.TTActiveFontSize= "12";
   stylejson.TTActiveFontStyle= "normal" ;
   stylejson.TTInActiveFontSize= "12" ;
   stylejson.TTInActiveFontStyle= "normal" ;


   stylejson.SectionFontSize= "12" ;
   stylejson.SectionFontStyle= "normal" ;

	if ( stylestr == "Smokey"){
		stylejson.ButtonBackground = "#818181";
		stylejson.ButtonFontColor =  "#ffffff";
		stylejson.ButtonHover = "#e6e6e6";
		stylejson.ButtonHoverFontColor = "#000000";

		stylejson.TTHeaderBackground = "#818181";
		stylejson.TTActiveBackground = "#aeb5b0";
		stylejson.TTActiveFontColor = "#ffffff";

		stylejson.TTInActiveFontColor = "#ffffff";
		stylejson.TTInActiveBackground = "#585d5a";
		
		stylejson.SectionBackground = "#818181";
		stylejson.SectionFontColor= "#ffffff";
	}else if ( stylestr == "Rhino"){
		stylejson.ButtonBackground = "#eaeaec";
		stylejson.ButtonFontColor =  "#4b5cb7";
		stylejson.ButtonHover = "#818181";
		stylejson.ButtonHoverFontColor = "#ffffff";

		stylejson.TTHeaderBackground = "#eaeaec";
		stylejson.TTActiveBackground = "#aeb5b0";
		stylejson.TTActiveFontColor = "#4b5cb7";

		stylejson.TTInActiveFontColor = "#ffffff";
		stylejson.TTInActiveBackground = "#585d5a";
		
		stylejson.SectionBackground = "#eaeaec";
		stylejson.SectionFontColor= "#4b5cb7";
	}else if ( stylestr == "Airline"){

		stylejson.ButtonBackground = "#547092";
		stylejson.ButtonFontColor =  "#ffffff";
		stylejson.ButtonHover = "#bbd2ef";
		stylejson.ButtonHoverFontColor = "#000000";

		stylejson.TTHeaderBackground = "#547092";
		stylejson.TTActiveBackground = "#c5c8d8";
		stylejson.TTActiveFontColor = "#ffffff";

		stylejson.TTInActiveFontColor = "#ffffff";
		stylejson.TTInActiveBackground = "#405a6f";
		
		stylejson.SectionBackground = "#547092";
		stylejson.SectionFontColor= "#ffffff";

	}else if ( stylestr == "Dusk"){

		stylejson.ButtonBackground = "#171d31";
		stylejson.ButtonFontColor =  "#ffffff";
		stylejson.ButtonHover = "#a5aab7";
		stylejson.ButtonHoverFontColor = "#ffffff";

		stylejson.TTHeaderBackground = "#171d31";
		stylejson.TTActiveBackground = "#4d5469";
		stylejson.TTActiveFontColor = "#ffffff";

		stylejson.TTInActiveFontColor = "#ffffff";
		stylejson.TTInActiveBackground = "#575a61";
		
		stylejson.SectionBackground = "#171d31";
		stylejson.SectionFontColor= "#ffffff";
		
  		stylejson.ButtonFontStyle= "italic" ;
  		stylejson.TTActiveFontStyle= "italic" ;
  		stylejson.ButtonHoverFontStyle ="italic" ;
  		stylejson.SectionFontStyle= "italic" ;
  		 stylejson.TTInActiveFontStyle= "italic" ;
	}else if ( stylestr == "Onyx"){

		stylejson.ButtonBackground = "#28313e";
		stylejson.ButtonFontColor =  "#ffffff";
		stylejson.ButtonHover = "#a5aab7";
		stylejson.ButtonHoverFontColor = "#ffffff";

		stylejson.TTHeaderBackground = "#28313e";
		stylejson.TTActiveBackground = "#4d5469";
		stylejson.TTActiveFontColor = "#ffffff";

		stylejson.TTInActiveFontColor = "#ffffff";
		stylejson.TTInActiveBackground = "#575a61";
		
		stylejson.SectionBackground = "#28313e";
		stylejson.SectionFontColor= "#ffffff";
		
  		
	}else if ( stylestr == "Whitewash"){

		stylejson.ButtonBackground = "#ffffff";
		stylejson.ButtonFontColor =  "#1a3ea7";
		stylejson.ButtonHover = "#eaeaec";
		stylejson.ButtonHoverFontColor = "#1a3ea7";

		stylejson.TTHeaderBackground = "#ffffff";
		stylejson.TTActiveBackground = "#eaeaec";
		stylejson.TTActiveFontColor = "#1a3ea7";

		stylejson.TTInActiveFontColor = "#1a3ea7";
		stylejson.TTInActiveBackground = "#ffffff";
		
		stylejson.SectionBackground = "#ffffff";
		stylejson.SectionFontColor= "#1a3ea7";
		
  		
	}

	LoadCSSFromJSON(stylejson);
}

function InitHelperColors(){

	$(".inputEntryColor").each(function(){
		if ( is_explorer || is_safari)
			return;
		
		var helper = $(this).prev("input");
		if ( helper.length > 0){
			 helper.width("70px");
			 helper.val($(this).val());

			
			 $(this).width("20px")
		}
	});
}



function LoadCSSFromJSON(stylejson){


	if ( $("#FormBackgroundColor").length > 0 ){
		if ( stylejson.FormBackgroundColor)  $("#FormBackgroundColor").val(stylejson.FormBackgroundColor);
	}else{
		if ( stylejson.FormBackgroundColor)  $("#PageBackgroundColor").val(stylejson.FormBackgroundColor);
	}
	if ( stylejson.PanelsHorizontalSpacing)  $("#PanelsHorizontalSpacing").val(stylejson.PanelsHorizontalSpacing);
	if ( stylejson.PanelsVerticalSpacing)  $("#PanelsVerticalSpacing").val(stylejson.PanelsVerticalSpacing);
	if ( stylejson.PanelsPadding)  $("#PanelsPadding").val(stylejson.PanelsPadding);
	if ( stylejson.PanelsBackgroundColor)  $("#PanelsBackgroundColor").val(stylejson.PanelsBackgroundColor);
	if ( stylejson.PanelsShadow && stylejson.PanelsShadow=="0")  $("#PanelsShadow").prop( "checked", false );
	if ( stylejson.PanelsBorderStyle)  $("#PanelsBorderStyle").val(stylejson.PanelsBorderStyle);
	if ( stylejson.PanelsBorderColor)  $("#PanelsBorderColor").val(stylejson.PanelsBorderColor);
	if ( stylejson.PanelsBorderSize)  $("#PanelsBorderSize").val(stylejson.PanelsBorderSize);

	

	if ( stylejson.FormUIStyle ) $("#FormUIStyle").val(stylejson.FormUIStyle);
	if ( stylejson.ButtonBackground )$("#ButtonBackground").val(stylejson.ButtonBackground);
	if ( stylejson.ButtonHover )  $("#ButtonHover").val(stylejson.ButtonHover);
	if ( stylejson.ButtonHoverFontColor )  $("#ButtonHoverFontColor").val(stylejson.ButtonHoverFontColor) ;
	if ( stylejson.ButtonHoverFontSize )$("#ButtonHoverFontSize").val(stylejson.ButtonHoverFontSize)  ;
	if ( stylejson.ButtonHoverFontStyle )  $("#ButtonHoverFontStyle").val(stylejson.ButtonHoverFontStyle) ;

	 if ( stylejson.ButtonFontColor )  $("#ButtonFontColor").val(stylejson.ButtonFontColor) ;
	 if ( stylejson.ButtonFontSize ) $("#ButtonFontSize").val(stylejson.ButtonFontSize)  ;
	 if ( stylejson.ButtonFontStyle )  $("#ButtonFontStyle").val(stylejson.ButtonFontStyle) ;

	 if ( stylejson.TTHeaderBackground )  $("#TTHeaderBackground").val(stylejson.TTHeaderBackground);
	 if ( stylejson.TTActiveBackground )  $("#TTActiveBackground").val(stylejson.TTActiveBackground) ;
	 if ( stylejson.TTActiveFontColor ) $("#TTActiveFontColor").val(stylejson.TTActiveFontColor) ;
	 if ( stylejson.TTActiveFontSize )  $("#TTActiveFontSize").val(stylejson.TTActiveFontSize);
	 if ( stylejson.TTActiveFontStyle )  $("#TTActiveFontStyle").val(stylejson.TTActiveFontStyle) ;

	 if ( stylejson.TTInActiveBackground )  $("#TTInActiveBackground").val(stylejson.TTInActiveBackground) ;
	 if ( stylejson.TTInActiveFontColor )  $("#TTInActiveFontColor").val(stylejson.TTInActiveFontColor) ;
	 if ( stylejson.TTInActiveFontSize )  $("#TTInActiveFontSize").val(stylejson.TTInActiveFontSize) ;
	 if ( stylejson.TTInActiveFontStyle )  $("#TTInActiveFontStyle").val(stylejson.TTInActiveFontStyle) ;

	 if ( stylejson.SectionBackground )  $("#SectionBackground").val(stylejson.SectionBackground) ;
	 if ( stylejson.SectionFontColor )  $("#SectionFontColor").val(stylejson.SectionFontColor) ;
	 if ( stylejson.SectionFontSize )  $("#SectionFontSize").val(stylejson.SectionFontSize) ;
	 if ( stylejson.SectionFontStyle )  $("#SectionFontStyle").val(stylejson.SectionFontStyle) ;

	  //also set the helper text to value of the color selected
	 InitHelperColors();
	
}

function addcss(css){
    var s = $("#curcss");
    if ( s.length > 0)
    	s.get(0).parentNode.removeChild(s.get(0));
  
    var head = document.getElementsByTagName('head')[0];
	s = document.createElement('style');
  	s.setAttribute('type', 'text/css');
	s.setAttribute('id', "curcss")
	head.appendChild(s);
  	if (s.styleSheet) {   // IE
		 s.styleSheet.cssText = css;
	} else {                // the world
		s.appendChild(document.createTextNode(css));
	}
   
   
   
   
 }

 function getPanelsCSS (stylejson, forui)
 {
 	var csstxt = "";
 	var isui = forui? forui : false;

 	csstxt += "#layout, #inner-center, #divDocPage { background : " + stylejson.FormBackgroundColor + "; }\r\n";

	csstxt += ".grid-stack > .grid-stack-item > .grid-stack-item-content  { \r\n";
	csstxt += 		"\tbackground-color : " + stylejson.PanelsBackgroundColor + " ;\r\n";
	if ( stylejson.PanelsShadow == "1"){
		csstxt += "\tbox-shadow: 0 0 35px 0 rgba(154,161,171,.15); \r\n";
	}else{
		csstxt += "\tbox-shadow: none; \r\n";
	}

	if ( forui && stylejson.PanelsBorderStyle == "none" ){
		csstxt += 		"\tborder:1px dotted darkgray;\r\n";
	
	}else{
		csstxt += 		"\tborder:" + stylejson.PanelsBorderSize + "px " + stylejson.PanelsBorderStyle + " " + stylejson.PanelsBorderColor + ";\r\n";
	
	}
	csstxt += 		"\tleft:" + stylejson.PanelsVerticalSpacing + "px !important;\r\n";
	csstxt += 		"\tright:" + stylejson.PanelsVerticalSpacing + "px !important;\r\n";
	if ( !isui ){
		csstxt += 		"\tpadding:" + stylejson.PanelsPadding + "px !important;\r\n";
	}
	csstxt += "}\r\n\r\n";

	//store the horizontal attributes into the grid-stack div..it will be used by the appFormBuilder.js 

	$(".grid-stack").attr("horizontalSpacing", stylejson.PanelsHorizontalSpacing  );

	if ( isui ){
		var grid = $('.grid-stack').data('gridstack');
		grid.verticalMargin (stylejson.PanelsHorizontalSpacing);
	}

	return csstxt;
 }


function getPanelsJSON()
{
	var stylejson = {};
	var bgobj ;
	if ( $("#FormBackgroundColor").length > 0 ){
		bgobj  = $("#FormBackgroundColor")
	}else{
		bgobj = $("#PageBackgroundColor")
	}

	stylejson.FormBackgroundColor = bgobj.val();
	
	
	stylejson.PanelsHorizontalSpacing = $("#PanelsHorizontalSpacing").val();
	stylejson.PanelsVerticalSpacing = $("#PanelsVerticalSpacing").val();
	stylejson.PanelsPadding = $("#PanelsPadding").val();
	stylejson.PanelsBackgroundColor = $("#PanelsBackgroundColor").val();
	stylejson.PanelsShadow = $("#PanelsShadow").is(":checked") ? "1" : "0";
	stylejson.PanelsBorderStyle = $("#PanelsBorderStyle").val();
	stylejson.PanelsBorderColor = $("#PanelsBorderColor").val();
	stylejson.PanelsBorderSize = $("#PanelsBorderSize").val();

	return stylejson;

	
}

function getFormCSS(){


	var stylejson = getPanelsJSON();
	var csstxt = "";

	csstxt = getPanelsCSS(stylejson);

	var uistyle = $("#FormUIStyle").val();
	if ( uistyle && uistyle != "System")
	{
		
		stylejson.FormUIStyle = 	uistyle;
		stylejson.ButtonBackground = $("#ButtonBackground").val();
		stylejson.ButtonHover =  $("#ButtonHover").val();
		stylejson.ButtonHoverFontColor = $("#ButtonHoverFontColor").val() ;
		stylejson.ButtonHoverFontSize = $("#ButtonHoverFontSize").val()  ;
		stylejson.ButtonHoverFontStyle = $("#ButtonHoverFontStyle").val() ;

		stylejson.ButtonFontColor = $("#ButtonFontColor").val() ;
		stylejson.ButtonFontSize = $("#ButtonFontSize").val()  ;
		stylejson.ButtonFontStyle = $("#ButtonFontStyle").val() ;

		stylejson.TTHeaderBackground =  $("#TTHeaderBackground").val();
		stylejson.TTActiveBackground = $("#TTActiveBackground").val() ;
		stylejson.TTActiveFontColor = $("#TTActiveFontColor").val() ;
		stylejson.TTActiveFontSize= $("#TTActiveFontSize").val();
		stylejson.TTActiveFontStyle = $("#TTActiveFontStyle").val() ;
		stylejson.TTInActiveBackground = $("#TTInActiveBackground").val() ;
		stylejson.TTInActiveFontColor = $("#TTInActiveFontColor").val() ;
		stylejson.TTInActiveFontSize = $("#TTInActiveFontSize").val() ;
		
		stylejson.TTInActiveFontStyle = $("#TTInActiveFontStyle").val() ;
		
		stylejson.SectionBackground = $("#SectionBackground").val() ;
		stylejson.SectionFontColor = $("#SectionFontColor").val() ;
		stylejson.SectionFontSize = $("#SectionFontSize").val() ;
		stylejson.SectionFontStyle = $("#SectionFontStyle").val() ;

		//button 
		csstxt += ".dtoolbar { \r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.ButtonBackground + " !important;\r\n";
		csstxt += 		"\tcolor:" + stylejson.ButtonFontColor + " !important;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.ButtonFontSize + "px !important;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.ButtonFontStyle + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//button hover
		csstxt += ".dtoolbar:hover {"
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent !important;;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.ButtonHover + " !important ;\r\n";
		csstxt += 		"\tcolor:" + stylejson.ButtonHoverFontColor + " !important ;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.ButtonHoverFontSize + "px !important ;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.ButtonHoverFontStyle + " !important ;\r\n";
		csstxt += "}\r\n\r\n";

		//Tabbed Table header

		csstxt += ".dTabHeader {\r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.TTHeaderBackground + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table Active
		csstxt += ".dTabActive {\r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent !important;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.TTActiveBackground + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table Active Font 
		csstxt += ".dTabActive a {\r\n";
		csstxt += 		"\tcolor:" + stylejson.TTActiveFontColor + " !important ;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.TTActiveFontSize + "px !important;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.TTActiveFontStyle + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table In Active
		csstxt += ".dTabInactive {\r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent !important;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.TTInActiveBackground + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table In Active font 
		csstxt += ".dTabInactive a {\r\n";
		csstxt += 		"\tcolor:" + stylejson.TTInActiveFontColor + " !important;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.TTInActiveFontSize + "px !important;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.TTInActiveFontStyle + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table inactive hover:
		csstxt += ".dTabInactive:hover {\r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent !important;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.TTActiveBackground + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//tabbed table inactive hover font:
		csstxt += ".dTabInactive a:hover {\r\n";
		csstxt += 		"\tcolor:" + stylejson.TTActiveFontColor + " !important;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.TTActiveFontSize + "px !important;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.TTActiveFontStyle + " !important;\r\n";
		csstxt += "}\r\n\r\n";

		//section background/text
		//tabbed table inactive hover font:
		csstxt += ".dWidgetHeader  {\r\n";
		csstxt += 		"\tbackground: none repeat scroll 0 0 transparent !important;\r\n";
		csstxt += 		"\tbackground-color : " + stylejson.SectionBackground + " !important;\r\n";
		csstxt += 		"\tcolor:" + stylejson.SectionFontColor + " !important;\r\n";
		csstxt += 		"\tfont-size:" + stylejson.SectionFontSize + "px !important;\r\n";
		csstxt += 		"\tfont-style:" + stylejson.SectionFontStyle + " !important;\r\n";
		csstxt += "}\r\n\r\n";

	}	
	
	
	
	var jsonStr =  JSON.stringify(stylejson);

	return jsonStr + "\r\n!!----!!\r\n" + csstxt;


}

function geticons(){
  srch = $("#IconSearch").val();
  if(srch == ""){
    $("#iconpicker").html(alliconhtml);
  }else{
    iconhtml = "";
    for(var x=0; x<fa_items.length; x++){
      if(fa_items[x].indexOf(srch)>-1){
        iconhtml += "<a title='" + fa_items[x] + "' role='button' href='#' class='iconpicker-item'><i class='" + fa_items[x] + "'></i></a>";
        $("#iconpicker").html(iconhtml);
      }
    }
  }
   //set click on returned icons
  $(".iconpicker-item").click(function(){

  	if ( $(currElem).attr("elem") == "icon" )
  	{
  		var menuIconClass = $(this).prop("title");

  		var cl =  $(currElem).attr("class").split(" ");
	    var newcl =[];
	    for(var i=0;i<cl.length;i++)
	    {
	    	var classval= trim(cl[i]);
	        if (  classval != "fas" && classval != "far"  && classval != "fab" && classval.indexOf ("fa-") != 0 )
	        {
	        	newcl.push(classval);
	        }
	    }
	    newcl.push(menuIconClass)
    	$(currElem).removeClass().addClass(newcl.join(" "));
    }
  });

  return;
}

function InitFontAwesomeIcons()
{
	//---Icon Picker----
	//---Generate and store html for all icons
	for(var x=0; x<fa_items.length; x++){
		iconhtml += "<a title='" + fa_items[x] + "' role='button' href='#' class='iconpicker-item'><i class='" + fa_items[x] + "'></i></a>";
	}
	alliconhtml = iconhtml;

	//---Set delay on icon picker search field
	var delayMilliseconds = 750; // i.e. = 1 second
	var timeoutId;
	$("#IconSearch").on('keyup', function () {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        timeoutId = setTimeout(geticons, delayMilliseconds);
	});
	
	//Initialize icons
	geticons();

}

function resetPicklistForm(obj)
{
	$('#fieldelement_container').empty();
	$('#sourceelement_container').empty();
	//$('#selectPicklistForm').val('');
	$('TR[class^="picklistAction"]').hide();
	if (obj.val() == 'A') {
		$('#picklistFrmFields').hide();
		if (!$('#sourceelement_container input.sourceelements').length) {
			$('#sourceelement_container').append('<li><i class="fal fa-minus-circle omitfield"></i><input type="text" value="" class="sourceelemnts inputEntry" style="width:86%;" /></li><li> = <input type="text" value="" class="targetvalue inputEntry"/></li>');
		}
	}
	$('TR.picklistAction' + obj.val()).show();
}
function resetFormElementsList(obj)
{
	var app = new DocovaApplication({
		"appid": docInfo.AppID
	});
	var result = app.getFormFields(obj.val());
	if (result) {
		$('#picklistFrmFields').show();
		$('#fieldelement_container').html('');
		var field_elms = result.split(',');
		var html = '';
		for (var x = 0; x < field_elms.length; x++) {
			if (field_elms[x] != 'txt_parentdockey') {
				html += '<li><i class="fal fa-minus-circle omitfield"></i><span>'+ field_elms[x] +'</span></li>';
				html += '<li> = <input placeholder="Source field name" id="'+ field_elms[x] +'" class="allelements inputEntry" type="text" value="" /></li>';
			}
		}
		$('#fieldelement_container').html(html);
	}
	else {
		$('#picklistFrmFields').hide();
	}
}