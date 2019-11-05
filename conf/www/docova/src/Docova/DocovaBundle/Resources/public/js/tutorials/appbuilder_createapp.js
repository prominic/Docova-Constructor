/**
 * DOCOVA App Builder Application Creation Tutorial
 */


	//Initialize a new instance of the tutorial engine
	var tutorial = new TutorialEngine({});

	//Initialize the tutorial steps
	var tutorial_steps = [
		{
			'target' : 'next #fraWorkspace'			
		},		
		{
			'target' : 'next #create_app,#btn-WSOptions',
			'variables' : {
				'wstype' : function(tobj, targetelem){return (jQuery(targetelem).attr("id") == "create_app" ? "D" : "B");}
			}			
		},
		{
			'skipif' : function(tobj){return (tobj.getVariable("wstype") == "D");},			
			'prompt' : 'Click the "Workspace Options" button to start creating your application',
			'target' : 'click #btn-WSOptions'
		},
		{
			'skipif' : function(tobj){ return (tobj.getVariable("wstype") == "D");},			
			'prompt' : 'Select Create Application option',
			'target' : 'click #docova_menu :nth-child(2)'
		},
		{
			'skipif' : function(tobj){ return (tobj.getVariable("wstype") == "B");},
			'prompt' : 'Click the "Create Application" button to start creating your application {%wstype%}',
			'target' : 'click #create_app'
		},		
		{
			'prompt' : 'Enter a title for your application',
			'target' : 'change #Title'
		},
		{
			'prompt' : 'Enter a description for your application',
			'target' : 'change #Description'
		},  		  	  		
		{
			'prompt' : 'Choose an icon for your application',
			'target' : 'click [id=iconpicker],[id=IconSearch]',
			'variables' : {
				'app_title' : function(tobj, targetelem){return (jQuery(targetelem).closest('body').find('input[id=Title]').val() || '');}
			}			
		}, 	
		{
			'prompt' : 'Select Create to create your new application',
			'target' : 'click span.ui-button-text:contains(Create)'
		},
		{
			'prompt' : 'Right click on the application titled {%app_title%}',
			'target' : 'rightclick div.app[apptitle="{%app_title%}"]',
			'variables' : {
				'app_id' : function(tobj, targetelem){return (jQuery(targetelem).attr('dockey') || '');}
			}			
		},
		{
			'prompt' : 'Select Open in App Builder option',
			'target' : 'click #hinkymenu li:nth-child(2)'
		},
		{
			'prompt' : 'Open the Forms listing in the {%app_title%} application',
			'target' : 'click div.list-group[dockey={%app_id%}]>a[id=appForms]'
		},	
		{
			'prompt' : 'Create a new form by selecting the New Form button',
			'target' : 'click #divActionPane>button[title="New Form"]'
		},
		{
			'prompt' : 'Give your new form a name',
			'target' : 'change input[id=DEName]'
		},		
		{
			'prompt' : 'Click on the form and type a field label',
			'target' : 'click div[id=section_1]>p:first'
		},
		{
			'prompt' : 'Select the design elements tab',
			'target' : 'click li[title=Elements]>'
		},		
		{
			'prompt' : 'Drag a text input field onto your form',
			'target' : 'click a.newElement[elem=text]'
		},
		{
			'prompt' : 'Give your new field a name',
			'target' : 'change input[id=element_id]'
		},
		{
			'prompt' : 'Click on the Save and Close button to save your new form',
			'target' : 'click #tdActionBar li:nth-child(3)',
			'variables' : {
				'form_name' : function(tobj, targetelem){return (jQuery(targetelem).closest('body').find('input[id=DEName]').val() || '');},
				'field_name' : function(tobj, targetelem){return (jQuery(targetelem).closest('body').find('div[id=section_1]>input[elem=text][elemtype=field]').attr('name') || '');}				
			}					
		},
		{
			'prompt' : 'Open the Views listing',
			'target' : 'click div.list-group[dockey={%app_id%}]>a[id=appViews]'
		},
		{
			'prompt' : 'Create a new view using the New View button',
			'target' : 'click #divActionPane>button[title="New View"]'
		},			
		{
			'prompt' : 'Give your new view a name',
			'target' : 'change #ViewName',
			'variables' : {
				'view_name' : function(tobj, targetelem){return (jQuery(targetelem).closest('body').find('input[id=ViewName]').val() || '');}
			}
		},		
		{
			'prompt' : 'Modify the selection formula to replace "Enter Form Name" with the new form name "{%form_name%}"',
			'target' : 'change #ViewSelectionFormula'
		},
		{
			'prompt' : 'Add a new column to the view by clicking on the Insert Column button',
			'target' : 'click #btnAddColumn'
		},
		{
			'prompt' : 'Click on the column heading',
			'target' : 'click td[colidx="0"]:contains("Enter Title")'
		},
		{
			'prompt' : 'Enter a heading for the column',
			'target' : 'change #ColumnTitle'
		},		
		{
			'prompt' : 'Enter the name of the field to display in the column (eg. {%field_name%})',
			'target' : 'change #ColumnFormula'
		},
		{
			'prompt' : 'Add a new action button by clicking on the Add Action button',
			'target' : 'click #btnAddAction'
		},		
		{
			'prompt' : 'Select the new action button',
			'target' : 'click button[title="Action"]'
		},	
		{
			'prompt' : 'Change the action button type to "Create New Document"',
			'target' : 'change #ActionType'
		},		
		{
			'prompt' : 'Choose {%form_name%} in the Form field',
			'target' : 'change #ActionDocType'
		},				
		{
			'prompt' : 'Select Save & Close to save the new view',
			'target' : 'click #tdActionBar a:nth-child(1)',
		},
		{
			'prompt' : 'Open the Menus listing',
			'target' : 'click div.list-group[dockey={%app_id%}]>a[id=appMenus]'
		},
		{
			'prompt' : 'Create a new Menu using the New Menu button',
			'target' : 'click #divActionPane>button[title="New Menu"]'
		},			
		{
			'prompt' : 'Give your new menu a name',
			'target' : 'change #DEName',
			'variables' : {
				'menu_name' : function(tobj, targetelem){return (jQuery(targetelem).closest('body').find('input[id=DEName]').val() || '');}
			}
		},	
		{
			'prompt' : 'Click on the menu item',
			'target' : 'click #divOutlineBuilder>ul:first>li:first'
		},
		{
			'prompt' : 'Give the menu item a new title',
			'target' : 'change #EntryLabel'
		},
		{
			'prompt' : 'Change the entry type to View',
			'target' : 'change #EntryType'
		},
		{
			'prompt' : 'Select the view named {%view_name%} from the list',
			'target' : 'change #ElementList'
		},
		{
			'prompt' : 'Select Save & Close to save the new menu',
			'target' : 'click #tdActionBar a:nth-child(2)',
		},
		{
			'prompt' : 'Open the Layouts listing',
			'target' : 'click div.list-group[dockey={%app_id%}]>a[id=appLayouts]'
		},
		{
			'prompt' : 'Create a new Layout using the New Layout button',
			'target' : 'click #divActionPane>button[title="New Layout"]'
		},
		{
			'prompt' : 'Give your new layout a name',
			'target' : 'change #DEName'
		}		
	];

	//set script config
	tutorial.setSteps(tutorial_steps);

	//run tuturial
	//tutorial.run();
