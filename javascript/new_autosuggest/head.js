<script type="text/javascript" src="javascript/modules/hms/new_autosuggest/bsn.AutoSuggest_2.1.3.js" charset="utf-8"></script>

<script type="text/javascript">

$(document).ready(function(){

    var options = {
        script:"index.php?module=hms&action=AjaxGetUsernameSuggestions&ajax=true&",
        varname:"username",
        json:true,
        shownoresults:false,
        maxresults:6,
        timeout:100000
    };

    var suggest = new bsn.AutoSuggest('student_search_form_username', options);

});

</script>
