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
      find('a.newrecord').
      click(
        function() {
          var id = this.href.match(/tableid=(\d+)&field:\w+=(\d+)/, 'ajax-newrecord-{$1}-{$2}');
          if ($('#' + id).length)
            $('#' + id).
            remove();
          else
            $(this).
            after('<div id="' + id + '"></div>').
            next().
            load(
              this.href + ' #content', 
              null, 
              function() { 
                $('#' + id + ' form').
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

//                  alert($(this).serialize());

                    $(this).
                    closest('td').
                    load(
                      $(this).attr('action') + ' #content',
                      $(this).serialize(),
                      function() {
                        $(this).
                        ajaxify();
                      }
                    );

                    return false;
                  }
                ).
                // the following lines are needed because jquery doesn't include the name=value of the submit button in form.serialize()
                append('<input type="hidden" name="action" value="' + $(this).find('.mainsubmit').val() + '"/>').
                find(':submit').
                click(
                  function() {
                    this.form.action.value = this.value;
                  }
                );
              }
            );
          return false;
        }
      ).
      end().
      find('.changeslost').
      css('display', 'none');
    }
  );
  return this;
};

$(document).ready(
  function() {
    $('.ajax').
    ajaxify();

    $(':text:first').
    focus();

    //jquery_document_ready_presentation goes here
  }
);
