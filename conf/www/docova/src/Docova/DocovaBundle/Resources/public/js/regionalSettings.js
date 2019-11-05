var domainstr = ".dlitools.com";
var pathstr = "/DLI/";
var dateformat = "DD/MM/YYYY";
var numberformat = "";
var thousandseparator = "";
var decimalseparator = "";
var dateorder = dateformat.replace(/\/|-|\./g, " ");
var d_sep = dateformat.indexOf(".")>-1 ?  "." : (dateformat.indexOf("-")>-1 ? "-" : (dateformat.indexOf("/") >-1 ? "/" : ""));
var d_order = "";
switch(dateorder.toUpperCase()){
case "MM DD YYYY":
     d_order = "0";
     break;    
case "DD MM YYYY":
     d_order = "1";
     break;
case "YYYY MM DD":
     d_order = "2";
     break;
}
var exdate=new Date();
exdate.setDate(exdate.getDate() + 365);
var rgcookie = "DomRegionalPrfM";
      rgcookie += "="
      rgcookie += "+";	          //+ valid or - invalid cookie
      rgcookie += ":6";               //cookie version
      rgcookie += ":UTF-8";        //character set
      rgcookie += ":";                 //locale
      rgcookie += ":" + d_order;  //date order
      rgcookie += ":" + d_sep;    //date separator
      rgcookie += ":";                 //time separator
      rgcookie += ":";                 //hour format 0=12hr 1=24hr
      rgcookie += ":";                 //AM string
      rgcookie += ":";                 //PM string
      rgcookie += ":1";               //AMPM pos 0=prefix 1=suffix
      rgcookie += ":" + decimalseparator;                 //decimal symbol
      rgcookie += ":1";               //decimal leading zeros 0=no leading 1=leading
      rgcookie += ":";                 //currency symbol
      rgcookie += ":0";               //currency pos 0=prefix 1=suffix
      rgcookie += ":0";               //currency space 0=no space 1=space
      rgcookie += ":" + thousandseparator;                 //thousands separator 
      rgcookie += ":1";               //year format 0=2 digit year 1=4 digit year
      rgcookie += ";expires=" + exdate.toUTCString();  //cookie expiry date
      rgcookie += ";domain=" + domainstr;
      rgcookie += ";path=" + pathstr;
   document.cookie = rgcookie;
