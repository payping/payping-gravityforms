
    jQuery(document).ready(function () {
	    jQuery(".datepicker").datepicker({
			dateFormat: "yy-mm-dd",
			showButtonPanel: true,
			changeMonth: true,
			changeYear: true
		});
	});
    function DeleteSetting(id) {
        jQuery("#action_argument").val(id);
        jQuery("#action").val("delete");
        jQuery("#confirmation_list_form")[0].submit();
    }
    
    function ToggleActive(img, feed_id) {
        var is_active = img.src.indexOf("active1.png") >= 0;
        if (is_active) {
            img.src = img.src.replace("active1.png", "active0.png");
            jQuery(img).attr('title', myLocalizedData.deactivate_message).attr('alt', myLocalizedData.deactivate_message);
        } else {
            img.src = img.src.replace("active0.png", "active1.png");
            jQuery(img).attr('title', myLocalizedData.activate_message).attr('alt', myLocalizedData.activate_message);
        }
        var mysack = new sack(myLocalizedData.ajaxurl);
        mysack.execute = 1;
        mysack.method = 'POST';
        mysack.setVar("action", "gf_payping_update_feed_active");
        mysack.setVar("gf_payping_update_feed_active", myLocalizedData.gf_payping_update_feed_active_nonce);
        mysack.setVar("feed_id", feed_id);
        mysack.setVar("is_active", is_active ? 0 : 1);
        mysack.onError = function () {
            alert(myLocalizedData.ajax_error_message);
        };
        mysack.runAJAX();
        return true;
    }
    