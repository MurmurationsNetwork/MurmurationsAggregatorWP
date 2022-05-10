import React from 'react';

function NodeField(props){

  if(!props.value){
    return null;
  }

  if(props.value === Object(props.value) && !Array.isArray(props.value)){
    return null;
  }

  var value = props.value;

  const { field, attribs, nodeData } = props;

  if(Array.isArray(value)){
    value = value.join(", ");
  }

  var labelElement = '';
  if(attribs.showLabel === true){
    var labelValue = attribs.label;
    labelElement = <div className={"node-field-label "+field}>{labelValue}</div>
  }else{
    labelElement = '';
  }

  if(attribs.truncate){
    if (value.length > attribs.truncate){
      value = value.slice(0, attribs.truncate) + '...'
    }
  }

  if(attribs.link){
    value = <a href={nodeData[attribs.link]}>{value}</a>
  }

  return(
    <div className={"node-field "+field}>
      {labelElement}
      <div className={"node-field-value "+field}>
      {value}
      </div>
    </div>
  );

}

export default NodeField
