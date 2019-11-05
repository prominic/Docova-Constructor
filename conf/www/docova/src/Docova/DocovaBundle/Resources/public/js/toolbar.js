function objCustomActionBar(initNow, objVar)
{

/*----------------------------------------------------------------------------------------------
Formats the Domino action bar HTML into a button format that jQuery can render. The buttons are dynamically styled with jQuery in a form's $(document).ready function where 
button primary icon, secondary icon and label are set using the attribute settings created in this function.
Domino action buttons in forms or subforms can have label text that specifies the primary icon, button label and secondary icon for jquery buttons in a format where they are split by a semi-colon
Developers can chose to show any combination of primary icon, text and secondary icon by delimiting them with a semi-colon.
------------------------------------------------------------------------------------------------*/
this.Render = function(targetElement)
{
	if(!this.isInitialized) {return;}

	if(targetElement != null && targetElement != "")
	{
		var btnBarContainer = document.getElementById(targetElement);
		if(btnBarContainer != null)
		{
			btnBarContainer.innerHTML = this.btnBarHTML;
			btnBarContainer.style.display = "";
		}else{
			return false;
		}
		if(jQuery != undefined){
			jQuery("#" + targetElement + " a").each(function(index,element){
				if ( $(this).attr('btnLabel') == "" )
					$(this).css("height", "2.2em");
		       		jQuery(element).button({
            					text: ($(this).attr('btnLabel') == "") ? false : true,
            					label: $(this).attr('btnLabel'),
           	 				icons: {
	               		 			primary: ($(this).attr('btnPrimary') == "") ? null : $(this).attr('btnPrimary'),
                					secondary: ($(this).attr('btnSecondary') == "") ? null : $(this).attr('btnSecondary')
            					}
        				})
   	 		});
		}
	}else{
		this.dominoActionBar.insertAdjacentHTML("beforeBegin", this.btnBarHTML);
	}
}
/*----------------------------------------------------------------------------------------------
Locates Domino action bar on the form and initializes base object variables
------------------------------------------------------------------------------------------------*/
this.Initialize = function(toolbarObj)
{
	if(this.isInitialized){return true;} //only one successful call to this function is allowed

	if ( toolbarObj ) 
		this.dominoActionBar=toolbarObj;
	else
		this.dominoActionBar = this.doc.getElementsByTagName('table')[0]; //default Domino action bar should be the first table

	if(this.dominoActionBar == null)
		{
		isInitialized = false; //doc has no tables, do not continue
		return false;
		}
		
	if(this.dominoActionBar.rows.length != 1)
		{
		isInitialized = false; //action bar table should have only one row, do not continue
		return false;
		}
		
	var dominoActionBarHR = this.doc.getElementsByTagName("hr")[0]; //HR under the action bar
	if(dominoActionBarHR != null)
		{
		dominoActionBarHR.style.display="none"; //hide the default HR under action bar
		}

	this.dominoActionBar.style.display = "none"; //hide the domino action bar

	//For each 'a' tag in each of the table cells of the Domino generated action bar, set the attributes for label, primary and secondary icons for
	//button labels and any font awesome icons that might have been employed.  Format for using fontawesome icons in an action button is to
	//primaryicon;label text;secondaryicon which is placed in the action button text label. Delimiter is semicolon.
	//The onready js of the form/document will format the 'a' tags as buttons using the attributes to set the primary icon, label and secondary icon.
	var actionBarCells = this.dominoActionBar.rows[0].cells;
	var i;
	var actionCell = null;
	var actionTag = null;
	var actionTextList = "";
	var actionTextArray;
	var actionText = "";
	var iconPrimary = "";
	var iconSecondary = "";

	for(i=0; i<actionBarCells.length; i++){
		actionCell = actionBarCells[i]
		actionTag = actionCell.getElementsByTagName('a')[0]
		if(actionTag){
			actionTextList = actionTag.innerHTML.replace(/&amp;/g, '&');
			actionTextArray = actionTextList.split(";")
			if(actionTextArray.length == 0){
				iconPrimary = "";
				actionText = "";
				iconSecondary = "";
			}
			if(actionTextArray.length == 1){
				iconPrimary = "";
				actionText = actionTextArray[0];
				iconSecondary = "";
			}
			if(actionTextArray.length == 2){
				iconPrimary = actionTextArray[0];
				actionText = actionTextArray[1];
				iconSecondary = "";
			}
			if(actionTextArray.length == 3){
				iconPrimary = actionTextArray[0];
				actionText = actionTextArray[1];
				iconSecondary = actionTextArray[2];
			}
			actionTag.setAttribute("btnLabel", actionText);
			actionTag.setAttribute("btnPrimary", iconPrimary);
			actionTag.setAttribute("btnSecondary", iconSecondary);
		}
		//alert("cell inner: " + actionCell.innerHTML)
		this.btnBarHTML += actionCell.innerHTML
	}
	
	this.isInitialized = true;
	return true;
}

/*----------------------------------------------------------------------------------------------
Properties
------------------------------------------------------------------------------------------------*/
	this.isInitialized = false; //true when object is attached to Domino action bar
	this.doc = document;
	this.varName = "aBar"; //name of the global variable referencing current object
	this.dominoActionBar = null; //object reference to the original domino action bar
	this.btnBarHTML = "";

} //end objCustomActionBar
 