
function viewLocalSchema(){

  logContainer = document.getElementById('murmagg-admin-form-log-container');

  logContainer.style.display = "block";

  logContainer.value = "Fetching local schema...\n";


  var data = {
	  'action' : 'get_local_schema',
    'nonce' : murmurmurations_aggregator_admin.ajaxnonce
	};

  jQuery.ajax({
    type: "POST",
    url: murmurmurations_aggregator_admin.ajaxurl,
    data: data,
    success: function(response) {
      if(response.status.toString() == 'success'){
        node_update_log(JSON.stringify(response.schema));
      } else {
        node_update_log("Failed to get schema");
      }
    }
  });
}

function recursiveNodeUpdate(nodes, index){

  var node = nodes[index];

  var data = {
    'action': 'update_node',
    'nonce' : murmurmurations_aggregator_admin.ajaxnonce,
    'node' : node
  };

  node_update_log("Fetching node " + (index + 1).toString() + " of " + nodes.length.toString());

  stats.queried += 1;

  jQuery.ajax({
    type: "POST",
    url: murmurmurations_aggregator_admin.ajaxurl,
    data: data,
    success: function(response) {

      if(response.status.toString() == 'success'){
        stats.success += 1;
      } else {
        stats.failed += 1;
      }

      for (const message of response.messages){
        node_update_log(message.message.toString());
      }
      node_update_log("*** " + response.status.toString() + " *** \n");

      if(nodes.length > (index + 1)){
        recursiveNodeUpdate(nodes,(index + 1))
      }else{

        var data = {
          'action': 'wrap_up_nodes_update',
			    'nonce' : murmurmurations_aggregator_admin.ajaxnonce,
        };

        jQuery.post(murmurmurations_aggregator_admin.ajaxurl, data, function(response) {
          if(response.status.toString() == 'success'){
            node_update_log("Set update time and updated filter options");
          } else {
            node_update_log("Failed to set update time or update filter options!");
          }
        });

        node_update_log("Nodes queried: " + stats.queried.toString() );
        node_update_log("Nodes failed: " + stats.failed.toString() );
        node_update_log("Nodes saved: " + stats.success.toString() );

      }
    }
  });

}

function ajaxUpdateNodes(){

	logContainer = document.getElementById('murmagg-admin-form-log-container');

	logContainer.style.display = "block";

  logContainer.value = "Fetching index data...\n";

	var data = {
	  'action': 'get_index_nodes',
    'nonce' : murmurmurations_aggregator_admin.ajaxnonce
	};

	jQuery.post(murmurmurations_aggregator_admin.ajaxurl, data, function(response) {

	  for (const message of response.messages){
      node_update_log(message.message.toString());
	  }

    if(response.nodes.length > 0){

      stats = {
        success : 0,
        failed : 0,
        queried : 0
      };

      node_update_log("Fetching " + response.nodes.length.toString() + " nodes...\n");

      recursiveNodeUpdate(response.nodes, 0);

    } else {
      node_update_log("No nodes found at index");
    }
	});

  return false;

}

function node_update_log(message){
  var logContainer = document.getElementById('murmagg-admin-form-log-container');
  logContainer.value += message + "\n";
}


const murmagAdminFormSubmit = (Form, e) => {

	formOverlay = document.getElementById('murmagg-admin-form-overlay');

	formOverlay.style.visibility = "visible";

	// Copy the data so we can modify it without screwing up the form,
	// using the bizarre nonsense that JS requires to do this...
	var ajaxFormData = JSON.parse(JSON.stringify(Form.formData));

	for (field in Form.formData){
		if(typeof(Form.formData[field]) == 'object'){
			if(Object.keys(Form.formData[field]).length === 0){
				ajaxFormData[field] = "empty_object";
			}
		}
		if(typeof(Form.formData[field]) == 'array'){
			if(Array.keys(Form.formData[field]).length === 0){
				ajaxFormData[field] = "empty_array";
			}
		}
		if(typeof(Form.formData[field]) == 'string'){
			if(Form.formData[field].trim() == ""){
				ajaxFormData[field] = "empty_string";
			}
		}

		/*
		console.log("Field", field);
		console.log("Type", Form.schema.properties[field].type);
		console.log("All properties", Form.schema.properties);
		*/

		if(Form.schema.properties[field].type == 'string'){
			if(typeof(Form.formData[field]) == 'undefined'){
				ajaxFormData[field] = "empty_string";
			}
		}
	}

	var data = {
		'action': 'save_settings',
		'formData': ajaxFormData,
		'nonce' : murmurmurations_aggregator_admin.ajaxnonce
	};

	jQuery.post(murmurmurations_aggregator_admin.ajaxurl, data, function(response) {

		formOverlay.style.visibility = "hidden";
		var noticeContainer = document.getElementById('murmagg-admin-form-notice');

		noticeContainer.innerHTML = "";

		for (const message of response.messages){
			var notice = document.createElement("div");
			notice.innerHTML = '<p>'+message.message+'</p>';
			notice.className = "notice notice-"+message.type;
			noticeContainer.appendChild(notice);
		}
		noticeContainer.style.display = "block";
	});
}
