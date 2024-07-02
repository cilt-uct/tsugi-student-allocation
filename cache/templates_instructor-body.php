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
                <a class="navbar-brand" href="index.php" id="tool-title">
                    <i class="fas fa-tasks"></i> Allocator
                </a>
            </div>
            <div class="navbar-collapse collapse">
                <ul class="nav navbar-nav navbar-right">
                    <li <?php if ($state == 'open') { ?>class="active"<?php } ?>>
                        <a data-toggle="tab" href="#add-groups-tab" id="topics">
                            <span class="fas fa-edit" aria-hidden="true"></span> Topics
                        </a>
                    </li>
                    <li <?php if ($state != 'open') { ?>class="active"<?php } ?>>
                        <a data-toggle="tab" href="#view-choices-tab" id="selections">
                            <span class="fas fa-poll-h" aria-hidden="true"></span> Selections
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="row row-padding" style="margin-top: 2rem;">
        <div class="col-lg-8"  style="margin-top: 2rem;">
            <div class="tab-content">
                <div id="add-groups-tab" class="tab-pane fade <?php if ($state == 'open') { ?>in active<?php } ?>">
                    <form id="frmAddGroups">
                        <input type="hidden" id="project_id" name="project_id" value="<?php echo $current_project['project_id'] ?>" />
                        <input type="hidden" id="project_state" name="project_state" value="<?php echo $state ?>" />

                        <div class="row row-padding">
                            <div class="col-xs-12">
                                <?php if ($state == 'open') { ?>
                                    <label for="instructions" class="form-label not-required">Instructions:</label>
                                    <textarea class="form-control" id="instructions" name="instructions" rows="3">
                                        <?php echo $current_project['instructions'] ?>
                                    </textarea>
                                <?php } else { ?>
                                    <div class="css_accordion">
                                        <input type="checkbox" name="accordion-1" id="cb1">
                                        <label for="cb1" class="css_accordionlabel">
                                            <span>Instructions:</span>
                                        </label>

                                        <div class="css_accordioncontent">
                                            <?php echo $current_project['instructions'] ?>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="row row-padding">
                            <div class="col-sm-5 col-xs-12">
                                <div>
                                    <label class="form-label"># Selections per student:</label>
                                </div>
                                <span class="form-inline label-top">
                                    <label class="form-label">Min</label>
                                    <input type="number" class="form-control small-input"
                                                            placeholder="Min" id="min-selections" name="min-selections"
                                                            min="0"
                                                            style="font-weight: bold; text-align: center;"
                                                            data-val="<?php echo $current_project['min_selections'] ?>"
                                                            value="<?php echo $current_project['min_selections'] ?>"
                                                            <?php if ($state != 'open') { ?>readonly<?php } ?>/>
                                </span>
                                <span style="padding-left: 0.5rem; position: relative; top: -7px; color: #ccc;"> to </span>
                                <span class="form-inline label-top">
                                    <label class="form-label">Max</label>
                                    <input type="number" class="form-control small-input"
                                                        min="0"
                                                        placeholder="Max" id="max-selections" name="max-selections"
                                                        style="font-weight: bold; text-align: center;"
                                                        data-val="<?php echo $current_project['max_selections'] ?>"
                                                        value="<?php echo $current_project['max_selections'] ?>"
                                                        <?php if ($state != 'open') { ?>readonly<?php } ?>/>
                                </span>
                            </div>
                            <div class="col-sm-7 col-xs-12">
                                <div>
                                    <label class="form-label">Dates:</label>
                                </div>
                                <span class="form-inline label-top">
                                    <label for="release-date" class="form-label">Release Date</label>
                                    <input type="date" class="form-control" id="release-date" name="release-date"
                                                data-val="<?php echo isset($current_project['release_date']) ? explode(' ',$current_project['release_date'])[0] : '' ?>"
                                                value="<?php echo isset($current_project['release_date']) ? explode(' ',$current_project['release_date'])[0] : '' ?>"
                                                <?php if ($state != 'open') { ?>readonly<?php } ?>/>
                                </span>
                                <span class="form-inline label-top">
                                    <label for="closing-date" class="form-label">Closing Date</label>
                                    <input type="date" class="form-control" id="closing-date" name="closing-date"
                                                data-val="<?php echo isset($current_project['closing_date']) ? explode(' ',$current_project['closing_date'])[0] : '' ?>"
                                                value="<?php echo isset($current_project['closing_date']) ? explode(' ',$current_project['closing_date'])[0] : '' ?>"
                                                <?php if ($state != 'open') { ?>readonly<?php } ?>/>
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
                        <div style="display: flex; align-items: center;">
                            <div style="flex: none;">
                                <h4><i class="fas fa-edit" aria-hidden="true"></i> OPTIONS</h4>
                            </div>
                            <?php if ($state == 'open') { ?>
                            <div>
                                <button type="button" id="btnAddRow" name="btnAddRow" class="btn btn-primary"
                                    style="margin-left: 3rem; font-size: 1rem; padding: 0.3rem 0.6rem;">
                                    <i class="fas fa-plus"></i> Add Option
                                </button>
                            </div>
                            <div>
                                <?php if ($participants > 0) { ?>
                                    <div class="alert alert-warning" style="display: inline-flex;font-weight: normal;padding: 0.4rem 0.8rem 0.4rem 0.4rem;margin-left: 2rem;align-items: center;justify-content: space-evenly;margin-bottom: 0.3rem;">
                                        <i class="fas fa-exclamation-circle" style="padding: 1rem;"></i>
                                        <small style="line-height:1.4rem">Students made selection based on the options, <br/> changing the options here might affect their choices...</small>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php } ?>
                        </div>
                        <table class="table table-bordered table-rounded" id="tblGroupsList">
                            <thead class="bg-info">
                                <tr>
                                    <th class="text-center">ID</th>
                                    <th class="text-center">Title</th>
                                    <th class="text-center">Size</th>
                                    <?php if ($state != "open") { ?>
                                        <th class="text-center"># Students</th>
                                        <th class="text-center">Avail Spaces</th>
                                    <?php } ?>
                                    <?php if (($state == "open") || ($state == 'review')) { ?>
                                    <th class="text-center">Action</th>
                                    <?php } ?>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <?php if (($state == "open") || ($state == 'review')) { ?>
                            <tfoot>
                                <tr>
                                    <td <?php if ($state != "open") { ?>colspan="4"<?php } else { ?>colspan="6"<?php } ?> id="error-groups"></td>
                                </tr>
                            </tfoot>
                            <?php } ?>
                        </table>
                        <?php if ($state == 'open') { ?>
                            <hr/>
                            <div class="form-group">
                                <button type="submit" class="btn btn-success" id="btnSave">Update Topic</button>
                                <a href="#" id="btnCancel">Cancel</a>
                            </div>
                        <?php } ?>
                    </form>
                </div>
                <div id="view-choices-tab" class="tab-pane fade <?php if ($state != 'open') { ?>in active<?php } ?>"">
                    <div id="divSelections">
                        <div style="margin-bottom: 1rem;">
                            <input type="hidden" id="selected_status" value="" />
                            <ul class="nav nav-pills justify-content-start" id="status_display"></ul>
                        </div>
                        <table class="table table-bordered table-rounded" id="tbl-allocation-choices">
                            <thead class="bg-info">
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Choices</th>
                                    <th>Allocated to</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>


                </div>
            </div>
        </div>
    </div>

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
                        <input type="hidden" id="studentId" name="studentId"/>
                        <input type="hidden" id="type" name="type" value="change"/>

                        <label for="studentId">Student: </label>
                        <span id="studentDisplay"></span>

                        <div class="form-group">
                            <label for="assignedGroup">Select Option:</label>
                            <select class="form-control" id="assignedGroup" name="assignedGroup" required></select>
                        </div>

                        <div class="modal-footer">
                            <div id="groupInfo"></div>
                            <button type="cancel" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success" id="btnAssign" name="btnAssign">Assign</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="groupAssignModal" tabindex="-1" role="dialog" aria-labelledby="groupAssignModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="frmGroupAssign" method="POST">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h5 class="modal-title" id="groupAssignModalLabel">Assign Students to Option</h5>
                    </div>
                    <div class="modal-body" id="group_assignments">
                    </div>
                    <div class="modal-footer">
                        <div id="groupInfo"></div>
                        <button type="cancel" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success" id="btnUpdateGroup" name="btnUpdateGroup">Update Allocation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
