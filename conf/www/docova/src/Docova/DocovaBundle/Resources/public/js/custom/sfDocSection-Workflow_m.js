
// onchange handler for the workflow template selection
function SwitchWorkflow(processId)
{
if(!processId)
 {
  document.all.DataEdit.style.display="none";
  return false;
 }

// get new data

var wfUrl =  docInfo.PortalWebPath +  "/xViewData.xml?ReadForm&view=xmlWorkflowStepsByProcessId&col=2&lkey="  + processId;

var wfData = document.getElementById("ProcessData");
var xmlDoc = wfData.XMLDocument;



var xmlObj = new ActiveXObject ("Microsoft.XMLHTTP"); 
xmlObj.open ("GET", wfUrl, false);
xmlObj.send();
xmlDoc.async = false;   
xmlDoc.loadXML(xmlObj.responseXML.xml)


return true;
}


function insertWorkflow( processId, insertBeforeItemTitle )
{
	if (!processId) { 
		document.all.DataEdit.style.display="none"; 
		return false; 
	} 

	// get new data 
	var wfUrl = docInfo.PortalWebPath +  "/xViewData.xml?ReadForm&view=xmlWorkflowStepsByProcessId&col=2&lkey="  + processId; 
	var wfData = document.getElementById("ProcessData"); 
	var xmlDoc = wfData.XMLDocument; 

	var xmlObj = new ActiveXObject ("Microsoft.XMLHTTP"); 
	xmlObj.open ("GET", wfUrl, false); 	
	xmlObj.send(); 

	var oXml =new ActiveXObject("Microsoft.XMLDOM"); 

	oXml.async = false;   
	oXml.loadXML(xmlObj.responseXML.xml) 

	if (oXml.parseError.errorCode != 0) { 
        var myErr = oXml.parseError; 
		alert (prmptMessages.msgWF001 + myErr.reason); 
	} else { 
	} 

	var root =  oXml.documentElement; 
	var currNode = xmlDoc.documentElement.lastChild; 

	//remove the isCurrentItem flag if it is set. 
	var fixNode= oXml.selectSingleNode('Documents/Document/wfIsCurrentItem[.="1"]' );
	if (fixNode != null) {
		fixNode.firstChild.nodeValue = 0;
	}

	var insertBeforeNode = null;
	if ( insertBeforeItemTitle != null ) { 
        var qry= 'Documents/Document[wfTitle="' + insertBeforeItemTitle + '"]'; 
        insertBeforeNode = xmlDoc.selectSingleNode (qry);
	} 

	if(insertBeforeNode != null){
		var stepaction = jQuery(insertBeforeNode).find("wfAction").text();
		if (stepaction == "Start"){
			//dont allow inserting before the start step
			insertBeforeNode = null;
		}
	}

	if(insertBeforeNode == null){
		//locate the end step of the existing workflow
		var qry= 'Documents/Document[wfAction="End"]'; 
		insertBeforeNode = xmlDoc.selectSingleNode (qry);			
	}
	
	for ( i=0; i < root.childNodes.length; i ++ ){ 
        var newNode = root.childNodes.item(i).cloneNode (true ); 
		var stepaction = jQuery(newNode).find("wfAction").text();
		if(stepaction != "Start" && stepaction != "End"){ 
        	xmlDoc.documentElement.insertBefore( newNode, insertBeforeNode ); 
		}
	} 

	//renumber all the nodes 
	var nodeList = xmlDoc.selectNodes( 'Documents/Document/wfOrder' ); 
	if(nodeList.length ==0){ return false;} 
	var txtnode; 
	var ctr = 0; 
	while( (objNode = nodeList.nextNode())){ 
        var txtNode = objNode.firstChild; 
        if ( txtNode != null ) { 
            txtNode.nodeValue= ctr; 
        }else{ 
            txtNode = xmlDoc.createTextNode('wfOrder'); 
            txtNode.nodeValue = ctr; 
            objNode.appendChild ( txtNode ); 
        } 
        ctr++; 
	} 

	ProcessWorkflow("UPDATE"); 
	return true; 
}


function isFieldCdata ( fieldname ){
     return false;  //TODO fix this for use in mobile
	var xmlDoc = ProcessData;

	//find the row we need to process...
	var isCdata = false;
	
	var fixNode = xmlDoc.selectSingleNode('Documents/Document/' + fieldname + '[0]');
	if ( fixNode != null ) {
		if ( fixNode.firstChild.nodeType == 4 ) isCdata = true;
	}

	return isCdata;
}

function handleCdataField ( key, field, newValue ){
	return; //TODO - update this function for use in mobile
	var table = document.all.tblDataEdit;
	var tbody = table.tBodies[key];
	if ( tbody ==null ) return;
	var row = tbody.rows[0];
	if ( row == null ) return;
	var tds = row.childNodes;
	for ( j = 0; j < tds.length; j ++){
		var elm = tds[j].firstChild;
	     if ( elm.dataFld == field ) elm.innerText = newValue;
	}

}



//---------- allows to customize a workflow step --------
function ChangeWorkflowStep(keyType, key, field, value, replaceToken, force )
{	var newValue;

	if(!HasWorkflowData()){
			return false;
		}

	if(keyType != "wfOrder" && keyType != "wfTitle"){
		return false;
	}
	
	if(field.toLowerCase() == "wfreviewerapproverlist") {field = "wfDispReviewerApproverList";}
	
	//check if field is a cData field
	var isCdata = isFieldCdata ( field );
	
	var currentStep = GetCurrentWorkflowStep();
	var currentStepNo = (currentStep)? jQuery(currentStep).find("wfOrder").text() : "0";
	var rs = jQuery(ProcessData).find('Document');
	for(var i=0; i<rs.length; i++)
	{
     	if(jQuery(rs[i]).find(keyType).text() == key){
			// found selected step, now check if it is still pending
			if(jQuery(rs[i]).find("wfOrder").text() <= currentStepNo){
				//step already completed, no changes allowed
				return false;
			}
			
			if ( replaceToken ) {
				newValue = jQuery(rs[i]).find(field).text().replace(replaceToken, value);
			}else{
				newValue = value;
			}
			
			 jQuery(rs[i]).find(field).text(newValue);
			//now lets manually update the value in the span tag if its a CDATA type node...
			if ( isCdata ){handleCdataField ( key, field, newValue )}
			 jQuery(rs[i]).find("Modified").text("1");
			return true;
		}
	}
	return false;
}

function ChangeWorkflowStepGroup(field, value, replaceToken )
{
	if(!HasWorkflowData()){
			return false;
		}

	if(field.toLowerCase() == "wfreviewerapproverlist") {field = "wfDispReviewerApproverList";}
	
	//check if field is a cData field
	var isCdata = isFieldCdata ( field );	

	var currentStep = GetCurrentWorkflowStep();
	var currentStepNo = (currentStep)? currentStep.Fields("wfOrder").Value : "0";
	var rs = doc.ProcessData.recordset;
	rs.MoveFirst
	while(!rs.EOF){
			if(rs.Fields("wfOrder").Value >= currentStepNo){
				if(replaceToken){
					newValue = rs.Fields(field).Value.replace(replaceToken,value);			
				}
				else{	
					newValue = value;
				}
				rs.Fields(field).Value = newValue;
				
				//now lets manually update the value in the span tag if its a CDATA type node...
				if ( isCdata ){handleCdataField ( rs.Fields("wfOrder").Value, field, newValue )}			
				rs.Fields("Modified").Value = "1";
			}
		rs.MoveNext;
	}
	return false;
}

//----- modifies the workflow data defaults -----
function SetDataDefaults(dataSource)
{
	
	// --- replace all instances of [Author] with the author's name ---
	
	if(!HasWorkflowData())
		{
			jQuery("#DataEdit").hide();
			return false;
		}
	jQuery("#DataEdit").show();
	

	var rs = jQuery(dataSource).find( 'Document' );
	if(rs.length > 0)
	{
	     rs.find("wfReviewerApproverList").each(function(){
	     	var tmpNames = jQuery(this).text();
	     	jQuery(this).text(tmpNames.replace("[Author]", docInfo.UserNameAB));
	     	jQuery(this).siblings("Modified").text("1");
	     });
	     rs.find("wfDispReviewerApproverList").each(function(){
	     	var tmpNames = jQuery(this).text();
	     	jQuery(this).text(tmpNames.replace("[Author]", docInfo.UserNameAB));
	     	jQuery(this).siblings("Modified").text("1");
	     });
    }
      
    var $page = "";
    if(wfInfo.qsType == "workflow") {
		$page = $(document); 
    } else {
		$page = jQuery("#" + jQuery.mobile.activePage.attr('id')); 			
    }
      
	$page.find("#tblDataEdit2").find('tbody').html("");
	$(rs).each(function(){
		
		var wftitle = $(this).find('wfTitle').text();
		var wfstatus = $(this).find('wfStatus').text();
		var wfaction = $(this).find('wfAction').text();
		var wfpending = $(this).find('wfReviewerApproverList').text();
		if ( wfpending =="" ) wfpending = "&#160;";
		var wfcompleted = $(this).find('wfReviewApprovalComplete').text();
		var wforder = $(this).find('wfOrder').text();
		var trhtml = "";
		var tdhtml = "";
		if ( wfstatus == "Pending" ) {
			trhtml = "<tr class='wfpending'>";
			tdhtml = "<td style='color:green'>";
		}else{
			trhtml = "<tr class ='wfcomplete'>"
			tdhtml = "<td>";
		}

		$page.find("#tblDataEdit2").find('tbody').append(trhtml + '<th>' + wftitle + '</th>' + tdhtml + wfstatus + '</td><td>' + wfpending + '</td><td>' + wfcompleted + '</td></tr>')
		
	});

	$page.find( "#tblDataEdit2" ).table( "refresh" );
}

// --- sets the default properties of items loaded in the workflow data table ----
function SetItemProperties(dataTable)
{

	if(!HasWorkflowData())
		{
			return false;
		}
	if (dataTable.readyState == "complete" )
	{
		var docInfo = document.getElementById("info"); 
		var rs = ProcessData.recordset;
		if(rs.RecordCount == 0){return;}
		rs.MoveFirst;
		while(!rs.EOF)
		{
		if(rs.FIELDS.Count > 0)
			{
			if(rs.FIELDS("wfIsCurrentItem").Value =="1") //highlight current item
			{
				var textSpan = dataTable.rows[rs.AbsolutePosition].cells[0].getElementsByTagName("SPAN")[0];
				textSpan.runtimeStyle.fontWeight="bold";
				dataTable.rows[rs.AbsolutePosition].runtimeStyle.backgroundColor="#dfefff";
			}
			if(rs.FIELDS("wfStatus").Value=="Pending"  && docInfo.isDocBeingEdited )
				{
				//-- show name selection button next to pending entries with enabled customization ----- 
				var addressButton = dataTable.rows[rs.AbsolutePosition].getElementsByTagName("BUTTON")[0];
				addressButton.style.display = (rs.FIELDS("wfReviewerApproverSelect").Value || rs.FIELDS("wfReviewerApproverSelect").Value == '[Please Select]')? "" : "none";
				}
			}
		rs.MoveNext
		}
	}
}

//--- select workflow participants for steps that can be customized----
function SelectParticipants(clickedObj)
{
	var rs = ProcessData.recordset;
	rs.AbsolutePosition = clickedObj.recordNumber;
	//----------------------------------
	var dialogType = (rs.Fields("wfType") == "Serial")? "single" : "multi";
	var ret = showNamePicker(rs.Fields("wfDispReviewerApproverList").Value, dialogType, "," ,true);
	if(ret == null) {return;}
	//----------------------------------
	rs.Fields("wfDispReviewerApproverList").Value = ret;
	rs.Fields("Modified").Value = "1";
	
	//if this is a cdata filed, then we need to refresh the html table manually...bug in recordset object with cdata type fields.
	if ( isFieldCdata ("wfDispReviewerApproverList" ) ) handleCdataField ( (clickedObj.recordNumber-1), "wfDispReviewerApproverList", ret )
	

	//--- need to update workflow if already created or store for update on save --			
	if(docInfo.isWorkflowCreated)
		{		
			ProcessWorkflow("UPDATE");
			//-- but only clear the modified flag if the document is not being edited						
			if(! docInfo.isDocBeingEdited){			
				rs.Fields("Modified").Value = "0";
			}
			//HandleSaveClick();
		}
}

//-- starts workflow on the document ---
function StartWorkflow()
{
     
	//Provide a way to determine whether doc is just being saved or saved and submitted to wf.  Handy for validation purposes.
	wfInfo.wfStartTriggered = "1"
	
	if(!IsValidData()) {
		wfInfo.wfStartTriggered = "";
		return false;
	}
	
	if (UserHasFilesCheckedOut())
		{
			alert(prmptMessages.msgWF002);
			wfInfo.wfStartTriggered = "";
			return false
		}
		
	//----- Initial inquiry -----
	var submitChoice = confirm(prmptMessages.msgWF009);
	if (! submitChoice)
		{
			wfInfo.wfStartTriggered = "";
			return(false);
		}
     showSpinner();
	//----------------------------------
	if( ! ValidateWorkflow("Start")) {
		wfInfo.wfStartTriggered = "";
		return false;
	}
	//----------------------------------
	var $page = jQuery("#" + jQuery.mobile.activePage.attr('id'));
	$page.find("[name=isWorkflowStarted]:first").val(1);
	
	if  ( ! onWorkflowStart() ) { 		
		wfInfo.wfStartTriggered = "";
		$page.find("[name=isWorkflowStarted]:first").val("");
		return false;
	}
	
	var additionalHeader = "";

	var currentStep = GetCurrentWorkflowStep();
	if(currentStep) 
	{
	//--- process on server --
	var action = (docInfo.isWorkflowCreated)? "COMPLETE" : "START";

	if(ProcessWorkflow(action, additionalHeader))
		{	
			HandleSaveClick(true);
		}
	}

}


// ----- process the "Decline" action-----	
function DenyWorkflowStep()
{
	if(!HasWorkflowData())
		{
			return false;
		}
		
	if (UserHasFilesCheckedOut())
		{
			alert(prmptMessages.msgWF003)
			return false
		}

	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	var userComment = "";
	
	if(currentStep)
		{
	//----- Initial inquiry -----
	var submitChoice = confirm(prmptMessages.msgWF011);
	if (!submitChoice)
		{
			return(false);
		}
		var userComment = GetComment("Please specify the reason:", true);
	     showSpinner();
		if( ! userComment ) {return false; }

		additionalHeader += "<UserComment><![CDATA[" + userComment +  "]]></UserComment>";
		
		//API HOOK
		
		if ( ! onDeny() ) { return false; }
		
		//--- process on server --
		if(ProcessWorkflow("DENY", additionalHeader))
			{
			//----- Add to additional comments subform section on document if set -----
			if(docInfo.isLinkComments){
				var addrequest = "<UserComment><![CDATA[" + "Declined: " + userComment +  "]]></UserComment>";
				addrequest += "<CommentType>LC</CommentType>";				
				if(ProcessComment("LOGCOMMENT", addrequest, true)){
					try{
//							doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
						}
					catch (e){}
				}
			}
				HandleSaveClick(true);
			}
		}
}

// ----- process the "Approve" action-----	
function ApproveWorkflowStep()
{
	if(!HasWorkflowData())
		{
			hideSpinner();
			return false;
		}
		
	if (UserHasFilesCheckedOut())
		{
			hideSpinner();
			alert(prmptMessages.msgWF004)
			return false
		}
	
	var submitChoice = confirm("You are about to approve this document.  Are you sure?");
	if (! submitChoice)
		{
			return false;
		}
     showSpinner();
	//do field validation
     if(!IsValidWfData("Approve")) {hideSpinner(); return false;}
	var additionalHeader = "";
	var userComment = "";
	var currentStep = GetCurrentWorkflowStep();	
	if(currentStep)
	{		
		//API HOOK		
		if ( ! onApprove() ) {
		     hideSpinner();			
			return false; 
		}
		//ensure currentStep resultset pointer correct as may be reset by custom functions
		currentStep = GetCurrentWorkflowStep();
		if(jQuery(currentStep).find("wfOptionalComments").text() == "1"){
			var userComment = GetComment("Please enter any comments (optional):", false);
			if(userComment == null || userComment === false) {return false}
			additionalHeader += "<UserComment><![CDATA[" + userComment +  "]]></UserComment>";
		}			

		//--- process on server --
		if(ProcessWorkflow("APPROVE", additionalHeader))
		{
			//----- Add to additional comments subform section on document if set -----
			if(docInfo.isLinkComments){
				if(userComment) {
					var addrequest = "<UserComment><![CDATA[" + "Approved: " + userComment +  "]]></UserComment>";
					addrequest += "<CommentType>LC</CommentType>";					
					if(ProcessComment("LOGCOMMENT", addrequest, true)){
						try{
//								doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
							}
						catch (e){}
					}
				}
			}
				HandleSaveClick(true);
			}
		}
}


// ----- process the "Complete" action-----	
function CompleteWorkflowStep()
{
	if(!HasWorkflowData())
		{
			return false;
		}
		
	if (UserHasFilesCheckedOut())
		{
			alert(prmptMessages.msgWF005);
			return false
		}
		
	var submitChoice = confirm("You are about to complete the review of this document.  Are you sure?");
	if (! submitChoice)
		{
			return false;
		}
    showSpinner();
	//set the action field that will tell validateWorkflow function what the user is trying to do at this point 
	
     if(!IsValidWfData("Complete")) {return false;}
	var additionalHeader = "";
	userComment = ""; 
	var currentStep = GetCurrentWorkflowStep();
	if(currentStep)
		{
	
		//API HOOK
		
		if ( ! onComplete() ) { return false;}
		//ensure currentStep resultset pointer correct as may be reset by custom functions
		currentStep = GetCurrentWorkflowStep();
		
		if(jQuery(currentStep).find("wfOptionalComments").text() == "1"){
			var userComment = GetComment("Please enter any comments (optional):", false);
			if(userComment == null || userComment === false) {return false}
			additionalHeader += "<UserComment><![CDATA[" + userComment +  "]]></UserComment>";
		}		
		
		//--- process on server --	
		if(ProcessWorkflow("COMPLETE", additionalHeader))
			{
			//----- Add to additional comments subform section on document if set -----
			if(docInfo.isLinkComments){
				if(userComment) {
					var addrequest = "<UserComment><![CDATA[" + "Reviewed: " + userComment +  "]]></UserComment>";
					addrequest += "<CommentType>LC</CommentType>";
					if(ProcessComment("LOGCOMMENT", addrequest, true)){
						try{
//								doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
							}
						catch (e){}
					}
				}
			}
				HandleSaveClick(true);
			}
		}
}

// ----- process the "Finish workflow" action-----	
function FinishWorkflow()
{
	//----Run custom on before release JS function if identified---//
	if(!CustomOnBeforeReleaseHandler()){
		return false;
	}
	if(!HasWorkflowData())
		{
			return false;
		}
	if (UserHasFilesCheckedOut())
		{
			alert(prmptMessages.msgWF006);
			return false
		}
		
	var submitChoice = confirm("You are about to release/publish this document.  Are you sure?");
	if (! submitChoice)
		{
			return false;
		}
		
	 //do field validation
     if(!IsValidWfData("Finish")) {return false;}
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	var userComment = "";
	
	if(currentStep)
		{
			if(docInfo.EnableVersions && false)  //*TODO - false added to disable this code, fix
				{
					var params = new Array();
					params[0] = docInfo.AvailableVersionList; 
					params[1] = docInfo.FullVersion; 
					params[2] = docInfo.isInitialVersion; 
					params[3] = docInfo.StrictVersioning;
					
					var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowDocRelease?OpenForm";
					var dlgSettings = "dialogHeight:270px;dialogWidth:435px;center:yes; help:no; resizable:no; status:no;";
					var retVal = window.showModalDialog(dlgUrl,params,dlgSettings); //Display the version dialog
					if( !retVal ) {return false; } //cancelled
					if( !retVal[0] ) {return false; } //version was not provided
		
					additionalHeader += "<Version>" + retVal[0] +  "</Version>";
					additionalHeader += "<UserComment><![CDATA[" + retVal[1] +  "]]></UserComment>";
				}
			else
				{
					var userComment = GetComment("Please enter comments (optional):", false);
					if( userComment == null || userComment === false) {return false; }
					var releaseVersion = (docInfo.FullVersion == '0.0' || docInfo.FullVersion == '0.0.0') ? '1.0.0' : docInfo.FullVersion;
					additionalHeader += "<Version>" + releaseVersion + "</Version>";
					additionalHeader += "<UserComment><![CDATA[";
					additionalHeader += (userComment)? userComment : ""; //userComment will be false if the dialog is cancelled
					additionalHeader += "]]></UserComment>";
				}
			//--- process on server --
	     showSpinner();
		if(ProcessWorkflow("FINISH", additionalHeader)){
			//----- Add to additional comments subform section on document if set -----
			if(docInfo.isLinkComments){
				var requestcomment =  (userComment)? userComment : (retVal ? retVal[1] : "");
				var addrequest =  "<UserComment><![CDATA[" + "Finish: " + requestcomment +  "]]></UserComment>"
				addrequest += "<CommentType>LC</CommentType>";				
				if(ProcessComment("LOGCOMMENT", addrequest, true)){
					try{
//							doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
						}
					catch (e){}
				}
			}	
			
			//-----Set tmpVersion field to make available for onAfterRelease event------
			if(docInfo.EnableVersions && false){  //*TODO - false added to disable this code, fix
				var $page = jQuery("#" + jQuery.mobile.activePage.attr('id'));
				$page.find("[name=tmpVersion]:first").val(retVal[0]);
			}else{			
				var $page = jQuery("#" + jQuery.mobile.activePage.attr('id'));
				$page.find("[name=tmpVersion]:first").val(docInfo.FullVersion);
			}

			if(!CustomOnAfterReleaseHandler()){
				return false;
			}
			
				HandleSaveClick(true);
			}
		}
}


function DeleteWorkflowStepByKey(keyType, key)
{
	
	if(keyType =="" || key ==""){ return false;}
	if(!HasWorkflowData()){return false;}
	var currentStep = GetCurrentWorkflowStep();
	if(!currentStep){ return false;}
	
	var currentStepNo = (currentStep)? jQuery(currentStep).find("wfOrder").text() : "0";
	
	
	var additionalHeader = "";
	var rs = jQuery(ProcessData).find('Document');
	if(rs.length ==0){ return false;}
	
	var changesmade = false;
	
	//-- loop through the workflow steps
	for(var i=0; i<rs.length; i++){
		var steporder = jQuery(rs[i]).find("wfOrder").text();
		var stepaction = jQuery(rs[i]).find("wfAction").text();
		var stepkey = jQuery(rs[i]).find(keyType).text();
		//-- check for matching key greater than current step and not start or end step
		if((stepkey == key) && (steporder > currentStepNo) && (stepaction != "Start") && (stepaction != "End")){			
			 jQuery(rs[i]).find("Selected").text("1");
			 changesmade = true;
		}
	}//end forloop
	
	if (changesmade){	
		var parentNode= jQuery(ProcessData).find('Documents');
		//--- process on server --
		if(docInfo.isWorkflowCreated){		
			if(! ProcessWorkflow("DELETE", additionalHeader, true))	{
				return false;
			}
		}
		//--- remove deleted nodes from current doc
		for(var i=0; i<rs.length; i++){
			if (jQuery(rs[i]).find("Selected").text() == "1"){
				jQuery(rs[i]).remove();
			}
		}
	}
}//--end DeleteWorkflowStepByKey


function DeleteWorkflowStep()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	
	xmlDom = doc.ProcessData.XMLDocument;
	var query = 'wfAction != "Start" and wfAction != "End" and wfOrder > ' + currentStep.FIELDS("wfOrder").Value;
	var nodeList = xmlDom.selectNodes( 'Documents/Document[' + query + ' ]' );
	if(nodeList.length ==0){
		return alert(prmptMessages.msgWF007);
	}
	//----- Initial inquiry -----
	var labeltext = "Please select the steps to delete:"; 
	var stepNoList = SelectWorkflowStep(nodeList, true, labeltext ); //let user select one of the completed steps

	if( stepNoList ==null) {return false; }

	var node = null;
	var selectEl = null;
	var stepIdxArray = stepNoList.split(",");
	parentNode=xmlDom.selectSingleNode( 'Documents');
	//------------------------------------------------------
	for (var k=0; k< stepIdxArray.length; k++)
		{
			query = 'wfOrder = ' + stepIdxArray[k];
			node = xmlDom.selectSingleNode( 'Documents/Document[' + query + ' ]' );
			if(node) {
				selectEl = node.selectSingleNode( 'Selected');
				selectEl.text="1";
			}
		}

		//--- process on server --
		if(docInfo.isWorkflowCreated)
		{		
		if(ProcessWorkflow("DELETE", additionalHeader, true))
			{
				//HandleSaveClick(true);
				doc.ProcessData.src=doc.ProcessData.src;
				return;
			}
		}
		else{
			//--- remove deleted nodes from current doc
			for (var k=0; k< stepIdxArray.length; k++)
				{
					query = 'wfOrder = ' + stepIdxArray[k];
					node = xmlDom.selectSingleNode( 'Documents/Document[' + query + ' ]' );
					if(node) {
						parentNode.removeChild(node);
					}
				}
		}

}

function CancelWorkflow()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	var userComment = "";
	
	if(currentStep)
		{
	//----- Initial inquiry -----
		var submitChoice = thingFactory.Messagebox(prmptMessages.msgWF013, 4+32, prmptMessages.msgWF012)
	if (submitChoice == 7)
		{
			return(false);
		}
		var userComment = GetComment("Please specify the reason:", true);

		if( !userComment ) {return(false); }

		additionalHeader += "<UserComment><![CDATA[" + userComment +  "]]></UserComment>";
		//--- process on server --
		ProcessWorkflow("CANCEL", additionalHeader);
		//----- Add to additional comments subform section on document if set -----
		if(docInfo.isLinkComments){
			var addrequest = "<UserComment><![CDATA[" + "Cancelled: " + userComment +  "]]></UserComment>";
			addrequest += "<CommentType>LC</CommentType>";			
			if(ProcessComment("LOGCOMMENT", addrequest, true)){
				try{
						doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
					}
				catch (e){}
			}
		}		
		HandleSaveClick(true);
		
		}
}

function PauseWorkflow()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	var userComment = "";
	
	if(currentStep)
		{
	//----- Initial inquiry -----
		var submitChoice = thingFactory.Messagebox(prmptMessages.msgWF014, 4+32, prmptMessages.msgWF012);
	if (submitChoice == 7)
		{
			return(false);
		}
		var userComment = GetComment("Please specify the reason:", true);

		if( !userComment ) {return(false); }

		additionalHeader += "<UserComment><![CDATA[" + userComment +  "]]></UserComment>";
		//--- process on server --
		ProcessWorkflow("PAUSE", additionalHeader);
		//----- Add to additional comments subform section on document if set -----
		if(docInfo.isLinkComments){
			var addrequest = "<UserComment><![CDATA[" + "Paused: " + userComment +  "]]></UserComment>";
			addrequest += "<CommentType>LC</CommentType>";			
			if(ProcessComment("LOGCOMMENT", addrequest, true)){
				try{
						doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
					}
				catch (e){}
			}
		}		
		HandleSaveClick(true);
		
		}
}

function BacktrackWorkflow()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();

	//----- Initial inquiry -----
	var submitChoice = thingFactory.Messagebox(prmptMessages.msgWF015, 4+32, prmptMessages.msgWF012);
	if (submitChoice == 7)
		{
			return(false);
		}
		if(currentStep)
		{
		xmlDom = doc.ProcessData.XMLDocument;
		var query = "wfOrder <= " + currentStep.FIELDS("wfOrder").Value;
		var nodeList = xmlDom.selectNodes( 'Documents/Document[' + query + ' ]' );
		if(nodeList.length <=1)
			{
				var stepNo = 0 //start from begining
			}
			else
			{
				var labeltext = "Please select the workflow step to backtrack to:"; 
				var stepNo = SelectWorkflowStep(nodeList, false, labeltext ); //let user select one of the completed steps
			}
		}

		if(stepNo == null ) {return; }

		additionalHeader += "<ProcessedStep>" + stepNo +  "</ProcessedStep>";
		//--- process on server --
		if(ProcessWorkflow("BACKTRACK", additionalHeader))
			{
				HandleSaveClick(true);
			}
		}

//--- processess the workflow on the server ---
function ProcessWorkflow(action, additionalHeader, processNow)
{
     var result = false;
//	ShowProgressMessage("Processing workflow request. Please wait...")
	var wfNodes = jQuery(ProcessData).find( 'Document' );

	if (wfNodes.length == 0) {
//	   HideProgressMessage();
	   return;
	}
	var request="";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<ServerUrl>" + docInfo.ServerUrl + "</ServerUrl>";
	request += (additionalHeader)? additionalHeader : "";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<EditMode>" + docInfo.isDocBeingEdited + "</EditMode>";
	
	request += serializeXML(wfNodes);

	request += "</Request>";

	if(docInfo.isDocBeingEdited && !processNow) //edit mode, doc WQS agent will take care of the request
		{	
			if (docInfo.isMobileWorkflowSave =="1") {
				$("#tmpWorkflowDataXml").val(request);
			}else{
				var $page = jQuery("#" + jQuery.mobile.activePage.attr('id'));
				$page.find("[name=tmpWorkflowDataXml]:first").val(request);
			}

			return true;
		}
	else // read mode, process via workflow services agent
		{			
			var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
			
			jQuery.ajax({
				url: url, 
				data: request,       
				cache: false, 
				async: false, // this was false. should be true but breaks return to workflow screen
				type: 'POST',
				contentType: "text/xml",
				dataType: "xml"
	      	})
			.success( function (data, textStatus, jqxhr) {
	    			 try{
					var $xml = jQuery(data);
					if($xml.find("Result:first").text() == "OK"){
					 var MobileWorkflowFlag=docInfo.isMobileWorkflowSave;
					 var MobileAppDevice=docInfo.MobileAppDevice;
						console.log( "isMobileWorkflowSave: "+ MobileWorkflowFlag );
						console.log( "MobileAppDevice: "+ MobileAppDevice );
						
					 	if (docInfo.isMobileWorkflowSave =="1") {
					 		if (docInfo.MobileAppDevice =="ios"){
						 		showIOSWorkflowScreen();
					 		}else if (docInfo.MobileAppDevice =="android") {
					 			showAndroidWorkflowScreen();
					 		}
					 		
					 	}
					 	else{
					 			    result = true;
								   return result;
					 	}
					 	

					}
				  }catch(e){}
	      	})
			.error( function (jqxhr, ajaxOptions, thrownError) {});             
	}
	
	return result;	
}


 function showAndroidWorkflowScreen() {
        //alert("redirecting to workflow screen")
        DOCOVA_MOBILE_HOOK.showWorkflowActionScreenFromDocument();
 }
 function showIOSWorkflowScreen() {
        window.location.href="https://www.docova.net/workflowcompletedtoken/";
   }
    
//--- check the basic requirements for the workflow ---
function ValidateWorkflow(action)
{

	if(!HasWorkflowData() && wfInfo.HasMultiWorkflow)
		{
			SelectWorkflow();
			if(!HasWorkflowData()) {return false;}
		}
	else if(!HasWorkflowData() && !wfInfo.HasMultiWorkflow)
		{
			alert(prmptMessages.msgWF016);
			return(false);
		}

	if(!HasWorkflowData())
		{
			alert(prmptMessages.msgWF018);
			return false;
		}
		
	var rs = jQuery(ProcessData).find('Document');
	if(rs.length == 0)
	{
		alert(prmptMessages.msgWF016);
		return(false);
	}

	//----- make sure that the names are selected for each workflow step ----
	for(var i=0; i<rs.length; i++)
	{
        if(jQuery(rs[i]).find("wfDispReviewerApproverList").text() == "" || rs.Fields("wfDispReviewerApproverList").Value == "[Please Select]")
	     {
			alert(prmptMessages.msgWF019)
			return(false);
		}
     }
     
     //do field validation
     return IsValidWfData(action);
}



// ------ locates current workflow step in the recordset ----
function GetCurrentWorkflowStep()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var rs = jQuery(ProcessData).find('Document');
	
	//---- find current record ------
	for(var i=0; i<rs.length; i++)
	{
		if(jQuery(rs[i]).find("wfIsCurrentItem").text()=="1") // if current item
		{
			return rs[i];
		}
	}
	return false; //could not find current item
}

// displays dialog allowing user to select one or more workflow steps for further processing
function SelectWorkflowStep(nodeList, selectMulti, fieldLabel)
{
var params = new Array();
params[0] = nodeList;
params[1] = (selectMulti)? selectMulti : false;
params[2] = fieldLabel;

var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowSelectStep?OpenForm";
var dlgSettings = "dialogHeight:240px;dialogWidth:460px;center:yes; help:no; resizable:no; status:no;";
return window.showModalDialog(dlgUrl,params,dlgSettings); //Display the selection dialog
}

// displays dialog allowing user to select workflow applicable to the document
function SelectWorkflow()
{
	xmlDom = doc.ProcessSettings.XMLDocument;
	var nodeList = xmlDom.selectNodes( 'Documents/Document' );
	if(nodeList.length == 0)
		{
			return alert(prmptMessages.msgWF008);
		}

var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowSelectProcess?OpenForm";
var dlgSettings = "dialogHeight:240px;dialogWidth:460px;center:yes; help:no; resizable:no; status:no;";
retVal = window.showModalDialog(dlgUrl,nodeList,dlgSettings); //Display the selection
if(retVal == null) {return false;}
doc.wfWorkflowName.value = retVal[1];
doc.spanDispWorkflowName.innerText = retVal[1];
doc.wfEnableImmediateRelease.value = retVal[2];
doc.wfCustomizeAction.value = retVal[2];
return SwitchWorkflow(retVal[0]);
}

//------ removes the user name from the list ----
function RemoveName(name, fromList)
{
fromList = fromList.replace(name, "");
fromList = trim(fromList.replace(", ", ",").split(",")).join(", "); 
return fromList;
}

//------ adds user name to the list -----
function AddName(name, toList)
{
if(toList.indexOf(name) != -1) {return toList;} 
return  (toList)? toList + ", "  + name : name;
}


// ---------- workflow info request dialog --------
function RequestWorkflowInfo()
{
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	var userComment = "";
	
	if(currentStep)
		{
		var dlgUrl =docInfo.ServerUrl + "/" + NsfName + "/" + "dlgWorkflowInfoRequest?OpenForm&ParentUNID=" + docInfo.DocID + "&AllowPause=" + wfInfo.AllowPause;
		var dlgSettings = "dialogHeight:370px;dialogWidth:420px;center:yes; help:no; resizable:no; status:no;";
		var retValues =  window.showModalDialog(dlgUrl,window,dlgSettings); //Display the address dialog
		if(!retValues) {return false;}

		additionalHeader += "<SendTo><![CDATA[" + retValues[0] +  "]]></SendTo>";
		additionalHeader += "<Subject><![CDATA[" + retValues[1] +  "]]></Subject>";
		additionalHeader += "<UserComment><![CDATA[" + retValues[2] +  "]]></UserComment>";		
		additionalHeader += "<PauseWorkflow>" + retValues[3] +  "</PauseWorkflow>";		
		//--- process on server --
		ProcessWorkflow("INFO", additionalHeader);
		}
		

}
//==========================================================================================
// workflow submenu
//==========================================================================================

function CreateWorkflowSubmenu(actionButton) //creates right-click contect menu
{
	if(!actionButton) {return}
	var popup = new objPopupmenu();
	popup.textColumnWidth = 125;
	popup.actionHeight=18;
	var actionHandler = "parent.ProcessWorkflowSubmenuAction(this)";

//	addAction= function(isActive, isChecked, isBold, actionText, actionName, actionIconSrc, actionShortcutKeyText, actionHandler)
	var isChecked = false; 

	popup.addAction((wfInfo.AllowPause) && HasWorkflowData() && docInfo.isWorkflowCreated, false, false, "Request Information" , "requestinfo", "" , "", actionHandler);
	popup.addAction(HasWorkflowData() && docInfo.isWorkflowCreated, false, false, "Add comment" , "addcomment", "" , "", actionHandler);
	popup.addDivider();
	popup.addAction(HasWorkflowData() && (docInfo.isNewDoc || wfInfo.isStartStep) && doc.wfEnableImmediateRelease.value=="1", false, false, "Release document" , "release", "" , "", actionHandler);
	popup.addAction((wfInfo.AllowPause) && HasWorkflowData() && docInfo.isWorkflowCreated, false, false, "Pause workflow" , "pause", "" , "", actionHandler);
	popup.addDivider();
	popup.addAction(wfInfo.AllowCustomize && HasWorkflowData(), false, false, "Delete workflow step" , "delete", "" , "", actionHandler);
	popup.addAction(wfInfo.AllowBacktrack && HasWorkflowData(), false, false, "Backtrack workflow" , "backtrack", "" , "", actionHandler);
	popup.addAction(wfInfo.AllowCancel && HasWorkflowData(), false, false, "Cancel workflow" , "cancel", "" , "", actionHandler);

	popup.height = 151;
	popup.width = 131;
	popup.offsetTop= 15;
	popup.offsetRight = 0;

	var oPopBody = oPopup.document.body;
	oPopBody.innerHTML = popup.innerHTML();

	oPopup.show(0, 20, popup.width, popup.height, actionButton);
	return false;

}


//==========================================================================================
// submenu handler
//==========================================================================================

function ProcessWorkflowSubmenuAction(actionObj) //handle action from contect menu
{
	if(!actionObj ) {return false};
	if(oPopup) {oPopup.hide();}
	var action = actionObj.actionName.split("-")[0];
	if(action=="delete") {
		if(!CanModifyDocument()){return false;}
		DeleteWorkflowStep();
	}
	else if(action=="backtrack"){
		if(!CanModifyDocument()){return false;}
		BacktrackWorkflow();
	}
	else if(action=="requestinfo"){
		RequestWorkflowInfo();
	}
	else if(action=="cancel"){
		if(!CanModifyDocument()){return false;}
		CancelWorkflow();
	}
	else if(action=="pause"){
		if(!CanModifyDocument()){return false;}
		PauseWorkflow();
	}
	else if(action=="release"){
		if(!CanModifyDocument()){return false;}
		FinishWorkflow();
	}
	else if(action=="addcomment"){
		LogLifecycleComment();
	}

	return;
}

function HasWorkflowData()
{
    try{
	  var wfNode = jQuery(ProcessData).find( 'Document:first' );
	   return (wfNode!=null);
	}catch(err){
	  return (false);
	}
}


function serializeXML(data){
    var out = "";
    if (typeof XMLSerializer == 'function' || typeof XMLSerializer == 'object') {
        var xs = new XMLSerializer();
        jQuery(data).each(function() {
            out += xs.serializeToString(this);
        });
    } else if (data[0] && data[0].xml != 'undefined') {
        jQuery(data).each(function() {
            out += this.xml;
        });
    }
    return out;
};


function getXMLDocumentFromString(txt){
	if (window.DOMParser)
  	{
  		parser=new DOMParser();
  		xmlDoc=parser.parseFromString(txt,"text/xml");
  	}
	else // Internet Explorer
  	{
  		xmlDoc=new ActiveXObject("Microsoft.XMLDOM");
  		xmlDoc.async=false;
  		xmlDoc.loadXML(txt);
  }  
  return xmlDoc;
};


//-- check to see how many approvals remain for the current workflow step
//-- used in the release phase to determine if normal approval required instead of finish 
function countApprovalsRemaining(){
	var returnval = 0;
	
	var currentStep = GetCurrentWorkflowStep();
	
	if (! currentStep){
		return false;
	}
	
	var steptype = jQuery(currentStep).find("wfType").text();
	var completeany = jQuery(currentStep).find("wfCompleteAny").text();
	var approvers = jQuery(currentStep).find("wfReviewerApproverList").text();
	approvers = approvers.split(", ");
	
	if (steptype == "Serial"){
			returnval = 1;
	}else if (steptype == "Parallel"){
			if (completeany == "0"){  //-- everyone needs to approve
				var remainingapprovers = approvers.length;
				if (remainingapprovers.length == 1 && remainingapprovers[0] == ""){
					returnval = 0;
				}else{
					returnval = approvers.length;
				}
			}else if (completeany == "1"){  //-- any one can approve
				returnval = 1;
			}else if (completeany == "2"){  //-- at least x must approve
				var completedapprovers = jQuery(currentStep).find("wfReviewApprovalComplete").text();
				completedapprovers = completedapprovers.split(", ");
				if (completedapprovers.length == 1 && completedapprovers[0] == ""){
					var completedcount = 0;
				}else{
					var completedcount = completedapprovers.length;
				}

				var completecount = jQuery(currentStep).find("wfCompleteCount").text();
				var remaining = parseInt(completecount) - completedcount;
				returnval = (remaining < 0)? 0 : remaining;
			}
	}

	return returnval;
}			
