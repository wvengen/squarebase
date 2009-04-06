String.prototype.regexmatch = function (format) {
  var result = new RegExp(format, 'im').exec(this);
  return !result ? null : (result.length === 1 ? result[0] : (result.length === 2 ? result[1] : result));
}

$.extend(
  $.expr[':'],
  {
    inline:     function(a) { return $(a).css('display') === 'inline'; },
    block:      function(a) { return $(a).css('display') === 'block';  },
    blocklevel: function(a) { var display = $(a).css('display'); return display === 'block' || display === 'table-row'; }
  }
);

jQuery.fn.formvalue = function() {
  return $(this).attr('type') == 'checkbox' ? $(this).attr('checked') : $(this).val();
}

jQuery.fn.setid = function(id) {
  $('#' + id).
  attr('id', null);

  $(this).
  attr('id', id);

  return this;
};

jQuery.fn.enhance_form = function() {
//alert('enhance_form ' + $(this).length);

  $(this).
  filter(':not(.enhancedform)').
  addClass('enhancedform').

  find(':input').
  keyup(
    function() {
      $(this).
      checkform();
    }
  ).
  end().

  find('select').
  change(
    function() {
      $(this).
      checkform();
    }
  ).
  end().

  checkform().

  //jquery_enhance_form_presentation goes here

  find('input:enabled, select:enabled').
  eq(0).
  focus();

  return this;
}

jQuery.fn.checkform = function() {
  $(this).
  closest('form').
  find('.ajaxproblem').
  removeClass('ajaxproblem').
  end().

  find('.notempty:enabled:not([value])').
  addClass('ajaxproblem');

  return this;
}

jQuery.fn.ajaxsubmit = function() {
  $(this).
  closest('form').
  filter(':not(.ajaxified)').
  addClass('ajaxified').
  submit(
    function() {
      if ($(this).checkform().find('.ajaxproblem:first').focus().length > 0)
        return false;

      $(this).
      find(':input[name=back]').
      attr('name', 'ajax').
      val(
        $(this).
        closest('.ajax').
        attr('id')
      ).
      end().

      closest('form').
      closest('.ajax').
      load(
        $(this).
        attr('action') + ' #content',

        $(this).
        find(':disabled').
        attr('disabled', null).
        end().
        serialize(),

        function() {
          $(this).
          find('.ajax').
          ajaxify();
        }
      );

      return false;
    }
  );
  return this;
};

jQuery.fn.unload = function() {
  $(this).
  remove();

  return this;
};

jQuery.fn.ajaxify = function() {
//$(this).
//css('background', 'red');
//alert('ajaxify ' + $(this).length);
//$(this).
//css('background', null);

  $(this).
  find('form').
  enhance_form().
  ajaxsubmit().
  end().

  find('.changeslost').
  css('display', 'none').
  end().

  find('a:not(.ajaxified)').
  addClass('ajaxified').
  click(
    function() {
      var ajaxcontent =  null;
      if ($(this).hasClass('ajaxreload')) {
        ajaxcontent = 
          $(this).
          closest('.ajaxcontent');
      }
      else {
        var containingblock =
          $(this).
          closest(':blocklevel');
        ajaxcontent =
          containingblock.
          next('.ajaxcontent');
        if (ajaxcontent.length == 0)
          containingblock.
          after(
            containingblock.css('display') == 'table-row'
            ? '<tr class="ajaxcontent"><td colspan="' + $(containingblock).children().length + '" style="padding: 0;"><div class="ajaxcontainer"></div></td></tr>'
            : '<div class="ajaxcontent"><div class="ajaxcontainer"></div></div>'
          );
        else
          ajaxcontent.
          unload();
        ajaxcontent =
          containingblock.
          next('.ajaxcontent');
      }

      if (ajaxcontent.length > 0) {
        ajaxcontent.
        find('.ajaxcontainer:first').
        load(
          this.href + ' #content',
          null,
          function() {
            $(this).
            find('form').
            ajaxsubmit().

            find('.mainsubmit').
            addClass('ajaxified').
            click(
              function() {
                // the following line is needed because jquery doesn't include the name=value of the submit button in form.serialize()
                $(this).
                append('<input type="hidden" name="action" value="' + $(this).val() + '"/>');
                return true;
              }
            ).

            end().

            find('.minorsubmit').
            addClass('ajaxified').
            end().

            end(). //find('form')

            find('.cancel, .close').
            addClass('ajaxified').
            click(
              function() {
                $(this).
                closest('.ajaxcontent').
                unload();
                return false;
              }
            ).
            end().

            ajaxify();
          }
        );
      }

      return false;
    }
  );
  return this;
};

$(document).
ready(
  function() {
    $('body.editrecord, body.newrecord, body.showtable, body.showdatabase').
    find('.ajax').
    ajaxify();

    $('form:not(.enhancedform)').
    enhance_form();

    $('#logs').
    before('<a href="" id="togglelogs" class="ajaxified">logs</a>');

    $('#togglelogs').
    click(
      function() {
        $('#logs').
        toggle();
        return false;
      }
    ).
    click();

    $('body.formmetabasefordatabase').
    find('.typename, .dependsontypename').
    change(
      function() {
        $(this).
        closest('form').
        find('.typename').
        each(
          (function() {
            var firsttypenames = {};
            return function () {
              var typename = $(this).val();
              $(this).
              closest('tr').
              find('.dependsontypename').
              attr('disabled', firsttypenames[typename] ? 'disabled' : null).
              filter(':disabled').
              each(
                function() {
                  var first = $(firsttypenames[typename]).closest('tr').find('[name$=' + $(this).attr('name').regexmatch(':\\w+$') + ']');
                  if ($(this).formvalue() == first.formvalue())
                    $(this).closest('td').
                    removeClass('ajaxwarning');
                  else
                    $(this).closest('td').
                    addClass('ajaxwarning');
                }
              );

              if (!firsttypenames[typename])
                firsttypenames[typename] = this;
            }
          })()
        );
      }
    ).
    eq(0).
    change().
    end().
    end().
    find('.presentation').
    change(
      function() {
        $(this).closest('tr').find('.foreigntablename').toggle($(this).val() == 'lookup');
      }
    );
    // the next line takes too long on Firefox 3.0
//  change();

    //jquery_document_ready_presentation goes here
  }
);
