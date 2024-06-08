<?php class_exists('Template') or exit; ?>
<!-- <pre>
    <?php echo json_encode($current_project) ?>
</pre> -->
<section>
    <nav class="navbar navbar-default navbar-fixed-top" role="navigation" id="tsugi_tool_nav_bar">
        <div class="container-fluid">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="index.php" id="tool-title">Allocator</a>
            </div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li class="active">
                        <a data-toggle="tab" href="#add-groups-tab" id="topics">
                            <span class="fas fa-edit" aria-hidden="true"></span> Topics
                        </a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#view-choices-tab" id="selections">
                            <span class="fas fa-poll-h" aria-hidden="true"></span> Selections
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="tab-content">
        <div id="add-groups-tab" class="tab-pane fade in active">

            <form id="frmAddGroups">
                <input type="hidden" id="project_id" name="project_id" value="<?php echo $current_project['project_id'] ?>" />

                <div class="row row-padding" style="margin-top: 2rem;">
                    <div class="col-xs-12" style="margin-top: 2rem;">
                        <label for="instructions" class="form-label not-required">Instructions:</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="3">
                            <?php echo $current_project['instructions'] ?>
                        </textarea>
                    </div>
                </div>
                <div class="row row-padding">
                    <div class="col-sm-4 col-xs-12">
                        <div>
                            <label class="form-label"># Selections per student:</label>
                        </div>
                        <span class="form-inline label-top">
                            <label class="form-label">Min</label>
                            <input type="number" class="form-control small-input"
                                                    placeholder="Min" id="min-selections" name="min-selections"
                                                    style="font-weight: bold; text-align: center;"
                                                    data-val="<?php echo $current_project['min_selections'] ?>"
                                                    value="<?php echo $current_project['min_selections'] ?>"/>
                        </span>
                        <span style="padding-left: 0.5rem; position: relative; top: -7px; color: #ccc;"> to </span>
                        <span class="form-inline label-top">
                            <label class="form-label">Max</label>
                            <input type="number" class="form-control small-input"
                                                placeholder="Max" id="max-selections" name="max-selections"
                                                style="font-weight: bold; text-align: center;"
                                                data-val="<?php echo $current_project['max_selections'] ?>"
                                                value="<?php echo $current_project['max_selections'] ?>"/>
                        </span>
                    </div>
                    <div class="col-sm-8 col-xs-12">
                        <div>
                            <label class="form-label">Dates:</label>
                        </div>
                        <span class="form-inline label-top">
                            <label for="release-date" class="form-label">Release Date</label>
                            <input type="date" class="form-control" id="release-date" name="release-date"
                                        data-val="<?php echo isset($current_project['release_date']) ? explode(' ',$current_project['release_date'])[0] : '' ?>"
                                        value="<?php echo isset($current_project['release_date']) ? explode(' ',$current_project['release_date'])[0] : '' ?>"/>
                        </span>
                        <span class="form-inline label-top">
                            <label for="closing-date" class="form-label">Closing Date</label>
                            <input type="date" class="form-control" id="closing-date" name="closing-date"
                                        data-val="<?php echo isset($current_project['closing_date']) ? explode(' ',$current_project['closing_date'])[0] : '' ?>"
                                        value="<?php echo isset($current_project['closing_date']) ? explode(' ',$current_project['closing_date'])[0] : '' ?>"/>
                        </span>
                        <span>
                            <a href="#" id="help_dates" class="csstooltip"
                                style="padding-left: 0.5rem; position: relative; top: -7px;"
                                title="The 'Release Date' is when the selection will become visible to students and project, after the 'Closing Date' the form becomes read-only.">
                                <i class="fas fa-info-circle"></i>
                            </a>
                        </span>
                    </div>
                </div>
                <br/>
                <h4>
                    <span class="fas fa-edit" aria-hidden="true"></span>
                    OPTIONS
                    <button type="button" id="btnAddRow" name="btnAddRow" class="btn btn-primary"
                    style="margin-left: 3rem; font-size: 1rem; padding: 0.3rem 0.6rem;">
                    <i class="fas fa-plus"></i> Add Option</button>
                    <?php if ($participants > 0) { ?>
                        <small class="alert alert-warning"
                                style="font-weight: normal; padding: 0.4rem 0.8rem 0.4rem 0.4rem; margin-left: 2rem;">
                            <i class="fas fa-exclamation-circle"></i>
                            &nbsp;Students made selection based on the options, changing the options here might affect their choices...
                        </small>
                    <?php } ?>
                </h4>
                <table class="table table-bordered table-rounded" id="tblGroupsList">
                    <thead class="bg-info">
                        <tr>
                            <th class="text-center">ID</th>
                            <th class="text-center">Title</th>
                            <th class="text-center">Size</th>
                            <?php if ($state != "open") { ?>
                                <th class="text-center"># Students</th>
                                <th class="text-center">Avail Spaces</th>
                            <?php } else { ?>
                                <th class="text-center">Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <td <?php if ($state != "open") { ?>colspan="4"<?php } else { ?>colspan="5"<?php } ?> id="error-groups"></td>
                    </tfoot>
                </table>
                <hr/>
                <div class="form-group">
                    <button type="submit" class="btn btn-success" id="btnSave">Update Topic</button>
                    <a href="#" id="btnCancel">Cancel</a>
                </div>
            </form>
        </div>
        <div id="view-choices-tab" class="tab-pane fade in">

            <div id="divSelections">
                <!-- <button type = "button" id="run_allocation" name="run_allocation" class="btn btn-primary pull-right">Run Allocation</button> -->
                <!-- <br/><br/> -->
                <h4 id="selections_title"><span><i class='far fa-hand-point-left' aria-hidden="true"></i></span> SELECTIONS</h4>

                <table class="table table-bordered table-rounded" id="tbl-allocation-choices">
                    <thead class="bg-info">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Project Choices</th>
                            <th>Allocated to</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div id="divMemberships" style="display:none;">
                <button type="button" class="btn download btn-sm pull-right" id="btnDownloadMemberships">
                    <span class="glyphicon glyphicon-download"></span> Download CSV
                </button>

                <br/><br/>

                <h3 id="memberships_title"><span><i class="fa fa-group"></i></span> MEMBERSHIPS</h3>
                <table class="table table-bordered table-rounded" id="tbl-user-memberships">
                    <thead class="bg-info">
                        <tr>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Project</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>

                <div class="modal fade" id="assignModal" tabindex="-1" role="dialog" aria-labelledby="assignModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <h5 class="modal-title" id="assignModalLabel">Assign Student</h5>
                            </div>
                            <div class="modal-body">
                                <form id="frmAssign" method="POST">
                                    <label for="studentId">Student ID: </label>
                                    <span id="studentIdDisplay"></span></p>
                                    <input type="hidden" class="form-control" id="studentId" name="studentId"/>
                                    <div class="form-group">
                                        <label for="assignedGroup">Select Group:</label>
                                        <select class="form-control" id="assignedGroup" name="assignedGroup" required></select>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="cancel" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success" id="btnAssign" name="btnAssign">Assign</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>