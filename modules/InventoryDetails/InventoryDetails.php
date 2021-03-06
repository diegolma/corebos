<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once('data/CRMEntity.php');
require_once('data/Tracker.php');

class InventoryDetails extends CRMEntity {

	var $db, $log; // Used in class functions of CRMEntity

	var $table_name = 'vtiger_inventorydetails';
	var $table_index= 'inventorydetailsid';
	var $column_fields = Array();

	/** Indicator if this is a custom module or standard module */
	var $IsCustomModule = true;
	var $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array('vtiger_inventorydetailscf', 'inventorydetailsid');
	// Uncomment the line below to support custom field columns on related lists
	// var $related_tables = Array('vtiger_inventorydetailscf'=>array('inventorydetailsid','vtiger_inventorydetails', 'inventorydetailsid'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	var $tab_name = Array('vtiger_crmentity', 'vtiger_inventorydetails', 'vtiger_inventorydetailscf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	var $tab_name_index = Array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_inventorydetails'   => 'inventorydetailsid',
	    'vtiger_inventorydetailscf' => 'inventorydetailsid');

	/**
	 * Mandatory for Listing (Related listview)
	 */
	var $list_fields = Array (
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Inventory Details No'=> Array('inventorydetails' => 'inventorydetails_no'),
		'Products'=> Array('inventorydetails' => 'productid'),
		'Related To'=> Array('inventorydetails' => 'related_to'),
		'Accounts'=> Array('inventorydetails' => 'account_id'),
		'Contacts'=> Array('inventorydetails' => 'contact_id'),
		'Vendors'=> Array('inventorydetails' => 'vendor_id'),
		'Quantity'=> Array('inventorydetails' => 'quantity'),
		'Listprice'=> Array('inventorydetails' => 'listprice'),
		'Line Total'=> Array('inventorydetails' => 'linetotal'),
		'Assigned To' => Array('crmentity' =>'smownerid')
	);
	var $list_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Inventory Details No'=> 'inventorydetails_no',
		'Products'=> 'productid',
		'Related To'=> 'related_to',
		'Accounts'=> 'account_id',
		'Contacts'=> 'contact_id',
		'Vendors'=> 'vendor_id',
		'Quantity'=> 'quantity',
		'Listprice'=> 'listprice',
		'Line Total'=> 'linetotal',
		'Assigned To' => 'assigned_user_id'
	);

	// Make the field link to detail view from list view (Fieldname)
	var $list_link_field = 'inventorydetails_no';

	// For Popup listview and UI type support
	var $search_fields = Array(
		/* Format: Field Label => Array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Inventory Details No'=> Array('inventorydetails' => 'inventorydetails_no'),
		'Products'=> Array('inventorydetails' => 'productid'),
		'Related To'=> Array('inventorydetails' => 'related_to'),
		'Accounts'=> Array('inventorydetails' => 'account_id'),
		'Contacts'=> Array('inventorydetails' => 'contact_id'),
		'Vendors'=> Array('inventorydetails' => 'vendor_id'),
		'Quantity'=> Array('inventorydetails' => 'quantity'),
		'Listprice'=> Array('inventorydetails' => 'listprice'),
		'Line Total'=> Array('inventorydetails' => 'linetotal'),
	);
	var $search_fields_name = Array(
		/* Format: Field Label => fieldname */
		'Inventory Details No'=> 'inventorydetails_no',
		'Products'=> 'productid',
		'Related To'=> 'related_to',
		'Accounts'=> 'account_id',
		'Contacts'=> 'contact_id',
		'Vendors'=> 'vendor_id',
		'Quantity'=> 'quantity',
		'Listprice'=> 'listprice',
		'Line Total'=> 'linetotal',
	);

	// For Popup window record selection
	var $popup_fields = Array('inventorydetails_no');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	var $sortby_fields = Array();

	// For Alphabetical search
	var $def_basicsearch_col = 'inventorydetails_no';

	// Column value to use on detail view record text display
	var $def_detailview_recname = 'inventorydetails_no';

	// Required Information for enabling Import feature
	var $required_fields = Array('inventorydetails_no'=>1);

	// Callback function list during Importing
	var $special_functions = Array('set_import_assigned_user');

	var $default_order_by = 'inventorydetails_no';
	var $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	var $mandatory_fields = Array('createdtime', 'modifiedtime', 'inventorydetails_no');
	
	function __construct() {
		global $log;
		$this_module = get_class($this);
		$this->column_fields = getColumnFields($this_module);
		$this->db = PearDatabase::getInstance();
		$this->log = $log;
		$sql = 'SELECT 1 FROM vtiger_field WHERE uitype=69 and tabid = ? limit 1';
		$tabid = getTabid($this_module);
		$result = $this->db->pquery($sql, array($tabid));
		if ($result and $this->db->num_rows($result)==1) {
			$this->HasDirectImageField = true;
		}
	}

	function save_module($module) {
		global $adb;
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id,$module);
		}
		$this->column_fields['cost_gross'] = $this->column_fields['quantity'] * $this->column_fields['cost_price'];
		$adb->pquery('update vtiger_inventorydetails set cost_gross=? where inventorydetailsid=?', array($this->column_fields['cost_gross'], $this->id));
		if (!empty($this->column_fields['productid'])) {
			$this->column_fields['total_stock'] = getPrdQtyInStck($this->column_fields['productid']);
			$adb->pquery('update vtiger_inventorydetails set total_stock=? where inventorydetailsid=?', array($this->column_fields['total_stock'], $this->id));
		}
	}

	/**
	 * Return query to use based on given modulename, fieldname
	 * Useful to handle specific case handling for Popup
	 */
	function getQueryByModuleField($module, $fieldname, $srcrecord, $query='') {
		// $srcrecord could be empty
	}

	/**
	 * Get list view query (send more WHERE clause condition if required)
	 */
	function getListQuery($module, $usewhere='') {
		$query = "SELECT vtiger_crmentity.*, $this->table_name.*";

		// Keep track of tables joined to avoid duplicates
		$joinedTables = array();

		// Select Custom Field Table Columns if present
		if(!empty($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$query .= " FROM $this->table_name";

		$query .= "	INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		$joinedTables[] = $this->table_name;
		$joinedTables[] = 'vtiger_crmentity';

		// Consider custom table join as well.
		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
			$joinedTables[] = $this->customFieldTable[0];
		}
		$query .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$joinedTables[] = 'vtiger_users';
		$joinedTables[] = 'vtiger_groups';

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($module));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			if(!in_array($other->table_name, $joinedTables)) {
				$query .= " LEFT JOIN $other->table_name ON $other->table_name.$other->table_index = $this->table_name.$columnname";
				$joinedTables[] = $other->table_name;
			}
		}

		global $current_user;
		$query .= $this->getNonAdminAccessControlQuery($module,$current_user);
		$query .= "	WHERE vtiger_crmentity.deleted = 0 ".$usewhere;
		return $query;
	}

	/**
	 * Apply security restriction (sharing privilege) query part for List view.
	 */
	function getListViewSecurityParameter($module) {
		global $current_user;
		require('user_privileges/user_privileges_'.$current_user->id.'.php');
		require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

		$sec_query = '';
		$tabid = getTabid($module);

		if($is_admin==false && $profileGlobalPermission[1] == 1 && $profileGlobalPermission[2] == 1
			&& $defaultOrgSharingPermission[$tabid] == 3) {

				$sec_query .= " AND (vtiger_crmentity.smownerid in($current_user->id) OR vtiger_crmentity.smownerid IN 
					(
						SELECT vtiger_user2role.userid FROM vtiger_user2role 
						INNER JOIN vtiger_users ON vtiger_users.id=vtiger_user2role.userid 
						INNER JOIN vtiger_role ON vtiger_role.roleid=vtiger_user2role.roleid 
						WHERE vtiger_role.parentrole LIKE '".$current_user_parent_role_seq."::%'
					) 
					OR vtiger_crmentity.smownerid IN 
					(
						SELECT shareduserid FROM vtiger_tmp_read_user_sharing_per 
						WHERE userid=".$current_user->id." AND tabid=".$tabid."
					) 
					OR (";

					// Build the query based on the group association of current user.
					if(sizeof($current_user_groups) > 0) {
						$sec_query .= " vtiger_groups.groupid IN (". implode(",", $current_user_groups) .") OR ";
					}
					$sec_query .= " vtiger_groups.groupid IN 
						(
							SELECT vtiger_tmp_read_group_sharing_per.sharedgroupid 
							FROM vtiger_tmp_read_group_sharing_per
							WHERE userid=".$current_user->id." and tabid=".$tabid."
						)";
				$sec_query .= ")
				)";
		}
		return $sec_query;
	}

	/**
	 * Create query to export the records.
	 */
	function create_export_query($where)
	{
		global $current_user;
		$thismodule = $_REQUEST['module'];

		include("include/utils/ExportUtils.php");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery($thismodule, "detail_view");

		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list, vtiger_users.user_name AS user_name 
				FROM vtiger_crmentity INNER JOIN $this->table_name ON vtiger_crmentity.crmid=$this->table_name.$this->table_index";

		if(!empty($this->customFieldTable)) {
			$query .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
		}

		$query .= " LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " LEFT JOIN vtiger_users ON vtiger_crmentity.smownerid = vtiger_users.id and vtiger_users.status='Active'";

		$linkedModulesQuery = $this->db->pquery("SELECT distinct fieldname, columnname, relmodule FROM vtiger_field" .
				" INNER JOIN vtiger_fieldmodulerel ON vtiger_fieldmodulerel.fieldid = vtiger_field.fieldid" .
				" WHERE uitype='10' AND vtiger_fieldmodulerel.module=?", array($thismodule));
		$linkedFieldsCount = $this->db->num_rows($linkedModulesQuery);

		$rel_mods[$this->table_name] = 1;
		for($i=0; $i<$linkedFieldsCount; $i++) {
			$related_module = $this->db->query_result($linkedModulesQuery, $i, 'relmodule');
			$fieldname = $this->db->query_result($linkedModulesQuery, $i, 'fieldname');
			$columnname = $this->db->query_result($linkedModulesQuery, $i, 'columnname');

			$other = CRMEntity::getInstance($related_module);
			vtlib_setup_modulevars($related_module, $other);

			if($rel_mods[$other->table_name]) {
				$rel_mods[$other->table_name] = $rel_mods[$other->table_name] + 1;
				$alias = $other->table_name.$rel_mods[$other->table_name];
				$query_append = "as $alias";
			} else {
				$alias = $other->table_name;
				$query_append = '';
				$rel_mods[$other->table_name] = 1;
			}

			$query .= " LEFT JOIN $other->table_name $query_append ON $alias.$other->table_index = $this->table_name.$columnname";
		}

		$query .= $this->getNonAdminAccessControlQuery($thismodule,$current_user);
		$where_auto = " vtiger_crmentity.deleted=0";

		if($where != '') $query .= " WHERE ($where) AND $where_auto";
		else $query .= " WHERE $where_auto";

		return $query;
	}

	/**
	 * Initialize this instance for importing.
	 */
	function initImport($module) {
		$this->db = PearDatabase::getInstance();
		$this->initImportableFields($module);
	}

	/**
	 * Create list query to be shown at the last step of the import.
	 * Called From: modules/Import/UserLastImport.php
	 */
	function create_import_query($module) {
		global $current_user;
		$query = "SELECT vtiger_crmentity.crmid, case when (vtiger_users.user_name not like '') then vtiger_users.user_name else vtiger_groups.groupname end as user_name, $this->table_name.* FROM $this->table_name
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index
			LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=vtiger_crmentity.crmid
			LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
			LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
			WHERE vtiger_users_last_import.assigned_user_id='$current_user->id'
			AND vtiger_users_last_import.bean_type='$module'
			AND vtiger_users_last_import.deleted=0";
		return $query;
	}

	/**
	 * Transform the value while exporting
	 */
	function transform_export_value($key, $value) {
		return parent::transform_export_value($key, $value);
	}

	/**
	 * Function which will set the assigned user id for import record.
	 */
	function set_import_assigned_user()
	{
		global $current_user, $adb;
		$record_user = $this->column_fields["assigned_user_id"];

		if($record_user != $current_user->id){
			$sqlresult = $adb->pquery("select id from vtiger_users where id = ? union select groupid as id from vtiger_groups where groupid = ?", array($record_user, $record_user));
			if($this->db->num_rows($sqlresult)!= 1) {
				$this->column_fields["assigned_user_id"] = $current_user->id;
			} else {
				$row = $adb->fetchByAssoc($sqlresult, -1, false);
				if (isset($row['id']) && $row['id'] != -1) {
					$this->column_fields["assigned_user_id"] = $row['id'];
				} else {
					$this->column_fields["assigned_user_id"] = $current_user->id;
				}
			}
		}
	}

	/**
	 * Function which will give the basic query to find duplicates
	 */
	function getDuplicatesQuery($module,$table_cols,$field_values,$ui_type_arr,$select_cols='') {
		$select_clause = "SELECT ". $this->table_name .".".$this->table_index ." AS recordid, vtiger_users_last_import.deleted,".$table_cols;

		// Select Custom Field Table Columns if present
		if(isset($this->customFieldTable)) $query .= ", " . $this->customFieldTable[0] . ".* ";

		$from_clause = " FROM $this->table_name";

		$from_clause .= " INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = $this->table_name.$this->table_index";

		// Consider custom table join as well.
		if(isset($this->customFieldTable)) {
			$from_clause .= " INNER JOIN ".$this->customFieldTable[0]." ON ".$this->customFieldTable[0].'.'.$this->customFieldTable[1] .
				" = $this->table_name.$this->table_index";
		}
		$from_clause .= " LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid";

		$where_clause = " WHERE vtiger_crmentity.deleted = 0";
		$where_clause .= $this->getListViewSecurityParameter($module);

		if (isset($select_cols) && trim($select_cols) != '') {
			$sub_query = "SELECT $select_cols FROM $this->table_name AS t " .
				" INNER JOIN vtiger_crmentity AS crm ON crm.crmid = t.".$this->table_index;
			// Consider custom table join as well.
			if(isset($this->customFieldTable)) {
				$sub_query .= " LEFT JOIN ".$this->customFieldTable[0]." tcf ON tcf.".$this->customFieldTable[1]." = t.$this->table_index";
			}
			$sub_query .= " WHERE crm.deleted=0 GROUP BY $select_cols HAVING COUNT(*)>1";
		} else {
			$sub_query = "SELECT $table_cols $from_clause $where_clause GROUP BY $table_cols HAVING COUNT(*)>1";
		}

		$query = $select_clause . $from_clause .
					" LEFT JOIN vtiger_users_last_import ON vtiger_users_last_import.bean_id=" . $this->table_name .".".$this->table_index .
					" INNER JOIN (" . $sub_query . ") AS temp ON ".get_on_clause($field_values,$ui_type_arr,$module) .
					$where_clause .
					" ORDER BY $table_cols,". $this->table_name .".".$this->table_index ." ASC";

		return $query;
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	function vtlib_handler($modulename, $event_type) {
		if($event_type == 'module.postinstall') {
			//Handle post installation actions
			
			require_once("modules/com_vtiger_workflow/include.inc");
			require_once("modules/com_vtiger_workflow/tasks/VTEntityMethodTask.inc");
			require_once("modules/com_vtiger_workflow/VTEntityMethodManager.inc");
			
			global $adb;
			
			$mod=Vtiger_Module::getInstance('InventoryDetails');
			$this->setModuleSeqNumber('configure', $modulename, '', '000000001');
			
			$modAccounts=Vtiger_Module::getInstance('Accounts');
			$modContacts=Vtiger_Module::getInstance('Contacts');
			$modVnd=Vtiger_Module::getInstance('Vendors');
			$modInvoice=Vtiger_Module::getInstance('Invoice');
			$modSO=Vtiger_Module::getInstance('SalesOrder');
			$modPO=Vtiger_Module::getInstance('PurchaseOrder');
			$modQt=Vtiger_Module::getInstance('Quotes');
			
			$modPrd=Vtiger_Module::getInstance('Products');
			$modSrv=Vtiger_Module::getInstance('Services');
			
			if ($modAccounts) $modAccounts->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modContacts) $modContacts->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modVnd) $modVnd->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modInvoice) $modInvoice->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modSO) $modSO->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modPO) $modPO->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modQt) $modQt->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modPrd) $modPrd->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			if ($modSrv) $modSrv->setRelatedList($mod, 'InventoryDetails', Array(''),'get_dependents_list');
			
			$wfrs = $adb->query("SELECT workflow_id FROM com_vtiger_workflows WHERE summary='Line Completed'");
			if ($wfrs and $adb->num_rows($wfrs)==1) {
				echo 'Workfolw already exists!';
			} else {
				$workflowManager = new VTWorkflowManager($adb);
				$taskManager = new VTTaskManager($adb);
				
				$InvDtWorkFlow = $workflowManager->newWorkFlow("InventoryDetails");
				$InvDtWorkFlow->test = '[{"fieldname":"units_delivered_received","operation":"equal to","value":"quantity","valuetype":"fieldname","joincondition":"and","groupid":"0"}]';
				$InvDtWorkFlow->description = "Line Completed";
				$InvDtWorkFlow->executionCondition = VTWorkflowManager::$ON_EVERY_SAVE;
				$InvDtWorkFlow->defaultworkflow = 1;
				$workflowManager->save($InvDtWorkFlow);
				
				$task = $taskManager->createTask('VTUpdateFieldsTask', $InvDtWorkFlow->id);
				$task->active = true;
				$task->summary = 'Mark as Line Completed';
				$task->field_value_mapping = '[{"fieldname":"line_completed","valuetype":"rawtext","value":"true:boolean"}]';
				$taskManager->saveTask($task);
			}
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	public static function createInventoryDetails($related_focus, $module){
		global $adb,$log,$current_user,$currentModule;
		$save_currentModule = $currentModule;
		$currentModule = 'InventoryDetails';
		$related_to = $related_focus->id;
		$taxtype = getInventoryTaxType($module, $related_to);
		if($taxtype == 'group'){
			$query = "SELECT id as related_to, vtiger_inventoryproductrel.productid, sequence_no, lineitem_id, quantity, listprice, comment as description,
			quantity * listprice AS extgross,
			COALESCE( discount_percent, COALESCE( discount_amount *100 / ( quantity * listprice ) , 0 ) ) AS discount_percent,
			COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 ) ) AS discount_amount,
			(quantity * listprice) - COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 )) AS extnet,
			((quantity * listprice) - COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 ))) AS linetotal,
			case when vtiger_products.productid != '' then vtiger_products.cost_price else vtiger_service.cost_price end as cost_price,
			case when vtiger_products.productid != '' then vtiger_products.vendor_id else 0 end as vendor_id
			FROM vtiger_inventoryproductrel
			LEFT JOIN vtiger_products ON vtiger_products.productid=vtiger_inventoryproductrel.productid
			LEFT JOIN vtiger_service ON vtiger_service.serviceid=vtiger_inventoryproductrel.productid
			WHERE id = ?";
		}
		elseif($taxtype == 'individual'){
			$query = "SELECT id as related_to, vtiger_inventoryproductrel.productid, sequence_no, lineitem_id, quantity, listprice, comment as description,
			coalesce( tax1 , 0 ) AS tax1, coalesce( tax2 , 0 ) AS tax2, coalesce( tax3 , 0 ) AS tax3,
			( COALESCE( tax1, 0 ) + COALESCE( tax2, 0 ) + COALESCE( tax3, 0 ) ) as tax_percent,
			quantity * listprice AS extgross,
			COALESCE( discount_percent, COALESCE( discount_amount *100 / ( quantity * listprice ) , 0 ) ) AS discount_percent,
			COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 ) ) AS discount_amount,
			(quantity * listprice) - COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 )) AS extnet,
			((quantity * listprice) - COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 ))) * ( COALESCE( tax1, 0 ) + COALESCE( tax2, 0 ) + COALESCE( tax3, 0 ) ) /100 AS linetax,
			((quantity * listprice) - COALESCE( discount_amount, COALESCE( discount_percent * quantity * listprice /100, 0 ))) * ( 1 + ( COALESCE( tax1, 0 ) + COALESCE( tax2, 0 ) + COALESCE( tax3, 0 )) /100) AS linetotal,
			case when vtiger_products.productid != '' then vtiger_products.cost_price else vtiger_service.cost_price end as cost_price,
			case when vtiger_products.productid != '' then vtiger_products.vendor_id else 0 end as vendor_id
			FROM vtiger_inventoryproductrel
			LEFT JOIN vtiger_products ON vtiger_products.productid=vtiger_inventoryproductrel.productid
			LEFT JOIN vtiger_service ON vtiger_service.serviceid=vtiger_inventoryproductrel.productid
			WHERE id = ?";
		}
		$res_inv_lines = $adb->pquery($query,array($related_to));

		$accountid = '0';
		$contactid = '0';

		switch ($module) {
			case 'Quotes':
					$accountid = $related_focus->column_fields['account_id'];
					$contactid = $related_focus->column_fields['contact_id'];
				break;
			case 'SalesOrder':
					$accountid = $related_focus->column_fields['account_id'];
					$contactid = $related_focus->column_fields['contact_id'];
				break;
			case 'Invoice':
					$accountid = $related_focus->column_fields['account_id'];
					$contactid = $related_focus->column_fields['contact_id'];
				break;
			case 'Issuecards':
					$accountid = $related_focus->column_fields['accid'];
					$contactid = $related_focus->column_fields['ctoid'];
				break;
			case 'PurchaseOrder':
					$contactid = $related_focus->column_fields['contact_id'];
				break;
			default:
				
				break;
		}
		// Delete all InventoryDetails where related with $related_to
		$res_to_del = $adb->pquery('SELECT inventorydetailsid FROM vtiger_inventorydetails
			INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_inventorydetails.inventorydetailsid
			WHERE deleted = 0 AND related_to = ? and lineitem_id not in (select lineitem_id from vtiger_inventoryproductrel where id=?)', array($related_to,$related_to));
		while($invdrow = $adb->getNextRow($res_to_del,false))
		{
			$invdet_focus = new InventoryDetails();
			$invdet_focus->id = $invdrow['inventorydetailsid'];
			$invdet_focus->trash('InventoryDetails',$invdet_focus->id);
		}

		$requestindex = 1;
		while (isset($_REQUEST['deleted'.$requestindex]) and $_REQUEST['deleted'.$requestindex] == 1) {
			$requestindex++;
		}
		// read $res_inv_lines result to create a new InventoryDetail for each register.
		// Remember to take the Vendor if the Product is related with this.
		while ($row = $adb->getNextRow($res_inv_lines,false)) {
			$invdet_focus = array();
			$invdet_focus = new InventoryDetails();
			$rec_exists = $adb->pquery('SELECT inventorydetailsid FROM vtiger_inventorydetails
				INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_inventorydetails.inventorydetailsid
				WHERE deleted = 0 AND lineitem_id = ?', array($row['lineitem_id']));
			if ($adb->num_rows($rec_exists)>0) {
				$invdet_focus->id = $adb->query_result($rec_exists, 0, 0);
				$invdet_focus->retrieve_entity_info($invdet_focus->id, 'InventoryDetails');
				$invdet_focus->mode = 'edit';
			} else {
				$invdet_focus->id = '';
				$invdet_focus->mode = '';
			}

			foreach ($invdet_focus->column_fields as $fieldname => $val) {
				if (isset($_REQUEST[$fieldname.$requestindex])) {
					$invdet_focus->column_fields[$fieldname] = vtlib_purify($_REQUEST[$fieldname.$requestindex]);
				} elseif (isset($row[$fieldname])) {
					$invdet_focus->column_fields[$fieldname] = $row[$fieldname];
				}
			}
			$invdet_focus->column_fields['lineitem_id'] = $row['lineitem_id'];
			$_REQUEST['assigntype'] = 'U';
			$invdet_focus->column_fields['assigned_user_id'] = $current_user->id;
			$invdet_focus->column_fields['account_id'] = $accountid;
			$invdet_focus->column_fields['contact_id'] = $contactid;

			if($taxtype == 'group') {
				$invdet_focus->column_fields['tax_percent'] = 0;
				$invdet_focus->column_fields['linetax'] = 0;
			}
			$handler = vtws_getModuleHandlerFromName('InventoryDetails', $current_user);
			$meta = $handler->getMeta();
			$invdet_focus->column_fields = DataTransform::sanitizeRetrieveEntityInfo($invdet_focus->column_fields,$meta);
			$invdet_focus->save("InventoryDetails");
			$requestindex++;
			while (isset($_REQUEST['deleted'.$requestindex]) and $_REQUEST['deleted'.$requestindex] == 1) {
				$requestindex++;
			}
		}
		$currentModule = $save_currentModule;
	}
}
?>
