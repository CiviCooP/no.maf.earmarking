<div id="maf-earmarking" class="crm-summary-row">
  <div class="crm-label">{ts}Earmarking{/ts}</div>
  <div class="crm-content crm-recurring-earmarking">{$earmarking}</div>
</div>

{literal}
  <script type='text/javascript'>
    cj(".crm-contact_type_label").parent().append(cj("#maf-earmarking"));
  </script>
{/literal}
