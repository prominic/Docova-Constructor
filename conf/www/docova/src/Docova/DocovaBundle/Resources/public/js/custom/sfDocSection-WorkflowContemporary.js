var WorkflowStepsVar = null; //data island object
var wfJSON = null;
var wfcontainerid = "wf_steps_container";
var WorkflowSteps = null;

$(function() {
	$('#wf_steps_container').on('mouseenter', '#wf_steps_header li', function(){
		var $step = $(this);
		$('#step_detail table').removeClass('active');
		$('#' + $step.attr('refid')).addClass('active');		
	}); 

	$('#wf_steps_container').on('mouseleave', '#wf_steps_header li', function(){
		$('#step_detail table').removeClass('active');
		if (active_elm === null) {
			var $step = $('#wf_steps_header li.active');
			$('#' + $step.attr('refid')).addClass('active');
		}
		else {
			$('#' + active_elm).addClass('active');
		}
	});

	$('#wf_steps_container').on('click', '#wf_steps_header li', function(){
		selectStep(this);
	});

	$('a.rt').on('click', function() {
		$('#wf_steps_header').animate({
			scrollLeft: c * $('#wf_steps_header li:nth-child('+ c +')').width()
		}, 300, function() {
			if (($('#wf_steps_header')[0].scrollWidth - $('#wf_steps_header')[0].scrollLeft) > $('#wf_steps_header').outerWidth()) {
				c++;
			}
		});
	});

	$('a.lt').on('click', function() {
		$('#wf_steps_header').animate({
			scrollLeft: (c-1) * $('#wf_steps_header li:nth-child('+ c +')').width()
		}, 300, function() {
			if (c > 1) {
				c--;
			}
		});
	});
	
	var stepsxml = $("#WFNodesXML").text();
	if ( stepsxml && stepsxml != ""){
		
		WorkflowSteps = new xmlDataIsland();

		WorkflowSteps.setXML(stepsxml);
		WorkflowSteps.process();
		SetDataDefaults(); 
	    WorkflowStepsVar = WorkflowSteps.getXMLDocument();

		var  docs = $(WorkflowStepsVar);
		docs = docs.find("Documents");
		var jsonstr = docs.attr("runtimejsonstr");
		wfJSON = JSON.parse(jsonstr);
	
	}

	LoadWorkflowSteps(null, true);
});

function selectStep(instep)
{

	$('#wf_steps_header li').removeClass('selected');
	if (!$(instep).hasClass('active')) {
		$(instep).addClass('selected');
		$('#wf_steps_header li.active').css({
			'border-top' : '0px',
			'border-bottom' : '0px'
		});
		$('#wf_steps_header li.pre-active').addClass('whiteborder');
		$('#wf_steps_header li.active').addClass('whiteborder');
	}
	else {
		$('#wf_steps_header li.active').attr('style', '');
		$('#wf_steps_header li.pre-active').removeClass('whiteborder');
		$('#wf_steps_header li.active').removeClass('whiteborder');
	}
	active_elm = $(instep).attr('refid');
	$('#step_detail table').removeClass('active');
	$('#'+ $(instep).attr('refid')).addClass('active');
}


/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: LoadWorkflowSteps
 * Loads or refreshes workflow steps from xml data
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function LoadWorkflowSteps(processUrl, refresh)
{
	if (typeof(wfInfo) == 'undefined' || !wfInfo) { return; }
	var wfUrl = wfInfo.WorkflowSourceUrl;
	var head_html = [];
	var detail_html = [];
	if (processUrl) {
		wfUrl = processUrl;
	}
	
	if (refresh === true && WorkflowStepsVar) 
	{
		var lastdoneindex = 0;
		$(WorkflowStepsVar).find('Document').each(function(){
			var retVal = buildWorkflowUI($(this));
			if ( retVal.isdone && ! retVal.iscurrentstopped)
			{
				lastdoneindex ++;
			}

			if (retVal.head && retVal.detail) 
			{
				if ( retVal.iscurrentstopped)
				{
					head_html.splice(lastdoneindex, 0, retVal.head)
					detail_html.splice(lastdoneindex, 0, retVal.detail)
				}else{
					head_html.push(retVal.head);
					detail_html.push(retVal.detail);
				}

			}
		});
		if (head_html.length && detail_html.length) 
		{


			$('#wf_steps_header').html(head_html.join(''));
			$('#step_detail').html(detail_html.join(''));
			active_elm = null;
			$('#step_detail button').button().click(function(e) {
				e.preventDefault();
			});
			var step_width = 0;
			$('#wf_steps_header li').each(function() {
				step_width += $(this).outerWidth();
			});

			if (step_width >= $('#wf_steps_header').width()) {
				$('A.lt').html('&laquo;');
				$('A.rt').html('&raquo;');
			}
		}
		else {
			$('#wf_steps_header').hide();
			$('#step_detail').html('<p>No workflow items defined.</p>');
		}		
	}
	else {
    	$.ajax({
    		url: wfUrl,
    		async: false,
    		type: 'GET',
    		dataType: 'xml'
    	})
		.done(function(retData) {
			if (retData && $(retData).has('Document')) 
			{
				var jsonstr = $(retData).find("Documents");
				jsonstr = jsonstr.attr("runtimejsonstr");
				wfJSON = JSON.parse(jsonstr);

				WorkflowSteps = new xmlDataIsland();
				WorkflowSteps.setXML(retData);
				WorkflowSteps.process();
				
				WorkflowStepsVar = retData;
				SetDataDefaults(processUrl, true);
				active_elm = null;
			}
			else {
				$('#wf_steps_header').hide();
				$('#step_detail').html('<p>No workflow items defined.</p>');
			}
		})
		.fail(function() {
			$('#wf_steps_header').hide();
			$('#step_detail').html('<p>No workflow items defined.</p>');
		});
	}
}//--end LoadWorkflowSteps

function buildWorkflowUI(node)
{
	var output = { head: '', detail: '', iscurrentstopped : false, isdone : false}

	if ( node.find("wfAction").text() == "Stop"  && node.find("wfIsCurrentItem").text() == "1"){
		output.iscurrentstopped = true;
	}

	if ( node.find('wfStatus').text() != 'Pending' ){
		output.isdone = true;
	}

	if (node.find('wfItemKey').text() != '' && node.find('wfItemKey').text() != undefined && (node.find("wfAction").text() != "Stop" || (node.find("wfAction").text() == "Stop"  && node.find("wfIsCurrentItem").text() == "1")) )
	{
    	output.head = '<li refid="';
    	output.detail = '<table border="0" cellpadding="5" id="';

    	output.head += node.find('Unid').text() + '" class="';
    	output.detail += node.find('Unid').text() + '"';
    	if (node.find('wfOrder').text() == '0') {
    		output.head += 'no-lf-border ';
    	}

    	if (node.find('wfStatus').text() == 'Pending' )
    	{
    		if (node.find('wfIsCurrentItem').text() == '1') {
    			output.head += 'active';
    		}else{
    			output.head += 'pending';
    			if (node.prev().find('wfIsCurrentItem').text() == '1') {
    				output.head += ' pre-active';
    			}
    		}
    	}else{
    		output.head += 'complete';
    	}
    
    	output.head += '">' + node.find('wfTitle').text() + '</li>';

    	if (node.find('wfIsCurrentItem').text() == '1') {
			output.detail += ' class="active"';
		}
		var addParticipant = '';
		var participants = '--';
		if (docInfo.isDocBeingEdited && node.find('wfStatus').text() == 'Pending' && node.find('wfReviewerApproverSelect').text()) {//@todo: check for ability to edit members
			addParticipant = '<button class="ui-icon-group" onclick="SelectParticipants(this);return false;"></button>';
		}
		if (node.find('wfDispReviewerApproverList').length && $.trim(node.find('wfDispReviewerApproverList').text())) {
			participants = $.trim(node.find('wfDispReviewerApproverList').text());
		}
		else if (node.find('wfReviewApprovalComplete').length && $.trim(node.find('wfReviewApprovalComplete').text())) {
			participants = $.trim(node.find('wfReviewApprovalComplete').text());
		}

		var wf_icon = '';
		switch (node.find('wfAction').text())
		{
			case 'Start':
				wf_icon = 'fa-sign-out';
				break;
			case 'Review':
				wf_icon = 'fa-repeat';
				break;
			case 'Approve':
				wf_icon = 'fa-check-square-o';
				break;
			case 'End':
				wf_icon = 'fa-sign-in';
				break;
		}
		
		output.detail += '>';
		output.detail += '<tr><td rowspan="4" class="wf-icon-cell"><i class="fa '+ wf_icon +' fa-5x"></i></td>';
    	output.detail += '<td style="width:120px;">Step:</td><td><samp field="wfTitle">'+ node.find('wfTitle').text() +'</samp></td>';
		output.detail += '<td rowspan="2" style="width:120px;">Participants: &nbsp;'+ addParticipant +'</td><td rowspan="2"><samp field="wfDispReviewerApproverList">'+ participants +'</samp></td></tr>';
		output.detail += '<tr><td>Workflow Step Status:</td><td><samp field="wfStatus">'+ node.find('wfStatus').text() +'</samp></td></tr>';
		output.detail += '<tr><td>Date Started:</td><td><samp field="wfDateStarted">'+ (node.find('wfDateStarted').length ? node.find('wfDateStarted').text() : '--') +'</samp></td>';
		output.detail += '<td rowspan="2" style="width:120px;">Completed By:</td><td rowspan="2"><samp field="wfReviewerApproverComplete">'+ (node.find('wfReviewApprovalComplete').length && $.trim(node.find('wfReviewApprovalComplete').text()) != '' ? node.find('wfReviewApprovalComplete').text() : '--') +'</samp></td></tr>';
		output.detail += '<tr><td>Date Completed:</td><td><samp field="wfDateCompleted">'+ (node.find('wfDateCompleted').length ? node.find('wfDateCompleted').text() : '--') +'</samp></td></tr>';
		output.detail += '</table>';
	}

	return output;
}



/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: handleCdataField
 * Updates display information for a cdata workflow field
 * Inputs: index - integer - row number in table to update
 *              field - string - xml field name to look for
 *              newValue - string - new value to insert into field
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function handleCdataField(index, field, newValue ){
	var steps = jQuery('#wf_steps_header li');
	var steps_detail = jQuery('#step_detail table');
	if (!steps.length || !steps_detail.length) return;
	
	steps.each(function(x) {
		if ((x+1) == index && field == 'wfTitle') {
			$(this).text(newValue);
			return false;
		}
	});

	steps_detail.each(function(x) {
		if ((x+1) == index) {
			$(this).children('samp').each(function() {
				if ($(this).attr('field') == field) {
					$(this).text(newValue);
					return false;
				}
			});
			return false;
		}
	});
}//--end handleCdataField


