<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
$LAUNCH = LTIX::requireData();

$p = $CFG->dbprefix;

$json = Settings::linkGet('json');

// View
$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="css/app.css"/>
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
$OUTPUT->flashMessages();
?>
    <div id="application" class="container">

        <div class="row">
            <div class="col-xs-4">
                <h1>Add Groups</h1>
            </div>
            <div class="col-xs-8">
            </div>
        </div>

        <div class="row">
            <form method="post" action="" class="col-xs-7">
                <hr/>
                <span>Expiry: <input type="datetime-local" name="expiry_date" /></span>
                <ul id="options-user" class="options-container">
                  <li>
                    <span style="display: inline-block; width: 5rem;">ID</span>
                    <span style="display: inline-block; width: calc(100% - 13rem); text-align: center;">Group Name</span>
                    <span style="display: inline-block; width: 6rem; float: right; padding-right: 0.5rem;"># Users</span>
                  </li>
                  <li>
                    <input type="text" name="id[]" />
                    <input type="text" name="group[]" />
                    <input type="number" name="occupancy[]" />
                  </li>
                  <li>
                    <input type="text" name="id[]" />
                    <input type="text" name="group[]" />
                    <input type="number" name="occupancy[]" />
                  </li>
                  <li>
                    <input type="text" name="id[]" />
                    <input type="text" name="group[]" />
                    <input type="number" name="occupancy[]" />
                  </li>
                  <li><button id="addAnother" type="button" class="btn btn-success">Add Another</button></li>
                </ul>
              <button class="btn btn-success">Submit</button>  
              <button type="button" class="btn">Cancel</button>  
            </form>
        </div>


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
      $('form').on('submit', function(e) {
        var json = [];
        e.preventDefault();
        var proceed = true;
        $(this).find('li').each(function() {
          var groupName = $(this).find('input[name="group[]"]').val();
          var groupId = $(this).find('input[name="id[]"]').val();
          var occupancy = $(this).find('input[name="occupancy[]"]').val();
          if (groupId && json.map(entry => entry.id).indexOf(groupId) > -1) {
            $(this).find('input[name="id[]"]').css('border-color', 'red');
            proceed = false;
            return;
          }
          if (groupName && json.map(entry => entry.name).indexOf(groupName) > -1) {
            $(this).find('input[name="id[]"]').css('border-color', 'red');
            proceed = false;
            return;
          }
          if (groupName) {
            var group = {
                          id: groupId,
                          name: groupName, 
                          occupancy: !isNaN(occupancy) ? parseInt(occupancy) : 10
                        };
            json.push(group);
          }
        });
        if (!proceed) {
          alert('one or more group ids or names are not unique');
          return;
        }
        var posts = {groups: json};
        var expiry = $('input[name=expiry_date]').val();
        if (expiry) {
          posts.expiry = expiry;
        }
        $.ajax({
          url: '<?= addSection("addgroups.php"); ?>',
          type: 'POST',
          data: JSON.stringify({entries: posts})
        }).done(function(res) {
          console.log(res);
        }).fail(function(err) {
          console.log(err);
        }).always(function() {
        });
      });

      $('#addAnother').on('click', function(e) {
        var $newInput = $('form ul').find('li:nth-child(2)').clone();
        $newInput.find('input').each(function() {
          $(this).val('');
        });
        $newInput.addClass('added');
        $('form ul')[0].insertBefore($newInput[0], $('form ul')[0].querySelector('li:last-child'));
        setTimeout(function() {$newInput.removeClass('added');}, 0);
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



