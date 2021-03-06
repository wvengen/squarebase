String.prototype.regexmatch = function (format) {
  var result = new RegExp(format, 'im').exec(this);
  return !result ? null : (result.length === 1 ? result[0] : (result.length === 2 ? result[1] : result));
}

jQuery.extend(
  $.expr[':'],
  {
    inline:     function(a) { return $(a).css('display') === 'inline'; },
    block:      function(a) { return $(a).css('display') === 'block';  },
    blocklevel: function(a) { var display = $(a).css('display'); return display === 'block' || display === 'table-row'; }
  }
);

jQuery.fn.log = function(msg) {
  console.log("%s: %o", msg, this);
  return this;
};

jQuery.fn.getScripts = function(url, selector, callback) {
  var target = $(selector);
  if (target.length)
    $.requireScript(url, callback, target);
  return this;
};

jQuery.fn.loading = function(on) {
  if (on)
    $(this).
    attr('id', 'loading').
    add('body').
    css('cursor', 'progress');
  else
    $('#loading').
    attr('id', '').
    add('body').
    css('cursor', '');
  return this;
}

//hash equivalent of serialize
jQuery.fn.formhash = function() {
  var hash = {};
  this.
  find(':input[name]').
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

jQuery.fn.enhance_form = function() {
  $(this).
  filter(
    function() {
      return $(this).length && !$(this).hasClass('enhancedform');
    }
  ).

  addClass('enhancedform').

  find(':input').
    keyup(
      function() {
        $(this).
        closest('form').
        check_form();
      }
    ).
  end().

  find('select').
    change(
      function() {
        $(this).
        closest('form').
        check_form();
      }
    ).
  end().

  check_form().

  /* jquery_enhance_form_* */

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

  find('.altsubmit').
    each(
      function() {
        $(this).
        after(
          $('<input>').
          attr('type', 'button').
          attr('name', 'altaction').
          attr('value', $(this).text()).
          addClass($(this).attr('for')).
          addClass('button').
          click(
            function() {
              $(this).
              closest('form').
                find('.altsubmit :checkbox').
                  attr('checked', true).
                end().
                append(
                  $('<input>').
                  attr('type', 'hidden').
                  attr('name', 'action').
                  attr('value', $(this).closest('form').find('.submit').val())
                ).
              end();

              $(this).
              closest('form').
              submit();
            }
          )
        ).
        hide();
      }
    ).
    closest('form').
      find('.submit').
        click(
          function() {
            $(this).
            closest('form').
            find('.altsubmit :checkbox').
            attr('checked', false);
          }
        ).
      end().
    end().
  end().

  submit(
    function() {
      $(this).
      check_form().
      find('.ajaxproblem').
      not('form .ajaxproblem').
      focus();
      return !$(this).has_problem();
    }
  ).

  find('input, select').
  filter(':enabled:not(:submit):not(:button):not(.readonly):not(.skipfirstfocus):not(.list)').
  eq(0).
  focus();

  return this;
}

jQuery.fn.check_form = function() {
  $(this).
  find('.ajaxproblem').
    removeClass('ajaxproblem').
  end().

  find('.notempty:enabled:not([value])').
  addClass('ajaxproblem');

  return this;
}

jQuery.fn.has_problem = function() {
  return false;
  //submitting a form now also submits the outermost form which maybe have ajaxproblemes
  return $(this).find('.ajaxproblem').not('form .ajaxproblem').length > 0 && $(this).find(':checked[name=deleterecord]').length == 0;
}

jQuery.fn.hidelogs = function() {
  $(this).

  find('.logs').
    before('<div><a href="" class="togglelogs ajaxified">logs</a></div>').
  end().

  find('.togglelogs').
  click(
    function() {
      $(this).
      parent().
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
      if ($(this).has_problem())
        return false;
      $(this).
      find(':input[name=back]').
        attr('name', 'ajax').
        val(
          $(this).
          closest('.ajax').
          attr('id')
        ).
      end();

      var ajaxcontainer =
        ($(this).hasClass('ajaxcontainerminus1')
        ? $(this).closest('.ajaxcontainer')
        : ($(this).hasClass('ajaxcontainerminus2')
          ? $(this).closest('.ajaxcontainer').parent().closest('.ajaxcontainer')
          : null
          )
        );

      if (ajaxcontainer) {
        ajaxcontainer.
        load(
          $(this).
          attr('action') + ' #content',

          $(this).
          formhash(),

          function(responseText, textStatus, XMLHttpRequest) {
            if (!responseText.regexmatch(' id="content"'))
              $(this).
              html('<div class="error">' + responseText + '</div>');
            $(this).
            hidelogs().
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

jQuery.fn.unload = function() {
  $(this).
  remove();
  return this;
};

jQuery.fn.ajaxify = function() {
  $(this).
  enhance_form().
  ajaxsubmit().

  /* jquery_ajaxify_* */

  find('.changeslost').
    css('display', 'none').
  end().

  find('.cancel:not(.ajaxified), .close:not(.ajaxified)').
    addClass('ajaxified').
    click(
      function() {
        var ajaxcontent =
          $(this).
          closest('.ajaxcontent');
        if (ajaxcontent.length) {
          ajaxcontent.
          unload();
          return false;
        }
      }
    ).
    hover(
      function() {
        $(this).
        closest('.box').
        toggleClass('closing');
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
            ? '<tr class="ajaxcontent"><td colspan="' + $(containingblock).children().length + '" style="padding: 0;"><div class="ajaxcontainer ajaxindent"></div></td></tr>'
            : '<div class="ajaxcontent"><div class="ajaxcontainer ajaxindent"></div></div>'
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

          function(responseText, textStatus, XMLHttpRequest) {
            if (!responseText.regexmatch(' id="content"'))
              $(this).
              html('<div class="error">' + responseText + '</div>');
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

  if ($('.referringlist').length > 0) {
    referringlist = $('.referringlist');
    tableedit = referringlist.closest('.box').find('.tableedit');

    if (referringlist.find('td:first').width() > tableedit.find('td:first').width()) {
      source = referringlist;
      target = tableedit;
    }
    else {
      source = tableedit;
      target = referringlist;
    }
    width1 = source.find('td:first').width();

    source.
    add(target).
    find('>tbody>tr>.filler').
      remove().
    end();

    width2 = target.find('td:nth-child(1)').width() + target.find('td:nth-child(2)').width() - width1;

    source.
    add(target).
    find('th').
      width(width1 + width2).
    end().
    find('tr td:nth-child(1)').
      width(width1).
    end().
    find('tr td:nth-child(2)').
      width(width2).
    end();
  }

  return this;
};

$(document).
ready(
  function() {
    $('html').
    hidelogs();

    if ($('body.ajaxy .ajax').length)
      $('.ajax').
      ajaxify();

    $('form').
    enhance_form();

    $('table.formmetabasefordatabase').
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
    end().

    find('.checkboxedit.viewfortable, .checkboxedit.include').
      change(
        function() {
          var rijen =
            $(this).
            closest('table').
            find('tr.table-' + $(this).closest('tr').find('.tablename').text());
          var full = !rijen.find('.viewfortable').attr('checked') && rijen.find('.include').attr('checked');

          rijen.
          filter('tr:first').
            children('td.top').
              attr('rowspan', full ? rijen.length : 1).
            end().
            find('.pluralsingular, .intablelist, td:not(.top):not(.reason)').
              contents().
                toggle(full).
              end().
            end().
          end().
          filter('tr:not(:first)').
            css('display', full ? 'table-row' : 'none').
          end();

          if ($(this).hasClass('include'))
            rijen.
            filter('tr:first').
              children('td.top').
                find('.alternative').
                  toggle($(this).attr('checked')).
                end().
              end().
            end();
        }
      ).
      change().
    end().

    find('.presentationname').
      change(
        function() {
          $(this).next('.foreigntablename').toggle($(this).val() == 'lookup');
        }
      ).
//    change(). this takes too long on Firefox 3.0, therefore the following lines
    end().
    find('.foreigntablename').
      hide().
    end().
    find('.presentationname[value=lookup]').
      each(
        function() {
          $(this).next('.foreigntablename').show();
        }
      ).
    end().
    
    find('td.row').
      hover(
        function() {
          $(this).
          closest('tr').
            find('td').
            toggleClass('active');
        }
      ).
    end();

    /* jquery_document_ready_* */
  }
);
