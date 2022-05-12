import React from 'react';
import NodeField from './NodeField.js';

function Node(props){

    var node_content = [];

    if(props.settings.apiNodeFormat == 'HTML'){
      node_content = <div dangerouslySetInnerHTML={{ __html: props.nodeData }} />
    }else if(props.settings.directoryDisplaySchema){
      for (var field in props.settings.directoryDisplaySchema) {
        if (props.settings.directoryDisplaySchema.hasOwnProperty(field)) {
          node_content.push(
            <NodeField
            field={field}
            value={props.nodeData[field]}
            attribs = {props.settings.directoryDisplaySchema[field]}
            nodeData = {props.nodeData}
            />
          )

        }
      }
    }

    return(
      <div className={"directory-node"}>
      {node_content}
      </div>
    );

}

export default Node
