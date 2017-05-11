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
            <div class="col-xs-8" id="timer-container">
            </div>
        </div>

        <div class="row">
            <div class="col-xs-5">
                
                <div class="row">
                    <div class="col-xs-8">
                        <h4>My Choices</h4>
                    </div>
                    <div class="col-xs-4">
                        <button type="button" id="options-user-clear" class="btn">Clear</button>
                    </div>
                </div>
                <hr/>
                <div id="options-user" class="options-container">
                        
                </div>    
            </div>
            <div class="col-xs-7">

                <div class="row">
                    <div class="col-xs-4">
                        <h4>All Options</h4>
                    </div>
                    <div class="col-xs-8">
                        <button type="button" id="options-search-clear" class="btn glyphicon glyphicon-remove"></button>
                        <input type="text" placeholder="Find option ..."  id="options-search"/>
                    </div>
                </div>
                <hr/>
                <div id="options-available" class="options-container"></div>
            </div>
        </div>

        <hr/>
        <button id="option-submit" type="button" class="btn btn-success">Submit</button>  
        <button id="option-cancel" type="button" class="btn">Cancel</button>  
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
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
	<script src="js/tmpl.min.js"></script>
    <script src="js/Sortable.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script type="text/javascript">    
        var num = ['st','nd','rd'],
            timespan = { year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                h: 3600,
                m: 60,
                s: 1
            };

        var sorter = null,
            raw = {
                expiry: "2017-05-12T08:00:00Z",
                all: [
                    {"id": "room1", name: "ARoom 1"}
                    ,{"id": "room2", name: "BRoom 2"}
                    ,{"id": "room3", name: "CRoom 3"}
                    ,{"id": "room4", name: "DRoom 4"}
                    ,{"id": "room5", name: "ERoom 5"}
                ],
                users: [
                    {"id": "room3", name: "CRoom 3", order: 3}
                    ,{"id": "room5", name: "ERoom 5", order: 1}
                ]
            };

        function getOption(_arr, _id) {
            var result = $.grep(_arr, function(e){ return e.id == _id; }); 
            return result.length > 0 ? result[0] : null;   
        }        

        function upFirst(string) 
        {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }  

        $(function () {

            var selected = $('#options-user'),
                available = $('#options-available'),
                search_input = $('#options-search'),
                search_id = null,
                timer = $('#timer-container'),
                timer_id = null;

             var now = new Date(),
                exp = Date.parse(raw.expiry),
                t = ((exp - now)/1000);

            timer.data('time', t);
            if (t > 0) {
                timer_id = window.setInterval(function(){
                    var t = timer.data('time') - 1;
                    timer.data('time', t);
                    timer.html(tmpl('tmpl-timer', { time: timer.data('time'), exp: raw.expiry }));
                    if (t <= 0) {
                        window.clearInterval(timer_id);
                        $('#option-submit').addClass('disabled').data('disabled', 1).addClass('hidden');
                    }
                }, 1000);
            } else {
                $('#option-submit').addClass('disabled').data('disabled', 1).addClass('hidden');
            }
            timer.html(tmpl('tmpl-timer', { time: timer.data('time'), exp: raw.expiry }));

            selected.html(tmpl('tmpl-options-user', raw.users));
            available.html(tmpl('tmpl-options-selectable', {list: raw.all, used: raw.users}));
            selected.height(available.height());
            available.height(available.height());
            search_input.html('');

            function selectOption(_rel, _st){
                var item = getOption(raw.all, _rel);   

                $('#'+ _st + _rel).addClass('hidden');
                item.order = raw.users.length;
                raw.users.push(item);
                selected.html(tmpl('tmpl-options-user', raw.users));
            } 

            $('#options-user-clear').on('click', function(event){
                raw.users = [];
                $('#options-available > div:hidden').removeClass('hidden');
                selected.html(tmpl('tmpl-options-user', raw.users));
            });

            available.on('click', 'a', function(event){
                event.preventDefault();
                selectOption($(this).attr('rel'), 'option-');
            });

            available.on('dblclick', '>div', function(event){
                event.preventDefault();
                selectOption($(this).data('id'), 'option-');
            });

            selected.on('click', 'a', function(event){
                event.preventDefault();
                var rel = $(this).attr('rel');
                raw.users = $.grep(raw.users, function(e){ return e.id != rel; });
                $('#option-'+ rel).removeClass('hidden');
                selected.html(tmpl('tmpl-options-user', raw.users));
            });

            sorter = Sortable.create(selected[0], {
                onEnd: function (event) {
                    var tmp = [];
                    selected.children('div').each(function(i, el) {
                        var item = $(el).data();
                        item.order = i;
                        tmp.push(item);
                    });
                    raw.users = tmp;
                    console.log(tmp);
                    selected.html(tmpl('tmpl-options-user', raw.users));
                }
            });

            function doFilter(val) {
                
                if (search_id) window.clearTimeout(search_id);

                if (val.length > 2) {

                    val = val.toUpperCase();
                    available.children('div').each(function(i, el){
                        var found = ($(this).data('name').toUpperCase().indexOf(val) > -1);

                        if (found) {
                            // should be visible
                            $(el).removeClass('filtered');
                        } else {
                            // should hide                            
                            $(el).addClass('filtered');
                        }
                    });
                } else {
                    available.children('div.filtered').removeClass('filtered');
                }
            }

            $('#options-search-clear').on('click', function(event){
                search_input.val('');
                doFilter('');
            });
            
            search_input.on('keyup', function(event){
                if (search_id) window.clearTimeout(search_id);
                search_id = window.setTimeout(function(){
                    doFilter(search_input.val());
                }, 300);
            });
        });
    </script>


    <script type="text/x-tmpl" id="tmpl-options-selectable">
         {%	
         
        o.list.sort(function(a, b) {
            if (a.name > b.name) {
                return 1;
            }
            if (a.name < b.name) {
                return -1;
            }
            return 0;
        });

         for (var i=0, len = o.list.length; i < len; i++) { 
			    var item = o.list[i];
                var used = getOption(o.used, item.id);
        %}
        <div id="option-{%=item.id%}" class="{%=(used === null?'':'hidden')%}" data-id="{%=item.id%}" data-name="{%=item.name%}">

            <a href="#" rel="{%=item.id%}" class="btn btn-success">Select</a>
            {%=item.name%}
        </div>
        {% } %}
    </script>

    <script type="text/x-tmpl" id="tmpl-options-user">
    {% 
        o.sort(function(a, b) {
            if (a.order > b.order) {
                return 1;
            }
            if (a.order < b.order) {
                return -1;
            }
            return 0;
        });

        if (o.length > 0) {
        for (var i=0, len = o.length; i < len; i++) { 
				var item = o[i]; %}
        <div id="option-use-{%=item.id%}" data-id="{%=item.id%}"data-name="{%=item.name%}" data-order="{%=item.order%}">

            <a href="#" rel="{%=item.id%}" class="glyphicon glyphicon-remove"></a>
            <strong>{%=(i+1)%}<sup>{%=(num[i]?num[i]:'th')%}</sup></strong>&nbsp;&nbsp;{%=item.name%}
        </div>
        {% } } else { %}<span>Please select your options before submission.</span>{% } %}
    </script>

    <script type="text/x-tmpl" id="tmpl-timer">
    {%
        var d = o.time,
            r = {},
            result = [];

        Object.keys(timespan).forEach(function(key){
            r[key] = Math.floor(d / timespan[key]);
            d -= r[key] * timespan[key];
        });

        //{year:0,month:0,week:1,day:2,hour:34,minute:56,second:7}
        $.each(r, function(i, el) {
            if (el > 0) {
                result.push((i.length == 1 ? (el < 10 ? ' '+el : el) : el) + (i.length == 1 ? i : ' '+ upFirst(i) + (el > 1 ? 's':'')));
            }
        });

        if (o.time <= 0) {
            print('<span class="label label-default">'+ moment(o.exp).calendar() +'</span>', true);
        } else {
            print('<span class="label '+ ( o.time <= 600 ? 'label-danger' : (o.time <= 1800 ? 'label-warning' : 'label-info'))+'"> Closing in '+ result.join('&nbsp;') +'</span>', true);
        }
    %}
    </script>
<?php
$OUTPUT->footerEnd();



