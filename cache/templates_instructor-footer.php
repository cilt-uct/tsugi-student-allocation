<?php class_exists('Template') or exit; ?>
<?php foreach($scripts as $script): ?>
    <script src="<?php echo $script ?>" type="text/javascript"></script>
<?php endforeach; ?>
<script type="text/javascript">
    function postResize() {
        parent.postMessage(JSON.stringify({
            subject: "iframeResize",
            width:  window.innerWidth,
            height: window.innerHeight
        }), "*");
    }
    window.onresize = postResize;
</script>
<script type="text/javascript">
    $(document).ready(function(){
        let _groups = <?php echo json_encode($allocation_groups) ?>;

    // Top Form ///////////////////////////////////////////////////////////////////////////////
        $('#help_dates').on('click', function(event){ event.preventDefault(); });

        // init html editor
        $('#instructions').trumbowyg({
            btns: [
                ['viewHTML'],
                ['undo', 'redo'], // Only supported in Blink browsers
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['mention'],
                ['fullscreen']
            ],
            plugins: {
                mention: {
                    source: [
                        {part: '{(ReleaseDate)}', name: 'Release Date'},
                        {part: '{(ClosingDate)}', name: 'Closing Date'},
                        {part: '{(SelectMin)}', name: 'Student Selection Minimum'},
                        {part: '{(SelectMax)}', name: 'Student Selection Maximum'},
                    ],
                    formatDropdownItem: function (item) {
                        return item.name;
                    },
                    formatResult: function (item) {
                        return item.part;
                    }
                }
            }
        });

        function checkAndSwapDates() {
            var releaseValue = $('#release-date').val();
            var closingValue = $('#closing-date').val();
            console.log(`releaseValue: ${releaseValue}`);
            console.log(`closingValue: ${closingValue}`);

            if (releaseValue && closingValue) {
                var releaseDate = new Date(releaseValue);
                var closingDate = new Date(closingValue);
                if (releaseDate > closingDate) {
                    // Swap the dates
                    $('#release-date').val(closingDate.toISOString().split('T')[0]);
                    $('#closing-date').val(releaseDate.toISOString().split('T')[0]);
                } else if (releaseDate == closingDate) {
                    releaseDate.setDate(releaseDate.getDate() - 1);
                    $('#release-date').val(releaseDate.toISOString().split('T')[0]);
                }
            }
        }

        $('#release-date, #closing-date').change(function() {
            checkAndSwapDates();
        });

        function checkAndSwapNumbers() {
            var minValue = parseFloat($('#min-selections').val());
            var maxValue = parseFloat($('#max-selections').val());

            if (minValue > maxValue) {
                // Swap the numbers
                $('#min-selections').val(maxValue);
                $('#max-selections').val(minValue);
            }
        }

        $('#min-selections, #max-selections').change(function() {
            checkAndSwapNumbers();
        });

    // Group Functions ////////////////////////////////////////////////////////////////////////
        function addRow(group) {
            var projectId = $('#tblGroupsList tbody tr').length + 1;
            projectId = ("000" + projectId).slice(-3);

            $('#tblGroupsList tbody').append(tmpl('tmpl-option-row',
                {'group_id': projectId, 'group_name': '', 'group_size': 1, 'num_students': 0, 'avail_spaces': 1, 'state': '<?php echo $state ?>'}
            ));

            updateGroupIds();
        }

        function updateGroupIds() {
            $('#tblGroupsList tbody tr').each(function(index) {
                var newGroupId = ("000" + (index + 1)).slice(-3);
                $(this).find('.group-id-cell span').text(newGroupId);
                $(this).find('.group-id-cell input').val(newGroupId);
            });
        }

        // Load initial setup
        _groups.forEach(function(group) {
                // var avail_spaces = group.group_size;
                // var num_students = 0;
                // _choices.forEach(function(choice) {
                //     if (choice.assigned === 1 && choice.group_id === group.group_id) {
                //         avail_spaces--;
                //         num_students++;
                //     }
                // });

                // group.avail_spaces = avail_spaces;
                // group.num_students = num_students;
                group.state = '<?php echo $state ?>';
            });

        $('#tblGroupsList tbody').html(tmpl('tmpl-options', _groups));

        // Make sortable
        $("#tblGroupsList tbody").sortable({
            items: 'tr',
            dropOnEmpty: false,
            start: function (G, ui) {
                ui.item.addClass("select");
            },
            stop: function (G, ui) {
                ui.item.removeClass("select");
                updateGroupIds();
            }
        });

        $('#btnAddRow').click(function(event) {
            event.preventDefault();
            addRow({ id: '', group_name: '', size: '', num_students: '', avail_spaces: '' });
        });

        $(document).on('click', '#tblGroupsList tbody .delete-row', function(event) {
            event.preventDefault();
            const row = $(this).closest('tr');
            row.remove();

            updateGroupIds();
        });

    // Form Submition /////////////////////////////////////////////////////////////////////////
        function validateForm() {
            groups = $('#tblGroupsList tbody tr').map(function(index, row) {
                    return {
                        'id': $(row).find("input[name='group-id[]']").val().trim(),
                        'title': $(row).find("input[name='group-title[]']").val().trim(),
                        'size': $(row).find("input[name='group-size[]']").val().trim()
                    }
                }).get();

            let empty_title = groups.filter(function(i) { return i.title===""; }),
                empty_size = groups.filter(function(i) { return i.size <=0 ; });

            if (empty_title.length > 0) {
                $('#error-groups').html(tmpl('tmpl-error-msg', {'msg': 'A title is required for all topics.'}));
                $.each(empty_title, function(i, el) { $(`td.group-id-cell span[data-id="${el['id']}"]`).addClass('error'); });
            } else {
                $(`td.group-id-cell span`).removeClass('error');
                $('#error-groups').html('');
            }

            // release is always before closing
            checkAndSwapDates();

            // set min and max selection values
            checkAndSwapNumbers();

            // NOT the errors
            return !(empty_title.length > 0); // additional checks can be added later
        }

        $('#frmAddGroups').submit(function(event) {
            event.preventDefault();
            event.stopPropagation();

            const _form = $('#frmAddGroups'),
                  _btn  = $('#btnSave'),
                  _data = new FormData(this);

            if (validateForm()) {
                _btn.html('<i class="fa fa-cog fa-spin"></i>&nbsp;&nbsp;Updating ...').addClass('disabled').attr('disabled', true);

                $.ajax({
                    url: '<?php echo $configure_url ?>',
                    type: 'POST',
                    data: _data, dataType: 'json',
                    processData: false,
                    contentType: false,
                }).done(function(data) {
                    if (data['success'] == 1) {
                        $().msgpopup({
                            text: 'Topic updated',
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
                        text: 'Could not update topic',
                        type: 'error',
                        time: 5000
                    });
                }).always(function() {
                    _btn.html('Update Topic').removeClass('disabled').attr('disabled', false);
                });

            } else {
                $().msgpopup({
                    text: 'There are some errors in the form.',
                    type: 'error',
                    time: 5000
                });
            }
        });

        $('#frmAddGroups #btnCancel').click(function(event) {
            event.preventDefault();
            location.reload();
        });

    // Student Selections /////////////////////////////////////////////////////////////////////
        let student_table = new DataTable('#tbl-allocation-choices', {
            ajax: {
                url: '<?php echo $get_student_selections_url ?>',
                type: 'POST',
                dataType: 'json',
                contentType: "application/json",
                data: function ( d ) {
                    return JSON.stringify($.extend( {}, d, {
                        // 'project_id': $('#term-select').val(), 'all': true,
                        'project_id': $('#project_id').val()
                    } ));
                },
                "dataSrc": function ( json ) {
                    // $('#status_display').html(tmpl('tmpl-status', json['counts']));
                    return json['data'];
                }
            },
            "columns": [
                { "data" : "EID" },
                { "data" : "name" },
                { "data" : "choices", orderable: false,
                    "createdCell": function (td, cellData, rowData, row, col) {
                        const choices = rowData['choices'].split(',').map(i => {
                                                    let k = i.split('~'); return {'rank': k[0], 'group': k[1], 'used': k[2] }
                                                });
                        $(td).html(choices.map(i => i['group']).join(', '));
                    }
                },
                { "data" : "assigned", orderable: false,
                    "createdCell": function (td, cellData, rowData, row, col) {
                        const choices = rowData['choices'].split(',').map(i => {
                                                    let k = i.split('~'); return {'rank': k[0], 'group': k[1], 'used': k[2] }
                                                }),
                            selected = choices.filter(i => i['used'] == 1);
                        $(td).html(selected.map(i => i['group']).join(', '));
                    }
                },
                { "data": "modified_at", render: function (data, type, row) {
                    if (data == null) {
                        return '';
                    }
                    return moment(data.replace(' GMT','')).fromNow();
                }}
            ],
            processing: true,
            serverSide: true,
            autoWidth: false,
            pageLength: 20,
            lengthMenu: [
                [20, 40, 60, 100],
                [20, 40, 60, 100],
            ],
        });

        student_table.on('draw.dt', function () {
            console.log( 'Table redrawn tbl-allocation-choices' );
            postResize();
        });
    });
</script>
