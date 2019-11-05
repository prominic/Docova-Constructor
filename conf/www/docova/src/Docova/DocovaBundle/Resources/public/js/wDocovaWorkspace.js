$(document).ready(function(){
	isipad = (/iphone|ipad/i.test(navigator.userAgent.toLowerCase()));
	$(document).on("contextmenu", function(e){ e.preventDefault(); e.stopPropagation();});
	
	//-- tile based workspace
	if(docInfo && docInfo.WorkspaceID){
		$("body").css("overflow", "hidden");
		$(document).on('scroll', function() {
	  		$(document).scrollLeft(0);
		});
	
		$("#divWorkspace").click( function(event){ 
			//click in the workspace closes the hinkyminky menu if it is open
			if($("#hinkymenu").length == 1){
				$("#hinkymenu").remove();
			}
		});
	
		LoadWorkspace();
	

		$("#control-prev").click(function(){
			moveLeft();
		});

		$("#control-next").click(function(){
			moveRight();
		});		
	//-- side navigator based workspace
	}else{
	
		$('a.lib_actions').button({text: false});

		$('#app_search').keyup(function () {
			clearTimeout(typingTimer);
			if ($('#app_search').val()) {
				typingTimer = setTimeout(function() { searchApp(); }, 800);
			}
			else {
				$('#app_search').css({'background': '#FFF'});
				$('#panels_container').css('height', '85vh');
				$('#search_result').css('height', '3vh');
				$('#apps UL.app_list_container').first().html('');
			}
		});
		$('#app_search').on('keydown', function () { clearTimeout(typingTimer); });
		$('#create_app').on('click', function(){ CreateApp(); });
		$('#add_cat').on('click', function(){ AddAppGroup(); });
		$('#manage_appgroup').on('click', function() { ManageAppGroups(); });
	
		$(document).on ('click', 'li.openapp', function(e) {
			var selectedapp = $(this).closest('li');
			if ($(e.target).hasClass('noaccess') || $(e.target).hasClass('disabled')) {
				$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
				return false;
			}
			window.top.Docova.Utils.showProgressMessage("Launching Application...");
			setTimeout(function(){
				OpenApp(selectedapp);
				window.top.Docova.Utils.hideProgressMessage();
			}, 10);
			return false;
		});


		$(document).on('click', 'ul.action_btns li.openappbuilder', function() {
			var selectedapp = $(this).parent().closest('li');
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			window.top.Docova.Utils.showProgressMessage("Opening Application in App Builder...");
			setTimeout(function(){
				OpenInAppBuilder(selectedapp);
				window.top.Docova.Utils.hideProgressMessage();
			}, 10);		
			return false;
		});

		$(document).on('click', 'ul.action_btns li.copyapp', function() {
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			CopyApp($(this).parent().closest('li'));		
			return false;		
		});

		$(document).on('click', 'ul.action_btns li.updatedesign', function() {
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			UpdateAppDesign($(this).parent().closest('li'));
			return false;		
		});

		$(document).on('click', 'ul.action_btns li.deleteapp', function() {
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			DeleteApp($(this).parent().closest('li'));
			return false;		
		});

		$(document).on('click', 'ul.action_btns li.appproperties', function() {
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			OpenAppProperties($(this).parent().closest('li'));
			return false;		
		});

		$(document).on('click', 'ul.action_btns li.managelibraries', function() {
			$('ul.action_btns').addClass('hidden').css({'left': 0, 'top': 0});
			ManageLibraries($(this).parent().closest('li'));
			return false;		
		});

		$(document).on('click', 'div.categories h4', function() {
			if ($(this).find('.far:first-child').hasClass('fa-angle-down')) {
				$(this).find('.far:first-child').removeClass('fa-angle-down');
				$(this).find('.far:first-child').addClass('fa-angle-up');
				$(this).toggleClass('activated');
				$(this).next('ul').slideDown();
			}
			else {
				$(this).find('.far:first-child').addClass('fa-angle-down');
				$(this).find('.far:first-child').removeClass('fa-angle-up');
				$(this).toggleClass('activated');
				$(this).next('ul').slideUp();
			}
			return false;		
		});

		$(document).on('click', 'span.hinky', function() {
			$('.action_btns').addClass('hidden');
			$(this).parent().next('.action_btns').position({
				my: 'left top',
				at: 'right top',
				of: $(this).closest('.openapp')
			})
			.removeClass('hidden');
			return false;
		});
	
		$(document).on('mouseenter', '.action_btns li:not(.noaccess, .separator)', function(){ 
			$(this).addClass('ui-state-focus');
		});
		$(document).on('mouseleave', '.action_btns li:not(.noaccess, .separator)', function(){ 
			$(this).removeClass('ui-state-focus');
		});
	
		$(document).on('mouseleave', 'ul.action_btns', function(){
			$('.action_btns').addClass('hidden');
			$('.action_btns').css({'left': 0, 'top': 0});
		});
	
		$('#panels_container').scroll(function() {
			if ($('#all_apps > H4').hasClass('activated')) {
				if ($(this).children('.app_list_container').height() - ($(this).scrollTop() + $(this).height()) < 10) {
					loadMoreApps();
				}
			}
	    });
	}
});

function loadMoreApps()
{
	var req_url = docInfo.PortalWebPath + '/searchapp';
	if (start != -1 && stop === false)
	{
		stop = true;
		$('#loadmore').removeClass('hidden');
    	$.ajax({
    		type: 'POST',
    		url: req_url,
    		data: { 'searchtxt' : encodeURIComponent('*'), 'offset' : start },
    		success: function(response) {
    			if ($.trim(response)) {
    				$('#all_apps UL.app_list_container').first().append(response);
    				$('#all_apps LI.invisible').each(function() {
    					$(this).fadeIn('fast');
    					$(this).css('margin-top', '0');
    				});
    				start += 20;
    				stop = false;
    			}
    			else {
    				start = -1;
    			}
    			$('#loadmore').addClass('hidden');
    		},
    		error: function() {
    			$('#loadmore').addClass('hidden');
    			//@note: should I alert something on error?!
    		}
    	});
	}
}

function CreateApp()
{
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgCreateApp?OpenForm"
	var dlgCreateApp = window.top.Docova.Utils.createDialog({
		id: "divDlgCreateApp",
		url: dlgUrl,
		title: "Create a New Application",
		height: 500,
		width: 500,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Create": function (){
				var dlgDoc = window.top.$("#divDlgCreateAppIFrame")[0].contentWindow.document
				
				//var appType = $("#Type", dlgDoc).val();
				var appType = $("#AppType", dlgDoc).val();
				var appTitle = $("#Title", dlgDoc).val();
				var appDesc = $("#Description", dlgDoc).val();
				var appIcon = $("#AppIcon", dlgDoc).val();
				var appIconColor = $("#AppIconColor", dlgDoc).val();
				var appTemplatePath = $("#AppTemplatePath", dlgDoc).val();
				var appCopyOption = $("[name=CopyOption]:checked", dlgDoc).val();
				var appInherit = $("#InheritOption", dlgDoc).is(":checked");
				var isApp = $("#IsApp", dlgDoc).val();
				
				//-----App title cannot be blank
				if($.trim(appTitle) == ""){
					window.top.Docova.Utils.messageBox({
						title: "Title Empty",
						prompt: "The Application or Library Group Title cannot be blank.  Please provide a Title",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}
				//-----App description cannot be blank
				if($.trim(appDesc) == ""){
					window.top.Docova.Utils.messageBox({
						title: "Description Empty",
						prompt: "The Application or Library Group Description cannot be blank.  Please provide a Description",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;				
				}
				//Retro-fit for appType mods and determine progress message text
				var progressMessageText = "";
				switch(appType) {
   					case "-Custom-":
       					appType = "A";
    					progressMessageText = "Creating Application...one moment.";
    				break;
   					case "-Library-":
       					appType = "L";
    					progressMessageText = "Creating DOCOVA Library...one moment.";
    				break;
   					case "-Library Group-":
       					appType = "LG";
    					progressMessageText = "Creating Library Group...one moment.";
   	 				break;
					default:
						//If one of the templates above was not selected, then the template being used is an app template or library template
						//If isApp is 0 then it is a library "L", otherwise it is an app "A"
						if(isApp == "0"){
       						appType = "L"
    						progressMessageText = "Creating Library from template...one moment.";
    					}else{
       						appType = "A"
    						progressMessageText = "Creating Application from template...one moment.";
    					}
        			break;
				} 

				//Show progress message
				window.top.Docova.Utils.showProgressMessage(progressMessageText)
				
				//-----Submit the request to the server
				var agentName = "WorkspaceServices"
				var request = "<Request><Action>CREATEAPP</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
				request += "<Document>"
				request += "<AppType><![CDATA[" + appType + "]]></AppType>"
				request += "<Title><![CDATA[" + appTitle + "]]></Title>"
				request += "<Description><![CDATA[" + appDesc + "]]></Description>"
				request += "<IsApp>" + isApp + "</IsApp>"
				request += "<AppIcon>" + appIcon + "</AppIcon>"
				request += "<AppIconColor>" + appIconColor + "</AppIconColor>"
				request += "<AppTemplatePath>" + appTemplatePath + "</AppTemplatePath>"
				request += "<AppCopyOption>" + appCopyOption + "</AppCopyOption>"
				request += "<AppInherit>" + appInherit + "</AppInherit>"
				request += "</Document>"
				request += "</Request>"
				
				var resulttext = SubmitRequest(request, agentName);
				if(resulttext == "FAILED"){
					window.top.Docova.Utils.hideProgressMessage();
					alert("The Application could not be created.")
					return;
				}
				if(resulttext == "DBEXISTS"){
					window.top.Docova.Utils.hideProgressMessage();
					alert("An Application with this name already exists.  Please provide a different Application Filename.")
					return;
				}
				
				//-----Generate the appletHTML to insert into the current panel
				window.top.Docova.Utils.hideProgressMessage();
				var appUnid = resulttext;
				//----- If this is a Library Group, appDocKey is blank, if an App, then set the key
				if(appType == "LG"){
					var appDocKey = "";
				}else{
					var appDocKey = resulttext;
				}

				if(docInfo && docInfo.WorkspaceID){
					var appletHTML = GenAppletHTML(appType, appTitle, appDesc, appUnid, appDocKey, appIcon, appIconColor)

					//Create the portlet for the new App on the workspace
					var currentPanelNo = activePanelNo;
					//first ensure there is a place to drop the app, if so drop it, if not tell the user
					if($(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first').length == 1){
						var appContainer = $(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first');
						$(appContainer).html(appletHTML)
						$(appContainer).removeClass("droppable ui-droppable") //after adding an app into an app container, remove droppable from the container.					
						styleTheWorkspace(false);
						SaveWorkspace();
						dlgCreateApp.closeDialog();
					}else{
						window.top.Docova.Utils.messageBox({
							title: "Panel Full",
							prompt: "This Panel is full.<br><br>The Application is created, but you will need to add it to a different Panel.",
							icontype: 1,
							msgboxtype: 0,
							width: 400
						});
						return;
					}				
				}else{
					refreshLeftNav();
					dlgCreateApp.closeDialog();
				}
			},
			"Cancel": function(){
				dlgCreateApp.closeDialog();
			}
		}
	});
}

function CopyApp(appObj){
	var appType = "A";
	var srcAppUnid = $(appObj).prop("id");
	var srcAppDocKey = $(appObj).attr("dockey"); //Used for looking up the Library dockey because it could be different than the Library doc Unid.
	var srcAppIcon = $(appObj).attr("appicon");
	var srcAppIconColor = $(appObj).attr("appIconColor");
     var appChanged = false;
     
	//If the App is a System App, then return
	if (srcAppUnid == "Dashboard" || srcAppUnid == "Libraries" || srcAppUnid == "Search" || srcAppUnid == "Designer" || srcAppUnid == "Admin"){
		alert("Sorry, DOCOVA System Apps have no properties to view or edit.")
		return;
	}
	
	//Update app portlet info from lib doc to ensure its the latest
	var wsAppTitle = $(appObj).attr("appTitle");
	var wsAppDescription = $(appObj).attr("appdesc");
	dlgParams[0] = wsAppTitle; //get the dlgParams ready for opening the properties dialog. They may change if different below.
	dlgParams[1] = wsAppDescription;
	var srcAppTitle = "";
	var srcAppDescription = "";
	var srcAppPath = "";
	var srcAppFilename = "";
	
	var elementURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openApp/" + srcAppUnid + "?OpenDocument" + "&" + (new Date()).valueOf();	

	$.get(elementURL, function(results){
		srcAppTitle = results.Properties.title;
		srcAppDescription = results.Properties.description;		

		if ($(appObj).hasClass('recentapp') || (docInfo && docInfo.WorkspaceID))
		{
    		//-----If the current AppTitle from the Library doc is different than the AppTitle on the workspace portlet then update the workspace portlet
    		if ($.trim(srcAppTitle) != ""){
    			if($.trim(srcAppTitle) != $.trim(wsAppTitle)){
				$(appObj).attr("apptitle",srcAppTitle);
				$(appObj).find("app-title").text(srcAppTitle);
    				dlgParams[0] = srcAppTitle;
    				appChanged = true;
    			}
    		}
    		
    		//-----If the current AppDesc from the Library doc is different than the AppDesc on the workspace portlet then update the workspace portet
    		if ($.trim(srcAppDescription) != ""){
    			if($.trim(srcAppDescription) != $.trim(wsAppDescription)){
				$(appObj).attr("appdesc", srcAppDescription);
				$(appObj).prop("title", srcAppDescription);
    				dlgParams[1] = srcAppDescription;
    				appChanged = true;
    			}
    		}
    		
    		//-----If there was an update to the app title or app description of the workspace portlet then save the workspace with the new mods
    		if(appChanged){
    			SaveWorkspace();
    		}
		}

		var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgCopyApp?OpenForm"
		var dlgCopyApp = window.top.Docova.Utils.createDialog({
    		id: "divDlgCopyApp",
    		url: dlgUrl,
    		title: "Copy Application",
    		height: 300,
    		width: 500,
    		useiframe: true,
    		sourcewindow: window,
    		sourcedocument: document,
    		buttons: {
				"Make Copy": function (){
					var dlgDoc = window.top.$("#divDlgCopyAppIFrame")[0].contentWindow.document
					var appTitle = $("#Title", dlgDoc).val();
					var appDesc = $("#Description", dlgDoc).val();
					var appCopyOption = $("[name=CopyOption]:checked", dlgDoc).val();
					var appInherit = $("#InheritOption", dlgDoc).is(":checked");
				
					//-----App title cannot be blank
					if($.trim(appTitle) == ""){
						window.top.Docova.Utils.messageBox({
							title: "Application Title Empty",
							prompt: "The Application Title cannot be blank.  Please provide a Title",
							icontype: 1,
							msgboxtype: 0,
							width: 400
						});
						return;
					}
    				//-----App description cannot be blank
    				if($.trim(appDesc) == ""){
    					window.top.Docova.Utils.messageBox({
    						title: "Application Description Empty",
    						prompt: "The Application Description cannot be blank.  Please provide a Description",
    						icontype: 1,
    						msgboxtype: 0,
    						width: 400
    					});
    					return;				
    				}
				
    				//-----Application Title cannot be the same---
    				if($.trim(appTitle) == $.trim(srcAppTitle)){
    					window.top.Docova.Utils.messageBox({
    						title: "Duplicate Application Title",
    						prompt: "The new application title is the same as the source application.  Please provide a new title.",
    						icontype: 1,
    						msgboxtype: 0,
    						width: 400
    					});
    					return;
    				}

    				window.top.Docova.Utils.showProgressMessage("Copying Application info....one moment.")
    				//-----Build the application copy request
    				var agentName = "WorkspaceServices"
    				var request = "<Request><Action>COPYAPP</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
    				request += "<Document>"
    				request += "<Title><![CDATA[" + appTitle + "]]></Title>"
    				request += "<Description><![CDATA[" + appDesc + "]]></Description>"	
    				request += "<AppCopyOption>" + appCopyOption + "</AppCopyOption>"
    				request += "<AppInherit>" + appInherit + "</AppInherit>"
    				request += "<AppIcon>" + srcAppIcon + "</AppIcon>"
    				request += "<AppIconColor>" + srcAppIconColor + "</AppIconColor>"
    				request += "<SrcAppUnid>" + srcAppUnid + "</SrcAppUnid>"
    				request += "<SrcAppDocKey>" + srcAppDocKey + "</SrcAppDocKey>"
    				request += "</Document>"
    				request += "</Request>"
				
    				//-----Submit the request to the server
    				var resulttext = SubmitRequest(request, agentName);
    				
    				if(resulttext == "FAILED"){
    					window.top.Docova.Utils.hideProgressMessage();
    					alert("The Application could not be copied.")
    					return;
    				}
    				if(resulttext == "DBEXISTS"){
    					window.top.Docova.Utils.hideProgressMessage();
    					alert("An Application with this name already exists.  Please provide a different Application Filename.")
    					return;
    				}
    				if(resulttext == "SRCDBNOTFOUND"){
    					window.top.Docova.Utils.hideProgressMessage();
    					alert("The source application was not found.  It may have been moved or deleted. Contact your System Administrator.")
    					return;
    				}
    				
    				//-----Generate the appletHTML to insert into the current panel
    				window.top.Docova.Utils.hideProgressMessage();
    
    				var appUnid = resulttext;
    				var appDocKey = resulttext;

				if(docInfo && docInfo.WorkspaceID){
					var appletHTML = GenAppletHTML(appType, appTitle, appDesc, appUnid, appDocKey, srcAppIcon, srcAppIconColor)

					//Create the portlet for the new App on the workspace
					var currentPanelNo = activePanelNo;
					//first ensure there is a place to drop the app, if so drop it, if not tell the user
					if($(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first').length == 1){
						$(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first').html(appletHTML);
						styleTheWorkspace(false);
						SaveWorkspace();
					}else{
						window.top.Docova.Utils.messageBox({
							title: "Panel Full",
							prompt: "This Panel is full.<br><br>The Application was copied, but you will need to add it to a different Panel.",
							icontype: 1,
							msgboxtype: 0,
							width: 400
						});
						return;
					}
				}else{
	    				//@note: should I open the app and refresh the recent apps? or should I add the app to app search results? For now it just pops up the message
    					alert('"'+ appTitle +'" app is successfully copied.\nYou can search for it on the left pane.');
				}
    				dlgCopyApp.closeDialog();
    				
    			}, //Update
    			"Cancel": function(){
    				dlgCopyApp.closeDialog();
    			} //Cancel
			} //buttons
		}); //dialog
	}); //get
}

function AddApp(){
	//Add dialog to pick the application to add then create the portlet

	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgAddApp?OpenForm&workspace=T"
	var dlgAddApp = window.top.Docova.Utils.createDialog({
			id: "divDlgAddApp",
			url: dlgUrl,
			title: "Add an Application",
			height: 510,
			width: 500,
			useiframe: true,
			sourcewindow: window,
			sourcedocument: document,
			buttons: {
				"Add": function (){
					var dlgDoc = window.top.$("#divDlgAddAppIFrame")[0].contentWindow.document //dialog doc
					var dlgWin = window.top.$("#divDlgAddAppIFrame")[0].contentWindow //dialog window
					
					var recordset = dlgWin.AvailableAppData.recordset

					if (recordset == null){
						return;
					}

					var AppsSelected = false;
					var maxrows = recordset.getRecordCount();
		
					for(var row=0; row<maxrows; row++){
						recordset.AbsolutePosition(row);
						var selected = recordset.Fields("Selected").getValue();
						if(selected == "1"){
							AppsSelected = true;
						}		
					}

					//Nothing selected
					if(!AppsSelected){
						window.top.Docova.Utils.messageBox({
							title: "Nothing Selected",
							prompt: "You have not selected any Applications from the list. <br><br> Please select one or more Applications.",
							icontype: 1,
							msgboxtype: 0,
							width: 400
						});
						return;
					}

					//Get the app type for the apps that are being added
					var appType = $("#AppType", dlgDoc).val();

					//For each selected app row, get the app info and add the app to the workspace
					var existsApp = null; //obj to hold app obj if it already exists on the workspace
					var appExists = 0; //count for number of apps that were not added because they already exist.
					var addNoRoom = 0; //count for number of apps that couldn't be added because there was no space on the panel
					
					//Generate a list of subscriptions to be added
					var appsAdded = [];
					
					for(var row=0; row<maxrows; row++){
						var addNewApp = true; //flag, if app is not already on ws, and there is space on this panel then add, else dont add
						recordset.AbsolutePosition(row);
						var selected = recordset.Fields("Selected").getValue();
						if(selected == "1"){
							var appTitle = recordset.Fields("Title").getValue();
							var appDesc = recordset.Fields("Description").getValue();
							var appUnid = recordset.Fields("Unid").getValue();
							var appDocKey = recordset.Fields("DocKey").getValue();
							var appIcon = recordset.Fields("AppIcon").getValue();
							var appIconColor = recordset.Fields("AppIconColor").getValue();
							var isApp = recordset.Fields("IsApp").getValue();
							var appiconHTML = ""
							if(appIcon == ""){
								appiconHTML = "<i class='far  fa-database fa-5x' style='color:#ff6600;' appIcon=fa-database appIconColor=#ff6600></i>"
							}else{
								appiconHTML = "<i class='far " + appIcon + " fa-5x' style='color:" + appIconColor + ";' appIcon=" + appIcon + " appIconColor=" + appIconColor + "></i>"
							}						
							//Check if the app is already on the workspace
							var existsApp = $("#" + appUnid);
							if($(existsApp).length==1){
								appExists++;
								addNewApp = false;
							}
							
							//If the app is not already on the workspace ensure there is a place to drop the app, if so drop it
							if(addNewApp){
								var currentPanelNo = activePanelNo ;
								if($(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first').length == 1){
									
									//If the appType is T, it was being added from the template list. Use isApp to determine if it is an App or Library
									//and properly set the appType to A or L instead of it being T for "Template"
									if(appType == "T"){
										if(isApp == "1"){
											appType = "A"
										}else{
											appType = "L"
										}
									}
									
									//Generate the appletHTML to insert
									var appletHTML = GenAppletHTML(appType, appTitle, appDesc, appUnid, appDocKey, appIcon, appIconColor)
								
									var appContainer = $(".panel[pid=" + currentPanelNo +"]").find('div.droppable:first');
									$(appContainer).html(appletHTML)
									$(appContainer).removeClass("droppable ui-droppable") //after adding an app into an app container, remove droppable from the container.
									styleTheWorkspace(false);
									SaveWorkspace();
									if(appType == "A"){
										appsAdded.push(appDocKey);
									}
								}else{
									addNoRoom++;
								}
							}						
						}
					} //end for loop
										
					dlgAddApp.closeDialog();
					if(appExists > 0){
						var appExistsMsg = "";
						if(appExists > 1){
							appExistsMsg += appExists + " applications were not added because the applications<br>"
							appExistsMsg += "are already on one of your Workspace Panels."
						}else{
							appExistsMsg += appExists + " application was not added because the application<br>"
							appExistsMsg += "is already on one of your Workspace Panels."					
						}
					}
					if(addNoRoom > 0){
						var addNoRoomMsg = ""
						if(addNoRoom > 1){
							addNoRoomMsg += addNoRoom + " applications were not added because there is no room<br>"
							addNoRoomMsg += "on this Panel.  Please add them to a different Panel."
						}else{
							addNoRoomMsg += addNoRoom + " application was not added because there is no room<br>"
							addNoRoomMsg += "on this Panel.  Please add it to a different Panel."					
						}
					}
					
					if(appExists > 0 & addNoRoom > 0){ 
						var addAppMsg = appExistsMsg + "<br><br>" + addNoRoomMsg;
					}
					if(appExists > 0 & addNoRoom == 0){
						var addAppMsg = appExistsMsg;
					}
					if(appExists == 0 & addNoRoom > 0){
						var addAppMsg = addNoRoomMsg;
					}
					if(appExists > 0 | addNoRoom > 0){
						window.top.Docova.Utils.messageBox({
							title: "One or more applications were not added to your Workspace!",
							prompt: addAppMsg,
							icontype: 1,
							msgboxtype: 0,
							width: 400
						});
					}				
				},
				"Cancel": function(){
					dlgAddApp.closeDialog();
				}
			}
	});
}

function AddAppGroup()
{
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgCreateAppGroup?OpenForm"
	var dlgCreateAppGroup = window.top.Docova.Utils.createDialog({
		id: "divDlgCreateAppGroup",
		url: dlgUrl,
		title: "Create a New App Group",
		height: 500,
		width: 500,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Create": function (){
				var dlgDoc = window.top.$("#divDlgCreateAppGroupIFrame")[0].contentWindow.document;
				var groupName = $.trim($('#AppGroupName', dlgDoc).val());
				if (!groupName) {
					alert('Please provide an app-group name.');
					return false;
				}

				var request = '<Request><Action>CREATEAPPGROUP</Action><UserName><![CDATA[' + docInfo.UserNameAB + ']]></UserName>';
				request += '<AppGroupName><![CDATA['+ groupName +']]></AppGroupName>';
				
				if ($('#selectedApps li', dlgDoc).length) {
					$('#selectedApps li', dlgDoc).each(function() {
						request += '<Document>';
						request += '<Unid>'+ $(this).attr('dockey') +'</Unid>';
						request += '<AppType>'+ $(this).attr('type') +'</AppType>';
						request += '</Document>';
					});
				}
				request += '</Request>';

				var resulttext = SubmitRequest(request, 'WorkspaceServices');
				if(resulttext == "FAILED"){
			    	window.top.Docova.Utils.hideProgressMessage();
			    	alert('The App-Group could not be created.');
			    	return;
				}

				refreshLeftNav();
				dlgCreateAppGroup.closeDialog();
			},	
			"Cancel": function(){
				dlgCreateAppGroup.closeDialog();
			}
		}
	});
}

function GenAppletHTML(appType, appTitle, appDesc, appUnid, appDocKey, appIcon, appIconColor){
	if(appIcon == ""){
		appIcon = "fa-database";
		appIconColor = "#ff6600";
	}
		
	var baseappletHTML = ""
	
	baseappletHTML += '<div id="' + appUnid + '" class="app draggable" apptype="' + appType + '" title="' + appDesc + '" apptitle="'+ appTitle + '" appdesc="' + appDesc + '" appicon="' + appIcon + '" appiconcolor="' + appIconColor + '" dockey="' + appDocKey + '">'
	baseappletHTML += '<div class="app-icon-box">'
	baseappletHTML += '<div class="app-icon-wrapper"><i class="app-icon far ' + appIcon + '" style="color:' + appIconColor + ';"></i></div>'
	baseappletHTML += '</div>'
	baseappletHTML += '<div class="app-title ui-widget">' + appTitle + '</div>'
	baseappletHTML += '</div>'
	
	return baseappletHTML;

}


function AddWorkspacePanel(){
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgWSPanelProperties?OpenForm"
	
	var dlgPanelProperties = window.top.Docova.Utils.createDialog({
		id: "divDlgPanelProperties",
		url: dlgUrl,
		title: "New Workspace Panel",
		height: 250,
		width: 475,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Add Panel" : function(){
				var dlgDoc = window.top.$("#divDlgPanelPropertiesIFrame")[0].contentWindow.document
				var NewPanelName = $.trim($("#PanelName", dlgDoc).val());
				
				if(NewPanelName == ""){
					window.top.Docova.Utils.messageBox({
						title: "No Panel name provided",
						prompt: "You cannot create a Panel without a name.<br><br>Please provide a Panel name.",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}
				var lastPanelNo = $(".panel").length;
				var nextPanelNo = parseInt(lastPanelNo) + 1
				//Get the default panel html
				var DefaultPanelHTMLID = docInfo.DefaultPanelHTMLID
				var elementURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/loadWorkspace/" + DefaultPanelHTMLID + "?OpenDocument" + "&" + (new Date()).valueOf();
				$.get(elementURL, function(results){
					$("#divWorkspace").append($(results).filter("#WorkspaceHTML").html());
					var newPanel = $(".panel[pid=9999]");
					$(newPanel).attr("panelname", NewPanelName);
					$(newPanel).attr("pid", nextPanelNo);
					$(".app-container[pid=0]").attr("pid", nextPanelNo); //for each app-container in the new panel, set the panel id "pid".
					SaveWorkspace();
					dlgPanelProperties.closeDialog();
					goToPanel(nextPanelNo);
					SetNavControls();
					SetPanelNavIcons();
					styleTheWorkspace();
				})
			},
			"Cancel" : function(){
				dlgPanelProperties.closeDialog();
			}
		}
	})
}

function DeleteWorkspacePanel(){
	delmsgtxt = "You are about to remove this Panel from your Workspace:<br><br>Are you sure?"
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: delmsgtxt, 
		icontype: 2, 
		title: "Remove Panel from Workspace", 
		width:400, 
		msgboxtype: 4,
		onNo: function() {return},
		onYes: function() {
			if( $(".panel").length == 1){
				window.top.Docova.Utils.messageBox({
					title: "Error Deleting Panel",
					prompt: "Sorry you cannot delete ALL Panels from your Workspace.  You must have at least one.",
					icontype: 1,
					msgboxtype: 0,
					width: 400
				});
				return;
			}
			
			//get a list of app ids to remove
			var appsToRemove = [];
			jQuery(".panel[pid=" + activePanelNo +"]").find("div.app-container>div.app[apptype=A]").each(function(){
				appsToRemove.push(jQuery(this).attr("dockey"));
			});			
			
			var currentPanelNo = activePanelNo;
			var navToPanel = currentPanelNo - 1;
			//-----find the li slider that has the current panel no, delete it and renumber the panels
			var newWSHTML = "";
			$(".panel").each(function(){
				if($(this).attr("pid") != currentPanelNo){
					newWSHTML += $(this)[0].outerHTML;
				}
			})
			$("#divWorkspace").html(newWSHTML);
			var panelcnt = 1;
			$(".panel").each(function(){
				$(this).attr("pid", panelcnt);
				$(this).children().attr("pid", panelcnt);
				panelcnt ++;
			})
			
			SaveWorkspace();
			styleTheWorkspace();
			goToPanel(navToPanel);
			SetNavControls();
		}
	 });	
}

function ManageAppGroups()
{
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgManageAppGroup?OpenForm"
	var dlgMngAppGroup = window.top.Docova.Utils.createDialog({
		id: "divDlgManageAppGroup",
		url: dlgUrl,
		title: "Manage App Groups",
		height: 540,
		width: 500,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Save": function (){
				var dlgDoc = window.top.$("#divDlgManageAppGroupIFrame")[0].contentWindow.document;
				var groupName = $.trim($('#AppGroupName', dlgDoc).val());
				if (!groupName) {
					alert('Please provide an app-group name.');
					return false;
				}

				var request = '<Request><Action>UPDATEAPPGROUP</Action><UserName><![CDATA[' + docInfo.UserNameAB + ']]></UserName>';
				request += '<AppGroupName><![CDATA['+ groupName +']]></AppGroupName>';
				request += '<AppGroupUnid>'+ $('#myAppGroups', dlgDoc).val() +'</AppGroupUnid>';
				
				if ($('#selectedApps li', dlgDoc).length) {
					$('#selectedApps li', dlgDoc).each(function() {
						request += '<Document>';
						request += '<Unid>'+ $(this).attr('dockey') +'</Unid>';
						request += '<AppType>'+ $(this).attr('type') +'</AppType>';
						request += '</Document>';
					});
				}
				request += '</Request>';

				var resulttext = SubmitRequest(request, 'WorkspaceServices');
				if(resulttext == "FAILED"){
			    	window.top.Docova.Utils.hideProgressMessage();
			    	alert('The App-Group could not be updated.');
			    	return;
				}

				refreshLeftNav();
				dlgMngAppGroup.closeDialog();
			},
			"Delete": function(){
				var dlgDoc = window.top.$("#divDlgManageAppGroupIFrame")[0].contentWindow.document;
				var selectedAppGrpId = $('#myAppGroups', dlgDoc).val();
				if (!$.trim(selectedAppGrpId)) {
					alert('Please select an app-group first.');
					return false;
				}
				updatemsgtxt = "You are about to DELETE the selected App-Group.<br><br>Are you sure?";
				var choice = window.top.Docova.Utils.messageBox({
					prompt: updatemsgtxt, 
					icontype: 2, 
					title: "Delete App-Group", 
					width:380, 
					msgboxtype: 4,
					onNo: function() {return},
					onYes: function() {
						var request = '<Request><Action>DELETEAPPGROUP</Action><UserName><![CDATA[' + docInfo.UserNameAB + ']]></UserName>';
						request += '<AppGroupUnid>'+ $('#myAppGroups', dlgDoc).val() +'</AppGroupUnid>';
						request += '</Request>';

						var resulttext = SubmitRequest(request, 'WorkspaceServices');
						if(resulttext == "FAILED"){
					    	window.top.Docova.Utils.hideProgressMessage();
					    	alert('Could not delete selected App-Group.');
					    	return;
						}

						alert('Selected App-Group is successfully deleted.');
						refreshLeftNav();
						dlgMngAppGroup.closeDialog();
					}
				});
			},
			"Cancel": function(){
				dlgMngAppGroup.closeDialog();
			}
		}
	});
}

function ManageLibraries(appObj){
	var LibraryGroupId = $(appObj).prop("id");
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgManageLibraryGroup?OpenForm";

	dlgParams.length = 0; //reset dlgParams array.
	dlgParams[0] = LibraryGroupId; //Current library group id make availabe for properties dlg to get.
					
	var dlgManageLibraries = window.top.Docova.Utils.createDialog({
		id: "divDlgManageLibraries",
		url: dlgUrl,
		title: "Manage Libraries",
		height: 500,
		width: 475,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Done": function (){
				var dlgDoc = window.top.$("#divDlgManageLibrariesIFrame")[0].contentWindow.document
				var dlgWin = window.top.$("#divDlgManageLibrariesIFrame")[0].contentWindow
				var recordset = dlgWin.SubscribedLibraries.recordset
				if (recordset == null){
					dlgManageLibraries.closeDialog();
					return;
				}
				var CurrLibList = "";
				recordset.MoveFirst();
				while(recordset.EOF() == false)
				{
					if($.trim(recordset.Fields("DocKey").getValue()) != ""){
						if(CurrLibList == ""){
							CurrLibList = $.trim(recordset.Fields("DocKey").getValue());
						}else{
							CurrLibList += "," + $.trim(recordset.Fields("DocKey").getValue());
						}
					}
				recordset.MoveNext();
				}	
				$(appObj).attr("libraryList", CurrLibList);
				var objApp = window.top.Docova.GlobalStorage[LibraryGroupId] //gets the cached app from globalstorage
				if(objApp != null){
					objApp.libraryList = CurrLibList;
				}
				
				if(docInfo && docInfo.WorkspaceID){
					SaveWorkspace();
				}
				dlgManageLibraries.closeDialog();
			},
			"Cancel": function(){
				dlgManageLibraries.closeDialog();
			}
		}
	});
}

function OpenApp(appObj){
	var appType = $(appObj).attr("apptype");
	var appUnid = $(appObj).prop("id")
	appDocKey = $(appObj).attr("dockey") //Used for looking up the Library dockey because it could be different than the Library doc Unid.
	var appChanged = false;
	if (appUnid == "AppBuilder") {
		if (isipad) {
			alert("App Builder is not supported on IPAD devices");
			return;
		}
	}

	//-----Special case for DOCOVA System Apps, if app is the Libraries Repository then open Libraries tab
	if (appUnid == "Dashboard" || appUnid == "Libraries" || appUnid == "Search" || appUnid == "Designer" || appUnid == "Admin"|| appUnid == "AppBuilder" ){
		var currAppTitle = $(appObj).attr("apptitle"); //re-get app title in case it had changed
		var AppWebPath = ""; //Path is blank for System Apps, resolved in AddAppTab function
		var findAppFrame = $("#iFrameMain", window.top.document).contents().find("#fra" + appUnid)
		
		if(findAppFrame.length == 0){
			Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').AddAppTab(appUnid, currAppTitle, AppWebPath);
			Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(appUnid)
		}else{
			Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame(appUnid)
		}
		return;
	}

	var wsAppTitle = $(appObj).attr("apptitle");
	var wsAppDescription = $(appObj).attr("appdesc");
	var wsAppIcon = $(appObj).attr("appicon");
	var wsAppIconColor = $(appObj).attr("appiconcolor");
	var AppTitle = "";
	var AppDescription = "";
	var AppIcon = "";
	var AppIconColor = "";	
	var AppWebPath = "";
	var IsApp = "";
	var AppDocKey = "";
	var LibraryList = "";
	
	var objApp = new DocovaApplication( { appid : appUnid });
	if(docInfo && docInfo.WorkspaceID){
		if ( objApp ==null ){
			if (appUnid != "Dashboard" & appUnid != "Libraries" & appUnid != "Search" & appUnid != "Designer" & appUnid != "Admin" & appUnid != "AppBuilder"){
				window.top.Docova.Utils.messageBox({
					title: "Error Opening Application",
					prompt: "There was an error opening this application. It was probably deleted.<br>You can try removing it from your Workspace and then re-adding it.",
					icontype: 1,
					msgboxtype: 0,
					width: 500
				});
			}
		}

		//-----If the current AppTitle from the Library doc is different then replace the App info
		if ($.trim(objApp.appName) != ""){
			if($.trim(objApp.appName) != $.trim(wsAppTitle)){
				$(appObj).attr("apptitle", objApp.appName); //change the app attribute
				$(appObj).find(".app-title").text(objApp.appName); //change the text in the .app-title div
				appChanged = true;
			}
		}
		
		//-----If the current AppDesc from the Library doc is different then replace the App info	
		if ($.trim(objApp.appDescription) != ""){
			if($.trim(objApp.appDescription) != $.trim(wsAppDescription)){
				$(appObj).attr("appdesc", objApp.appDescription); //change the appdesc attribute for the app
				$(appObj).prop("title", objApp.appDescription); //set the title on the app for rollover
				appChanged = true;
			}
		}
		
		//-----If the current AppIcon from the Library doc is different then replace the App icon	
		if ($.trim(objApp.appIcon) != ""){
			if($.trim(objApp.appIcon) != $.trim(wsAppIcon)){
				$(appObj).attr("appicon", objApp.appIcon); //change the appicon attr for the app
				$(appObj).find("i:first").removeClass(wsAppIcon).addClass(objApp.appIcon);
				appChanged = true;
			}
		}

		//-----If the current AppIconColor from the Library doc is different then replace the App icon color	
		if ($.trim(objApp.appIconColor) != ""){
			if(typeof(parentPortlet) != undefined && $.trim(objApp.appIconColor) != $.trim(wsAppIconColor)){
				$(parentPortlet).find("i:first").css("color", objApp.appIconColor);
				$(parentPortlet).find("i:first").attr("appIconColor", objApp.appIconColor)
				$(appObj).attr("appiconcolor", objApp.appIconColor);
				$(appObj).find("i:first").css("color", objApp.appIconColor);
				appChanged = true;
			}
		}		
		
		//-----Save Workspace if one of the app portlet items has changed
		if(appChanged){
			SaveWorkspace();
		}
	}

	objApp.launchApplication();
	updateRecentOpenedApps(appUnid, appType);
	
	if(!(docInfo && docInfo.WorkspaceID)){
		refreshLeftNav();
	}	
	return;
}

function OpenInAppBuilder(appObj){
	if ( isipad ) {
		alert ( "App Builder is not supported on IPAD devices" );
		return;
	}

	var appDocKey = $(appObj).attr("DocKey");
	
	//Check User access to open the application in the App Builder		
	var resulttext = CheckUserAppAccess(appDocKey);
    if(resulttext < 5){  //5 is Designer, 6 is Manager anything less is insufficient access
    	window.top.Docova.Utils.messageBox({
    		title: "Insufficient Access",
    		prompt: "Sorry, you have insufficient access to edit the Design of this Application.",
    		icontype: 1,
    		msgboxtype: 0,
    		width: 300
    	});
    	return;
    }	

    //Saves the users workspace when changes are made
    var agentName = "WorkspaceServices"
    var request = "<Request><Action>ADDTOAPPLIST</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
    request += "<Document>"
    request += "<AppID>" + appDocKey+ "</AppID>"
    request += "</Document>"
    request += "</Request>"
    
    var result = SubmitRequest(request, agentName);
    if(result == "FAILED"){
    	window.top.Docova.Utils.hideProgressMessage();
    	alert("The Application could not be opened in App Builder.");
    	return;
    }

    //If AppBuilder tab is not open, open it and nav to app. If it is open, nave to app and switch to frame/tab
    window.top.Docova.GlobalStorage['appBuilderOpenApp'] = appDocKey;
    var findAppFrame = $("#iFrameMain", window.top.document).contents().find("#fraAppBuilder");
    if(findAppFrame.length == 0){
    	Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').AddAppTab("AppBuilder", "App Builder", "");
    	Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame("AppBuilder");
    }else{
    	var AppFrameDoc = findAppFrame[0].contentWindow.document;
    	var leftFrameSrc = $("#fraLeftFrame", AppFrameDoc).attr("src");
    	$("#fraLeftFrame", AppFrameDoc).attr("src", leftFrameSrc);
    	Docova.getUIWorkspace(document).getDocovaFrame('fraToolbar', 'window').OpenFrame("AppBuilder");
    }
    updateRecentOpenedApps(appDocKey, 'A');
	if(!(docInfo && docInfo.WorkspaceID)){
		refreshLeftNav();
	}	
    return;
}

function updateRecentOpenedApps(appUnid, appType)
{
	var agentName = "WorkspaceServices";
	var request = "<Request><Action>UPDATELATESTOPENAPP</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Document>";
	request += "<AppID>" + appUnid+ "</AppID>";
	request += "<AppType>" + appType+ "</AppType>";
	request += "</Document>";
	request += "</Request>";

	var resulttext = SubmitRequest(request, agentName);
	if(resulttext == "FAILED"){
		//@note: should I alert the error message?
		alert('Could not update recent opened apps.');
	}

	return true; //allow continue opening app
}

function refreshLeftNav()
{
	var agentName = "WorkspaceServices"
	var request = "<Request><Action>REFRESHNAV</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
		request += "</Request>";
	var fresh_content = SubmitRequest(request, agentName);
	if (fresh_content && fresh_content != 'FAILED')
	{
		$('#panels_container').html(decodeURIComponent(fresh_content));
	}
}



function UpdateAppDesign(appObj)
{
	var appDocKey = $(appObj).attr("DocKey");
	var agentName = "LibraryServices";
	var request = "<Request><Action>GETLIBRARYINFO</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	request += "<Document>";
	request += "<LibraryKey>" + appDocKey + "</LibraryKey>";
	request += '<LibraryName></LibraryName>';
	request += "<InfoType>InheritDesignFrom</InfoType>";
	request += "</Document>";
	request += "</Request>";
				
	var resulttext = SubmitRequest(request, agentName);
	if($.trim(resulttext) == ""){
		var InheritFromApp = "Master Application/Library";
	}else{
		var InheritFromApp = resulttext;
	}

	updatemsgtxt = "You are about to UPDATE the design of this Application.<br><br>This Application's design will be updated with:<br><b>" + InheritFromApp + "</b><br><br>Are you sure?";
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: updatemsgtxt, 
		icontype: 2, 
		title: "Update Application Design", 
		width:450, 
		msgboxtype: 4,
		onNo: function() {return},
		onYes: function() {
			window.top.Docova.Utils.showProgressMessage("Updating design....one moment.");
			agentName = "WorkspaceServices"
			request = "<Request><Action>UPDATEAPPDESIGN</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
			request += "<Document>"
			request += "<AppDocKey>" + appDocKey + "</AppDocKey>"
			request += "</Document>"
			request += "</Request>"
			
			resulttext = SubmitRequest(request, agentName);
			
			window.top.Docova.Utils.hideProgressMessage();
			
			if($.trim(resulttext) == "DESIGNUPDATED"){
				alert("The Application's design was updated successfully.")
			}else{
				alert("The Application's design was NOT updated successfully.  Try again or contact your System Administrator.")
			}
		}
	});	
}



function DeleteApp(appObj){
	var appUnid = $(appObj).prop("id")
	var appType = $(appObj).attr("apptype")
	var delAction = "";
	
	if (appUnid == "Dashboard" || appUnid == "Libraries" || appUnid == "Search" || appUnid == "Designer" || appUnid == "Admin"){
		alert("You cannot delete DOCOVA System Applications")
		return;
	}
	
	if(appType == "LG"){
		delmsgtxt = "You are about to DELETE this Library Group.<br><br>Deleting a Library Group will only delete the group, NOT the libraries.<br><br>Are you sure?"	
	}else{
		delmsgtxt = "You are about to DELETE this application.<br><br>Deleting this application will delete all the information in the Application.<br><br>Are you sure?"
	}
	
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: delmsgtxt, 
		icontype: 2, 
		title: "Delete the Application/Library Group", 
		width:400, 
		msgboxtype: 4,
		onNo: function() {return},
		onYes: function() {
			var appDocKey = $(appObj).attr("dockey")
			var IsApp = "";
		
			if(appType == "LG"){
				delAction = "DELETELG" //delete action if library group
			}else{
				delAction = "DELETEAPP" //delete action if app
			}
		
			window.top.Docova.Utils.showProgressMessage("Deleting Application....one moment.")
			var elementURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openApp/" + appUnid + "?OpenDocument" + "&" + (new Date()).valueOf();
    		if (appType != "LG")
    		{	
        		$.get(elementURL, function(results){
        			IsApp = $(results).find("#luIsApp").val()
        			//-----Submit the request to the server
        			var agentName = "WorkspaceServices"
        			var request = "<Request><Action>" + delAction + "</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
        			request += "<Document>";
        			request += "<appUnid>" + appUnid + "</appUnid>";
        			request += "<appDocKey>" + appDocKey + "</appDocKey>";
        			request += "<isApp>" + IsApp + "</isApp>";
        			request += "</Document>";
        			request += "</Request>";
        		
        			var resulttext = SubmitRequest(request, agentName);
        			if(resulttext == "NOACCESS"){
        				window.top.Docova.Utils.hideProgressMessage();
        				alert("The Application could not be deleted.  You do not have sufficient access to delete!")
        				return;
        			}
        			if(resulttext == "DELETED"){
        				//-----Remove the application from the workspace
						if(docInfo && docInfo.WorkspaceID){
							var appContainer = $(appObj).parent();
		        				$(appObj).remove();
		        				$(appContainer).addClass("droppable ui-droppable");
						}else{
		        				refreshLeftNav();
						}
	        			window.top.Docova.Utils.hideProgressMessage();
	        			if(docInfo && docInfo.WorkspaceID){
	        				styleTheWorkspace(false);
	        				SaveWorkspace();			
						}
					
        			}
        		});
    		}
    		else {
    			var agentName = "WorkspaceServices";
       			var request = "<Request><Action>" + delAction + "</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
       			request += "<Document>";
       			request += "<appUnid>" + appUnid + "</appUnid>";
       			request += "<appDocKey>" + appDocKey + "</appDocKey>";
      			request += "<isApp>" + IsApp + "</isApp>";
       			request += "</Document>";
       			request += "</Request>";
       		
      			var resulttext = SubmitRequest(request, agentName);
       			if(resulttext == "LGNOTDELETED"){
       				window.top.Docova.Utils.hideProgressMessage();
       				alert("The Library group could not be deleted.");
       				return;
      			}
       			if(resulttext == "DELETED"){
       				//-----Remove the application from the workspace
       				$(appObj).remove();
					if(docInfo && docInfo.WorkspaceID){
						var appContainer = $(appObj).parent();
						$(appContainer).addClass("droppable ui-droppable");
					}
	       			window.top.Docova.Utils.hideProgressMessage();
					if(docInfo && docInfo.WorkspaceID){
						styleTheWorkspace(false);
	       				SaveWorkspace();
					}			
       			}
			}
		}
	});	
}

function OpenAppProperties(appObj)
{
	var appUnid = $(appObj).prop("id");
	var appType = $(appObj).attr("apptype");
	appDocKey = $(appObj).attr("dockey"); //Used for looking up the Library dockey because it could be different than the Library doc Unid.
	var appChanged = false;
	//If the App is a System App, then return
	if (appUnid == "Dashboard" || appUnid == "Libraries" || appUnid == "Search" || appUnid == "Designer" || appUnid == "Admin"){
		alert("Sorry, DOCOVA System Apps have no properties to view or edit.")
		return;
	}
	
	//Update app portlet info from lib doc to ensure its the latest
	var wsAppTitle = $(appObj).attr("apptitle");
	var wsAppDescription = $(appObj).attr("appdesc");
	var wsAppIcon = $(appObj).attr("appicon");
	var wsAppIconColor = $(appObj).attr("appiconcolor");
	var wsAppType = $(appObj).attr("apptype")

	dlgParams[0] = wsAppTitle; //get the dlgParams ready for opening the properties dialog. They may change if different below.
	dlgParams[1] = wsAppDescription;
	dlgParams[2] = wsAppIcon;
	dlgParams[3] = wsAppIconColor;
	dlgParams[4] = wsAppType;
	dlgParams[5] = appDocKey;
	dlgParams[6] = ""; //For isTemplate
	var AppTitle = "";
	var AppDescription = "";
	var AppIcon = "";
	var AppIconColor = "";
	
	var elementURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/openApp/" + appUnid + "?OpenDocument&apptype=" + appType + "&" + (new Date()).valueOf();	

	$.get(elementURL, function(results){
		AppTitle = $(results).find("#luAppTitle").val();
		AppDescription = $(results).find("#luDescription").val();		
		AppIcon = $(results).find("#luAppIcon").val();
		AppIconColor = $(results).find("#luAppIconColor").val();
		dlgParams[6] = $(results).find("#luIsTemplate").val();

		if (appObj.hasClass('recentapp') || (docInfo && docInfo.WorkspaceID))
		{
	    		//-----If the current AppTitle from the Library doc is different then replace the App info
	    		if ($.trim(AppTitle) != ""){
	    			if($.trim(AppTitle) != $.trim(wsAppTitle)){
					$(appObj).attr("apptitle", AppTitle);
					$(appObj).find(".app-title").text(AppTitle);
	    				dlgParams[0] = AppTitle;
	    				appChanged = true;
	    			}
	    		}
    		
	    		//-----If the current AppDesc from the Library doc is different then replace the App info	
	    		if ($.trim(AppDescription) != ""){
	    			if($.trim(AppDescription) != $.trim(wsAppDescription)){
					$(appObj).attr("appdesc", AppDescription);
					$(appObj).prop("title", AppDescription);
	    				dlgParams[1] = AppDescription;
	    				appChanged = true;
	    			}
	    		}
    		
	    		//-----If the current AppIcon from the Library doc is different then replace the App icon	
	    		if ($.trim(AppIcon) != ""){
	    			if($.trim(AppIcon) != $.trim(wsAppIcon)){
					$(appObj).attr("appicon", AppIcon);
					$(appObj).find("i:first").removeClass(wsAppIcon).addClass(AppIcon);
	    				dlgParams[2] = AppIcon;
	    				appChanged = true;
	    			}
	    		}
    
	    		//-----If the current AppIconColor from the Library doc is different then replace the App icon color	
	    		if ($.trim(AppIconColor) != ""){
	    			if($.trim(AppIconColor) != $.trim(wsAppIconColor)){
					$(appObj).attr("appiconcolor", AppIconColor);
					$(appObj).find("i:first").css("color", AppIconColor);
	    				dlgParams[3] = AppIconColor;
	    				appChanged = true;
	    			}
	    		}		
		
	    		//-----Save Workspace if one of the app portlet items has changed
	    		if(appChanged){
	    			SaveWorkspace();
	    		}
		}

		var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgAppProperties?OpenForm"
		var dlgAppProperties = window.top.Docova.Utils.createDialog({
			id: "divDlgAppProperties",
    			url: dlgUrl,
    			title: "Application Properties",
    			height: 500,
    			width: 500,
    			useiframe: true,
    			sourcewindow: window,
    			sourcedocument: document,
    			buttons: {
	    			"Update": function (){
	    				var dlgDoc = window.top.$("#divDlgAppPropertiesIFrame")[0].contentWindow.document
	    				var appTitle = $("#Title", dlgDoc).val();
	    				var appDesc = $("#Description", dlgDoc).val();
	    				var appIcon = $("#AppIcon", dlgDoc).val();
	    				var appIconColor = $("#AppIconColor", dlgDoc).val();
    				
	    				//-----App title cannot be blank
	    				if($.trim(appTitle) == ""){
	    					window.top.Docova.Utils.messageBox({
	    						title: "Application Title Empty",
	    						prompt: "The Application Title cannot be blank.  Please provide a Title",
	    						icontype: 1,
	    						msgboxtype: 0,
	    						width: 400
	    					});
	    					return;
	    				}
	    				//-----App description cannot be blank
	    				if($.trim(appDesc) == ""){
	    					window.top.Docova.Utils.messageBox({
	    						title: "Application Description Empty",
	    						prompt: "The Application Description cannot be blank.  Please provide a Description",
	    						icontype: 1,
	    						msgboxtype: 0,
	    						width: 400
	    					});
	    					return;				
	    				}
    
	    				window.top.Docova.Utils.showProgressMessage("Updating Application info....one moment.")
	    				//-----Submit the request to the server
	    				var agentName = "WorkspaceServices"
	    				var request = "<Request><Action>UPDATEAPPDOC</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	    				request += "<Document>"
	    				request += "<AppUnid>" + appUnid + "</AppUnid>"
	    				request += "<AppDocKey>" + appDocKey + "</AppDocKey>"
	    				request += "<AppType>" + appType + "</AppType>"
	    				request += "<Title><![CDATA[" + appTitle + "]]></Title>"
	    				request += "<Description><![CDATA[" + appDesc + "]]></Description>"
	    				request += "<AppIcon>" + appIcon + "</AppIcon>"
	    				request += "<AppIconColor>" + appIconColor + "</AppIconColor>"
	    				request += "</Document>"
	    				request += "</Request>"
    				
	    				var resulttext = SubmitRequest(request, agentName);
	    				if(resulttext == "NOACCESS"){
	    					window.top.Docova.Utils.hideProgressMessage();
	    					alert("Sorry, you don't have access to change the Properties for this Library/Application.")
	    					return;
	    				}
	    				if(resulttext == "NOLIBDOC"){
	    					window.top.Docova.Utils.hideProgressMessage();
	    					alert("Sorry, the Properties could not be updated for this Library/Application.")
	    					return;
	    				}
    				
					var holdAppIcon = $(appObj).attr("appicon");
					if(docInfo && docInfo.WorkspaceID){
						$(appObj).attr("apptitle", appTitle);
						$(appObj).find(".app-title").text(appTitle);
						$(appObj).attr("appdesc", appDesc);
						$(appObj).prop("title", appDesc);
						$(appObj).attr("appicon", appIcon);
						$(appObj).find("i:first").removeClass(holdAppIcon).addClass(appIcon);
						$(appObj).attr("appiconcolor", appIconColor);
						$(appObj).find("i:first").css("color", appIconColor);
					
						SaveWorkspace();
					}else{
		    				$('UL.app_list_container li[id='+ appUnid +']').each(function() {
		        				$(this).attr("apptitle", appTitle);
		        				$(this).find(".app_title").text(appTitle);
		        				$(this).attr("appdesc", appDesc);
		        				$(this).prop("title", appDesc);
		        				$(this).attr("appicon", appIcon);
		        				$(this).find("i:first").removeClass(holdAppIcon).addClass(appIcon);
		        				$(this).attr("appiconcolor", appIconColor);
		        				$(this).find("i:first").css("color", appIconColor);        				
		        			});
					}
				    				
	    				window.top.Docova.Utils.hideProgressMessage();
	    				dlgAppProperties.closeDialog();
	    			}, //Update
	    			"Cancel": function(){
	    				dlgAppProperties.closeDialog();
	    			} //Cancel
	    		} //buttons
	    	}); //dialog
	}); //get
}



function moveLeft() {
	var numOfPanels = $(".panel").length;
	var currPanelNo = activePanelNo;
	
	if(currPanelNo == 1){
		var nextPanelNo = numOfPanels;
	}else{ 
		var nextPanelNo = parseInt(currPanelNo) - 1;
	}
	
	var currPanel = $(".panel[pid=" + currPanelNo +"]");
	var nextPanel = $(".panel[pid=" + nextPanelNo + "]");
	activePanelNo = nextPanelNo;

    $(currPanel).hide('slide', {direction: 'right'}, 500);
    $(nextPanel).show('slide', {direction: 'left'}, 500);
    
	currPanelName = $(".panel[pid=" + activePanelNo + "]").attr("panelname")
  	$("#CurrentPanelName").text(currPanelName);
  	
  	SetNavControls();
  	SetPanelNavIcons();
};

function moveRight() {
	var numOfPanels = $(".panel").length;
	var currPanelNo = activePanelNo;
	
	if(currPanelNo == numOfPanels){
		var nextPanelNo = 1;
	}else{ 
		var nextPanelNo = parseInt(currPanelNo) + 1;
	}	
	
	var currPanel = $(".panel[pid=" + currPanelNo +"]");
	var nextPanel = $(".panel[pid=" + nextPanelNo + "]");
	activePanelNo = nextPanelNo;
    $(currPanel).hide('slide', {direction: 'left'}, 1000);
    $(nextPanel).show('slide', {direction: 'right'}, 1000);
  	
	currPanelName = $(".panel[pid=" + activePanelNo + "]").attr("panelname")
  	$("#CurrentPanelName").text(currPanelName);
	
  	SetNavControls();
  	SetPanelNavIcons();
};

function goToPanel(panelNo){
	var currPanelNo = activePanelNo;
	var currPanel = $(".panel[pid=" + currPanelNo +"]");
	var gotoPanel = $(".panel[pid=" + panelNo +"]");
	
	if(currPanelNo < panelNo){
		$(currPanel).hide('slide', {direction: 'left'}, 500);
    		$(gotoPanel).show('slide', {direction: 'right'}, 500);
    	}else{
		$(currPanel).hide('slide', {direction: 'right'}, 500);
    		$(gotoPanel).show('slide', {direction: 'left'}, 500);    	
    	}

    $("#CurrentPanelName").text($(gotoPanel).attr("panelname"))
    activePanelNo = panelNo;
    SetNavControls();
    SetPanelNavIcons()
    return;
}

function LoadWorkspace(gotoSlide){
	var WorkspaceID = docInfo.WorkspaceID
	var elementURL = docInfo.ServerUrl + "/" + docInfo.NsfName + "/loadWorkspace/" + WorkspaceID + "?OpenDocument" + "&" + (new Date()).valueOf();	

	$("#divWorkspaceHMTL").css("display", "none");
	$.get(elementURL, function(results){
		$("#divWorkspace").html($(results).filter("#WorkspaceHTML").html())
			$(".panel").css("display", "none")
			$(".panel[pid=1]").css("display", "block");
			
			$("div.app, div.droppable, div.draggable, draggable2, .panel").removeClass("bound");
			
			styleTheWorkspace(true, gotoSlide);
			if(docInfo.isDefaultWorkspace == "1"){
				SaveWorkspace();
				AboutWorkspace();
			}
			
			SetNavControls();
	})
}

function styleTheWorkspace(includeSlider, gotoSlide){
	
	$("div.app:not(.bound)").click(function(){
				appObj = $(this);
				OpenApp(appObj);
	}).on("mousedown", function(e){ 
		if ( isipad ) {
			appObj = $(this);
			OpenApp(appObj);
			return;
		}
		if( e.button == 2 ) {
			appObj = $(this); 
			OpenHinkyMinkyMenu(e);
		}
	}).mouseover(function(e){
		var innerWrapper = $(this).find(".app-icon-wrapper");
		$(innerWrapper).removeClass("app-icon-wrapper").addClass("app-icon-wrapper-on");
	}).mouseout(function(e){
		var innerWrapper = $(this).find(".app-icon-wrapper-on");
		$(innerWrapper).removeClass("app-icon-wrapper-on").addClass("app-icon-wrapper");
	});		
		
	$("div.draggable:not(.bound)").on("mousedown", function(e){
		var innerWrapper = $(this).find(".app-icon-wrapper-on")
		$(innerWrapper).removeClass("app-icon-wrapper-on").addClass("app-icon-wrapper");
		ddAppletHTML = $(this).parent().html();
	}).draggable({
		revert: "invalid",
		delay: 100,
		containment: "document",
		appendTo: "body",
		refreshPositions: true,
		opacity: 0.5,			
		helper: function (event,ui) { //needed because draggable itself doesn't work with the panes, this helper appends to body
			$draghelper = $(this).clone();
			var appHeight = $(this).height();
			var appWidth = $(this).width();
			return $draghelper.appendTo("body").css("zIndex",5).css("width", appWidth).css("height", appHeight).show();
		},
		start: function() {
			$(".app-container").addClass("app-container-reveal");
			$(this).hide();
		},
		stop: function() {
			$(".app-container").removeClass("app-container-reveal");
			$(this).show();
		}
	});				
	
	$("div.droppable:not(.bound)").droppable({
		accept: ".draggable",
		tolerance: "pointer",
		drop: function( event, ui ) {
			$(".app-container").removeClass("app-container-reveal");
			AppletDropped(this, ui)
		},
		over: function (event, ui) {
			$(this).removeClass("app-container-reveal");
			$(this).addClass("app-container-dragover");
			},
		out: function (event, ui) {
			$(this).removeClass("app-container-dragover");
			$(this).addClass("app-container-reveal");
		}
	});	
	
	$(".droppable2:not(.bound)").droppable({
		accept: ".draggable",
		tolerance: "pointer",
		over: function (event, ui){
			if($(this).prop("id") == "control-prev"){
				moveLeft();
			}
			if($(this).prop("id") == "control-next"){
				moveRight();
			}	
		}
	});
	
	$(".panel:not(.bound)").on('scroll', function() {
  		$(".panel").scrollLeft(0);
	});
	
	$("div.app, div.droppable, div.draggable, draggable2, .panel").addClass("bound");
	
	currPanelName = $(".panel[pid=" + activePanelNo + "]").attr("panelname")
	$("#CurrentPanelName").text(currPanelName);
		
	SetPanelNavIcons();	
	
}

function AppletDropped(thisobj, ui){
	$(thisobj).html(ddAppletHTML); //thisobj is the currently highlighted app container
	$(thisobj).removeClass("droppable ui-droppable app-container-dragover bound").find("div.app, div.droppable, div.draggable").removeClass("bound");
	try{
		$(thisobj).droppable( "destroy" );
	}catch(e){}
	
	prevAppContainer = ui.draggable.parent();
	$(prevAppContainer).html("")
	$(prevAppContainer).addClass("droppable ui-droppable")
	$(prevAppContainer).removeClass("app-container-dragover bound").find("div.app, div.droppable, div.draggable").removeClass("bound");
	
	styleTheWorkspace(false);
	SaveWorkspace();

}

function RemoveFromWorkspace(){
	delmsgtxt = "You are about to remove this application from your Workspace:<br><br>Removing does <b>NOT</b> delete the application.<br><br>Are you sure?"
	var choice = window.top.Docova.Utils.messageBox({ 
		prompt: delmsgtxt, 
		icontype: 2, 
		title: "Remove Application from Workspace", 
		width:400, 
		msgboxtype: 4,
		onNo: function() {return},
		onYes: function() {
			var appcontainer = $(appObj).parent();
			var appkey = jQuery(appObj).attr("dockey");
			var apptype = jQuery(appObj).attr("apptype");
			$(appObj).remove();
			$(appcontainer).addClass("droppable ui-droppable");
			styleTheWorkspace(false);
			SaveWorkspace();
		}
	 });	
}

function OpenPanelProperties(){
	var dlgUrl = docInfo.ServerUrl + "/" + docInfo.NsfName + "/dlgWSPanelProperties?OpenForm"

	//-----Get the current panel name and assign to dlgParams so the properties dialog can pick it up.
	var currPanelNo = activePanelNo;
	var currPanelName = $(".panel[pid=" + currPanelNo +"]").attr('panelname');
	dlgParams.length = 0; //reset dlgParams array.
	dlgParams[0] = currPanelName; //Current panel name make availabe for properties dlg to get.
					
	var dlgPanelProperties = window.top.Docova.Utils.createDialog({
		id: "divDlgPanelProperties",
		url: dlgUrl,
		title: "Panel Properties",
		height: 250,
		width: 475,
		useiframe: true,
		sourcewindow: window,
		sourcedocument: document,
		buttons: {
			"Change": function (){
				var dlgDoc = window.top.$("#divDlgPanelPropertiesIFrame")[0].contentWindow.document
				
				var NewPanelName = $("#PanelName", dlgDoc).val();
				
				if(NewPanelName == ""){
					window.top.Docova.Utils.messageBox({
						title: "No Panel Name Provided",
						prompt: "You have not provided a Panel name. <br><br> Please provide a name for this Panel.",
						icontype: 1,
						msgboxtype: 0,
						width: 400
					});
					return;
				}
				//$("[panelno=" + currPanelNo +"]").find('.PanelName').text(NewPanelName)
				$(".panel[pid=" + currPanelNo +"]").attr('panelname', NewPanelName)
				$("#CurrentPanelName").text(NewPanelName);
				SaveWorkspace();
				dlgPanelProperties.closeDialog();
			},
			"Cancel": function(){
				dlgPanelProperties.closeDialog();
			}
		}
	})
}

function AboutWorkspace(){
	var dlgUrl=docInfo.ServerUrl + docInfo.PortalWebPath + "/dlgAboutWorkspace?OpenForm"
	var AboutWorkspaceDlg = window.top.Docova.Utils.createDialog({
		id: "divAboutWorkspace", 
		url: dlgUrl,
		title: "About Workspace",
		height: 550,
		width: 600, 
		useiframe: true,
		buttons: {
        		"Let's Go!": function() {
        			location.reload();
				AboutWorkspaceDlg.closeDialog();
        		}
      	},
      	onClose: function(){
			location.reload();
      	}
	})
}

function SetPanelNavIcons(){
	//hook up panel nav dots with panel names
	var panelNavIconHTML = "";
	$(".panel").each(function(){
		panelNavIconHTML += '<i class="fal fa-circle fa-2x panel-nav-icon" pid="' + $(this).attr("pid") + '" title="' + $(this).attr("panelname") + '"></i>'
	})
	$("#PanelNavigator").html(panelNavIconHTML);
	
	//set currently active panel
	$(".panel-nav-icon[pid=" + activePanelNo + "]").removeClass("fal").addClass("fas");
	$(".panel-nav-icon").hover(function(){
			if($(this).attr("pid") != activePanelNo){
				$(this).removeClass("fas").addClass("fal");
			}
		}, function(){
			if($(this).attr("pid") != activePanelNo){
				$(this).removeClass("fal").addClass("fas");
			}
	})
	$(".panel-nav-icon").on("click", function(){
		var gotoPanelNo = $(this).attr("pid");
		goToPanel(gotoPanelNo);
	})
}

function SetNavControls(){
	//Control-prev is shown if it is not the first panel
	if(activePanelNo == 1){
		$("#control-prev").css("display", "none");
	}else{
		$("#control-prev").css("display", "block");
	}
	
	//Control-next is shown if there is more than 1 panel and it is not the last panel
	if( ($(".panel").length > 1) & ($(".panel").length != activePanelNo) ){
  			$("#control-next").css("display", "block");
  	}else{
  		$("#control-next").css("display", "none");
  	}
  		
  		return;
}

function SaveWorkspace(){
	//Saves the users workspace when changes are made
	var agentName = "WorkspaceServices"
	
	var workspaceHTML = $("#divWorkspace").html();
	var request = "<Request><Action>SAVEWORKSPACE</Action><UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>"
	request += "<Document>"
	request += "<WorkspaceHTML><![CDATA[" + workspaceHTML + "]]></WorkspaceHTML>"
	request += "</Document>"
	request += "</Request>"
	
	var result = SubmitRequest(request, agentName);
	
	return;
}

function OpenHinkyMinkyMenu(event){ //hinkyminky
	var appType = $(appObj).attr("apptype")
	var appDocKey = $(appObj).attr("dockey")
	var disableopeninappbuilder = false;
	var disableCopy = false;
	var disableUpdateDesign = false;
	var disableDeleteApp = false;
	var disableManageLibraries = false;
	
	if(appType != "LG"){
		var appAccessLevel = CheckUserAppAccess(appDocKey)
		disableManageLibraries = true;
	}
	
	if(appAccessLevel == "NA" || appAccessLevel < 5){
		disableCopy = true;	
		disableUpdateDesign = true;
		disableDeleteApp = true;
	}
	
	if(appType != "A" || isipad){ //if not an app
		disableopeninappbuilder = true;
		disableCopy = true;
	}
	
	if(appType == "LG"){//if a library group
		disableUpdateDesign = true;
	}
	Docova.Utils.menu({
		menuid: "hinkymenu",
		delegate: event,		
		width: 250,
		position : 'XandY',
		shiftX : 10,
		shiftY : 5,		
		menus: [
				{ title: "Open", itemicon: "ui-icon-extlink", action: "OpenApp(appObj)" },
				{ title: "Open in App Builder", itemicon: "ui-icon-wrench", action: "OpenInAppBuilder(appObj)", disabled: disableopeninappbuilder },
				{ title: "Remove from Workspace", itemicon: "ui-icon-minus", action: "RemoveFromWorkspace(appObj)" },	
				{ separator: true },
				{ title: "Make Copy", itemicon: "ui-icon-copy", action: "CopyApp(appObj)", disabled: disableCopy },
				{ title: "Manage Libraries", itemicon: "ui-icon-copy", action: "ManageLibraries(appObj)", disabled: disableManageLibraries },				
				{ title: "Update Design", itemicon: "ui-icon-refresh", action: "UpdateAppDesign(appObj)", disabled: disableUpdateDesign },
				{ title: "Delete Application", itemicon: "ui-icon-circle-close", action: "DeleteApp(appObj)", disabled: disableDeleteApp }	,
				{ separator: true },
				{ title: "Properties", itemicon: "ui-icon-gear", action: "OpenAppProperties(appObj)" }	
		]
	})
}