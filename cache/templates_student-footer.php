<?php class_exists('Template') or exit; ?>
<?php foreach($scripts as $script): ?>
    <script src="<?php echo $script ?>" type="text/javascript"></script>
<?php endforeach; ?>

<script type="text/javascript">
    var selectedChoices = [];
    var currentDate = new Date();
    var today = currentDate.getFullYear() + '-' + ('0' + (currentDate.getMonth() + 1)).slice(-2) + '-' + ('0' + currentDate.getDate()).slice(-2);

    $(document).ready(function() {
        var _settings = <?php echo json_encode($allocation_settings) ?>;
        var _options = <?php echo json_encode($allocation_groups) ?>;
        var _choices = <?php echo json_encode($selected_groups) ?>;

        $('#tblGroups tbody').html(tmpl('tmpl-option-row', { settings: _settings, choices: _choices, options: _options }));

        $('#frmSubmitChoices').submit(function(event) {
            event.preventDefault();
            event.stopPropagation();

            const _form = $('#frmSubmitChoices'),
                  _btn  = $('#btnSubmitChoices'),
                  _data = new FormData(this);

            // for (var [key, value] of _data.entries()) {
            //     console.log(key + ': ' + value);
            // }

            _btn.html('<i class="fa fa-cog fa-spin"></i>&nbsp;&nbsp;Saving ...').addClass('disabled').attr('disabled', true);
            $.ajax({
                url: '<?php echo $add_choice_url ?>',
                type: 'POST',
                data: _data, dataType: 'json',
                processData: false,
                contentType: false,
            }).done(function(data) {
                if (data['success'] == 1) {
                    $().msgpopup({
                        text: 'Your choices were saved successfully.',
                        type: 'success',
                        time: 2000
                    });
                } else {
                    $().msgpopup({
                        text: data['msg'],
                        type: 'error',
                        time: 5000
                    });
                }
            }).fail(function() {
                $().msgpopup({
                    text: 'An error occurred while processing your submission. Please try again later.',
                    type: 'error',
                    time: 5000
                });
            }).always(function() {
                _btn.html('Submit Choices').removeClass('disabled').attr('disabled', false);
            });
        });
    });
</script>
