var userNameExists=false;
var userEmailExists=false;

$(function() {
	$('form').height($(window).height() - 46);
	$( window ).unload(function() {
		return processOnUnload();
	});

	$('#divFormContainer').tabs({
		heightStyle: "content"
	});

	$('#AccountType').on('change', function() {
		if ($(this).val() == 1) {
			$('#password_container').show();
		}
		else {
			$('#password_container').hide();
		}
	});

	//generates user ID based on first and last name
	$('#FirstName, #LastName').on('blur', function() {
		var firstName=$("#FirstName").val();
		var lastName=$("#LastName").val();
		if (firstName.length >0 && lastName.length > 0)
		{
			$("#idUserName").val((firstName.substring(0,1) + lastName).toLowerCase());
		}
	});

	$("#btnAddDelegate").button({
		icons: {
			primary: "ui-icon-person"
		},
		text: true
	}).click(function(event){
		addDelegate();
		event.preventDefault();
	});

	$("#btnRemoveDelegate").button({
		icons: {
			primary: "ui-icon-person"
		},
		text: true
	}).click(function(event){
		DeleteDelegate();
		event.preventDefault();
	});

	$("#btnDelegateOwner").button({
		icons: {
			primary: "ui-icon-person"
		},
		text: false
	}).click(function(event){
		window.top.Docova.Utils.showAddressDialog({ fieldname: "DelegateOwner", dlgtype: "single", sourcedocument:document});
		event.preventDefault();
	});

	$('#btnUpdatePwd').button().click(function(e) {
		e.preventDefault()
		updatePwd($('#password_container'));
	});
	
	$('#UserTheme').change(function() {
		window.top.Docova.Utils.messageBox({
			title: "Change Theme",
			prompt: "If you save your profile with a new theme, hit F5 to refresh your browser.",
			icontype: 4, 
			msgboxtype: 0, 
			width: 400
		});		
	});
	
	$('#WorkspaceTheme').change(function() {
		window.top.Docova.Utils.messageBox({
			title: "Change Theme",
			prompt: "If you save your profile with a new workspace theme, hit F5 to refresh your browser.",
			icontype: 4, 
			msgboxtype: 0, 
			width: 400
		});		
	});
});

// validate user profile fields before submitting
function validateDocumentFields()
{
	var userFirstNm = $("#FirstName").val();
	var userLastNm = $("#LastName").val();
	var NewPwd = $("#NewPassword").val();
	var userEmailAddr = $("#Email").val();
	var OldEmailAddress = $("#OldEmailAddress").val();
	var userMailSystem = $("input[name=UserMailSystem]:checked").val();
	var confirmPwd = $("#RetypePassword").val();
	var mailServer = $('#mailServerUrl').val();

	if (doc_mode=="Create"){
		// this check if user name eixsts 
		userExists($("#idUserName").val(), $.trim(userEmailAddr)); 
		if (userNameExists)
		{
			$('#idUserName').prop('readonly', false);
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				width: 400,
				title: prmptMessages.msgCF018,
				prompt: prmptMessages.msgAUP012.replace('%username%', $("#idUserName").val())
			});
			$("#idUserName").focus();
			userNameExists=false;
			return false;
		}
		
		if (userEmailExists)
		{
			window.top.Docova.Utils.messageBox({
				icontype: 1,
				msgboxtype: 0,
				width: 400,
				title: prmptMessages.msgCF018,
				prompt: prmptMessages.msgAUP013.replace('%emailaddress%', $.trim(userEmailAddr))
			});
			$("#Email").focus();
			userEmailExists=false;
			return false;
		}
	}

	if($.trim(userFirstNm) == ""){
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP014
		});
		$("#FirstName").focus();
		return false;
	}
	// Last Name
	if($.trim(userLastNm) == ""){
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP015
		});
		$("#LastName").focus();
		return false;
	}
	// Check if user with Admin is removing admin privileges
	if(docInfo.initUserRole == "Administration"){
		var currRole = $("#UserRole").val();
		if(currRole != "ROLE_ADMIN"){
			if(docInfo.UserNameAB == $('#idUserName').text()){
				window.top.Docova.Utils.messageBox({
					icontype: 2,
					msgboxtype: 4,
					width: 450,
					title: prmptMessages.msgAUP017,
					prompt: prmptMessages.msgAUP016,
					onYes: function() {
						return true;
					},
					onNo: function() {
						return false;
					}
				});
			}
		}
	}
	//chek new pwd
	if($.trim(NewPwd) == "" && (docInfo.isNewDoc || $('#NewPassword').length)){
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP018
		});
		$("#NewPassword").focus();
		return false;
	}
	// chk confirm pwd
	else if($.trim(confirmPwd) == "" && (docInfo.isNewDoc || $('#NewPassword').length)) {
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP018
		});
		$("#RetypePassword").focus();
		return false;
	}
	// edit doc
	else if ($('#NewPassword').length && $.trim(NewPwd) && $.trim(NewPwd) != $.trim(confirmPwd)) {
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP019
		});
		$("#NewPassword, #RetypePassword").val('');
		$("#NewPassword").focus();
		return false;
	}
	//userEmailAddr
	if($.trim(userEmailAddr) == ""){
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP020
		});
		$("#Email").focus();
		return false;
	}
	else if ($.trim(userEmailAddr) != $.trim(OldEmailAddress)){
		if (docInfo.isNewDoc)
			$("#OldEmailAddress").val($.trim(userEmailAddr));
		else
			$("#OldEmailAddress").val($.trim(userEmailAddr));
	}
	//userMailSystem
	if($.trim(userMailSystem)==""){
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP021
		});
		$("input[name=UserMailSystem]").focus();
		return false;
	}
	//Mail Server URL
	if ($('input[name=UserMailSystem]:checked').val() == 'X' && !$.trim(mailServer))
	{
		window.top.Docova.Utils.messageBox({
			icontype: 1,
			width: 400,
			title: prmptMessages.msgCF018,
			prompt: prmptMessages.msgAUP022
		});
		$('#mailServerUrl').focus();
		return false;			
	}
	//
	return true;
	
}
//verify pwd for new pwd and make sure to confirm 
function updatePwd(fldObj){
	//alert("updatePwd:updFlag: "+updFlag)
	fldObj.show();
	fldObj.children('td').first().html(' New Password:<span class="red">&nbsp;*</span>');
	fldObj.children('td').last().html('<input type="password" id="NewPassword" name="NewPassword" class="text ui-widget-content ui-corner-all"><span style="padding-left: 10px;">Retype password: </span><input type="password" id="RetypePassword" class="text ui-widget-content ui-corner-all">');
}

//check it user id or email exists
function userExists(user_name, user_email){

	var url = docInfo.CheckUserNameUrl;
	url += "?userName="+user_name+"&userEmail="+user_email;

	$.ajax({
		dataType: "xml",
		url: url,
		type: "GET",
		async:false,
		success: function(data){
			var $xml = jQuery(data);

			userNameExists= ($xml.find('user_name').text() === 'true');
			userEmailExists= ($xml.find('user_email').text() ==='true');
		}
	});
}

//========== DELEGATES ==============================================================
function aboutDelegates() {
	msg = "DOCOVA Delegates allow you to designate one or more users to Review or Approve documents on your behalf.  For example, if you are going away on vacation." + "<br><br>";  
	msg += "<lu>";
	msg += "<li>A Delegate is notified when they are set as your Delegate</li>";
	msg += "<li>If 'Notifications' is set to Yes, your Delegate will be notified at the same time that you are notified to complete a workflow step</li>";
	msg += "<li>A Delegate must have read access to a document in order to complete the workflow step on your behalf</li>";
	msg += "<li>Documents that are pending a workflow action that is assigned to you will NOT show in your Delegate's workflow 'to do' list.  The ability to Review or Approve as a Delegate is only evaluated when a document is opened.</li>";
	msg += "<li>When a Delegate Reviews/Approves on your behalf, the Audit Log records the name of the user that completed the action and that it was done on your behalf.</li>";
	msg += "</lu>";
	window.top.Docova.Utils.messageBox({
		title: "About DOCOVA Delegates",
		prompt: msg,
		icontype: 4, 
		msgboxtype: 0, 
		width: 700
	});
}

function DeleteDelegate(){
	var rs = DelegateData.recordset;
	var delegateUnidList = "";
	rs.MoveFirst();
	while(rs.EOF() == false){
		if (rs.Fields("Selected").getValue() == "1"){
			if(delegateUnidList == ""){
				delegateUnidList = rs.Fields("docid").getValue();
			}else{
				delegateUnidList += ", " + rs.Fields("docid").getValue();
			}
		}
	rs.MoveNext();
	}
	
	if(delegateUnidList == ""){
		window.top.Docova.Utils.messageBox({
			prompt: "Please select at least one Delegate entry to remove.",
			icontype: 1,
			msgboxtype: 0, 
			title: "No Delegates selected."
		})
		return;
	}
	
	var unidArray = new Array();
	unidArray = delegateUnidList.split(",");
	
	//----- Build and process the request -----
	var unidlist = "";
	for(var i=0; i<unidArray.length; i++){
		unidlist += "<Unid>" + unidArray[i] + "</Unid>";
	}

	var request = "<Request><Action>REMOVEDELEGATE</Action>";
	request += unidlist;
	request += "</Request>";
	//send the request to server
	var processUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/UserDataServices?OpenAgent";
	var httpObj = new objHTTP();
	if(!httpObj.PostData(request, processUrl) || httpObj.status=="FAILED"){ }
	ReloadDelegates();
}

function EditDelegate(row) {
	var rs = DelegateData.recordset;
	rs.AbsolutePosition(row);	
	var docid = rs.Fields("docid").getValue();
	var dlgUrl =  docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgDelegate/" + docid + "?EditDocument";
	var dlgDelegates = window.top.Docova.Utils.createDialog({
		id: "divDlgDelegates", 
		url: dlgUrl,
		title: "Delegates",
		height: 550,
		width: 500, 
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
        	"Save": function() {
				window.top.$("#divDlgDelegatesIFrame")[0].contentWindow.completeWizard(); //Call completeWizard in iframe in dialog
				//completeWizard is responsible for closing this dialog when save is clicked
        	},
        	"Close": function() {
				dlgDelegates.closeDialog();
        	}
      	}
	});
}

function addDelegate() {
	var dlgUrl =  docInfo.ServerUrl + "/" + docInfo.NsfName + "/" + "dlgDelegate?OpenForm";
		var dlgDelegates = window.top.Docova.Utils.createDialog({
		id: "divDlgDelegates", 
		url: dlgUrl,
		title: "Delegates",
		height: 450,
		width: 500, 
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
       		"Save": function() {
				window.top.$("#divDlgDelegatesIFrame")[0].contentWindow.completeWizard(); //Call completeWizard in iframe in dialog
			//completeWizard is responsible for closing this dialog when save is clicked
       		},
       		"Close": function() {
				dlgDelegates.closeDialog();
        	}
      	}
	});
}

function ReloadDelegates(){
	DelegateData.reload()
}

function DelegatesLoaded(){
	var xobj = DelegateData.getXMLDocument();
	var nodes = xobj.selectSingleNode("documents/document");
	if (nodes == null){
		document.getElementById("tblDelegateData").style.display = "none";
		document.getElementById("NoDelegateDataMsg").style.display = "";
	}else{
		document.getElementById("tblDelegateData").style.display = "";
		document.getElementById("NoDelegateDataMsg").style.display = "none";
	}
	
	$("#otblDelegateData tr").mouseover(function(){
		$(this).css('background-color', '#d8d8d8');
	}).mouseout(function(){
		$(this).css('background-color', 'white');
	});
	
	$(".btnEditRow").button({
		text: false,
		label: "Edit",		
		icons: 
		{
			primary: "ui-icon-pencil"
		}
	}).click( function(event){
		EditDelegate($(this).closest('tr').index()); //parameter is rowIndex. JQuery returns 0 based rows so add 1
		event.preventDefault();
	});
}
//========== END DELEGATES ==============================================================