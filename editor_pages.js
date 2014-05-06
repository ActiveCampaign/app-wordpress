var acwm = null;
var wp_version = php_data.wp_version;
var wp_version_3_8 = wp_version.match(/^3\.8/);

function activecampaign_editor_form_embed(form_id) {
	// puts the [activecampaign form=#] shortcode into the body of the post.
	if (wp_version_3_8) {
		var return_text = "[activecampaign form=" + form_id + "]";
		tinymce.execCommand("mceInsertContent", 0, return_text);
		$("#activecampaign_editor_forms").dialog("close");
	} else {
		acwm.close(); // closes the pop-up modal.
		var return_text = "[activecampaign form=" + form_id + "]";
		tinyMCE.activeEditor.insertContent(return_text);
	}
}

function activecampaign_editor_form_dialog() {
	// runs when you click the ActiveCampaign icon in the TinyMCE toolbar.
	if (wp_version_3_8) {
		$("#activecampaign_editor_forms").dialog({
			title: "Insert ActiveCampaign Form"
		});
	} else {
		acwm = tinyMCE.activeEditor.windowManager.open({
			title: "Insert ActiveCampaign Form",
			url: ajaxurl + "?action=activecampaign_get_forms_html",
			width: 400,
			height: 300,
			inline: 1
		});
	}
}

jQuery(document).ready(function($) {

	if (wp_version_3_8) {

		var editor_forms = "<div id='activecampaign_editor_forms' style='display: none;'></div>";

		var ajaxdata = {
			action: "activecampaign_get_forms"
		};

		$.ajax({
			url: ajaxurl,
			type: "GET",
			data: ajaxdata,
			error: function(jqXHR, textStatus, errorThrown) {
				console.log(errorThrown);
			},
			success: function(data) {
				data = JSON.parse(data);
				if (typeof(data.length) == "undefined") {
					// when there is data, data.length returns undefined for some reason.
					var editor_forms = "<ul>";
					for (var i in data) {
						if (typeof(data[i]) != "function") {
							editor_forms += "<li><a href='#' onclick='activecampaign_editor_form_embed(" + i + "); return false;'>" + data[i] + "</a></li>";
						}
					}
					editor_forms += "</ul>";
				}
				else if (data.length == 0) {
					var editor_forms = "<p>No forms chosen yet. Go to the <a href='options-general.php?page=activecampaign'>ActiveCampaign Settings page</a> to choose your forms.</p>";
				}
				$("#activecampaign_editor_forms").html(editor_forms);
			}
		});

		$("body").append(editor_forms);

	}

});
