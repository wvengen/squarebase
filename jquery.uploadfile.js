jQuery.fn.uploadFile = function(action, success, error) {
  var iframe = $('#uploadiframe');
  if (!iframe.length) {
    iframe = $('<iframe id="uploadiframe"/>');
    $('body').append(iframe);
  }

  var form = $('<form  action="' + action + '" method="POST" id="uploadiform" enctype="multipart/form-data"/>');
  iframe.contents().find('body').empty().append(form); 

  form.append($(this).clone());
  $(this).attr('disabled', 'disabled');

  iframe.load(
    function() {
      success(iframe);
    }
  );
  form.submit();

  return this;
}
