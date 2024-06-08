<?php class_exists('Template') or exit; ?>
<section>
    <div id="student-view" class="container mt-5">
        <div id="tool-details" class="padded">
            <div id="divInstructions">
                <div id="instructions"><?php echo $current_project['instructions'] ?></div>
            </div>
            <div class="row">
                <div style="display: none;" class="text-center" id="assigned_div"></div>
                <div id="projects-container">
                    <br/>
                    <h4><span><i class='far fa-hand-point-left' aria-hidden="true"></i></span> My Choices</h4>

                    <form id="frmSubmitChoices" method="post">
                        <input type="hidden" id="project_id" name="project_id" value="<?php echo $current_project['project_id'] ?>" />

                        <table class="table table-bordered table-rounded" id="tblGroups">
                            <thead class="bg-info">
                                <tr>
                                    <th class="text-center">Choice #</th>
                                    <th class="text-center">Title</th>
                                </tr>
                            </thead>
                            <tbody id="project-list"></tbody>
                        </table>
                        <br/>

                        <button type="submit" id="btnSubmitChoices" name="btnSubmitChoices" class="btn btn-success">Submit Choices</button>
                        <b><span id="error-message" name="error-message" class="text-danger"></span></b>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>