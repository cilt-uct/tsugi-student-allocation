<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$json = Settings::linkGet('json');
$user_json = $RESULT->getJSON();
$RESULT->setJSON("{ 'x': 42 }");

// View
$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="css/app.css"/>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
if ( $USER->instructor ) {
    echo('<a href="configure.php">Configure</a>'."\n");
}
var_dump($user_json);
?>
    <div id="application" class="container">

        <div class="row">
            <div class="col-xs-4">
                <h1>Module</h1>
            </div>
            <div class="col-xs-8">
            </div>
        </div>

        <div class="row">
            <div class="col-xs-5">
                <h3>My Choices</h3>
                <hr/>
                <div id="options-user" class="options-container">
                    <span>Please select your options before submission.</span>    
                </div>    
            </div>
            <div class="col-xs-7">

                <div class="row">
                    <div class="col-xs-4">
                        <h3>All Options</h3>
                    </div>
                    <div class="col-xs-8" style="padding-top: 15px;">
                        <button type="button" id="options-search-clear" class="btn glyphicon glyphicon-remove"></button>
                        <input type="text" placeholder="Find option ..."  id="options-search"/>
                    </div>
                </div>
                <hr/>
                <div id="options-available" class="options-container"></div>
            </div>
        </div>


        <button type="button" class="btn btn-success">Submit</button>  
        <button type="button" class="btn">Cancel</button>  
    </div>
<?php
if(strlen($json) < 1 ) {
    echo("<p>Not yet configured</p>\n");
} else {
?>
<p>Configuration:</p>
<pre>
<?= $json ?>
</pre>
<?php
}
$OUTPUT->footerStart();
?>
	<!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="js/tmpl.min.js"></script>
    <script type="text/javascript">    

        var options_all = [
            {"id": "room1", name: "Room 1"}
            ,{"id": "room2", name: "Room 2"}
            ,{"id": "room3", name: "Room 3"}
            ,{"id": "room4", name: "Room 4"}
            ,{"id": "room5", name: "Room 5"}
        ];

        var options_user = [
            {"id": "room3", name: "Room 3", order: 3}
            ,{"id": "room5", name: "Room 5", order: 1}
        ];

        $(function () {

            $('#options-user').html(tmpl('tmpl-options-user', options_user));
            $('#options-available').html(tmpl('tmpl-options-selectable', {list: options_all, used: options_user}));

            $('#options-available').on('click', 'a', function(event){
                console.log('click');
            });
        });
    </script>


    <script type="text/x-tmpl" id="tmpl-options-selectable">
         {%	
         
        o.list.sort(function(a, b) {
            if (a.name < b.name) {
                return 1;
            }
            if (a.name > b.name) {
                return -1;
            }
            return 0;
        });

         for (var i=0, len = o.list.length; i < len; i++) { 
			    var item = o.list[i];
                var used = $.grep(o.used, function(e){ return e.id == item.id; });    
                if (used.length === 0) {
        %}
        <div id="option-{%=item.id%}">

            <a href="#" rel="option-{%=item.id%}" class="btn btn-success">Select</a>
            {%=item.name%}
        </div>
        {% } } %}
    </script>

    <script type="text/x-tmpl" id="tmpl-options-user">
    {% 
        o.sort(function(a, b) {
            if (a.order < b.order) {
                return 1;
            }
            if (a.order > b.order) {
                return -1;
            }
            return 0;
        });

        for (var i=0, len = o.length; i < len; i++) { 
				var item = o[i]; %}
        <div id="option-{%=item.id%}">

            <a href="#" rel="option-{%=item.id%}" class="glyphicon glyphicon-remove"></a>
            {%=item.name%}
        </div>
        {% } %}
    </script>
<?php
$OUTPUT->footerEnd();



