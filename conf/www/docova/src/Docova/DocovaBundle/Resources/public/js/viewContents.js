var SUBJECTNODE = "F8"; //to pass doc subject to tab function
var objView = null; //view object
var curColumnObj = null; //view column object
var currentPerspective=""; //current perspective id
var isiPad = navigator.userAgent.match(/iPad/i) != null; //Detects if User is using an iPad.  Used to handle things like double click.

var dlgParams = new Array();  //params array that gets used by dialogs
var retValues = new Array(); //ret params array that can be used by dialogs
var shiftkeypressed = false;
var ctrlkeypressed = false;
var DLExtensions = null;

//Column actions, set as global to pass to Docova.menu function
var colAscSortAction="";
var colDescSortAction="";
var colDefaultSortAction="";
var colCategorizeAction="";
var colFreezeAction="";
var colDeleteAction="";
var colInsertAction="";
var colAppendAction="";
var colPropertiesAction="";

//Content paging related variables
var typingTimer;                //timer identifier 
var total = 0;
var count = 0;
var start = 1;
var currCnt = null;
var origCount = 0;	
var buttonsDisabled = null;

//View event handler variables
var dragSourceColumn; //column heading being dragged into new location
var dragTargetColumn; //target column heading for dragover and drop 
var selectDragMode; //specifies how the drag selection over checkboxes should be handled
var viewContainer ; //object where the view contents will be plugged
var disableOpenDocInEditMode = false;
//---------------------------------------------------------------------
function getinfovar(){
   return info;
}

$(document).ready(function(){
	DLExtensions = window.top.DocovaExtensions;
	var objContent = $("#divViewContent");
	objContent.disableSelection();
	objContent.on('dragover', function (e) {
    	e.stopPropagation();
   		e.preventDefault();
    	$( this).css("border", "2px dashed gray");
	});
	objContent.on('dragleave', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$( this).css("border", "0px");
	});
	
	objContent.on('drop', function (e) {
		e.stopPropagation();
		e.preventDefault();
		if(docInfo.FolderID){
			$(this).css("border", "0px dashed gray");
			doDrop(e);
		}
	});

	if(!info.FolderID){		
		//if no buttons in the action pane, then hide the header
		if(info.HideActions || $("#tdActionBar > button, #tdActionBar > a").length == 0  || docInfo.HideActions == "true"){
			$("#actionPaneHeader").hide();
			$("#divViewContent").css("top", "0px");
			$("#divToolbarSearch").css("top", "0px");
			$("#divContentPaging").css("top", "0px");
		}
	}
	else {
		$('#tdActionBar a').each(function(index,element) {
	   		$(element).button({
				text: $.trim($(this).text()) ? true : false,
				label: $.trim($(this).text()),
				icons: {
			 		primary: ($.trim($(this).attr('primary'))) ? $(this).attr('Primary') : null,
					secondary: ($.trim($(this).attr('secondary'))) ? $(this).attr('secondary') : null
				}
			});
		});
		$('#divHeaderSection').css('visibility', 'visible');
	}

	$("#btnFTSearch").button({
		text: false,
		icons: { primary: "ui-icon-search"}
	}).click(function(event){
		ViewFTSearch();
	})

	$("#btnAdvancedSearch").button({
		text: false,
		icons: { primary: "ui-icon-zoomin"}
	}).click(function(event){
		AdvancedSearch();
	})
	
	$("#btnFTClear").button({
		text: false,
		disabled: true,
		icons: { primary: "ui-icon-arrowrefresh-1-e"}
	}).click(function(event){
		ViewFTClear();
	})
	
	$("#btnContPage1").button({
		text: true,
		icons: { primary: "ui-icon-seek-first"}
	}).click(function(event){
		first();
	});	
	
	$("#btnContPage2").button({
		text: true,
		icons: { primary: "ui-icon-seek-prev"}
	}).click(function(event){
		previous();
	});
	
	$("#btnContPage3").button({
		text: true,	
		
		icons: { primary: "ui-icon-seek-next"}
	}).click(function(event){
		next();
	});	
	
	$("#btnContPage4").button({
		text: true,	
		icons: { primary: "ui-icon-seek-end"}
	}).click(function(event){
		last();
	});		
	
	$("#btnDelete").button({
		text: false,
		disabled: true,
		icons: { primary: "ui-icon-trash"}
	}).click(function(event){
		DeleteSearch();
	});
	
	$("#btnClearAllFilters").button({
		text: false,
		icons: { primary: "ui-icon-arrowrefresh-1-e"}
	}).click(function(event){
		ClearAllFilters();
	});

	$("#btnResetFolderFilter").button({
		text: false,
		icons: { primary: "ui-icon-refresh"}
	}).click(function(event){
		ResetFolderFilter();
	});		
	
	$("#inpSwitchPerspective").multiselect({
		multiple: false,
		header: false,		
		noneSelectedText: "Perspective",
		selectedList: 1,
		height: "auto"
	});		

	$("#selVersionScope").multiselect({
		multiple: false,
		header: false,		
		selectedList: 1,
		height: "auto",
		minWidth: 140
	});		
	
	$('input:text')
 		.button()
		.css({
			'font' : 'inherit',
			'color' : 'inherit',
			'text-align' : 'left',
			'outline' : 'none',
			'cursor' : 'text',
			'background' : '#ffffff',
			'padding' : '.35em'		
	}).off('keydown');
	
	$('#inpQuery').hover( function() {$('#inpQuery').css("background", "#ffffff").removeAttr("placeholder");});
	$('#inpQuery').keypress(function(e) {
	    if(e.which == 13) {
    		ViewFTSearch();
		}
	});
	
	$("#MySavedSearches").multiselect({
		multiple: false,
		header: false,		
		noneSelectedText: "My Saved Searches",
		selectedList: 1,
		height: "auto",
		minWidth: 150,
		showSelected: false
	});	
	$("#MySavedSearches").multiselect("clearSingle");
    
	$("button").not("#MySavedSearchesbtn, #inpSwitchPerspectivebtn, #selVersionScopebtn, #btnContentPage1, #btnContentPage2, #btnContentPage3, #btnContentPage4").button().click(function( event ) {	
		event.preventDefault();
	});
	
	$('#GetPage').keypress(function(event) {
		if (event.keyCode == 13) {
			event.preventDefault();
		}
	});
	
	if(docInfo.UseContentPaging == "1") { $("#divContentPaging").css("display", "block"); }

	$('#divViewContent').scroll(moveScroll);
	
	if ($("#tdActionBar").children().length == 0 )
	{
		$("#divToolbarSearch").css("top", "0px");
	}else{
		//handle toolbar buttons
		$("#tdActionBar").find("button").each(function() 
		{ 
			var icnrght = $(this).attr("iconright");
			var icnleft= $(this).attr("iconleft");
			var title = $(this).attr("title");
			var showText = ($(this).attr("btntext")== "0") ? false : true ;
		
			$(this).button({
				text: showText,
				label: title,	
				icons: { primary: icnleft, secondary: icnrght}
			});
		});
	}
	
	

	if(!info.FolderID){
		//show/reveal view after all elements are loaded
		$("#viewMainContainer").css("display", "");
	}
	
	InitPage();
});   

function mayUserEditDoc(docID) {
	//supports folder context menu option 'Edit' and 'Edit in New Window'
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
	var request="";
	request += "<Request>";
	request += "<Action>MAYUSEREDIT</Action>";
	request += "<Unid>" + docID + "</Unid>";
	request += "</Request>";	

	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, url) || httpObj.status=="FAILED") {return false; }

	return httpObj.results[0];
}

//=================== Content Paging ==========================
var typingTimer;                //timer identifier 
 
//on keyup, start the countdown 
function triggerGetPage() {
	clearTimeout(typingTimer);	
	typingTimer = setTimeout(getPage, 1000);  
}  

function selText(obj){
	obj.select();
}

function getPage() {

	if(document.getElementById("GetPage").value == "") {return}

	objView.docSubject = document.getElementById("GetPage").value;
	objView.exactMatch = false;
	if(docInfo.EnableFolderFiltering == "1"){
		$("#CurrentFilterDiv").html("");
		ClearAllColFilterFlags();
	}
	objView.getTotal = false;
	objView.Refresh(true,true,false);
	setResizeHandlers()
}

function disableContentPaging(disabled){
	
	//this function is called by viewObjects prior to the document.ready firing
	//button definitions have to be included here.
	$("#btnContPage1").prop("disabled", disabled );
	$("#btnContPage2").prop("disabled", disabled );
	$("#btnContPage3").prop("disabled", disabled );
	$("#btnContPage4").prop("disabled", disabled );	
	if(disabled) {
		$("#currPic").css("background-color", "#DFDFDF");
		$("#currCount").css("background-color", "#DFDFDF");		
		$("#GetPage").css("background-color", "#DFDFDF");				
		$("#GetPage").attr("disabled", "disabled");		
	} else {
		$("#currPic").css("background-color", "white");
		$("#currCount").css("background-color", "white");	
		$("#GetPage").css("background-color", "white");	
		$("#GetPage").removeAttr("disabled");	
	}
}

function initContentPagingVars() {
	if($("#divContentPaging").css("display") == "none") {return;}
	
	total = parseInt($("#totalCount").html());
	count = parseInt(objView.docCount);
	start = document.getElementById("startCount");
	currCnt = document.getElementById("currCount");
	if(origCount == 0) { origCount = parseInt(objView.docCount);}

	document.getElementById("GetPage").value = "";
	if(docInfo.EnableFolderFiltering == "1"){
		$("#CurrentFilterDiv").html("");
		ClearAllColFilterFlags();
	}
	objView.getTotal = false;
}

function first(){
	initContentPagingVars();
	if(objView.startCount == 1) {return;}
	objView.startCount = 1;
	$('#prevoffset').html('');
	$(start).html(1);
	$(currCnt).html(objView.docCount);
	objView.Refresh(true, true, false);
	setResizeHandlers()
	document.getElementById("GetPage").value = "";
}

function last(){
	initContentPagingVars();
	if(parseInt($(currCnt).html()) == total) {return;}
	var newStart = total - count;
	objView.startCount = newStart;
	$(start).html(newStart);
	objView.Refresh(true, true, false);
	setResizeHandlers()
	$(currCnt).html(total);
}

function next(){
	
	initContentPagingVars();
	if ($.trim($('#actualoffset').html()) != '') {
		var temp = $('#actualoffset').html().split(',');
		var last = temp.pop();
		if (parseInt(last) + parseInt(objView.docCount) < total) {
			$('#prevoffset').append(','+$('#actualoffset').html());
		}
		var newStart = parseInt($('#actualoffset').html());
	}
	else {
		var newStart = parseInt($(start).html()) + parseInt(objView.docCount);
	}
	if(newStart >= total) {return;}
	objView.startCount = newStart;
	$(start).html(parseInt($(start).html()) + parseInt(objView.docCount));
	var newCnt = (newStart-1) + parseInt(objView.docCount);
	if(newCnt > total) { newCnt = total; }
	$(currCnt).html(newCnt);
	objView.Refresh(true,true,false);
	setResizeHandlers()
}

function previous(){
	initContentPagingVars();
	if ($.trim($('#actualoffset').html()) != '') {
		if ($('#prevoffset').html() == '') {
			var newStart = 1;
		}
		else {
			var temp = $('#prevoffset').html().split(',');
			var newStart = parseInt(temp[temp.length - 1]);
			temp.pop();
			temp = temp.join(',');
			if (!temp || temp == '') {
				temp = '1';
			}
			$('#prevoffset').html(temp);
		}
	}
	else {
		var newStart = parseInt($(start).html()) - parseInt(objView.docCount);
	}
	if(newStart < 0) {return;}
	if(newStart < 1) {newStart = 1;}
	objView.startCount = newStart;
	$(start).html(newStart);
	var newCnt = (newStart-1) + parseInt(objView.docCount);
	if(newCnt < parseInt(objView.docCount)) { newCnt = objView.docCount; }
	if(newCnt > total) { newCnt = total; }
	$(currCnt).html(newCnt);
	objView.Refresh(true,true,false);
	setResizeHandlers()
}

function getDisplayCount(source){ 
	if($("#GetPage").attr("disabled") == "disabled") {return}
	initContentPagingVars();	
	var content = "";
	var sel = "";
	for(x=1; x<=4; x++) {
		sel = origCount * x;
		content += "<div onclick=\"setDisplayCount('" + sel + "');\" onmouseover=\"this.style.background='#1E90FF';this.style.color='white'\" onmouseout=\"this.style.background='white';this.style.color='black'\">" +
		"<span style=\"padding-left:1px\">" + sel + "</span></div>";
	}
	
	var sourcepos = jQuery("#currCount").position();	
	$("#countPicker").html(content).css({"display":"", "top" : sourcepos.top, "left" : sourcepos.left});
}

function setDisplayCount(selCount) {
	objView.docCount = selCount;
	objView.startCount = 1;
	start.innerHTML = 1;
	var newCnt = parseInt(objView.docCount);
	if(newCnt > total) { newCnt = total; }
	currCnt.innerHTML = newCnt;
	$("#countPicker").toggle();
	objView.Refresh(true,false,true);
	setResizeHandlers()
}

//=================== Document Compare ==========================

function CompareSelectedWordDocuments(){

	var PDFCreatorAvailable = true;
	//check whether PDF Creator is installed, which is required to view the comparison results
	//unless the user has printing rights, in which case comparison results may be viewed in Word.
	if(!DLExtensions.isPDFCreatorInstalled()) { PDFCreatorAvailable = false; }
	
	//check whether PDF Creator is installed, which is required to view the comparison results
	if(PDFCreatorAvailable==false && docInfo.RequirePDFCreator) {
		alert("Unable to run document comparison.  PDF Creator is not installed.");
		return;
	}
	
	//get the docids that have been selected and validate two have been selected
	var docids = objView.selectedEntries;
	if((docids.length > 2) || (docids.length < 2)) {
		alert("Please select two documents to compare.");
		return;
	}
	
	CompareWordAttachments(docids, function(saveCompareDocPath){	
		if(!saveCompareDocPath) {
			Docova.Utils.hideProgressMessage();
			return;
		}

		if(docInfo.RequirePDFCreator) {		
			Docova.Utils.showProgressMessage("Converting comparison results to PDF..." );
		
			//------------------ convert the compare results to PDF ----------------------------------
			var pdfPath = DLExtensions.ConvertToPDF ( saveCompareDocPath, true, "");
			Docova.Utils.hideProgressMessage();
		
			//--------------- launch the pdf for viewing ----------------------------
			DLExtensions.LaunchFile(pdfPath);
		} else {
			//launch in Word
			Docova.Utils.hideProgressMessage();
			DLExtensions.LaunchFile(saveCompareDocPath);
		}
	});
}

//----- object presence testers ----
function HasUploaderResize()
{
	try	{
		if(SetUploaderDimensions){return true;}
	}
	catch (e)
	{
		return false;
	}
	return false;
}

function HasViewPane()
{
	try	{
		if(ViewLoadDefaultPerspective){return true;}
	}
	catch (e)
	{
		return false;
	}
	return false;
}
// ------------- preloads icons used in view object---------------

function PreloadBaseImages()
{
    var curNsf = docInfo.ServerUrl + docInfo.ImgPath;
    var imgArray = new Array();
    var imgNames = "cat-collapse.gif,cat-expand.gif,coloptions.gif,pincolumn.gif,viewRefreshGreen.gif,chkrbrdclosed.gif,chkrbrdopened.gif,icn16-stddoc.gif,icn16-unknowndoc.gif,icn16-webpage.gif,sortadred-default.gif,popmenu-check.gif";
    var imgList = imgNames.split(",");
	for (k=0; k<imgList.length; k++)
	{
    	imgArray[k] = new Image();
    	imgArray[k].src = curNsf + imgList[k] + "?Open";
	}
}

//--------------------------------------Initializepage --------------------------
function InitPage()
{
	if(docInfo.FolderID){
		if (docInfo.isRecycleBin) {
			$("#labelViewOptions").prop("disabled", true);
			$("#inpViewScope").prop("disabled", true);
			$("#selVersionScope").prop("disabled", true);
		}
		if (docInfo.SyncNav) {
			try {
				var navFrame = parent.frames['fraLeftFrame'];
				navFrame.SyncFolderContent();
			} catch (e) {}
		}
	}
	ViewSetOnloadState();
	ViewLoadDefaultPerspective();
	ViewOpenDoc();
	
	if(objView.contentPaging) {
		$("#totalCount").html(objView.totalDocCount);
	}	
	checkAvailableHeight();
	if ( ! isiPad ){

		$(window).on("resize", function(){ checkAvailableHeight() });
	}
	
	/*if(typeof window['Postopen'] == "function"){
		try{
			Postopen();
		}catch(e){}
	}	*/
}

// ------------- sets the view object and loads default view perspective---------------
function ViewLoadDefaultPerspective()
{
	objView = new ObjView("divViewContent", (docInfo.FolderID ? false : true));
	objView.iconBaseUrl = docInfo.ImgPath;
	objView.imgPath = docInfo.ImgPath;
	objView.iconBaseUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/";
	objView.baseUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/";
	if (docInfo.isRecycleBin) {
		objView.baseLookupView="readrecycledataview.xml?LibraryID=" + docInfo.LibraryKey ;
	}
	else {
		objView.baseXmlUrl=docInfo.ServerUrl + "/" + docInfo.NsfName + "/readfolderdataview.xml?OpenAgent";
	}
	
	objView.folderID = docInfo.FolderID; 
	objView.columnPropertiesDialogUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgViewColumnProperties?OpenForm&FolderID=" + docInfo.FolderID+ "&LibraryID=" + docInfo.LibraryKey; 
	objView.serverName=docInfo.ServerName;
	objView.nsfName=docInfo.NsfName;
	objView.serviceAgent=docInfo.ServerUrl + "/" + docInfo.NsfName + "/ViewServices?OpenAgent" + (!docInfo.FolderID ? ("&AppID=" + docInfo.AppID) : '');
	objView.dateFormat = docInfo.SessionDateFormat;
	objView.embView = (docInfo.IsEmbedded || false);
	
	if ( docInfo.Query_String.indexOf ("&restrictToCategory=") > 0 )
	{
		objView.restrictToCategory = docInfo.RestrictToCategory;
	}

	if (docInfo.ChildrenOnly == "1" && docInfo.IsEmbedded ){
		objView.childrenOnlyKey = window.parent.docInfo.DocID;
	}
		
	if(docInfo.UseContentPaging == "1") {	
		objView.contentPaging = true;
		objView.docCount = docInfo.MaxDocCount;
	}
	ViewLoadPerspective();
	ViewAttachEvents();
}


// ======================= view event handlers =======================

// event attacher
function ViewAttachEvents()
{
	var paneObj = document.getElementById("divViewContent"); //#divViewEventCapture
	if(!paneObj) {return false;}
	$(paneObj).on("mousedown", function(e){ ViewHandleEvent(e); });
	$(paneObj).on("dblclick", function(e){ e.stopPropagation(); ViewHandleEvent(e); });
	$("#divViewEventCapture").on("keydown", function(e){ ViewHandleEvent(e); });
	$("#divViewEventCapture").on("keyup", function(e){  ViewHandleEvent(e); });
	if (docInfo.IsEmbedded  !== true){
		$(paneObj).on("contextmenu", function (e) {
			e.preventDefault();
			ViewHandleEvent(e);
		});
	}
}


//=====================  event dispatcher ===========================
function ViewHandleEvent(event)
{
	var eventSource = event.target;
	var eventType = event.type;
	var sourceClass=eventSource.className || "";
	sourceClass = jQuery.trim(sourceClass.replace("ui-resizable", "")); 
	var sourceId=eventSource.id; 
	var keyCode = event.keyCode;
	var currentRow; //table row containing the eventSource
	var sourceType="";
	if (!eventSource) {
		return;
	}

	currentRow = $(eventSource).closest("TR").get(0);
	var tagname = $(eventSource).prop("tagName").toUpperCase();

	if ( $(eventSource).hasClass("far") || $(eventSource).hasClass("fas") || $(eventSource).hasClass("fab"))
	{
		if ( $(eventSource).hasClass(objView.categoryExpandClass.split(" ")[1]) || $(eventSource).hasClass(objView.categoryCollapseClass.split(" ")[1]) )
		{
			ViewEventDispatch(event, eventType, eventSource, "categorytoggle", keyCode, currentRow);
			return;
		}else if ($(eventSource).hasClass("fa-sync")  ){
			ViewEventDispatch(event, eventType, eventSource, "viewrefresh", keyCode);
			 return;
		}else if ( $(eventSource).hasClass("sorting")){
			 ViewEventDispatch(event, eventType, eventSource, "viewsort", keyCode);
			 return;
		}
	}

	//------------------ check if there are any event handlers for the event source --------
	//------------- clickable images
	if (tagname == "IMG") {
		if (sourceClass == "listsorticon") {
			ViewEventDispatch(event, eventType, eventSource, "viewsort", keyCode);
			return;				
		} else if (sourceClass == "reflection" || sourceClass == "content portray" || sourceClass == "content landscape") {
			ViewEventDispatch(event, eventType, eventSource, "coverflow", keyCode, currentRow);
			return;			
		} else if (sourceClass == "listviewrefresh") {
			ViewEventDispatch(event, eventType, eventSource, "viewrefresh", keyCode);
			return;
		} else if (sourceClass == "listexpandericon") {
			ViewEventDispatch(event, eventType, eventSource, "categorytoggle", keyCode, currentRow)
			return;
		}else if ( sourceClass=="shadow"){
			eventSource = eventSource.parentNode;
			currentRow = eventSource;
			ViewEventDispatch(event, eventType, eventSource, "thumbnail", keyCode, currentRow);
		}			
	//------------ input fields		
	}else if (tagname == "INPUT") {
		if (sourceId == "inpQuery") {
			ViewEventDispatch(event, eventType, eventSource, "ftquery", keyCode);
			return;
		}else if(sourceId=="GetPage") {
			return;
		}else if(sourceId == "ExportSelectCb"){
			ViewEventDispatch(event, eventType, eventSource,  "selectcell", keyCode);
			return;
		}
	//------------ cells/rows		
	}else if (tagname == "TD" || tagname == "TH" || tagname == "SPAN") {
		//thumbnail column
		if ( sourceClass == "thumbnail" ) {
			currentRow = eventSource;
			ViewEventDispatch(event, eventType, eventSource, "thumbnail", keyCode, currentRow);
			return;
		//column heading	
		}else if(sourceClass=="listheader" || sourceClass=="listheaderfr" || sourceClass=="listheaderfltr" || sourceClass=="listheaderfrfltr"){ 
			ViewEventDispatch(event, eventType, eventSource, "viewheader", keyCode);
			return;
		//selection column heading	
		} else if (sourceClass == "listselheader"){
			ViewEventDispatch(event, eventType, eventSource, "viewselectheader", keyCode);
			return;
		//data or total cell
		} else if (sourceClass == "listitem" || sourceClass == "listitemfr") {
			if ($(currentRow).attr("isRecord")) {
				ViewEventDispatch(event, eventType, eventSource, "datarow", keyCode, currentRow);
				return;
			}else if ($(currentRow).attr("isCategory")) {
				ViewEventDispatch(event, eventType, eventSource, "categoryrow", keyCode, currentRow);
				return;
			}else if ($(currentRow).attr("isSubtotal")) {
				ViewEventDispatch(event, eventType, eventSource, "subtotalrow", keyCode, currentRow);
				return;
			}else if ($(currentRow).attr("isTotal")) {
				ViewEventDispatch(event, eventType, eventSource, "totalrow", keyCode, currentRow);
				return;
			}
		//category heading cell			
		} else if (sourceClass == "listcat" || sourceClass == "listcatfr") {
			ViewEventDispatch(event, eventType, eventSource,  "categoryrow", keyCode, currentRow);
			return;
		//selection margin cell					
		} else if (sourceClass == "listsel") {
			ViewEventDispatch(event, eventType, eventSource,  "selectcell", keyCode, currentRow);
			return;
		}
	}

	ViewEventDispatch(event, eventType, eventSource,  "", keyCode, currentRow);
}

//------------------ dispatch event handler based on event source and type --------
function ViewEventDispatch(event, eventType, eventSource, sourceType, keyCode, currentRow)
{
	if(eventType=="mousemove")
	{
		if(sourceType=="selectcell") {
			ViewDocSelectDrag(eventSource, event.which);
			return CancelEvent();
		} //doc selection column
		if(sourceType=="viewheader" ) {
			ViewColumnDrag(eventSource);
			return CancelEvent();
		} //doc selection column
	}
	else if(eventType=="mousedown" && (event.which == 1 || event.which == 3)) //left mouse button or right mouse button
	{
		$("#divViewContent").focus();
		if(sourceType=="selectcell"){
			ViewDocSelectClick(eventSource);
		} //doc selection column
		if(sourceType=="viewsort") {
			ViewSortColumn(eventSource);
		}
		if(sourceType=="viewrefresh" || sourceType=="viewselectheader")
		{ //view refresh icon
			if(docInfo.EnableFolderFiltering == "1")
			{
				$("#divViewContent").css("display", "none");
				objView.Refresh(true,false,true,false,false, true);
				setResizeHandlers()
				$("#divViewContent").css("display", "");
			}else{
				objView.Refresh(true, false, true);
				moveScroll(); //in case view list is scrolled we need to reset the header
				setResizeHandlers()
			}
		} 
		if(sourceType=="categorytoggle") {
			ViewToggleCategory(currentRow);
		}
		if(sourceType=="datarow" || sourceType=="subtotalrow" || sourceType=="categoryrow" || sourceType=="thumbnail")
		{
			if(isiPad){  //if single click on an iPad, if row is already highlighted then open doc like a double click.
				if( currentRow.id == objView.currentEntry){ //check to ensure a doc is highlighted
					if(sourceType=="datarow" || sourceType=="subtotalrow" || sourceType=="categoryrow" || sourceType =="thumbnail" ) {
						ViewHighlightEntry(currentRow);
					}
					if(sourceType=="datarow") {
						CreateEntrySubmenu(event);
					}
				}else{
					ViewHighlightEntry(currentRow);
				}
			}else{
				ViewHighlightEntry(currentRow);
			}
		}
		if(sourceType=="viewheader" && event.which !== 3) {
			ViewColumnSelectFilter(eventSource);
		}
	}
	else if(eventType=="mouseover")
	{
		if(sourceType=="viewheader" ) {
			ViewColumnDragOver(eventSource);
		}
	}
	else if(eventType=="mouseup")
	{
		if(sourceType=="viewheader" ) {
			ViewColumnDragDrop(eventSource, eventType);
		}
		return ViewClearEventState();
	}
	else if(eventType=="click")
	{
		return CancelEvent();
	}
	else if(eventType=="dblclick" )
	{
		if(sourceType=="datarow") {
			var continueload = true;
			if(!docInfo.FolderID){
				var uiView = Docova.getUIView();
				if (  uiView._triggers['dblclick'] )  {
					if (! uiView.triggerHandlerAsync('dblclick', uiView) ){
					 	continueload=false;
					}
				}
			}
			if(continueload){
				ViewLoadDocument(false);
			}
		}
		if(sourceType=="categoryrow" ) {
			ViewToggleCategory(currentRow);
		}
		if(sourceType=="thumbnail"){
			ViewLoadDocument(false);
		}
		if(sourceType=="coverflow"){
			ViewHighlightEntry(currentRow);
			ViewLoadDocument(false);
		}
	}
	else if(eventType=="mouseout")
	{
		return ViewClearEventState();
	}
	else if(eventType=="keydown")
	{
		var isIE = false || !!document.documentMode;
		if (isIE)
			event.preventDefault();
		ViewHandleKeyboardEvent(eventType, eventSource, sourceType, keyCode, currentRow);
	}
	else if ( eventType == "keyup")
	{
		var isIE = false || !!document.documentMode;
		if (isIE)
			event.preventDefault();
		shiftkeypressed = false;
		ctrlkeypressed = false;
		return;
	}
	else if(eventType=="selectstart")
	{
		if(sourceType !="ftquery") {return CancelEvent();}
	}
	else if(eventType=="contextmenu")
	{
		if(sourceType=="datarow" || sourceType=="subtotalrow" || sourceType=="categoryrow" || sourceType =="thumbnail" ) {
			ViewHighlightEntry(currentRow);
		}
		if(sourceType=="datarow"){
			CreateEntrySubmenu(event);
		}
		if(sourceType=="thumbnail"){
			CreateThumbnailsSubmenu(event, eventSource);
		}
		if(sourceType=="viewheader" || sourceType=="viewselectheader" || sourceType=="viewrefresh") {
			ViewShowContextPopup(sourceType, eventSource);
		}
		return CancelEvent();
	}
}

// ------------------------------------ document list key handlers ------------------------------------------------

function ViewHandleKeyboardEvent(eventType, eventSource, sourceType, keyCode, currentRow)
{
	var DEL_KEY = 46;
	var ENTER_KEY = 13;
	var DOWNARROW_KEY = 40;
	var UPARROW_KEY = 38;
	var F9_KEY = 120;
	var SPACE_KEY= 32;
	var ESC_KEY= 27;
	var SHIFT_KEY=16;
	var CTRL_KEY = 17;
	var C_KEY  = 67;
	var V_KEY = 86;
	var X_KEY = 88;
    var A_KEY = 65;


	//The keydown event.target is different in different browsers. Use view object currentEntry to re-set currentRow
	//and sourceType
	if(objView.currentEntry){
		currentRow = $("#divViewEventCapture").find("#" + objView.currentEntry).get(0);
		if($(currentRow).attr("isRecord")){sourceType = "datarow";}
		if($(currentRow).attr("isCategory")){sourceType = "categoryrow";}
		if($(currentRow).attr("isSubtotal")){sourceType = "subtotalrow";}
		if($(currentRow).attr("isTotal")){sourceType = "totalrow";}
	}
	
	if(keyCode == DEL_KEY)
	{
		if(docInfo.CanDeleteDocuments) {
			if(sourceType=="datarow" ){
				if(docInfo.EnableFolderFiltering == "1"){
					objView.DeleteSelectedEntries();
					$("#divViewContent").css("display", "none");
					ApplyFolderFilter(true);
					$("#divViewContent").css("display", "");
				}else{
					objView.DeleteSelectedEntries();
				}
			}
		}
	}
	else if ( keyCode == SHIFT_KEY )
	{
		shiftkeypressed = true;
	}else if ( keyCode == CTRL_KEY ){
		ctrlkeypressed = true;
	}else if ( keyCode == C_KEY ){
		if ( ctrlkeypressed ) {
			ViewCopySelected();
		}
	}else if ( keyCode == V_KEY ) {
		if ( ctrlkeypressed ) {
			ViewPasteSelected();
		}
	}else if ( keyCode == X_KEY ){
		if ( ctrlkeypressed ) {
			ViewCutSelected();
		}
	}else if ( keyCode == A_KEY ){
		if ( ctrlkeypressed ) {
			objView.SelectAllEntries();
			$("#divViewContent").triggerHandler( "focus" );
		}
	}
	else if(keyCode == ENTER_KEY)
	{
		if(sourceType=="datarow" && objView.currentEntry != "") //process entry only if it is highlighted
		{
			ViewLoadDocument(false);
		}
		if(sourceType=="categoryrow" || sourceType== "categorycell") {
			ViewToggleCategory(currentRow);
		}
		if(sourceType=="ftquery") {ViewFTSearch();}
	}
	else if(keyCode == DOWNARROW_KEY)
	{
		if(sourceType=="datarow" || sourceType=="categoryrow" || sourceType=="subtotalrow")
		{
			ViewMoveDocHighlight("down");
		}
	}
	else if(keyCode == UPARROW_KEY)
	{
		if(sourceType=="datarow" || sourceType=="categoryrow" || sourceType=="subtotalrow")
		{
			ViewMoveDocHighlight("up");
		}
	}
	else if(keyCode == ESC_KEY)
	{
		if(sourceType=="datarow" || sourceType=="categoryrow" || sourceType=="subtotalrow" && objView.currentEntry != "") //select the entry only if it is highlighted
		{
			ViewResetEntryHighlight();
		}
		if(sourceType=="ftquery") {ViewFTClear(); $("#inpQuery").focus();}	
	}
	else if(keyCode == F9_KEY)
	{
		objView.Refresh(true, false, true);
		setResizeHandlers()
	}	
	else if(keyCode == SPACE_KEY)
	{
		if(sourceType=="datarow" && objView.currentEntry != "") //select the entry only if it is highlighted
		{
			ViewDocSelectClick(document.getElementById(objView.currentEntry));
		}
		if(sourceType=="categoryrow" || sourceType== "categorycell") {
			ViewToggleCategory(currentRow);
		}
		if(sourceType=="ftquery"){return;} //let it go
		ViewClearEventState();
		CancelEvent(); //handled
	}
}


//===================== Event handler functions ===================

//----------------------------- highlight entry on click -------------------
function ViewHighlightEntry(source)
{
	if (!source) {
		return false;
	}
	if ( source.className == "thumbnail" ){
		$(".thumbnail").css("background", "");
		$(source).css("background", "lightgray" );
	}
	//if shift is being pressed then set checkbox
	if ( shiftkeypressed ) {
		var startid = objView.currentEntry;
		if (startid == "")
			return;
		var endid =  source.id;
		objView.ShiftSelectEntries  (startid, endid )
	}else if ( ctrlkeypressed ){
		var id =   source.id;
		objView.CtrlSelectEntries(id );
	}else{
		if(objView.isAppView){
			objView.HighlightEntryByRowIndex(source.rowIndex);
		}else{
			objView.HighlightEntryById(source.id);
		}
	}
	CancelEvent();
}

//----------------------------- un-highlight entry on ESC key -------------------
function ViewResetEntryHighlight()
{
	objView.ResetHighlight();
}

//----------------------------- up/down keys on highlighted entry ------------------------
function ViewMoveDocHighlight(dir) //up/down arrow key handler
{
	objView.MoveEntryHighlight(dir) ;
	return CancelEvent();
}


// ----- called to refreesh the view after adding/editing a document --------------
function ViewReload(selectDoc)
{
	if(selectDoc) {
		objView.currentEntry = selectDoc;
	}
	objView.queryOptions = ViewGetQueryOptions();
	
	//-----If view is being filtered, reapply the filter-----
	if(docInfo.EnableFolderFiltering == "1"){
		$("#divViewContent").css("display", "none");	
		objView.Refresh(true,false,true);
		ApplyFolderFilter(true);
		setResizeHandlers()
		$("#divViewContent").css("display", "");
	}else{
		objView.Refresh(true,false,true);
		setResizeHandlers()
	}
}


//---- open specific document after opening the folder ---------
function ViewOpenDoc() {

	if(docInfo.LoadDoc) {
		objView.HighlightEntryById(docInfo.LoadDoc);
	}

	if(docInfo.DocumentTypeOption=="N" && !docInfo.isRecycleBin){
		infoUrl=docInfo.ServerUrl + "/" + docInfo.NsfName + "/wFolderInfo?OpenForm&ParentUNID=" + docInfo.DocID;
		ViewLoadDocument(infoUrl);
	}
	if(docInfo.LoadDoc){
		(objView.currentEntry)? ViewLoadDocument() : OpenDocumentWindow(docInfo.LoadDoc);
	}
}

//----------------  open/close document handlers -----------------------
function ViewLoadDocument(docUrl, docTypeName, isNewDoc, editMode)
{
	var entryObj = null;
	if(docInfo.isRecycleBin) // documents in recycle bin cannot be opened, just the properties dialog is displayed
	{
		var entryObj = objView.GetCurrentEntry();
		if(!entryObj) {return; }
		var recType = entryObj.GetElementValue("rectype");
		if(recType == "fld") //deleted folder
		{
			ShowFolderProperties(entryObj.entryId);
		}
		else if(recType == "doc") //deleted doc
		{
			ShowDocumentProperties(entryObj.entryId);
		}
		return;
	}
	
	if(typeof window['Queryopendocument'] == "function"){
		var oktoopen = true;
		try{
			oktoopen = Queryopendocument();
		}catch(e){}
		if(!oktoopen){
			return false;
		}
	}		
	
	//-- check if running in DOE interface
	if(docInfo.IsDOE){
		window.external.DOE_OpenDoc( objView.currentEntry);
	//-- browser interface
	}else{
		var action = "Open";
		if(editMode) { 
			//need to check if user is authorized to edit
			if(!mayUserEditDoc(objView.GetCurrentEntry().entryId)) {
				Docova.Utils.messageBox({
					title: "Not Authorized",
					prompt: "You are not authorized to edit this document.",
					icontype: 1,
					msgboxtype: 0
				});
				return;
			}
			action = "Edit";
		}
	
		//check if the view defaults to open the doc in edit mode
		if (docInfo.OpenDocInEditMode && docInfo.OpenDocInEditMode == "1" && disableOpenDocInEditMode == false){
			action = "Edit";
		}
	
		// regular folder
		if (!docUrl && objView.currentEntry) {
			if (action == 'Edit') {
				if (docInfo.FolderID) {
					//External Views Hook
					if (docInfo.externalView && docInfo.externalView!='')
						docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + objView.currentEntry+ "?DataView="+docInfo.externalView;
					else
						docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + objView.currentEntry;			
				} else 
					docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wViewForm/0/" + objView.currentEntry + "?EditDocument&AppID=" + docInfo.AppID;
			}
			else {
				if (docInfo.FolderID) {
					//External Views Hook
					if (docInfo.externalView && docInfo.externalView!='')
						docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + objView.currentEntry + "?OpenDocument&ParentUNID=" + docInfo.DocID + "&DataView="+docInfo.externalView;
					else
						docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + objView.currentEntry + "?OpenDocument&ParentUNID=" + docInfo.DocID;
				} else
					docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wReadDocument/" + objView.currentEntry + "?OpenDocument&ParentUNID=" + docInfo.AppID;
			}
		}
		if(!docUrl) {
			Docova.Utils.messageBox({
				title: "Error",
				prompt: "Document Url cannot be located.",
				icontype: 1,
				msgboxtype: 0
			});
			return;
		}
	
		//------------- tabbed interface ----------------------------------
		entryObj = objView.GetCurrentEntry();

		var frameID = "";
		var title = "";
		if(isNewDoc) {
			frameID = window.parent.fraTabbedTable.objTabBar.GetNewDocID();
			if(docTypeName == undefined || docTypeName == "") {
				docTypeName = "Document";
			}
			title = "New " + docTypeName;	
		} else if(entryObj) { 
			
		
			frameID = entryObj.entryId; 
		
			//thumbnails have a docid~attachment name as id.
			//if this is the case, get the docid alone
		
			if ( frameID.indexOf("~" ) > 0 ){
				frameID = frameID.substring(0, frameID.indexOf("~") );
			}
		
			var titlenode = ( docInfo.TitleNode && docInfo.TitleNode  != "" ? docInfo.TitleNode : (docInfo.FolderID ? "F8" : "CF0"));
			title = entryObj.GetElementValue(titlenode);
		}

		try	{
			if(onDocumentOpen()){return true;}
		}catch (e){
			//-- application view
			if(objView.isAppView){
				if (docInfo.OpenDocInDialog && docInfo.OpenDocInDialog == '1') {
					//OpenDocumentWindow(frameID, action);
					Docova.Utils.openDialog({
						isresponse: true,
						docid: entryObj.entryId,
						title: title,
						width: 800,
						height: 600,
						docurl: docUrl,
						onClose: function(){
							objView.Refresh(true,false,true);
						}
					});
				}
				else {
					var uiws = Docova.getUIWorkspace(document);
					var parentFrame = parent.frameElement;
					var parentPanel = $(parentFrame).parents(".grid-stack-item");
					var eTarget = parentPanel.attr("target");
					eTarget = eTarget ? eTarget : "";
		
					var targetPanel = null;
		
					if ( eTarget != ""){	
						targetPanel = uiws.getPanel(docInfo.AppID, eTarget);
		
					}else{
						targetPanel = uiws.getCurrentPanel();
					}
		
					if ( targetPanel ){
						var tabbedTable = uiws.getFrame(targetPanel.document, "fraTabbedTable" );
					}
		
					if ( tabbedTable){
						var panelwindow = tabbedTable[0].contentWindow;
						if ( panelwindow.objTabBar) {
							panelwindow.objTabBar.CreateTab(title, frameID, "D", docUrl, docInfo.DocID, isNewDoc);
						}
					}else{
						if ( eTarget != "" ){
							targetPanel.location.href	 = docUrl;
						}else{
							OpenDocumentWindow(frameID);
						}
					}
				}
			//-- library folder view
			}else{
				if ( docInfo.IsEmbedded && docInfo.IsEmbedded === true ){
					window.parent.parent.fraTabbedTable.objTabBar.CreateTab(title, frameID, "D", docUrl, docInfo.DocID, isNewDoc);
				}else{
					window.parent.fraTabbedTable.objTabBar.CreateTab(title, frameID, "D", docUrl, docInfo.DocID, isNewDoc);
				}				
			}
		}
	}
}

function OpenDocumentWindow(docID, action) {
	var targetUnid=(docID)? docID : objView.currentEntry;
	if(!targetUnid){return false;}
	
	if(typeof window['Queryopendocument'] == "function"){
		var oktoopen = true;
		try{
			oktoopen = Queryopendocument();
		}catch(e){}
		if(!oktoopen){
			return false;
		}
	}		
	
	if (!action || action == "Open") {
		if (docInfo.FolderID) {
			var docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + targetUnid + "?OpenDocument&ParentUNID=" + docInfo.DocID + "&mode=window";
		}
		else {
			var docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wReadDocument/" + docID + "?OpenDocument&ParentUNID=" + docInfo.AppID + "&mode=window";
		}
	}
	else {
		if (docInfo.FolderID) {
			var docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + targetUnid + "?editDocument&mode=window";
		}
		else {
			var docUrl = docInfo.ServerUrl + '/' + docInfo.NsfName + '/wViewForm/0/' + docID + '?EditDocument&AppID=' + docInfo.AppID;
		}
	}

	var leftPosition = (screen.width) ? (screen.width-800)/2 : 20;
	var topPosition = (screen.height) ? (screen.height-600)/2 : 20;
	dlgSize = "height=600,width=800,top=" + topPosition+ ",left=" + leftPosition;
	var dlgSettings = dlgSize + ",status=no,toolbar=no,menubar=no,location=no,scrollbars=yes,resizable=yes";
	return window.open(docUrl,targetUnid,dlgSettings); 
}

function OpenFileWindow(filename){
	var targetUnid=objView.currentEntry;
	if(!targetUnid){return false;}
	
	var docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + targetUnid + "/$file/" + filename + "?open&" + Math.random();

	return window.open(docUrl); //Display the address dialog

}

function ViewUnloadDocument(refreshView, selectDocId)
{
	ViewSetTitleOptions("Folder: " + docInfo.FolderName);
	window.parent.fsContentFrameset.rows = "*,0";	

	if(refreshView) {
		ViewReload(selectDocId);
		return;
	}
		
	if(objView.currentEntry != "") 
	{
		objView.HighlightEntryById(objView.currentEntry);
	}
	else
	{
		$("#divViewContent").focus();
	}
	var curContentUrl = window.parent.fraContentBottom.location.href;
	if(curContentUrl.indexOf("/BlankContent?")==-1){
		var contentUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" +  "BlankContent?OpenPage";
		window.parent.fraContentBottom.location.href=contentUrl ;
	}
}

function setResizeHandlers()
{
	//setResizeHandlers();
	$("#VDataTable thead tr td:not(:last-child)").resizable({ handles: "e" }) ;
}

//------------ perspective handlers -----------------------------
function ViewLoadPerspective()
{
	var perspectiveId = (currentPerspective)? currentPerspective : docInfo.DefaultPerspective;
	var el = document.getElementById("xmlViewPerspective");
	if(!el){
		return;
	}
	var perspectiveDocXml = el.textContent || el.innerText || el.nodeValue || el.innerHTML;	
	objView.SetViewParams(perspectiveDocXml);

	var perspectiveDoc = (new DOMParser()).parseFromString(perspectiveDocXml, "text/xml");
	if(Sarissa.getParseErrorText(perspectiveDoc) != Sarissa.PARSED_OK){  
  		var errorText = Sarissa.getParseErrorText(perspectiveDoc);
		alert("Error parsing xsl: " + errorText);
		perspectiveDoc = null;
		return;
	}					
	var isAutoCollapseNode = perspectiveDoc.selectSingleNode("viewperspective/autocollapse");
	if (isAutoCollapseNode == null){
		var isAutoCollapse = "0";
	}else{
		var isAutoCollapse = isAutoCollapseNode.textContent || isAutoCollapseNode.text;
	}
	
	jQuery("#inpSwitchPerspective").val(perspectiveId);
	$("#inpSwitchPerspective").multiselect('refresh');
	if(docInfo.FolderID){
		ViewHighlightDefaultPerspective();
	}
	objView.queryOptions = ViewGetQueryOptions();

	if(isAutoCollapse == "1"){
		$("#divViewContent").css("display", "none");
		if(docInfo.EnableFolderFiltering == "1"){ //Initialize and apply default filtering if enabled	, last "true" parameter on the .Refresh
			objView.Refresh(true,true,true,true,true);
			setResizeHandlers();
		}else{
			objView.Refresh(true,true,true);
			setResizeHandlers();
		}
		objView.CollapseAll();
		$("#divViewContent").css("display", "");
	} else {
	
		if(docInfo.EnableFolderFiltering == "1"){ //Initialize and apply default filtering if enabled, last "true" parameter on the .Refresh
			$("#divViewContent").css("display", "none");
			objView.Refresh(true,true,true,true,true);
			setResizeHandlers();
			$("#divViewContent").css("display", "");
		}else{
			objView.Refresh(true,true,true);
			setResizeHandlers();
		}
	}
}

function ViewHighlightDefaultPerspective()
{
	var selectBox = document.getElementById("inpSwitchPerspective");
	for(var i=0; i<selectBox.options.length; i++)
	{
		var optcolor = $(selectBox.options[i]).val() == docInfo.DefaultPerspective ? "#ff0000" : "";
		$(selectBox.options[i]).css("color",  optcolor);
	}
}

function ViewSwitchPerspective(perspectiveId)
{
	var processPerspective = false;
	var url = "";
	
	if(! perspectiveId) {return;}
	var el = document.getElementById("xmlViewPerspective");
		if(! el){
		return;
	}	

	var idParts = perspectiveId.split("_");
	if(idParts[0] == "system" || idParts[0] == "custom") //system perspectives are stored in the home database
	{
		var url=  docInfo.ServerUrl + docInfo.PortalWebPath + "/perspectives.xml?OpenPage&pid=" + idParts[1];	
		processPerspective = true;
	}
	else if(idParts[0] == "user") //user perspectives are stored in super cookie
	{
		return;
	}
	else //unknown location
	{
		return;
	}
	
	if(processPerspective) {
		$.ajax({
			'type' : "GET",
			'url' : url,
			'contentType': false,
			'async' : false,
			'dataType' : 'text'
		})
		.done(function(data) {
			if(!data) {
				obj.status="FAILED";
				obj.error = "No data received from server";
				return false;
			} else {
				var parser = new DOMParser();
				Sarissa.updateContentFromNode(parser.parseFromString(data,"text/xml"), el);
			}
		});
	}	

	currentPerspective = perspectiveId;
	ViewLoadPerspective();
}

function ShowPerspectiveProperties(clickSrc)
{
	var perspectiveDoc = doc.xmlViewPerspective.XMLDocument;
	var perspectiveDocXml = "";
	if(!perspectiveDoc)	{return false;}
	
	var descNode = perspectiveDoc.selectSingleNode("viewperspective/description");
	var nameNode = perspectiveDoc.selectSingleNode("viewperspective/name");
	var typeNode  = perspectiveDoc.selectSingleNode("viewperspective/type");
	var authorNode = perspectiveDoc.selectSingleNode("viewperspective/createdby");
	var createdNode = perspectiveDoc.selectSingleNode("viewperspective/createddate");
	var modifiedNode = perspectiveDoc.selectSingleNode("viewperspective/modifiedby");
	var modDateNode = perspectiveDoc.selectSingleNode("viewperspective/modifieddate");
			
	oPopup = window.createPopup();
	var popupHtml = '<div style="width:100%; height: 100%;background-color: white margin: 0px; scroll:no; border: solid 1px #7DA5E0;">';
	popupHtml += '<div style="width:100%; height: 18px; filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr=\'#C8DFFB\', EndColorStr=\'#7DA5E0\'); font: 11px verdana,arial; padding: 2px 0px 2px 4px;"';
	popupHtml += '>';
	popupHtml += '<div style="float:left;">' + nameNode.text + '</div>';
	popupHtml += '<img style="float:right; background-color: #dd2200;" src="' + docInfo.ServerUrl + "/" + docInfo.NsfName + '/vpclose.gif"  onclick="document.oPopup.hide();"/>'; 
	popupHtml += '</div>';
	popupHtml += '<div style="width:100%; height: 150px; font: 11px verdana,arial; padding: 4px;">';
	popupHtml += 'Type: ' + typeNode.text;
	popupHtml += '<br>Created by: ' + authorNode.text;
	popupHtml += '<br>Created on: ' + createdNode.text;
	popupHtml += '<br>Modified by: ' + modifiedNode.text;
	popupHtml += '<br>Modified on: ' + modDateNode.text;
	popupHtml += '<br>Description:<br>' + descNode.text;
	popupHtml += '</div>';
	popupHtml += '</div>';
	oPopup.document.oPopup = oPopup;
	oPopup.document.body.innerHTML = popupHtml; 
	oPopup.show(0,0, 220, 174, doc.inpSwitchPerspective);
}

function ViewSavePerspective()
{
	currentPerspective = $("#inpSwitchPerspective").val();
	var el = document.getElementById("xmlViewPerspective");
	if(! el){
		return;
	}

	var perspectiveXml = el.textContent || el.innerText || el.nodeValue || el.innerHTML;	
	var parser = new DOMParser;
	var perspectiveDoc = parser.parseFromString(perspectiveXml, "text/xml");

	var descNode = perspectiveDoc.selectSingleNode("viewperspective/description");
	var nameNode = perspectiveDoc.selectSingleNode("viewperspective/name");
	var autocollapseNode = perspectiveDoc.selectSingleNode("viewperspective/autocollapse");
	var typeNode  = perspectiveDoc.selectSingleNode("viewperspective/type");
	var unidNode  = perspectiveDoc.selectSingleNode("viewperspective/Unid");
	var idNode = perspectiveDoc.selectSingleNode("viewperspective/id");
	var libScope = perspectiveDoc.selectSingleNode("viewperspective/libscope");
	var libDefault = perspectiveDoc.selectSingleNode("viewperspective/libdefault");

	dlgParams.length = 0;
	dlgParams[0] = typeNode.textContent || typeNode.text;
	dlgParams[1] = nameNode.textContent || nameNode.text;
	dlgParams[2] = descNode.textContent || descNode.text;
	dlgParams[3] = unidNode.textContent || unidNode.text;		
	dlgParams[4] = idNode.textContent || idNode.text;
	dlgParams[5] = (libScope != null)? libScope.textContent || libScope.text : "";
	dlgParams[6] = (libDefault != null)? libDefault.textContent || libDefault.text : "";
	dlgParams[7] = (currentPerspective==docInfo.DefaultPerspective);	
	dlgParams[8]= (autocollapseNode != null)? autocollapseNode.textContent || autocollapseNode.text : "0";
	var dlgSettings = "dialogHeight: 560px; dialogWidth: 500px; dialogTop: px; dialogLeft: px; edge: raised; ";
	dlgSettings += "center: Yes; help: No; resizable: No; status: No;";
	
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgSavePerspective?OpenForm&ParentUNID=" + docInfo.DocID;
	var dlgViewPerspective = window.top.Docova.Utils.createDialog({
			id: "divDlgViewPerspective", 
			url: dlgUrl,
			title: "View Perspective",
			height: 450,
			width: 500,
			useiframe: true,
			sourcewindow: window,
			buttons: {
       			"Save": function() {
					var dlgDoc = window.top.$("#divDlgViewPerspectiveIFrame")[0].contentWindow.document;
					var description = $("#Description", dlgDoc).val();
					var name = $("#Name", dlgDoc).val();
					var autocollapse = $("#AutoCollapse", dlgDoc).prop("checked") ? 1 : 0;  //solo checkbox
					var action = $("input[name=SaveOption]:checked", dlgDoc).val(); //multi checkbox with name SaveOption
					var makeDefault =  $("#MakeDefault", dlgDoc).prop("checked") ? 1 : 0; //solo checkbox
					var libScope = $("#LibScope", dlgDoc).prop("checked") ? 1 : 0; //solo checkbox
					var libDefault = $("#LibDefault", dlgDoc).prop("checked") ? 1 : 0; //solo checkbox
					var type = "custom";
					if($.trim(name) == ""){
						window.top.Docova.Utils.messageBox({
							prompt: "Please provide a Perspective Name.",
							title: "No Perspective Name.",
							icontype: 1,
							width: 300
						});
						return;
					}
					//--build the update request	
					var request="";
					request += "<Request>";
					request += "<Action>" + action + "</Action>";
					request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
					request += "<Unid>" + docInfo.DocID + "</Unid>";
					request += "<setdefault>" + makeDefault + "</setdefault>";
					request += "<libscope>" + libScope + "</libscope>";
					request += "<libdefault>" + libDefault + "</libdefault>";
					request += "<viewperspective>";
					request += "<type>" +  type + "</type>";
					request += "<Unid>" + dlgParams[3] + "</Unid>";
					request += "<name><![CDATA[" + name + "]]></name>";	
					request += "<description><![CDATA[" + description + "]]></description>";
					request += "<autocollapse>" + autocollapse + "</autocollapse>";					
					request += objView.GetViewParams();
					request += "</viewperspective>";
					request += "</Request>";

					request = encodeURIComponent(request);
					DoViewSavePerspective(request, action, name, type, makeDefault);
					dlgViewPerspective.closeDialog();
        		},
        		"Cancel": function() {
					dlgViewPerspective.closeDialog();
        		}
      		}
		});
}

function DoViewSavePerspective(request, action, name, type, makeDefault){		
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/FolderServices?OpenAgent";

	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml)
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				if(action == "NEWPERSPECTIVE"){ //added new perspective (save as action)	
					//add new perspective to the selection field without reloading the view
					var newId = xmlobj.find('Results Result[ID=Ret1]').text();
					var newoptionval = type + "_" + newId;
					$('<option>').val(newoptionval).text(name).attr("selected", "selected").appendTo('#inpSwitchPerspective'); ///or
					$("#inpSwitchPerspective").multiselect('refresh');
					if(makeDefault){
						docInfo.DefaultPerspective = type + "_" + newId; 
					}
					ViewHighlightDefaultPerspective();
					ViewSwitchPerspective(type + "_" + newId);
				}
			}
		},
		error: function(){
			alert("Error:  The perspective could not be created.  Please try again or contact your Administrator");
		}
	});
return;
}

function ViewDeletePerspective()
{
	var el = document.getElementById("xmlViewPerspective");
	if(! el){
		return;
	}

	var perspectiveXml = el.textContent || el.innerText || el.nodeValue || el.innerHTML;	
	var parser = new DOMParser;
	var perspectiveDoc = parser.parseFromString(perspectiveXml, "text/xml");
	
	var typeNode  = perspectiveDoc.selectSingleNode("viewperspective/type");
	var unidNode  = perspectiveDoc.selectSingleNode("viewperspective/Unid");

	var unid = unidNode.textContent || unidNode.text;
	var perspectiveType = typeNode.textContent || typeNode.text;
	
	if(perspectiveType == "system"){
		window.top.Docova.Utils.messageBox({
			prompt: "The Perspective you are trying to delete is a system perspective.  You do not have access to delete it.",
			title: "Cannot delete Perspective.",
			icontype: 1,
			width: 300
		});
		return;
	}

	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgDeletePerspective?OpenForm&ParentUNID=" + docInfo.DocID;
	var dlgDeletePerspective = window.top.Docova.Utils.createDialog({
		id: "divDlgDeletePerspective", 
		url: dlgUrl,
		title: "Delete Perspective Options",
		height: 200,
		width: 300,
		useiframe: true,
		sourcewindow: window,
		buttons: {
       		"Delete": function() {
				var dlgDoc = window.top.$("#divDlgDeletePerspectiveIFrame")[0].contentWindow.document;
				var action = $("input[name=DeleteOption]:checked", dlgDoc).val(); //multi checkbox with name SaveOption
				var type = "custom";
				if($.trim(action) == ""){
					window.top.Docova.Utils.messageBox({
						prompt: "Please choose one of the options or Cancel the dialog.",
						title: "No Option Selected.",
						icontype: 1,
						width: 300
					});
					return;
				}
				//--build the update request	
				var request="";
				request += "<Request>";
				request += "<Action>" + action + "</Action>";
				request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
				request += "<Unid>" + docInfo.DocID + "</Unid>";
				request += "<viewperspective>";
				request += "<type>" + type + "</type>";			
				request += "<Unid>" + unid + "</Unid>";
				request += "</viewperspective>";
				request += "</Request>";
				request = encodeURIComponent(request);
				DoViewDeletePerspective(request);
				dlgDeletePerspective.closeDialog();
			},
    		"Cancel": function() {
				dlgDeletePerspective.closeDialog();
    		}
    	}
	});
}

function DoViewDeletePerspective(request){
	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/FolderServices?OpenAgent";
	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				Docova.Utils.messageBox({
					prompt: "The perspective was deleted.",
					title: "Perspective Deleted.",
					width: 300
				});
				location.replace(location.href);					
			}
		},
		error: function(){
			alert("Error:  The perspective could not be deleted.  Please try again or contact the Administrator.");
		}
	})
}
//-------- column drag and drop --------------------
function ViewColumnDragStart(source) {}

function ViewColumnDrag(source) {}
function ViewColumnDragOver(source) {}
function ViewColumnDragDrop(source) {}

//-------- clipboard functions --------------------

function ViewCopySelected()
{
	if(objView.currentEntry =="" && objView.selectedEntries.length == 0) {return false;}
	ViewSetClipboard("copy");
}

function ViewCutSelected()
{
	if(objView.currentEntry =="" && objView.selectedEntries.length == 0) {return false;}
	ViewSetClipboard("cut");
}

function ViewSetClipboard(action)
{
	if(objView.currentEntry =="" && objView.selectedEntries.length == 0) {return false;}

	var clipdata="<srclibkey>" + docInfo.LibraryKey + "</srclibkey>";
	clipdata += "<srcfolderid>" + docInfo.FolderID + "</srcfolderid>"; // source folder id for refresh
	if(objView.selectedEntries.length > 0)
	{
		for(var k=0; k<objView.selectedEntries.length; k++)
		{
			clipdata+="<Unid>" + objView.selectedEntries[k] + "</Unid>";
		}
	}
	else
	{
		clipdata+="<Unid>" + objView.currentEntry  + "</Unid>";
	}

	Docova.Utils.setCookie({ keyname: "clipaction", keyvalue: action });
	Docova.Utils.setCookie({ keyname: "clipdata", keyvalue: clipdata });
}

function ViewPasteSelected()
{
	
	if(typeof window['Querypaste'] == "function"){
		var oktopaste = true;
		try{
			oktopaste = Querypaste();
		}catch(e){}
		if(!oktopaste){
			return false;
		}
	}
	
	var clipdata = Docova.Utils.getCookie({ keyname: "clipdata" });
	if(clipdata == "") {return false;}
	var action = Docova.Utils.getCookie({keyname: "clipaction" });
	var request = "<Request>";
	request += "<Action>PASTE</Action>";
	request += "<clipaction>" + action + "</clipaction>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<targetfolder>" + docInfo.FolderID + "</targetfolder>";
	request += clipdata;
	request += "</Request>";	

	if(action == "cut") //paste after cut is a one time shot
	{
 		var parser = new DOMParser();
		var tmpXMLDocument = parser.parseFromString("<dummy>" + clipdata + "</dummy>","text/xml");		
		var node = tmpXMLDocument.documentElement.selectSingleNode("srcfolderid");
		if ( node != null ){
			var folderid = node.textContent || node.text;
			var unid = folderid.substring(2);
			if (window.parent.fraTabbedTable){ 
				window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(unid, ""); // added for source folder refresh
			}
		}
		Docova.Utils.showProgressMessage("Moving documents. Please wait...");
		Docova.Utils.setCookie({
			keyname: "clipaction",
			keyvalue: ""
		});
		Docova.Utils.setCookie({
			keyname: "clipdata",
			keyvalue: ""
		});
	} else {
		Docova.Utils.showProgressMessage("Pasting documents. Please wait...");
	}

	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ViewServices?OpenAgent"+ (!docInfo.FolderID ? ("&AppID=" + docInfo.AppID) : '');
	var httpObj = new objHTTP();

	var retStatus = httpObj.PostData(request, url);
	Docova.Utils.hideProgressMessage();
	
	if(!retStatus ){return false;}
	
	if(typeof window['Postpaste'] == "function"){
		try{
			Postpaste();
		}catch(e){}
	}			
	
	objView.queryOptions = ViewGetQueryOptions();
	
	if(docInfo.EnableFolderFiltering == "1"){
		$("#divViewContent").css("display", "none");
		objView.Refresh(true, false, true);
		ApplyFolderFilter(true);
		setResizeHandlers();
		$("#divViewContent").css("display", "");
	}else{
		objView.Refresh(true, false, true);
		setResizeHandlers();
	}
}

// ------------------------------------- selection checkbox handlers ------------------------------------------------------

function ViewDocSelectClick(source)
{
	if(!source) {return CancelEvent();}

	currentRow = $(source).closest("TR").get(0);
	parentRow = currentRow;

	if(!$(parentRow).attr("isRecord")) {return CancelEvent();}

	var chkbox = $(currentRow).find("INPUT").get(0);
	if (!$(chkbox).prop("checked"))
	{
		$(parentRow).attr("isChecked", true);
		if (chkbox) {
			objView.ToggleSelectEntryById($(parentRow).prop("id"), "check");
		}
	}
	else
	{
		$(parentRow).attr("isChecked", false);
		if (chkbox) {
			objView.ToggleSelectEntryById($(parentRow).prop("id"), "uncheck");
		}
	}

	return CancelEvent(); //handled
}

//--------------------------------------------- selecting documents by dragging mouse over selection boxes ------
function ViewDocSelectDrag(source, mouseButton)
{
	if(!source) {return false;}

	if (mouseButton == 0 || mouseButton == 1)
	{
		var parentRow  = source.parentElement; //check if the handler should continue
		if((selectDragMode=="check" && parentRow.isChecked) || (selectDragMode=="uncheck" && !parentRow.isChecked))
		{
			return; //handled
		}
		else
		{
			ViewDocSelectClick(source); //call check handler
		}
	}
	return CancelEvent(); //handled
}

 
//--------------------------------------------- column sorting ----------------------------------------------------

function ViewSortColumn(source)
{
	var  colIdx = source.id.split("-")[1];
	objView.ToggleCustomSort(colIdx);
	moveScroll();  //-- in case we resorted while scrolled down the page
	setResizeHandlers();
	return CancelEvent();
}

//--------------------------------------------- expand/collapse category ----------------------------------------------------

function ViewToggleCategory(currentRow)
{
	objView.ToggleCategory(currentRow.id);
	setResizeHandlers();
	return CancelEvent();
}

//-------------------------------------- view header toolbars show/hide handler -------------------------------
function ViewToggleToolbar(toolbar, action, updatecookie) {	
	var toolbarObj = document.getElementById("divToolbar" + toolbar);

	var setcookie = ((typeof updatecookie != "undefined" && updatecookie === false) ? false : true);

	if(typeof action == "undefined"){
		var action = "hide";
		if(jQuery(toolbarObj).css("display") == "none"){
			action = "show";
		}
	}

	if (action == "show") {
		$(toolbarObj).css("display", "");
		try{
			if(setcookie){
				Docova.Utils.setCookie({
					keyname: "FolderToolbar" + toolbar,
					keyvalue: "show",
					httpcookie: true
				});
			}
		}catch(err){}
	} else {
		$(toolbarObj).css("display", "none");
		try{
			if(setcookie){
				Docova.Utils.setCookie({
					keyname: "FolderToolbar" + toolbar,
					keyvalue: "",
					httpcookie: true
				});
			}
		}catch(err){}
	}
	checkAvailableHeight();
}

//---------------------------- keeps the onload folder state persistence ----------------------------------------

function ViewSetOnloadState()
{
	var toolbarSearchDisplay = docInfo.UseContentPaging == "1" ? "show" : Docova.Utils.getCookie({ 
		keyname: "FolderToolbarSearch",
		httpcookie: true
	});
	var toolbarPerspectiveDisplay = Docova.Utils.getCookie({
		keyname: "FolderToolbarPerspective",
		httpcookie: true
	});
	//-- check if there has been an override to the search display 
	if(typeof docInfo.ShowSearch != "undefined"){
		if(docInfo.ShowSearch == "1"){
			toolbarSearchDisplay = "show";
		}else{
			toolbarSearchDisplay = false;			
		}
	}	

	ViewToggleToolbar("Search", toolbarSearchDisplay, false);
	ViewToggleToolbar("Perspective", toolbarPerspectiveDisplay, false);

}
//--------------------------------------------- execute full text search ----------------------------------------------------

function ViewFTSearch(customQuery)
{
	var query;
	if (customQuery) {
		query = customQuery;
	}else{
		query = document.getElementById("inpQuery").value;
		$(divQueryFields).html("") //clear QueryFields div in case residual info exists from a previous advanced search
		$("#MySavedSearches").multiselect("clearSingle");
		$("#btnDelete").button( "option", "disabled", true );
	}

	if(!query)
	{
		alert("Please enter the search query.");
		return CancelEvent(); 
	}
	if (query.indexOf('-') != -1 && (query.indexOf('?') != -1 || query.indexOf('*') != -1)) {
		alert("Searching using both wildcards and hyphens is not supported.\nPlease edit your search to remove either the wildcard characters (*, ?) or the hyphens (-).");
		return CancelEvent();
	}
	//-----Clear current filter and any column filter flags if filtering is on---
	if(docInfo.EnableFolderFiltering == "1"){
		$("#CurrentFilterDiv").html("");
		ClearAllColFilterFlags();
	}		
	objView.queryOptions = ViewGetQueryOptions();
	$("#divSearchQuery").html(query);

	var scope = "";
	if(docInfo.FolderID){
		scope = ($("#inpViewScope").prop("checked"))? "TREE" : "FOLDER";
	}else{
		scope = "VIEW";	
	}

	var tempquery = query;
	//-- look for presence of AND OR NOT in query if found leave it alone
	if(tempquery.match(/\s(and|or|not)\s/i) === null){
		//-- look for presence of spaces dashes forward slash backslash in query if found encapsulate in quotes, unless quote found
		if(tempquery.match(/\s|\-|\/|\\/) !== null && tempquery.match(/"/) === null){
			tempquery = '"' + tempquery + '"'; 
		}
	}
	objView.DoFTSearch(tempquery, scope);

	$("#btnFTClear").button( "option", "disabled", false );

	if(objView.contentPaging) {
		disableContentPaging(true);		
	}
}

//--------------------------------------------- clear full text search ----------------------------------------------------

function ViewFTClear()
{
	objView.queryOptions = ViewGetQueryOptions();
	if(docInfo.EnableFolderFiltering == "1"){
		$("#divViewContent").css("display", "none");
		
		objView.ResetFTSearch();
		$("#btnFTClear").button( "option", "disabled", true );
		ApplyFolderFilter(false);
		$("#divViewContent").css("display", "");
		$("#divSearchQuery").html("");
		$("#divQueryFields").html("");
		Docova.Utils.setField({ field: "inpQuery", value: "" });
		$("#MySavedSearches").multiselect("clearSingle");
		$("#btnDelete").button( "option", "disabled", true );
	}else{
		objView.ResetFTSearch();
		$("#btnFTClear").button( "option", "disabled", true );
		$("#divSearchQuery").html("");
		$("#divQueryFields").html("");
		Docova.Utils.setField({ field: "inpQuery", value: "" });
		$("#MySavedSearches").multiselect("clearSingle");
		$("#btnDelete").button( "option", "disabled", true );
	}
	$("#divSearchResultCount").css("display", "none");
	if(objView.contentPaging) {
		disableContentPaging(false);
	}
}

//-------------------------------------------- save current search -----------------------------------------------
function openSaveSearch(){ 

	//store selected Saved Search so dlg can access
	var ssValue = $("#MySavedSearches").multiselect("getChecked").map(function(){ return this.value; }).get();
	var ssText = $("#MySavedSearches option[value='" + ssValue + "']").text(); 
	dlgParams[0] = "";
	if(ssValue != "") {	dlgParams[0] = ssText; }
	
	retValues.length = 0;
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgSaveSearch?OpenForm"
		
	var dlgSaveSearch = window.top.Docova.Utils.createDialog({
		id: "divDlgSaveSrch", 
		url: dlgUrl,
		title: "Save Search",
		height: 200,
		width: 500, 
		useiframe: true,
		sourcedocument: document,
		sourcewindow: window,
		buttons: [{
			id: "btnSaveUpdate",
			text: "Save As New Search",
			click: function() {	
				if(window.top.$("#divDlgSaveSrchIFrame")[0].contentWindow) {
					window.top.$("#divDlgSaveSrchIFrame")[0].contentWindow.completeWizard();	
				} else {
					window.top.$("#divDlgSaveSrchIFrame")[0].window.completeWizard();	
				}
			}
		},
		{
        	text: "Cancel",	
			click: function() {	dlgSaveSearch.closeDialog();}
      	}]
	});
}

function SaveSearch() {
	var searchkey = Docova.Utils.getField("MySavedSearches");
	var searchquery = document.getElementById("divSearchQuery").innerHTML;
	var queryfields = document.getElementById("divQueryFields").innerHTML;	
	var url = docInfo.ServerUrl + docInfo.PortalWebPath + "/UserDataServices?OpenAgent"
	var action;
	var request="";
	//----- The Update Search checkbox is returned in retVal[1] from the Save Search dialog.  If true, update the search, if false then
	//----- save the search as a new search
	if (retValues[1] == true){
		action = "UPDATESAVEDSEARCH";
	}else{
		action = "ADDSAVEDSEARCH";
	}

	//--build the saved search request
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<SearchName><![CDATA[" + retValues[0] + "]]></SearchName>";
	request += "<SearchKey>" + searchkey + "</SearchKey>";
	request += "<SearchQuery><![CDATA[" + searchquery + "]]></SearchQuery>";
	request += "<QueryFields><![CDATA[" + queryfields + "]]></QueryFields>"
	request += "<LibraryKey>" + docInfo.LibraryKey  + "</LibraryKey>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "</Request>";

	var httpObj = new objHTTP();
	if (httpObj.PostData(request, url))	{
		if (httpObj.status=="OK"){
			RefreshSavedSearches(); //Refreshes the saved searches in the MySavedSearches select field
			if(retValues[1] == true){  //if this was an update to a search...set MySavedSearches field to the current search after the refresh	
				$("#MySavedSearches").multiselect({showSelected: true });
				$("#MySavedSearches").multiselect("refresh");
				$("#MySavedSearches").val(searchkey);
				GetSavedSearch(document.getElementById("MySavedSearches"));
				ViewFTSearch(document.getElementById("divSearchQuery").innerHTML);
			}
			Docova.Utils.messageBox({
				prompt: "Your search was saved.",
				title: "Search Saved"
			});
			window.top.Docova.Utils.closeDialog({
				id: "divDlgSaveSrch"
			});
			window.top.Docova.Utils.closeDialog({
				id: "divDlgAdvancedSrch"
			});
			$("#MySavedSearches").multiselect("refresh");
			return true;
		}
	}
}

//----- Delete a saved search
function DeleteSearch(){		//updated
	var selectObj = document.getElementById("MySavedSearches");
	var SearchName = selectObj.options[selectObj.selectedIndex].text
	delmsgtxt = "You are about to delete the current Saved Search:<br><br><b>" + SearchName + "</b><br><br>Are you sure?"
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: delmsgtxt, 
		icontype: 2, 
		title: "Delete Saved Search", 
		width:400, 
		msgboxtype: 4,
		onNo: function () {
			return;
		},
		onYes: function() {
			var url = docInfo.ServerUrl + docInfo.PortalWebPath + "/UserDataServices?OpenAgent"
			var searchkey = selectObj.value
			var request="";
			//--build the delete search request
			request += "<Request>";
			request += "<Action>DELETESAVEDSEARCH</Action>";
			request += "<SearchKey>" + searchkey + "</SearchKey>";
			request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
			request += "</Request>";
		
			var httpObj = new objHTTP()
			if (httpObj.PostData(request, url))	{
				if (httpObj.status=="OK"){
					RefreshSavedSearches()
					ViewFTClear()
					window.top.Docova.Utils.messageBox({
						title: "Search Deleted",
						prompt: "Your search was deleted.",
						icontype: 4,
						msgboxtype: 0
					});
					return true;
				}
			}
		}
	 });
}

function GetSavedSearch(obj){
	var SearchKey = $(obj).multiselect("getChecked").map(function(){ return this.value;}).get();	
	if(SearchKey == ""){
		$( "#btnDelete" ).button( "option", "disabled", true );
		ViewFTClear();
		return
	}
	$( "#btnDelete" ).button( "option", "disabled", false );
	var url = docInfo.ServerUrl + docInfo.PortalWebPath + "/UserDataServices?OpenAgent"
	var request="";
	var searchArr = new Array();
	var searchQuery;
	var queryfields = "";
	var searchLibUnidList = "";

	//--build the saved search request
	request += "<Request>";
	request += "<Action>GETSAVEDSEARCH</Action>";
	request += "<SearchKey>" + SearchKey + "</SearchKey>";
	request += "</Request>";

	var httpObj = new objHTTP();
	
	if (httpObj.PostData(request, url))	{
		searchArr = (httpObj.status).split(";");
		searchQuery = searchArr[0];
		queryfields = searchArr[1];
		$("#divSearchQuery").html(searchQuery);
		$("#divQueryFields").html(queryfields);
		Docova.Utils.setField({ field: "inpQuery", value: "" });
		ViewFTSearch(searchQuery);
	}
}

function RefreshSavedSearches(){
	var LKey = docInfo.LibraryKey;
	var PortalNsfName = docInfo.PortalNsfName;
	Docova.Utils.dbColumn({ 
		nsfname: PortalNsfName, 
		viewname: "luSavedSearches", 
		key: LKey, 
		column: "2", 
		htmllistbox: "MySavedSearches"
	});
	return;
}

//--------------------------------------------- get additional options for data retrieval ----------------------------------------------------
function ViewGetQueryOptions()
{
objView.disableFreeze = $("#inpViewScope").prop("checked");
var options= ($("#inpViewScope").prop("checked"))? "<viewscope>ST</viewscope>" :"";
objView.viewScope = (options)? "ST": "";
var versionOption = Docova.Utils.getField("selVersionScope");
objView.versionOption =  (versionOption)? versionOption :"";
options += (versionOption)? "<versions>" + versionOption  + "</versions>" :"";
return options;
}

//--------------------------------------------- get additional options for data retrieval ----------------------------------------------------
function ViewApplyQueryOptions()
{
	objView.queryOptions = ViewGetQueryOptions();
	if(objView.isFTSearch)
	{
		ViewFTSearch();
	}
	else
	{
		if(docInfo.EnableFolderFiltering == "1"){
			$("#divViewContent").css("display", "none");
			objView.Refresh(true,true,false);
			ApplyFolderFilter(false);
			setResizeHandlers();
			$("#divViewContent").css("display", "");
		}else{
			objView.Refresh(true,true,false);
			setResizeHandlers();
		}
	}
}

//--------------------------------------------- add doc to favorites ----------------------------------------------------
function ViewAddToFavorites()
{
	var entryObj = objView.GetCurrentEntry();
	if(!entryObj) {return; }
	
	if ( objView.isThumbnails){
		var docId = objView.currentEntry
	}else{
		var docId = entryObj.GetElementValue("docid");
	}

	//--- processing agent url
	var url = docInfo.ServerUrl + docInfo.PortalWebPath + "/UserDataServices?OpenAgent"
	var request="";
	var docDescription = ( objView.isThumbnails)? "" : entryObj.columnValues.join(", ");
	//--build the update request
	request += "<Request>";
	request += "<Action>NEW</Action>";
	request += "<LibraryKey>" + docInfo.LibraryKey  + "</LibraryKey>";
	request += "<Unid>" + docId +  "</Unid>";
	request += "</Request>";
	
	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = xmlobj.find("Result").first().text();
			if(statustext == "OK"){
				var objLeftFrame = window.parent.frames["fraLeftFrame"];	
				if (objLeftFrame){
					objLeftFrame.RefreshFavorites();
				}
				Docova.Utils.messageBox({
					title: "Add to favorites.",
					prompt: "Document was added to your Favorites",
					width: 400,
					icontype: 4,
					msgboxtype: 0
				});				
			}
		},
		error: function(){
			alert("Error:  The document was not added to your favorites.  Please try again or contact the Administrator.");
		}
	});
}

//----------- utility functions ---------------

// clears the view event variables in case mouse cursor had wandered to far
function ViewClearEventState()
{
	var dragSourceColumn=null;
	var dragTargetColumn=null;
	selectDragMode=null;
	return true;
}

function CancelEvent()
{
	if(!window.event) {return;}
	window.event.cancelBubble = true;
	window.event.returnValue=false;
	return false;
}

// ------- context menu handler ---------
function ViewShowContextPopup(popupSource, sourceObject)
{

	if(popupSource=="viewheader" ){
	
	var colNo = parseInt($(sourceObject).attr("colIdx"))	
	curColumnObj = objView.columns[colNo];
	
	var ascIsCheckedIcon = curColumnObj.customSortOrder=="ascending" ? "ui-icon-check" : "";
	var descIsCheckedIcon = curColumnObj.customSortOrder=="descending" ? "ui-icon-check" : "";
	var defaultIsCheckedIcon = curColumnObj.customSortOrder=="none" ? "ui-icon-check" : "";
	var isColCategorizedIcon = curColumnObj.isCategorized ? "ui-icon-check" : "";
	var allowColCustomization = curColumnObj.parentObj.allowCustomization
	colAscSortAction = 'col-' + curColumnObj.colIdx + '-sort-ascending'
	colDescSortAction = 'col-' + curColumnObj.colIdx + '-sort-descending'
	colDefaultSortAction = 'col-' + curColumnObj.colIdx + '-sort-none'
	colCategorizeAction="col-" + curColumnObj.colIdx + "-cat"
	colFreezeAction="col-" + curColumnObj.colIdx + "-freeze"
	colDeleteAction="col-" + curColumnObj.colIdx + "-delete"
	colInsertAction="col-" + curColumnObj.colIdx + "-insert"
	colAppendAction="col-" + curColumnObj.colIdx + "-append"
	colPropertiesAction="col-" + curColumnObj.colIdx + "-properties"

	var menuitems = [];
	if(docInfo.FolderID){
		menuitems = [{
			title: "Sort ascending",
			itemicon: ascIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colAscSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}, {
			title: "Sort descending",
			itemicon: descIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colDescSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}, {
			title: "Default sort",
			itemicon: defaultIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colDefaultSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}, {
			separator: true
		}, {
			title: "Categorize",
			itemicon: isColCategorizedIcon,
			action: "curColumnObj.ProcessContextAction(colCategorizeAction)",
			disabled: !allowColCustomization
		}, {
			separator: true
		}, {
			title: "Delete Column",
			itemicon: "ui-icon-minus",
			action: "curColumnObj.ProcessContextAction(colDeleteAction)",
			disabled: !allowColCustomization
		}, {
			title: "Insert Column",
			itemicon: "ui-icon-arrowthick-1-n",
			action: "InsertAppendEditColumn('insert')",
			disabled: !allowColCustomization
		}, {
			title: "Append Column",
			itemicon: "ui-icon-plus",
			action: "InsertAppendEditColumn('append')",
			disabled: !allowColCustomization
		}, {
			separator: true
		}, {
			title: "Properties",
			itemicon: "ui-icon-gear",
			action: "InsertAppendEditColumn('edit')",
			disabled: !allowColCustomization
		}];			
	}else{
		menuitems = [{
			title: "Sort ascending",
			itemicon: ascIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colAscSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}, {
			title: "Sort descending",
			itemicon: descIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colDescSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}, {
			title: "Default sort",
			itemicon: defaultIsCheckedIcon,
			action: "curColumnObj.ProcessContextAction(colDefaultSortAction)",
			disabled: !curColumnObj.hasCustomSort
		}];				
	}
	
	Docova.Utils.menu({
		delegate: sourceObject,
		width: 170,
		menus: menuitems
	});
	}
}

//-----View Insert a new column-----
function InsertAppendEditColumn(actiontype){
	//actiontype is insert or append, the difference is just to +1 to the colIdx if appending
	if(actiontype == "append"){
		var srcColumnIdx = curColumnObj.colIdx + 1
	}else{
		var srcColumnIdx = curColumnObj.colIdx
	}
	var dlgUrl = curColumnObj.parentObj.columnPropertiesDialogUrl
	if(actiontype != "edit"){ //if append or inserting a column, create a new column obj
		var newColumnObj = new ObjViewColumn();
		curColumnObj = newColumnObj
	}
	
	var divDlgName = "divColPropertiesDlg"
	var dlgColPropertiesDlg = window.top.Docova.Utils.createDialog({
		id : divDlgName,
		url : dlgUrl,
		title: "Column Properties",
		height: 435,
		width: 620,
		autoopen: true,
		useiframe: true,
		sourcewindow: window,
		buttons: {
			"OK": function() {
				if(window.top.$("#" + divDlgName + "IFrame")[0].contentWindow.completeWizard()){
					if(actiontype != "edit"){ //if append or insert then call InsertColumn method of viewobject
						objView.InsertColumn(srcColumnIdx, curColumnObj);
					}
					objView.Refresh(true, true, true);
					setResizeHandlers();
					dlgColPropertiesDlg.closeDialog();
				}
			},
			Cancel: function() {
				dlgColPropertiesDlg.closeDialog();
			}
  		}
     });
}

//==========================================================================================
// View submenu
//==========================================================================================
function CreateViewSubmenu(actionButton) //creates right-click context menu
{
	if(!actionButton) {return}

	var isSearchOn = ($("#divToolbarSearch").css("display") == "")
	var isPerspectiveOn = ($("#divToolbarPerspective").css("display")=="");

	Docova.Utils.menu({
		delegate: $(actionButton),
		width: 170,
		menus: [{
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC033 : "Search Bar"),
				itemicon: "ui-icon-search",
				action: "viewbtnSearchBar()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC034 : "View Options Bar"),
				itemicon: "ui-icon-check",
				action: "ViewToggleToolbar('Perspective')"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC035 : "Folder Information"),
				itemicon: "ui-icon-info",
				action: "viewbtnFolderInfo()",
				disabled: docInfo.isRecycleBin
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC036 : "Refresh"),
				itemicon: "ui-icon-refresh",
				action: "viewbtnRefresh()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC037 : "Expand All"),
				itemicon: "ui-icon-plus",
				action: "objView.ExpandAll()",
				disabled: !objView.isCategorized
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC038 : "Collapse All"),
				itemicon: "ui-icon-minus",
				action: "objView.CollapseAll()",
				disabled: !objView.isCategorized
			}
		]
	})
	return false;
}

//----- View button menu functions -----
function viewbtnSearchBar(){
		ViewToggleToolbar("Search");
		if(objView.isFTSearch) {ViewFTClear();}
}

function viewbtnRefresh(){
	if(docInfo.EnableFolderFiltering == "1"){
		$("#divViewContent").css("display", "none")
		objView.Refresh(true,false,true,false,false, true)
		$("#divViewContent").css("display", "")
	}else{
		objView.Refresh(true,false,true);
	}
}

function viewbtnFolderInfo(){
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wFolderInfo?OpenForm&ParentUNID=" + docInfo.DocID;
	var folderInfoDialog = window.top.Docova.Utils.createDialog({
		id: "divFolderInfo", 
		url: dlgUrl,
		title: "Folder Information",
		height: 400,
		width: 700, 
		useiframe: true,
		sourcedocument: document,
		buttons: {
        		"Close": function() {
				folderInfoDialog.closeDialog();
        		}
      	}
	});
}

//==========================================================================================
// Tools submenu
//==========================================================================================
function CreateToolsSubmenu(actionButton) //creates right-click contect menu
{
	if(!actionButton) {return;}
	
	var objEntry = objView.GetCurrentEntry();	
	//----- vars for enabling/disabling menu options
	var showImportFiles = !docInfo.isRecycleBin && docInfo.CanCreateDocuments;
	var showExportFiles = !docInfo.isRecycleBin;
	var showImportMessages = !docInfo.isRecycleBin && docInfo.CanCreateDocuments;
	var flags = (objEntry ? objEntry.GetElementValue("flags") : false);
	var isBookmark = (objEntry ? (!objEntry.GetElementAttribute("bmk/img", "src") == "") : false);
	var showBookmark = (!docInfo.isRecycleBin) && (!isBookmark) && (objEntry && objEntry.isRecord) && (!docInfo.DisableBookmarks);
	var showForwardDocument = !docInfo.isRecycleBin && (objEntry && objEntry.isRecord) && (flags & 128);
	var showChangeDocType = !docInfo.isRecycleBin && docInfo.DocAccessLevel>="6" && !isBookmark && objView.currentEntry;
	var canSavePerspective = false;
	//Determine if user can see Delete Perspective menu option
	var canDeletePerspective = false;
	var el = document.getElementById("xmlViewPerspective");
	var perspectiveXml = el.textContent || el.innerText || el.nodeValue || el.innerHTML;	
	var parser = new DOMParser;
	var perspectiveDoc = parser.parseFromString(perspectiveXml, "text/xml");
	if(perspectiveDoc)
		{
			var typeNode  = perspectiveDoc.selectSingleNode("viewperspective/type");
			var typeNodeText = typeNode.textContent || typeNode.text;
			var authorNode = perspectiveDoc.selectSingleNode("viewperspective/createdby");
			var authorNodeText = authorNode.textContent || authorNode.text;
			if(typeNodeText == "system"){ //Can't delete if it is a system perspective
				canDeletePerspective = false;
			}else{
				if(docInfo.DocAccessLevel >= "6" || authorNodeText == docInfo.UserNameAB){ // Can delete if access level is >= 6 or user is the creator of the perspective
					canDeletePerspective = true;
				}
			}
		}
	canSavePerspective = (docInfo.DocAccessLevel>="6" && !objView.isSummary)? true : false;	
	var showEditSavePerspective = !docInfo.isRecycleBin && canSavePerspective;
	var showCanDeletePerspective = !docInfo.isRecycleBin && canDeletePerspective;
	var showFolderProperties = !docInfo.isRecycleBin && !docInfo.IsDOE;
	var showOpenFolderArchive = !docInfo.isRecycleBin;
	var showArchiveSelected =  !docInfo.isRecycleBin && (docInfo.DocAccessLevel>="6");
	var showCompare = !docInfo.IsDOE;
	
	//-----Build menu -----
	Docova.Utils.menu({
		delegate: $(actionButton),
		width: 210,
		menus: [{
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC015 : "Export to Excel"),
				itemicon: "ui-icon-arrowthickstop-1-s",
				action: "ViewExportToExcel()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC016 : "Import Files"),
				itemicon: "ui-icon-arrowthickstop-1-n",
				action: "ImportFiles()",
				disabled: !showImportFiles
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC017 : "Import Folder"),
				itemicon: "ui-icon-folder-collapsed-1-n",
				action: "ImportFolder()",
				disabled: !showImportFiles
			},
			{
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC018 : "Export Files"),
				itemicon: "ui-icon-circle-arrow-s",
				action: "ExportFiles()",
				disabled: !showExportFiles
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC019 : "Import Messages"),
				itemicon: "ui-icon-circle-arrow-n",
				action: "ImportMessages()",
				disabled: !showImportMessages
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC020 : "Send Email Notification"),
				itemicon: "ui-icon-extlink",
				action: "TriggerSendDocumentMessage()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC021 : "Forward Document"),
				itemicon: "ui-icon-arrowthick-1-ne",
				action: "ForwardDocument()",
				disabled: !showForwardDocument
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC022 : "Compare Documents"),
				itemicon: "ui-icon-transferthick-e-w",
				action: "CompareSelectedWordDocuments()",
				disabled: !showCompare
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC023 : "Copy Link"),
				itemicon: "ui-icon-link",
				action: "CopyLink()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC024 : "Create Bookmark"),
				itemicon: "ui-icon-bookmark",
				action: "CreateBookmark()",
				disabled: !showBookmark
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC025 : "Edit/Save Perspective"),
				itemicon: "ui-icon-circle-check",
				action: "ViewSavePerspective()",
				disabled: !showEditSavePerspective
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC026 : "Delete Perspective"),
				itemicon: "ui-icon-circle-close",
				action: "ViewDeletePerspective()",
				disabled: !showCanDeletePerspective
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC027 : "Folder Properties"),
				itemicon: "ui-icon-gear",
				action: "ShowFolderProperties()",
				disabled: !showFolderProperties
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC028 : "Open Folder Archive"),
				itemicon: "ui-icon-folder-open",
				action: "ShowFolderArchive()",
				disabled: !showOpenFolderArchive
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC029 : "Archive Selected Documents"),
				itemicon: "ui-icon-tag",
				action: "ArchiveSelected()",
				disabled: !showArchiveSelected
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC030 : "Change Document Type"),
				itemicon: "ui-icon-newwin",
				action: "ChangeDocType()",
				disabled: !showChangeDocType
			}
		]
	});

}

function ViewExportToExcel(){
		var docids = objView.selectedEntries;
		var selectedonly = (docids.length > 0);
		objView.ExportToExcel(selectedonly);
}

/**********************************************************************************************
 * function: ImportFolder
 * Description: allows imort of a folder tree into docova.
 * Output: boolean - true if no errors
 *********************************************************************************************/
function ImportFolder() {
	var result = false;
	if (!window.top.Docova.IsPluginAlive & !docInfo.IsDOE) {
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the bulk exporting of files.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;
	}
	//------- get library url ----------------
	var LibraryUrl = "/" + docInfo.NsfName
	if (LibraryUrl == "") {
		return (false);
	}
	var doc = document.all;
	//------- get current library id ----------
	var libraryid = docInfo.LibraryKey;
	//------- get highlighted folder id ---------
	var folderid = docInfo.FolderID;
	var folderUNID = docInfo.DocID;
	var uiws = Docova.getUIWorkspace(document);
	var folderControlWin = uiws.getDocovaFrame("foldercontrol", "window");
	var fpath = ""
	if (folderControlWin) {
		fpath = folderControlWin.DLITFolderView.getFolderPathByID(folderid);
	}
	var limit = "";
	if (docInfo.LimitPathLength)
		limit = docInfo.LimitPathLength
	//-------------------------- display file add dialog
	var dlgUrl = LibraryUrl + "/" + "dlgFolderImport?OpenForm&ParentUNID=" + folderUNID;
	Docova.Utils.createDialog({
		url: dlgUrl,
		id: "dialogFolderImport",
		useiframe: true,
		autoopen: true,
		title: "Import Folder",
		width: "600",
		height: "260",
		buttons: [{
			text: "Continue",
			icons: {
				primary: "ui-icon-check"
			},
			click: function () {
				var iwin = $(this).find("iframe")[0].contentWindow;
				var incSubfolder = iwin.$("input[name='IncludeSubfolders']:checked").val();
				var rootFolder = iwin.$("#OutputFolder").val();
				var doctypekey = iwin.$("#DocumentTypeKey").val();
				if (doctypekey == "" || doctypekey == "N") {
					alert("Please select a document type before continuing!");
					return;
				}
				if (rootFolder == "") {
					alert("Please select source folder before continuing!");
					return;
				}
				Docova.Utils.closeDialog({
					id: "dialogFolderImport",
					useiframe: true
				});
				window.top.DocovaExtensions.ImportFolderTree({
					"RootFolder": rootFolder,
					"LibraryUrl": LibraryUrl,
					"ParentFolderUNID": folderUNID,
					"IncludeSubfolders": incSubfolder,
					"FolderPath": fpath,
					"FolderPathLimit": limit,
					"DocumentType": doctypekey,
					onDone: function() {
						var uiws = Docova.getUIWorkspace(document);
						var folderControlWin = uiws.getDocovaFrame("foldercontrol", "window");
						if (folderControlWin) {
							folderControlWin.DLITFolderView.RefreshFolder(folderid);
						}
					}
				});
			}
		}, {
			text: "Cancel",
			icons: {
				primary: "ui-icon-cancel"
			},
			click: function () {
				Docova.Utils.closeDialog({
					id: "dialogFolderImport",
					useiframe: true
				});
			}
		}]
	});
}
//============ import files dialog ===============
function ImportFiles() {
	if(!window.top.Docova.IsPluginAlive && !docInfo.IsDOE){
		window.top.Docova.Utils.messageBox({
			title: "DOCOVA Plugin Not Running",
			prompt: "The DOCOVA Plugin is required for the bulk importing of files.",
			width: 400,
			icontype: 4,
			msgboxtype: 0
		});
		return false;	
	}		
	
	var dlgUrl =docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgFileImport?OpenForm&ParentUNID=" + docInfo.DocID + "&folderid=" + docInfo.FolderID;
	
	window.top.Docova.Utils.createDialog({
		 url:dlgUrl,
		 id: "dialogFileImport" ,
		 useiframe: true,
		 autoopen: true,
		 title: "Import Files",
		 width: "750",
		 height:"440",
		 buttons: [{
			 text: "Finish",
			 icons: {primary: "ui-icon-check"},
			 click: function() {
				var iwin = $(this).find("iframe")[0].contentWindow;
				iwin.completeWizard(function()
				{
					 window.top.Docova.Utils.closeDialog({id: "dialogFileImport", useiframe:true});
					 objView.Refresh(true, false, true);
				});
			 }
		 	},
		 	{
			 text:"Cancel",
			 icons: { primary: "ui-icon-cancel"},
			 click: function(){
				 window.top.Docova.Utils.closeDialog({id: "dialogFileImport", useiframe:true});
			 }
		 	}]
		});
	
	
	
}

//============ export folder dialog ===============
function ExportFiles()
{
	if(!window.top.Docova.IsPluginAlive && !docInfo.IsDOE){
			window.top.Docova.Utils.messageBox({
				title: "DOCOVA Plugin Not Running",
				prompt: "The DOCOVA Plugin is required for the bulk exporting of files.",
				width: 400,
				icontype: 4,
				msgboxtype: 0
			});
			return false;	
	}		

	var dlgInputs = new Array();
	dlgInputs.push(window);
	dlgInputs.push(objView);
	
	window.top.Docova.GlobalStorage["dialogFileExport"] = { "dlginp" : dlgInputs };
	
	var dlgUrl =docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgFileExport?OpenForm&ParentUNID=" + docInfo.DocID + "&folderid=" + docInfo.FolderID + "&currentonly=" + ((docInfo.DocAccessLevel > 2) ? "0" : "1")
	
	var expdialog = window.top.Docova.Utils.createDialog({
		 url:dlgUrl,
		 id: "dialogFileExport" ,
		 useiframe: true,
		 autoopen: true,
		 title: "Export Files",
		 width: "450",
		 height:"415",
		 buttons: [{
			text: "Export",
			icons: {primary: "ui-icon-check"},
			click: function() {
				var iwin = $(this).find("iframe")[0].contentWindow;
				if ( iwin.exportFiles() ){
      						
					window.top.Docova.Utils.closeDialog({id: "dialogFileImport", useiframe:true});
			
					return true;
				}
			 }
		 	},
		 	{
				text:"Cancel",
				icons: { primary: "ui-icon-cancel"},
				click: function(){
					window.top.Docova.Utils.closeDialog({id: "dialogFileExport", useiframe:true});
				}
		 	}]
		});
	

}

//============ import records from file dialog ===============
function ImportFromFile() {
	
	var dlgUrl =docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgImportFromFile?OpenForm" + (!docInfo.FolderID ? ("&appid=" + docInfo.AppID) : ("&folderid=" + docInfo.FolderID)) ;
	
	window.top.Docova.Utils.createDialog({
		 url:dlgUrl,
		 id: "dialogImportFromFile" ,
		 useiframe: true,
		 autoopen: true,
		 title: "Import From File",
		 width: "750",
		 height:"425",
		 buttons: [{
			 text: "Import",
			 icons: {primary: "ui-icon-check"},
			 click: function() {
				var iwin = $(this).find("iframe")[0].contentWindow;
				iwin.completeWizard(function(result)
				{
					var stats = "";
					var rescode = "FAILED";
					var errmsg = "";
					
					if(jQuery.trim(result) != ""){
						try{
							var xmlresp = jQuery.parseXML(result);
							rescode = jQuery.trim(jQuery(xmlresp).find("Result[ID=Status]").text());
							if(rescode == "OK"){
								stats = jQuery(xmlresp).find("Stats").text();
							}else{
								errmsg = jQuery(xmlresp).find("ErrMsg").text();
							}							
						}catch(e){}
					}
					
					if(rescode == "OK"){
						window.top.Docova.Utils.closeDialog({id: "dialogImportFromFile", useiframe:true});
						if(jQuery.trim(stats) !== ""){
							window.top.Docova.Utils.messageBox({
								'icontype' : 4,
								'msgboxtype' : 0,
								'title' : "Import from File Results:",
								'prompt' : stats
							});
						}					 
						objView.Refresh(true, false, true);						
					}else{
						window.top.Docova.Utils.messageBox({
							'icontype' : 1,
							'msgboxtype' : 0,
							'title' : "Import from File Error:",
							'prompt' : errmsg
						});						
					}

				});
			 }
		 	},
		 	{
			 text:"Cancel",
			 icons: { primary: "ui-icon-cancel"},
			 click: function(){
				 window.top.Docova.Utils.closeDialog({id: "dialogImportFromFile", useiframe:true});
			 }
		 	}]
		});
}



//==========================================================================================
// Edit submenu
//==========================================================================================

function CreateEditSubmenu(actionButton) //creates drop down menu
{
	if(!actionButton) {return}

	var isthumbnailview =  objView.isThumbnails;
	var showClipActions = !isthumbnailview && !docInfo.isRecycleBin && !docInfo.CutCopyPaste;
	var showCut = showClipActions && docInfo.CanDeleteDocuments && docInfo.CanCreateDocuments;
	var showPaste = showClipActions && Docova.Utils.getCookie({ keyname: "clipdata" }) != "" && docInfo.CanCreateDocuments;
	var canDelete = !isthumbnailview && !docInfo.isRecycleBin && (objView.currentEntry !="" || objView.selectedEntries.length > 0)  && docInfo.CanSoftDeleteDocuments && docInfo.CanCreateDocuments && docInfo.CanDeleteDocuments;

	Docova.Utils.menu({
		delegate: $(actionButton),
		width: 170,
		menus: [{
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC010 : "Cut"),
				itemicon: "ui-icon-scissors",
				action: "ViewCutSelected()",
				disabled: !showCut
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC011 : "Copy"),
				itemicon: "ui-icon-copy",
				action: "ViewCopySelected()",
				disabled: !showClipActions
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC012 : "Paste"),
				itemicon: "ui-icon-clipboard",
				action: "ViewPasteSelected()",
				disabled: !showPaste
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC039 : "Select All"),
				itemicon: "ui-icon-check",
				action: "objView.SelectAllEntries()"
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC040 : "Deselect All"),
				itemicon: "ui-icon-minus",
				action: "objView.DeselectAllEntries()"
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC032 : "Delete Selected"),
				itemicon: "ui-icon-close",
				action: "ViewDeleteSelected()",
				disabled: !canDelete
			}
		]
	});
}

function ViewDeleteSelected(){
	if(docInfo.EnableFolderFiltering == "1"){
		objView.DeleteSelectedEntries();
		$("#divViewContent").css("display", "none")		
		ApplyFolderFilter(true);
		$("#divViewContent").css("display", "")
	}else{
		objView.DeleteSelectedEntries();
	}
}

function ViewSortThumbnails(id){
	
	objView.ToggleThumbnailSort(id);
}

//thumbnails submenu
function CreateThumbnailsSubmenu(event, clickObj){

	if(!clickObj) {return}
	
	var winwidth = $(window).width();
	var winheight = $(window).height();
	var posX = event.pageX;
	var posY = event.pageY; 
	
	var menuwidth = 170;
	var menuheight = 200;

	if((posY + menuheight) > winheight){
		shiftY = winheight - (posY+menuheight)
	}else{
		shiftY = 2; //default
	}

	if((posX + menuwidth) > winwidth){
		shiftX = winwidth - (posX+menuwidth)
	}else{
		shiftX = 1; //default
	}

	
	Docova.Utils.menu({
		delegate: $(clickObj),
		width: 190,
		position: "XandY",
		shiftX: shiftX,
		shiftY: shiftY,		
		menus: [{
				title: "Open File",
				itemicon: "ui-icon-file",
				action: "ProcessEntrySubmenuAction('openfilethumb')"
			}, {
				title: "Open Document",
				itemicon: "ui-icon-copy",
				action: "ProcessEntrySubmenuAction('open')"
			}, {
				title: "Open Document in New Window",
				itemicon: "ui-icon-clipboard",
				action: "ProcessEntrySubmenuAction('openwindow')"
			}, {
				separator: true
			}, {
				title: "Add to favorites",
				itemicon: "ui-icon-check",
				action: "ProcessEntrySubmenuAction('favorites')"
			}, {
				title: "Copy Link",
				itemicon: "ui-icon-minus",
				action: "ProcessEntrySubmenuAction('copylink')"
			},
		]
	});
}


//==========================================================================================
// Entry submenu
//==========================================================================================

function CreateEntrySubmenu(clickObj) //creates right-click context menu
{
	if(!clickObj) {return}

	var objEntry = objView.GetCurrentEntry();	
	var showClipActions = !docInfo.isRecycleBin && !docInfo.CutCopyPaste;
	var isBookmark = (objEntry ? (!objEntry.GetElementAttribute("bmk/img", "src") == "") : false);
	var showBookmark = !docInfo.isRecycleBin && !isBookmark && (objEntry && objEntry.isRecord) && !docInfo.DisableBookmarks; 
	var showChangeDocType = !docInfo.isRecycleBin && docInfo.DocAccessLevel>="6" && !isBookmark && objView.currentEntry;	
	var showChangeDocStatus = !docInfo.isRecycleBin && docInfo.DocAccessLevel>="6" && !isBookmark && objView.currentEntry;	
	var showCut = showClipActions && docInfo.CanDeleteDocuments;
	var showPaste = showClipActions && (Docova.Utils.getCookie({ keyname: "clipdata" }) != "");
	var showDelete = !docInfo.isRecycleBin && docInfo.CanSoftDeleteDocuments && docInfo.CanDeleteDocuments;

	var winwidth = $(window).width();
	var winheight = $(window).height();
	var posX = clickObj.pageX;
	var posY = clickObj.pageY; 
	var menuwidth = (docInfo.FolderID ? 240 : 150);
	var menuheight = (docInfo.FolderID ? 360 : 150);

	if((posY + menuheight) > winheight){
		shiftY = winheight - (posY+menuheight);
	}else{
		shiftY = 2; //default
	}

	if((posX + menuwidth) > winwidth){
		shiftX = winwidth - (posX+menuwidth);
	}else{
		shiftX = (docInfo.FolderID ? 1 : 5); //default
	}
	var menulist = [];
	if(docInfo.FolderID){
		menulist = [{
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC001 : "Open"),
				itemicon: "ui-icon-document",
				action: "ProcessEntrySubmenuAction('open')",
				disabled: docInfo.isRecycleBin
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC002 : "Restore"),
				itemicon: "ui-icon-newwin",
				action: "ProcessEntrySubmenuAction('restore')",
				disabled: !docInfo.isRecycleBin
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC003 : "Edit"),
				itemicon: "ui-icon-pencil",
				action: "ProcessEntrySubmenuAction('edit')",
				disabled: docInfo.isRecycleBin
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC004 : "Print Attachments"),
				itemicon: "ui-icon-print",
				action: "ProcessEntrySubmenuAction('printattachments')",
				disabled: docInfo.isRecycleBin
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC005 : "Add to Favorites"),
				itemicon: "ui-icon-heart",
				action: "ProcessEntrySubmenuAction('favorites')",
				disabled: docInfo.isRecycleBin
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC006 : "Copy Link"),
				itemicon: "ui-icon-link",
				action: "ProcessEntrySubmenuAction('copylink')",
				disabled: docInfo.isRecycleBin
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC007 : "Create Bookmark"),
				itemicon: "ui-icon-bookmark",
				action: "ProcessEntrySubmenuAction('createbookmark')",
				disabled: !showBookmark
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC008 : "Change Document Type"),
				itemicon: "ui-icon-newwin",
				action: "ProcessEntrySubmenuAction('changedoctype')",
				disabled: !showChangeDocType
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC009 : "Release Document(s)"),
				itemicon: "ui-icon-extlink",
				action: "ProcessEntrySubmenuAction('changedocstatus')",
				disabled: !showChangeDocStatus
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC010 : "Cut"),
				itemicon: "ui-icon-scissors",
				action: "ProcessEntrySubmenuAction('cut')",
				disabled: !showCut
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC011 : "Copy"),
				itemicon: "ui-icon-copy",
				action: "ProcessEntrySubmenuAction('copy')",
				disabled: !showClipActions
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC012 : "Paste"),
				itemicon: "ui-icon-clipboard",
				action: "ProcessEntrySubmenuAction('paste')",
				disabled: !showPaste
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC013 : "Delete"),
				itemicon: "ui-icon-closethick",
				action: "ProcessEntrySubmenuAction('delete')",
				disabled: !showDelete
			}, {
				separator: true
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC014 : "Properties"),
				itemicon: "ui-icon-gear",
				action: "ProcessEntrySubmenuAction('properties')"
			}];	
	}else{
		menulist = [{ 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC001 : "Open"), 
				itemicon: "ui-icon-document", 
				action: "ProcessEntrySubmenuAction('open')" 
			}, {
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC003 : "Edit"),
				itemicon: "ui-icon-pencil",
				action: "ProcessEntrySubmenuAction('edit')",
				disabled: docInfo.isRecycleBin
			}, { 
				separator: true 
			}, { 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC011 : "Copy"), 
				itemicon: "ui-icon-clipboard", 
				action: "Docova.getUIView().copy({ type: 'current' })" 
			}, { 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC031 : "Copy selected"), 
				itemicon: "ui-icon-check", 
				action: "Docova.getUIView().copy({ type: 'selected' })" 
			}, { 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC012 : "Paste"), 
				itemicon: "ui-icon-copy", 
				action: "Docova.getUIView().paste()" 
			}, { 
				separator: true 
			}, { 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC013 : "Delete"), 
				itemicon: "ui-icon-closethick", 
				action: "ProcessEntrySubmenuAction('delete')" 
			}, { 
				title: (typeof prmptMessages !== "undefined" ? prmptMessages.msgVC032 : "Delete selected"), 
				itemicon: "ui-icon-circle-close", 
				action: "ProcessEntrySubmenuAction('deleteselected')" 
			}];			
		
	}

	Docova.Utils.menu({
		delegate: clickObj,
		width: menuwidth,
		height: menuheight,
		position: "XandY",
		shiftX: shiftX,
		shiftY: shiftY,		
		menus: menulist
	});
}


//==========================================================================================
// Entry submenu handler
//==========================================================================================

function ProcessEntrySubmenuAction(action) //handle action from contect menu
{
	if(action == "" ) {return false};

	var entryObj = objView.GetCurrentEntry();
	if(!entryObj) {return; }
	
	if ( objView.isThumbnails ){
		var recType = null;
	}else{
		var recType = entryObj.GetElementValue("rectype");
	}

	if(action=="open"){
		if (objView.isAppView) {
			disableOpenDocInEditMode = true;
		}
		ViewLoadDocument();
	}
	else if ( action=="openfile"){
		var fname = entryObj.filename;
		OpenFileWindow(fname);
	}
	else if ( action=="openfilethumb"){
		var fname = $(entryObj.parentRow).attr("filename");
		OpenFileWindow(fname);
	}
	else if(action=="edit"){
		ViewLoadDocument("","","", true);
	}
	else if(action=="printattachments"){
		printAttachments();
	}		
	else if(action=="favorites"){
		ViewAddToFavorites();
	}
	else if(action=="copylink"){
		CopyLink(objView.currentEntry);
	}
	else if(action=="cut"){
		ViewCutSelected();
	}
	else if(action=="copy"){
		ViewCopySelected();
	}	
	else if(action=="paste"){
		ViewPasteSelected();
	}
	else if(action=="delete"){
		if(docInfo.FolderID){
			if (docInfo.isRecycleBin) {
				objView.RemoveSelectedEntries(true)
			} else {
	
				if (docInfo.EnableFolderFiltering == "1") {
					objView.DeleteSelectedEntries(true);
					$("#divViewContent").css("display", "none")
					ApplyFolderFilter(true);
					$("#divViewContent").css("display", "")
				}else{
					objView.DeleteSelectedEntries(true);
				}		
			}		
		}else{
			//if this is a system view then delete design doc and related system design elements
			if(docInfo.ViewName=="AppForms"){  //delete form
				DeleteDesignElement("form", objView.currentEntry)
			}else if(docInfo.ViewName=="AppSubForms"){	//delete subform
				DeleteDesignElement("subform", objView.currentEntry)
			}else if(docInfo.ViewName=="AppLayouts"){ //delete layout
				objView.RemoveSelectedEntries(true);
			}else if(docInfo.ViewName=="AppViews"){ //delete view
				DeleteDesignElement("view", objView.currentEntry)
			}else if(docInfo.ViewName=="AppPages"){ //delete page
				DeleteDesignElement("page", objView.currentEntry)
			}else if(docInfo.ViewName=="AppOutlines"){ //delete outline
				objView.RemoveSelectedEntries(true);
			}else if(docInfo.ViewName=="AppFiles"){ //delete image
				DeleteDesignElement("image", objView.currentEntry)	
			}else if(docInfo.ViewName=="luWorkflow"){ //delete workflow
				DeleteDesignElement("workflow", objView.currentEntry)
			}else if(docInfo.ViewName=="AppJS"){ //delete JS Library
				DeleteDesignElement("jslib", objView.currentEntry)
			}else if(docInfo.ViewName=="AppCSS"){ //delete CSS
				DeleteDesignElement("css", objView.currentEntry)	
			}else{
				objView.RemoveSelectedEntries(true);
			}
		}
	}
	else if(action == "deleteselected"){
		if(!docInfo.FolderID){
			if(docInfo.ViewName=="AppForms"){  //delete form
				DeleteSelectedDesignElements("form", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppSubForms"){	//delete subform
				DeleteSelectedDesignElements("subform", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppLayouts"){ //delete layout
				objView.RemoveSelectedEntries();
			}else if(docInfo.ViewName=="AppViews"){ //delete view
				DeleteSelectedDesignElements("view", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppPages"){ //delete page
				DeleteSelectedDesignElements("page", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppOutlines"){ //delete outline
				objView.RemoveSelectedEntries();
			}else if(docInfo.ViewName=="AppFiles"){ //delete image
				DeleteSelectedDesignElements("image", objView.selectedEntries)	
			}else if(docInfo.ViewName=="luWorkflow"){ //delete workflow
				DeleteSelectedDesignElements("workflow", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppJS"){ //delete JS Library
				DeleteDesignElement("jslib", objView.selectedEntries)
			}else if(docInfo.ViewName=="AppCSS"){ //delete CSS
				DeleteDesignElement("css", objView.selectedEntries)	
			}else{
				objView.RemoveSelectedEntries();
			}
		}		
	}
	else if(action=="changedoctype"){
		ChangeDocType(objView.currentEntry);
	}
	else if(action=="changedocstatus"){
		ChangeDocStatus(objView.currentEntry);
	}
	else if(action =="createbookmark")
	{
		CreateBookmark();
	}
	else if(action=="restore")	{
		objView.UndeleteSelectedEntries(true);
		try	{
			parent.frames['fraLeftFrame'].ReloadLibraryByID( docInfo.LibraryKey, "", false);
		}
		catch (e){}
	}
	else if(action=="properties"){
		if ( objView.isThumbnails ){
			ShowDocumentProperties();
			return;
		}
		if(recType == "fld")
		{
			ShowFolderProperties();
		}
		else if(recType == "doc")
		{
			ShowDocumentProperties();
		}
	}

	return;
}

//Change status of selected documents
function ChangeDocStatus(currentEntry)
{
	if ( currentEntry == undefined ) {
		currentEntry = objView.currentEntry
		if(objView.selectedEntries.length == 0 && ( currentEntry == undefined  || currentEntry == "" )){
			window.top.Docova.Utils.messageBox({
				title: "Choose a Document",
				prompt: "Please select at least one document to release.",
				icontype: 1,
				width: 400,
				msgboxtype: 0
			});
			return false;
		}
	}
	
	if (confirm("Are you sure that you want to Release the "+(objView.selectedEntries.length > 0 ? objView.selectedEntries.length + " " : "") +"selected document(s)?")==false);
			return;
	selStatus="Released"
	var request="";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>CHANGEDOCSTATUS</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Status>" + selStatus+ "</Status>";
		
	if(objView.selectedEntries.length> 0){
			for(var k=0; k<objView.selectedEntries.length; k++){
				request += (objView.selectedEntries[k])? "<Unid>" + objView.selectedEntries[k] +  "</Unid>" :"";
			}
	}else
	{
		request += "<Unid>" + currentEntry+  "</Unid>" ;
	}
	request += "</Request>";
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"

	var httpObj = new objHTTP();
	ShowProgressMessage("Processing request. Please wait...");
	var retVal=false;	
	if(httpObj.PostData(request, url))
	{
		if(httpObj.status=="OK") {
			retVal=true;
		}
	}
	HideProgressMessage();
	objView.Refresh(true, false, true);
	setResizeHandlers();
	return retVal;	
}//--end ChangeDocStatus



//------------------------------------ document properties dialog -------------------------------
function ShowDocumentProperties(docId)
{
	var targetId = (docId)? docId : objView.currentEntry;
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName+ "/dlgDocumentProperties?OpenForm&ParentUNID=" + targetId + "&mode=R";
	var propDialog = window.top.Docova.Utils.createDialog({
		id: "divDocProperties", 
		url: dlgUrl,
		title: "Document Properties",
		height: 400,
		width: 700, 
		useiframe: true,
		sourcedocument: document,
		buttons: {
        		"Close": function() {
				propDialog.closeDialog();
        		}
      	}
	});
}

//------------------------------------ folder properties dialog -------------------------------
function ShowFolderProperties(folderId, forceMode)
{
	if (!docInfo.isRecycleBin) {
		var targetID = (folderId)? folderId : docInfo.DocKey;
	}
	else {
		var entryObj = objView.GetCurrentEntry();
		var targetID = entryObj.entryId;
	}
	var isManager = (docInfo.DocAccessLevel>="6");
	var mode = (isManager && !docInfo.isRecycleBin)? "E" : "R";
	mode = (forceMode)? forceMode : mode;
	var DLITFolderView = parent.frames['fraLeftFrame'].DLITFolderView;
	if(DLITFolderView){
			DLITFolderView.ShowProperties({"folderid" : targetID, "mode" : mode});
	}
}//--end ShowFolderProperties


//------------------------------------ folder archive dialog -------------------------------
function ShowFolderArchive()
{
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName+ "/dlgFolderArchive?OpenForm&parentUNID=" + docInfo.DocID;
	dlgUrl += ($("#inpViewScope").prop("checked"))? "&viewscope=ST" : "&viewscope=";
	var folderArchiveDialog = window.top.Docova.Utils.createDialog({
		id: "divDlgFolderArchive", 
		url: dlgUrl,
		title: "Folder Archive",
		height: 550,
		width: 750, 
		useiframe: true,
		sourcedocument: document,
		buttons: {
			"Restore Selected Documents": function(){
				var dlgDoc = window.top.$("#divDlgFolderArchiveIFrame")[0].contentWindow.document;
				var dlgWindow = window.top.$("#divDlgFolderArchiveIFrame")[0].contentWindow;
				var ArchiveData = dlgWindow.ArchiveData
				var rs = ArchiveData.recordset;
				var idList = "";
				rs.MoveFirst();
				while(!rs.EOF()){
					if(rs.getFIELDSCount() > 0){
						if(rs.Fields("Selected").getValue()=="1"){
					 		idList += "<Unid>" + rs.Fields("docid").getValue() + "</Unid>";
						}
					}
					rs.MoveNext()
				}
				if(idList == ""){
					window.top.Docova.Utils.messageBox({
						title: "Nothing Selected",
						prompt: "You have not selected any documents to restore. Please select one or more documents to restore or Cancel",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}else{
					folderArchiveDialog.closeDialog();
					RestoreArchivedDocuments(idList)
				}
			},
        	"Cancel": function() {
				folderArchiveDialog.closeDialog();
        	}
      	}
	});
}

function RestoreArchivedDocuments(idList){
	var request="";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>RESTOREARCHIVED</Action>";
	request += "<UserName>" + docInfo.UserNameAB + "</UserName>";
	request += idList;
	request += "</Request>";

	//--- processing agent url
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent"
	Docova.Utils.showProgressMessage("Restoring selected document(s). One moment...");
	jQuery.ajax({
		type: "POST",
		url: url,
		data: request,
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var resulttext = xmlobj.find("Result").first().text();
			if(resulttext == "OK"){
				Docova.Utils.messageBox({
					title: "Documents Restored",
					prompt: "The selected documents were restored from the Archive.",
					icontype: 4,
					msgboxtype: 0,
					width: 400
				});
			Docova.Utils.hideProgressMessage();
			objView.Refresh(true,false,true);	
			setResizeHandlers();
			}
		},
		error: function(){
			Docova.Utils.hideProgressMessage();
			alert("Error: One or more documents were not restored.  Please try again or contact the Administrator.");
		}
	})	
}

//---------------------------- create new document ----------------------------
function ViewCreateDocument()
{
	//-- check if running in DOE interface
	if(docInfo.IsDOE){
		window.external.DOE_CreateDoc();
		objView.Refresh(true, false, true);
	//-- browser interface
	}else{	
		var docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/Document?OpenForm&ParentUNID=" + docInfo.DocID;
	
		var docTypeArray=docInfo.DocumentType.split(", ");
		if(docTypeArray.length==1 && docTypeArray[0] !="")
		{
			docUrl += "&typekey=" + docInfo.DocumentType;
			ViewLoadDocument(docUrl, docInfo.DocumentTypeName, true);
		}
		else
		{		
			var dlgUrl = "/" + docInfo.NsfName + "/" + "dlgSelectDocType?OpenForm&ParentUNID=" + docInfo.DocID;
			var tmpDocova = (window.top.Docova ? window.top.Docova : Docova);
			var doctypedlg = tmpDocova.Utils.createDialog({
				id: "divDlgSelectDocType", 
				url: dlgUrl,
				title: "New Document",
				height: 425,
				width: 400, 
				useiframe: true,
				buttons: {
					"Create Document" : function() {
						var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
						if(result && result.DocumentType){
							docUrl += "&typekey=" + result.DocumentType;
							doctypedlg.closeDialog();
							ViewLoadDocument(docUrl, result.DocumentTypeName, true);
						}
					},
        			"Cancel": function() {
						doctypedlg.closeDialog();
					}
				}
			});
		}
	}
}//--end ViewCreateDocument

//================= sets header title ====================
function ViewSetTitleOptions(title)
{
	$("#divHeadingTitle").html(title);
}

//-- initializes default subject and calls send email dialog
function TriggerSendDocumentMessage() {
	var defsubject = docInfo.FolderName;
	if (defsubject == undefined) {
		defsubject = "";
	} else {
		if (defsubject != "") {
			defsubject = "Re:" + defsubject;
		}
	}
	SendDocumentMessage(defsubject);
} //--end TriggerSendDocumentMessage

//--send email message with link to folder or public access link
function SendDocumentMessage(optionalDefaultSubject,optionalDefaultBody)
{
	var fwdAttachments = "Yes"
	if(docInfo.EnableForwarding == "") { fwdAttachments = "0" }
	
	dlgParams.length = 0; //reset dlgParams array.
	dlgParams[0] = optionalDefaultSubject;
	dlgParams[1] = optionalDefaultBody;

	//build an array of the selected documents in the view
    var selectedDocIds = new Array();    
	if(objView.selectedEntries.length != 0){
		for(var k=0; k<objView.selectedEntries.length; k++){
			selectedDocIds.push( (objView.selectedEntries[k])? objView.selectedEntries[k] :"" ) ;
		}
	}
	//pass the selected document ids to the send dialog
	dlgParams[3]= selectedDocIds;
	
	//See dlgParams[2] also at the end of this function which is set to the dialog object so the dialog can use .closeDialog()
	
	var dlgUrl =docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgSendLinkMessage?OpenForm&ParentUNID=" + docInfo.DocID + "&FwdAtt=" + fwdAttachments;
	
	var dlgSendLinkMessage = window.top.Docova.Utils.createDialog({
		id: "divDlgSendDocMessage", 
		url: dlgUrl,
		title: "Send Email Notification",
		height: 500,
		width: 420, 
		useiframe: true,
		sourcedocument: document,
		sourcewindow: window,
		buttons: {
      		"Send": function() {
				var dlgDoc = window.top.$("#divDlgSendDocMessageIFrame")[0].contentWindow.document;
				var dlgWin = window.top.$("#divDlgSendDocMessageIFrame")[0].contentWindow;
				var tmpurl = "HomeFrame?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID;
				if (dlgWin.info.PublicAccessEnabled == 'true')
					var folderPath = docInfo.ServerUrl + docInfo.PortalWebPath + "/publicAccess?OpenPage&gotourl=" + tmpurl;
				else 
					var folderPath = docInfo.ServerUrl + docInfo.PortalWebPath + '/w' + tmpurl;
				var sendto = $.trim($("#SendTo", dlgDoc).val());
				var subject = $.trim($("#Subject", dlgDoc).val());
				var body = $.trim($("#Body", dlgDoc).val());
				var contentinclude = $("input[name=ContentInclude]:checked", dlgDoc).val();
				//--- If activity type is not selected 
				if(sendto == ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter the recipient names.",
							width: 300,
							icontype : 1,
							msgboxtype : 0
					})
					return false;
				}
				//--- If recipient is not document owner and/or sendto list
				if(subject == ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter the subject",
							width: 300,
							icontype : 1,
							msgboxtype : 0
					});
					return false;
				}
				//--- If subject for activity email is blank.
				if(body == ""){
					window.top.Docova.Utils.messageBox({
							title: "Invalid Entry.",
							prompt: "Please enter a message.",
							width: 300,
							icontype : 1,
							msgboxtype : 0
					});
					return false;
				}

				if(contentinclude == "P"){
					dlgWin.completeWizard();
				}else{
					//--- If all ok, generate request
					var request = "<?xml version='1.0' encoding='UTF-8' ?>";
					request += "<Request>";
					request += "<Action>";
					request += (contentinclude=="A")? "SENDATTACHMENTMSG" : "SENDLINKMSG";
					request += "</Action>";
					request += "<SendTo><![CDATA[" + sendto +  "]]></SendTo>";
					request += "<Subject><![CDATA[" + subject +  "]]></Subject>";
					request += "<Body><![CDATA[" + body +  "]]></Body>";
					request += "<UserName><![CDATA[" + docInfo.UserName +  "]]></UserName>";	
					request += "<FolderName><![CDATA[" + docInfo.FolderName +  "]]></FolderName>";
					request += "<FolderPath><![CDATA["  + folderPath +  "]]></FolderPath>";
					//Difference between SendDocumentMessage in a View vs Document is Unid node might have many selected docs in a View
					if(objView.selectedEntries.length != 0){
						for(var k=0; k<objView.selectedEntries.length; k++){
							request += (objView.selectedEntries[k])? "<Unid>" + objView.selectedEntries[k] +  "</Unid>" :"";
						}
					}					
					request += "</Request>";

					DoSendDocumentMessage(request);
					dlgSendLinkMessage.closeDialog();
				}
       		},
       		"Cancel": function() {
				dlgSendLinkMessage.closeDialog();
     		}
     	}
	});
	
	//Puts the dialog into the dlgParams array so that it is available to be closed with closeDialog() within the dialog itself.
	dlgParams[2] = dlgSendLinkMessage;
}

function DoSendDocumentMessage(request){
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/MessagingServices?OpenAgent"

	jQuery.ajax({
		type: "POST",
		url: url,
		data: encodeURI(request),
		cache: false,
		async: false,
		dataType: "xml",
		success: function(xml){
			var xmlobj = $(xml);
			var statustext = $(xmlobj).find("Result").first().text()
			if(statustext == "OK"){
				alert("Message was sent.");
			}
		},
		error: function(){
			alert("Error.  Message was not sent.  Please check error logs for more information.");
		}
	});
}

//Forward a document as an email with an optional introduction
function ForwardDocument()
{
	var objEntry = objView.GetCurrentEntry();
	var selecteddocid = (objEntry && objEntry.isRecord ? objEntry.entryId : null);
	if(! selecteddocid){
		return false;
	}

	var forcesave = (objEntry.GetElementValue("flags") & 256);  //256 is the indicator for Force Save of Forwards
 	var promptsave = (objEntry.GetElementValue("flags") & 512);	//512 is the indicator for Prompt for Save of Forwards
  	var savecopyoption = (docInfo.CanCreateDocuments ? (forcesave ? "1" : (promptsave ? "2" : "0")) : "0");  //Disable save if user cannot create documents in folder
	var defsubject = encodeURIComponent(objEntry.GetElementValue("F8"));
	
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgForwardDocument?OpenForm&SourceDocUNID=" + selecteddocid + "&savecopy=" + savecopyoption + "&DefaultSubject=" + defsubject ;
	
	window.top.Docova.Utils.createDialog({
		id: "divForwardDoc", 
		url: dlgUrl,
		title: "Forward Document",	
		height: 625,
		width: 685, 
		useiframe: true,
		defaultButton: 1,
		sourcedocument: document, 
		buttons: {
        	"Send": function() {
				var dlg = "";        		
				var dlgDoc = "";
				if($("#divForwardDocIFrame", this)[0].contentWindow) {
					dlg = $("#divForwardDocIFrame", this)[0].contentWindow;
				} else {
					dlg = $("#divForwardDocIFrame", this)[0].window;
				}		
				dlgDoc = dlg.document;
						
				var sendto = Docova.Utils.allTrim($("#SendTo", dlgDoc).val());
				var subject = Docova.Utils.allTrim($("#Subject", dlgDoc).val());
				
				if(!sendto) { //recipients required
					window.top.Docova.Utils.messageBox({
						prompt: "Please enter the recipient names.",
						title: "Invalid entry",
						width: 300
					});
					return false;
				}
				if(!subject) { //subject required
					window.top.Docova.Utils.messageBox({
						prompt: "Please enter the subject.",
						title: "Invalid entry"
					});
					dlgDoc.getElementById("Subject").focus();
					return false;
				}
				$("#btnFinish").prop( "disabled", true );
				window.top.Docova.Utils.showProgressMessage("Forwarding message. Please wait...");
				//-----------------------------------------------------------------------------------------------------------------------------------------------
				//var msgbody = Docova.Utils.allTrim(Docova.Utils.getField({ field: "Body" }, dlgDoc));	
				var msgbody = dlgDoc.getElementById("dEdit0").innerHTML;
				msgbody = encodeURIComponent(msgbody);
				var contentinclude = Docova.Utils.allTrim(Docova.Utils.getField({ field: "ContentInclude" }, dlgDoc));
				var savecopy = Docova.Utils.allTrim(Docova.Utils.getField({ field: "SaveCopy" }, dlgDoc));
				var origbody = dlgDoc.getElementById("OriginalBody").innerHTML;
				origbody = encodeURIComponent(origbody);
				
				var request = "";	
				request += "<?xml version='1.0' encoding='UTF-8' ?>";
				request += "<Request>";
				request += "<Action>FORWARDDOCUMENT</Action>";
				request += "<IncludeAttachments>" +  ((contentinclude=="A")? "1" : "") + "</IncludeAttachments>";
				request += "<SaveCopy>" +  savecopy + "</SaveCopy>";
				request += "<SendTo><![CDATA[" + sendto +  "]]></SendTo>";
				request += "<Subject><![CDATA[" + subject +  "]]></Subject>";
				request += "<Body><![CDATA[" + msgbody +  "]]></Body>";
				request += "<UserName><![CDATA[" + docInfo.UserName +  "]]></UserName>";	
				request += "<Unid>" + dlg.docInfo.SourceDocUNID +  "</Unid>";
				request += "<OriginalBody><![CDATA[" + origbody + "]]></OriginalBody>";
				request += "</Request>"
			
				//-----------------------------------------------------------------------------------------------------------------------------------------------
				
				//--- process on server --				
				var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/MessagingServices?OpenAgent"				
				var httpObj = new objHTTP();
				
				if(httpObj.PostData(encodeURIComponent(request), url)){
					window.top.Docova.Utils.hideProgressMessage();
					 if(httpObj.status=="OK"){
						//all OK
						window.top.Docova.Utils.messageBox({
							prompt: "Message was forwarded.",
							title: "Message Forwarded"
						});
						window.top.Docova.Utils.closeDialog({
							id: "divForwardDoc"
						});
						return true;
					}
				}	
				window.top.Docova.Utils.hideProgressMessage();			
			},					
        	"Cancel": function() {
				window.top.Docova.Utils.closeDialog({ id:"divForwardDoc" });
			}
		}
	});
	
	return false;
}


//Change the doctype of  a document
function ChangeDocType(currentEntry)
{
	if ( currentEntry == undefined ) {
		currentEntry = objView.currentEntry
		if(objView.selectedEntries.length == 0 && ( currentEntry == undefined  || currentEntry == "" )){
			Docova.Utils.messageBox({
				title: "Error",
				prompt: "Please select at least one document for changing the doc type.",
				icontype: 1,
				msgboxtype: 0
			});
			return false;
		}
	}
	
	
	var dlgUrl = "/" + docInfo.NsfName + "/" + "dlgSelectDocType?OpenForm&ParentUNID=" + docInfo.DocID + "&mode=change";
	var tmpDocova = (window.top.Docova ? window.top.Docova : Docova);
	var doctypedlg = tmpDocova.Utils.createDialog({
		id: "divDlgSelectDocType", 
		url: dlgUrl,
		title: "Change Document Type",
		height: 425,
		width: 400, 
		useiframe: true,
		buttons: {
			"Change Document Types" : function() {
				var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
				if(result && result.DocumentType){
					doctypedlg.closeDialog();

					//--collect the xml for all nodes to be processed
					var request="";						
					request += "<Request>";
					request += "<Action>CHANGEDOCTYPE</Action>";
					request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
					request += "<TypeKey>" + result.DocumentType+ "</TypeKey>";	
					if(objView.selectedEntries.length> 0){
						for(var k=0; k<objView.selectedEntries.length; k++){
							request += (objView.selectedEntries[k])? "<Unid>" + objView.selectedEntries[k] +  "</Unid>" :"";
						}
					}else
					{
						request += "<Unid>" + currentEntry+  "</Unid>" ;
					}
					request += "</Request>";
					
					var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent"	
					var httpObj = new objHTTP();
					Docova.Utils.showProgressMessage("Processing request. Please wait...");
					var retVal=false;
					if(httpObj.PostData(request, url))
					{
	 					if(httpObj.status=="OK") {
							retVal=true;
						}
					}
					Docova.Utils.hideProgressMessage();
					objView.Refresh(true, false, true);
					return retVal;	
				}
			},
    		"Cancel": function() {
				doctypedlg.closeDialog();
        	}
      	}
	});		
}
		
		
//-------------------------- manually archive selected documents ----------------------
function ArchiveSelected()
{

	if(objView.selectedEntries.length == 0){
		window.top.Docova.Utils.messageBox({
			title: "Error",
			prompt: "Please select at least one document for archiving.",
			icontype: 1,
			msgboxtype: 0,
			width: 300
		});		
		return false;
	}

	window.top.Docova.Utils.messageBox({
		title: "Archive documents?",
		prompt: "You are about to archive " + objView.selectedEntries.length + " document(s).  Are you sure?",
		icontype: 2,
		msgboxtype: 4,
		width : 400, 
		onYes: function(){
			var request="";
			//--collect the xml for all nodes to be processed
			request += "<Request>";
			request += "<Action>ARCHIVESELECTED</Action>";
			request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
			if(objView.selectedEntries.length != 0){
				for(var k=0; k<objView.selectedEntries.length; k++){
					request += (objView.selectedEntries[k])? "<Unid>" + objView.selectedEntries[k] +  "</Unid>" :"";
				}
			}
			request += "</Request>";
		
			//--- processing agent url
			var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/LibraryServices?OpenAgent";
			
			Docova.Utils.showProgressMessage("Archiving selected document(s). One moment...");
			jQuery.ajax({
				type: "POST",
				url: url,
				data: request,
				cache: false,
				async: false,
				dataType: "xml",
				success: function(xml){
					var xmlobj = $(xml);
					var resulttext = xmlobj.find("Result").first().text();
					if(resulttext == "OK"){
						retVal=true;
						Docova.Utils.hideProgressMessage();
						objView.Refresh(true, false, true);
					}
				},
				error: function(){
					Docova.Utils.hideProgressMessage();
				}
			});
			return retVal;	
		} //end onYes
	});
}

//-------------------------- import mail messages from email or dropbox ----------------------
function ImportMessages()
{
	var dlgUrl =docInfo.MailAcquireMessagesDialogUrl;
	if (dlgUrl == ''){
			window.top.Docova.Utils.messageBox({
				title: "Import Messages Not Available",
				prompt: "Import Messages is not available for your current mail configuration.",
				width: 400,
				icontype: 4,
				msgboxtype: 0
			});
			return false;
	}

	if(docInfo.UserMailSystem == "O" && !window.top.Docova.IsPluginAlive && !docInfo.IsDOE){
			window.top.Docova.Utils.messageBox({
				title: "DOCOVA Plugin Not Running",
				prompt: "The DOCOVA Plugin is required for the importing of messages from Outlook.",
				width: 400,
				icontype: 4,
				msgboxtype: 0
			});
			return false;	
	}	

	var dlgmail = window.top.Docova.Utils.createDialog({
		id: "divDlgAcquireMessages", 
		url: dlgUrl,
		title: "Import Mail Messages",
		height: 600,
		width: 800, 
		useiframe: true,
		sourcedocument : document,		
		sourcewindow : window,
		buttons: {
			"Close": function() {
				var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.GetImportCount();
				if(returnValue){
					objView.Refresh(true, false, true);
	        	}	  			
		   		dlgmail.closeDialog();
        	},
        	"Import Selected": function() {
	        	var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.CompleteDialog(function(returnValue){
		        	if(returnValue){
						objView.Refresh(true, false, true);
		        	}else{
		        		window.top.Docova.Utils.messageBox({
							prompt: "Please select one or more emails to import.",
							icontype: 3,
							msgboxtype: 0, 
							title: "Choose One or More Emails",
							width: 400
						});
					}	        			
				});
			}
      	}
	});		
}

//----------- advanced search ------------
function AdvancedSearch()
{
	var dlgUrl = "";
	var ssObj = $("#MySavedSearches").multiselect("getChecked").map(function(){ return this.value;	}).get();	
	var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgCustomSearch?OpenForm&edit=" + ((ssObj == "") ? "false" : "true") + "&folder=true";
	
	var dlgAdvancedSearch = window.top.Docova.Utils.createDialog({
		id: "divDlgAdvancedSrch", 
		url: dlgUrl,
		title: "Advanced Search",
		height: 410,
		width: 650, 
		useiframe: true,
		defaultButton: 1,
		sourcewindow: window,
		buttons: {
        	"Save": function() {
	        		var cmd = "";
				if(window.top.$("#divDlgAdvancedSrchIFrame")[0].contentWindow) {	//Chrome/FF
					cmd = window.top.$("#divDlgAdvancedSrchIFrame")[0].contentWindow.SetQueryInfo();
				} else {
					cmd = window.top.$("#divDlgAdvancedSrchIFrame")[0].window.SetQueryInfo();	//IE
				}
				if(cmd) { openSaveSearch(); }
			},		
        	"Search": function() {
				if(window.top.$("#divDlgAdvancedSrchIFrame")[0].contentWindow) {
					window.top.$("#divDlgAdvancedSrchIFrame")[0].contentWindow.CompleteWizard();				
				} else {
					window.top.$("#divDlgAdvancedSrchIFrame")[0].window.CompleteWizard();				
				}
        	},
        	"Close": function() {
				dlgAdvancedSearch.closeDialog();
        	}
      	}
	});
}

function CopyLink(currentEntry){
	if(currentEntry == null){
		if(objView.currentEntry == ""){
			Docova.Utils.messageBox({
				title: "Nothing highlighted",
				prompt: "To copy a document URL link, please highlight the document first.",
				icontype: 1,
				msgboxtype: 0,
				width: 400
			});
			return;
		}else{
			currentEntry = objView.currentEntry;
		}
	}
	var docUrl=docInfo.ServerUrl + docInfo.PortalWebPath + "/wHomeFrame?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID;
	docUrl += "," + currentEntry;
	var html = '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr valign="top"><td width="100%">\
		<div id="dlgContentNh" class="ui-widget" style="width:100%;">\
		<span id="FieldLabel"class="frmLabel">Use CTRL+A to select and CTRL+C to copy:</span>\
		<textarea style="font: 11px Verdana;width:350px;" class="txFld" id="docURL" name="docURL" rows=6>'+ ($.trim(docUrl) ? docUrl : errortext) +'</textarea></div></td></tr></table>';

	var dlgCopyDocURL = window.top.Docova.Utils.createDialog({
		id: "divDocUrl", 
		dlghtml : html,
		title: "Document Link URL",
		height: 300,
		width: 400,
		sourcewindow: window,
		buttons: {
        	"Close": function() {
				dlgCopyDocURL.closeDialog();
        	}
      	}
	});
}

function CreateShortcut()
{
	var docUrl=docInfo.ServerUrl + docInfo.PortalWebPath + "/wHomeFrame?ReadForm&goto=" + docInfo.LibraryKey + "," + docInfo.FolderID;
	var objExt = doc.DLExtensions;
	//-------------------------------------------
	if(objExt.CreateIEShortcut("DocLogic folder - " + docInfo.FolderName.replace(/[\(\)\<\>\,\;\:\\\/\"\[\]]/, " "), docUrl, true)){
		alert("Folder shortcut was added to your desktop.");
	}
}


function ResetFolderFilter(){
//-----Reset the CurrentFolderDiv innerHTML to the folder's set filter and refresh-----
//-----This is different than the ClearAllFilterswhich clears all filters-----
	$("#CurrentFilterDiv").html("");
	var UseOriginalXML = true;
	ApplyDefaultFolderFilter(UseOriginalXML);
	return;
}

function ClearAllFilters()
{
	//-----Clears all filtering including the default folder filter if there is one----
	ClearAllColFilterFlags();
	$("#divViewContent").css("display", "none");
	objView.oXml = objView.oOriginalXml;
	objView.Refresh(false,true,true );
	ApplyFolderFilter(false);				
	setResizeHandlers();
	$("#divViewContent").css("display", "");
	return;
}

function ClearAllFiltersSub(UseOriginalXML)
{
	//-----Clears all filtering including the default folder filter if there is one and when the Query option is set to All Versions----
	ClearAllColFilterFlags();
	if (UseOriginalXML) {
		objView.Refresh(true,true,false);
	}
	setResizeHandlers();	
	return;
}

function ClearAllColFilterFlags(){
	$("#CurrentFilterDiv").html("");
	for(var x=0; x<objView.columns.length; x++){
		var objColumn = objView.columns[x]
		$(objColumn).attr("isFiltered", false);
	}
	return;
}

function ClearColFilterFlag(NodeName){
	for(var x=0; x<objView.columns.length; x++){
		var objColumn = objView.columns[x]
		if(NodeName == objColumn.xmlNodeName){
			$(objColumn).attr("isFiltered", false);
		}
	}
	return;
}

function SetColumnFilterFlag(NodeName){
	for(var x=0; x<objView.columns.length; x++){
		var objColumn = objView.columns[x]
		if(NodeName == objColumn.xmlNodeName){
			$(objColumn).attr("isFiltered", true);
		}
	}
	return;
}

function SetAllColFilterFlags(){
	var filterexpArray;
	var filterarray = $("#CurrentFilterDiv").html().split("~");
	
	for (var x=0; x<objView.columns.length; x++){
		var objColumn = objView.columns[x]
		objColumn.isFiltered = false; //first we reset the columns as we come to them..then determine if they are to be set according to the current filter expression
		for (var i=0; i< filterarray.length; i++){
			filterexpArray = filterarray[i].split("=")
			if(filterexpArray[0] == objColumn.xmlNodeName){
				$(objColumn).attr("isFiltered", true);
			}
		}
	}
	return;
}

function CreateFolderFilterStyle(filterExpr){
	try
	{
		var xslFilter = '<?xml version="1.0"?>';
		xslFilter += '<xsl:stylesheet  version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" ><xsl:output method="xml"/>';
		xslFilter += '<xsl:template match="/">';
		xslFilter += '<documents>';
		xslFilter += '<xsl:for-each select="//document[' + filterExpr + ']">';
		xslFilter += '<xsl:copy-of select="."></xsl:copy-of>';
		xslFilter += '</xsl:for-each>';
		xslFilter += '</documents>';
		xslFilter += '</xsl:template>';
		xslFilter += '</xsl:stylesheet>';

		$("#FolderFilterXSL").html(xslFilter);	
		
	}
	catch(e)
	{   
		Docova.Utils.messageBox({
			title: "Filter error",
			prompt: "Could not create filter: " + e.message,
			icontype: 1,
			msgboxtype: 0
		});		
		return false;
	}    	
return true;
}

function ApplyFolderFilter(UseOriginalXML){

	ApplyQueryOptionToFilter();
	
	var filterexp = "";
	var CurrentFilter = $("#CurrentFilterDiv").html();
	var Fn = "";
	var FnExp = "";
	var NewExp;
	var expArray;
		
	if (CurrentFilter != ""){
		var CurrentFilterArray = CurrentFilter.split("~")
		for (var x=0; x<CurrentFilterArray.length; x++){
			if(CurrentFilterArray[x].indexOf(" or ") != -1){ 
				//----- if ' or ' is found in the string, that is, 'space or space', then we have an 'or' in the expression so just pass it through.
				NewExp = " (" + CurrentFilterArray[x] + ") "
			}else{ //----- ' or ' is not found in the string therefore we want to perform a 'contains"
				expArray = CurrentFilterArray[x].split("=")
				Fn = expArray[0]
				FnExp = "', " + rightBack(leftBack(expArray[1], "'"),"'") + ", '"
				NewExp = "contains(concat(concat(', ', " + Fn + "), ', '), " + FnExp + ")"				
			}

			if(filterexp == ""){
				filterexp = NewExp;
			}else{
				filterexp += " and " + NewExp
			}
		}	
	}
	
	if(filterexp == ""){
		ClearAllFiltersSub(UseOriginalXML);
	}else{
		if(CreateFolderFilterStyle(filterexp)){
			if(UseOriginalXML){
				objView.oXml =objView.oOriginalXml;
			}
			
			var el = document.getElementById("FolderFilterXSL");
			var xsl = el.textContent || el.innerText || el.nodeValue || el.innerHTML;
			var parser = new DOMParser();
			var objXSLDoc = parser.parseFromString(xsl,"text/xml");
			var processor = new XSLTProcessor();
			processor.importStylesheet(objXSLDoc); 
			objView.oXml = processor.transformToDocument(objView.oXml); 
			objView.Refresh(false,true,true);
			setResizeHandlers();
		}
	}
	return;
}

function ApplyCurrentFolderFilter(UseOriginalXML )
{
	SetAllColFilterFlags();
	ApplyFolderFilter(UseOriginalXML);
	return;
}

function ApplyDefaultFolderFilter(UseOriginalXML){

	var userfilternodes = docInfo.UserFltrFieldNodes;
	var userfilternodevals = docInfo.UserFltrFieldNodeVals;
	var fltrNodes = docInfo.fltrFieldNodes;

	var userfilternodesArr = docInfo.UserFltrFieldNodes.split(";")
	var userfilternodevalsArr = docInfo.UserFltrFieldNodeVals.split(";")
	var fltrNodesArr = docInfo.fltrFieldNodes.split(";")
	var filterexp = ""

	for (i=0; i< fltrNodesArr.length; i++){
		for (j=0; j<userfilternodesArr.length;j++){
			if (fltrNodesArr[i] == userfilternodesArr[j] && fltrNodesArr[i] != ""){				
				if(filterexp == ""){
					filterexp = fltrNodesArr[i] + "=" + "'" + userfilternodevalsArr[j] + "'"
				}else{
					filterexp =filterexp + "~" + fltrNodesArr[i] + "=" + "'" + userfilternodevalsArr[j] + "'"
				}
			}
		}
	}
	$("#CurrentFilterDiv").html(filterexp);
	SetAllColFilterFlags();
	ApplyFolderFilter(UseOriginalXML);
	return;
}

function ViewColumnSelectFilter(source){
	if(docInfo.EnableFolderFiltering != "1"){return;}

	var NodeList
	var columnvals = "";
	var slist = ""; //sorted list
	var ulist = ""; //unique list
	var columnObj = objView.columns[$(source).attr("colidx")];
	
	if (columnObj == null){ return;}
	var NodeName = columnObj.xmlNodeName;
	var NodeExpr = "//" + NodeName;
	var CurrentSelectedOptionText;
	var NodeListText = "";
		
	NodeList = objView.oXml.selectNodes(NodeExpr) 
	if(NodeList[0]==null){   //no documents in the folder/view
		Docova.Utils.messageBox({
			title: "Error",
			prompt: "There are no documents to filter."
		});
		return;
	}
	
	//-----If NodeList length is greater than one then sort and unique the values-----
	if(NodeList.length > 1){
		for( var i = 0; i < NodeList.length; i++ ){ 
			NodeListText = (NodeList[i].text == undefined ? (NodeList[i].textContent == undefined ? "" : NodeList[i].textContent) : NodeList[i].text);			
			if (NodeListText.indexOf(",") > 0){
				NodeListText = NodeListText.replace(", ", "~")
			}
			if(columnvals == ""){
				columnvals += NodeListText;
			}else{
				columnvals += "~" + NodeListText;
			}
		} 
		slist = Docova.Utils.sort({ inputstr: columnvals, delimiterin: "~", delimiterout: "~" });
		ulist = Docova.Utils.unique({ inputstr: slist, delimiterin: "~", delimiterout: "~" });
	}else{
		ulist = (NodeList[0].text == undefined ? (NodeList[0].textContent == undefined ? "" : NodeList[0].textContent) : NodeList[0].text);
	}

	CurrentSelectedOptionText = getCurrentSelectedOptionText(NodeName)

	var optionarray = ulist.split("~");
	var items = [];
	items.push("<select name='" + NodeName + "' id='" + NodeName + "' onchange='CreateFilter(this)' style='padding:5px 0;line-height:19px;' onblur='HideFilterSelection(this)'><option value='All'>All</option>");

	for (var x = 0; x < optionarray.length; x ++) {
		if(optionarray[x] == CurrentSelectedOptionText) {
			items.push("<option selected=true value='" + optionarray[x] + "'>" + optionarray[x] + "</option>");
		} else {
			items.push("<option value='" + optionarray[x] + "'>" + optionarray[x] + "</option>");
		}
	}
	items.push('</select>');
	$("#SelectFilterDiv").html(items.join(''));
	
	$("#SelectFilterDiv").position({
		my: "left top",
		at: "left bottom",
		of: source
	});
	
	$("#" + NodeName).css("width", parseInt(columnObj.width) + 5);	
	$("#SelectFilterDiv").css("display", "" );
	var optionlist = "<select name='" + NodeName + "' id='" + NodeName + "' onchange='CreateFilter(this)' style='padding:5px 0;line-height:19px;' onblur='HideFilterSelection(this)'><option value='All'>All</option>";
	for(var j = 0; j<optionarray.length; j++){
		if(optionarray[j] == CurrentSelectedOptionText){
			optionlist += "<option selected=true value='" + optionarray[j] + "'>" + optionarray[j] + "</option>";
		}else{
			optionlist += "<option value='" + optionarray[j] + "'>" + optionarray[j] + "</option>";
		}
	}
	optionlist += "</select>";
}

function CreateFilter(selectobj){
	var filterexp;
 	var NodeName = selectobj.name
 	var NodeValue = selectobj.options[selectobj.selectedIndex].value
 	var CurrentFilter;
 	var CurrentFilterArray;
 	var filterexp;
 	var newfilterexp;
 	var UseOriginalXML = false;

 	filterexp = NodeName + "='" + NodeValue + "'"
 	
 	if(NodeValue == "All"){
 		RemoveFromCurrentFilter(NodeName)
 		UseOriginalXML = true;
 	}else{
 		AddToCurrentFilter(filterexp)
 		SetColumnFilterFlag(NodeName)
 		UseOriginalXML = false;
 	}
 	
 	ApplyFolderFilter(UseOriginalXML)
 	
 	$(selectobj).css("display", "none");
}

function RemoveFromCurrentFilter(NodeName){
//-----Find the node and remove it-----
	var CurrentFilterTxt = $("#CurrentFilterDiv").html();
	var NewFilterTxt = "";
	var CurrentFilterArray = CurrentFilterTxt.split("~")
	var CurrentExpArray;
	
	for (var x=0; x<CurrentFilterArray.length; x++){
		CurrentExpArray = CurrentFilterArray[x].split("=")
		if (CurrentExpArray[0] != NodeName){
			if(NewFilterTxt == ""){
				NewFilterTxt = CurrentFilterArray[x]
			}else{
				NewFilterTxt += "~" + CurrentFilterArray[x]
			}
		}
	}
	ClearColFilterFlag(NodeName)
	$("#CurrentFilterDiv").html(NewFilterTxt);
}

function AddToCurrentFilter(filterexp){
	var CurrentFilterTxt = "";

	CurrentFilterTxt = $("#CurrentFilterDiv").html();
	if(CurrentFilterTxt == ""){
		$("#CurrentFilterDiv").html(filterexp);
	}else{
		if (CurrentFilterTxt.indexOf(filterexp) == -1){
			CurrentFilterTxt += "~" + filterexp;
			$("#CurrentFilterDiv").html(CurrentFilterTxt);
		}
	}
}

function getOffset( el ) {
	var _x = 0;     
	var _y = 0;
	while( el && !isNaN( el.offsetLeft ) && !isNaN( el.offsetTop ) ) {
		_x += el.offsetLeft - el.scrollLeft;
		_y += el.offsetTop - el.scrollTop;
		el = el.offsetParent;
	}

	return { top: _y, left: _x }; 
}  

function getCurrentSelectedOptionText(NodeName){

	var CurrentFilterTxt = $("#CurrentFilterDiv").html();
	var CurrentFilterArray = CurrentFilterTxt.split("~")
	var expValuesArray;
	var currentSelectedOptionText = "";

	for (var x=0; x<CurrentFilterArray.length; x++){
		expValuesArray = CurrentFilterArray[x].split("=")
		if(expValuesArray[0] == NodeName){
			currentSelectedOption = expValuesArray[1];
			currentSelectedOptionText = rightBack(leftBack(currentSelectedOption, "'"),"'")
			return currentSelectedOptionText;
		}
	}
	return currentSelectedOptionText;
}

function HideFilterSelection(objSelectFilterField){
	$(objSelectFilterField).css("display", "none");
}

function ApplyQueryOptionToFilter(){
	var addfilteroption = "";
	var currShowOption = Docova.Utils.getField("selVersionScope");

	if (currShowOption == "REL") {
		addfilteroption = "statno='1' or apflag='1'" //Released or allow preview flag
	}
	if (currShowOption == "NEW") {
		addfilteroption = "statno='0'" //Draft
	}			
	RemoveFromCurrentFilter("statno")
	AddToCurrentFilter(addfilteroption)
	return;
}


function CreateBookmark(currentEntry) {
	if (currentEntry == undefined) {
		currentEntry = objView.currentEntry
			if (currentEntry == undefined || currentEntry == "") {
				window.top.Docova.Utils.messageBox({
					title : "Error",
					prompt : "Please highlight a document to create a bookmark for.",
					icontype : 1,
					msgboxtype : 0
				});
				return;
			}
	}

	window.top.Docova.Utils.messageBox({
		title : "Create Bookmark?",
		prompt : "Would you like to create a Bookmark entry for the highlighted document?",
		icontype : 2,
		msgboxtype : 4,
		width : 400,
		onYes : function () {
			//-- choose target folder
			var dlgUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgFolderSelect?ReadForm&flags=create,notcurrent,norecycle";
			var folderdbox = window.top.Docova.Utils.createDialog({
					id : "divDlgFolderSelect",
					url : dlgUrl,
					title : "Select Bookmark Folder",
					height : 420,
					width : 420,
					useiframe : true,
					sourcedocument : document,
					buttons : {
						"Create Bookmark" : function () {
							var returnValue = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();

							//-- returnValue [0]=LibraryID, [1]=FolderID, [2]=FolderUNID, [3]=FolderAccessLevel
							if (returnValue) {
								if (returnValue[1] == docInfo.FolderID) {
									alert("Unable to create bookmark in the same folder as the source document. Please choose an alternate folder.");
									return;
								}
								//---------------------------------- Check Folder Access Level -----------------------------------------
								if (Number(returnValue[3]) < 3) {
									alert("You do not have sufficient rights to create documents in the selected folder. Please choose an alternate folder.");
									return;
								}
								folderdbox.closeDialog();
								//--- processing agent url
								var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
								//--build the CREATEBOOKMARK request
								var request = "";								
								request += "<Request>";
								request += "<Action>CREATEBOOKMARK</Action>";
								request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
								request += "<Unid>" + currentEntry + "</Unid>";
								request += "<LibraryID>" + returnValue[0] + "</LibraryID>";
								request += "<FolderID>" + returnValue[1] + "</FolderID>";
								request += "</Request>";
								var httpObj = new objHTTP();
								if (httpObj.PostData(request, url)) {
									if (httpObj.status == "OK") {
										if (httpObj.results.length > 0) {
											alert("Bookmark successfully created in chosen folder.");
										}
									}
								}
							}
						},
						"Cancel" : function () {
							folderdbox.closeDialog();
						}						
					}
				});  //end createDialog
		} //end onYes
	})
}//--end CreateBookmark


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: printAttachments 
 * 
 * Inputs: 
 * Returns: 
 * Example:
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function printAttachments(){		
	
	if(! window.top.Docova.IsPluginAlive && !docInfo.IsDOE){
		window.top.Docova.Utils.messageBox({
			prompt: "DOCOVA Plugin is not running.  This functionality requires the use of the DOCOVA Plugin.",
			title: "Print Attachments",
			width: 400
		});
		return false;
	}
	
	var folderid = docInfo.FolderID;
	var excludeextensions = "exe,com,dll,ocx";
	
	var IDList = new Array();
	if(objView.selectedEntries.length > 0){
		IDList = objView.selectedEntries;
	}else if (objView.currentEntry !=""){
		IDList.push(objView.currentEntry);
	}else{
		alert("Please select documents to print.");
		return false;
	}; 
									
    var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent&" + Math.random();
    var request = "";
    request += "<Request>";
    request += "<Action>GETATTACHMENTS</Action>";
    request += "<FolderID>" + folderid + "</FolderID>";
    request += "<SelectionType>1</SelectionType>";
    request += "<SelectedDocs>";
	for ( var j=0; j < IDList.length; j ++ ) {
		request += "<DocID>" + IDList[j] + "</DocID>";
	}     	
    request += "</SelectedDocs>";
    request += "<IncludeExtensions></IncludeExtensions>";
    request += "<ExcludeExtensions>"+ excludeextensions +"</ExcludeExtensions>";
    request += "<IncludeThumbnails></IncludeThumbnails>";
    request += "<AppendVersionInfo></AppendVersionInfo>";
    request += "</Request>";

	var printFiles = true;
	var fileList  = "";

	var tempfolder = DLExtensions.getTemporaryFolder(); 
	if(tempfolder == ""){
		alert("Unable to get temporary folder.  Print cancelled");
		return false;
	}
	if (tempfolder.charAt(tempfolder.length-1) != "\\"){
		tempfolder = tempfolder + "\\";
	}
	
	jQuery.ajax({
		'type' : "POST",
		'url' : url,
		'data' : request,
		'contentType': "xml",
		'async' : false,
		'dataType' : 'xml'
	})
	.done(function(xmldata){
		jQuery(xmldata).find("File").each(function(){
				var fileName = jQuery(this).find("FileName").text();
				var fileUrl = jQuery(this).find("URL").text(); 
				if (fileName != ""){
					var targetFilePath = tempfolder + fileName;
					if (DLExtensions.DownloadFileFromURL(fileUrl, targetFilePath, true)){
						fileList = (fileList == "") ? targetFilePath : fileList += "*" + targetFilePath;
					}
				}		
		})	
	})
	.fail(function(){
		window.top.Docova.Utils.messageBox({
			title: "Print Attachments",
			prompt: "Error - Unable to retrieve a listing of attachments to print.",
			width: 400
		});
	 	return false;				
	});
	
 			
	if(printFiles) {
		DLExtensions.printFiles({
			filelist: fileList, 
			onSuccess: function(){
				Docova.Utils.messageBox({
					title: "Print Attachments",
					prompt: "All files have been sent to the printer.",
					width: 300
				});
			},
			onFailure: function(){
				Docova.Utils.messageBox({
					title: "Print Attachments",
					prompt: "Error - Unable to print one or more files.  Please check that all source files are undamaged.",
					width: 400
				});
			}
		});
	}
}//--end printAttachments


function checkAvailableHeight(){
	try{
		var vpheight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;	
		if(vpheight == 0){return;}  //-- in case we aren't able to get the height
		var buttonheader = document.getElementById("actionPaneHeader");
		var viewheader = document.getElementById("divViewHeader");
		var headingsheight = buttonheader.clientHeight + viewheader.clientHeight;
		var container = document.getElementById("divViewContent");
		var hght = vpheight  - headingsheight;
		if ( hght > 0 ){
			$(container).css("height", vpheight  - headingsheight);
		}
	}catch(e){}
}

function ViewOpenFile(filename, editMode, entryid) {
	if (docInfo.isRecycleBin) // documents in recycle bin cannot be opened, just the properties dialog is displayed
	{
		return;
	}
	var rawfilename = decodeURIComponent(filename);

	var docid = "";
	if (entryid && entryid != "") {
		docid = entryid;
	} else {
		var entryObj = objView.GetCurrentEntry();
		if (entryObj) {
			docid = entryObj.entryId;
		}
	}
	if (docid == "") {
		return;
	}

	var action = "Open";
	if (editMode) {
		//need to check if user is authorized to edit
		if (mayUserEditDoc(docid)) {
			action = "Edit";

			var logkey = docid + rawfilename;
			var clogs = Docova.Utils.dbLookup({
					"servername": "",
					"nsfname": docInfo.PortalWebPath,
					"viewname": "luCIAOLogsByFileID",
					"key": logkey,
					"columnorfield": "1",
					"delimiter": ",",
					"alloweditmode": false,
					"secure": docInfo.SSLState,
					"failsilent": true
				});
			if (clogs == logkey) {
				Docova.Utils.messageBox({
					title: "File Checked Out",
					prompt: "This file is currently checked out and cannot be edited.",
					icontype: 1,
					msgboxtype: 0
				});
				return false;
			}
		} else {
			Docova.Utils.messageBox({
				title: "Not Authorized",
				prompt: "You are not authorized to edit this document.",
				icontype: 1,
				msgboxtype: 0
			});
			return false;
		}
	}

	if (action == 'Edit') {
		if (docInfo.FolderID) {
			//External Views Hook
			if (docInfo.externalView && docInfo.externalView!='')
				docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + docid + "?DataView="+docInfo.externalView;
			else
				docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/EditDocument/" + docid;
		} else 
			docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wViewForm/0/" + docid + "?EditDocument&AppID=" + docInfo.AppID;
	}
	else {
		if (docInfo.FolderID) {
			//External Views Hook
			if (docInfo.externalView && docInfo.externalView!='')
				docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + docid + "?OpenDocument&ParentUNID=" + docInfo.DocID + "&DataView="+docInfo.externalView;
			else
				docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/ReadDocument/" + docid + "?OpenDocument&ParentUNID=" + docInfo.DocID;
		} else
			docUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/wReadDocument/" + docid + "?OpenDocument&ParentUNID=" + docInfo.AppID;
	}
	if (filename) {
		docUrl += "&targetfile=" + encodeURIComponent(filename);
	}

	//-- check if running in DOE interface
	if(docInfo.IsDOE){
		docUrl += "&mode=dle";
		window.external.DOE_OpenUrl( docUrl);
	//-- browser interface
	}else{			
		//------------- tabbed interface ----------------------------------
		var frameID = "";
		var title = "";
		frameID = docid;

		window.parent.fraTabbedTable.objTabBar.CreateTab(title, frameID, "D", docUrl, docInfo.DocID, false);
	}
}

function ViewRenameFile(filename, entryid) {
	if (docInfo.isRecycleBin) // documents in recycle bin cannot be changed, just the properties dialog is displayed
	{
		return false;
	}

	var rawfilename = decodeURIComponent(filename);

	var docid = "";
	if (entryid && entryid != "") {
		docid = entryid;
	} else {
		var entryObj = objView.GetCurrentEntry();
		if (entryObj) {
			docid = entryObj.entryId;
		}
	}
	if (docid == "") {
		return false;
	}

	if (!mayUserEditDoc(docid)) {
		Docova.Utils.messageBox({
			title: "Not Authorized",
			prompt: "You are not authorized to edit this document.",
			icontype: 1,
			msgboxtype: 0
		});
		return false;
	}

	var logkey = docid + rawfilename;
	var clogs = Docova.Utils.dbLookup({
			"servername": "",
			"nsfname": docInfo.PortalWebPath,
			"viewname": "luCIAOLogsByFileID",
			"key": logkey,
			"columnorfield": "1",
			"delimiter": ",",
			"alloweditmode": false,
			"secure": docInfo.SSLState,
			"failsilent": true
		});
	if (clogs == logkey) {
		Docova.Utils.messageBox({
			title: "File Checked Out",
			prompt: "This file is currently checked out and cannot be renamed.",
			icontype: 1,
			msgboxtype: 0
		});
		return false;
	}
	var newfilename = prompt("Please enter a new file name.", rawfilename);

	if (newfilename && newfilename !== "" && newfilename != rawfilename) {
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
		var request = "";
		request += "<Request>";
		request += "<Action>RENAMEATTACHMENT</Action>";
		request += "<Unid>" + docid + "</Unid>";
		request += "<AttachmentName><![CDATA[" + uniEncode(rawfilename) + "]]></AttachmentName>";
		request += "<NewName><![CDATA[" + uniEncode(newfilename) + "]]></NewName>";
		request += "</Request>";

		var httpObj = new objHTTP();
		if (httpObj.PostData(request, url)) {
			if (httpObj.status == "OK") {
				if (docInfo.EnableFolderFiltering == "1") {
					$("#divViewContent").css("display", "none");
					objView.Refresh(true, false, true, false, false, true);
					$("#divViewContent").css("display", "");
				} else {
					objView.Refresh(true, false, true);
				}
				return true;
			}
		}
	}
	return false;
}

function ViewDownloadFile(filename, entryid) {

	rawfilename = decodeURIComponent(filename);

	var docid = "";
	if (entryid && entryid != "") {
		docid = entryid;
	} else {
		var entryObj = objView.GetCurrentEntry();
		if (entryObj) {
			docid = entryObj.entryId;
		}
	}
	if (docid == "") {
		return false;
	}

	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openDocFile/" + encodeURIComponent(rawfilename) + "?OpenElement&doc_id="+ docid +"&" + Math.random();
	DLExtensions.SelectFolder({
		title: "Select Download Folder:",
		onSuccess: function (foldername) {
			if (foldername && foldername != "") {
				var targetfile = foldername + (foldername.indexOf("\\", foldername.length - 1) > -1 ? "" : "\\") + rawfilename;

				var result = DLExtensions.DownloadFileFromURL(url, targetfile);
				if (result) {
					alert("File downloaded to " + result);
				}
			}
		}
	});
}

function AttLinkMenu(clickObj, unid, filename) //creates contect menu for a view link
{
	if (!clickObj || !unid || !filename) {
		return;
	}

	var viewportOffset = clickObj.getBoundingClientRect();

	var winwidth = $(window).width();
	var winheight = $(window).height();
	var posX = viewportOffset.left;
	var posY = viewportOffset.top;

	var menuwidth = 240;
	var menuheight = (4 * 25);

	if ((posY + menuheight) > winheight) {
		shiftY = winheight - (posY + menuheight);
	} else {
		shiftY = 2; //default
	}

	if ((posX + menuwidth) > winwidth) {
		shiftX = winwidth - (posX + menuwidth);
	} else {
		shiftX = 1; //default
	}

	var cmdViewFile = "ViewOpenFile('" + filename + "', false, '" + unid + "')";
	var cmdEditFile = "ViewOpenFile('" + filename + "', true, '" + unid + "')";
	var cmdDownloadFile = "ViewDownloadFile('" + filename + "', '" + unid + "')";
	var cmdRenameFile = "ViewRenameFile('" + filename + "', '" + unid + "')";

	//-----Build menu -----
	Docova.Utils.menu({
		delegate: clickObj,
		width: menuwidth,
		position: "XandY",
		shiftX: shiftX,
		shiftY: shiftY,
		menus: [{
				title: "View File",
				itemicon: "ui-icon-newwin",
				action: cmdViewFile,
				disabled: false
			}, {
				title: "Download File",
				itemicon: "ui-icon-extlink",
				action: cmdDownloadFile,
				disabled: false
			}, {
				title: "Edit File",
				itemicon: "ui-icon-pencil",
				action: cmdEditFile,
				disabled: false
			}, {
				title: "Rename File",
				itemicon: "ui-icon-carat",
				action: cmdRenameFile,
				disabled: false
			}
		]
	});

} //--end AttLinkMenu



function moveScroll() {
	var scroll = $('#divViewContent').offset().top;
	var anchor_top = $("#VDataTable").offset().top;

	if (scroll > anchor_top){ //&& scroll < anchor_bottom) {
		clone_table = $("#clone");
					
		if (clone_table.length === 0) {
			var top = 0;
			if ($("#divHeaderSection").css("display") != "none"){
				top += $("#divHeaderSection").height();
			}
					
			
			clone_table = $("#VDataTable").clone().find("tbody > tr").remove().end().find('.ui-resizable-handle').remove().end();
			clone_table.attr({
				id : "clone"
			}).css({
				"position" : "fixed",
				"left" : $("#VDataTable").offset().left + 'px',
				"z-index": "100",
//				"border-top" : "solid 1px silver",
				"top" : top	
			}).width($("#VDataTable").width());
						
			$("#divViewContent").append(clone_table);

			$("#clone").width($("#VDataTable").width());
			$("#clone thead").css({
				visibility : "true"
			});

			// clone tbody is hidden
			$("#clone tbody").css({
				visibility : "hidden"
			});

			var footEl = $("#clone tfoot");
			if (footEl.length) {
				footEl.css({
					visibility : "hidden"
				});
			}
						
			$("#clone").find("td.listheader").resizable({
				handles: "e",
				resize: function(event){
					var tdid = jQuery(event.target).attr("colidx");
					var tdwidth = jQuery(event.target).width();
					$("#VDataTable").find("td.listheader[colidx=" + tdid + "]").width(tdwidth);
				}
			});
						
		}
	} else {
		$("#clone").remove();
	}
}
			

function DeleteDesignElement(type, unid){
	
	if (!confirm("Are you sure you want to delete the current " + type +  " design element?")) {return false;}
	
	var uname = docInfo.UserNameAB
	var agentName = "DesignServices"
	var library = docInfo.NsfName

	var request = "<Request><Action>DELETEELEMENT</Action><UserName><![CDATA[" + uname + "]]></UserName>"
	request += "<Document>"
	request += "<ElementType>" + type + "</ElementType>"
	request += "<unid>" + unid + "</unid>"
	request += "<apppath>" + docInfo.NsfName + "</apppath>"
	request += "</Document>"
	request += "</Request>"

	var result = SubmitViewRequest(request, agentName);
	if(result == true){
		objView.RemoveSelectedEntries(true);
	}
	return result
}

function DeleteSelectedDesignElements(type, unidArray){
	if (!confirm("Are you sure you want to delete the currently selected " + type +  " design elements?")) {return false;}
	
	var uname = docInfo.UserNameAB
	var agentName = "DesignServices"
	var library = docInfo.NsfName
	var unid;
	var request;
	
	for(var x=0; x<unidArray.length; x++){
		unid = unidArray[x]
		request = "<Request><Action>DELETEELEMENT</Action><UserName><![CDATA[" + uname + "]]></UserName>"
		request += "<Document>"
		request += "<ElementType>" + type + "</ElementType>"
		request += "<unid>" + unid + "</unid>"
		request += "<apppath>" + docInfo.NsfName + "</apppath>"
		request += "</Document>"
		request += "</Request>"

		SubmitViewRequest(request, agentName);
	}
	objView.RemoveSelectedEntries();
}

function doDrop(evt) {
	//get the file upload id
	var liburl = "/" + docInfo.NsfName;
	var id = docInfo.DocID;
	var libid = docInfo.FolderID;
	var defDocType = "";

	if (!docInfo.CanCreateDocuments) {
		alert("You don't have sufficient rights to be able to create documents in this folder!");
		return;
	}

	var inputid = "";
	var uiws = Docova.getUIWorkspace(document);
	var folderControlWin = uiws.getDocovaFrame("foldercontrol", "window");
	var fpath = "";
	if (folderControlWin) {
		fpath = folderControlWin.DLITFolderView.getFolderPathByID(libid);

	}

	var files = evt.originalEvent.dataTransfer.files;
	var flArr = [];
	for (var x = 0; x < files.length; x++) {
		flArr.push(files[x].name);
	}

	if (!ValidatePathLength(docInfo.LimitPathLength, fpath, flArr)){
		return;
	}

	var doctypearr = [];
	var DefaultDocType = "";

	var nodoctypesfound = false;
	var folderpropUrl = liburl + "/xFolderProperties.xml?ReadForm&ParentUNID=" + id;
	jQuery.ajax({
		type: "GET",
		url: folderpropUrl,
		cache: false,
		async: false,
		dataType: "xml",
		success: function (xml) {
			var xmlobj = jQuery(xml);
			var doctypenode = xmlobj.find("DocumentType");
			if (doctypenode.children().length == 0) {
				nodoctypesfound = true;
				return;
			};

			var tmpdefault = jQuery.trim(xmlobj.find("DefaultDocumentType").text());
			if (tmpdefault != "None"){
				DefaultDocType = tmpdefault;
			}
			
			for (var p = 0; p < doctypenode.children().length; p++) {
				var curnode = $(doctypenode.children[p]);
				var key = curnode.find("key").text();
				doctypearr.push(key);
			}
		}
	});

	if (nodoctypesfound) {
		alert("No document type has been specified for this folder.  Unable to create documents.")
		return;
	}

	if (DefaultDocType != "") {
		defDocType = DefaultDocType
	} else if (doctypearr.length == 1 && doctypearr[0] != "") {
		defDocType = doctypearr[0];
	} else {
		var dlgUrl = liburl + "/dlgSelectDocType?OpenForm&ParentUNID=" + id;
		var tmpDocova = (window.top.Docova ? window.top.Docova : Docova);
		var doctypedlg = tmpDocova.Utils.createDialog({
				id: "divDlgSelectDocType",
				url: dlgUrl,
				title: "Select Document Type",
				height: 425,
				width: 400,
				useiframe: true,
				buttons: {
					"Continue": function () {
						var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
						if (result && result.DocumentType) {
							var defkey = result.DocumentType;

							doctypedlg.closeDialog();
							createDocsDragDrop(liburl, defkey, id, files);
						}
					},
					"Cancel": function () {
						doctypedlg.closeDialog();
					}
				}
			});

	}

	if (defDocType != ""){
		createDocsDragDrop(liburl, defDocType, id, files);
	}
}

function createDocsDragDrop(liburl, defDocType, id, files) {
	var elementURL = liburl + "/Document?OpenForm&ParentUNID=" + id + "&Seq=1&typekey=" + defDocType
		var inputid = ""
		jQuery.ajax({
			url: elementURL,
			async: false,
			success: function (results) {
				//Get/Set formname
				var res = $(results).find('#fileAttach');
				inputid = res.attr("name");
			}
		});

	if (inputid == "") {
		alert("Could not find uploader on the document type " + defDocType + ".  Exiting")
		return;
	}

	if (files.length > 0) {
		var um = new uploadMultiple({
				"files": files,
				"inputid": inputid,
				"liburl": liburl,
				"defDocType": defDocType,
				"id": id,
				"onComplete": function () {
					try {
						objView.Refresh(true, false, true);

					} catch (e) {}

				}
			});
		um.start();
	}

}

function SubmitViewRequest(request, agentName){
	//send the request to server
	var processUrl = docInfo.ServerUrl + docInfo.PortalWebPath + "/" + agentName  + "?OpenAgent"
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){
		HideProgressMessage();
		return false;
	}

	return (httpObj.results.length)? httpObj.results[0] : true;
}
