<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GFPersian_DB_payping {

	private static $method = 'payping';

	public static function update_table() {
		global $wpdb;
	
		$table_name = self::get_table_name();
	
		$old_table = $wpdb->prefix . "rg_payping";
		if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $old_table))) {
			$wpdb->query($wpdb->prepare("RENAME TABLE %s TO %s", $old_table, $table_name));
		}
	
		$charset_collate = '';
		if (!empty($wpdb->charset)) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}
		if (!empty($wpdb->collate)) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}
	
		$feed = "CREATE TABLE IF NOT EXISTS $table_name (
				  id mediumint(8) unsigned not null auto_increment,
				  form_id mediumint(8) unsigned not null,
				  is_active tinyint(1) not null default 1,
				  meta longtext,
				  PRIMARY KEY  (id),
				  KEY form_id (form_id)
		) $charset_collate;";
	
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($feed);
	}
	

	public static function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . "gf_payping";
	}

	public static function get_entry_table_name() {

		$version = GFCommon::$version;
		if ( method_exists( 'GFFormsModel', 'get_database_version' ) ) {
			$version = GFFormsModel::get_database_version();
		}

		return version_compare( $version, '2.3-dev-1', '<' ) ? GFFormsModel::get_lead_table_name() : GFFormsModel::get_entry_table_name();
	}

	public static function drop_tables() {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS " . self::get_table_name() );
	}

	public static function get_available_forms() {
		$forms           = RGFormsModel::get_forms();
		$available_forms = array();
		foreach ( $forms as $form ) {
			$available_forms[] = $form;
		}

		return $available_forms;
	}

	public static function get_feed( $id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$sql        = $wpdb->prepare( "SELECT id, form_id, is_active, meta FROM $table_name WHERE id=%d", $id );
		$results    = $wpdb->get_results( $sql, ARRAY_A );
		if ( empty( $results ) ) {
			return array();
		}
		$result         = $results[0];
		$result["meta"] = maybe_unserialize( $result["meta"] );

		return $result;
	}

	public static function get_feeds() {
		global $wpdb;
		$table_name      = self::get_table_name();
		$form_table_name = RGFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
                FROM $table_name s
                INNER JOIN $form_table_name f ON s.form_id = f.id";
		$results         = $wpdb->get_results( $sql, ARRAY_A );
		$count           = sizeof( $results );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]["meta"] = maybe_unserialize( $results[ $i ]["meta"] );
		}

		return $results;
	}

	public static function get_feed_by_form( $form_id, $only_active = false ) {
		global $wpdb;
		$table_name    = self::get_table_name();
		$active_clause = $only_active ? " AND is_active=1" : "";
		$sql           = $wpdb->prepare( "SELECT id, form_id, is_active, meta FROM $table_name WHERE form_id=%d $active_clause", $form_id );
		$results       = $wpdb->get_results( $sql, ARRAY_A );
		if ( empty( $results ) ) {
			return array();
		}
		$count = sizeof( $results );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]["meta"] = maybe_unserialize( $results[ $i ]["meta"] );
		}

		return $results;
	}

	public static function update_feed( $id, $form_id, $is_active, $setting ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$setting    = maybe_serialize( $setting );
		if ( $id == 0 ) {
			$wpdb->insert( $table_name, array(
				"form_id"   => $form_id,
				"is_active" => $is_active,
				"meta"      => $setting
			), array( "%d", "%d", "%s" ) );
			$id = $wpdb->get_var( "SELECT LAST_INSERT_ID()" );
		} else {
			$wpdb->update( $table_name, array(
				"form_id"   => $form_id,
				"is_active" => $is_active,
				"meta"      => $setting
			), array( "id" => $id ), array( "%d", "%d", "%s" ), array( "%d" ) );
		}

		return $id;
	}

	public static function delete_feed( $id ) {
		global $wpdb;
		$table_name = self::get_table_name();
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE id=%s", $id ) );
	}

	//----جمع پرداخت های  این درگاه این فرم----------
	public static function get_transaction_totals( $form_id ) {
		global $wpdb;
		$entry_table_name = self::get_entry_table_name();
		$sql              = $wpdb->prepare( " SELECT l.status, sum(l.payment_amount) revenue, count(l.id) transactions
                                 FROM {$entry_table_name} l
                                 WHERE l.form_id=%d AND l.status=%s AND l.is_fulfilled=%d AND l.payment_method=%s
                                 GROUP BY l.status", $form_id, 'active', 1, self::$method );
		$results          = $wpdb->get_results( $sql, ARRAY_A );
		$totals           = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$totals[ $result["status"] ] = array(
					"revenue"      => empty( $result["revenue"] ) ? 0 : $result["revenue"],
					"transactions" => empty( $result["transactions"] ) ? 0 : $result["transactions"]
				);
			}
		}

		return $totals;
	}

	//-----جمع پرداخت های  همه فرمهای این درگاه-----------
	public static function get_transaction_totals_this_gateway() {
		global $wpdb;
		$entry_table_name = self::get_entry_table_name();
		$sql              = $wpdb->prepare( " SELECT l.status, sum(l.payment_amount) revenue, count(l.id) transactions
                                 FROM {$entry_table_name} l
                                 WHERE l.status=%s AND l.is_fulfilled=%d AND l.payment_method=%s
                                 GROUP BY l.status", 'active', 1, self::$method );
		$results          = $wpdb->get_results( $sql, ARRAY_A );
		$totals           = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$totals[ $result["status"] ] = array(
					"revenue"      => empty( $result["revenue"] ) ? 0 : $result["revenue"],
					"transactions" => empty( $result["transactions"] ) ? 0 : $result["transactions"]
				);
			}
		}

		return $totals;
	}

	//-----جمع پرداخت های  همه روشهای این فرم---------------
	public static function get_transaction_totals_gateways( $form_id ) {
		global $wpdb;
		$entry_table_name = self::get_entry_table_name();
		$sql              = $wpdb->prepare( " SELECT l.status, sum(l.payment_amount) revenue, count(l.id) transactions
                                 FROM {$entry_table_name} l
                                 WHERE l.form_id=%d AND l.status=%s AND l.is_fulfilled=%d
                                 GROUP BY l.status", $form_id, 'active', 1 );
		$results          = $wpdb->get_results( $sql, ARRAY_A );
		$totals           = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$totals[ $result["status"] ] = array(
					"revenue"      => empty( $result["revenue"] ) ? 0 : $result["revenue"],
					"transactions" => empty( $result["transactions"] ) ? 0 : $result["transactions"]
				);
			}
		}

		return $totals;
	}

	//-----جمع کل پرداخت های همه فرمهای سایت-------------
	public static function get_transaction_totals_site() {
		global $wpdb;
		$entry_table_name = self::get_entry_table_name();
		$sql              = $wpdb->prepare( " SELECT l.status, sum(l.payment_amount) revenue, count(l.id) transactions
                                 FROM {$entry_table_name} l
                                 WHERE l.status=%s AND l.is_fulfilled=%d
                                 GROUP BY l.status", 'active', 1 );
		$results          = $wpdb->get_results( $sql, ARRAY_A );
		$totals           = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$totals[ $result["status"] ] = array(
					"revenue"      => empty( $result["revenue"] ) ? 0 : $result["revenue"],
					"transactions" => empty( $result["transactions"] ) ? 0 : $result["transactions"]
				);
			}
		}

		return $totals;
	}

}