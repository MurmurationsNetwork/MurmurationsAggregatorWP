
function viewLocalSchema(){

  logContainer = document.getElementById('murmagg-admin-form-log-container');

  logContainer.style.display = "block";

  logContainer.value = "Fetching local schema...\n";


  var data = {
	  'action': 'get_local_schema'
	};

  jQuery.ajax({
    type: "POST",
    url: ajaxurl,
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
    'node' : node
  };

  node_update_log("Fetching node " + (index + 1).toString() + " of " + nodes.length.toString());

  stats.queried += 1;

  jQuery.ajax({
    type: "POST",
    url: ajaxurl,
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
          'action': 'set_update_time'
        };

        jQuery.post(ajaxurl, data, function(response) {
          if(response.status.toString() == 'success'){
            node_update_log("Set update time");
          } else {
            node_update_log("Failed to set update time!");
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
	  'action': 'get_index_nodes'
	};

	jQuery.post(ajaxurl, data, function(response) {

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
