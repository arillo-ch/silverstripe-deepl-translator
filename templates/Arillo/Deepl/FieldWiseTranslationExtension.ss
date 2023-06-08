<div class="deepl js-deepl" data-value="$CurrentValue">
  <%-- <span class="font-icon-translatable" title="Translatable field"></span> --%>
  $FieldTitle
  <ul>
    <% loop $CurrentValues %>
      <% if not $IsCurrent %>
        <li class='js-deepl-translate' data-source-lang="$Language" data-target-lang="$Up.TargetLanguage" data-text="$Value.ATT">
          <%t Arillo\Deepl.TranslateFrom 'Translate from {locale}' locale=$Locale.Title %>
        </li>
      <% end_if %>
    <% end_loop %>
    <% loop $CurrentValues %>
      <% if not $IsCurrent %>
        <li class='js-deepl-revert-to' data-target-lang="$Up.TargetLanguage" data-text="$Value.ATT">
          <%t Arillo\Deepl.RevertTo 'Revert to {lang}' lang=$Locale.Title %>
        </li>
      <% end_if %>
    <% end_loop %>
    <li class="js-deepl-reset">
      <%t Arillo\Deepl.ResetChanges 'Reset changes' %>
    </li>
  </ul>
  <span class="caret">&#9662;</span>
</div>
