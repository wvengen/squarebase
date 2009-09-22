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

$.fn.loading = function(on) {
  if (on)
    $(this).
    attr('id', 'loading').
    add('body').
    css('cursor', 'progress');
  else
    $('#loading').
    add('body').
    css('cursor', '');
  return this;
}

//hash equivalent of serialize
$.fn.formhash = function() {
  var hash = {};
  this.
  find(':input[name]:not([type=submit])').
  each(
    function() {
      var name = $(this).attr('name');
      if (hash[name] == undefined)
        hash[name] = $(this).formvalue();
    }
  );
  return hash;
};
    
jQuery.fn.formvalue = function() {
  var type = $(this).attr('type');
  return type == 'checkbox' ? ($(this).attr('checked') ? 'on' : '') : (type == 'radio' ? $(this).closest('form').find('input[name=' + $(this).attr('name') + ']:checked').val() : $(this).val());
}

jQuery.fn.setid = function(id) {
  $('#' + id).
  attr('id', null);

  $(this).
  attr('id', id);

  return this;
};

jQuery.fn.enhance_form = function() {
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

  find(':radio+:text').
  focus(
    function() {
      $(this).
      prev().
      attr('checked', 'checked');
    }
  ).
  blur(
    function() {
      if (!$(this).val())
        $('[name=' + $(this).prev().attr('name') + ']').
        eq(0).
        attr('checked', 'checked');
    }
  ).
  end().

  find('input:enabled:not(.readonly):not(.skipfirstfocus), select:enabled:not(.readonly):not(.skipfirstfocus)').
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

jQuery.fn.hidelogs = function() {
  $(this).

  find('.logs').
  before('<a href="" class="togglelogs" class="ajaxified">logs</a>').
  end().

  find('.togglelogs').
  click(
    function() {
      $(this).
      next().
      toggle();
      return false;
    }
  ).
  click();

  return this;
};

jQuery.fn.ajaxsubmit = function() {
  $(this).
  closest('form:not(.ajaxified)').
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
        formhash(),

        function() {
          $(this).
          hidelogs().
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
  $(this).
  find('form').
  enhance_form().
  ajaxsubmit().
  end().

  find('.changeslost').
  css('display', 'none').
  end().

  find(':input[type=submit]:not(.ajaxified)').
  addClass('ajaxified').
  click(
    function() {
      // the following line is needed because the name=value of the submit button isn't included in form.serialize()/form.formhash()
      // because there is no way to know which submit button is pressed
      $(this).
      append('<input type="hidden" name="action" value="' + $(this).val() + '"/>');
      return true;
    }
  ).
  end().

  find('.cancel:not(.ajaxified), .close:not(.ajaxified)').
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
        else {
          if (ajaxcontent.attr('ajaxurl') == this.href)
            ajaxcontent.
            unload();
        }
        ajaxcontent =
          containingblock.
          next('.ajaxcontent').
          attr('ajaxurl', this.href);
      }

      if (ajaxcontent.length > 0) {
      $(this).
      loading(true);

        ajaxcontent.
        find('.ajaxcontainer:first').
        load(
          this.href + ' #content',

          null,

          function() {
            $(this).
            hidelogs().
            ajaxify().
            loading(false);
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
    $('html').
    hidelogs();

    $('body.editrecord, body.newrecord, body.showtable, body.showdatabase').
    find('.ajax').
    ajaxify();

    $('form:not(.enhancedform)').
    enhance_form();

    $('body.formmetabasefordatabase').
    find('.insome').
    change(
      function() {
        $(this).
        closest('form').
        find('.insome[name^=' + $(this).attr('name').regexmatch('^\\w+:') + '][name$=' + $(this).attr('name').regexmatch(':\\w+$') + ']').
        closest('td').
        toggleClass('ajaxincompatible', 
          $(this).
          closest('form').
          find('.insome[name^=' + $(this).attr('name').regexmatch('^\\w+:') + '][name$=' + $(this).attr('name').regexmatch(':\\w+$') + ']:checked').
          length == 0
        );
      }
    ).
    change().
    end().

    find('.presentationname').
    change(
      function() {
        $(this).closest('tr').find('.foreigntablename').toggle($(this).val() == 'lookup');
      }
    ).
//  change(). this takes too long on Firefox 3.0, therefore the following lines
    end().
    find('.foreigntablename').
    hide().
    end().
    find('.presentationname[value=lookup]').
    each(
      function() {
        $(this).closest('tr').find('.foreigntablename').show();
      }
    );

    //jquery_document_ready_presentation goes here
  }
);
