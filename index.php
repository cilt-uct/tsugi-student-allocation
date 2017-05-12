<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$json = Settings::linkGet('json'); // LTI_LINK JSON > contains the course settings

//$RESULT->setJSON("[]");
$user_json = $RESULT->getJSON();  // Saved Student Data
if(strlen($user_json) < 10 ) {
    $user_json = '{"result": {},"selection": []}';
}

// View
$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="css/app.css"/>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();

if ( $USER->instructor ) {
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class="active"><a href="#">My Selection</a></li>
    <li><a href="roomsetup.php">Administrate</a></li>
    <li><a href="configure.php">Configure</a></li>
    <li>
        <form method="GET" action="perl.php" target="iframe-frame" style="display: inline">
            <input type="hidden" name="link_id" value="<?= $LINK->id ?>">
            <input type="submit" value="Run Assignments"
                onclick="showModalIframe(this.title, 'iframe-dialog', 'iframe-frame', _TSUGI.spinnerUrl, true);" >
        </form>
    </li>
</ul>
<?php
}

$selection = json_decode($user_json);
if (json_last_error() <> JSON_ERROR_NONE) {
    // print json_last_error_msg();
    $user_json = '{"result": {},"selection": []}';
}

$assigned_name = '';
if (isset($selection->{'result'}->{'name'})) {
    $assigned_name = $selection->{'result'}->{'name'};
}

$settings = Settings::linkGet('json');
$json = json_encode($settings);

$date_now = new DateTime();
$date_expiry = new DateTime();
if (isset($settings['expiry'])) {
    $date_expiry = new DateTime($settings['expiry']);
}

$date_left = $date_expiry->getTimestamp() - $date_now->getTimestamp();

if (json_last_error() <> JSON_ERROR_NONE) {
    print "error<br/>";
    print json_last_error_msg() . "<br>";

    $json = '{ "expiry": "'. date("Y-m-d") .'T06:00:00Z","constraints": { "max": -1}, groups: []}';
    $date_left = -1;
}
?>
<div id="iframe-dialog" title="Read Only Dialog" style="display: none;">
   <iframe name="iframe-frame" style="height:400px" id="iframe-frame"
    src="<?= $OUTPUT->getSpinnerUrl() ?>"></iframe>
</div>
    <div id="application">

        <div class="row">
            <div class="col-xs-8">
                <h1><?= $LINK->title ?></h1>
            </div>
            <div id="timer-container" class="col-xs-4"></div>
        </div>

<?php if ($date_left > 0) { ?>
        <div class="row">
            <div class="col-xs-5">
                
                <div class="row">
                    <div class="col-xs-8">
                        <h4 id="option-user-title">My Choices</h4>
                    </div>
                    <div class="col-xs-4 text-right">
                        <button type="button" id="options-user-clear" class="btn">Clear</button>
                    </div>
                </div>
                <hr/>
                <div id="options-user" class="options-container sortable"></div>    
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
        <div class="row footer">
            <div class="col-xs-5" style="height:50px">
                <button id="option-submit" type="button" data-disabled="1" class="btn btn-success hidden">Submit</button>
                <div id="options-valid"></div>
            </div>
            <div id="option-notification" class="col-xs-7"></div>
        </div>
<?php } else { ?>
        
        <div class="row">
            <div class="col-xs-5">
                
                <h4>Assigned to: <?= $assigned_name ?></h4>
                <div class="row">
                    <div class="col-xs-8">
                        <h4 id="option-user-title">My Choices</h4>
                    </div>
                    <div class="col-xs-4 text-right"></div>
                </div>
                <hr/>
                <div id="options-user" class="options-container static"></div>    
            </div>
        </div>
        <hr/>
<?php } ?>
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
    <script src="js/Sortable.min.js"></script>
    <script src="js/moment.min.js"></script>
    <script src="js/app.js"></script>
    <script type="text/javascript">    
        
        var raw = <?= $json ?>,
            my_selection = <?= $user_json ?>;

        function notify(type, msg) {
            if (type == undefined) {
                $('#option-notification').html('');
            } else {
                $('#option-notification').html(tmpl('tmpl-notify', {type: type, msg: msg}));
            }
        }

        $(function () {

            var selected = $('#options-user'),
                selected_title = $('#option-user-title'),
                selected_valid = $('#options-valid'),
                available = $('#options-available'),
                search_input = $('#options-search'),
                search_id = null,
                timer = $('#timer-container'),
                timer_id = null,
                btn_submit = $('#option-submit');

            function showSelected() {
                var t = timer.data('time'),
                    valid = (t > 1) && (my_selection.selection.length > 0) &&
                            ((my_selection.selection.length >= raw.constraints.min) || (my_selection.selection.length == raw.constraints.max));
                selected.html(tmpl('tmpl-options-user', {list: my_selection.selection, limit: raw.constraints}));
                selected_title.html(tmpl('tmpl-options-title', {len: my_selection.selection.length, limit: raw.constraints}));
                selected_valid.html(tmpl('tmpl-options-valid', {len: my_selection.selection.length, limit: raw.constraints}));

                if (valid) {
                    btn_submit.removeClass('disabled hidden').data('disabled', 0);
                    selected_title.addClass('text-success').children('i').addClass('glyphicon-ok').removeClass('glyphicon-minus');
                } else {
                   btn_submit.addClass('disabled').data('disabled', 1).removeClass('hidden');    
                   if (t < 1) {
                       btn_submit.addClass('hidden');
                   }

                   selected_title.removeClass('text-success').children('i').addClass('glyphicon-minus').removeClass('glyphicon-ok');
                }
            }
            
            function selectOption(_rel, _st){
                var item = getOption(raw.groups, _rel);   

                if (raw.constraints.max === 0) {
                    $('#'+ _st + _rel).addClass('hidden');
                    item.order = my_selection.selection.length;
                    my_selection.selection.push(item);
                    showSelected();
                } else {
                    if (my_selection.selection.length < raw.constraints.max) {
                        $('#'+ _st + _rel).addClass('hidden');
                        item.order = my_selection.selection.length;
                        my_selection.selection.push(item);
                        showSelected();
                    }
                }
            } 

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
                        btn_submit.addClass('disabled').data('disabled', 1).addClass('hidden');
                    }
                }, 1000);
            }
            timer.html(tmpl('tmpl-timer', { time: timer.data('time'), exp: raw.expiry }));

            showSelected();
            search_input.html('');
            if (available.length > 0) {
                available.html(tmpl('tmpl-options-selectable', {list: raw.groups, used: my_selection.selection}));
                selected.height(available.height());
                available.height(available.height());
            }

            $('#options-user-clear').on('click', function(event){
                my_selection.selection = [];
                $('#options-available > div:hidden').removeClass('hidden');
                showSelected();
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
                my_selection.selection = $.grep(my_selection.selection, function(e){ return e.id != rel; });
                $('#option-'+ rel).removeClass('hidden');
                showSelected();
            });

            if ($('#options-user.sortable').length) {
                sorter = Sortable.create(selected[0], {
                    onEnd: function (event) {
                        var tmp = [];
                        selected.children('div').each(function(i, el) {
                            var item = $(el).data();
                            item.order = i;
                            tmp.push(item);
                        });
                        my_selection.selection = tmp;
                        selected.html(tmpl('tmpl-options-user', {list: my_selection.selection, limit: raw.constraints}));
                    }
                });
            }

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

            btn_submit.on('click', function(event) {
                event.preventDefault();

                if ((btn_submit.data('disabled') != '1') && (!btn_submit.hasClass('disabled'))) {

                    notify();
                    $.ajax({
                        url: "<?= addSession('setchoice.php'); ?>",
                        type: 'POST',
                        dataType: 'text',
                        data: {selection: my_selection.selection}
                    }).done(function(res) {

                        if (res === "{status: 1}") {
                            notify('success', 'Your selection was saved.');
                        } else {
                            notify('danger', '<strong>Error</strong> saving the selection failed, please contact the Vula help team.');
                        }
                    }).fail(function(err) {
                        notify('danger', '<strong>Error</strong> saving the selection failed, please contact the Vula help team.');
                    }).always(function() {
                    });
                }
            });
        });
    </script>

<?php if ($date_left > 0) { ?>

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
        o.list.sort(function(a, b) {
            if (a.order > b.order) {
                return 1;
            }
            if (a.order < b.order) {
                return -1;
            }
            return 0;
        });

        if (o.list.length > 0) {
        for (var i=0, len = o.list.length; i < len; i++) { 
				var item = o.list[i]; %}
        <div id="option-use-{%=item.id%}" data-id="{%=item.id%}"data-name="{%=item.name%}" data-order="{%=item.order%}">

            <a href="#" rel="{%=item.id%}" class="glyphicon glyphicon-remove"></a>
            <strong>{%=(i+1)%}<sup>{%=(num[i]?num[i]:'th')%}</sup></strong>&nbsp;&nbsp;{%=item.name%}
        </div>
        {% } } else { %}
            {% if (o.limit.max != 0) { %}
                {% if (o.limit.max == o.limit.min) { %}
                    <span>Please select <strong>{%=o.limit.max%}</strong> option{%=(o.limit.max > 1?'s':'')%}.</span>
                {% } else { %}
                    <span>Please select {%#(o.limit.min > 0 ? 'at least <strong>'+ o.limit.min +'</strong> option'+ (o.limit.min > 1?'s':'') +', maximum of ':'')%}
                    <strong>{%=o.limit.max%}</strong>{%=(o.limit.min == 0? ' option'+ (o.limit.max > 1?'s':''):'')%}.</span>
                {% } %}
            {% } else { %}
                <span>Please select your options.</span>
            {% } %}
        {% } %}
    </script>

    <script type="text/x-tmpl" id="tmpl-options-valid">
        <span class="small">
        {% if (o.limit.max != 0) { %}
            <br/>
            {% if (o.limit.max == o.limit.min) { %}
                (selected {%=o.len%} of {%=o.limit.max%})
            {% } else { %}
                (selected {%=o.len%} of {%=o.limit.max%}{%=(o.limit.min > 0 ? ', at least '+ o.limit.min:'')%})
            {% } %}
        {% } %}
        </span>
    </script>
<?php } else { ?>
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

        if (o.list.length > 0) {
        for (var i=0, len = o.list.length; i < len; i++) { 
				var item = o.list[i]; %}
        <div id="option-use-{%=item.id%}" data-id="{%=item.id%}"data-name="{%=item.name%}" data-order="{%=item.order%}">
            <strong>{%=(i+1)%}<sup>{%=(num[i]?num[i]:'th')%}</sup></strong>&nbsp;&nbsp;{%=item.name%}
        </div>
        {% } } else { %}<span>The selection process is closed, please discuss this with your lecturer.</span>{% } %}
    </script>

<?php } ?>

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
                result.push((i.length == 1 ? (el < 10 ? ' '+el : el) : el) + (i.length == 1 ? i : ' '+ (i+'').upFirst() + (el > 1 ? 's':'')));
            }
        });

        if (o.time <= 0) {
            print('<span class="label label-default">'+ moment(o.exp).calendar() +'</span>', true);
        } else {
            print('<span class="label '+ ( o.time <= 600 ? 'label-danger' : (o.time <= 1800 ? 'label-warning' : 'label-info'))+'"> Closing in '+ result.join('&nbsp;') +'</span>', true);
        }
    %}
    </script>

    <script type="text/x-tmpl" id="tmpl-notify">
        <div class="alert alert-{%=o.type%}">{%#o.msg%}</div>
    </script>

    <script type="text/x-tmpl" id="tmpl-options-title">
        <i class="glyphicon"></i> My Choices <span class="small">({%=o.len%})</span>
    </script>    

<?php
$OUTPUT->footerEnd();


