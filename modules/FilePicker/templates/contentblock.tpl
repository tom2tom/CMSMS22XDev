<div class="cmsfp_cont">
{* the instance uniquely identifies this field, and associates it with the corresponding popup *}
  <input type="text" id="{$instance}" name="{$blockName}" value="{$value}" data-cmsfp-instance="{$instance}" size="{if !empty($inputlength)}{$inputlength}{else}60{/if}">
  <script>
  $(function() {
    $('#{$instance}').filepicker({
      title: "{$title}",
      remove_title: "{$mod->Lang('clear')}",
      remove_label: "{$mod->Lang('clear')}",
      required: {if $required}true{else}false{/if},
      param_sig: "{$sig}",
      param_useprefix: true
    });
  });
  </script>
</div>
