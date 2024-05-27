<?php
namespace Allocation\DAO;

define("OTHER", 9999);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

class AllocationDAO {

    private $PDOX;
    private $p;
    private $tool;

    public function __construct($PDOX, $p, $tool) {
        $this->PDOX = $PDOX;
        $this->p = $p;
        $this->tool = $tool;
    }

    function configureAllocation($link_id, $user_id, $site_id, $tool_name, $tool_instructions, 
                                    $min_selections, $max_selections, $release_date, $closing_date,
                                    $groups_doc, $groups) {

        try {    
            // Insert / Update allocation settings 
            $this->PDOX->queryDie("INSERT INTO {$this->p}`allocation_site`
                    (`link_id`, `site_id`, `name`, `instructions`, `min_selections`, `max_selections`,
                    `release_date`, `closing_date`, `groups_doc`, `created_by`)
                    VALUES (:linkId, :siteId, :toolName, :toolInstructions, :minSelections, 
                            :maxSelections, :releaseDate, :closingDate, :groupsDocLink, :createdBy)
                    ON DUPLICATE KEY UPDATE
                    `name` = VALUES(`name`),
                    `instructions` = VALUES(`instructions`),
                    `min_selections` = VALUES(`min_selections`),
                    `max_selections` = VALUES(`max_selections`),
                    `release_date` = VALUES(`release_date`),
                    `closing_date` = VALUES(`closing_date`),
                    `groups_doc` = VALUES(`groups_doc`),
                    `created_by` = VALUES(`created_by`)",
            
                array(':linkId' => $link_id, ':siteId' => $site_id, ':toolName' => $tool_name, 
                        ':toolInstructions' => $tool_instructions, ':minSelections' => $min_selections,
                        ':maxSelections' => $max_selections,':releaseDate' => $release_date,
                        ':closingDate' => $closing_date,':groupsDocLink' => $groups_doc,':createdBy' => $user_id));
            
            // Insert / Update allocation groups 
            foreach ($groups as $group) {
	        if ($group['delete']) {
		    // Delete the row if marked for deletion
                    $this->PDOX->queryDie("DELETE FROM {$this->p}`allocation_group` WHERE `link_id` = :linkId AND `group_id` = :groupId",
                                    array(':linkId' => $link_id, ':groupId' => $group['id']));
                } else {
	        	$this->PDOX->queryDie("INSERT INTO {$this->p}`allocation_group`
                            (`group_id`, `link_id`, `group_name`, `group_size`, `created_by`)
                            VALUES (:groupId, :linkId, :groupName, :groupSize, :createdBy)
                            ON DUPLICATE KEY UPDATE
                            `group_name` = VALUES(`group_name`),
                            `group_size` = VALUES(`group_size`),
                            `created_by` = VALUES(`created_by`)",
                        array(':groupId' => $group['id'], ':linkId' => $link_id, ':groupName' => $group['title'], 
                            ':groupSize' => $group['size'], ':createdBy' => $user_id));
		}
            }

            return true;
        } catch (PDOException $e) {
            throw $e;
            return json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
        }
    }

    function getGroups($link_id) {
        $query = "SELECT * FROM {$this->p}`allocation_group`
            WHERE `link_id` = :linkId ORDER BY `group_id` ASC;";

        $arr = array(':linkId' => $link_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function getSettings($link_id, $site_id) {
        $query = "SELECT * FROM {$this->p}`allocation_site`
            WHERE `link_id` = :linkId AND `site_id` = :siteId;";

        $arr = array(':linkId' => $link_id, ':siteId' => $site_id);
        return $this->PDOX->allRowsDie($query, $arr);
    }

    function addChoices($link_id, $user_id, $user_name, $selectedGroups) {
        $values = array();
        $placeholders = array();
        $query = "REPLACE INTO {$this->p}`allocation_choice`
            (`link_id`, `user_id`, `user_name`, `group_id`, `choice_id`, `created_by`)
            VALUES ";

        foreach ($selectedGroups as $group) {
            $choiceNumber = $group['choice_number'];
            $groupId = $group['group_id'];

            if (!empty($groupId) && !empty($choiceNumber)) {
                $placeholders[] = "(:linkId{$groupId}, :userId{$groupId}, :userName{$groupId}, :groupId{$groupId}, :choiceId{$groupId}, :createdBy{$groupId})";
		$values[":linkId{$groupId}"] = $link_id;
		$values[":userId{$groupId}"] = $user_id;
		$values[":userName{$groupId}"] = $user_name;
                $values[":groupId{$groupId}"] = $groupId;
                $values[":choiceId{$groupId}"] = $choiceNumber;
                $values[":createdBy{$groupId}"] = $user_id;
            }
        }
    
        if (!empty($placeholders)) {
            $query .= implode(", ", $placeholders);
            $this->PDOX->queryDie($query, $values);
        }
    
        return true;
    }

    function getChoices($link_id, $user_id = null) {

	$query = "SELECT `choice`.*, `user`.`displayname` AS `full_name`, `group`.`group_name` AS `group_name`, `group`.`group_size` AS `group_size`
		FROM   {$this->p}`allocation_choice` AS `choice`
		LEFT JOIN `lti_user` AS `user` ON `choice`.`user_id` = `user`.`user_key`
		LEFT JOIN (SELECT `group_id`, `group_name`, `group_size` FROM `allocation_group` WHERE `link_id` = :linkId) AS `group` 
			ON `choice`.`group_id` = `group`.`group_id` 
		WHERE `choice`.`link_id` = :linkId";
	    
	$arr = array(':linkId' => $link_id);

        // If user_id is provided, include it in the query and parameters
        if ($user_id !== null) {
            $query .= " AND `choice`.`user_id` = :userId";
            $arr[':userId'] = $user_id;
        }

        $query .= " ORDER BY `choice`.`user_id` ASC;";

        return $this->PDOX->allRowsDie($query, $arr);
    }

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
}

?>
