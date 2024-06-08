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
        $query = "SELECT `group_id`, `group_name`, `group_size`
                    FROM {$this->p}allocation_group
                    WHERE `project_id` = :project_id ORDER BY `group_id` ASC;";

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
            $records = $this->PDOX->rowDie("SELECT count(distinct `choice`.`user_id`) as 'c'
                                FROM {$this->p}allocation_choice `choice`
                                where `choice`.`project_id` = :project_id", $query_arr);

            $result['recordsTotal'] = $records['c'];

            $search_query = '';
            if ($search_st !== '') {
                $search_query = " and (LOCATE(:search, `all_user`.EID) or LOCATE(:search, `user`.displayname)) ";
                $query_arr[':search'] = $search_st;
            }

            $records = $this->PDOX->rowDie("SELECT count(distinct `choice`.`user_id`) as 'c'
                                FROM {$this->p}allocation_choice `choice`
                                left join allocation_user `all_user` on `all_user`.user_id = `choice`.`user_id`
                                left join lti_user `user` on `user`.user_id = `choice`.`user_id`
                                where `choice`.`project_id` = :project_id ". $search_query, $query_arr);
            $result['recordsFiltered'] = $records['c'];

            switch ($order_column) {
                case 'EID':
                    $order_column = '`all_user`.EID';
                    break;
                case 'name':
                    $order_column = '`user`.displayname';
                    break;
                case 'modified_at':
                    $order_column = 'max(`choice`.`modified_at`)';
                    break;
            }

            $result['order'] = "ORDER BY ". $order_column ." ". $order_dir;

            $query = "SELECT `all_user`.EID as EID, `user`.displayname as 'name',
                    group_concat( concat(`choice`.choice_rank,'~',`choice`.group_id,'~', `choice`.`assigned`) order by `choice`.choice_rank) as 'choices',
                    '0' as 'assigned',
                    max(`choice`.`modified_at`) as 'modified_at'
                FROM {$this->p}allocation_choice `choice`
                left join allocation_user `all_user` on `all_user`.user_id = `choice`.`user_id`
                left join lti_user `user` on `user`.user_id = `choice`.`user_id`
                where `choice`.`project_id` = :project_id ". $search_query
                ." group by `all_user`.EID, `user`.displayname "
                ." ORDER BY ". $order_column ." ". $order_dir
                ." limit ". $limit ." offset ". $offset .";";

            $result['data'] = $this->PDOX->allRowsDie($query, $query_arr);
        } catch (Exception $e) {
            $result["error"] = "PDO Exception: " . $e->getMessage();
        }

        return $result;
    }

    /////////////////////////////
    // function getSettings($link_id, $site_id) {
    //     $query = "SELECT * FROM {$this->p}allocation_site
    //         WHERE `link_id` = :linkId AND `site_id` = :siteId;";

    //     $arr = array(':linkId' => $link_id, ':siteId' => $site_id);
    //     return $this->PDOX->allRowsDie($query, $arr);
    // }






    function addAssignments($link_id, $user_id, $assignments) {
        try {
            $query = "UPDATE {$this->p}`allocation_choice`
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
        $row =  $this->PDOX->rowDie("SELECT `group_id` FROM {$this->p}`allocation_choice`
                    WHERE `link_id` = :linkId AND `user_id` = :userId AND `assigned` = :assigned",
                array(':linkId' => $link_id, ':userId' => $user_id, ':assigned' => 1));

        return $row ? $row['group_id'] : null;
    }

    function assignUser($link_id, $user_id, $student_id, $group_id) {
        try {
            $this->PDOX->queryDie("UPDATE {$this->p}`allocation_choice`
                    SET `assigned` = :assigned, `modified_by` = :modifiedBy, `modified_at` = :modifiedAt
                    WHERE `link_id` = :linkId AND `user_id` = :studentId AND `group_id` = :groupId",
                array(':linkId' => $link_id, ':studentId' => $student_id, ':groupId' => $group_id,
                    ':assigned' => 1, ':modifiedBy' => $user_id, ':modifiedAt' => date("Y-m-d H:i:s")));

            return true;
        } catch (PDOException $e) {
            throw $e;
            return json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
        }
    }

    function setState($link_id, $user_id, $state) {
        try {
            $this->PDOX->queryDie("UPDATE {$this->p}`allocation_site`
                SET `state` = :toolState, `modified_by` = :modifiedBy, `modified_at` = :modifiedAt
                WHERE link_id = :linkId",
            array(':linkId' => $link_id, ':toolState' => $state, ':modifiedBy' => $user_id, ':modifiedAt' => date("Y-m-d H:i:s")));

            return  true;
        } catch (PDOException $e) {
            throw $e;
            return json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
        }
    }

    function checkState($link_id, $site_id) {
        $query = "SELECT `state` FROM {$this->p}`allocation_site`
            WHERE `link_id` = :linkId AND `site_id` = :siteId;";

        $arr = array(':linkId' => $link_id, ':siteId' => $site_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }
}

?>
