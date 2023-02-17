<% if $HasLocalizedObject %>
<div class="deepl js-deepl" 
  data-source-lang="$SourceLanguage" 
  data-target-lang="$TargetLanguage"
  data-value="$CurrentValue"
  data-source-value="$SourceValue"
>
  <%-- <span class="font-icon-translatable" title="Translatable field"></span> --%>
  $FieldTitle
    <ul>
      <li class='js-deepl-translate'>
        <%t Arillo\Deepl.Translate 'Translate' %>
      </li>
      <li class='js-deepl-revert'>
        <%t Arillo\Deepl.RevertTo 'Revert to {lang}' lang=$SourceLanguage %>
      </li>
      <li class="js-deepl-reset">
        <%t Arillo\Deepl.ResetChanges 'Reset changes' %>
      </li>
    </ul>
  <span class="caret">&#9662;</span>
</div>
<% end_if %>