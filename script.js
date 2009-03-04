String.prototype.match = function match(regex, format) {
  var matches = new RegExp(regex).exec(this);
  if (!matches) return null;
  if (!format && matches.length == 2) return matches[1];
  if (!format) return matches[0];
  var result = format;
  for (var i = 0; i < matches.length; i++)
    result = result.replace('\{\$' + i + '\}', matches[i]);
  return result;
}

jQuery.fn.ajaxify = function() {
  $(this).each(
    function() {
      $(this).
      find('.changeslost').
      css('display', 'none');

      $(this).
      find('a').
      click(
        function() {
          var ajaxcontent = $(this).closest('.ajax').find('.ajaxcontent:first');
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
                submit(
                  function() {
                    $(this).
                    find(':input[name=back]').
                    attr('name', 'ajax').
                    val(
                      $(this).
                      closest('.ajax').
                      attr('id')
                    );

                    $(this).
                    closest('td').
                    load(
                      $(this).attr('action') + ' #content',
                      $(this).serialize(),
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

                find('.minorsubmit, .newsubrecord').
                css('display', 'none').
                end().

                find('.cancel').
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

$(document).ready(
  function() {
    $('body.editrecord .ajax').
    ajaxify();

    $(':text:first').
    focus();

    //jquery_document_ready_presentation goes here
  }
);
