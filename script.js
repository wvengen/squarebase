$.extend(
  $.expr[':'],
  {
    inline:     function(a) { return $(a).css('display') === 'inline'; },
    block:      function(a) { return $(a).css('display') === 'block';  },
    blocklevel: function(a) { return $(a).css('display') === 'block' || $(a).css('display') === 'table-row';  }
  }
);

jQuery.fn.enhanceform = function() {
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

jQuery.fn.ajaxify = function() {
//$(this).
//css('background', 'red');
//alert('ajaxify');
//$(this).
//css('background', null);

  $(this).
  find('form').
  enhanceform().
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
        if (containingblock.next('.ajaxcontent').length == 0) {
          containingblock.
          after(
            containingblock.css('display') == 'table-row'
            ? '<tr class="ajaxcontent"><td colspan="' + $(containingblock).children().length + '" style="padding: 0;"><div class="ajaxcontainer"></div></td></tr>'
            : '<div class="ajaxcontent"><div class="ajaxcontainer"></div></div>'
          );
        }
        ajaxcontent = 
          containingblock.
          next('.ajaxcontent');
      }

      if (ajaxcontent.attr('id') == this.href) {
        ajaxcontent.
        remove();
      }
      else {
        ajaxcontent.
        attr('id', this.href).
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
                remove();
                return false;
              }
            ).
            end().

            find('.ajax').
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

    $('form').
    enhanceform();

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

    //jquery_document_ready_presentation goes here
  }
);
