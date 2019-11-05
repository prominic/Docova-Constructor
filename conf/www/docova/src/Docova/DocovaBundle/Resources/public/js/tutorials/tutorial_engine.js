/**
 * Tutorial Engine
 * Generate prompting messages to provide step by step tutorials on program usage
 * 
 * Written by: DLI.tools Inc. http://www.docova.com
 * 
 * Dependencies: jquery
 * 
 * Usage:  1. include this script on a web page
 *         2. initialize an instance of the tutorial engine
 *            var tutorial = new TutorialEngine({});
 *         3. create the tutorial steps in the following format
 *         	  var tutorial_steps = [
 * 	            {'prompt' : 'promptmessage',
 *               'target' : 'eventname targetselector',
 *               'variables' : {'variablename' : functionorvalue, .. ,'variablename' : functionorvalue},
 *               'skipif' : function
 *              },
 *              ..
 *              {'prompt' : "promptmessage',
 *               'target' : 'eventname targetselector,
 *               'variables' : {'variablename' : functionorvalue, .. ,'variablename' : functionorvalue},
 *               'skipif' : function
 *              }
 *            ];
 *          
 *            Step Properties:
 *               prompt - (optional) - prompt message to display to user when step becomes active
 *                      - promptmessage - string - message to be displayed to the user, can include variables in the form {%variablename%}
 *               target - (optional) - html element that should be targetted with an event or action before moving to next step
 *                      - eventname - string - event that will complete the step 
 *                        (click|rightclick|change|mousedown|mouseup|mouseover|mouseout|focus|blur|next) 
 *                      - targetselector - jquery selector that identifies the target element (eg. button, input, span etc) that the step completion event is tied to
                          can make use of variables in the form {%variablename%}
 *               variables - (optional) - object - list of variables to be stored as part of the step
 *                      - variablename - string - name of a variable that should be set on the completion of the event (used to store values from one step to another)
 *                      - functionorvalue - function|mixed - anonymous function to run to return a value to store, or a fixed value to store in the variable
 *               skipif - (optional) - function - function to run and return true to skip the current step, or false to continue with the current step     
 *
 *         	4. set script config
 *             tutorial.setSteps(tutorial_steps);
 *             
 *          5. run the tutorial
 *             tutorial.run();
 *       
 */

var TutorialEngine = function(_custoptions){
	
	var _self = this;
	
	var _defoptions = {
		'prompt' : {
			'width' : 400,
			'height' : 50,
			'backgroundcolor' : 'white',
			'borderwidth' : 1,
			'bordercolor' : 'grey',
			'textcolor' : 'black',
			'fontfamily' : 'Arial',
			'fontsize' : 15
		},
		'timeout' : 300
	};
    var _options = $.extend(_defoptions, _custoptions);
	
	var _stepdata = [];
	var _curstep = 0;
	var _tutorialid = '';
	var _promptelem = null;
	var _retrycount = 0;
	var _variables = {};
	
	
	/** PUBLIC properties **/
	Object.defineProperty(this, 'tutorialid', {
		get: function () {
			return _tutorialid;
		},
		enumerable: true
	});	
	
	Object.defineProperty(this, 'currentstepno', {
		get: function () {
			return _curstep;
		},
		set: function (newval) {
			_curstep = newval;
		},
		enumerable: true
	});	
	
	/** PUBLIC methods **/
  
	/**
	 * setSteps
	 * Assign tutorial steps to engine
	 * Inputs: stepdata - json array - consisting of the following components;
	 */
	_self.setSteps = function(stepdata){
		_stepdata = stepdata;
		_curstep = 0;
	};
	
	/**
	 * run
	 * Starts the tutorial
	 */	
	_self.run = function(){
		_curstep = 0;
		_runStep();
	};
	
	/**
	 * getVariable
	 * Retrieves a variable
	 */	
	_self.getVariable = function(variablename){
		var result = null;
		
		if(_variables && typeof _variables[variablename] !== 'undefined'){
			result = _variables[variablename];		
		}
		
		return result;
	};	
	
	
	/** PRIVATE methods **/
		
	var _runStep = function(){
		var targetelem = null;
		
		if(typeof _stepdata !== "undefined" && Array.isArray(_stepdata) && _stepdata.length > _curstep){
			var stepdata = _stepdata[_curstep];
			var targetevent = '';
			var targetselector = '';
			
			if(typeof stepdata.skipif == 'function'){
				if(stepdata.skipif(_self) === true){
					_nextStep();
					return;					
				}
			}
			
			if(stepdata.target !== '' && stepdata.target !== null){
				var pos = stepdata.target.indexOf(" ");
				if(pos > -1){
					targetevent = stepdata.target.slice(0, pos).trim();
					targetselector = stepdata.target.slice(pos).trim();
					var temptargetselector = _replaceVariables(targetselector);				
					targetelem = _getElem(temptargetselector, jQuery(window.top.document));
				}
			}
			
			if(targetelem && targetelem.length > 0){
				stepdata.activeelement = targetelem;
				if(targetevent == "click" || targetevent == "change" || targetevent == "mousedown" || targetevent == "mouseup" || targetevent == "mouseover" || targetevent == "mouseout" || targetevent == "focus" || targetevent == "blur"){
					jQuery(targetelem).on(targetevent, null, targetevent, _eventHandler);
				}else if(targetevent == "rightclick"){
					jQuery(targetelem).on("mousedown", null, "rightclick", _eventHandler);
				}else if(targetevent == "next"){
					_nextStep();
					return;
				}
				
				if(typeof stepdata.prompt == "string"){
					_displayPrompt(_replaceVariables(stepdata.prompt));	
				}
			}else{
				setTimeout(function(){_runStep();}, _options.timeout);
			}
		}
	}
	
	var _clearStep = function(){
		if(typeof _stepdata !== "undefined" && Array.isArray(_stepdata) && _stepdata.length > _curstep){
			var stepdata = _stepdata[_curstep];
			if(stepdata && stepdata.activeelement){
				var targetevent = '';
				if(stepdata.target !== '' && stepdata.target !== null){
					var pos = stepdata.target.indexOf(" ");
					if(pos > -1){
						targetevent = stepdata.target.slice(0, pos).trim();
					}
				}							
				if(targetevent == "click" || targetevent == "change" || targetevent == "mousedown" || targetevent == "mouseup" || targetevent == "mouseover" || targetevent == "mouseout" || targetevent == "focus" || targetevent == "blur"){
					jQuery(stepdata.activeelement).off(targetevent, _eventHandler);
				}else if(targetevent == "rightclick"){
					jQuery(stepdata.activeelement).off("mousedown", _eventHandler);
				}
			}
			_hidePrompt();
		}		
	}
	
	var _nextStep = function(){
		_storeVariables();
		_clearStep();
		_curstep ++;
		if(typeof _stepdata !== "undefined" && Array.isArray(_stepdata) && _stepdata.length > _curstep){
			_runStep();
		}
	};
	
    var _getElem = function(selector, $root) {
        if (!$root) $root = $(document);
        var $collection = $();
		
        // Select all elements matching the selector under the root
        var $tempcollection = $root.find(selector);
        if($tempcollection && $tempcollection.length > 0){
            $collection = $collection.add($tempcollection);
        }

        if($collection.length === 0){
            // Loop through all frames
            $root.find('iframe,frame').each(function() {
                // Recursively call the function, setting "$root" to the frame's document
                $tempcollection = _getElem(selector, $(this.contentWindow.document));
                if($tempcollection && $tempcollection.length > 0){
                    $collection = $collection.add($tempcollection);
					return;
                }        
            });
        }
        
        return ($collection.length > 0 ? $($collection.get(0)) : $collection);
    };   

	var _newGuid = function(){
		var s4 = function() {
			return Math.floor((1 + Math.random()) * 0x10000)
     	        .toString(16)
          	   .substring(1);
		};		
		result = s4().toUpperCase() + '-' + s4().toUpperCase() + s4().toUpperCase();	
		return result;
	};	
	
	var _displayPrompt = function(message, position){
		jQuery(_promptelem).html('<span style="color: ' + _options.prompt.textcolor +'; font-family: ' + _options.prompt.fontfamily + '; font-size: ' + _options.prompt.fontsize.toString() +'px;">' + message + '</span>');
		jQuery(_promptelem).show().offset({top: 200, left: 50}).css('z-index', 2147483647);
	};
	
	var _hidePrompt = function(){
		jQuery(_promptelem).html('');
		jQuery(_promptelem).hide();
	};	
	
	var _storeVariables = function(){
		if(typeof _stepdata !== "undefined" && Array.isArray(_stepdata) && _stepdata.length > _curstep){
			var stepdata = _stepdata[_curstep];
			if(stepdata && stepdata.variables){
				for (var variablename in stepdata.variables) {
					if (stepdata.variables.hasOwnProperty(variablename)) {
						if(typeof stepdata.variables[variablename] == "function"){
							_variables[variablename] = stepdata.variables[variablename](_self, (stepdata.activeelement || null ));
						}else{
							_variables[variablename] = stepdata.variables[variablename];
						}
					}
				}
			}
		}				
	};
	
	var _replaceVariables = function(inputstring){
		var result = inputstring;

		var tokens = result.match(/{%\S*?%}/gm);
		if(tokens !== null && Array.isArray(tokens)){
			for(var i=0; i<tokens.length; i++){
				var key = tokens[i].slice(2, -2);
				var replacewith = '';
				if(_variables && typeof _variables[key] !== 'undefined'){
					replacewith = _variables[key];
					if(typeof replacewith !== "string"){
						replacewith = replacewith.toString();
					}
				}
				result = result.replace(tokens[i], replacewith);
			}
		}
		
		return result;
	};
	
	var _eventHandler = function(event){
		var keepgoing = true;
		if(event && event.data){
			if(event.data == "rightclick"){
				if(event.which != 3){
					keepgoing = false;
				}
			}
		}
		if(keepgoing){
			setTimeout(function(){ _nextStep();}, _options.timeout);
		}
	};
	
	var _construct = function(){
		_tutorialid = "tutorial-" + _newGuid();
		
		var prompthtml = '';
		prompthtml += '<div id="prompt_' + _self.tutorialid + '" class="' + _self.tutorialid + '"';
		prompthtml += ' style="display:none; position:absolute;';
		prompthtml += ' border:' + _options.prompt.borderwidth + 'px solid ' + _options.prompt.bordercolor +';';
		prompthtml += ' background-color: '+ _options.prompt.backgroundcolor +';';
		prompthtml += ' width:' + _options.prompt.width.toString() +'px;';
		prompthtml += ' height:' + _options.prompt.height.toString() + 'px;"';
		prompthtml += '></div>';
		
		jQuery("body", window.top.document).append(jQuery(prompthtml));
		_promptelem = jQuery("#prompt_" + _self.tutorialid, window.top.document);
		
	};

	var _destroy = function(){
		
	};	
	
	
	/** Initialize **/
	_construct();
}

