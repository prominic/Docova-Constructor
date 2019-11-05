$(document).ready(function(){
	$( ".btn-AddWidget" ).button({
		text:false,
		label: "Change widget.",
		icons: { primary : "ui-icon-plus" }
	}).click(function( event ) {
		event.preventDefault();
		SelectWidget(this);//Calls the Change Widget dialog
	});

	$( ".btn-RemoveWidget" ).button({
		text:false,
		label: "Remove widget.",
		icons: { primary : "ui-icon-minus" }
	}).click(function( event ) {
		event.preventDefault();
		dlgRemoveWidget(this);//Calls the Remove Widget dialog
	});

	//Show tblContent after everything is loaded
	$("#tblContent").show();
});

function GetLibraryPath(libKey)
{
	var libListXml = AvailableLibraries.XMLDocument;
	var libNode = libListXml.selectSingleNode('Libraries/Library[DocKey="' + libKey + '"]');
	
	if(libNode==null) {return false;}
	var libPathNode =libNode.selectSingleNode("NsfName");
	if(libPathNode==null) {return false;}
	
	return libPathNode.textContent || libPathNode.text;
}

function OpenDocument(entryObj, docToOpen) {
	var libID = entryObj.GetElementValue("librarykey");
	var libPath = "/" + docInfo.NsfName + "/";
	var folderID = entryObj.GetElementValue("folderid");
	var docKey = entryObj.GetElementValue("parentdocid");
	var docTitle = entryObj.GetElementValue("title");
	var rectype = entryObj.GetElementValue("rectype");
	var isappForm = entryObj.GetElementValue("isappform")
	rectype = (typeof rectype == 'undefined') ? "doc" : rectype;
	var isApp = isappForm == "1" ? true : false;
	
	if ( isApp ){
		var app = new DocovaApplication( { appid : libID });
		app.launchApplication();
		app.openDialog({
			title : "Document in application : " + app.appName,
			height : 500,
			width : 800,
			docid: docKey,
			resizable : true,
			useiframe : true})
		return;
	}

	if(!libPath)
	{
		alert("Could not locate the source library for this document.");
		return; 
	}
	
	if(docToOpen == "Parent" || docToOpen == "" || docToOpen == undefined) {
		var docKey = entryObj.GetElementValue("parentdocid");
	} else {
		var docKey = entryObj.GetElementValue("docid");
	}
	var ws = Docova.getUIWorkspace(document);
	var fraToolbarWin = ws.getDocovaFrame("fraToolbar", "window");
	fraToolbarWin.OpenLibraries(function(){
		var fraFolderWin = ws.getDocovaFrame("foldercontrol", "window");
		var folderControl = fraFolderWin.DLITFolderView;
		if(rectype == "doc" && folderControl){
			folderControl.OpenFolder(folderID, false, libID, function(result){
//				if(result){
					var newDocUrl = libPath + "ReadDocument/" + docKey + "?OpenDocument&ParentUNID="  + folderID;
					var fraTabbedTableWin = ws.getDocovaFrame("fraTabbedTable", "window");
					fraTabbedTableWin.objTabBar.CreateTab(docTitle, docKey, "D", newDocUrl);
//				}else{
//					alert("The selected document folder could not be found.");
//				}
			});
		}
		if(rectype == "fld" && folderControl){
			folderControl.OpenFolder(folderID, false, libID, function(result){
				if(!result){
					alert("The selected folder could not be found.");
				}
			});
		}
	});
}

function SelectWidget(Obj){
	//---Check access control to edit panel widgets---
	if(docInfo.PanelEditors != docInfo.UserNameAB){
		var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Sorry, You do not have access to change the widgets\n\r on this panel.';
		dlgHTML += 'Contact the owner (' + docInfo.PanelEditors + ') to request changes.</p>';
		var dlgChangeWidgetDenied = window.top.Docova.Utils.createDialog({
			id: "divDlgChangeWidgetDenied",
			dlghtml: dlgHTML,
			title: "Change widget denied",
			height: 200,
			width: 500,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
				"OK": function(){
					dlgChangeWidgetDenied.closeDialog();
				}
			}
		})
		return false;
	}
	
	//Get widget box
	var widgetObj = $(Obj).parentsUntil('td').parent();

	BoxNumber = widgetObj.attr('boxnum');
	var dlgSelectWidget = window.top.Docova.Utils.createDialog({
		id: "divDlgSelectWidget",
		url: "_dlgSelectWidget?OpenPage",
		title: "Select a Widget",
		height: 260,
		width: 500,
		useiframe: false,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Use Widget": function() {
				var parentWin = window.top.Docova.GlobalStorage["divDlgSelectWidget"].sourcewindow;
				parentWin.ChangeWidget();
				dlgSelectWidget.closeDialog();
			},
			"Cancel": function() {
				dlgSelectWidget.closeDialog();
			}
		}
	});
	return;
}

function ChangeWidget(){
	var WidgetID = window.top.Docova.Utils.getField("WidgetList") //get from dialog in window.top
	var PanelLayout = Docova.Utils.getField("panellayout")
	var uname = docInfo.UserNameAB;
	var agentName = "DashboardServices";
	
	var request = "<Request><Action>CHANGEWIDGET</Action>";
	request += "<Document>";
	request += "<Username><![CDATA[" + uname + "]]></Username>";
	request += "<WidgetID><![CDATA[" + WidgetID + "]]></WidgetID>";
	request += "<PanelKey><![CDATA[" + docInfo.PanelKey + "]]></PanelKey>";
	request += "<BoxNumber>" + BoxNumber + "</BoxNumber>";
	request += "</Document>";
	request += "</Request>";

	var result = SubmitRequest(request, agentName);

	if (result == "NOTOK"){ //If widget is already on this panel, notify user to select a different widget.
		var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>The selected widget is already on this panel.  Please select a different widget.</p>';
		var dlgDuplicateWidget = window.top.Docova.Utils.createDialog({
			id: "divDlgDuplicateWidget",
			dlghtml: dlgHTML,
			title: "Duplicate Widget",
			height: 200,
			width: 500,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
				"OK": function() {
					dlgDuplicateWidget.closeDialog();
				}
			}
		});
	}else{
		document.location.reload();
	}
}

function dlgRemoveWidget(Obj){
	//---Check access control to remove panel widgets---
	if(docInfo.PanelEditors != docInfo.UserNameAB){
		var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>Sorry, You do not have access to remove the widgets\n\r on this panel.';
		dlgHTML += 'Contact the owner (' + docInfo.PanelEditors + ') to request changes.</p>';
		var dlgNoRemoveWidget = window.top.Docova.Utils.createDialog({
			id: "divDlgNoRemoveWidget",
			dlghtml: dlgHTML,
			title: "Remove widget denied.",
			height: 200,
			width: 500,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
				"OK": function() {
					dlgNoRemoveWidget.closeDialog();
				}
			}
		});
		return false;
	}

	var dlgHTML = '<p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You are about to remove a widget from this panel.  Are you sure?</p>';
	var dlgRemoveWidget = window.top.Docova.Utils.createDialog({
		id: "divDlgRemoveWidget",
		dlghtml: dlgHTML,
		title: "Remove widget?",
		height: 200,
		width: 500,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Remove": function() {
				RemoveWidget(Obj);
				dlgRemoveWidget.closeDialog();
			},
			"Cancel": function() {
				dlgRemoveWidget.closeDialog();
			}
		}
	});
}

function RemoveWidget(Obj){
	var uname = docInfo.UserNameAB;
	var agentName = "DashboardServices";
	
	var widgetObj = $(Obj).parentsUntil('td').parent();
	BoxNumber = widgetObj.attr('boxnum');
	
	var request = "<Request><Action>REMOVEWIDGET</Action>";
	request += "<Document>";
	request += "<Username><![CDATA[" + uname + "]]></Username>";
	request += "<PanelKey><![CDATA[" + docInfo.PanelKey + "]]></PanelKey>";
	request += "<BoxNumber>" + BoxNumber + "</BoxNumber>";
	request += "</Document>";
	request += "</Request>";

	var result = SubmitRequest(request, agentName);
	document.location.reload();
}

function GetWidgetDescription(Obj){
	var selectedWidgetID = window.top.Docova.Utils.getField("WidgetList"),
		opts = {
			view: 'luWidgetsByKey',
			key: selectedWidgetID,
			columns: ['WidgetDescription']
		};

	var value = lookupNode(opts);
	if (value !== false)
		window.top.document.getElementById("WidgetDescription").value = value['WidgetDescription'];
}

/**
 * NOT SURE IF I SHOULD USE THIS FUNCTION OR THE ONE IN wDocovaWorkspace.js!
 * 
function SubmitRequest(request, agentName){
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + agentName  + "?OpenAgent";
//	Docova.Utils.showProgressMessage("One moment....");
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){
//		Docova.Utils.hideProgressMessage();
		return false;
	}
//	Docova.Utils.hideProgressMessage();
	return (httpObj.results.length)? httpObj.results[0] : httpObj.status;
}
*/

/**
 * Simulated DBLookup for libraries
 */
function lookupNode(opts)
{
	var uri = docInfo.ServerUrl + docInfo.PortalWebPath + '/' + opts.view;
	var output = [];
	$.ajax({
		url : uri + '?ReadViewEntries&Count=1&StartKey=' + encodeURIComponent(opts.key),
		type: 'GET',
		async: false,
		dataType : 'xml',
		success: function(response) {
			for (var x = 0; x < opts.columns.length; x++ ) {
				if (isNaN(opts.columns[x])) {
					var value = $(response).find('entrydata[name='+ opts.columns[x] +']').text();
				}
				else {
					var value = $(response).find('entrydata[columnnumber='+ opts.columns[x] +']').text();
				}
				output[opts.columns[x]] = value;
			}
		},
		error: function() {
			alert("An error occurred retrieving data from the server.");
			output = false;
		}
	});

	return output
}
