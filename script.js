var formnr, elementnr, eerste = null;
for (formnr = 0; !eerste && formnr < document.forms.length; formnr++)
  for (elementnr = 0; !eerste && elementnr < document.forms[formnr].elements.length; elementnr++)
    if (document.forms[formnr].elements[elementnr].type != 'hidden')
      eerste = document.forms[formnr].elements[elementnr];
if (eerste)
  eerste.focus();
