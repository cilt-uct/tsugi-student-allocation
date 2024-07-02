<?php
namespace Allocation\DAO;

class AllocationDAO {

    private $PDOX;
    private $p;
    private $link_id;
    private $site_id;
    private $user_id;
    private $EID;

    public function __construct($PDOX, $p, $link_id, $site_id, $user_id, $EID, $role) {
        $this->PDOX = $PDOX;
        $this->p = $p;
        $this->link_id = $link_id;
        $this->site_id = $site_id;
        $this->user_id = $user_id;
        $this->EID = $EID;

        $this->PDOX->queryDie("REPLACE INTO {$this->p}allocation_user (user_id, EID, role) VALUES (:user_id, :EID, :role)",
                        array(':user_id' => $user_id, ':EID' => $EID, ':role' => $role));

        if ($this->getSite() === false) {
            # create a default project to save groups into
            $this->createSite();
        }

        # Check to see if there is a project for this link and site
        if ($this->getProject() === false) {
            # create a default project to save groups into
            $this->createProject();
        }
    }

    function getSite() {
        $query = "SELECT * FROM {$this->p}allocation_site WHERE link_id = :link_id and site_id = :site_id;";
        $arr = array(':link_id' => $this->link_id, ':site_id' => $this->site_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function createSite() {
        $query = "INSERT INTO {$this->p}allocation_site (link_id, site_id) VALUES (:link_id, :site_id)";
        $arr = array(':link_id' => $this->link_id, ':site_id' => $this->site_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getProject() {
        $query = "SELECT * FROM {$this->p}allocation_project WHERE link_id = :link_id and site_id = :site_id;";
        $arr = array(':link_id' => $this->link_id, ':site_id' => $this->site_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function createProject() {
        // Read the file into a string
        $htmlContent = file_get_contents("templates/default_instructions.html");

        $query = "INSERT INTO {$this->p}allocation_project
                            (link_id, site_id, instructions, created_by, modified_by) VALUES
                            (:link_id, :site_id, :instructions, :user_id, :user_id)";
        $arr = array(':link_id' => $this->link_id, ':site_id' => $this->site_id,
                        ':instructions' => $htmlContent,
                        ':user_id' => $this->user_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getGroups($project_id) {
        $query = "SELECT `group`.`group_id`, `group`.`group_name`, `group`.`group_size`,
                            ifnull((select sum(`choice`.`assigned`) from {$this->p}allocation_choice `choice`
                                    where `group`.group_id = `choice`.group_id and `group`.project_id = `choice`.project_id), 0)  as 'assigned'
                        FROM {$this->p}allocation_group `group`
                    WHERE `group`.`project_id` = :project_id ORDER BY `group`.`group_id` ASC;";

        $arr = array(':project_id' => $project_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function updateAllocation($project_id, $link_id, $user_id, $site_id,
                                    $instructions,
                                    $min_selections, $max_selections, $release_date, $closing_date,
                                    $groups) {

        try {
            // Insert / Update allocation settings
            $this->PDOX->queryDie("UPDATE {$this->p}allocation_project
                                        SET
                                        `link_id` = :linkId,
                                        `site_id` = :siteId,
                                        `instructions` = :instructions,
                                        `min_selections` = :min,
                                        `max_selections` = :max,
                                        `release_date` = :release,
                                        `closing_date` = :closing,
                                        `modified_by` = :user_id
                                        WHERE `project_id` = :project_id;",
                    array(':project_id' => $project_id,
                            ':linkId' => $link_id, ':siteId' => $site_id,
                            ':instructions' => $instructions,
                            ':min' => $min_selections, ':max' => $max_selections,
                            ':release' => $release_date, ':closing' => $closing_date,
                            ':user_id' => $user_id));

            // Delete the row if marked for deletion
            $this->PDOX->queryDie("DELETE FROM {$this->p}allocation_group WHERE `project_id` = :project_id",
                                    array(':project_id' => $project_id));

            // Insert / Update allocation groups
            foreach ($groups as $group) {
                $this->PDOX->queryDie("INSERT INTO allocation_group
                                (`group_id`, `project_id`,
                                `group_name`,
                                `group_size`, `created_by`, `modified_by`)
                                VALUES (:group_id, :project_id,
                                        :group_name,
                                        :group_size, :user_id, :user_id)",
                            array(':group_id' => $group['id'], ':project_id' => $project_id,
                                ':group_name' => $group['title'],
                                ':group_size' => $group['size'],
                                ':user_id' => $user_id));
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    function getChoices($project_id, $user_id = NULL) {

        $query = "SELECT `choice`.group_id, `choice`.choice_rank, `choice`.assigned,
                            `group`.`group_name` AS `group_name`, `group`.`group_size` AS `group_size`
                    FROM {$this->p}allocation_choice `choice`
                    LEFT JOIN (SELECT `group_id`, `group_name`, `group_size`
                                FROM `allocation_group` WHERE `project_id` = :project_id) AS `group`
                                    ON `choice`.`group_id` = `group`.`group_id`
                    WHERE `choice`.project_id = :project_id";

        $arr = array(':project_id' => $project_id);

        // If user_id is provided, include it in the query and parameters
        if ($user_id !== null) {
            $query .= " AND `choice`.`user_id` = :userId";
            $arr[':userId'] = $user_id;
        }

        $query .= " ORDER BY `choice`.`user_id` ASC;";

        return $this->PDOX->allRowsDie($query, $arr);
    }

    function addChoices($project_id, $user_id, $selectedGroups) {

        try {
            // Delete the row if marked for deletion
            $this->PDOX->queryDie("DELETE FROM {$this->p}allocation_choice
                                    WHERE `project_id` = :project_id and `user_id` = :user_id",
            array(':project_id' => $project_id, ':user_id' => $user_id));

            // Insert / Update allocation groups
            foreach ($selectedGroups as $group) {
                $this->PDOX->queryDie("INSERT INTO allocation_choice
                    (`group_id`, `project_id`,
                    `user_id`, `choice_rank`,
                    `created_by`, `modified_by`)
                    VALUES (:group_id, :project_id,
                            :user_id, :rank,
                            :user_id, :user_id)",
                    array(':group_id' => $group['group_id'], ':project_id' => $project_id,
                        ':user_id' => $user_id,
                        ':rank' => $group['choice_number']));
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    function getAllStudentChoices($project_id, $draw, $offset, $limit,
                                    $order_column, $order_dir,
                                    $search_st, $search_regexp) {

        $result = array('draw' => $draw, "recordsTotal" => 0, "recordsFiltered"=> 0, "data" => []);
        try {

            $query_arr = array(':project_id' => $project_id);
            $records = $this->PDOX->rowDie("SELECT count(distinct `membership`.user_id) as c
                                FROM {$this->p}allocation_project `project`
                                left join {$this->p}lti_link `link` on `link`.link_id = `project`.link_id
                                left join {$this->p}lti_membership `membership` on `membership`.context_id = `link`.context_id
                                left join {$this->p}allocation_user `user` on `user`.user_id = `membership`.user_id
                                where `project`.project_id = :project_id and `membership`.role = 0 and `user`.EID is not null", $query_arr);

            $result['recordsTotal'] = $records['c'];

            $result['counts'] = $this->PDOX->allRowsDie("SELECT `choice`.assigned as 'status',
                                                                count(distinct `membership`.user_id) as c
                                    FROM {$this->p}allocation_project `project`
                                    left join {$this->p}lti_link `link` on `link`.link_id = `project`.link_id
                                    left join {$this->p}lti_membership `membership` on `membership`.context_id = `link`.context_id
                                    left join {$this->p}allocation_choice `choice` on `choice`.project_id=`project`.project_id
                                    left join {$this->p}allocation_user `user` on `user`.user_id = `membership`.user_id
                                    where `project`.project_id = :project_id and `membership`.role = 0 and `user`.EID is not null
                                    group by `choice`.assigned", $query_arr);

            $search_query = '';
            if ($search_st !== '') {
                $search_query = " and (LOCATE(:search, `user`.EID) or LOCATE(:search, `lti_user`.displayname)) ";
                $query_arr[':search'] = $search_st;
            }

            $records = $this->PDOX->rowDie("SELECT count(distinct `membership`.user_id) as c
                                FROM {$this->p}allocation_project `project`
                                left join {$this->p}lti_link `link` on `link`.link_id = `project`.link_id
                                left join {$this->p}lti_membership `membership` on `membership`.context_id = `link`.context_id
                                left join {$this->p}lti_user `lti_user` on `lti_user`.user_id = `membership`.user_id
                                left join {$this->p}allocation_user `user` on `user`.user_id = `membership`.user_id
                                where `project`.project_id = :project_id and `membership`.role = 0 and `user`.EID is not null". $search_query, $query_arr);
            $result['recordsFiltered'] = $records['c'];

            switch ($order_column) {
                case 'EID':
                    $order_column = '`user`.EID';
                    break;
                case 'name':
                    $order_column = '`lti_user`.displayname';
                    break;
                case 'modified_at':
                    $order_column = "ifnull((select max(`choice`.`modified_at`) from {$this->p}allocation_choice `choice` where `choice`.user_id = `membership`.user_id),'')";
                    break;
            }

            $result['order'] = "ORDER BY ". $order_column ." ". $order_dir;

            $query = "SELECT `user`.EID,
                            `lti_user`.displayname as 'name',
                            `membership`.user_id,
                            `membership`.role,
                            ifnull((select group_concat( concat(`choice`.choice_rank,'~',`choice`.group_id,'~', `choice`.`assigned`) order by `choice`.choice_rank) from {$this->p}allocation_choice `choice` where `choice`.user_id = `membership`.user_id),'') as 'choices',
                            (select count(*) > 0 from {$this->p}allocation_choice `choice` where `choice`.user_id = `membership`.user_id) as 'accessed',
                            ifnull((select group_id from {$this->p}allocation_choice `choice` where `choice`.user_id = `membership`.user_id and `choice`.assigned=1),'') as 'assigned',
                            ifnull((select max(`choice`.`modified_at`) from {$this->p}allocation_choice `choice` where `choice`.user_id = `membership`.user_id),'')  as 'modified_at'
                    FROM {$this->p}allocation_project `project`
                    left join {$this->p}lti_link `link` on `link`.link_id = `project`.link_id
                    left join {$this->p}lti_membership `membership` on `membership`.context_id = `link`.context_id
                    left join {$this->p}lti_user `lti_user` on `lti_user`.user_id = `membership`.user_id
                    left join {$this->p}allocation_user `user` on `user`.user_id = `membership`.user_id
                    where `project`.project_id = :project_id and `membership`.role = 0 and `user`.EID is not null". $search_query
                    ." ORDER BY ". $order_column ." ". $order_dir
                    ." limit ". $limit ." offset ". $offset .";";

            $result['data'] = $this->PDOX->allRowsDie($query, $query_arr);
        } catch (Exception $e) {
            $result["error"] = "PDO Exception: " . $e->getMessage();
        }

        return $result;
    }

    function getStudentSelectionPerGroup($project_id, $group_id) {

        $query = "SELECT `user`.EID,
                            `lti_user`.displayname as 'name',
                            `membership`.user_id,
                            ifnull((select group_id from {$this->p}allocation_choice `choice`
                                        where `choice`.user_id = `membership`.user_id and `choice`.assigned=1),'') as 'current',
		                    if(ifnull((select group_id from {$this->p}allocation_choice `choice`
                                        where `choice`.user_id = `membership`.user_id and `choice`.assigned=1),'')=:group_id,1,0) as 'mine'
                    FROM {$this->p}allocation_project `project`
                    left join {$this->p}lti_link `link` on `link`.link_id = `project`.link_id
                    left join {$this->p}lti_membership `membership` on `membership`.context_id = `link`.context_id
                    left join {$this->p}lti_user `lti_user` on `lti_user`.user_id = `membership`.user_id
                    left join {$this->p}allocation_user `user` on `user`.user_id = `membership`.user_id
                    where `project`.project_id = :project_id and `membership`.role = 0 and `user`.EID is not null";
        $arr = array(':project_id' => $project_id, ':group_id' => $group_id);

        return $this->PDOX->allRowsDie($query, $arr);
    }

    function setState($state) {
        try {
            $this->PDOX->queryDie("UPDATE {$this->p}allocation_project
                    SET `state` = :toolState, `modified_by` = :modifiedBy
                    WHERE link_id = :linkId",
                array(':linkId' => $this->link_id, ':toolState' => $state, ':modifiedBy' => $this->user_id));

            return TRUE;
        } catch (PDOException $e) {
            return FALSE;
        }
    }

    function checkState($link_id, $site_id) {
        $query = "SELECT `state` FROM {$this->p}allocation_site
            WHERE `link_id` = :linkId AND `site_id` = :siteId;";

        $arr = array(':linkId' => $link_id, ':siteId' => $site_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getGroupStatus($project_id, $group_id) {

        $query = "SELECT `group`.group_size,
                    ifnull((select sum(`choice`.`assigned`) from {$this->p}allocation_choice `choice`
                                where `group`.group_id = `choice`.group_id and `group`.project_id = `choice`.project_id), 0)  as 'c'
                FROM {$this->p}allocation_group `group`
                where `group`.project_id = :project_id and `group`.group_id = :group_id";
        $arr = array(':project_id' => $project_id, ':group_id' => $group_id);
        return $this->PDOX->rowDie($query, $arr);
    }

    function getStudent($EID) {
        $query = "SELECT user_id FROM {$this->p}allocation_user WHERE EID = :EID;";
        $arr = array(':EID' => $EID);
        return $this->PDOX->rowDie($query, $arr);
    }

    function changeStudentAssign($project_id, $group_id, $student_eid) {
        try {

            $student_user_id = $this->getStudent($student_eid);
            if (!isset($student_user_id['user_id'])) {
                return FALSE;
            }

            // remove previous assignments
            $this->PDOX->queryDie("UPDATE {$this->p}allocation_choice
                    SET `assigned` = 0, `modified_by` = :modifiedBy
                    WHERE `project_id` = :project_id AND `user_id` = :studentId",
                array(':project_id' => $project_id, ':studentId' => $student_user_id['user_id'], 'modifiedBy' => $this->user_id));

            // find out if it exist already
            $exists = $this->PDOX->rowDie("SELECT ifnull(group_id,'0') FROM {$this->p}allocation_choice
                                        WHERE `project_id` = :project_id AND `user_id` = :studentId AND `group_id` = :groupId",
                    array(':project_id' => $project_id, ':studentId' => $student_user_id['user_id'], ':groupId' => $group_id));

            if ($exists == FALSE) {
                // doesn't exist - move rankings, and add group
                $this->PDOX->queryDie("UPDATE {$this->p}allocation_choice
                    SET `choice_rank` = `choice_rank` + 1, `modified_by` = :modifiedBy
                    WHERE `project_id` = :project_id AND `user_id` = :studentId",
                array(':project_id' => $project_id, ':studentId' => $student_user_id['user_id'], 'modifiedBy' => $this->user_id));

                $this->PDOX->queryDie("INSERT INTO {$this->p}allocation_choice
                                    (`group_id`, `user_id`, `project_id`,
                                    `choice_rank`, `assigned`,
                                    `created_by`, `modified_by`)
                                    VALUES
                                    (:groupId, :studentId, :project_id,
                                    1,1,:user,:user)",
                    array(':project_id' => $project_id, ':studentId' => $student_user_id['user_id'], 'user' => $this->user_id,
                        ':groupId' => $group_id));
            } else {

                $this->PDOX->queryDie("UPDATE {$this->p}allocation_choice
                        SET `assigned` = 1, `modified_by` = :modifiedBy
                        WHERE `project_id` = :project_id AND `user_id` = :studentId AND `group_id` = :groupId",
                    array(':project_id' => $project_id, ':studentId' => $student_user_id['user_id'], 'modifiedBy' => $this->user_id,
                        ':groupId' => $group_id));
            }

            return TRUE;
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    function changeGroupStudentAssignment($project_id, $group_id, $student_EID_list) {

        // remove previous assignments for this group
        $this->PDOX->queryDie("UPDATE {$this->p}allocation_choice
            SET `assigned` = 0, `modified_by` = :modifiedBy
            WHERE `project_id` = :project_id AND `group_id` = :group_id",
        array(':project_id' => $project_id, ':group_id' => $group_id, 'modifiedBy' => $this->user_id));

        $result = TRUE;
        foreach ($student_EID_list as $EID) {
            $result = $result && $this->changeStudentAssign($project_id, $group_id, $EID);
        }
        return $result;
    }

    /////////////////////////////
    // To review ...

    function addAssignments($link_id, $user_id, $assignments) {
        try {
            $query = "UPDATE {$this->p}allocation_choice
                    SET `assigned` = :assigned, `modified_by` = :modifiedBy, `modified_at` = :modifiedAt
                    WHERE `link_id` = :linkId AND `user_id` = :userId AND `group_id` = :groupId";

            for ($i = 1; $i < count($assignments); $i++) {
                $assignment = $assignments[$i];
                $studentId = ltrim($assignment['student_id'], 's');
                $groupId = ltrim($assignment['assigned_group'], 'p');

                $existingAssignedGroup = $this->getAssignedGroup($link_id, $studentId);

                // If there's already an assigned group, reset it to 0
                if ($existingAssignedGroup) {
                    $resetArr = array(':linkId' => $link_id, ':userId' => $studentId, ':groupId' => ltrim($existingAssignedGroup, 'p'),
                        ':assigned' => 0, ':modifiedBy' => $user_id, ':modifiedAt' => date("Y-m-d H:i:s"));

                    $this->PDOX->queryDie($query, $resetArr);
                }

                $arr = array(':linkId' => $link_id, ':userId' => $studentId, ':groupId' => $groupId,
                    ':assigned' => 1, ':modifiedBy' => $user_id, ':modifiedAt' => date("Y-m-d H:i:s"));

                $this->PDOX->queryDie($query, $arr);
            }

            return true;
        } catch (PDOException $e) {
            throw $e;
            return json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
        }
    }

    function getAssignedGroup($link_id, $user_id) {
        $row =  $this->PDOX->rowDie("SELECT `group_id` FROM {$this->p}allocation_choice
                    WHERE `link_id` = :linkId AND `user_id` = :userId AND `assigned` = :assigned",
                array(':linkId' => $link_id, ':userId' => $user_id, ':assigned' => 1));

        return $row ? $row['group_id'] : null;
    }
}

?>
