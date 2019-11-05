var oPopup = window.createPopup(); // global popup object as suggested by
									// Microsoft
function objPopupAction(isActive, isChecked, isBold, actionText, actionName,
		actionIconSrc, actionShortcutKeyText, actionHandler) {
	this.type = "action";
	if (isActive) {
		this.isActive = isActive;
	} else {
		this.isActive = false;
	}
	if (isChecked) {
		this.isChecked = isChecked;
	} else {
		this.isChecked = false;
	}
	if (isBold) {
		this.isBold = isBold;
	} else {
		this.isBold = false;
	}
	if (actionText) {
		this.actionText = actionText;
	} else {
		this.actionText = "";
	}
	if (actionName) {
		this.actionName = actionName;
	} else {
		this.actionName = "";
	}
	if (actionIconSrc) {
		this.actionIconSrc = actionIconSrc;
	} else {
		this.actionIconSrc = "";
	}
	if (actionShortcutKeyText) {
		this.actionShortcutKeyText = actionShortcutKeyText;
	} else {
		this.actionShortcutKeyText = "";
	}
	if (actionHandler) {
		this.actionHandler = actionHandler;
	} else {
		this.actionHandler = "";
	}
}
function objPopupDivider() {
	this.type = "divider";
}
function objPopupmenu() {
	// the four values below are set by the calling function and used to
	// construct the box to dispaly popup html
	this.height = 0;
	this.width = 0;
	this.offsetTop = 10;
	this.offsetRight = 10;
	// the values below control the popup HTML
	this.iconColumnWidth = 25;
	this.shortcutKeyColumnWidth = 20;
	this.textColumnWidth = 20;
	this.toggleIconColumnWidth = 12;
	this.actionHeight = 20; // height of the action bar
	this.toggleIconHTML = "";
	this.useStyleSheet = false;
	this.actionCount = 0;
	this.dividerCount = 0;
	this.closeActionHandler = resetMenu;
	this.parentObject = null; // whatever is this popup attached to
	this.hasShortcutKeys = false; // determines if the actions have a separate
									// space for the shortcut key info
	this.hasActionIcons = false; // determines if the actions have a separate
									// space for the action icon
	this.hasToggleIcons = false; // determines if the actions have a separate
									// space for the action toggle icon
	this.actionTargetBase = "parent."; // string identifying the location of
										// the default action handler
	this.defaultActionHandler = "ppActionHandler(this)"; // name of the
															// function that
															// knows what to do
															// with clicked
															// action
	this.actionSetHTML = "";
	this.popupObj = null;
	this.useMsPopup = true;
	this.actionSet = new Array();
	this.actionSetIdx = 0;
	this.isOpen = false;
	this.show = function() {}
	this.hide = function() {}
	this.addAction = function(isActive, isChecked, isBold, actionText,
			actionName, actionIconSrc, actionShortcutKeyText, actionHandler) {
		this.actionSet[this.actionSetIdx++] = new objPopupAction(isActive,
				isChecked, isBold, actionText, actionName, actionIconSrc,
				actionShortcutKeyText, actionHandler)
		this.actionCount++
	}
	this.renderAction = function(actionObj) {
		// default styles
		var actionTableStyle = "";
		var actionIconCellStyle = "style=\"white-space: nowrap; padding:2px; width: "
				+ this.iconColumnWidth + "px; \"";
		var actionToggleCellStyle = "style=\"white-space: nowrap; padding:2px; width: "
				+ this.toggleIconColumnWidth + "px; \"";
		var actionTextCellStyle = "style=\"white-space: nowrap; padding:2px; width: "
				+ this.textColumnWidth + "px; \""
		var actionTextInactiveStyle = "style=\"position:relative; top:0; left:0; color:GrayText;\"";
		var actionTextInactiveShadowStyle = "style=\"position:absolute; top:+1; left:+1; color:ThreeDHighlight; z-index:-1;\"";
		var actionShortcutCellStyle = "style=\"white-space: nowrap; padding:2px; width: "
				+ this.shortcutKeyColumnWidth + "px; \""
		var actionRowStyleNormal = "style=\"height:"
				+ this.actionHeight
				+ "px; color:MenuText; cursor:hand; font: normal 11px verdana, arial,sans-serif; \"";
		var actionRowStyleHighlight = "cssText = 'background: Highlight; color: HighlightText;'";

		if (this.useStyleSheet) // custom styles
		{
			actionTableStyle = "class=\"ppActionTable\"";
			actionIconCellStyle = "class=\"ppActionIconCell\"";
			actionToggleCellStyle = "class=\"ppActionToggleCell\"";
			actionTextCellStyle = "class=\"ppActionTextCell\"";
			actionTextInactiveStyle = "class=\"ppActionTextInactive\"";
			actionTextInactiveShadowStyle = "class=\"ppActionTextInactiveShadow\"";
			actionShortcutCellStyle = "class=\"ppActionShortcutCell\"";
			actionRowStyleNormal = "class=\"ppActionRowNormal\"";
			actionRowStyleHighlight = "className = 'ppActionRowHighlight;'";
		}
		if (actionObj.actionHandler == "") {
			actionObj.actionHandler = this.actionTargetBase
					+ this.defaultActionHandler;
		}
		var actionIconHTML = "";
		if (actionObj.actionIconSrc != "") {
			if (actionObj.isActive) {
				actionIconHTML = "<img align=\"right\" src=\""
						+ actionObj.actionIconSrc + "\"/>";
			} else {
				actionIconHTML = "<img style=\"filter:alpha( opacity=60 );\" align=\"right\" src=\""
						+ actionObj.actionIconSrc + "\"/>";
			}
		} else {
			actionIconHTML = "<span style=\"color: Window;visibility: hidden;\">.</span>";
		}
		var actionToggleIconHTML = "";
		if (actionObj.isChecked) {
			if (actionObj.isActive) {
				actionToggleIconHTML = this.toggleIconHTML;
			} else {
				actionToggleIconHTML = "<span style=\"filter:alpha( opacity=60 );\">"
						+ this.toggleIconHTML + "</span>";
			}
		} else {
			actionToggleIconHTML = "<span style=\"color: Window;visibility: hidden;\">.</span>"; // just
																									// to
																									// fill
																									// the
																									// table
																									// cell
																									// with
																									// something
		}

		if (actionObj.isBold) {
			actionObj.actionText = "<span style=\"font-weight: bold;\">"
					+ actionObj.actionText + "</span>";
			actionObj.actionShortcutKeyText = "<span style=\"font-weight: bold;\">"
					+ actionObj.actionShortcutKeyText + "</span>";
		}

		// var actionHTML = "<table width=\"100%\" cellpadding=\"0\"
		// cellspacing=\"0\" " + actionTableStyle + ">";
		// actionHTML += "<tr actionName=\"" + actionObj.actionName + "-" +
		// this.actionCount + "\" " + actionRowStyleNormal ;
		actionHTML = "<tr actionName=\"" + actionObj.actionName + "-"
				+ this.actionCount + "\" " + actionRowStyleNormal;

		if (actionObj.isActive) // click action applies only to active actions
		{
			actionHTML += " onmouseover=\"this.runtimeStyle."
					+ actionRowStyleHighlight + ";\"";
			actionHTML += " onmouseout=\"this.runtimeStyle.cssText=''\"";
			actionHTML += " onselectstart=\"window.event.returnValue = false;\""
			actionHTML += " onclick=\"" + actionObj.actionHandler + ";\"";
		} else {
			actionHTML += " onmouseover=\"this.runtimeStyle."
					+ actionRowStyleHighlight
					+ ";this.all.ppInactiveShadow.runtimeStyle.display='none';\"";
			actionHTML += " onmouseout=\"this.runtimeStyle.cssText=''; this.all.ppInactiveShadow.runtimeStyle.display=''\""; // not
																																// using
																																// style
																																// sheet
			actionHTML += " onselectstart=\"window.event.returnValue = false;\""
		}
		actionHTML += ">" // end of action table row opening tag
		// action icon cell if used add it
		if (this.hasActionIcons) {
			actionHTML += "<td align=\"left\"><div " + actionIconCellStyle
					+ ">" + actionIconHTML + "</div></td>";
		}

		// toggle checkmark cell
		if (this.hasToggleIcons) {
			actionHTML += "<td align=\"right\"><div " + actionToggleCellStyle
					+ ">" + actionToggleIconHTML + "</div></td>";
		}

		if (actionObj.isActive) {
			actionHTML += "<td align=\"left\"><div " + actionTextCellStyle
					+ ">" + actionObj.actionText + "</div></td>"; // standard
																	// text
		} else { // shadowed text
			actionHTML += "<td align=\"left\"><div " + actionTextCellStyle
					+ ">";
			actionHTML += "<div " + actionTextInactiveStyle + ">"
					+ actionObj.actionText;
			actionHTML += "<div  id=\"ppInactiveShadow\" "
					+ actionTextInactiveShadowStyle + ">";
			actionHTML += actionObj.actionText + "</div></div>";
			actionHTML += "</div></td>";
		}
		if (this.hasShortcutKeys) {
			actionHTML += "<td align=\"left\"><div " + actionShortcutCellStyle
					+ ">" + actionObj.actionShortcutKeyText
					+ "</div></td></tr>";
		}

		// actionHTML += "</tr></table>"
		actionHTML += "</tr>"
		// this.actionSetHTML += actionHTML;
		return actionHTML;
	}
	this.addDivider = function() {
		this.actionSet[this.actionSetIdx++] = new objPopupDivider();
		this.dividerCount++;
	}
	this.renderDivider = function() {
		var colspan = 1;
		if (this.hasActionIcons) {
			colspan++;
		}
		if (this.hasToggleIcons) {
			colspan++;
		}
		if (this.hasShortcutKeys) {
			colspan++;
		}
		var dividerStyle = "";
		var dividerStyle = "style=\"margin-left:1px; margin-right:1px;margin-top:2px; margin-bottom:0px; width: 100%; white-space: nowrap;";
		dividerStyle += "line-height: 0px; border-top:1px solid ThreeDShadow; border-bottom: 1px solid ThreeDHighlight\"";

		if (this.useStyleSheet) {
			dividerStyle = "class=\"ppActionDivider\"";
		}
		var dividerHTML = "<tr><td colspan=\"" + colspan + "\"><span "
				+ dividerStyle + "></span></td></tr>";
		// this.actionSetHTML += dividerHTML;
		return dividerHTML;
	}
	this.innerHTML = function() {
		var containerOuterStyle = "style=\"position:relative; top:0; left:0; border-top:1px solid ThreeDShadow; border-left:1px solid ThreeDShadow; ";
		containerOuterStyle += " border-right: 1px solid ThreeDDarkShadow; border-bottom: 1px solid ThreeDDarkShadow; background:Menu;\"";

		var containerInnerStyle = "style=\"position:relative; top:0; left:0; border-top:1px solid ThreeDHighlight; border-left:1px solid ThreeDHighlight; ";
		containerInnerStyle += "border-right: 1px solid ThreeDShadow; border-bottom: 1px solid ThreeDShadow; background:Menu; height:100%; width:100%; padding: 1px;\"";
		if (this.useStyleSheet) {
			containerOuterStyle = "class=\"ppOuterContainer\"";
			containerOuterStyle = "class=\"ppInnerContainer\"";
		}

		var containerHTML = "<div " + containerOuterStyle + "><div "
				+ containerInnerStyle + ">";
		containerHTML += "<table cellpadding=\"0\" cellspacing=\"0\" >";
		var k = 0;
		for (k = 0; k < this.actionSet.length; k++) {
			if (this.actionSet[k].type == "action") {
				containerHTML += this.renderAction(this.actionSet[k]);
			} else {
				containerHTML += this.renderDivider();
			}
		}
		containerHTML += "</table></div></div>";
		return containerHTML;
	}
	this.showPopup = function(parentObj) {
		var popuptable = document.getElementById("ppContainer")
		if (!popuptable) {
			popuptable = window.document.createElement("TABLE")
			var tb = document.createElement('tbody');
			popuptable.style.visibility = "hidden";
			popuptable.style.position = "absolute";
			popuptable.style.border = 0;
			popuptable.style.cellPadding = 0;
			popuptable.style.cellSpacing = 0;
			if (!this.useMsPopup) {
				popuptable.style.filter = "progid:DXImageTransform.Microsoft.Shadow(color='#888888', Direction=135, Strength=2)";
			}
			popuptable.id = "ppContainer";
			document.body.appendChild(popuptable);
			popuptable.appendChild(tb);
			var popuprow = window.document.createElement("TR")
			tb.appendChild(popuprow);
			var popupcell = window.document.createElement("TD")
			popupcell.id = "ppContainerCell";
			popupcell = popuprow.appendChild(popupcell);
			popuptable.onclick = this.hidePopup;
		}
		if (!popupcell) {
			popupcell = document.getElementById("ppContainerCell");
		}
		popuptable.style.visibility = "hidden";
		popupcell.innerHTML = this.innerHTML();
		if (this.useMsPopup) {
			var oPopBody = oPopup.document.body;
			oPopBody.innerHTML = popuptable.innerHTML;
			oPopBody.onunload = this.closeActionHandler;
			this.width = (this.width > 0) ? this.width : popuptable.clientWidth; // Nov18,2009
			this.height = (this.height > 0) ? this.height
					: popuptable.clientHeight - 5; // Nov 18,2009
			this.parentObject = (parentObj) ? parentObj : this.parentObject;
			oPopBody.parentObject = this.parentObject; // custom property to
														// pass the reference to
														// cleanup function
			oPopup.show(this.offsetRight, this.offsetTop, this.width,
					this.height, parentObj);
			this.isOpen = oPopup.isOpen;
			return false;
		} else {
			popuptable.style.top = parentObj.offsetTop + parentObj.offsetHeight
					+ this.offsetTop;
			popuptable.style.left = parentObj.offsetLeft + this.offsetRight;
			popuptable.style.visibility = "visible";
			this.isOpen = true;
		}
	}
	this.hidePopup = function() {
		if (this.useMsPopup) {
			if (oPopup) {
				if (oPopup.isOpen) {
					oPopup.hide();
				} // close popup object
				this.isOpen = oPopup.isOpen;
			}
		} else {
			var popuptable = document.getElementById("ppContainer")
			if (popuptable) {
				popuptable.style.visibility = "hidden";
			}
			this.isOpen = false;
		}
	}
}
