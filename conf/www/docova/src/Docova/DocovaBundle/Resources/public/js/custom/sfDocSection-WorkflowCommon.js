/*------------------------------------------------------------------------------------------------------------
 * Function: viewDelegates
 * Displays a list of delegates
 *------------------------------------------------------------------------------------------------------------*/
function viewDelegates() {
	var title = "No Delegates";
	var prompt = "No Reviewer/Approver Delegates were found for the current workflow step.";
	
	var request="";
	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>GETDELEGATES</Action>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "</Request>";
			
	var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/DocumentServices?OpenAgent";
	var httpObj = new objHTTP();		
	if(!httpObj.PostData(request, url) || httpObj.status=="FAILED") {
	
	} else if(httpObj.results && httpObj.results[0] && httpObj.results[0] != "") {
		title = "Delegates";
		prompt = httpObj.results[0];
	}
	
	window.top.Docova.Utils.messageBox({
		"title" : title,
		"prompt" : prompt,
		"icontype" : 1,
		"msgboxtype" : 0,
		"width" : 400						
	});	
	
}//--end viewDelegates

/*------------------------------------------------------------------------------------------------------------
 * Function: SwitchWorkflow
 * Changes the workflow process currently assigned to a document
 * Inputs: processId - string - id/key of the workflow process to switch to
 * Returns: boolean - true if workflow data retreived sucessfully, false otherwise
 *------------------------------------------------------------------------------------------------------------*/
function SwitchWorkflow(processId)
{
	var result = false;
	if(!processId){
  		jQuery("#" + wfcontainerid ).hide();
  		return result;
 	}

	// get new data
	var wfUrl =  docInfo.PortalWebPath +  "/xViewData.xml?ReadForm&view=xmlWorkflowStepsByProcessId&col=2&lkey="  + processId + "&cache=" + Date.now();
	LoadWorkflowSteps(wfUrl);
	result = true;

	return result;
}//--end SwitchWorkflow



/*------------------------------------------------------------------------------------------------------------
 * Function: StartWorkflow
 * Starts workflow on the document
 *------------------------------------------------------------------------------------------------------------*/
function StartWorkflow()
{
	
	//Provide a way to determine whether doc is just being saved or saved and submitted to wf.  Handy for validation purposes.
	wfInfo.wfStartTriggered = "1"
	
	if(!IsValidData()) {
		wfInfo.wfStartTriggered = "";
		return;
	}
	
	if (UserHasFilesCheckedOut())
	{
		alert(prmptMessages.msgWF002);
		wfInfo.wfStartTriggered = "";
		return;
	}
		
	//----- confirm that user wants to start workflow -----
	window.top.Docova.Utils.messageBox({
		"title" : prmptMessages.msgWF010,
		"prompt" : prmptMessages.msgWF009,
		"icontype" : 2,
		"msgboxtype" : 3,
		"width" : 400,
		"onYes" : function(){
					//-- start workflow logic --
					//-- validate workflow before starting --
					if( ! ValidateWorkflow("Start")) {
						wfInfo.wfStartTriggered = "";
						return;
					}
					//-- trigger workflow processes ---
					jQuery("#isWorkflowStarted").val("1");
					if  ( ! onWorkflowStart() ) {
//						jQuery("#isWorkflowStarted").val("0");					
						wfInfo.wfStartTriggered = "";
						return;
					}
					
					
					var additionalHeader = "";
					var currentStep = GetCurrentWorkflowStep();

					if(currentStep) 
					{
						var nextStepNo =  currentStep.Fields("wfOrder").getValue();

						if (  docInfo.IsAppForm == "1" )
						{
							nextStepNo = getNextWFStep(currentStep, "trueside");
							
						}else{
							nextStepNo = parseInt(nextStepNo) + 1;
						}
						
						if ( nextStepNo == -1){
							alert ( "Unable to get next step in workfow.  Exiting");
							return;
						}

						//--- process on server --
						var action = (docInfo.isWorkflowCreated)? "COMPLETE" : "START";
						if(ProcessWorkflow(action, additionalHeader, false,  nextStepNo))
						{
							if(docInfo.IsAppForm == "1" & !docInfo.isDocBeingEdited){
								var uidoc = Docova.getUIDocument();
								uidoc.close();
							}else{
								if (window.parent.fraTabbedTable){ 
									window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(docInfo.FolderUNID, docInfo.DocID);
								}
								HandleSaveClick(true);
							}
						}					
					}
					//--end workflow start code	
		},
		"onNo" : function(){
					wfInfo.wfStartTriggered = "";								
		},
		"onCancel" : function(){
					wfInfo.wfStartTriggered = "";								
		}
	});	
	//-- do not add code here, as it will be triggered too early (before the prompt returns)
}//--end StartWorkflow


/*------------------------------------------------------------------------------------------------------------
 * Function: StartNextActionStep
 * finds next step for workflow..if decision then execute formula until you get a not decision step
 *------------------------------------------------------------------------------------------------------------*/

function findNextActionStep( idno )
{

	for ( var k = wfJSON.length -1 ; k >=0 ; k--){
		var injson = wfJSON[k];
		if ( injson.id == idno)
		{
			if ( injson.stype != "Decision")
			{
				return injson;
			}else{
				//decision...run the formula
				var frm = injson.formula;
				if ( frm && frm != "")
				{
					var formula = atob(frm)
					var result = eval ( formula );
					if ( result ) 
					{
						return findNextActionStep (injson.trueside)
					}else{
						return findNextActionStep (injson.falseside)
					
					}
				}
			}
		}
	}

}


/*------------------------------------------------------------------------------------------------------------
 * Function: recurseWFJson
 * Recurses the workflow JSON to find the next step based on current step no and based on direction
 *------------------------------------------------------------------------------------------------------------*/
function recurseWFJson( injsonarr, currstepno, direction, visited )
{	

	for ( var k = injsonarr.length -1; k >= 0; k --){
		var injson = injsonarr[k];
		var sindex = injson.cellNo;
		if ( sindex == currstepno)
		{
			if ( direction == "trueside")
			{
				target = findNextActionStep( injson.trueside);
			}else{
				target = findNextActionStep( injson.falseside);
			}
			return target;
		}

	}



	var sindex = injson.cellNo;
	visited = visited || [];
	var target = null;
	if ( sindex in visited && injson.stype != "Decision" ) 
	{
			return null;
	}

	visited[sindex] = true;
	if ( sindex == currstepno)
	{
		if ( direction == "trueside")
		{
			target = findNextActionStep( injson.trueside);
		}else{
			target = findNextActionStep( injson.falseside);
		}
		return target;
	}else
	{
		if ( injson.trueside && target == null)
    		target =  recurseWFJson(injson.trueside, currstepno, direction , visited);

    	if ( injson.falseside && target == null)
    		target =  recurseWFJson(injson.falseside, currstepno, direction , visited);

	}

	
	return target;
}

/*------------------------------------------------------------------------------------------------------------
 * Function: executeParticipantFormulas
 * Excecutes the participant formula for a patricular step and updates the xML with the result
 *------------------------------------------------------------------------------------------------------------*/
function executeParticipantFormulas(cellno) 
{
	var rs = WorkflowSteps.recordset;
	if(rs.getRecordCount() > 0)
	{		
		rs.MoveFirst();
		while(!rs.EOF())
		{
			if(rs.getFIELDSCount() > 0)
			{	

				if (rs.FIELDS("wfOrder").getValue() == cellno )
				{
					var formulastr = rs.FIELDS("wfParticipantFormula").getValue();

					if (formulastr && formulastr != "")
					{
						var fieldval = "";
						formulastr = atob(formulastr);
						var tmpfunc = new Function(formulastr);

						try {
							fieldval = tmpfunc();
						}catch(e){
							alert ( "Error running Participant formula \r\n " +  formulastr );
						}

						if ( fieldval && fieldval != ""){
							rs.FIELDS("wfReviewerApproverList").setValue(fieldval);
							rs.FIELDS("wfDispReviewerApproverList").setValue(fieldval);
							rs.FIELDS("Modified").setValue("1");
						}
							
					}
				}

			}
			rs.MoveNext();
		}
		WorkflowSteps.Refresh();
	}	

							
}



/*------------------------------------------------------------------------------------------------------------
 * Function: getNextWFStep
 * Finds the next workflow step based on the current step and the direction
 *------------------------------------------------------------------------------------------------------------*/
function getNextWFStep(currentstep, direction)
{
	 currentStep = currentstep ? currentstep : GetCurrentWorkflowStep();

	 var stepno = currentStep.Fields("wfOrder").getValue()

	 var nexttarget = recurseWFJson( wfJSON, stepno, direction);

	 if ( nexttarget)
	 {
	 	

	 	//also check if we need to update participans based on formulas

	 	var attrobj = $(nexttarget.attrStr);
	 	var pformula = attrobj.attr("wfParticipantFormula");

	 	if ( pformula && pformula != "" ){
	 		executeParticipantFormulas(nexttarget.cellNo);	
	 	}
	 	

	 	return nexttarget.cellNo;
	 }

	 return -1;
}

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: insertWorkflow
 * Retrieves a specified set of workflow steps and inserts them into an existing workflow.
 * Start and end steps for the workflow step being inserted are ignored.
 * Inputs: processId - string - id of workflow sequence to retrieve
 *               insertBeforeItemTitle - string (optional) - title of existing workflow step to insert new workflow steps
 *                                   ahead of.  If not specified, or if the existing start step is specfied, then the new workflow
 *                                   will be inserted before the existing end step
 * 				silent - boolean (optional) - true to not display prompt on error retrieving workflow
 * Returns: boolean - true if workflow is inserted properly, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function insertWorkflow( processId, insertBeforeItemTitle, silent)
{
	if (!processId) { 
  		jQuery("#" + wfcontainerid ).hide();
     	return false; 
	} 

	// get new data to insert 
	var wfUrl = docInfo.PortalWebPath +  "/xViewData.xml?ReadForm&view=xmlWorkflowStepsByProcessId&col=2&lkey="  + processId + "&cache=" + Date.now(); 
	jQuery.ajax({
		url: wfUrl,
		async: false,
		cache: false,
		type: "GET",
		dataType: "xml"
	})
	.done(function( xmldata ) {
	
		var xmlDoc = WorkflowSteps.getXMLDocument();
		var currNode = xmlDoc.documentElement.lastChild; 

		//remove the isCurrentItem flag if it is set. 
		var fixNode= xmldata.selectSingleNode('Documents/Document/wfIsCurrentItem[.="1"]' ); 
		if ( fixNode != null ) fixNode.firstChild.nodeValue = 0; 

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
		
		var root =  xmldata.documentElement; 
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
		for ( var p = 0; p < nodeList.length; p ++ )
		{
			objNode = nodeList.item(p);
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
		WorkflowSteps.setXML(xmlDoc);
		
		ProcessWorkflow("UPDATE"); 
		WorkflowSteps.Refresh(true);
		return true; 		
	})
	.fail(function(){
		if(! silent){
			window.top.Docova.Utils.messageBox({
				"title" : "Error Inserting Workflow",
				"prompt" : "An error occurred while attempting to retreive additional workflow steps to insert.",
				"icontype" : 1,
				"msgboxtype" : 0,
				"width" : 400						
			});
		}
	});
	return false;
}//--end insertWorkflow


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: isFieldCdata
 * Returns true if a workflow data xml node is a CData wrapped element
 * Inputs: fieldname - string - xml field name to look for
 * Returns: boolean - true if xml element is cdata wrapped, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function isFieldCdata ( fieldname ){
	var isCdata = false;

	//find the row we need to process...
	var xmlDom = WorkflowSteps.getXMLDocument();
	var nodeList = jQuery(xmlDom).find("Documents > Document > " + fieldname);
	for(var i=0; i<nodeList.length; i++){
		if(nodeList[i].childNodes.length > 0){
			if(nodeList[i].firstChild.nodeType == 4 ){
			   isCdata = true;
			   break;
			}
		}
	}
	
	return isCdata;
}//--end isFieldCdata


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ChangeWorkflowStep
 * Customizes a workflow step
 * Inputs: keyType - string - wfOrder or wfTitle - type of search to use to locate matching step
 *              key - string - value to match against wfOrder or wfTitle field to locate matchign step
 *              field - string - xml field name to update
 *              value - string - new value to update workflow step with
 *              replaceToken - string (optional) - existing string to search for and replace with the new value
 *              force - boolean (optional) - allows changing of current/previous workflow steps
 * Returns: boolean - true if change successful, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ChangeWorkflowStep(keyType, key, field, value, replaceToken, force){
	var result = false;
	
	var newValue;

	if(!HasWorkflowData()){
		return result;
	}

	if(keyType != "wfOrder" && keyType != "wfTitle"){
		return result;
	}
	
	if(field.toLowerCase() == "wfreviewerapproverlist") {field = "wfDispReviewerApproverList";}
	
	//check if field is a cData field
	var isCdata = isFieldCdata ( field );
	
	var currentStep = GetCurrentWorkflowStep();
	var currentStepNo = (currentStep)? currentStep.Fields("wfOrder").getValue() : "0";
	var rs = WorkflowSteps.recordset;
	rs.MoveFirst();
	var index =0;
	while(!rs.EOF()){		
		if(rs.Fields(keyType).getValue() == key){
		// found selected step, now check if it is still pending
			if(rs.Fields("wfOrder").getValue() < currentStepNo && !force){
				//step already completed, no changes allowed
				return result;
			}
			
			if ( replaceToken ) {
				newValue = rs.Fields(field).getValue().replace(replaceToken,value);
			}else{
				newValue = value;
			}
			
			rs.Fields(field).setValue(newValue);
			//now lets manually update the value in the span tag if its a CDATA type node...
			if ( isCdata ){handleCdataField ( index, field, newValue )}
			rs.Fields("Modified").setValue ("1");
			WorkflowSteps.Refresh(true);
			result = true;
			break;
		}
		index++;
		rs.MoveNext();
	}
	return result;
}//--end ChangWorkflowStep

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ChangeWorkflowStepGroup
 * Customizes a group of workflow steps
 * Inputs: field - string - xml field name to update
 *              value - string - new value to update workflow step with
 *              replaceToken - string (optional) - existing string to search for and replace with the new value
 *              force - boolean (optional) - allows changing of current/previous workflow steps
 * Returns: boolean - true if change successful, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ChangeWorkflowStepGroup(field, value, replaceToken, force)
{
	var result = false;
	
	if(!HasWorkflowData()){
			return result;
	}

	if(field.toLowerCase() == "wfreviewerapproverlist") {field = "wfDispReviewerApproverList";}
	
	//check if field is a cData field
	var isCdata = isFieldCdata ( field );	

	var currentStep = GetCurrentWorkflowStep();
	var currentStepNo = (currentStep)? currentStep.Fields("wfOrder").getValue() : "0";
	var rs = WorkflowSteps.recordset;
	rs.MoveFirst();
	while(!rs.EOF()){
			if(rs.Fields("wfOrder").getValue() >= currentStepNo && !force){
				if(replaceToken){
					newValue = rs.Fields(field).getValue().replace(replaceToken,value);			
				}
				else{	
					newValue = value;
				}
				rs.Fields(field).setValue(newValue);
				
				//now lets manually update the value in the span tag if its a CDATA type node...
				if ( isCdata ){handleCdataField ( rs.Fields("wfOrder").getValue(), field, newValue )}			
				rs.Fields("Modified").setValue("1");
				result = true;
			}
		rs.MoveNext();
	}
	if(result == true){
		WorkflowSteps.Refresh(true);
	}
	
	return result;
}//--end ChangeWorkflowStepGroup



/*------------------------------------------------------------------------------------------------------------
 * Function: SetDataDefaults
 * Sets initial default options for workflow data
 *------------------------------------------------------------------------------------------------------------*/
function SetDataDefaults()
{

	// --- replace all instances of [Author] with the author's name ---
	if(docInfo.isDocBeingEdited)
		{
		if(!HasWorkflowData())
		{
				jQuery("#" + wfcontainerid ).hide();
				return false;
		}
		jQuery("#" + wfcontainerid ).show();
	
		var rs = WorkflowSteps.recordset;
		if(rs.getRecordCount() > 0)
		{		
			rs.MoveFirst();
			while(!rs.EOF())
				{
					if(rs.getFIELDSCount() > 0)
					{	
						//--- replace "[Author]" with author's name ----
						tmpNames = rs.FIELDS("wfReviewerApproverList").getValue();

						var tmpNamesArr = multiSplit(tmpNames, ",");
						for ( var p = 0; p < tmpNamesArr.length; p ++){
							var token = tmpNamesArr[p].trim();
							if ( token == "[Author]")
							{
								rs.FIELDS("wfReviewerApproverList").setValue(tmpNames.replace("[Author]", docInfo.UserNameAB));
								rs.FIELDS("Modified").setValue("1");
							}
						}

						//--- replace "[Author]" with author's name ----
						tmpNames = rs.FIELDS("wfDispReviewerApproverList").getValue();

						var tmpNamesArr = multiSplit(tmpNames, ",");
						for ( var p = 0; p < tmpNamesArr.length; p ++){
							var token = tmpNamesArr[p].trim();
							if ( token == "[Author]")
							{
								rs.FIELDS("wfDispReviewerApproverList").setValue(tmpNames.replace("[Author]", docInfo.UserNameAB));
								rs.FIELDS("Modified").setValue("1");
							}
						}			
					}
					rs.MoveNext();
				}
		WorkflowSteps.Refresh();
		}			
	}
}//--end SetDataDefaults

/*------------------------------------------------------------------------------------------------------------
 * Function: SetItemProperties
 * Sets display options for workflow data
 *------------------------------------------------------------------------------------------------------------*/
function SetItemProperties()
{
	if(!HasWorkflowData()){return false;}

	var rs = WorkflowSteps.recordset;
	if(rs.getRecordCount() == 0){return;}

	var $dataTable = jQuery("#tblDataEdit");
	rs.MoveFirst();
	while(!rs.EOF())
	{
		if(rs.getFIELDSCount() > 0)
		{
			var rownum = rs.getAbsolutePosition()+1;
			if(rs.FIELDS("wfIsCurrentItem").getValue() =="1") //highlight current item
			{
				jQuery($dataTable.get(0).rows[rownum]).addClass("ui-state-hover");
			}else{
				jQuery($dataTable.get(0).rows[rownum]).removeClass("ui-state-hover");			
			}
			if(rs.FIELDS("wfStatus").getValue()=="Pending"  && docInfo.isDocBeingEdited )
			{
				//-- show name selection button next to pending entries with enabled customization ----- 
				jQuery($dataTable.get(0).rows[rownum]).find("BUTTON:first").button().css({
					"width" : 22, 
					"height" : 22, 
					"display" : (rs.FIELDS("wfReviewerApproverSelect").getValue() ? "" : "none")
				}).children("span").css("padding", 0);
			}
		}
		rs.MoveNext();
	}
}//--end SetItemProperties

/*------------------------------------------------------------------------------------------------------------
 * Function: SelectParticipants
 * Select workflow participants for steps that can be customized
 * Inputs: clickedObj - element - button selected by the user
 *------------------------------------------------------------------------------------------------------------*/
function SelectParticipants(clickedObj)
{
	if ( wfcontainerid == "wf_steps_container" )
	{
		//contemporary look
		var rownum = jQuery(clickedObj).closest("table").index();

	}else{
		var rownum = jQuery(clickedObj).closest("tr").get(0).rowIndex - 1;

	}

	
	var rs = WorkflowSteps.recordset;	
	rs.AbsolutePosition(rownum);	
		
	var dialogType = (rs.Fields("wfType") == "Serial")? "single" : "multi";
	window.top.Docova.Utils.showAddressDialog({ 
		"dlgtype": dialogType,
		"defaultvalues": rs.Fields("wfDispReviewerApproverList").getValue(), 
		"cb" : function(ret){
			if(ret){
				rs.Fields("wfDispReviewerApproverList").setValue(ret);
				rs.Fields("Modified").setValue("1");
	
				//if this is a cdata filed, then we need to refresh the html table manually...bug in recordset object with cdata type fields.
				if ( isFieldCdata ("wfDispReviewerApproverList" ) ) handleCdataField ( (rownum-1), "wfDispReviewerApproverList", ret )
				//--- need to update workflow if already created or store for update on save --			
				if(docInfo.isWorkflowCreated)
				{		
					ProcessWorkflow("UPDATE");
					//-- but only clear the modified flag if the document is not being edited						
					if(! docInfo.isDocBeingEdited){			
						rs.Fields("Modified").setValue("0");
					}
				}
				if ( wfcontainerid == "wf_steps_container" )
				{
					LoadWorkflowSteps(null, true);
					var stepid = $(clickedObj).closest("table").attr("id");
					selectStep($("li[refid='" + stepid + "']"));
				}else{
					WorkflowSteps.Refresh(true);	
				}
				
			}
		}//callback
	});//address dialog	
}//--end SelectParticipants

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: WorkflowHandler
 * Common set of operations called by various workflow actions. Recursively calls itself.
 * Inputs: workflowaction - string - workflow action being triggered. one of the following
 *                                           DENY, APPROVE, COMPLETE, FINISH
 *               cb - callback function (optional) - function to call on completion of processing
 *               actionstep - string (optional) - specific processing step to perform. One of;
 *                                     GETCOMMENT, PROCESS
 *               data - (optional) - variable data that may be being passed between recursive calls
 *------------------------------------------------------------------------------------------------------------------------------------------- */
 function WorkflowHandler(workflowaction, cb, actionstep, data){
 	var checkinrequired = false;
	var checkinprompt = "";
 	var actionlabel = ""; 	
 	var validatefields = false;
 	var confirmationrequired = false;
  	var confirmationprompt = "";	
 	var getcomment = false;
	var commentprompt = "Please enter your comments below:";
 	var commentrequired = false;
 	var customValidateFunction = null;
 	var customPreProcessFunction = null;	
	var customPostProcessFunction = null;
	
	var currentStep = GetCurrentWorkflowStep();
	if(! currentStep){
		if(cb && typeof cb == "function"){	cb(false); }
		return false;		
	}			
	
 	if(workflowaction == "DENY"){
 		checkinrequired = true;
		checkinprompt = "Before you Deny this workflow step you must check in any files you have checked out.";
  		actionlabel = "Declined";		
 		confirmationrequired = true;
 		confirmationprompt = "You are about to Deny the workflow step.  Are you sure?";
 		getcomment = true;
 		commentprompt = "Please enter your reasons for declining:";
 		commentrequired = true;
 		customPreProcessFunction = function(){return onDeny()};
 	}else if(workflowaction == "APPROVE"){
  		checkinrequired = true;	
 		checkinprompt = "Before you Approve this workflow step you must check in any files you have checked out.";	
		actionlabel = "Approved";		
		validatefields = "Approve"; 
 		if(currentStep.Fields("wfOptionalComments").getValue() == "1") {
	 		getcomment = true;
 		}					
 		customPreProcessFunction = function(){return onApprove()};		
 	}else if(workflowaction == "COMPLETE"){
  		checkinrequired = true;	
		checkinprompt = "Before you Complete this workflow step you must check in any files you have checked out.";			
		actionlabel = "Reviewed";		
		validatefields = "Complete"; 
 		if(currentStep.Fields("wfOptionalComments").getValue() == "1") {
	 		getcomment = true;
 		}					
 		customPreProcessFunction = function(){return onComplete()};			
	}else if(workflowaction == "FINISH"){
 		checkinrequired = true;	
		checkinprompt = "Before you Complete this workflow step you must check in any files you have checked out.";			
		actionlabel = "Finish";		
		validatefields = "Finish"; 
  		getcomment = true;
 		customValidateFunction = function(){return CustomOnBeforeReleaseHandler()};
		customPostProcessFunction = function(){return CustomOnAfterReleaseHandler()};				
 	}else if(workflowaction == "PAUSE"){
 		actionlabel = "Paused";		
  		confirmationrequired = true;
 	 	confirmationprompt = "You are about to pause the workflow. This action will stop past due reminders.\r\rAre you sure?";
  		getcomment = true;
		commentprompt = "Please enter your reasons for pausing this workflow:";
	}else if(workflowaction == "CANCEL"){
		actionlabel = "Canceled";
		confirmationrequired = true;
		var statuslist = wfInfo.StatusList.split(",");
		var stagename = (statuslist[0]==undefined) ? "Draft" : statuslist[0];
		confirmationprompt =  "You are about to cancel the workflow. This action will delete the current workflow and return this document to " + stagename + " state.\r\rDo you want to continue?",
		getcomment = true;
 		commentprompt = "Please enter your reasons for cancelling the workflow process:";
 		commentrequired = true;		
 	}

	//-- Initial checks and validations --
	if(actionstep  == undefined || actionstep == null || actionstep == ""){
	
		//-- API HOOK - custom validation function to process 
		if ( customValidateFunction && ! customValidateFunction() ) { 
			if(cb && typeof cb == "function"){	cb(false); }			
			return false;
		}
	
		if(!HasWorkflowData())
		{
			if(cb && typeof cb == "function"){	cb(false); }
			return false;
		}
	 	
	 	if (UserHasFilesCheckedOut()){
			window.top.Docova.Utils.messageBox({
				"title" : "Files are checked out",
				"prompt" : checkinprompt,
				"icontype" : 4,
				"msgboxtype" : 0,
				"width" : 400
			});
			if(cb && typeof cb == "function"){	cb(false); }
			return false;
		}
		
		if(validatefields != ""){
			//do field validation
		     if(!IsValidWfData(validatefields)) {
				return false;
			}
		}
		

		//-- confirm that user wants to proceed --
		if(confirmationrequired){
			window.top.Docova.Utils.messageBox({
				"title" : "Document Workflow",
				"prompt" : confirmationprompt,
				"icontype" : 2,
				"msgboxtype" : 3,
				"width" : 400,
				"onYes" : function(){	
					WorkflowHandler(workflowaction, cb, (getcomment ? "GETCOMMENT" : "PROCESS"));
					return;
				},
				"onNo" : function(){
					if(cb && typeof cb == "function"){	cb(false); }				
					return false;
				},
				"onCancel" : function(){
					if(cb && typeof cb == "function"){	cb(false); }
					return false;
				}
			});
		//-- no confirmation needed just go ahead --			
		}else{
			WorkflowHandler(workflowaction, cb, (getcomment ? "GETCOMMENT" : "PROCESS"));
			return;			
		}		
	}//-- end INITIALIZE
	//-- prompt user for a workflow COMMENT				
	else if(actionstep == "GETCOMMENT"){
		dlgParams.length = 0;
		//-- special case when finish workflow step and versioning enabled
		if(workflowaction== "FINISH" && docInfo.EnableVersions){
			dlgParams[0] = docInfo.AvailableVersionList; 
			dlgParams[1] = docInfo.FullVersion; 
			dlgParams[2] = docInfo.isInitialVersion; 
			dlgParams[3] = docInfo.StrictVersioning;
		
			var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowDocRelease?OpenForm";
			var dlgVer = Docova.Utils.createDialog({
				id: "divDlgWorkflowDocRelease", 
				url: dlgUrl,
				title: "Release Document",
				height: 270,
				width: 455, 
				useiframe: true,
				buttons: {
     	  			"OK": function() {
     		  			var dlgDoc = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.document;
						var userComment = $("#Comment", dlgDoc).val();
						var isFirstRelease = dlgParams[2];
						if(isFirstRelease){
							if(!isNumeric($("#MajorVersion", dlgDoc).val()) || !isNumeric($("#MinorVersion", dlgDoc).val()) || !isNumeric($("#Revision", dlgDoc).val())){
								alert("Sorry.  You must enter numeric values for major and minor version number.");
								$("#MajorVersion", dlgDoc).focus();
								return false;
							}
							version = $("#MajorVersion", dlgDoc).val() + "." + $("#MinorVersion", dlgDoc).val()  + "." + $("#Revision", dlgDoc).val()
						}else{
							version = $("input[name=VersionSelect]:checked", dlgDoc).val()
							if(version ==""){
								alert("Sorry.  You must select the major or minor version increment.")
								return false;
							}
						}
						if(!isFirstRelease && !userComment){ //comment is required for auto incremented versions
							alert("Comment required. Please describe the changes to be included in this version.")
							$("#Comment", dlgDoc).focus();
							return false;
						}
						dlgVer.closeDialog();
						WorkflowHandler(workflowaction, cb, "PROCESS", {"version" : version, "comment" : userComment});						
   	     			}, //OK release
	    	    			"Cancel": function() {
						dlgVer.closeDialog();
    		    			} //Cancel release
      			}
			})					
		//--otherwise use default comment handler	
		}else{
			dlgParams[0] = commentprompt;
			dlgParams[1] = commentrequired;
			var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowComment?OpenForm";
			var dlgcmnt = window.top.Docova.Utils.createDialog({
				id: "divDlgGetComment", 
				url: dlgUrl,
				title: "Comment",
				height: 250,
				width: 420, 
				useiframe: true,
				sourcedocument: document,
				sourcewindow: window,				
				buttons: {
    		   			"OK": function() {
    		   					var dlgDoc = window.top.$("#" + this.id + "IFrame")[0].contentWindow.document;
    		   					var userComment = $("#Comment", dlgDoc).val();
							if(!(commentrequired && userComment.trim() == "")){
								dlgcmnt.closeDialog();
								WorkflowHandler(workflowaction, cb, "PROCESS", {"comment" : userComment});							
							}       				
 					}, //OK comment
 					"Cancel": function() {
						dlgcmnt.closeDialog();
    		    			} //Cancel comment
				}
			});	
		}
	}//-- end GETCOMMENT
	//-- PROCESS the workflow step
	else if(actionstep == "PROCESS"){
		var additionalHeader = "";
		if(getcomment && data && data.comment){
			additionalHeader += "<UserComment><![CDATA[" + data.comment +  "]]></UserComment>";		
		}
		
		//-- API HOOK - custom function to process 
		if ( customPreProcessFunction && ! customPreProcessFunction() ) { 
			if(cb && typeof cb == "function"){	cb(false); }			
			return false;
		}

		if(workflowaction == "FINISH")
		{
			additionalHeader += "<Version>" + (docInfo.EnableVersions ? (data && data.version ? data.version : docInfo.FullVersion) : docInfo.FullVersion) + "</Version>";
		}else{

			//ensure currentStep resultset pointer correct as may be reset by custom functions
			currentStep = GetCurrentWorkflowStep();
			var currentstepno = currentStep.Fields("wfOrder").getValue();

			var direction = "trueside";

			if ( workflowaction == "DENY")
			{
				direction = "falseside";
			}

			var nextstepno = -1;
			if ( docInfo.IsAppForm != "1"){
				 nextstepno = parseInt(currentstepno) + 1;
			}else{
				nextstepno = getNextWFStep(currentStep, direction);
			}
			
			if ( nextstepno == -1){
				alert ( "Unable to get next step in workfow.  Exiting");
				return;
			}
		}
		//--- process on server --	
		if(ProcessWorkflow(workflowaction, additionalHeader, false, nextstepno )){
			//----- Add to additional comments subform section on document if set -----
			if(docInfo.isLinkComments && getcomment && data && data.comment){
				var addrequest = "<UserComment><![CDATA[" + actionlabel + ": " + data.comment +  "]]></UserComment>";
				addrequest += "<CommentType>LC</CommentType>";				
				if(ProcessComment("LOGCOMMENT", addrequest, true)){
					try{
						//TODO FIXME - update to work with data island comments
						doc.AdvComments.src=doc.AdvComments.src + "&" + (new Date()).valueOf(); //refresh data island to show the new comment
					}
					catch (e){}
				}
			}
			
			
			if(workflowaction == "FINISH"){
				//-----Set tmpVersion field to make available for onAfterRelease event------
				doc.tmpVersion.value =  (docInfo.EnableVersions ? (data && data.version ? data.version : docInfo.FullVersion) : docInfo.FullVersion);	
			}		 

			//-- API HOOK - custom function to process 
			if ( customPostProcessFunction && ! customPostProcessFunction() ) { 
				if(cb && typeof cb == "function"){	cb(false); }			
				return false;
			}
			
			if(docInfo.IsAppForm == "1" & !docInfo.isDocBeingEdited){
				var uidoc = Docova.getUIDocument();
				uidoc.close();
			}else{
				if (window.parent.fraTabbedTable){ 
					window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(docInfo.FolderUNID, docInfo.DocID);
				}
				HandleSaveClick(true);
			}

		}
	}//--end PROCESS component
 }//--end WorkflowHandler


/*------------------------------------------------------------------------------------------------------------
 * Function: DenyWorkflowStep
 * Process the 'Decline' workflow step action
 * Inputs: cb - callback function (optional) - function to call with result
 *------------------------------------------------------------------------------------------------------------*/
function DenyWorkflowStep(cb)
{
	WorkflowHandler("DENY", cb);
}//--end DenyWorkflowStep


/*------------------------------------------------------------------------------------------------------------
 * Function: ApproveWorkflowStep
 * Process the 'Approve' workflow step action
 * Inputs: cb - callback function (optional) - function to call with result
 *------------------------------------------------------------------------------------------------------------*/
function ApproveWorkflowStep(cb)
{
	WorkflowHandler("APPROVE", cb);
}//--end ApproveWorkflowStep


/*------------------------------------------------------------------------------------------------------------
 * Function: CompleteWorkflowStep
 * Process the 'Complete' workflow step action
 * Inputs: cb - callback function (optional) - function to call with result 
 *------------------------------------------------------------------------------------------------------------*/
function CompleteWorkflowStep(cb)
{
	WorkflowHandler("COMPLETE", cb);
}//--end CompleteWorkflowStep


/*------------------------------------------------------------------------------------------------------------
 * Function: FinishWorkflow
 * Process the 'Finish' workflow step action
 * Inputs: cb - callback function (optional) - function to call with result
 *------------------------------------------------------------------------------------------------------------*/
function FinishWorkflow(cb)
{
	if(!CanModifyDocument(true)){
		if(cb && typeof cb == "function"){	cb(false); }			
		return false;
	}
	WorkflowHandler("FINISH", cb);
}//--end FinishWorkflow


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: CancelWorkflow
 * Cancels the current workflow process.
 * Inputs: cb - callback function (optional) - function to call with result
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function CancelWorkflow(cb)
{
	if(!CanModifyDocument(true)){
		if(cb && typeof cb == "function"){	cb(false); }			
		return false;
	}
	WorkflowHandler("CANCEL", cb);
}//--end CancelWorkflow


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: PauseWorkflow
 * Pauses the current workflow process.
 * Inputs: cb - callback function (optional) - function to call with result
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function PauseWorkflow(cb)
{
	if(!CanModifyDocument(true)){
		if(cb && typeof cb == "function"){	cb(false); }			
		return false;
	}
	WorkflowHandler("PAUSE", cb);
}//--end PauseWorkflow

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: DeleteWorkflowStepByKey
 * Removes a specified workflow step
 * Inputs: keyType - string - workflow xml field to use as a search parameter
 *              key - string - value to match against specified workflow xml field
 * Returns: boolean - true if deletion is successful, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function DeleteWorkflowStepByKey(keyType, key)
{
	var result = false;
	
	if(keyType =="" || key ==""){ return result;}
	if(!HasWorkflowData()){return result;}
	
	var currentStep = GetCurrentWorkflowStep();
	if(!currentStep){ return result;}
	
	var additionalHeader = "";
	xmlDom = WorkflowSteps.getXMLDocument();
	var query = 'wfAction != "Start" and wfAction != "End" and wfOrder > ' + currentStep.FIELDS("wfOrder").getValue();
	query +=  ' and ' + keyType + ' = "' + key + '"' ;
	var nodeList = xmlDom.selectNodes( 'Documents/Document[' + query + ' ]' );	
	if(nodeList.length ==0){ return result;}

	var objNode=null;
	var selectEl = null;	

	for ( var p =0; p < nodeList.length; p++ ){
		objNode = nodeList.item(p)
		selectEl = objNode.selectSingleNode( 'Selected');
		selectEl.text="1";
		if ( selectEl.textContent )
					selectEl.textContent ="1";
	}
		
	var parentNode=xmlDom.selectSingleNode( 'Documents');
	//--- process on server --
	if(docInfo.isWorkflowCreated)
	{		
		if(ProcessWorkflow("DELETE", additionalHeader, false))	{
			WorkflowSteps.reload();
			result = true;
		}
	}
	else{		
		//--- remove deleted nodes from current doc
		for (var i=0; i < nodeList.length; i++) {
      		objNode = nodeList.item(i);
			parentNode.removeChild(objNode);      	
		}
		WorkflowSteps.Refresh(true);		
		result = true;		
	}
	
	return result;
}//--end DeleteWorkflowStepByKey


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: DeleteWorkflowStep
 * Displays a dialog of workflow steps and prompts the user to select one or more to be deleted
 * Calls function to deletes the selected steps from the current workflow.
 * Inputs: cb - callback function (optional) - function to pass on result of deletion
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function DeleteWorkflowStep(cb)
{
	var result = false;
	if(!HasWorkflowData() || !CanModifyDocument(true)){
		if(cb && typeof cb == "function"){
			cb(result);
		}
		return;
	}
	
	var additionalHeader = "";
	var currentStep = GetCurrentWorkflowStep();
	
	xmlDom = WorkflowSteps.getXMLDocument();
	var query = 'wfAction != "Start" and wfAction != "End" and wfOrder > ' + currentStep.FIELDS("wfOrder").getValue();
	var nodeList = xmlDom.selectNodes( 'Documents/Document[' + query + ' ]' );
	if(nodeList.length ==0){
		alert("There are no steps that can be deleted.")
		if(cb && typeof cb == "function"){
			cb(result);
		}
		return;		
	}

	var labeltext = "Please select the steps to delete:"; 
	var buttontext = "Delete Selected Steps";
	//-- prompt user to select nodes to delete and delete the selected nodes	
	SelectWorkflowStep(nodeList, true, labeltext, buttontext, function(data){
		if(data){
			result = ProcessDeleteWorkflowStep(data);
		}
		if(cb && typeof cb == "function"){
			cb(result);
		}
	}); 
}//--end DeleteWorkflowStep


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ProcessDeleteWorkflowStep
 * Deletes a specified set of steps from the current workflow.
 * Inputs: stepNoList - delimited string - comma delimited string of integers representing workflow step nos
 *  								to be deleted from the workflow  
 * Returns: boolean - true if deletion proceeded successfully, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ProcessDeleteWorkflowStep(stepNoList){
	var result = false;
	
	if( stepNoList ==null) {return result; }

	var node = null;
	var selectEl = null;
	var stepIdxArray = stepNoList.split(",");
	xmlDom = WorkflowSteps.getXMLDocument();
	parentNode=xmlDom.selectSingleNode( 'Documents');
	//------------------------------------------------------
	for (var k=0; k< stepIdxArray.length; k++)
		{
			query = 'wfOrder = ' + stepIdxArray[k];
			node = xmlDom.selectSingleNode( 'Documents/Document[' + query + ' ]' );
			if(node) {
				selectEl = node.selectSingleNode( 'Selected');
				selectEl.text="1";
				if ( selectEl.textContent )
					selectEl.textContent ="1";
			}
		}

		//--- process on server --
		if(docInfo.isWorkflowCreated)
		{		
		if(ProcessWorkflow("DELETE", "", true))
			{
				WorkflowSteps.reload();
				result = true;
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
				WorkflowSteps.Refresh(true);
				result = true;
		}		
		return result;
}//--end ProcessDeleteWorkflowStep


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: BacktrackWorkflow
 * Changes the current workflow step to a previous step
 * Returns: boolean - true if backtrack proceeded successfully, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function BacktrackWorkflow()
{
	if(!CanModifyDocument(true)){return false;}
	
	if(!HasWorkflowData())
	{
			return false;
	}
	
	//----- confirm that user wants to backtrack workflow -----
	window.top.Docova.Utils.messageBox({
			"title" : "Document Workflow",
			"prompt" : "You are about to backtrack the workflow. Are you sure?",
			"icontype" : 2,
			"msgboxtype" : 3,
			"width" : 400,
			"onYes" : function(){
					//-- start backtrack logic --
					var additionalHeader = "";
					var currentStep = GetCurrentWorkflowStep();
					
					if(currentStep)
					{
						var action = "BACKTRACK";
						xmlDom = WorkflowSteps.getXMLDocument();
						var query = "wfOrder <= " + currentStep.FIELDS("wfOrder").getValue();
						var nodeList = xmlDom.selectNodes( 'Documents/Document[' + query + ' ]' );
						if(nodeList.length <=1)
						{
							var stepNo = 0 //start from begining
							additionalHeader += "<ProcessedStep>" + stepNo +  "</ProcessedStep>";							
							//--- process on server --
							if(ProcessWorkflow(action, additionalHeader))
							{
								if (window.parent.fraTabbedTable){ 
									window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(docInfo.FolderUNID, docInfo.DocID);
								}
								if (docInfo.isDocBeingEdited) {
									HandleSaveClick(true);
								}
								else {
									window.location.reload();
								}
							}																		
						}else{
							var labeltext = "Please select the workflow step to backtrack to:"; 
							var buttontext = "Backtrack to Step";
							//let user select one of the completed steps
							SelectWorkflowStep(nodeList, false, labeltext, buttontext, function(stepNo){
								if(stepNo == null || stepNo == "") {return; }
								additionalHeader += "<ProcessedStep>" + stepNo +  "</ProcessedStep>";
								//--- process on server --
								if(ProcessWorkflow(action, additionalHeader))
								{
									if (window.parent.fraTabbedTable){ 
										window.parent.fraTabbedTable.objTabBar.RefreshHelper.AddFolderToRefreshList(docInfo.FolderUNID, docInfo.DocID);
									}
									if (docInfo.isDocBeingEdited) {
										HandleSaveClick(true);
									}
									else {
										window.location.reload();
									}
								}
							}); 
						}
					}
					//-- end backtrack  logic --			
			},
			"onNo" : function(){
				return false;
			},
			"onCancel" : function(){
				return false;
			}
	});
	//-- do not add code here as it will be triggered before the prompt returns
}//--end BacktrackWorkflow

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ProcessWorkflow
 * Process the workflow on the server
 * Inputs: action - string - workflow action to perform
 *              additionalHeader - string (optional) - additional xml data to include with request
 *              processNow - boolean (optional) - true to force workflow to process on server even if document 
 *                                        is in edit mode (normally documents in edit mode delay processing until save
 * Returns: boolean - true if processing proceeded successfully, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ProcessWorkflow(action, additionalHeader, processNow,  nextstepno)
{
	var xmlDom = WorkflowSteps.getXMLDocument();
	//--check xmlDom if null it is probably due to doc having multi-workflows, one hasn't been selected yet and user is saving in draft.
	if(xmlDom == null || xmlDom == "undefined"){
		return true;
	}	
	window.top.Docova.Utils.showProgressMessage("Processing workflow request. Please wait...")
	var wfNodes = xmlDom.selectNodes( 'Documents/Document' );

	if (wfNodes.length == 0) {
	   window.top.Docova.Utils.hideProgressMessage();
	   return false;
	}
	var request="";

	//--collect the xml for all nodes to be processed
	request += "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<ServerUrl>" + docInfo.ServerUrl + "</ServerUrl>";
	request += (additionalHeader)? additionalHeader : "";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<NextStep>" + nextstepno + "</NextStep>";
	request += "<Unid>" + docInfo.DocID + "</Unid>";
	request += "<EditMode>" + docInfo.isDocBeingEdited + "</EditMode>";
		
	for(var k=0; k<wfNodes.length; k++)
	{
		var xmlString = new XMLSerializer().serializeToString(wfNodes[k]);  
		request +=xmlString;
	}
	request += "</Request>";

	if(docInfo.isDocBeingEdited && !processNow) //edit mode, doc WQS agent will take care of the request
	{
		jQuery("#tmpWorkflowDataXml").val(request);
	}
	else // read mode, process via workflow services agent
	{
		var url = docInfo.ServerUrl + "/" + docInfo.NsfName + "/WorkflowServices?OpenAgent"
		var httpObj = new objHTTP();
		if(!httpObj.PostData(encodeURIComponent(request), url) || httpObj.status=="FAILED")
		{
			window.top.Docova.Utils.hideProgressMessage();
			return false;
		}
		alert ( "Workflow action successfully completed!");
	}
	window.top.Docova.Utils.hideProgressMessage();
	return true;
}//--end ProcessWorkflow

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: ValidateWorkflow
 * Check the basic requirements for the workflow
 * Inputs: action - string - workflow action to perform
 * Returns: boolean - true if workflow passes validation, false otherwise
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function ValidateWorkflow(action)
{
	if(!HasWorkflowData() && wfInfo.HasMultiWorkflow)
	{
			SelectWorkflow();
			return false;
	}
	else if(!HasWorkflowData() && !wfInfo.HasMultiWorkflow)
	{
		window.top.Docova.Utils.messageBox({
			"title" : "No Workflow Defined",
			"prompt" : "No workflow actions are defined in this document.",
			"icontype" : 4,
			"msgboxtype" : 0,
			"width" : 400
		});	
		return false;
	}

	if(!HasWorkflowData())
	{
		window.top.Docova.Utils.messageBox({
			"title" : "No Workflow Defined",
			"prompt" : "Please select the applicable workflow.",
			"icontype" : 4,
			"msgboxtype" : 0,
			"width" : 400
		});	
		return false;
	}
		
	var rs = WorkflowSteps.recordset;
	//-- check that there are available workflow steps
	if(rs.getRecordCount() == 0)
	{
		window.top.Docova.Utils.messageBox({
			"title" : "No Workflow Defined",
			"prompt" : "No workflow actions are defined in this document.",
			"icontype" : 4,
			"msgboxtype" : 0,
			"width" : 400
		});	
		return(false);
	}

	//----- make sure that the names are selected for each workflow step ----
	rs.MoveFirst();
	while(!rs.EOF())
	{
	     if((rs.Fields("wfStatus") == "Pending" && rs.Fields("wfDispReviewerApproverList").getValue() == "" || rs.Fields("wfDispReviewerApproverList").getValue() == "[Please Select]") && rs.Fields("wfAction").getValue() != "Stop" )
	     {
	     	
			window.top.Docova.Utils.messageBox({
				"title" : prmptMessages.msgWF017,
				"prompt" : prmptMessages.msgWF019,
				"icontype" : 4,
				"msgboxtype" : 0,
				"width" : 400
			});		     
			return(false);
		}
		rs.MoveNext();
     }
     
     //do field validation
     return IsValidWfData(action);
}//--end ValidateWorkflow


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: GetCurrentWorkflowStep
 * Locates the current/active workflow step in the workflow recordset
 * Returns: recordset - recordset object initialized to current workflow step, or false if no current step found
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function GetCurrentWorkflowStep()
{
	if(!HasWorkflowData())
		{
			return false;
		}
	var rs = WorkflowSteps.recordset
	
	//---- find current record ------
	rs.MoveFirst();
	while(!rs.EOF())
		{
			if(rs.getFIELDSCount() > 0)
				{
				if(rs.FIELDS("wfIsCurrentItem").getValue()=="1") // if current item
					{
					return rs;
					}
				}
		rs.MoveNext();
		}
		return false; //could not find current item
}//--end GetCurrentWorkflowStep

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: SelectWorkflowStep
 * Displays a dialog allowing user to select one or more workflow steps for further processing
 * Inputs: nodeList - xml workflow step nodes to choose from
 *              selectMulti - boolean (optional) - whether to allow selection of multiple steps - defaults to false
 *              fieldLabel - string (optional) - text instruction to appear at top of listing
 *              buttonlabel - string (optional) - text label for completion button (defaults to Select Step(s)) 
 *              cb - callback function - function to call with result on close of dialog
 * Returns: recordset - recordset object initialized to current workflow step, or false if no current step found
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function SelectWorkflowStep(nodeList, selectMulti, fieldLabel, buttonlabel, cb)
{
	if(nodeList.length == 0){
		alert("There are no workflow steps available for this document.");
		if(cb && typeof cb === "function"){	cb(false);}
	}else{
		var xmlDom = jQuery.parseXML("<Documents></Documents>");
		var root =  xmlDom.documentElement; 
		for ( i=0; i < nodeList.length; i ++ ){ 
			var newNode = nodeList[i].cloneNode (true ); 
			root.appendChild( newNode); 
		} 		
		
		var dlgID = "divDlgSelectWorkflowStep";
		window.top.Docova.GlobalStorage[dlgID] = {
			"xml" :  xmlDom,
			"multi" : ((selectMulti)? selectMulti : false),
			"fieldlabel" : (fieldLabel ? fieldLabel : "")
		};
		var btnname = (buttonlabel && buttonlabel != "") ? buttonlabel : ("Select Step"  + (selectMulti ? "s" : ""));
		var buttonobj = {};
		buttonobj[btnname] = function() {
					if(jQuery("#" + this.id + "IFrame", this).length > 0){
						var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
						if(result){
							wsdlg.closeDialog();	
							if(cb && typeof cb === "function"){	cb(result);}						
						}
					}
				}
		buttonobj["Cancel"] = function() {
						wsdlg.closeDialog();
						if(cb && typeof cb === "function"){	cb(false);}
        			}

		var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowSelectStep?OpenForm";	
		var wsdlg = window.top.Docova.Utils.createDialog({
			id: dlgID, 
			url: dlgUrl,
			title: "Select Workflow Step" + (selectMulti ? "s" : ""),
			height: 325,
			width: 600, 
			useiframe: true,
			buttons: buttonobj
		});
	}
}//--end SelectWorkflowStep

/*------------------------------------------------------------------------------------------------------------
 * Function: SelectWorkflow
 * Displays dialog allowing user to select workflow applicable to the document
 * Inputs: cb - callback (optional) - optional callback function to call with result
 *------------------------------------------------------------------------------------------------------------*/
function SelectWorkflow(cb)
{
	var xmlText = jQuery("#ProcessSettings").text();
	var xmlDom = jQuery.parseXML(xmlText);
	var nodeList = jQuery(xmlDom).find("Documents > Document");
	if(nodeList.length == 0){
		alert("There are no workflow processess available for this document.");
		if(cb && typeof cb === "function"){	cb(false);}
	}else{
		var dlgID = "divDlgSelectWorkflowProcess";
		window.top.Docova.GlobalStorage[dlgID] = {"xml" : xmlText};
		var dlgUrl ="/" + NsfName + "/" + "dlgWorkflowSelectProcess?OpenForm";	
		var wpdlg = window.top.Docova.Utils.createDialog({
			id: dlgID, 
			url: dlgUrl,
			title: "Select Workflow Process",
			height: 325,
			width: 460, 
			useiframe: true,
			buttons: {
				"Select Workflow" : function() {
					if(jQuery("#" + this.id + "IFrame", this).length > 0){
						var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
						if(result && result.wfID && result.wfName){
							wpdlg.closeDialog();	
							jQuery("#spanDispWorkflowName").text(result.wfName);						
							jQuery("#wfWorkflowName").val(result.wfName);
							jQuery("#wfEnableImmediateRelease").val(result.EnableImmediateRelease);
							jQuery("#wfCustomizeAction").val(result.wfCustomizeAction);
							var result = SwitchWorkflow(result.wfID);
							if(cb && typeof cb === "function"){	cb(result);}						
						}
					}
				},
        			"Cancel": function() {
						wpdlg.closeDialog();
						if(cb && typeof cb === "function"){	cb(false);}
        			}
      		}
		});
	}
}//--end SelectWorkflow


/*------------------------------------------------------------------------------------------------------------
 * Function: RemoveName
 * Removes the user name from the list
 * Inputs: name - string - name to remove from the supplied list
 *              fromList - string - comma delimited list of names
 * Returns: comma delimited list of names with specified name removed
 *------------------------------------------------------------------------------------------------------------*/
function RemoveName(name, fromList)
{
	fromList = fromList.replace(name, "");
	fromList = trim(fromList.replace(", ", ",").split(",")).join(", "); 
	return fromList;
}//--end RemoveName

/*------------------------------------------------------------------------------------------------------------
 * Function: AddName
 * Adds the user name from the list
 * Inputs: name - string - name to add to the supplied list
 *              tList - string - comma delimited list of names
 * Returns: comma delimited list of names with specified name added
 *------------------------------------------------------------------------------------------------------------*/
function AddName(name, toList)
{
	if(toList.indexOf(name) != -1) {return toList;} 
	return  (toList)? toList + ", "  + name : name;
}//--end AddName


/*------------------------------------------------------------------------------------------------------------
 * Function: RequestWorkflowInfo
 * Display workflow request information dialog
 *------------------------------------------------------------------------------------------------------------*/
function RequestWorkflowInfo()
{
	if(!CanModifyDocument(true)){return false;}
	
	var currentStep = GetCurrentWorkflowStep();
	if(currentStep)
	{
		var dlgUrl =docInfo.ServerUrl + "/" + NsfName + "/" + "dlgWorkflowInfoRequest?OpenForm&ParentUNID=" + docInfo.DocID + "&AllowPause=" + wfInfo.AllowPause;
		var widlg = window.top.Docova.Utils.createDialog({
			id: "divDlgWorkflowInfoRequest", 
			url: dlgUrl,
			title: "Request Information",
			height: 400,
			width: 460, 
			useiframe: true,
			buttons: {
				"Send Request" : function() {
					if(jQuery("#" + this.id + "IFrame", this).length > 0){
						var result = jQuery("#" + this.id + "IFrame", this).get(0).contentWindow.completeWizard();
						if(result && result.sendto && result.subject && result.comment){
							widlg.closeDialog();					
							var additionalHeader = "";
							additionalHeader += "<SendTo><![CDATA[" + result.sendto +  "]]></SendTo>";
							additionalHeader += "<Subject><![CDATA[" + result.subject +  "]]></Subject>";
							additionalHeader += "<UserComment><![CDATA[" + result.comment +  "]]></UserComment>";		
							additionalHeader += "<PauseWorkflow>" + (result.pause ? result.pause : "") +  "</PauseWorkflow>";		
							//--- process on server --
							ProcessWorkflow("INFO", additionalHeader);				
						}
					}
				},
        			"Cancel": function() {
						widlg.closeDialog();
        			}
      		}
		});
	}
	//-- do not add code here as it will be triggered before the dialog returns
}//--end RequestWorkflowInfo


//==========================================================================================
// workflow submenu
//==========================================================================================
function CreateWorkflowSubmenu(actionButton) //creates right-click contect menu
{
	if(!actionButton) {return}
	var showRequestInformation = wfInfo.AllowInfoRequest && HasWorkflowData() && docInfo.isWorkflowCreated;
	var showAddComment = HasWorkflowData() && docInfo.isWorkflowCreated
	var showReleaseDocument = HasWorkflowData() && (docInfo.isNewDoc || wfInfo.isStartStep) && (doc.wfEnableImmediateRelease.value=="1")
	var showPauseWorkflow = wfInfo.AllowPause && HasWorkflowData() && docInfo.isWorkflowCreated
	var showDeleteWorkflowStep = wfInfo.AllowCustomize && HasWorkflowData()
	var showBacktrackWorkflowStep = wfInfo.AllowBacktrack && HasWorkflowData()
	var showCancelWorkflow = wfInfo.AllowCancel && HasWorkflowData()
	
	//-----Build menu-----
	Docova.Utils.menu({
		delegate: actionButton,
		width: 200,
		menus: [
				{ title: "Request Information", itemicon: "ui-icon-info", action: "RequestWorkflowInfo()", disabled: !showRequestInformation },
				{ title: "Add Comment", itemicon: "ui-icon-comment", action: "LogLifecycleComment()", disabled: !showAddComment },
				{ separator: true },
				{ title: "Release Document", itemicon: "ui-icon-circle-check", action: "FinishWorkflow()", disabled : !showReleaseDocument },
				{ title: "Pause Workflow", itemicon: "ui-icon-pause", action: "PauseWorkflow()", disabled : !showPauseWorkflow },
				{ separator: true },
				/*{ title: "Delete Workflow Step", itemicon: "ui-icon-circle-close", action: "DeleteWorkflowStep()", disabled: !showDeleteWorkflowStep },				*/
				/*{ title: "Backtrack Workflow", itemicon: "ui-icon-circle-arrow-west", action: "BacktrackWorkflow();", disabled: !showBacktrackWorkflowStep },*/
				{ title: "Cancel Workflow", itemicon: "ui-icon-close", action: "CancelWorkflow()", disabled: !showCancelWorkflow }
		]
	})
}


/*------------------------------------------------------------------------------------------------------------
 * Function: HasWorkflowData
 * Determines if the current document has workflow data or not 
 * Returns: boolean - true if workflow data elements found, false otherwise
 *------------------------------------------------------------------------------------------------------------*/
function HasWorkflowData()
{
	if(WorkflowSteps && WorkflowSteps.getXMLDocument()){	
		if(jQuery(WorkflowSteps.getXMLDocument()).find("Documents > Document").length > 0){
			return true;
		}
	}
	return false;
}//--end HasWorkflowData


/*------------------------------------------------------------------------------------------------------------
 * Function: countApprovalsRemaining
 * Calculates the number of approvals that are remaining for the current workflow step 
 * Used by the release process to see whether a normal approval should be used in
 * place of the finish step.
 * Returns: integer - number of approvals remaining or 0 if none
 *------------------------------------------------------------------------------------------------------------*/
function countApprovalsRemaining(){
	var returnval = 0;
	
	var currentStep = GetCurrentWorkflowStep();
	
	if (! currentStep){
		return false;
	}
	
	var steptype = currentStep.Fields("wfType").getValue();
	var completeany = currentStep.Fields("wfCompleteAny").getValue();
	var approvers = currentStep.Fields("wfReviewerApproverList").getValue();
	approvers = multiSplit(approvers, ",");
	
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
			var completedapprovers = currentStep.Fields("wfReviewApprovalComplete").getValue();
			completedapprovers = completedapprovers.split(", ");
			if (completedapprovers.length == 1 && completedapprovers[0] == ""){
				var completedcount = 0;
			}else{
				var completedcount = completedapprovers.length;
			}
				
			var remaining = parseInt(currentStep.Fields("wfCompleteCount").getValue()) - completedcount;
			returnval = (remaining < 0)? 0 : remaining;
		}
	}

	return returnval;
}//--end countApprovalsRemaining










