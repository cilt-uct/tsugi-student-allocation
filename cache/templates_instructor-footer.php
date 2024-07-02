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
    jQuery.fn.exists = function(){ return this.length > 0; }

    function getObj(id, arr, key) { key = key || 'id'; var o = null; $.each(arr, function (i, el) { if (el[key] == id) { o=el; return; } }); return o; };
    function cleanObj(o) { Object.keys(o).forEach(key => o[key] === undefined ? delete o[key] : {}); return o; }

    let _groups = <?php echo json_encode($allocation_groups) ?>;

    function display_groups() {
        // display groups / options
        _groups.forEach(function(group) {
            group.state = '<?php echo $state ?>';
        });

        $('#tblGroupsList tbody').html(tmpl('tmpl-options', _groups));
    }

    async function fetch_groups() {
        try {
            let response = await $.ajax({
                url: '<?php echo $get_groups_url ?>', // Replace with your server URL
                method: 'GET',
                dataType: 'json'
            });

            _groups = response.data;
            display_groups();
        } catch (error) {
            console.log(error.statusText);
        }
    }

    $(document).ready(function(){

        $('#selections').on('click', function(event) {
            $('#tbl-allocation-choices').DataTable().ajax.reload();
        });
        $('#topics').on('click', function(event) {
            console.log('clicked topics');
        });

        display_groups();

    // Student Selections /////////////////////////////////////////////////////////////////////
        function datatable_button(clicked) {
            let _state = $('#project_state').val(),
                _btn = $('.assign-btn');

            // should the button be active
            _btn.removeClass('invisible');
            if (new Date($('#closing-date').val() + 'T23:59:59') < new Date()) {
                _btn.attr('disabled', false);
            } else{
                _btn.attr('disabled', true);
            }

            switch(_state) {
                case 'open':
                    _btn.html('<span>Start Allocation</span>')
                        .attr('title', 'Start the process of allocating students...');
                    if (clicked) {
                        _btn.html('<i class="fa fa-cog fa-spin"></i>&nbsp;&nbsp;Starting ...').addClass('disabled').attr('disabled', true);

                        $.ajax({
                            url: '<?php echo $set_state_url ?>',
                            type: 'POST',
                            data:JSON.stringify({ "tool-state": "waiting" }),
                            contentType: 'application/json'
                        }).done(function(data) {
                            if (data['success'] == 1) {
                                location.reload();
                            } else {
                                $().msgpopup({
                                    text: data['data'],
                                    type: 'error',
                                    time: 5000
                                });
                            }
                        }).fail(function() {
                            $().msgpopup({
                                text: 'Could not update',
                                type: 'error',
                                time: 5000
                            });
                        }).always(function() {
                            _btn.html('<span>Start Allocation</span>').removeClass('disabled').attr('disabled', false);
                        });
                    }
                    break;

                case 'waiting':
                    _btn.html('<span>Awaiting Allocation ...</span>').addClass('disabled').attr('disabled', true)
                        .attr('title', 'Waiting for script to allocate students to topics.');
                    break;

                case 'running':
                    _btn.html('<span>Running Allocation ...</span>').addClass('disabled').attr('disabled', true)
                        .attr('title', 'Allocating students to topics.');
                    break;

                case 'review':
                    _btn.html('<span>Complete Review</span>').removeClass('disabled').attr('disabled', false)
                        .attr('title', 'Complete review and start assigning.');
                    if (clicked) {
                        _btn.html('<i class="fa fa-cog fa-spin"></i>&nbsp;&nbsp;Start Assignment...').addClass('disabled').attr('disabled', true);

                        $.ajax({
                            url: '<?php echo $set_state_url ?>',
                            type: 'POST',
                            data:JSON.stringify({ "tool-state": "reviewed" }),
                            contentType: 'application/json'
                        }).done(function(data) {
                            if (data['success'] == 1) {
                                location.reload();
                            } else {
                                $().msgpopup({
                                    text: data['data'],
                                    type: 'error',
                                    time: 5000
                                });
                            }
                        }).fail(function() {
                            $().msgpopup({
                                text: 'Could not update',
                                type: 'error',
                                time: 5000
                            });
                        }).always(function() {
                            _btn.html('<span>Complete Review</span>').removeClass('disabled').attr('disabled', false);
                        });
                    }
                    break;

                case 'reviewed':
                    _btn.html('<span>Awaiting Assignment ...</span>').addClass('disabled').attr('disabled', true)
                        .attr('title', 'Waiting for script to assign students to topics in LMS.');
                    break;

                case 'assigning':
                    _btn.html('<span>Running Assignment ...</span>').addClass('disabled').attr('disabled', true)
                        .attr('title', 'Assigning students to topics in LMS.');
                    break;

                case 'error':
                    _btn.html('<span>Error</span>').removeClass('btn-secondary btn-primary')
                        .addClass('disabled btn-danger').attr('disabled', true)
                        .attr('title', 'There is an error in processing this project - please contact CILT Help Team');
                    break;
                case 'assigned':
                default:
                    _btn.addClass('invisible');
            }
        }

        let student_table = new DataTable('#tbl-allocation-choices', {
            ajax: {
                url: '<?php echo $get_student_selections_url ?>',
                type: 'POST',
                dataType: 'json',
                contentType: "application/json",
                data: function ( d ) {
                    return JSON.stringify($.extend( {}, d, {
                        'status': ($('#selected_status').exists ? $('#selected_status').val() : ''),
                        'project_id': $('#project_id').val()
                    } ));
                },
                "dataSrc": function ( json ) {
                    // update counts
                    total = json['counts'].reduce((t, i) => t + i.c, 0);
                    $('#status_display').html(tmpl('tmpl-status', {
                                                    'total': total,
                                                    'selected': $('#selected_status').exists ? $('#selected_status').val() : '',
                                                    'counts': json['counts']
                                                }));

                    // update buttons
                    datatable_button(false);
                    $('.download-btn').removeClass('invisible');

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
                            _state = $('#project_state').val(),
                            selected = choices.filter(i => i['used'] == 1),
                            selected_st = selected.map(i => i['group']).join(', ');

                        if (_state == 'review') {
                            $(td).addClass('edit')
                                .html(`<span>${selected_st}</span>
                                        <button id="change_${rowData['EID']}"
                                            data-id="${rowData['EID']}"
                                            data-assigned="${selected_st}"
                                            data-name="${rowData['name']}">
                                        <i class="fas fa-user-edit"></i></button>`)
                        } else {
                            $(td).html(selected_st);
                        }
                    }
                },
                { "data": "modified_at", render: function (data, type, row) {
                    if ((data == null) || (data == '')) {
                        return '';
                    }
                    return moment(data.replace(' GMT','')).fromNow();
                }}
            ],
            dom: 'lBfrtip',
            buttons: [ {
                            text: 'Run',
                            className: 'btn-primary invisible assign-btn',
                            titleAttr: '...',
                            action: function ( e, dt, node, config ) {
                                datatable_button(true);
                            }
                        },
                        {
                            text: '<i class="fas fa-file-download"></i>&nbsp;&nbsp;Download',
                            className: 'invisible download-btn',
                            titleAttr: 'Download all the assignments as a CSV',
                            action: function ( e, dt, node, config ) {

                            }
                        }
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
            // console.log( 'Table redrawn tbl-allocation-choices' );
            postResize();
        });

        $('.nav-pills').on('click','.nav-link', function(event){
            event.preventDefault();
            $('#selected_status').val($(this).attr('rel'));
            $('#tbl-allocation-choices').DataTable().ajax.reload();
        });

        postResize();
    });
</script>
<?php if ($state == 'review') { ?>
<script type="text/javascript">
    async function fetch_students_per_group(group_id) {
        try {
            let response = await $.ajax({
                url: '<?php echo $get_student_per_group_url ?>', // Replace with your server URL
                method: 'POST',
                data: { 'group_id': group_id },
                dataType: 'json'
            });

            const selection_changed = function($left, $right, $options) {
                $('#selected_group_total').html(tmpl('tmpl-multi-select-total',{
                                                        'assigned': $('#group_assignment_select option').length,
                                                        'size': $('#selected_group_size').val()
                                                    }));

                $('#selected_group_allocation').val($('#group_assignment_select option').map(function(i, opt) { return $(opt).val() })
                                                                                        .get().join(','))
            };

            if (response.success == 1) {
                $('#group_assignments').html(tmpl('tmpl-multi-select', { 'group': getObj(group_id, _groups, 'group_id'), 'students': response.data}));

                $('#group_assignment_select').multiselect({
                    search: {
                        left: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
                        right: '<input type="text" name="q" class="form-control" placeholder="Search..." />',
                    },
                    fireSearch: function(value) {
                        return value.length >= 2;
                    },
                    afterMoveToRight: selection_changed,
                    afterMoveToLeft: selection_changed
                });
            } else {
                $('#group_assignments').html('<div class="alert alert-danger" role="alert">Could not load student selection.</div>');
            }

        } catch (error) {
            console.log(error.statusText);
        }
    }

    $(document).ready(function(){

        $('#tbl-allocation-choices').on('click', 'td.edit>button', function(event) {
            event.preventDefault();
            const _btn = $(this),
                  _select = $('#assignedGroup'),
                  _submit_btn = $('#btnAssign');

            $('#studentId').val(_btn.data('id'));
            $('#studentDisplay').text(`${_btn.data('id')} - ${_btn.data('name')}`);
            _select.empty();
            _select.append($('<option>', {value: '', text: 'Select from list'}));

            _groups.forEach(function(group) {
                _select.append($('<option>', {
                    value: group.group_id,
                    text: group.group_id + ': ' + group.group_name
                }));
            });
            _select.val(_btn.data('assigned'));
            _select.data('start', _btn.data('assigned'));

            _submit_btn.addClass('disabled').attr('disabled', true);

            $('#groupInfo').html('');
            $('#assignModal').modal('show');
        });

        $('#assignedGroup').on('change', function(event) {
            const _select = $(this),
                  _group_status = $('#groupInfo'),
                  _submit_btn = $('#btnAssign');

            if (_select.val() === _select.data('start')) {
                _group_status.html('<div class="alert" role="alert">Already assigned to this choice</div>');
                _submit_btn.addClass('disabled').attr('disabled', true);
                return;
            }

            _group_status.html('<i class="fa fa-cog fa-spin"></i>&nbsp;&nbsp;Checking ...');
            $.ajax({
                url: '<?php echo $set_student_assign_url ?>',
                type: 'POST',
                data: {'type': 'check', 'assignedGroup': _select.val() },
                dataType: "json"
            }).done(function(data) {
                let final = parseInt(data['data']['c'], 10) + 1,
                    size = parseInt(data['data']['group_size'], 10);

                _submit_btn.removeClass('disabled').attr('disabled', false);
                _group_status.html(final <= size ?
                                    `<div class="alert alert-info" role="alert"><i class="fas fa-check"></i> Choice Allocation: ${final}/${size}</div>` :
                                    `<div class="alert alert-danger" role="alert"><i class="fas fa-times"></i> Choice Allocation: <b>${final}</b>/${size}</div>`);

            }).fail(function(jqXHR, textStatus) {
                _group_status.html('');
                $().msgpopup({ text: textStatus, type: 'error', time: 3000});
                console.error(jqXHR.responseText);
            });
        })

        $('#frmAssign').submit(function(event) {
            event.preventDefault();
            var formData = new FormData(this);

            $.ajax({
                url: '<?php echo $set_student_assign_url ?>',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json"
            }).done(function(data) {
                console.log(typeof(data));
                if (data['success'] == 1) {
                    $().msgpopup({
                        text: 'Allocation updated',
                        type: 'success',
                        time: 2000
                    });
                    $('#assignModal').modal('hide');
                    $('#tbl-allocation-choices').DataTable().search($('#studentId').val()).draw();
                    // location.reload();
                    fetch_groups();

                } else{
                    $().msgpopup({ text: data['data'], type: 'error', time: 3000});
                }
            }).fail(function(jqXHR, textStatus) {
                $().msgpopup({ text: JSON.parse(jqXHR.responseText).error, type: 'error', time: 3000});
                console.error(JSON.parse(jqXHR.responseText).error);
            });
        });

        $('#tblGroupsList').on('click', 'td.edit>button', function(event) {
            event.preventDefault();
            const _btn = $(this);

            fetch_students_per_group(_btn.data('id'));
            $('#groupAssignModal').modal('show');
        });


        $('#frmGroupAssign').submit(function(event) {
            event.preventDefault();
            var formData = new FormData(this);
            // console.log(JSON.stringify(Object.fromEntries(new FormData(this))));

            $.ajax({
                url: '<?php echo $set_group_student_assignment_url ?>',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json"
            }).done(function(data) {
                console.log(data);
                if (data['success'] == 1) {
                    $().msgpopup({
                        text: 'Allocation updated',
                        type: 'success',
                        time: 2000
                    });
                    $('#groupAssignModal').modal('hide');
                    $('#tbl-allocation-choices').DataTable().ajax.reload();
                    fetch_groups();

                } else{
                    $().msgpopup({ text: data['data'], type: 'error', time: 3000});
                }
            }).fail(function(jqXHR, textStatus) {
                $().msgpopup({ text: JSON.parse(jqXHR.responseText).error, type: 'error', time: 3000});
                console.error(JSON.parse(jqXHR.responseText).error);
            });
        });

    });
</script>
<?php } ?>
<?php if ($state == 'open') { ?>
<script type="text/javascript">
    $(document).ready(function(){

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

            if (releaseValue && closingValue) {
                var releaseDate = new Date(releaseValue);
                var closingDate = new Date(closingValue);

                if (releaseDate > closingDate) {
                    // Swap the dates
                    $('#release-date').val(closingDate.toISOString().split('T')[0]);
                    $('#closing-date').val(releaseDate.toISOString().split('T')[0]);
                } else if (releaseValue == closingValue) {
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
            postResize();
        }

        function updateGroupIds() {
            $('#tblGroupsList tbody tr').each(function(index) {
                var newGroupId = ("000" + (index + 1)).slice(-3);
                $(this).find('.group-id-cell span').text(newGroupId);
                $(this).find('.group-id-cell input').val(newGroupId);
            });
        }

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
                    url: '<?php echo $set_configure_url ?>',
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
                        _groups = data['parameters']['groups'];
                        if ($('#project_state').val() !== data['parameters']['state']) {
                            // this page has transitioned to another state reload it
                            location.reload();
                        }
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
    });
</script>
<?php } ?>
