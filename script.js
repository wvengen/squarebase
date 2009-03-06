jQuery.fn.checkform = function() {
  $(this).
  each(
    function() {
      $(this).
      submit(
        function() {
          var problems =
            $(this).
            find('.notempty:enabled:not([value])');
            //add('...')

          problems.
          css('border-color', '#f00').
          eq(0).
          focus();

          return problems.length == 0;
        }
      );
    }
  );
  return this;
};

jQuery.fn.ajaxify = function() {
  $(this).
  each(
    function() {
      $(this).
      find('.changeslost').
      css('display', 'none');

      $(this).
      find('a').
      css('background-color', '#cff').
      click(
        function() {
          var ajaxcontent =
            $(this).
            closest('.ajax').
            find('.ajaxcontent:first');
          if (ajaxcontent.length == 0) //error
            $(this).
            closest('.ajax').
            css('background-color', '#fcc');

          ajaxcontent.
          css('background-color', '#cff');

          if (ajaxcontent.attr('id') == this.href) {
            ajaxcontent.
            attr('id', '').
            empty();
          }
          else
            ajaxcontent.
            attr('id', this.href).
            load(
              this.href + ' #content', 
              null, 
              function() { 
                $(this).
                find('form').
                checkform().
                submit(
                  function() {
                    $(this).
                    find(':input[name=back]').
                    css('background-color', '#cff').
                    attr('name', 'ajax').
                    val(
                      $(this).
                      closest('.ajax').
                      attr('id')
                    );

                    $(this).
                    closest('td').
                    load(
                      $(this).
                      attr('action') + ' #content',

                      $(this).
                      serialize(),

                      function() {
                        $(this).
                        find('.ajax').
                        ajaxify();
                      }
                    );

                    return false;
                  }
                ).

                // the following line is needed because jquery doesn't include the name=value of the submit button in form.serialize()
                append('<input type="hidden" name="action" value="' + $(this).find('.mainsubmit').val() + '"/>').

                find('.mainsubmit').
                css('background-color', '#cff').
                end().

                find('.minorsubmit').
                css('display', 'none').
                end().

                find('a:contains(cancel)').
                css('background-color', '#cff').
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
                
                find('.ajax').
                ajaxify();
              }
            );

          return false;
        }
      );
    }
  );
  return this;
};

$(document)
.ready(
  function() {
    $('form').
    checkform();

    $('body.editrecord .ajax, body.newrecord .ajax').
    ajaxify();

    $('input:enabled, select:enabled').
    eq(0).
    focus();

    //jquery_document_ready_presentation goes here
  }
);
