var WorkflowSteps = null; //data island object
var wfJSON = null;
var wfcontainerid = "DataEdit";
/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: LoadWorkflowSteps
 * Loads or refreshes workflow steps from xml data
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function LoadWorkflowSteps(workflowsourceurl){


	
	
	if(WorkflowSteps){
		if(workflowsourceurl){
			WorkflowSteps.setSrc(workflowsourceurl);			
		}else{	
			WorkflowSteps.reload();
		}
	}else{
		WorkflowSteps = new xmlDataIsland();
		WorkflowSteps.id = "ProcessData";
		if(workflowsourceurl != null && workflowsourceurl != undefined && workflowsourceurl != ""){
			WorkflowSteps.setSrc(workflowsourceurl, true);						
		}
		WorkflowSteps.setTemplateName( "otblDataEdit");
		WorkflowSteps.ondatasetcomplete = function()
		{
			var jsonstr = $(this.oxml).find("Documents");
			jsonstr = jsonstr.attr("runtimejsonstr");
			wfJSON = JSON.parse(jsonstr);
			SetDataDefaults(); 
			SetItemProperties();
		};
		WorkflowSteps.process();
	}
}//--end LoadWorkflowSteps


function InitWorkflowSteps ()
{
	WorkflowSteps = new xmlDataIsland();
	WorkflowSteps.id = "ProcessData";
	WorkflowSteps.setTemplateName( "otblDataEdit");
	var xml = $("#WFNodesXML").text();

	if ( xml == ""){
		LoadWorkflowSteps(wfInfo.WorkflowSourceUrl);
	}else{

		WorkflowSteps.setXML ( xml);
	}

	WorkflowSteps.onrecordmerge = function ( thetag, record ){
		var isskipped = $(record).find("wfNodeSkipped").text();
		if ( isskipped == "1"){
			$(thetag).find("[datafld]").each ( function (){
				$(this).css("color", "lightgray");
			})

			$(thetag).find("button").each ( function (){
				$(this).prop("disabled", "true");
			})
		}

		if ( $(record).find("wfAction").text() == "Stop" && $(record).find("wfIsCurrentItem").text() != "1")
		{
			$(thetag).hide();
		}

		$(thetag).attr("wfAction", $(record).find("wfAction").text());
		$(thetag).attr("isCurrentItem", $(record).find("wfIsCurrentItem").text());
		$(thetag).attr("wfStatus", $(record).find("wfStatus").text());
	}

	WorkflowSteps.ondatasetcomplete = function()
	{
		var jsonstr = $(this.oxml).find("Documents");

		if ( docInfo.IsAppForm == "1")
		{
			jsonstr = jsonstr.attr("runtimejsonstr");
			wfJSON = JSON.parse(jsonstr);
		}
		SetDataDefaults(); 
		SetItemProperties();
	};

	WorkflowSteps.process();

	var lastcompleted = 0;
	$("#otblDataEdit").find("tr").each ( function (){
		if ( $(this).attr("wfStatus") != "Pending" && $(this).attr("wfAction") != "Stop"){
			lastcompleted ++;
		}
		if ( $(this).attr("wfAction") == "Stop" && $(this).attr("iscurrentitem") == "1")
		{
			var trow = $('#otblDataEdit tr:nth-child(' + lastcompleted + ')');
			$(this).insertAfter(trow);
		}
	})
}



/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: handleCdataField
 * Updates display information for a cdata workflow field
 * Inputs: index - integer - row number in table to update
 *              field - string - xml field name to look for
 *              newValue - string - new value to insert into field
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function handleCdataField (index, field, newValue ){
	var tbody = jQuery("#otblDataEdit").get(0);
	if ( tbody ==null ) return;
	var row = tbody.rows[index];
	if ( row == null ) return;
	var tds = row.childNodes;
	for ( j = 0; j < tds.length; j ++){
		if(tds[j].childNodes.length > 0){
			var elm = tds[j].firstChild;
	     	if ( elm.dataFld == field ) elm.innerText = newValue;
	     }
	}
}//--end handleCdataField

