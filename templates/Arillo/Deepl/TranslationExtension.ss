<div class="autotranslate js-autotranslate" 
  data-source-lang="$SourceLanguage" 
  data-target-lang="$TargetLanguage"
  data-value="$CurrentValue"
  data-source-value="$SourceValue"
>
  <span class="font-icon-translatable" title="Translatable field"></span>
  $FieldTitle
  <% if $HasLocalizedObject %>
    <ul>
      <li class='js-autotranslate-translate'>Translate</li>
      <li class='js-autotranslate-revert'>Revert to {$SourceLanguage} </li>
      <li class="js-autotranslate-reset">Reset changes</li>
    </ul>
  <% end_if %>
  <span class="caret">&#9662;</span>
</div>