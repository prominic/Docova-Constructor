var params= window.dialogArguments;

var dlgDoc = null;

document.oncontextmenu=stopContextMenu;

// For convenience

function stopContextMenu()
{
return false;
}

function completeWizard()
{
	var returnArray = new Array();
	var version = "";
	var comment="";
	var isFirstRelease = params[2];
	if(isFirstRelease){
		//first release, let user type the version number
		if(!isNumeric(dlgDoc.MajorVersion.value) || !isNumeric(dlgDoc.MinorVersion.value) || !isNumeric(dlgDoc.Revision.value))
		{
			thingFactory.MessageBox(prmptMessages.msgWFR001, 16, prmptMessages.msgCF018 );
			dlgDoc.MajorVersion.focus();
			return false;
		}
		version = dlgDoc.MajorVersion.value + "." + dlgDoc.MinorVersion.value  + "." + dlgDoc.Revision.value
	}
	else
	{
		version = thingFactory.GetHTMLItemValue("VersionSelect");
		if(version =="")
		{
			thingFactory.MessageBox(prmptMessages.msgWFR002, 16, prmptMessages.msgCF018 );
			return false;
		}
	}
	returnArray[0] = version;
	
	comment = thingFactory.AllTrim(dlgDoc.Comment.value);
	
	if(!isFirstRelease && !comment) //comment is required for auto incremented versions
		{
		thingFactory.MessageBox(prmptMessages.msgWFR003, 16, prmptMessages.msgCF018);
		dlgDoc.Comment.focus();
		return false;
		}
	returnArray[1] = comment;
	window.returnValue=returnArray;
	window.close()
}

function cancelWizard()
{
	window.returnValue=false;
	window.close()
	
}



// -------------------------- OnLoad initialization ----------------------------------------------
function initDialog()
{
	//Global variables for convenience
	dlgDoc = document.all;
	InitVars(info);

	var availVersionListArray = params[0].split(",");
	var availCount = availVersionListArray.length;
	var curVerArray = availVersionListArray[0].split(".");
	var strictversioning = params[3];
	
	if(strictversioning){
		initStrictOptions(dlgDoc, availVersionListArray, availCount, curVerArray);
	}else{
		initRegularOptions(dlgDoc, availVersionListArray, availCount, curVerArray);
	}
	

}

function initRegularOptions(dlgDoc, availVersionListArray, availCount, curVerArray){
	dlgDoc.VersionSelect[0].value = availVersionListArray[0];
	dlgDoc.VersionSelect[0].checked=(availCount ==1) || (params[1] == availVersionListArray[0]);
	dlgDoc.VersionSelectText[0].innerText = availVersionListArray[0];
	//----------------------------

	dlgDoc.MajorVersion.value = curVerArray[0];
	dlgDoc.MinorVersion.value = curVerArray[1];
	//dlgDoc.MinorVersion.value = curVerArray[2];  === error? should be revision?
	dlgDoc.Revision.value = curVerArray[2];
	//----------------------------
	if(availCount > 1){
		dlgDoc.VersionSelect[1].style.display=""
		dlgDoc.VersionSelectText[1].style.display=""
		dlgDoc.VersionSelect[1].value = availVersionListArray[1];	
		dlgDoc.VersionSelect[1].checked=(params[1] == availVersionListArray[1]);
		dlgDoc.VersionSelectText[1].innerText = availVersionListArray[1];
		}
	if(availCount > 2){
		dlgDoc.VersionSelect[2].style.display=""
		dlgDoc.VersionSelectText[2].style.display=""
		dlgDoc.VersionSelect[2].value = availVersionListArray[2];		
		dlgDoc.VersionSelect[1].checked=(params[1] == availVersionListArray[2]);
		dlgDoc.VersionSelectText[2].innerText = availVersionListArray[2];
		}
	// check if it is an initial version
	(params[2])? dlgDoc.VersionEditOption.style.display="" : dlgDoc.VersionSelectOption.style.display=""

	dlgDoc.Comment.focus();
}

function initStrictOptions(dlgDoc, availVersionListArray, availCount, curVerArray){
	dlgDoc.VersionSelect[0].value = availVersionListArray[0];
	dlgDoc.VersionSelect[0].checked=true;
	dlgDoc.VersionSelectText[0].innerText = availVersionListArray[0];
	//----------------------------
	dlgDoc.MajorVersion.value = curVerArray[0];
	dlgDoc.MajorVersion.disabled = "disabled";
	//dlgDoc.MinorVersion.value = curVerArray[1];
	dlgDoc.MinorVersion.value = 0;
	dlgDoc.MinorVersion.disabled = "disabled";
	//dlgDoc.MinorVersion.value = curVerArray[2];
	dlgDoc.Revision.value = 0
	dlgDoc.Revision.disabled = "disabled";
	//----------------------------
	if(availCount > 1){
		dlgDoc.VersionSelect[1].style.display=""
		dlgDoc.VersionSelectText[1].style.display=""
		dlgDoc.VersionSelect[1].value = availVersionListArray[1];	
		//dlgDoc.VersionSelect[1].checked=(params[1] == availVersionListArray[1]);
		dlgDoc.VersionSelectText[1].innerText = availVersionListArray[1];
		dlgDoc.VersionSelect[1].disabled="disabled";
		}
	if(availCount > 2){
		dlgDoc.VersionSelect[2].style.display=""
		dlgDoc.VersionSelectText[2].style.display=""
		dlgDoc.VersionSelect[2].value = availVersionListArray[2];		
		//dlgDoc.VersionSelect[1].checked=(params[1] == availVersionListArray[2]);
		dlgDoc.VersionSelectText[2].innerText = availVersionListArray[2];
		dlgDoc.VersionSelect[2].disabled="disabled";
		}
	// check if it is an initial version
	(params[2])? dlgDoc.VersionEditOption.style.display="" : dlgDoc.VersionSelectOption.style.display=""

	dlgDoc.Comment.focus();
}