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
      var ajaxcontent =
        $(this).
        closest('.ajax').
        find('.ajaxcontent:first');

      if (ajaxcontent.length == 0) //error
        $(this).
        closest('.ajax').
        addClass('ajaxproblem');

      if (ajaxcontent.attr('id') == this.href) {
        ajaxcontent.
        attr('id', '').
        empty();
      }
      else {
        ajaxcontent.
        attr('id', this.href).
        load(
          this.href + ' #content',
          null,
          function() {
            $(this).
            find('form').
            ajaxsubmit().

            // the following line is needed because jquery doesn't include the name=value of the submit button in form.serialize()
            append('<input type="hidden" name="action" value="' + $(this).find('.mainsubmit').val() + '"/>').

            find('.mainsubmit').
            addClass('ajaxified').
            end().

            find('.minorsubmit').
            addClass('ajaxified').
            end().

            find('.cancel').
            addClass('ajaxified').
            click(
              function() {
                $(this).
                closest('.ajax').
                find('.ajaxcontent').
                attr('id', null).
                empty();
                return false;
              }
            ).
            end().

            closest('.ajax').
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
    $('body.editrecord .ajax, body.newrecord .ajax').
    ajaxify();

    $('form').
    enhanceform();

    //jquery_document_ready_presentation goes here
  }
);
